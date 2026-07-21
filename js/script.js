/**
 * SCRIPT.JS - TOÀN BỘ HÀNH VI TRÌNH DUYỆT DÙNG CHUNG
 *
 * File này thay cho các thẻ <script> nằm rải trong từng trang PHP.
 * Mỗi hàm init tự kiểm tra phần tử đặc trưng của trang; vì vậy chỉ chạy
 * khi người dùng đang ở đúng trang, dù script được footer nạp toàn site.
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.addEventListener('DOMContentLoaded', () => {
    initHomePage();
    initHotelDetailPage();
    initCommunityPage();
    initAdminAddPage();
    initGenericCardInteractions();
});

/** Trang chủ: banner, ngân sách, tiện nghi, ảnh card và so sánh. */
function initHomePage() {
    const slides = [...document.querySelectorAll('.hero-slide')];
    const dots = [...document.querySelectorAll('.hero-dot')];
    if (slides.length) {
        let currentHero = 0;
        let heroTimer;
        const showHero = index => {
            currentHero = (index + slides.length) % slides.length;
            slides.forEach((slide, i) => slide.classList.toggle('is-active', i === currentHero));
            dots.forEach((dot, i) => dot.classList.toggle('is-active', i === currentHero));
        };
        const restartHeroTimer = () => {
            window.clearInterval(heroTimer);
            if (slides.length > 1) heroTimer = window.setInterval(() => showHero(currentHero + 1), 5200);
        };
        dots.forEach(dot => dot.addEventListener('click', () => {
            showHero(Number(dot.dataset.slide || 0));
            restartHeroTimer();
        }));
        restartHeroTimer();
    }

    const budgetRange = document.getElementById('budgetRange');
    const budgetValue = document.getElementById('budgetValue');
    const updateBudget = () => {
        if (!budgetRange) return;
        const min = Number(budgetRange.min);
        const max = Number(budgetRange.max);
        const value = Number(budgetRange.value);
        const percent = ((value - min) / (max - min)) * 100;
        budgetRange.style.background = `linear-gradient(to right, #ead2bb 0%, #c58b56 ${percent}%, #fff ${percent}%, #fff 100%)`;
        if (budgetValue) budgetValue.textContent = value.toLocaleString('vi-VN');
    };
    if (budgetRange) {
        budgetRange.addEventListener('input', updateBudget);
        updateBudget();
    }

    const amenityDropdown = document.getElementById('amenityDropdown');
    const amenitySummary = document.getElementById('amenitySummaryText');
    if (amenityDropdown && amenitySummary) {
        const checkboxes = [...amenityDropdown.querySelectorAll('input[name="amenities[]"]')];
        const updateSummary = () => {
            const selected = checkboxes.filter(item => item.checked);
            if (!selected.length) amenitySummary.textContent = 'Chọn tiện nghi';
            else if (selected.length === 1) {
                amenitySummary.textContent = selected[0].closest('label')?.querySelector('span')?.textContent.trim() || 'Đã chọn 1 tiện nghi';
            } else amenitySummary.textContent = `Đã chọn ${selected.length} tiện nghi`;
        };
        checkboxes.forEach(item => item.addEventListener('change', updateSummary));
        document.addEventListener('click', event => {
            if (amenityDropdown.open && !amenityDropdown.contains(event.target)) amenityDropdown.removeAttribute('open');
        });
        updateSummary();
    }

    document.querySelectorAll('.auto-slide-img').forEach(image => {
        const images = (image.dataset.images || '').split('|||').map(item => item.trim()).filter(Boolean);
        if (images.length <= 1) return;
        let index = 0;
        window.setInterval(() => {
            index = (index + 1) % images.length;
            image.style.opacity = '0.25';
            window.setTimeout(() => {
                image.src = images[index];
                image.style.opacity = '1';
            }, 220);
        }, 4300);
    });

    initCompareDock();
}

/** Thanh so sánh: chỉ có mặt ở trang chủ và giới hạn năm khách sạn. */
function initCompareDock() {
    const checkboxes = [...document.querySelectorAll('.cb-compare')];
    const dock = document.getElementById('compareDock');
    const count = document.getElementById('compareCount');
    const form = document.getElementById('compareForm');
    const clearButton = document.getElementById('btnClearCompare');
    if (!checkboxes.length || !dock || !count || !form) return;

    const selectedIds = () => checkboxes.filter(item => item.checked).map(item => item.value);
    const render = () => {
        const ids = selectedIds();
        count.textContent = String(ids.length);
        dock.classList.toggle('hidden', ids.length === 0);
        document.body.classList.toggle('compare-dock-visible', ids.length > 0);
        form.querySelectorAll('input[name="hotel_ids[]"]').forEach(input => input.remove());
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hotel_ids[]';
            input.value = id;
            form.appendChild(input);
        });
    };

    checkboxes.forEach(item => item.addEventListener('change', function () {
        if (selectedIds().length > 5) {
            this.checked = false;
            window.alert('Bạn chỉ có thể chọn tối đa 5 khách sạn để so sánh.');
        }
        render();
    }));
    clearButton?.addEventListener('click', () => {
        checkboxes.forEach(item => { item.checked = false; });
        render();
    });
    form.addEventListener('submit', event => {
        if (!selectedIds().length) {
            event.preventDefault();
            window.alert('Vui lòng chọn ít nhất một khách sạn.');
        }
    });
    render();
}

/** Trang chi tiết: carousel ảnh phụ, tự chạy và dừng khi rê chuột. */
function initHotelDetailPage() {
    const carousel = document.getElementById('hotelCarousel');
    if (!carousel) return;

    const track = carousel.querySelector('.hotel-carousel-track');
    const slides = [...carousel.querySelectorAll('.hotel-carousel-slide')];
    const dots = [...carousel.querySelectorAll('.hotel-carousel-dot')];
    if (!track || slides.length <= 1) return;

    let currentIndex = 0;
    let timer;
    const showSlide = index => {
        currentIndex = (index + slides.length) % slides.length;
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
        dots.forEach((dot, i) => dot.classList.toggle('active', i === currentIndex));
    };
    const start = () => {
        window.clearInterval(timer);
        timer = window.setInterval(() => showSlide(currentIndex + 1), 3000);
    };

    carousel.querySelector('.hotel-carousel-button.previous')?.addEventListener('click', () => {
        showSlide(currentIndex - 1); start();
    });
    carousel.querySelector('.hotel-carousel-button.next')?.addEventListener('click', () => {
        showSlide(currentIndex + 1); start();
    });
    dots.forEach(dot => dot.addEventListener('click', () => { showSlide(Number(dot.dataset.index)); start(); }));
    carousel.addEventListener('mouseenter', () => window.clearInterval(timer));
    carousel.addEventListener('mouseleave', start);
    showSlide(0);
    start();
}

/** Trang cộng đồng: like, preview ảnh và slider/menu/xóa bài. */
function initCommunityPage() {
    document.querySelectorAll('.btn-like').forEach(button => button.addEventListener('click', async function () {
        const count = this.querySelector('.like-count');
        this.disabled = true;
        try {
            const response = await fetch('api.php?action=toggle-like', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: new URLSearchParams({ post_id: this.dataset.id || '', csrf_token: csrfToken }),
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Không thể cập nhật lượt thích.');
            count.textContent = data.likes_count;
            this.classList.toggle('is-liked', Boolean(data.liked));
            this.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
        } catch (error) {
            window.alert(error.message || 'Có lỗi xảy ra.');
        } finally {
            this.disabled = false;
        }
    }));

    const fileInput = document.getElementById('post_images');
    const preview = document.getElementById('image-preview-container');
    fileInput?.addEventListener('change', function () {
        preview.innerHTML = '';
        const files = [...this.files];
        if (files.length > 10) {
            window.alert('Bạn chỉ được đăng tối đa 10 ảnh trong một bài viết!');
            this.value = '';
            return;
        }
        files.filter(file => file.type.startsWith('image/')).forEach(file => {
            const reader = new FileReader();
            reader.onload = event => {
                const wrapper = document.createElement('div');
                wrapper.className = 'preview-item';
                const image = document.createElement('img');
                image.src = event.target.result;
                wrapper.appendChild(image);
                preview.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        });
    });
}

const communitySliderStates = {};
window.moveSlider = (postId, direction) => {
    const slider = document.getElementById(`slider-${postId}`);
    const total = slider?.querySelectorAll('.slide-item').length || 0;
    if (total <= 1) return;
    communitySliderStates[postId] = ((communitySliderStates[postId] || 0) + direction + total) % total;
    updateCommunitySlider(postId);
};
window.currentSlide = (postId, index) => {
    communitySliderStates[postId] = index;
    updateCommunitySlider(postId);
};
function updateCommunitySlider(postId) {
    const slider = document.getElementById(`slider-${postId}`);
    if (!slider) return;
    const index = communitySliderStates[postId] || 0;
    slider.style.transform = `translateX(-${index * 100}%)`;
    slider.closest('.post-images-slider-wrapper')?.querySelectorAll('.slider-dots .dot')
        .forEach((dot, i) => dot.classList.toggle('active', i === index));
}
window.toggleMenu = (postId, event) => {
    event.stopPropagation();
    const menu = document.getElementById(`menu-${postId}`);
    const isOpen = menu?.classList.contains('open');
    document.querySelectorAll('.post-menu-dropdown.open').forEach(item => item.classList.remove('open'));
    if (!isOpen) menu?.classList.add('open');
};
document.addEventListener('click', () => {
    document.querySelectorAll('.post-menu-dropdown.open').forEach(item => item.classList.remove('open'));
});
window.deletePost = async (postId, button) => {
    if (!window.confirm('Bạn có chắc muốn xóa bài đăng này không?')) return;
    button.disabled = true;
    button.textContent = 'Đang xóa...';
    try {
        const response = await fetch('api.php?action=delete-post', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams({ post_id: postId, csrf_token: csrfToken }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Không thể xóa bài.');
        const card = button.closest('.post-card');
        card.classList.add('post-removing');
        card.addEventListener('animationend', () => card.remove(), { once: true });
    } catch (error) {
        window.alert(error.message || 'Lỗi kết nối. Vui lòng thử lại.');
        button.disabled = false;
        button.textContent = '🗑️ Xóa bài đăng';
    }
};

/** Form thêm khách sạn: lấy dữ liệu Maps rồi điền vào các trường admin. */
function initAdminAddPage() {
    const fetchButton = document.getElementById('btnFetchInfo');
    if (!fetchButton) return;
    fetchButton.addEventListener('click', async () => {
        const input = document.getElementById('googleMapsUrl');
        const url = input.value.trim();
        if (!url) return window.alert('Vui lòng nhập tên hoặc dán link Google Maps trước!');

        const loading = document.getElementById('fetchLoading');
        loading.style.display = 'inline-block';
        fetchButton.disabled = true;
        try {
            const response = await fetch('api.php?action=fetch-map', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: new URLSearchParams({ url, csrf_token: csrfToken }),
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Không lấy được thông tin.');
            const setValue = (id, value) => {
                const field = document.getElementById(id);
                if (field) field.value = value || '';
            };
            setValue('adminName', data.name);
            setValue('adminAddress', data.address);
            setValue('adminPhone', (data.phone || '').replace(/\s+/g, ''));
            setValue('adminStars', data.stars || 4);
            setValue('adminDescription', data.description);
            window.alert('Tự động điền dữ liệu từ Google Maps thành công! Hãy kiểm tra trước khi lưu.');
        } catch (error) {
            window.alert(error.message || 'Có lỗi xảy ra trong quá trình xử lý yêu cầu.');
        } finally {
            loading.style.display = 'none';
            fetchButton.disabled = false;
        }
    });
}

/** Hành vi nhẹ dùng ở các card cũ nếu trang đó có phần tử phù hợp. */
function initGenericCardInteractions() {
    document.querySelectorAll('.btn-toggle-detail').forEach(button => button.addEventListener('click', function () {
        const detail = this.closest('.card-content')?.querySelector('.dropdown-detail');
        if (detail) detail.style.display = detail.style.display === 'none' ? 'block' : 'none';
    }));
}
