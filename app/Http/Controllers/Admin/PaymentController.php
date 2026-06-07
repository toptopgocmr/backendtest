<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ZipArchive;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    // ── Valeurs enum exactes (avec accents, identiques à la migration) ────────
    const STATUS_SUCCES    = 'succès';
    const STATUS_ECHOUE    = 'échoué';
    const STATUS_REMBOURSE = 'remboursé';

    // ── Liste des paiements ───────────────────────────────────────────────────
    public function index(Request $request)
    {
        $payments = Payment::with(['user', 'booking.property'])
            ->when($request->method,    fn ($q, $v) => $q->where('method', $v))
            ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
            // ✅ FIX Bug 1 : les filtres date_from / date_to étaient absents de index()
            //    alors qu'ils existent dans le formulaire Blade et dans exportCsv().
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate(15);

        $stats = [
            'success_amount' => Payment::where('status', self::STATUS_SUCCES)->sum('amount'),
            'pending_count'  => Payment::whereIn('status', ['en_attente', 'en_attente_confirmation'])->count(),
            'failed_count'   => Payment::where('status', self::STATUS_ECHOUE)->count(),
            'refunded_count' => Payment::where('status', self::STATUS_REMBOURSE)->count(),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    // ── ✅ VALIDER PAIEMENT ───────────────────────────────────────────────────
    public function validatePayment($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === self::STATUS_SUCCES) {
            return back()->with('info', 'Ce paiement est déjà validé.');
        }

        $payment->update([
            'status'      => self::STATUS_SUCCES,
            'paid_at'     => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $booking = $payment->booking;
        if ($booking) {
            $booking->update(['status' => 'confirmé']);
        }

        Notification::create([
            'user_id' => $payment->user_id,
            'title'   => 'Paiement validé ✅',
            'body'    => "Votre paiement de {$payment->formatted_amount} a été confirmé. Votre réservation est active.",
            'type'    => 'payment',
            'data'    => ['payment_id' => $payment->id, 'booking_id' => $payment->booking_id],
        ]);

        return back()->with('success', 'Paiement validé avec succès.');
    }

    // ── ❌ REFUSER PAIEMENT ───────────────────────────────────────────────────
    public function rejectPayment(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $payment = Payment::findOrFail($id);

        if ($payment->status === self::STATUS_ECHOUE) {
            return back()->with('info', 'Ce paiement est déjà refusé.');
        }

        $payment->update([
            'status'        => self::STATUS_ECHOUE,
            'refund_reason' => $request->reason,
            'admin_note'    => $request->reason,
        ]);

        $payment->booking?->update(['status' => 'en_attente']);

        Notification::create([
            'user_id' => $payment->user_id,
            'title'   => 'Paiement refusé ❌',
            'body'    => "Votre paiement a été refusé."
                         . ($request->reason ? " Motif : {$request->reason}" : ''),
            'type'    => 'payment',
            'data'    => ['payment_id' => $payment->id, 'booking_id' => $payment->booking_id],
        ]);

        return back()->with('error', 'Paiement refusé.');
    }

    // ── 💸 REMBOURSEMENT ──────────────────────────────────────────────────────
    public function refund(Request $request, string $ref)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $payment = Payment::where('reference', $ref)->firstOrFail();

        if ($payment->status !== self::STATUS_SUCCES) {
            return back()->with('error', 'Seuls les paiements validés peuvent être remboursés.');
        }

        $payment->update([
            'status'        => self::STATUS_REMBOURSE,
            'refund_reason' => $request->reason,
            'refunded_at'   => now(),
        ]);

        $payment->booking?->update(['status' => 'annulé']);

        Notification::create([
            'user_id' => $payment->user_id,
            'title'   => 'Remboursement effectué 💸',
            'body'    => "Votre paiement de {$payment->formatted_amount} a été remboursé.",
            'type'    => 'payment',
        ]);

        return back()->with('success', 'Remboursement effectué.');
    }

    // ── 📄 EXPORT CSV ─────────────────────────────────────────────────────────
    public function exportCsv(Request $request): Response
    {
        $payments = Payment::with(['user', 'booking.property'])
            ->when($request->method,    fn ($q, $v) => $q->where('method', $v))
            ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->get();

        $filename = 'paiements_' . now()->format('Ymd_His') . '.xlsx';
        $xlsx     = $this->buildXlsx($payments);

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length'      => strlen($xlsx),
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    private function buildXlsx($payments): string
    {
        $headers = [
            'Référence paiement', 'Référence réservation', 'Client',
            'Téléphone client', 'Propriété', 'Méthode', 'Tél. utilisé',
            'ID transaction', 'Montant (XAF)', 'Statut',
            'Date soumission', 'Date validation',
        ];

        $rows = [];
        foreach ($payments as $p) {
            $rows[] = [
                $p->reference                              ?? "PAY-{$p->id}",
                $p->booking?->reference                    ?? "BK-{$p->booking_id}",
                $p->user?->name                            ?? '—',
                $p->user?->phone                           ?? '—',
                $p->booking?->property?->title             ?? '—',
                $p->method_label                           ?? $p->method,
                $p->phone                                  ?? '—',
                $p->provider_ref                           ?? '—',
                (float)($p->amount                        ?? 0),
                $p->status_label                           ?? $p->status,
                $p->created_at?->format('d/m/Y H:i')     ?? '—',
                $p->verified_at?->format('d/m/Y H:i')    ?? '—',
            ];
        }

        return $this->generateXlsx($headers, $rows, 'Paiements TholadImmo');
    }

    private function generateXlsx(array $headers, array $rows, string $sheetTitle): string
    {
        $numericCols = [8]; // Montant

        $allStrings = [];
        $strIndex   = [];
        $addStr = function (string $s) use (&$allStrings, &$strIndex): int {
            if (!isset($strIndex[$s])) {
                $strIndex[$s] = count($allStrings);
                $allStrings[] = $s;
            }
            return $strIndex[$s];
        };

        $sheetRows = '<row r="1">';
        foreach ($headers as $ci => $h) {
            $col = $this->colLetter($ci) . '1';
            $si  = $addStr((string)$h);
            $sheetRows .= "<c r=\"{$col}\" t=\"s\" s=\"1\"><v>{$si}</v></c>";
        }
        $sheetRows .= '</row>';

        foreach ($rows as $ri => $row) {
            $rowNum     = $ri + 2;
            $sheetRows .= "<row r=\"{$rowNum}\">";
            foreach ($row as $ci => $val) {
                $col = $this->colLetter($ci) . $rowNum;
                if (in_array($ci, $numericCols)) {
                    $sheetRows .= "<c r=\"{$col}\" s=\"2\"><v>" . (float)$val . "</v></c>";
                } else {
                    $si = $addStr((string)$val);
                    $sheetRows .= "<c r=\"{$col}\" t=\"s\"><v>{$si}</v></c>";
                }
            }
            $sheetRows .= '</row>';
        }

        $colsXml = '<cols>';
        $widths  = [20,20,18,16,22,14,16,20,16,20,18,18];
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $colsXml .= "<col min=\"{$n}\" max=\"{$n}\" width=\"{$w}\" customWidth=\"1\"/>";
        }
        $colsXml .= '</cols>';

        $lastCol = $this->colLetter(count($headers) - 1);
        $lastRow = count($rows) + 1;

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . $colsXml
            . '<sheetData>' . $sheetRows . '</sheetData>'
            . '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>'
            . '</worksheet>';

        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' count="' . count($allStrings) . '" uniqueCount="' . count($allStrings) . '">';
        foreach ($allStrings as $s) {
            $ssXml .= '<si><t xml:space="preserve">' . htmlspecialchars($s, ENT_XML1, 'UTF-8') . '</t></si>';
        }
        $ssXml .= '</sst>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1D6FA4"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD0D0D0"/></left>'
            . '<right style="thin"><color rgb="FFD0D0D0"/></right>'
            . '<top style="thin"><color rgb="FFD0D0D0"/></top>'
            . '<bottom style="thin"><color rgb="FFD0D0D0"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            . '</cellXfs></styleSheet>';

        $escapedTitle = htmlspecialchars($sheetTitle, ENT_XML1, 'UTF-8');
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $escapedTitle . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip     = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',        $contentTypesXml);
        $zip->addFromString('_rels/.rels',                $relsXml);
        $zip->addFromString('xl/workbook.xml',            $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRelsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml',   $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml',       $ssXml);
        $zip->addFromString('xl/styles.xml',              $stylesXml);
        $zip->close();

        $bytes = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $bytes;
    }

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

    // ── 🖨️ REÇU DÉFINITIF ────────────────────────────────────────────────────
    public function receipt($id)
    {
        $payment = Payment::with(['user', 'booking.property'])->findOrFail($id);

        if ($payment->status !== self::STATUS_SUCCES) {
            return back()->with('error', 'Seuls les paiements validés ont un reçu définitif.');
        }

        return view('admin.payments.receipt', compact('payment'));
    }
}
