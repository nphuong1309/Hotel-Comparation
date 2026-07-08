document.addEventListener("DOMContentLoaded", () => {
    // 1. Cập nhật text thanh kéo ngân sách
    const budgetRange = document.getElementById('budgetRange');
    const budgetValue = document.getElementById('budgetValue');
    if (budgetRange) {
        budgetRange.addEventListener('input', (e) => {
            budgetValue.textContent = Number(e.target.value).toLocaleString('vi-VN');
        });
    }

    // 2. Tính năng thả xuống Xem chi tiết
    const toggleBtns = document.querySelectorAll('.btn-toggle-detail');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const dropdown = this.closest('.card-content').querySelector('.dropdown-detail');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
    });

    // 3. Hiệu ứng tự nhảy ảnh (Image Carousel tự động)
    const autoImages = document.querySelectorAll('.auto-slide-img');
    autoImages.forEach(img => {
        const imageList = img.getAttribute('data-images');
        if (imageList) {
            const images = imageList.split(',').filter(src => src.trim() !== '');
            if (images.length > 1) {
                let currentIndex = 0;
                setInterval(() => {
                    currentIndex = (currentIndex + 1) % images.length;
                    img.style.opacity = 0.8; // Tạo hiệu ứng mờ nhạt khi chuyển
                    setTimeout(() => {
                        img.src = images[currentIndex];
                        img.style.opacity = 1;
                    }, 150);
                }, 30000); // Đổi ảnh mỗi 3 giây
            }
        }
    });

    // 4. Xử lý logic So sánh (Tối đa 5 khách sạn)
    const compareCheckboxes = document.querySelectorAll('.cb-compare');
    const compareDock = document.getElementById('compareDock');
    const compareCount = document.getElementById('compareCount');
    const compareForm = document.getElementById('compareForm');
    const btnClearCompare = document.getElementById('btnClearCompare');

    let selectedIds = [];

    function updateDock() {
        compareCount.textContent = selectedIds.length;
        if (selectedIds.length > 0) {
            compareDock.classList.remove('hidden');
        } else {
            compareDock.classList.add('hidden');
        }

        // Tạo các input hidden gửi qua GET
        compareForm.innerHTML = '<button type="submit" class="btn-primary">Bắt đầu so sánh</button>';
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hotel_ids[]';
            input.value = id;
            compareForm.appendChild(input);
        });
    }

    compareCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) {
                if (selectedIds.length >= 5) {
                    alert("Bạn chỉ được chọn tối đa 5 khách sạn để so sánh!");
                    this.checked = false;
                    return;
                }
                selectedIds.push(this.value);
            } else {
                selectedIds = selectedIds.filter(id => id !== this.value);
            }
            updateDock();
        });
    });

    if (btnClearCompare) {
        btnClearCompare.addEventListener('click', () => {
            selectedIds = [];
            compareCheckboxes.forEach(cb => cb.checked = false);
            updateDock();
        });
    }
});

// 5. Frontend Form Validation cho Admin
function validateAdminForm() {
    const name = document.getElementById('adminName').value;
    const price2 = document.getElementById('adminPrice2').value;
    const price4 = document.getElementById('adminPrice4').value;

    if (name.trim() === '') {
        alert('Tên khách sạn không được để trống.');
        return false;
    }
    if (Number(price2) <= 0 || Number(price4) <= 0) {
        alert('Giá phòng phải là số dương lớn hơn 0.');
        return false;
    }
    return true;
}