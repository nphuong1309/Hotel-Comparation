<!-- Bắt đầu Footer -->
<footer class="site-footer">
    <div class="container footer-grid">
        <!-- Cột 1: Thông tin thương hiệu -->
        <div class="footer-col brand-col">
            <h3 class="logo">Mini<span>Hotel</span></h3>
            <p class="brand-desc">
                Nền tảng tìm kiếm và gợi ý khách sạn hàng đầu. Chúng tôi đồng hành cùng bạn trên mọi hành trình tại Cần Thơ.
            </p>
            <div class="social-links">
                <a href="#" aria-label="Facebook">📘</a>
                <a href="#" aria-label="Instagram">📸</a>
                <a href="#" aria-label="Twitter">🐦</a>
                <a href="#" aria-label="YouTube">▶️</a>
            </div>
        </div>

        <!-- Cột 2: Khám phá -->
        <div class="footer-col links-col">
            <h4>Khám phá</h4>
            <ul>
                <li><a href="#">Khách sạn nổi bật</a></li>
                <li><a href="#">Khu nghỉ dưỡng</a></li>
                <li><a href="#">Cẩm nang du lịch</a></li>
                <li><a href="#">Cộng đồng</a></li>
            </ul>
        </div>

        <!-- Cột 3: Hỗ trợ -->
        <div class="footer-col links-col">
            <h4>Hỗ trợ</h4>
            <ul>
                <li><a href="#">Trung tâm trợ giúp</a></li>
                <li><a href="#">Hướng dẫn đặt phòng</a></li>
                <li><a href="#">Chính sách hoàn tiền</a></li>
                <li><a href="#">Bảo mật</a></li>
            </ul>
        </div>

        <!-- Cột 4: Liên hệ -->
        <div class="footer-col contact-col">
            <h4>Liên hệ với chúng tôi</h4>
            <ul class="contact-info">
                <li><strong>📍 Địa chỉ:</strong> Khu II, đường 3/2, Q. Ninh Kiều, Cần Thơ</li>
                <li><strong>📞 Hotline:</strong> 1900 8888</li>
                <li><strong>✉️ Email:</strong> support@joytix.id.vn</li>
            </ul>
            <!-- Tận dụng class btn-primary có sẵn trong CSS của bạn -->
            <form action="#" class="newsletter-form">
                <input type="email" placeholder="Nhập email..." required>
                <button type="submit" class="btn-primary">Gửi</button>
            </form>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; 2026 MiniHotel. Đã đăng ký bản quyền.</p>
    </div>
</footer>

<style>
    /* Kế thừa màu nền navbar và màu chữ từ file gốc */
.site-footer {
    width: 100vw; /* Mới thêm: Bắt buộc rộng bằng 100% màn hình */
    margin-left: calc(50% - 50vw); /* Mới thêm: Kéo giãn tràn đều ra hai bên */
    
    background: #130358;
    color: #FFF4E6;
    padding: 60px 0 0 0;
    margin-top: 60px;
    font-size: 15px;
    line-height: 1.6;
    box-shadow: 0 -2px 10px rgba(74, 37, 69, 0.25);
}

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 40px;
    }

    .footer-col h4 {
        color: #FFFFFF;
        font-size: 18px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .brand-desc {
        margin: 15px 0 20px 0;
        color: #E5B8A8;
    }

    .social-links a {
        display: inline-block;
        margin-right: 15px;
        font-size: 20px;
        text-decoration: none;
        transition: transform 0.3s ease;
    }

    .social-links a:hover {
        transform: translateY(-3px);
    }

    .links-col ul, .contact-info {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .links-col ul li, .contact-info li {
        margin-bottom: 12px;
        color: #E5B8A8;
    }

    .links-col ul li a {
        color: #E5B8A8;
        text-decoration: none;
        transition: color 0.3s ease, padding-left 0.3s ease;
    }

    /* Kế thừa màu nhấn #df6040 từ CSS gốc */
    .links-col ul li a:hover {
        color: #df6040; 
        padding-left: 5px;
    }

    .contact-info strong {
        color: #FFF4E6;
    }

    .newsletter-form {
        display: flex;
        margin-top: 15px;
        gap: 5px;
    }

    .newsletter-form input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #E5B8A8;
        border-radius: 5px;
        outline: none;
        background: #FFFDF8;
        color: #2F2F2F;
    }

    .footer-bottom {
        text-align: center;
        padding: 20px;
        margin-top: 50px;
        border-top: 1px solid rgba(229, 184, 168, 0.2);
        color: #E5B8A8;
        font-size: 14px;
    }

    @media (max-width: 992px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 576px) {
        .footer-grid {
            grid-template-columns: 1fr;
        }
        .newsletter-form {
            flex-direction: column;
        }
    }
</style>
<script src="js/script.js"></script>
<!-- Đóng các thẻ body/html đang mở từ header.php -->
</body>
</html>