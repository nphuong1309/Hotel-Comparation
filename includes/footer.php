</main>

<footer class="site-footer">
    <div class="footer-inner container">
        <div class="footer-brand-row">
            <span class="footer-brand-line" aria-hidden="true"></span>

            <div class="footer-brand" aria-label="JoyTix">
                <div class="footer-brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <path d="M14 54V27l18-13 18 13v27"></path>
                        <path d="M21 54V32l11-8 11 8v22"></path>
                        <path d="M28 54V39l4-3 4 3v15"></path>
                        <path d="M32 14V5"></path>
                        <path d="M24 20v-8h16v8"></path>
                    </svg>
                </div>
                <div class="footer-brand-name">JOY<span>TIX</span></div>
            </div>

            <span class="footer-brand-line" aria-hidden="true"></span>
        </div>

        <div class="footer-info-grid">
            <section class="footer-info-col">
                <div class="footer-kicker">LIÊN HỆ 24/7</div>
                <h3>Hỗ trợ</h3>
                <p><a href="tel:19008888">1900 8888</a></p>
                <p><a href="mailto:support@joytix.id.vn">support@joytix.id.vn</a></p>
            </section>

            <section class="footer-info-col">
                <div class="footer-kicker">ĐỊA ĐIỂM</div>
                <h3>Địa chỉ</h3>
                <p>Khu II, đường 3/2, Q. Ninh Kiều, Cần Thơ</p>
            </section>

            <section class="footer-info-col">
                <div class="footer-kicker">THEO DÕI</div>
                <h3>Mạng xã hội</h3>

                <div class="footer-socials">
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
            </section>

            <section class="footer-info-col footer-newsletter-col">
                <div class="footer-kicker">ĐĂNG KÝ NHẬN</div>
                <h3>Bản tin</h3>

                <form action="#" class="footer-newsletter" onsubmit="return false;">
                    <input type="email" placeholder="Nhập email của bạn" aria-label="Email nhận bản tin" required>
                    <button type="submit" aria-label="Đăng ký nhận bản tin">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="1"></rect>
                            <path d="m4 7 8 6 8-6"></path>
                        </svg>
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div class="footer-bottom">
        <p>JoyTix. 2026 Đã đăng ký bản quyền.</p>
    </div>
</footer>

<style>
    .site-footer,
    .site-footer * {
        box-sizing: border-box;
    }

    .site-footer {
        width: 100%;
        margin: 60px 0 0 !important;
        flex: 0 0 auto;
        padding: 68px 0 0;
        border-top: 3px solid var(--site-primary, #dda975);
        background: #191919 !important;
        color: #fff;
        font-family: var(--font-body, 'Lato', Arial, sans-serif);
        font-size: 15px;
        line-height: 1.65;
        box-shadow: none;
    }

    .footer-inner {
        padding-bottom: 62px;
    }

    .footer-brand-row {
        display: grid;
        grid-template-columns: minmax(80px, 1fr) auto minmax(80px, 1fr);
        align-items: center;
        gap: 32px;
        margin-bottom: 72px;
    }

    .footer-brand-line {
        height: 1px;
        background: rgba(255, 255, 255, .48);
    }

    .footer-brand {
        display: flex;
        align-items: center;
        flex-direction: column;
        gap: 10px;
        min-width: 240px;
    }

    .footer-brand-icon {
        width: 120px;
        height: 120px;
        border: 4px solid var(--site-primary, #dda975);
        display: grid;
        place-items: center;
        color: var(--site-primary, #dda975);
    }

    .footer-brand-icon svg {
        width: 72px;
        height: 72px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.5;
        stroke-linecap: square;
        stroke-linejoin: miter;
    }

    .footer-brand-name {
        color: #fff;
        font-family: var(--font-logo, 'Cinzel', Georgia, serif);
        font-size: 35px;
        line-height: 1;
        letter-spacing: .13em;
    }

    .footer-brand-name span {
        color: var(--site-primary, #dda975);
    }

    .footer-info-grid {
        display: grid;
        grid-template-columns: 1fr 1.15fr .9fr 1.25fr;
        gap: 72px;
    }

    .footer-kicker {
        margin-bottom: 8px;
        color: var(--site-primary, #dda975);
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .footer-info-col h3 {
        margin: 0 0 38px;
        color: #fff;
        font-family: var(--font-heading, 'Cormorant Garamond', Georgia, serif);
        font-size: 31px;
        font-weight: 400;
        line-height: 1.2;
    }

    .footer-info-col p {
        margin: 0 0 12px;
        color: #fff;
    }

    .footer-info-col a {
        color: #fff;
        text-decoration: none;
        transition: color .2s ease;
    }

    .footer-info-col a:hover {
        color: var(--site-primary, #dda975);
    }

    .footer-socials {
        display: flex;
        align-items: center;
        gap: 24px;
        padding-top: 4px;
    }

    .footer-socials a {
        width: 25px;
        height: 25px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }

    .footer-socials svg {
        width: 22px;
        height: 22px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .footer-socials a:first-child svg {
        fill: currentColor;
        stroke: none;
    }

    .footer-newsletter {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 86px;
        width: 100%;
        min-height: 78px;
        border: 1px solid rgba(255, 255, 255, .55);
        background: transparent;
    }

    .footer-newsletter input {
        min-width: 0;
        padding: 0 22px;
        border: 0;
        outline: 0;
        background: transparent;
        color: #fff;
        font-size: 16px;
    }

    .footer-newsletter input::placeholder {
        color: #858585;
    }

    .footer-newsletter button {
        border: 0;
        background: var(--site-primary, #dda975) !important;
        color: #191919 !important;
        cursor: pointer;
        transition: background .2s ease, color .2s ease;
    }

    .footer-newsletter button:hover {
        background: var(--site-primary-dark, #c58b56) !important;
        color: #fff !important;
    }

    .footer-newsletter button svg {
        width: 30px;
        height: 30px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .footer-bottom {
        margin: 0 !important;
        padding: 30px 20px;
        background: #252525;
        color: #fff;
        text-align: center;
        font-size: 14px;
    }

    .footer-bottom p {
        margin: 0;
    }



    /* Xóa khoảng trống do CSS cũ chừa sẵn dưới cuối trang. */
    html,
    body {
        background: #fef4e8 !important;
    }

    body {
        margin-bottom: 0 !important;
    }

    .site-footer:last-of-type {
        margin-bottom: 0 !important;
    }

    @media (max-width: 1050px) {
        .footer-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 52px;
        }
    }

    @media (max-width: 680px) {
        .site-footer {
            padding-top: 48px;
        }

        .footer-brand-row {
            grid-template-columns: 1fr;
            gap: 22px;
            margin-bottom: 54px;
        }

        .footer-brand-line {
            display: none;
        }

        .footer-brand {
            min-width: 0;
        }

        .footer-brand-icon {
            width: 100px;
            height: 100px;
        }

        .footer-brand-name {
            font-size: 31px;
        }

        .footer-info-grid {
            grid-template-columns: 1fr;
            gap: 42px;
        }

        .footer-info-col h3 {
            margin-bottom: 20px;
        }
    }
</style>

<script src="js/script.js"></script>
</body>
</html>