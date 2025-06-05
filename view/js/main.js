// Định nghĩa base URL
const BASE_URL = '/BookStore';

// Hàm helper để tạo URL
function getUrl(path) {
    return BASE_URL + '/' + path.replace(/^\/+/, '');
}

// Back to top button functionality
document.addEventListener('DOMContentLoaded', function () {
    const backToTopBtn = document.getElementById('backToTopBtn');

    // Show button when user scrolls down 300px
    window.addEventListener('scroll', function () {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });

    // Scroll to top when button is clicked
    backToTopBtn.addEventListener('click', function (e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Cart popup functionality
    const viewCartBtn = document.querySelector('.action-buttons-fixed .btn-success');
    const cartPopupOverlay = document.createElement('div');
    cartPopupOverlay.className = 'cart-popup-overlay';
    document.body.appendChild(cartPopupOverlay);

    if (viewCartBtn) {
        viewCartBtn.addEventListener('click', function () {
            showCartPopup();
        });
    }

    function showCartPopup() {
        fetch('cart.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.text())
            .then(html => {
                cartPopupOverlay.innerHTML = html;
                cartPopupOverlay.classList.add('active');
                document.body.classList.add('cart-popup-open');

                // Add event listeners to close buttons
                const closeButtons = cartPopupOverlay.querySelectorAll('.close-cart-popup');
                closeButtons.forEach(button => {
                    button.addEventListener('click', closeCartPopup);
                });

                // Close popup when clicking outside
                cartPopupOverlay.addEventListener('click', function (e) {
                    if (e.target === cartPopupOverlay) {
                        closeCartPopup();
                    }
                });
            })
            .catch(error => {
                console.error('Error loading cart:', error);
            });
    }

    function closeCartPopup() {
        cartPopupOverlay.classList.remove('active');
        document.body.classList.remove('cart-popup-open');
    }

    // Add to Cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function () {
            const bookId = this.dataset.bookId;

            fetch(getUrl('view/update_cart.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `book_id=${bookId}&quantity=1&action=add`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('Book added to cart successfully!');
                    } else {
                        alert('Error adding book to cart: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    });
});