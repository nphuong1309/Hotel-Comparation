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
                <a href="#" aria-label="Facebook" title="Facebook">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14 8h3V4h-3c-3 0-5 2-5 5v3H6v4h3v5h4v-5h3.5l.5-4h-4V9c0-.7.3-1 1-1Z"></path>
                    </svg>
                </a>

                <a href="#" aria-label="Instagram" title="Instagram">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                        <circle cx="12" cy="12" r="4"></circle>
                        <circle cx="17.5" cy="6.5" r="1"></circle>
                    </svg>
                </a>

                <a href="#" aria-label="X" title="X">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 4l14 16"></path>
                        <path d="M19 4 5 20"></path>
                    </svg>
                </a>

                <a href="#" aria-label="YouTube" title="YouTube">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="6" width="18" height="12" rx="4"></rect>
                        <path d="m10 9 5 3-5 3Z"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Hai cột giữa: Danh sách khách sạn -->
        <div class="footer-col hotel-partners">
            <h4>Hân hạnh đồng hành cùng</h4>

            <div class="hotel-link-columns">
                <ul>
                    <li><a href="https://luxurycantho.muongthanh.com/" target="_blank" rel="noopener noreferrer">Mường Thanh Luxury</a></li>
                    <li><a href="https://azerai.com/vi/offers/azerai-can-tho-discovery/" target="_blank" rel="noopener noreferrer">Azerai Cần Thơ Resort</a></li>
                    <li><a href="https://www.victoriahotels.asia/en/victoria-can-tho-resort/" target="_blank" rel="noopener noreferrer">Victoria Resort</a></li>
                    <li><a href="https://ttchospitality.vn/ttc-hotel/ttc-hotel-can-tho" target="_blank" rel="noopener noreferrer">TTC Hotel Premium</a></li>
                    <li><a href="https://www.theirissignature.com/trang-chu/" target="_blank" rel="noopener noreferrer">Iris Hotel</a></li>
                </ul>

                <ul>
                    <li><a href="https://www.greenvillagemekong.com/" target="_blank" rel="noopener noreferrer">Green Village Mekong</a></li>
                    <li><a href="https://www.ninhkieuriversidehotel.vn/" target="_blank" rel="noopener noreferrer">Ninh Kiều Riverside</a></li>
                    <li><a href="https://www.facebook.com/kphotelcantho/" target="_blank" rel="noopener noreferrer">KP Hotel Boutique</a></li>
                    <li><a href="https://www.conkhuongresort.com/" target="_blank" rel="noopener noreferrer">Cồn Khương Resort</a></li>
                    <li>
                        <a href="https://applehotel.vn/" target="_blank" rel="noopener noreferrer">Apple Hotel</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Cột 4: Liên hệ -->
        <div class="footer-col contact-col">
            <h4>Liên hệ với chúng tôi</h4>
            <ul class="contact-info">
                <li>
                    <svg class="contact-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                        <circle cx="12" cy="10" r="2.5"></circle>
                    </svg>
                    <span><strong>Địa chỉ:</strong> Khu II, đường 3/2, Q. Ninh Kiều, Cần Thơ</span>
                </li>

                <li>
                    <svg class="contact-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 4h4l2 5-3 2a16 16 0 0 0 5 5l2-3 5 2v4c0 1-1 2-2 2C10 21 3 14 3 6c0-1 1-2 2-2Z"></path>
                    </svg>
                    <span><strong>Hotline:</strong> 1900 8888</span>
                </li>

                <li>
                    <svg class="contact-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m4 7 8 6 8-6"></path>
                    </svg>
                    <span><strong>Email:</strong> support@joytix.id.vn</span>
                </li>
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

    .social-links {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .social-links a {
        width: 38px;
        height: 38px;
        border: 1px solid rgba(255, 244, 230, 0.35);
        border-radius: 50%;
        color: #FFF4E6;
        text-decoration: none;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        transition:
            transform 0.25s ease,
            border-color 0.25s ease,
            background-color 0.25s ease,
            color 0.25s ease;
    }

    .social-links a svg {
        width: 19px;
        height: 19px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .social-links a:first-child svg {
        fill: currentColor;
        stroke: none;
    }

    .social-links a:hover {
        transform: translateY(-3px);
        border-color: #df6040;
        background: #df6040;
        color: #FFFFFF;
    }

    .hotel-partners {
        grid-column: span 2;
    }

    .hotel-link-columns {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 40px;
    }

    .hotel-link-columns ul, .contact-info {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .hotel-link-columns ul li, .contact-info li {
        margin-bottom: 12px;
        color: #E5B8A8;
    }

    .hotel-link-columns ul li a {
        color: #E5B8A8;
        text-decoration: none;
        transition: color 0.3s ease, padding-left 0.3s ease;
    }

    /* Kế thừa màu nhấn #df6040 từ CSS gốc */
    .hotel-link-columns ul li a:hover {
        color: #df6040;
        padding-left: 5px;
    }

    .contact-info strong {
        color: #FFF4E6;
    }

    .contact-info li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .contact-icon {
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        margin-top: 3px;

        fill: none;
        stroke: #FFF4E6;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
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

        .hotel-partners {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 576px) {
        .footer-grid {
            grid-template-columns: 1fr;
        }

        .hotel-partners {
            grid-column: auto;
        }

        .hotel-link-columns {
            grid-template-columns: 1fr;
            gap: 0;
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