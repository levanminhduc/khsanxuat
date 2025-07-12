# Script kiểm tra chất lượng code PHP theo PSR-12
param(
    [string]$File = "",
    [switch]$Fix = $false,
    [switch]$Summary = $false
)

$phpPath = "C:\xampp\php\php.exe"
$phpcsPath = "phpcs.phar"
$phpcbfPath = "phpcbf.phar"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "KIỂM TRA CHẤT LƯỢNG CODE PHP - PSR-12" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Kiểm tra xem PHP có tồn tại không
if (!(Test-Path $phpPath)) {
    Write-Host "Lỗi: Không tìm thấy PHP tại $phpPath" -ForegroundColor Red
    exit 1
}

# Kiểm tra xem phpcs có tồn tại không
if (!(Test-Path $phpcsPath)) {
    Write-Host "Lỗi: Không tìm thấy PHP_CodeSniffer tại $phpcsPath" -ForegroundColor Red
    exit 1
}

if ($Fix) {
    Write-Host "Chế độ: TỰ ĐỘNG SỬA LỖI" -ForegroundColor Yellow
    Write-Host ""
    
    if ($File -eq "") {
        # Sửa tất cả file PHP chính
        $files = @("db_connect.php", "contdb.php", "index.php", "indexdept.php", "import.php")
        
        foreach ($f in $files) {
            if (Test-Path $f) {
                Write-Host "Đang sửa $f..." -ForegroundColor Green
                & $phpPath $phpcbfPath --standard=PSR12 $f
                Write-Host ""
            }
        }
    } else {
        if (Test-Path $File) {
            Write-Host "Đang sửa $File..." -ForegroundColor Green
            & $phpPath $phpcbfPath --standard=PSR12 $File
        } else {
            Write-Host "Lỗi: Không tìm thấy file $File" -ForegroundColor Red
        }
    }
} else {
    Write-Host "Chế độ: KIỂM TRA" -ForegroundColor Green
    Write-Host ""
    
    $reportType = if ($Summary) { "summary" } else { "full" }
    
    if ($File -eq "") {
        # Kiểm tra tất cả file PHP chính
        $files = @("db_connect.php", "contdb.php", "index.php", "indexdept.php", "import.php")
        
        foreach ($f in $files) {
            if (Test-Path $f) {
                Write-Host "Kiểm tra $f..." -ForegroundColor Yellow
                & $phpPath $phpcsPath --standard=PSR12 --report=$reportType $f
                Write-Host ""
            }
        }
    } else {
        if (Test-Path $File) {
            Write-Host "Kiểm tra $File..." -ForegroundColor Yellow
            & $phpPath $phpcsPath --standard=PSR12 --report=$reportType $File
        } else {
            Write-Host "Lỗi: Không tìm thấy file $File" -ForegroundColor Red
        }
    }
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "HOÀN THÀNH" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Cách sử dụng:" -ForegroundColor White
Write-Host "  .\check-quality.ps1                    # Kiểm tra tất cả file chính" -ForegroundColor Gray
Write-Host "  .\check-quality.ps1 -File index.php    # Kiểm tra file cụ thể" -ForegroundColor Gray
Write-Host "  .\check-quality.ps1 -Fix               # Tự động sửa tất cả file" -ForegroundColor Gray
Write-Host "  .\check-quality.ps1 -Fix -File index.php # Tự động sửa file cụ thể" -ForegroundColor Gray
Write-Host "  .\check-quality.ps1 -Summary           # Hiển thị tóm tắt" -ForegroundColor Gray
