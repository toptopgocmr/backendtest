<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ZipArchive;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
                  ->orWhere('phone', 'like', "%$v%")
                  ->orWhere('email', 'like', "%$v%"))
            ->when($request->role, fn($q, $v) => $q->where('role', $v))
            ->when($request->status !== null && $request->status !== '',
                fn($q) => $q->where('is_active', (int) $request->status))
            ->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function show(string $id)
    {
        $user = User::with([
            'bookings.property',
            'reviews',
            'favorites.property',
        ])->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    public function toggle(string $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        $msg = $user->is_active ? 'Utilisateur activé.' : 'Utilisateur suspendu.';

        return back()->with('success', $msg);
    }

    /**
     * Vérifier manuellement un compte depuis l'admin.
     * is_verified = true + is_active = true → le client peut se connecter immédiatement.
     */
    public function verify(string $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'is_verified'    => true,
            'is_active'      => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        return back()->with('success', "Compte de {$user->name} vérifié et activé.");
    }

    // ── EXPORT EXCEL (.xlsx) ─────────────────────────────────────────────────
    public function exportCsv(Request $request): Response
    {
        $users = User::when($request->search, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
                  ->orWhere('phone', 'like', "%$v%")
                  ->orWhere('email', 'like', "%$v%"))
            ->when($request->role,   fn($q, $v) => $q->where('role', $v))
            ->when($request->status !== null && $request->status !== '',
                fn($q) => $q->where('is_active', (int) $request->status))
            ->latest()
            ->get();

        $filename = 'utilisateurs_' . now()->format('Ymd_His') . '.xlsx';

        $headers_row = [
            'ID', 'Prénom', 'Nom', 'Nom complet', 'Email', 'Téléphone',
            'Rôle', 'Vérifié', 'Actif', 'Pays', 'Date inscription',
        ];

        $rows = [];
        foreach ($users as $u) {
            $rows[] = [
                $u->id,
                $u->first_name  ?? '',
                $u->last_name   ?? '',
                $u->name,
                $u->email       ?? '—',
                $u->phone,
                $u->role,
                $u->is_verified ? 'Oui' : 'Non',
                $u->is_active   ? 'Actif' : 'Suspendu',
                $u->country     ?? 'Congo Brazzaville',
                $u->created_at?->format('d/m/Y H:i') ?? '—',
            ];
        }

        $xlsx = $this->generateXlsx($headers_row, $rows, 'Utilisateurs TholadImmo');

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length'      => strlen($xlsx),
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function generateXlsx(array $headers, array $rows, string $sheetTitle): string
    {
        $numericCols = [0]; // ID

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
            $rowNum = $ri + 2;
            $sheetRows .= "<row r=\"{$rowNum}\">";
            foreach ($row as $ci => $val) {
                $col = $this->colLetter($ci) . $rowNum;
                if (in_array($ci, $numericCols)) {
                    $sheetRows .= "<c r=\"{$col}\"><v>" . (int)$val . "</v></c>";
                } else {
                    $si = $addStr((string)$val);
                    $sheetRows .= "<c r=\"{$col}\" t=\"s\"><v>{$si}</v></c>";
                }
            }
            $sheetRows .= '</row>';
        }

        $colsXml = '<cols>';
        $widths  = [6, 16, 16, 22, 26, 18, 10, 10, 12, 20, 18];
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
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="center"/></xf>'
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

}
