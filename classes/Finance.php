<?php
class Finance
{
    private PDO $db;
    private int $userId;

    public function __construct(PDO $db, int $userId)
    {
        $this->db = $db;
        $this->userId = $userId;
    }

    private function rows(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$this->userId], $params));
        return $stmt->fetchAll();
    }

    public function income(string $keyword = ''): array
    {
        return $this->rows('SELECT * FROM income WHERE user_id = ? AND (source LIKE ? OR date LIKE ? OR amount LIKE ?) ORDER BY date DESC, income_id DESC', array_fill(0, 3, '%' . $keyword . '%'));
    }

    public function goals(string $keyword = ''): array
    {
        return $this->rows('SELECT * FROM savings_goals WHERE user_id = ? AND (goal_name LIKE ? OR status LIKE ?) ORDER BY goal_id DESC', array_fill(0, 2, '%' . $keyword . '%'));
    }

    public function totals(): array
    {
        $result = [];
        $month = date('Y-m-01');
        $next = date('Y-m-01', strtotime($month . ' +1 month'));
        foreach (['income', 'expenses'] as $table) {
            $stmt = $this->db->prepare('SELECT COALESCE(SUM(amount), 0) AS total, COALESCE(SUM(CASE WHEN date >= ? AND date < ? THEN amount ELSE 0 END), 0) AS monthly, COUNT(*) AS records FROM ' . $table . ' WHERE user_id = ?');
            $stmt->execute([$month, $next, $this->userId]);
            $result[$table] = $stmt->fetch();
        }
        $result['goals'] = $this->rows("SELECT COALESCE(SUM(current_amount), 0) AS saved, COALESCE(SUM(target_amount), 0) AS target, COUNT(*) AS records, COALESCE(SUM(status = 'Completed'), 0) AS completed FROM savings_goals WHERE user_id = ?")[0];
        return $result;
    }

    public function recent(): array
    {
        $stmt = $this->db->prepare("SELECT source AS description, amount, date, 'Income' AS type, income_id AS id FROM income WHERE user_id = ? UNION ALL SELECT description, amount, date, 'Expense' AS type, expenses_id AS id FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 8");
        $stmt->execute([$this->userId, $this->userId]);
        return $stmt->fetchAll();
    }

    public function monthly(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime(date('Y-m-01') . ' -' . $i . ' months'));
            $months[$key] = ['label' => date('M', strtotime($key . '-01')), 'income' => 0, 'expenses' => 0];
        }
        foreach (['income', 'expenses'] as $table) {
            $rows = $this->rows("SELECT DATE_FORMAT(date, '%Y-%m') AS month, SUM(amount) AS total FROM " . $table . " WHERE user_id = ? AND date >= ? GROUP BY DATE_FORMAT(date, '%Y-%m')", [array_key_first($months) . '-01']);
            foreach ($rows as $row) {
                if (isset($months[$row['month']])) {
                    $months[$row['month']][$table] = (float) $row['total'];
                }
            }
        }
        return $months;
    }

    public function saveIncome(?int $id, string $source, string $amount, string $date): void
    {
        if ($id) {
            $this->owned('income', 'income_id', $id);
            $stmt = $this->db->prepare('UPDATE income SET source = ?, amount = ?, date = ? WHERE income_id = ? AND user_id = ?');
            $stmt->execute([$source, $amount, $date, $id, $this->userId]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO income (source, amount, date, user_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$source, $amount, $date, $this->userId]);
        }
    }

    public function saveGoal(?int $id, string $name, string $target, string $current, string $date): void
    {
        if ((float) $current > (float) $target) {
            throw new InvalidArgumentException('Saved amount cannot exceed the target amount.');
        }
        $status = (float) $current >= (float) $target ? 'Completed' : 'Active';
        if ($id) {
            $this->owned('savings_goals', 'goal_id', $id);
            $stmt = $this->db->prepare('UPDATE savings_goals SET goal_name = ?, target_amount = ?, current_amount = ?, target_date = ?, status = ? WHERE goal_id = ? AND user_id = ?');
            $stmt->execute([$name, $target, $current, $date, $status, $id, $this->userId]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO savings_goals (goal_name, target_amount, current_amount, target_date, status, user_id) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $target, $current, $date, $status, $this->userId]);
        }
    }

    public function contribute(int $id, string $amount): void
    {
        // The row lock prevents simultaneous deposits from overwriting each other.
        $this->owned('savings_goals', 'goal_id', $id);
        $stmt = $this->db->prepare('UPDATE savings_goals SET status = CASE WHEN current_amount + ? >= target_amount THEN ? ELSE ? END, current_amount = current_amount + ? WHERE goal_id = ? AND user_id = ? AND current_amount + ? <= target_amount');
        $stmt->execute([$amount, 'Completed', 'Active', $amount, $id, $this->userId, $amount]);
        if ($stmt->rowCount() !== 1) {
            throw new InvalidArgumentException('The contribution exceeds the amount remaining for this goal.');
        }
    }

    public function deleteIncome(int $id): void
    {
        $this->owned('income', 'income_id', $id);
        $stmt = $this->db->prepare('DELETE FROM income WHERE income_id = ? AND user_id = ?');
        $stmt->execute([$id, $this->userId]);
    }

    public function deleteGoal(int $id): void
    {
        $this->owned('savings_goals', 'goal_id', $id);
        $stmt = $this->db->prepare('DELETE FROM savings_goals WHERE goal_id = ? AND user_id = ?');
        $stmt->execute([$id, $this->userId]);
    }

    private function owned(string $table, string $key, int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ' . $table . ' WHERE ' . $key . ' = ? AND user_id = ? FOR UPDATE');
        $stmt->execute([$id, $this->userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('This record is no longer available.');
        }
        return $row;
    }
}
