<?php

namespace App;

use Carbon\Carbon;

class Url
{
    private string $address;
    private int $id;
    private string $created_at;


    public function getId(): int
    {
        return $this->id;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setAddress($address): void
    {
        $this->address = $address;
    }

    public function urlExists(): bool
    {
        return !is_null($this->getId());
    }

    public function getCreated_at(): mixed
    {
        return $this->created_at;
    }

    public function setCreated_at($createdAt = null)
    {
        if ($createdAt) {
            $time = Carbon::parse($createdAt);
        } else {
            $time = Carbon::now();
        }

        $this->created_at = $time->setTimezone('Europe/Moscow');
    }

    public function fromArray(array $urlData): Url
    {
        [$address] = $urlData;
        $url = new Url();
        $url->setAddress($address);
        $url->setCreated_at();
        return $url;
    }

    public static function makeObjectUrl(array $urlData): Url
    {
        $url = new self();
        $url->setAddress($urlData['address']);
        $url->setCreated_at($urlData['created_at']);
        $url->setId($urlData['id']);
        return $url;
    }
}
