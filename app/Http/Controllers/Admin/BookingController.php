<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BookingController extends Controller
{
    // ── Liste des réservations ────────────────────────────────────────────────
    public function index(Request $request)
    {
        $bookings = Booking::with([
                'user',
                'property.owner.ownerProfile',
                'payment',
            ])
            ->where('status', '!=', 'pending_payment') // ← exclure réservations sans preuve de paiement
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(15);

        return view('admin.bookings.index', compact('bookings'));
    }

    // ── Détail d'une réservation ──────────────────────────────────────────────
    public function show(string $ref)
    {
        $booking = Booking::with([
                'user',
                'property.images',
                'property.owner.ownerProfile',
                'payment',
                'review',
            ])
            ->where('reference', $ref)
            ->firstOrFail();

        return view('admin.bookings.show', compact('booking'));
    }

    // ── Confirmer ─────────────────────────────────────────────────────────────
    public function confirm(string $ref)
    {
        Booking::where('reference', $ref)->firstOrFail()
            ->update(['status' => 'confirmé']);
        return back()->with('success', 'Réservation confirmée.');
    }

    // ── Marquer comme terminée ────────────────────────────────────────────────
    public function complete(string $ref)
    {
        Booking::where('reference', $ref)->firstOrFail()
            ->update(['status' => 'terminé']);
        return back()->with('success', 'Réservation marquée comme terminée.');
    }

    // ── Annuler ───────────────────────────────────────────────────────────────
    public function cancel(Request $request, string $ref): RedirectResponse
    {
        $booking = Booking::where('reference', $ref)->firstOrFail();

        if (!in_array($booking->status, ['en_attente', 'confirmé'])) {
            return back()->with('error',
                "La réservation {$ref} ne peut pas être annulée (statut : {$booking->status}).");
        }

        $booking->update([
            'status'        => 'annulé',
            'cancel_reason' => $request->input('reason', "Annulation par l'administrateur"),
            'cancelled_at'  => now(),
        ]);

        if ($booking->payment && $booking->payment->status === 'succès') {
            $booking->payment->update([
                'status'        => 'remboursé',
                'refund_reason' => $request->input('reason', 'Annulation admin'),
                'refunded_at'   => now(),
            ]);
        }

        if (class_exists(\App\Models\Notification::class)) {
            \App\Models\Notification::create([
                'user_id' => $booking->user_id,
                'title'   => 'Réservation annulée',
                'body'    => "Votre réservation {$ref} a été annulée."
                             . ($request->input('reason') ? ' Motif : ' . $request->input('reason') : ''),
                'type'    => 'booking',
                'data'    => ['booking_id' => $booking->id],
            ]);
        }

        return redirect()->route('admin.bookings.index')
            ->with('success', "Réservation {$ref} annulée avec succès.");
    }

    // ── EXPORT EXCEL (.xlsx) ──────────────────────────────────────────────────
    public function exportCsv(Request $request): Response
    {
        $bookings = Booking::with([
                'user',
                'property.owner.ownerProfile',
                'payment',
            ])
            ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('check_in', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('check_in', '<=', $v))
            ->latest()
            ->get();

        $filename = 'reservations_' . now()->format('Ymd_His') . '.xlsx';
        $xlsx     = $this->buildXlsx($bookings);

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length'      => strlen($xlsx),
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    // ── Générateur XLSX pur PHP (sans dépendance) ─────────────────────────────
    private function buildXlsx($bookings): string
    {
        $headers = [
            'Référence', 'Client', 'Téléphone', 'Propriété', 'Ville',
            'Arrivée', 'Départ', 'Durée', 'Unité', 'Voyageurs',
            'Montant base (XAF)', 'Frais (XAF)', 'Total (XAF)',
            'Commission (%)', 'Commission Tholad (XAF)', 'Reversé propriétaire (XAF)',
            'Statut réservation', 'Statut paiement', 'Créée le',
        ];

        $rows = [];
        foreach ($bookings as $b) {
            $rows[] = [
                $b->reference                                           ?? '',
                $b->user?->name                                         ?? '—',
                $b->user?->phone                                        ?? '—',
                $b->property?->title                                    ?? '—',
                $b->property?->city                                     ?? '—',
                $b->check_in?->format('d/m/Y')                         ?? '—',
                $b->check_out?->format('d/m/Y')                        ?? '—',
                (int)($b->nights ?? 1),
                $this->durationUnitLabel($b->property?->price_period ?? 'nuit', (int)($b->nights ?? 1)),
                (int)($b->guests ?? 0),
                (float)($b->base_amount ?? 0),
                (float)($b->fees_amount ?? 0),
                (float)($b->total_amount ?? 0),
                (float)($b->commission_rate ?? 0),
                (float)($b->owner_commission_amount ?? $b->commission_amount ?? 0),
                (float)($b->owner_amount ?? 0),
                $b->status                                              ?? '—',
                $b->payment?->status                                    ?? 'non_payé',
                $b->created_at?->format('d/m/Y H:i')                   ?? '—',
            ];
        }

        return $this->generateXlsx($headers, $rows, 'Réservations TholadImmo');
    }

    // ── Génération XLSX via format OpenXML (ZIP de XML) ───────────────────────
    private function generateXlsx(array $headers, array $rows, string $sheetTitle): string
    {
        // Colonnes numériques (index 0-based)
        $numericCols = [7, 9, 10, 11, 12, 13, 14, 15];

        // ── shared strings ─────────────────────────────────────────────────────
        $allStrings = [];
        $strIndex   = [];

        $addStr = function (string $s) use (&$allStrings, &$strIndex): int {
            if (!isset($strIndex[$s])) {
                $strIndex[$s] = count($allStrings);
                $allStrings[] = $s;
            }
            return $strIndex[$s];
        };

        // ── feuille XML ────────────────────────────────────────────────────────
        $sheetRows = '';

        // Ligne d'en-tête (style 1 = gras fond bleu)
        $sheetRows .= '<row r="1">';
        foreach ($headers as $ci => $h) {
            $col  = $this->colLetter($ci) . '1';
            $si   = $addStr((string)$h);
            $sheetRows .= "<c r=\"{$col}\" t=\"s\" s=\"1\"><v>{$si}</v></c>";
        }
        $sheetRows .= '</row>';

        // Lignes de données
        foreach ($rows as $ri => $row) {
            $rowNum = $ri + 2;
            $sheetRows .= "<row r=\"{$rowNum}\">";
            foreach ($row as $ci => $val) {
                $col = $this->colLetter($ci) . $rowNum;
                if (in_array($ci, $numericCols)) {
                    // Nombre → style 2 (format nombre)
                    $sheetRows .= "<c r=\"{$col}\" s=\"2\"><v>" . (float)$val . "</v></c>";
                } else {
                    $si = $addStr((string)$val);
                    $sheetRows .= "<c r=\"{$col}\" t=\"s\"><v>{$si}</v></c>";
                }
            }
            $sheetRows .= '</row>';
        }

        // Largeurs colonnes
        $colsXml = '<cols>';
        $widths  = [18,18,16,22,16,12,12,8,10,10,20,12,14,14,22,24,20,16,18];
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $colsXml .= "<col min=\"{$n}\" max=\"{$n}\" width=\"{$w}\" customWidth=\"1\"/>";
        }
        $colsXml .= '</cols>';

        $lastCol = $this->colLetter(count($headers) - 1);
        $lastRow = count($rows) + 1;
        $ref     = "A1:{$lastCol}{$lastRow}";

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . $colsXml
            . '<sheetData>' . $sheetRows . '</sheetData>'
            . '<autoFilter ref="' . $ref . '"/>'
            . '</worksheet>';

        // ── Shared strings XML ─────────────────────────────────────────────────
        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' count="' . count($allStrings) . '" uniqueCount="' . count($allStrings) . '">';
        foreach ($allStrings as $s) {
            $ssXml .= '<si><t xml:space="preserve">' . htmlspecialchars($s, ENT_XML1, 'UTF-8') . '</t></si>';
        }
        $ssXml .= '</sst>';

        // ── Styles XML (header bleu + nombre) ────────────────────────────────
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            .   '<fill><patternFill patternType="none"/></fill>'
            .   '<fill><patternFill patternType="gray125"/></fill>'
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF1D6FA4"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            .   '<border><left/><right/><top/><bottom/><diagonal/></border>'
            .   '<border><left style="thin"><color rgb="FFD0D0D0"/></left>'
            .     '<right style="thin"><color rgb="FFD0D0D0"/></right>'
            .     '<top style="thin"><color rgb="FFD0D0D0"/></top>'
            .     '<bottom style="thin"><color rgb="FFD0D0D0"/></bottom>'
            .   '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center"/></xf>'
            .   '<xf numFmtId="4"  fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';

        // ── Workbook XML ──────────────────────────────────────────────────────
        $escapedTitle = htmlspecialchars($sheetTitle, ENT_XML1, 'UTF-8');
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $escapedTitle . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
            . ' Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings"'
            . ' Target="sharedStrings.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>'
            . '</Relationships>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"'
            . ' Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml"  ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml"'
            .   ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml"'
            .   ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml"'
            .   ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml"'
            .   ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        // ── Assemblage ZIP en mémoire ─────────────────────────────────────────
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip     = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',          $contentTypesXml);
        $zip->addFromString('_rels/.rels',                  $relsXml);
        $zip->addFromString('xl/workbook.xml',              $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels',   $wbRelsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml',     $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml',         $ssXml);
        $zip->addFromString('xl/styles.xml',                $stylesXml);

        $zip->close();

        $bytes = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $bytes;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = intdiv($index, 26);
        }
        return $letter;
    }

    private function durationUnitLabel(string $pricePeriod, int $duration): string
    {
        $singular = match ($pricePeriod) {
            'heure'   => 'heure',
            'jour'    => 'jour',
            'semaine' => 'semaine',
            'mois'    => 'mois',
            'an'      => 'an',
            default   => 'nuit',
        };
        $plural = match ($pricePeriod) {
            'heure'   => 'heures',
            'jour'    => 'jours',
            'semaine' => 'semaines',
            'mois'    => 'mois',
            'an'      => 'ans',
            default   => 'nuits',
        };
        return $duration <= 1 ? "{$duration} {$singular}" : "{$duration} {$plural}";
    }
}
