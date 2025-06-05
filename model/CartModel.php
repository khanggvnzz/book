<?php

class CartModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getCartItems($userId)
    {
        $query = "SELECT c.book_id, c.quantity, b.title, b.author, b.price, b.image 
                 FROM cart c
                 JOIN books b ON c.book_id = b.id
                 WHERE c.user_id = :user_id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addToCart($userId, $bookId, $quantity = 1)
    {
        // First check if item already exists in cart
        $query = "SELECT quantity FROM cart WHERE user_id = :user_id AND book_id = :book_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':book_id', $bookId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Item exists, update quantity
            $currentQty = $stmt->fetch(PDO::FETCH_ASSOC)['quantity'];
            $newQty = $currentQty + $quantity;

            return $this->updateCartItem($userId, $bookId, $newQty);
        } else {
            // Item doesn't exist, insert new item
            $query = "INSERT INTO cart (user_id, book_id, quantity) VALUES (:user_id, :book_id, :quantity)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':book_id', $bookId);
            $stmt->bindParam(':quantity', $quantity);

            return $stmt->execute();
        }
    }

    public function updateCartItem($userId, $bookId, $quantity)
    {
        $query = "UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND book_id = :book_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':book_id', $bookId);

        return $stmt->execute();
    }

    public function removeFromCart($userId, $bookId)
    {
        $query = "DELETE FROM cart WHERE user_id = :user_id AND book_id = :book_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':book_id', $bookId);

        return $stmt->execute();
    }

    public function clearCart($userId)
    {
        $query = "DELETE FROM cart WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    public function getCartItemCount($userId)
    {
        $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ? (int) $result['count'] : 0;
    }

    public function transferSessionCartToDatabase($userId, $sessionCart)
    {
        $success = true;

        foreach ($sessionCart as $bookId => $quantity) {
            if (!$this->addToCart($userId, $bookId, $quantity)) {
                $success = false;
            }
        }

        return $success;
    }
}