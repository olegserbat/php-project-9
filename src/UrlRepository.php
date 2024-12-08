<?php

namespace App;

class UrlRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getEntities(): array
    {
        $urls = [];
        $sql = "SELECT * FROM urls";
        $stmt = $this->conn->query($sql);

        while ($row = $stmt->fetch()) {
            $url = Url::fromArray([$row['address'], $row['created_at']]);
            $url->setId($row['id']);
            $urls[] = $url;
        }
        return $urls;
    }

    public function find(int $id): mixed
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result !== []) {
            return $result;
        } else {
            return false;
        }
    }

    public function check(string $address): mixed
    {
        $sql = "SELECT * FROM urls WHERE address = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(1, $address);
        $stmt->execute();
        $result = $stmt->fetch();
        if ($result !== []) {
            return $result;
        } else {
            return false;
        }
//        if ($row = $stmt->fetch())  {
//            $url = Url::fromArray([$row['address'], $row['created_at']]);
//            $url->setId($row['id']);
//            return $url;
//        }
//        return null;
    }

//    public function save(Url $url): void {
//        if ($url->urlExists()) {
//            $this->update($url);
//        } else {
//            $this->create($url);
//        }
//    }

    private function update(Url $url): void
    {
        $sql = "UPDATE urls SET address = :address, created_at = :created_at WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $id = $url->getId();
        $address = $url->getAddress();
        $created_at = $url->getCreatedAt();
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function save(Url $url): void
    {
        $sql = "INSERT INTO urls ( address, created_at) VALUES ( :address, :created_at)";
        $stmt = $this->conn->prepare($sql);
        $address = $url->getAddress();
        $created_at = $url->getCreatedAt();
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->execute();
        $id = (int)$this->conn->lastInsertId();
        $url->setId($id);
//        $id = (int) $this->conn->lastInsertId();
//        $url->setId($id);
    }
}
