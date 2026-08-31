document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.storefront-menu-toggle');
    const storefrontNavigation = document.getElementById('storefront-navigation');

    if (menuToggle && storefrontNavigation) {
        menuToggle.addEventListener('click', function() {
            const isOpen = storefrontNavigation.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            menuToggle.setAttribute('aria-label', isOpen ? 'Đóng menu điều hướng' : 'Mở menu điều hướng');
        });

        storefrontNavigation.addEventListener('click', function(event) {
            if (event.target.closest('a')) {
                storefrontNavigation.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'Mở menu điều hướng');
            }
        });
    }

    // Lắng nghe sự kiện submit của mọi form trên trang
    document.addEventListener('submit', function(event) {
        const form = event.target;
        
        // Kiểm tra xem form có gửi đến trang add.php của giỏ hàng hay không
        if (form.action && form.action.includes('cart/add.php')) {
            // Chặn chuyển trang
            event.preventDefault();
            
            const formData = new FormData(form);
            
            // Gửi yêu cầu AJAX qua Fetch API
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Cập nhật số lượng giỏ hàng trên badge
                const badge = document.querySelector('.header-cart-badge');
                if (badge && typeof data.cart_count !== 'undefined') {
                    badge.textContent = data.cart_count;
                }
                
                // Hiển thị Toast thông báo
                if (data.message) {
                    showToast(data.message, data.status);
                }
            })
            .catch(error => {
                console.error('Lỗi khi thêm giỏ hàng AJAX:', error);
                // Fallback nếu có lỗi xảy ra thì submit form truyền thống
                form.submit();
            });
        }
    });
});
