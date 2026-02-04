<?php
// Kết nối database
require "contdb.php";

// Header Config
$header_config = [
    'title' => 'Hướng dẫn sử dụng hệ thống hạn xử lý',
    'title_short' => 'Hướng dẫn',
    'logo_path' => 'img/logoht.png',
    'logo_link' => '/trangchu/',
    'show_search' => false,
    'show_mobile_menu' => true,
    'actions' => [
        ['url' => 'settings.php', 'icon' => 'img/settings.png', 'title' => 'Cài đặt', 'tooltip' => 'Trang cài đặt'],
        ['url' => 'check_deadline_system.php', 'icon' => 'img/check.png', 'title' => 'Kiểm tra', 'tooltip' => 'Kiểm tra hệ thống'],
        ['url' => 'index.php', 'icon' => 'img/home.png', 'title' => 'Trang chủ', 'tooltip' => 'Trang chủ']
    ]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hướng dẫn sử dụng hạn xử lý</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Header CSS -->
    <link rel="stylesheet" href="assets/css/header.css">
    
    <style>
        body {
            padding-bottom: 20px;
            background-color: #f8f9fa;
        }
        /* Removed legacy .header styles */
        .footer {
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid #e5e5e5;
            text-align: center;
        }
        .screenshot {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            max-width: 100%;
            height: auto;
            margin-bottom: 20px;
        }
        .help-section {
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .badge-deadline-ok {
            background-color: #28a745;
            color: white;
        }
        .badge-deadline-warning {
            background-color: #ffc107;
            color: black;
        }
        .badge-deadline-danger {
            background-color: #dc3545;
            color: white;
        }
        .badge-deadline-info {
            background-color: #17a2b8;
            color: white;
        }
        .badge-deadline-none {
            background-color: #6c757d;
            color: white;
        }
        /* Override bootstrap container padding for header */
        .container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>
    
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group sticky-top" style="top: 80px; z-index: 1;">
                    <a href="#overview" class="list-group-item list-group-item-action">Tổng quan</a>
                    <a href="#deadline-types" class="list-group-item list-group-item-action">Các loại tính hạn</a>
                    <a href="#default-settings" class="list-group-item list-group-item-action">Cài đặt mặc định</a>
                    <a href="#custom-settings" class="list-group-item list-group-item-action">Cài đặt tùy chỉnh</a>
                    <a href="#deadline-display" class="list-group-item list-group-item-action">Hiển thị hạn xử lý</a>
                    <a href="#common-issues" class="list-group-item list-group-item-action">Vấn đề thường gặp</a>
                </div>
                
                <div class="card mt-3 sticky-top" style="top: 360px;">
                    <div class="card-header">
                        <h5>Liên kết hữu ích</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog"></i> Trang cài đặt
                        </a>
                        <a href="check_deadline_system.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-check-circle"></i> Kiểm tra hệ thống
                        </a>
                        <a href="index.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div id="overview" class="help-section">
                    <h2>Tổng quan về hệ thống hạn xử lý</h2>
                    <p>Hệ thống hạn xử lý tiêu chí đánh giá cho phép bạn:</p>
                    <ul>
                        <li>Thiết lập hạn xử lý cho từng tiêu chí đánh giá</li>
                        <li>Tính hạn xử lý theo nhiều cách khác nhau dựa trên ngày vào/ngày ra của đơn hàng</li>
                        <li>Có cài đặt mặc định và cài đặt tùy chỉnh cho từng đơn hàng</li>
                        <li>Hiển thị trạng thái hạn xử lý (còn hạn, sắp hết hạn, đã quá hạn)</li>
                    </ul>
                    <p>Hệ thống này giúp theo dõi tiến độ xử lý các tiêu chí đánh giá, đảm bảo các công việc được hoàn thành đúng hạn.</p>
                </div>
                
                <div id="deadline-types" class="help-section">
                    <h2>Các loại tính hạn xử lý</h2>
                    <p>Hệ thống cho phép tính toán hạn xử lý theo 4 cách khác nhau:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Loại tính hạn</th>
                                    <th>Công thức</th>
                                    <th>Giải thích</th>
                                    <th>Trường hợp sử dụng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Ngày vào</strong><br>(ngay_vao)</td>
                                    <td>Hạn xử lý = Ngày vào</td>
                                    <td>Hạn xử lý chính là ngày vào của đơn hàng</td>
                                    <td>Các tiêu chí phải hoàn thành vào ngày đơn hàng được nhập vào hệ thống</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày vào + số ngày</strong><br>(ngay_vao_cong)</td>
                                    <td>Hạn xử lý = Ngày vào + Số ngày xử lý</td>
                                    <td>Hạn xử lý được tính bằng cách cộng thêm số ngày xử lý vào ngày vào</td>
                                    <td>Các tiêu chí có thời gian xử lý tính từ khi đơn hàng được nhập vào hệ thống</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày ra</strong><br>(ngay_ra)</td>
                                    <td>Hạn xử lý = Ngày ra</td>
                                    <td>Hạn xử lý chính là ngày ra dự kiến của đơn hàng</td>
                                    <td>Các tiêu chí phải hoàn thành vào ngày đơn hàng dự kiến giao</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày ra - số ngày</strong><br>(ngay_ra_tru)</td>
                                    <td>Hạn xử lý = Ngày ra - Số ngày xử lý</td>
                                    <td>Hạn xử lý được tính bằng cách trừ đi số ngày xử lý từ ngày ra</td>
                                    <td>Các tiêu chí phải hoàn thành trước ngày giao hàng một khoảng thời gian nhất định</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <p><strong>Lưu ý quan trọng:</strong> Khi thay đổi ngày vào hoặc ngày ra của đơn hàng, hạn xử lý sẽ được tính toán lại dựa trên loại tính hạn được chọn.</p>
                </div>
                
                <div id="default-settings" class="help-section">
                    <h2>Cài đặt hạn xử lý mặc định</h2>
                    <p>Cài đặt mặc định sẽ được áp dụng cho tất cả đơn hàng mới và các tiêu chí chưa có cài đặt tùy chỉnh.</p>
                    
                    <h5>Cách truy cập cài đặt mặc định:</h5>
                    <ol>
                        <li>Truy cập trang <a href="settings.php">Cài đặt hệ thống</a></li>
                        <li>Chọn bộ phận muốn cài đặt từ menu bên trái</li>
                        <li>Sử dụng công cụ bên trái để áp dụng cài đặt cho tất cả tiêu chí của bộ phận</li>
                    </ol>
                    
                    <h5>Cách reset về cài đặt mặc định ban đầu:</h5>
                    <ol>
                        <li>Truy cập trang <a href="settings.php">Cài đặt hệ thống</a></li>
                        <li>Chọn bộ phận muốn reset từ menu bên trái</li>
                        <li>Nhấn nút "Reset về mặc định" và xác nhận</li>
                    </ol>
                    
                    <p><strong>Giá trị mặc định ban đầu:</strong> 7 ngày xử lý, tính từ ngày vào.</p>
                </div>
                
                <div id="custom-settings" class="help-section">
                    <h2>Cài đặt hạn xử lý tùy chỉnh</h2>
                    <p>Cài đặt tùy chỉnh cho phép bạn thiết lập hạn xử lý riêng cho từng tiêu chí của một đơn hàng cụ thể.</p>
                    
                    <h5>Cách truy cập cài đặt tùy chỉnh:</h5>
                    <ol>
                        <li>Truy cập trang chi tiết đơn hàng hoặc danh sách đơn hàng</li>
                        <li>Nhấn vào nút "Cài đặt hạn xử lý" từ menu tùy chọn</li>
                        <li>Một cửa sổ modal sẽ hiển thị với danh sách tiêu chí và cài đặt hiện tại</li>
                        <li>Điều chỉnh số ngày xử lý và loại tính hạn cho từng tiêu chí</li>
                        <li>Nhấn "Lưu" để áp dụng thay đổi cho tiêu chí đó, hoặc "Lưu thay đổi" để áp dụng cho tất cả</li>
                    </ol>
                    
                    <h5>Cài đặt tùy chỉnh cho nhiều tiêu chí cùng lúc:</h5>
                    <ol>
                        <li>Trong modal cài đặt hạn xử lý, chọn "Áp dụng cùng hạn xử lý cho tất cả tiêu chí"</li>
                        <li>Nhập số ngày xử lý và chọn loại tính hạn</li>
                        <li>Nhấn "Áp dụng cho tất cả tiêu chí" để thiết lập cùng một giá trị cho tất cả</li>
                        <li>Nhấn "Lưu thay đổi" để lưu các thay đổi</li>
                    </ol>
                    
                    <h5>Áp dụng cài đặt tùy chỉnh vào cài đặt mặc định:</h5>
                    <ol>
                        <li>Trong modal cài đặt hạn xử lý, chọn "Áp dụng thay đổi này vào cài đặt mặc định"</li>
                        <li>Thực hiện các thay đổi mong muốn</li>
                        <li>Nhấn "Lưu thay đổi" để lưu các thay đổi và cập nhật cài đặt mặc định</li>
                    </ol>
                    
                    <p><strong>Lưu ý:</strong> Cài đặt tùy chỉnh sẽ được đánh dấu bằng biểu tượng bánh răng trong giao diện hiển thị hạn xử lý.</p>
                </div>
                
                <div id="deadline-display" class="help-section">
                    <h2>Hiển thị hạn xử lý</h2>
                    <p>Hạn xử lý được hiển thị dưới dạng badge với các màu sắc khác nhau tùy thuộc vào trạng thái:</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Còn nhiều thời gian
                                    <span class="badge badge-deadline-ok">Còn 5 ngày</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Sắp hết hạn (còn 2 ngày hoặc ít hơn)
                                    <span class="badge badge-deadline-warning">Còn 1 ngày</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Đã quá hạn
                                    <span class="badge badge-deadline-danger">Quá hạn 3 ngày</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Cài đặt tùy chỉnh
                                    <span class="badge badge-deadline-ok"><i class="fas fa-cog"></i> Còn 5 ngày</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Chưa thiết lập hạn xử lý
                                    <span class="badge badge-deadline-none">Chưa thiết lập</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Chi tiết hạn xử lý:</h5>
                    <p>Khi di chuột qua badge hạn xử lý, bạn sẽ thấy thông tin chi tiết về hạn xử lý, bao gồm:</p>
                    <ul>
                        <li>Ngày hạn xử lý chính xác</li>
                        <li>Loại tính hạn đang được áp dụng</li>
                        <li>Số ngày xử lý được thiết lập</li>
                    </ul>
                </div>
                
                <div id="common-issues" class="help-section">
                    <h2>Các vấn đề thường gặp</h2>
                    
                    <div class="accordion" id="accordionFAQ">
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Hạn xử lý không hiển thị đúng sau khi cập nhật ngày vào/ngày ra
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionFAQ">
                                <div class="card-body">
                                    <p>Nguyên nhân: Hạn xử lý được tính toán dựa trên ngày vào và ngày ra, nên khi thay đổi các ngày này, hạn xử lý cần được tính toán lại.</p>
                                    <p>Giải pháp:</p>
                                    <ol>
                                        <li>Truy cập trang chi tiết đơn hàng</li>
                                        <li>Nhấn vào nút "Cài đặt hạn xử lý"</li>
                                        <li>Nhấn "Lưu thay đổi" để tính toán lại hạn xử lý dựa trên ngày mới</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="headingTwo">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Thay đổi cài đặt mặc định không ảnh hưởng đến đơn hàng hiện có
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionFAQ">
                                <div class="card-body">
                                    <p>Nguyên nhân: Cài đặt mặc định chỉ áp dụng cho đơn hàng mới hoặc tiêu chí chưa có cài đặt riêng.</p>
                                    <p>Giải pháp: Nếu muốn áp dụng cài đặt mới cho tất cả đơn hàng hiện có:</p>
                                    <ol>
                                        <li>Viết script cập nhật tùy chỉnh</li>
                                        <li>Hoặc mở từng đơn hàng và áp dụng lại cài đặt mặc định</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="headingThree">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Đã thay đổi cài đặt nhưng không thấy áp dụng
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionFAQ">
                                <div class="card-body">
                                    <p>Nguyên nhân: Có thể do cache trình duyệt hoặc cần làm mới trang.</p>
                                    <p>Giải pháp:</p>
                                    <ol>
                                        <li>Nhấn F5 để làm mới trang</li>
                                        <li>Xóa cache trình duyệt</li>
                                        <li>Đăng xuất và đăng nhập lại</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Hệ thống quản lý hạn xử lý tiêu chí đánh giá &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 