<?php
// Kết nối database
include 'contdb.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sơ đồ Tính năng Hạn Xử lý</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .feature-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #3498db;
        }
        .btn-feature {
            margin-top: auto;
        }
        .section-title {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-sitemap"></i> Sơ đồ Tính năng Hạn Xử lý</h1>
        
        <div class="jumbotron">
            <h2><i class="fas fa-clock"></i> Hệ thống Quản lý Hạn Xử lý</h2>
            <p class="lead">Trang này giúp bạn tìm thấy tất cả các tính năng liên quan đến hạn xử lý trong hệ thống.</p>
            <hr class="my-4">
            <p>Hãy chọn tính năng bạn muốn sử dụng từ các mục bên dưới.</p>
            <a class="btn btn-primary btn-lg" href="index.php" role="button"><i class="fas fa-home"></i> Trang chính</a>
        </div>
        
        <h3 class="section-title"><i class="fas fa-tools"></i> Công cụ Quản lý Hạn Xử lý</h3>
        
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5 class="card-title">Kiểm tra Cài đặt Hạn Xử lý</h5>
                        <p class="card-text">Kiểm tra và cập nhật hạn xử lý cho từng đơn hàng dựa trên cài đặt mặc định.</p>
                        <a href="check_default_settings.php" class="btn btn-primary mt-auto btn-feature">Mở công cụ</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="feature-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h5 class="card-title">Cập nhật Hàng loạt Hạn Xử lý</h5>
                        <p class="card-text">Cập nhật hạn xử lý cho nhiều đơn hàng cùng một lúc dựa trên điều kiện lọc.</p>
                        <a href="batch_update_deadline.php" class="btn btn-primary mt-auto btn-feature">Mở công cụ</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="feature-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h5 class="card-title">Hướng dẫn Hạn Xử lý</h5>
                        <p class="card-text">Tìm hiểu cách sử dụng các tính năng hạn xử lý trong hệ thống.</p>
                        <a href="help_deadline.php" class="btn btn-primary mt-auto btn-feature">Xem hướng dẫn</a>
                    </div>
                </div>
            </div>
        </div>
        
        <h3 class="section-title"><i class="fas fa-file-import"></i> Nhập và Quản lý Dữ liệu</h3>
        
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="feature-icon">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <h5 class="card-title">Import Dữ liệu từ Excel</h5>
                        <p class="card-text">Nhập dữ liệu đơn hàng từ file Excel và tự động áp dụng cài đặt hạn xử lý.</p>
                        <a href="import.php" class="btn btn-primary mt-auto btn-feature">Nhập dữ liệu</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="feature-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h5 class="card-title">Cài đặt Mặc định</h5>
                        <p class="card-text">Quản lý cài đặt mặc định cho hệ thống hạn xử lý theo bộ phận và xưởng.</p>
                        <a href="settings.php" class="btn btn-primary mt-auto btn-feature">Quản lý cài đặt</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="feature-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5 class="card-title">Kiểm tra Hệ thống Hạn Xử lý</h5>
                        <p class="card-text">Kiểm tra tính chính xác và cấu hình của hệ thống hạn xử lý.</p>
                        <a href="check_deadline_system.php" class="btn btn-primary mt-auto btn-feature">Kiểm tra hệ thống</a>
                    </div>
                </div>
            </div>
        </div>
        
        <h3 class="section-title"><i class="fas fa-info-circle"></i> Thông tin Hệ thống</h3>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Các loại tính hạn xử lý</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>ngay_vao (Ngày vào trừ)</strong><br>
                                Tính hạn xử lý bằng ngày vào - số ngày xử lý
                            </li>
                            <li class="list-group-item">
                                <strong>ngay_vao_cong (Ngày vào cộng)</strong><br>
                                Tính hạn xử lý bằng ngày vào + số ngày xử lý
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>ngay_ra (Ngày ra)</strong><br>
                                Sử dụng ngày ra làm hạn xử lý
                            </li>
                            <li class="list-group-item">
                                <strong>ngay_ra_tru (Ngày ra trừ)</strong><br>
                                Tính hạn xử lý bằng ngày ra - số ngày xử lý
                            </li>
                        </ul>
                    </div>
                </div>
                
                <h5 class="card-title mt-4">Trạng thái hiển thị hạn xử lý</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <span class="badge badge-success p-2 mb-2" style="font-size: 16px;">
                                    <i class="fas fa-check-circle"></i> Còn 5 ngày
                                </span>
                                <p>Còn nhiều ngày để xử lý</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <span class="badge badge-warning p-2 mb-2" style="font-size: 16px;">
                                    <i class="fas fa-exclamation-triangle"></i> Còn 1 ngày
                                </span>
                                <p>Còn ít ngày để xử lý</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <span class="badge badge-danger p-2 mb-2" style="font-size: 16px;">
                                    <i class="fas fa-exclamation-circle"></i> Quá hạn 2 ngày
                                </span>
                                <p>Đã quá hạn xử lý</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại Trang chủ</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
</body>
</html> 