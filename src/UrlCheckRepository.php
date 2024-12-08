<?php

namespace App;

class UrlCheckRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function save(UrlCheck $url): void
    {
        $sql = "INSERT INTO url_checks ( url_id, status_code, h1, title, description, created_at) 
                VALUES ( :url_id, :status_code, :h1, :title, :description, :created_at)";
        $stmt = $this->conn->prepare($sql);
        $url_id = $url->getUrlId();
        $status_code = $url->getStatusCod();
        $h1 = $url->getH1();
        $title = $url->getTitle();
        $description = $url->getDescription();
        $created_at = $url->getCreatedAt();
        $stmt->bindParam(':url_id', $url_id);
        $stmt->bindParam(':status_code', $status_code);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->execute();
        $id = (int)$this->conn->lastInsertId();
        $url->setId($id);
    }

    public function findUrlCheck(int $url_id): mixed
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $url_id);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if ($result !== []) {
            return $result;
        } else {
            return false;
        }
    }

    public function findAllUrls(): mixed
    {
        $sql = "
        SELECT 
        urls.id AS url_id,
        urls.address AS name,
        (SELECT url_checks.created_at FROM url_checks WHERE urls.id = url_checks.url_id 
                                                      ORDER BY url_checks.created_at DESC LIMIT 1) AS created_at,
        (SELECT url_checks.status_code FROM url_checks WHERE urls.id = url_checks.url_id 
        ORDER BY url_checks.created_at DESC LIMIT 1) AS status_code
        FROM urls;
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if ($result !== []) {
            return $result;
        } else {
            return false;
        }
    }
}
