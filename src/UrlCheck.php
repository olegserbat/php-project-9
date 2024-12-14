<?php

namespace App;

use Carbon\Carbon;

class UrlCheck
{
    private int $id;
    private int $urlId;
    private string $h1;
    private string $description;
    private int $statusCode;
    private string $createdAt;
    private string $title;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
    }

    public function getH1(): string
    {
        return $this->h1;
    }

    public function getStatusCod(): int
    {
        return $this->statusCode;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function setStatuseCod(int $status): void
    {
        $this->statusCode = $status;
    }

    public function setDescription(string $string): void
    {
        $this->description = $string;
    }

    public function setH1(string $string): void
    {
        $this->h1 = $string;
    }

    public function setTitle(string $string): void
    {
        $this->title = $string;
    }

    public function setCreatedAt(mixed $createdAt = null): void
    {
        if ($createdAt) {
            $time = Carbon::parse($createdAt);
        } else {
            $time = Carbon::now();
        }
        $this->createdAt = $time->setTimezone('Europe/Moscow');
    }

    public function makeUrlCheckObject(array $urlCheck): UrlCheck
    {
        $url = new UrlCheck();
        if (isset($urlCheck['id'])) {
            $url->setId($urlCheck['id']);
        }
        if (isset($urlCheck['created_at'])) {
            $url->setCreatedAt($urlCheck['created_at']);
        } else {
            $url->setCreatedAt();
        }
        $url->setDescription($urlCheck['description']);
        $url->setH1($urlCheck['h1']);
        $url->setTitle($urlCheck['title']);
        $url->setStatuseCod($urlCheck['status_code']);
        $url->setUrlId($urlCheck['url_id']);
        return $url;
    }
}
