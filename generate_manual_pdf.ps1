$markdownPath = Join-Path $PSScriptRoot 'manual_usuario_fondas.md'
$pdfPath = Join-Path $PSScriptRoot 'manual_usuario_fondas.pdf'
$lines = Get-Content -Path $markdownPath
$wrapped = @()
foreach ($line in $lines) {
    $text = $line
    while ($text.Length -gt 90) {
        $wrapped += $text.Substring(0, 90)
        $text = $text.Substring(90)
    }
    $wrapped += $text
}

$pages = @()
$page = @()
foreach ($line in $wrapped) {
    $page += $line
    if ($page.Count -ge 60) {
        $pages += ,$page
        $page = @()
    }
}
if ($page.Count -gt 0) {
    $pages += ,$page
}

$objects = @()
$objects += "1 0 obj`n<< /Type /Catalog /Pages 2 0 R >>`nendobj`n"
$kids = @()
for ($i = 0; $i -lt $pages.Count; $i++) {
    $kids += "{0} 0 R" -f (3 + $i)
}
$objects += "2 0 obj`n<< /Type /Pages /Kids [ {0} ] /Count {1} >>`nendobj`n" -f ($kids -join ' '), $pages.Count
$objects += "4 0 obj`n<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>`nendobj`n"
$contentStart = 5
for ($i = 0; $i -lt $pages.Count; $i++) {
    $pageNum = 3 + $i
    $contentNum = $contentStart + $i
    $objects += "{0} 0 obj`n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents {1} 0 R >>`nendobj`n" -f $pageNum, $contentNum

    $content = "BT /F1 10 Tf 50 760 Td`n"
    foreach ($line in $pages[$i]) {
        $escaped = $line.Replace('\\', '\\\\').Replace('(', '\\(').Replace(')', '\\)')
        $content += "({0}) Tj T*`n" -f $escaped
    }
    $content += "ET`n"
    $length = [System.Text.Encoding]::ASCII.GetByteCount($content)
    $objects += "{0} 0 obj`n<< /Length {1} >>`nstream`n{2}endstream`nendobj`n" -f $contentNum, $length, $content
}

$pdf = "%PDF-1.4`n"
foreach ($obj in $objects) {
    $pdf += $obj
}

$xrefOffset = [System.Text.Encoding]::ASCII.GetByteCount($pdf)
$objectsCount = $objects.Count
$pdf += "xref`n0 {0}`n0000000000 65535 f `n" -f ($objectsCount + 1)
$pos = 0
foreach ($obj in $objects) {
    $pdf += "{0:D10} 00000 n `n" -f $pos
    $pos += [System.Text.Encoding]::ASCII.GetByteCount($obj)
}
$pdf += "trailer << /Size {0} /Root 1 0 R >>`nstartxref`n{1}`n%%EOF`n" -f ($objectsCount + 1), $xrefOffset
[System.IO.File]::WriteAllBytes($pdfPath, [System.Text.Encoding]::ASCII.GetBytes($pdf))
Write-Output "PDF_GENERATED: $pdfPath"
