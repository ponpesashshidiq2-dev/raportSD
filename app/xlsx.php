<?php

function xlsx_read(string $path, string $originalName = ''): array
{
    $name = $originalName !== '' ? $originalName : basename($path);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext === 'csv' || $ext === 'txt') {
        return csv_read($path);
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        if ($ext === '' || $ext === 'xls') {
            return csv_read($path);
        }
        throw new RuntimeException('File Excel tidak dapat dibuka.');
    }
    $shared = [];
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string) $si->t;
                } else {
                    $txt = '';
                    foreach ($si->r ?? [] as $r) {
                        $txt .= (string) $r->t;
                    }
                    $shared[] = $txt;
                }
            }
        }
    }
    $sheetName = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = $zip->getNameIndex($i);
        if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $n)) {
            $sheetName = $n;
            break;
        }
    }
    if (!$sheetName) {
        $zip->close();
        throw new RuntimeException('Lembar kerja tidak ditemukan pada file Excel.');
    }
    $tmp = tempnam(sys_get_temp_dir(), 'xls');
    file_put_contents($tmp, $zip->getFromName($sheetName));
    $zip->close();

    $rows = [];
    $reader = new XMLReader();
    $reader->open($tmp);
    $colIndex = 0;
    $rowIndex = -1;
    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'row') {
            $rowIndex++;
            $rows[$rowIndex] = [];
            $colIndex = 0;
        } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'c') {
            $ref = $reader->getAttribute('r');
            $type = $reader->getAttribute('t');
            $colIndex = $ref !== null ? xlsx_col_to_num(preg_replace('/\d+/', '', $ref)) : $colIndex;
            $value = '';
            $inner = $reader->readInnerXML();
            if ($type === 's') {
                $v = '';
                if (preg_match('#<v(?:\s[^>]*)?>(.*?)</v>#s', $inner, $m)) {
                    $v = $m[1];
                }
                $value = $shared[(int) $v] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = preg_match('#<t[^>]*>(.*?)</t>#s', $inner, $m) ? html_entity_decode($m[1], ENT_QUOTES) : '';
            } else {
                $value = preg_match('#<v(?:\s[^>]*)?>(.*?)</v>#s', $inner, $m) ? $m[1] : '';
            }
            $rows[$rowIndex][$colIndex] = $value;
            $colIndex++;
        }
    }
    $reader->close();
    unlink($tmp);

    $out = [];
    foreach ($rows as $r) {
        if (!$r) {
            continue;
        }
        ksort($r);
        $out[] = array_values($r);
    }
    return $out;
}

function xlsx_col_to_num(string $col): int
{
    $n = 0;
    foreach (str_split($col) as $ch) {
        $n = $n * 26 + (ord(strtoupper($ch)) - 64);
    }
    return $n - 1;
}

function csv_read(string $path): array
{
    $content = file_get_contents($path);
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        $content = substr($content, 3);
    }
    $first = strtok($content, "\r\n") ?: '';
    $delim = ',';
    foreach ([',' => 'comma', ';' => 'semi', "\t" => 'tab'] as $d => $name) {
        if (substr_count($first, $d) > substr_count($first, $delim)) {
            $delim = $d;
        }
    }
    $rows = [];
    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $content);
    rewind($handle);
    while (($row = fgetcsv($handle, 0, $delim)) !== false) {
        $rows[] = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
    }
    fclose($handle);
    return $rows;
}

function xlsx_download(string $filename, array $rows, array $widths = [], array $dropdowns = []): void
{
    $zip = new ZipArchive();
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip->open($tmp, ZipArchive::OVERWRITE);

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets>
</workbook>');

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2">
<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
</fonts>
<fills count="3">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF44546A"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FF9E9E9E"/></left><right style="thin"><color rgb="FF9E9E9E"/></right><top style="thin"><color rgb="FF9E9E9E"/></top><bottom style="thin"><color rgb="FF9E9E9E"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="3">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>');

    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    if ($rows) {
        $sheet .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView></sheetViews>';
    }
    if ($widths) {
        $sheet .= '<cols>';
        foreach ($widths as $i => $w) {
            $sheet .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $sheet .= '</cols>';
    }
    $sheet .= '<sheetData>';
    $rNum = 0;
    foreach ($rows as $row) {
        $rNum++;
        $style = $rNum === 1 ? 1 : 2;
        $sheet .= '<row r="' . $rNum . '"' . ($rNum === 1 ? ' ht="22" customHeight="1"' : '') . '>';
        $cNum = 0;
        foreach ($row as $val) {
            $ref = xlsx_num_to_col($cNum) . $rNum;
            $isNum = is_int($val) || is_float($val);
            if ($isNum) {
                $sheet .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $val . '</v></c>';
            } else {
                $sheet .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t>' . xlsx_esc((string) $val) . '</t></is></c>';
            }
            $cNum++;
        }
        $sheet .= '</row>';
    }
    $sheet .= '</sheetData>';
    if ($dropdowns) {
        $sheet .= '<dataValidations count="' . count($dropdowns) . '">';
        foreach ($dropdowns as $col => $list) {
            $colName = xlsx_num_to_col((int) $col);
            $sqref = $colName . '2:' . $colName . '100000';
            $sheet .= '<dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="1" errorStyle="stop" errorTitle="Nilai tidak valid" error="Nilai harus sesuai daftar yang disediakan." promptTitle="Pilihan" prompt="Pilih nilai dari daftar." sqref="' . $sqref . '"><formula1>' . xlsx_esc($list) . '</formula1></dataValidation>';
        }
        $sheet .= '</dataValidations>';
    }
    $sheet .= '</worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}

function xlsx_num_to_col(int $n): string
{
    $s = '';
    $n++;
    while ($n > 0) {
        $m = ($n - 1) % 26;
        $s = chr(65 + $m) . $s;
        $n = intdiv($n - 1, 26);
    }
    return $s;
}

function xlsx_esc(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
