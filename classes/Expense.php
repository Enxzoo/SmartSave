<?php
class Expense
{
    private $conn;
    private $table = "expenses";
    private int $userId;

    public function __construct($db, int $userId)
    {
        $this->conn = $db;
        $this->userId = $userId;
    }

    public function addExpense($description, $amount, $date, $user_id, $category_id)
    {
        $sql = "INSERT INTO expenses
                (description, amount, date, user_id, category_id)
                VALUES
                (:description, :amount, :date, :user_id, :category_id)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":description" => $description,
            ":amount" => $amount,
            ":date" => $date,
            ":user_id" => $this->userId,
            ":category_id" => $category_id
        ]);
    }

    public function getExpenses()
    {
        $sql = "SELECT e.*, c.category_name
                FROM expenses e
                LEFT JOIN categories c ON e.category_id = c.category_id
                WHERE e.user_id = :user_id
                ORDER BY e.expenses_id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":user_id" => $this->userId]);

        return $stmt->fetchAll();
    }

    public function getExpenseById($id)
    {
        $sql = "SELECT e.*, c.category_name
                FROM expenses e
                LEFT JOIN categories c ON e.category_id = c.category_id
                WHERE e.expenses_id = :id AND e.user_id = :user_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $id, ":user_id" => $this->userId]);

        return $stmt->fetch();
    }

    public function getTotalExpenses()
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":user_id" => $this->userId]);

        return (float) $stmt->fetch()["total"];
    }

    public function getCategories()
    {
        $sql = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function updateExpense($id, $description, $amount, $date, $user_id, $category_id)
    {
        if (!$this->getExpenseById($id)) {
            throw new InvalidArgumentException('This expense is no longer available.');
        }
        $sql = "UPDATE expenses
                SET description = :description,
                    amount = :amount,
                    date = :date,
                    user_id = :user_id,
                    category_id = :category_id
                WHERE expenses_id = :id AND user_id = :owner_id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":description" => $description,
            ":amount" => $amount,
            ":date" => $date,
            ":user_id" => $this->userId,
            ":owner_id" => $this->userId,
            ":category_id" => $category_id,
            ":id" => $id
        ]);
    }

    public function deleteExpense($id)
    {
        if (!$this->getExpenseById($id)) {
            throw new InvalidArgumentException('This expense is no longer available.');
        }
        $sql = "DELETE FROM expenses
                WHERE expenses_id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":id" => $id, ":user_id" => $this->userId]);
    }

    public function searchExpenses($keyword)
    {
        $sql = "SELECT e.*, c.category_name
                FROM expenses e
                LEFT JOIN categories c ON e.category_id = c.category_id
                WHERE e.user_id = :user_id AND (e.description LIKE :keyword
                   OR e.amount LIKE :keyword
                   OR e.date LIKE :keyword
                   OR e.user_id LIKE :keyword
                   OR c.category_name LIKE :keyword)
                ORDER BY e.expenses_id DESC";

        $stmt = $this->conn->prepare($sql);

        $search = "%" . $keyword . "%";

        $stmt->execute([
            ":keyword" => $search,
            ":user_id" => $this->userId
        ]);

        return $stmt->fetchAll();
    }
}
?>
