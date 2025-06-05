<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/BookModel.php';
require_once __DIR__ . '/../controller/search.php';

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

// Redirect to index if no search keyword
if (empty($searchKeyword)) {
    header('Location: index.php');
    exit;
}

// Log search query for debugging
error_log("Searching for: " . $searchKeyword);

// Use SearchController to perform search
$searchResult = $searchController->search($searchKeyword, $page, $perPage);
$books = $searchResult['books'];
$totalPages = $searchResult['totalPages'];
$totalBooks = $searchResult['totalBooks'];

// Set page title to reflect search
$pageTitle = 'Search Results for "' . htmlspecialchars($searchKeyword) . '"';

// Get all categories (needed for navigation)
$categories = $bookModel->getAllCategories();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - TheBook.PK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <?php
    include __DIR__ . '/navigation/navigation.php';
    ?>

    <div class="container mt-4">
        <!-- Search header -->
        <div class="search-header py-3">
            <h1 class="search-title">Search Results</h1>
            <p class="text-muted">Showing results for:
                <strong>"<?php echo htmlspecialchars($searchKeyword); ?>"</strong>
            </p>
            <p class="search-count">Found <?php echo $totalBooks; ?> book(s)</p>

            <!-- Search form -->
            <form class="d-flex mt-4 mb-5" method="GET" action="search_page.php">
                <input class="form-control me-2" type="search" name="search" placeholder="Find your book here..."
                    value="<?php echo htmlspecialchars($searchKeyword); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
        </div>

        <!-- Fixed Action Buttons -->
        <div class="action-buttons-fixed">
            <button class="btn btn-success rounded-pill"><i class="bi bi-cart"></i> View Cart</button>
            <button class="btn btn-outline-success rounded-pill">Request a Book</button>
        </div>

        <!-- Search Results Section -->
        <div class="py-4">
            <?php if (count($books) === 0): ?>
                <div class="alert alert-info text-center my-5">
                    <h4 class="alert-heading">No results found</h4>
                    <p>We couldn't find any books matching your search criteria.</p>
                    <p>Try different keywords or <a href="index.php" class="alert-link">browse all books</a>.</p>
                </div>
            <?php else: ?>
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
                                    style="height: 300px; object-fit: contain;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="card-text">By <?php echo htmlspecialchars($book['author']); ?></p>
                                    <p class="card-text text-primary fw-bold">$<?php echo number_format($book['price'], 2); ?>
                                    </p>
                                    <div class="d-flex justify-content-between">
                                        <a href="book-details.php?id=<?php echo $book['id']; ?>"
                                            class="btn btn-outline-primary">View Details</a>
                                        <button class="btn btn-success">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5">
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