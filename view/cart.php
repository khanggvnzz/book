<?php

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/BookModel.php';
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/CartModel.php';

$database = new Database();
$db = $database->connect();
$bookModel = new BookModel($db);
$cartModel = new CartModel($db);

// Kiểm tra người dùng đã đăng nhập chưa
$isLoggedIn = isset($_SESSION['user_id']);
$cartItems = [];
$totalAmount = 0;

if ($isLoggedIn) {
    // Lấy giỏ hàng từ database cho người dùng đã đăng nhập
    $userId = $_SESSION['user_id'];
    $cartItems = $cartModel->getCartItems($userId);
} else {
    // Lấy giỏ hàng từ session cho người dùng chưa đăng nhập
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $bookId => $quantity) {
            $book = $bookModel->getBookById($bookId);
            if ($book) {
                $cartItems[] = [
                    'id' => $bookId,
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'price' => $book['price'],
                    'image' => $book['image'],
                    'quantity' => $quantity
                ];
            }
        }
    }
}

// Tính tổng tiền
foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// Xử lý Ajax request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    // Nếu là request Ajax, chỉ hiển thị nội dung giỏ hàng
    include __DIR__ . '/cart/cart_content.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - TheBook.PK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            transition: opacity 0.5s, transform 0.5s;
        }

        .cart-item.removing {
            opacity: 0;
            transform: translateX(50px);
        }

        .btn-danger {
            transition: all 0.3s;
        }

        .btn-danger:hover {
            background-color: #dc3545;
            transform: scale(1.1);
        }

        .cart-item-image {
            width: 80px;
            height: 100px;
            object-fit: contain;
            background-color: #f8f9fa;
            margin-right: 15px;
        }

        .cart-item-info {
            flex-grow: 1;
        }

        .cart-item-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .cart-item-author {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .cart-item-price {
            font-weight: bold;
            color: #0d6efd;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }

        .cart-item-quantity input {
            width: 50px;
            text-align: center;
        }

        .cart-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/navigation/navigation.php'; ?>

    <div class="container mt-4 mb-5">
        <h1 class="mb-4">Your Shopping Cart</h1>

        <?php if (empty($cartItems)): ?>
            <div class="alert alert-info">
                <p>Your cart is empty. <a href="index.php" class="alert-link">Continue shopping</a>.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item">
                                    <?php
                                    $imagePath = 'images/books/' . ($item['image'] ?: 'default-book.jpg');
                                    $fullImagePath = file_exists(__DIR__ . '/' . $imagePath) ? $imagePath : 'images/default-book.jpg';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($fullImagePath); ?>"
                                        alt="<?php echo htmlspecialchars($item['title']); ?>" class="cart-item-image">
                                    <div class="cart-item-info">
                                        <h5 class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                        <p class="cart-item-author">By <?php echo htmlspecialchars($item['author']); ?></p>
                                        <p class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <div class="cart-item-quantity">
                                        <button class="btn btn-sm btn-outline-secondary me-2 update-quantity"
                                            data-book-id="<?php echo $item['id']; ?>" data-action="decrease">-</button>
                                        <input type="number" min="1" value="<?php echo $item['quantity']; ?>"
                                            class="form-control quantity-input" data-book-id="<?php echo $item['id']; ?>">
                                        <button class="btn btn-sm btn-outline-secondary ms-2 update-quantity"
                                            data-book-id="<?php echo $item['id']; ?>" data-action="increase">+</button>
                                    </div>
                                    <button class="btn btn-danger btn-sm remove-item" data-book-id="<?php echo $item['id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4>Order Summary</h4>
                        <div class="d-flex justify-content-between mt-3">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($totalAmount, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span>$<?php echo number_format($totalAmount, 2); ?></span>
                        </div>
                        <div class="mt-3">
                            <a href="checkout.php" class="btn btn-success w-100">Proceed to Checkout</a>
                        </div>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-outline-primary w-100">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/footer/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cart functionality
        document.addEventListener('DOMContentLoaded', function () {
            // Update quantity
            document.querySelectorAll('.update-quantity').forEach(button => {
                button.addEventListener('click', function () {
                    const bookId = this.dataset.bookId;
                    const action = this.dataset.action;
                    const inputElement = document.querySelector(`.quantity-input[data-book-id="${bookId}"]`);
                    let quantity = parseInt(inputElement.value);

                    if (action === 'increase') {
                        quantity += 1;
                    } else if (action === 'decrease' && quantity > 1) {
                        quantity -= 1;
                    }

                    inputElement.value = quantity;
                    updateCart(bookId, quantity);
                });
            });

            // Update on manual input
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function () {
                    const bookId = this.dataset.bookId;
                    let quantity = parseInt(this.value);

                    if (isNaN(quantity) || quantity < 1) {
                        quantity = 1;
                        this.value = 1;
                    }

                    updateCart(bookId, quantity);
                });
            });

            // Remove item
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function () {
                    const bookId = this.dataset.bookId;
                    const itemElement = this.closest('.cart-item');
                    const bookTitle = itemElement.querySelector('.cart-item-title').textContent;

                    // Xác nhận trước khi xóa
                    if (confirm(`Remove "${bookTitle}" from your cart?`)) {
                        // Thêm hiệu ứng fade-out
                        itemElement.style.transition = 'opacity 0.5s';
                        itemElement.style.opacity = '0';

                        // Đợi hiệu ứng kết thúc rồi mới xóa phần tử
                        setTimeout(() => {
                            // Gửi request xóa đến server
                            updateCart(bookId, 0).then(() => {
                                // Xóa phần tử khỏi DOM
                                itemElement.remove();

                                // Cập nhật tổng tiền
                                updateTotals();

                                // Kiểm tra nếu giỏ hàng trống
                                if (document.querySelectorAll('.cart-item').length === 0) {
                                    // Nếu không còn sản phẩm nào, hiển thị thông báo giỏ hàng trống
                                    const cardBody = document.querySelector('.card-body');
                                    cardBody.innerHTML = `
                                        <div class="alert alert-info">
                                            <p>Your cart is empty. <a href="index.php" class="alert-link">Continue shopping</a>.</p>
                                        </div>`;

                                    // Ẩn phần summary
                                    document.querySelector('.cart-summary').parentElement.style.display = 'none';
                                }
                            });
                        }, 500);
                    }
                });
            });

            // Function to update cart via AJAX
            function updateCart(bookId, quantity) {
                return fetch('/BookStore/view/cart/update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `book_id=${bookId}&quantity=${quantity}`
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Cập nhật giao diện nếu cần
                            if (quantity > 0) {
                                updateTotals();
                            }
                            return true;
                        } else {
                            alert('Error updating cart: ' + (data.message || 'Unknown error'));
                            return false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update cart. Please try again.');
                        return false;
                    });
            }

            // Function to update totals without reloading the page
            function updateTotals() {
                // Tính toán tổng tiền từ các sản phẩm hiện có
                let total = 0;
                document.querySelectorAll('.cart-item').forEach(item => {
                    const price = parseFloat(item.querySelector('.cart-item-price').textContent.replace('$', ''));
                    const quantity = parseInt(item.querySelector('.quantity-input').value);
                    total += price * quantity;
                });

                // Cập nhật hiển thị tổng tiền
                const totalElements = document.querySelectorAll('.cart-summary .fw-bold span');
                const subtotalElement = document.querySelector('.cart-summary .d-flex:first-child span:last-child');

                if (subtotalElement) {
                    subtotalElement.textContent = `$${total.toFixed(2)}`;
                }

                totalElements.forEach(el => {
                    el.textContent = `$${total.toFixed(2)}`;
                });
            }
        });
    </script>
</body>

</html>