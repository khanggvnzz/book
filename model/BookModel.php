<?php

class BookModel
{
    private $db;

    public function __construct($conn)
    {
        $this->db = $conn;
    }

    // Create a new book
    public function createBook($title, $author, $price, $description, $category, $publisher, $image)
    {
        $sql = "INSERT INTO books (title, author, price, description, category, publisher, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$title, $author, $price, $description, $category, $publisher, $image]);
    }

    // Get all books
    public function getAllBooks()
    {
        $sql = "SELECT * FROM books ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get book by ID
    public function getBookById($id)
    {
        $sql = "SELECT * FROM books WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update book
    public function updateBook($id, $title, $author, $price, $description, $category, $publisher, $image)
    {
        $sql = "UPDATE books 
                SET title = ?, author = ?, price = ?, description = ?, category = ?, publisher = ?, image = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$title, $author, $price, $description, $category, $publisher, $image, $id]);
    }

    // Delete book
    public function deleteBook($id)
    {
        $sql = "DELETE FROM books WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    // Search books
    public function searchBooks($keyword, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $keyword = "%$keyword%";

        $sql = "SELECT * FROM books 
                WHERE title LIKE :keyword 
                OR author LIKE :keyword 
                OR description LIKE :keyword 
                OR category LIKE :keyword 
                ORDER BY id DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':keyword', $keyword, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalSearchResults($keyword)
    {
        $keyword = "%$keyword%";
        $sql = "SELECT COUNT(*) as total FROM books 
                WHERE title LIKE :keyword 
                OR author LIKE :keyword 
                OR description LIKE :keyword 
                OR category LIKE :keyword";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':keyword', $keyword, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Get books by category
    public function getBooksByCategory($category)
    {
        $sql = "SELECT * FROM books WHERE category = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get books by publisher
    public function getBooksByPublisher($publisher)
    {
        $sql = "SELECT * FROM books WHERE publisher = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publisher]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all categories
    public function getAllCategories()
    {
        try {
            $sql = "SELECT * FROM categories ORDER BY name ASC";
            // Kiểm tra kết nối database
            if (!$this->db) {
                throw new Exception("Database connection is null");
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Log lỗi
            error_log('Error in getAllCategories: ' . $e->getMessage());
            // Trả về mảng trống thay vì gây lỗi
            return [];
        }
    }

    public function getBooks($page = 1, $perPage = 20, $category = null, $sort = null)
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM books";
        $params = [];

        if ($category) {
            $sql .= " WHERE category = :category";
            $params[':category'] = $category;
        }

        // Add sorting
        switch ($sort) {
            case 'price_asc':
                $sql .= " ORDER BY price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY price DESC";
                break;
            case 'title_asc':
                $sql .= " ORDER BY title ASC";
                break;
            case 'title_desc':
                $sql .= " ORDER BY title DESC";
                break;
            default:
                $sql .= " ORDER BY id DESC";
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalBooks($category = null)
    {
        if ($category) {
            $sql = "SELECT COUNT(*) as total FROM books WHERE category = :category";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(*) as total FROM books";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}