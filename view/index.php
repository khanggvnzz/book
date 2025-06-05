<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/BookModel.php';
require_once __DIR__ . '/../model/CartModel.php';
require_once __DIR__ . '/../controller/search.php';

// Handle routing based on URL parameter
$url = isset($_GET['url']) ? trim($_GET['url']) : 'home';

// Route to specific pages
if ($url === 'login') {
    // Include login.php from the login directory
    if (file_exists(__DIR__ . '/login/login.php')) {
        include __DIR__ . '/login/login.php';
        exit; // Stop further execution after including login.php
    } else {
        // Show 404 if login.php doesn't exist
        header('HTTP/1.0 404 Not Found');
        include __DIR__ . '/404.php';
        exit;
    }
}

// Default logic for book display (original code)
$database = new Database();
$db = $database->connect();
$bookModel = new BookModel($db);

// Pagination settings
$perPage = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;

// Initialize SearchController
$searchController = new SearchController($bookModel);

// Check if search is being performed
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedCategory = null;

if (!empty($searchKeyword)) {
    // Log search query for debugging
    error_log("Searching for: " . $searchKeyword);

    // Use SearchController to perform search
    $searchResult = $searchController->search($searchKeyword, $page, $perPage);
    $books = $searchResult['books'];
    $totalPages = $searchResult['totalPages'];
    $totalBooks = $searchResult['totalBooks'];

    // Set page title to reflect search
    $pageTitle = 'Search Results for "' . htmlspecialchars($searchKeyword) . '"';
} else {
    // Get selected category from URL
    $selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
    $totalBooks = $bookModel->getTotalBooks($selectedCategory);
    $totalPages = ceil($totalBooks / $perPage);

    // Get sort parameter
    $sort = isset($_GET['sort']) ? $_GET['sort'] : null;
    $books = $bookModel->getBooks($page, $perPage, $selectedCategory, $sort);

    // Set default page title
    $pageTitle = $selectedCategory ? htmlspecialchars($selectedCategory) . ' Books' : 'Latest Books';
}

// Get all categories
$categories = $bookModel->getAllCategories();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheBook.PK - Online BookStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <?php
    include __DIR__ . '/navigation/navigation.php';
    ?>

    <!-- Action Buttons Row -->
    <div class="container mt-2">
        <!-- Fixed Action Buttons -->
        <div class="action-buttons-fixed">
            <button class="btn btn-success rounded-pill view-cart-btn"><i class="bi bi-cart"></i> View Cart</button>
            <button class="btn btn-outline-success rounded-pill">Request a Book</button>
        </div>

        <!-- Hero Section -->
        <div class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6 hero-text">
                        <div class="hero-stats">
                            <p>200+ Authors</p>
                            <p>20k+ Books</p>
                        </div>
                        <h1>THEBOOKSTORE.COM</h1>
                        <p class="lead">Get into our Store</p>
                        <p>Here every book is a new adventure</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <img src="images/huit/huit.png" alt="Person reading a book" class="img-fluid hero-image-small">
                    </div>
                </div>
            </div>
        </div>

        <?php
        include __DIR__ . '/banner/banner.php';
        ?>

        <!-- Book Shelf Section -->
        <div class="container mt-5">
            <h2 class="section-title">Book Shelf</h2>

            <div class="filter-tabs">
                <div class="filter-tab active">New arrivals</div>
                <div class="filter-tab">Best Selling</div>
            </div>

            <div class="row">
                <?php
                $featuredBooks = array_slice($books, 0, 4);
                foreach ($featuredBooks as $book): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="book-card h-100">
                            <?php
                            $imagePath = 'images/books/' . ($book['image'] ?: 'default-book.jpg');
                            $fullImagePath = file_exists(__DIR__ . '/' . $imagePath) ? $imagePath : 'images/default-book.jpg';
                            ?>
                            <img src="<?php echo htmlspecialchars($fullImagePath); ?>" class="book-image"
                                alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <div class="p-3">
                                <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                <small class="text-muted d-block mb-2">By
                                    <?php echo htmlspecialchars($book['author']); ?>
                                </small>
                                <p class="card-text text-primary fw-bold">$<?php echo number_format($book['price'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-end mb-5">
                <a href="#" class="view-more">View More <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>

        <!-- Main Books Section -->
        <div class="container py-5">
            <h2 class="section-title">
                <?php echo $selectedCategory ? htmlspecialchars($selectedCategory) . ' Books' : 'Latest Books'; ?>
            </h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php
                            $imagePath = 'images/books/' . ($book['image'] ?: 'default-book.jpg');
                            $fullImagePath = file_exists(__DIR__ . '/' . $imagePath) ? $imagePath : 'images/default-book.jpg';
                            ?>
                            <img src="<?php echo htmlspecialchars($fullImagePath); ?>" class="card-img-top"
                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                style="height: 300px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                <p class="card-text">By <?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="card-text text-primary fw-bold">$<?php echo number_format($book['price'], 2); ?>
                                </p>
                                <div class="d-flex justify-content-between">
                                    <a href="book-details.php?id=<?php echo $book['id']; ?>"
                                        class="btn btn-outline-primary">View Details</a>
                                    <button class="btn btn-success add-to-cart"
                                        data-book-id="<?php echo $book['id']; ?>">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- Previous page link -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php
                            echo http_build_query(array_merge($_GET, ['page' => $page - 1]));
                            ?>">Previous</a>
                        </li>

                        <!-- Page numbers -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php
                                echo http_build_query(array_merge($_GET, ['page' => $i]));
                                ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next page link -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php
                            echo http_build_query(array_merge($_GET, ['page' => $page + 1]));
                            ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>

        <?php
        include __DIR__ . '/footer/footer.php';
        ?>

        <!-- Back to top button -->
        <button id="backToTopBtn" class="btn btn-success rounded-circle back-to-top-btn" title="Go to top">
            <i class="bi bi-arrow-up"></i>
        </button>

        <script src="js/main.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>

</html>