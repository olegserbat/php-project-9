<?php

namespace App;

class Validator
{
    public function validate(array $url)
    {
        $errors = null;
        if ($url['name'] === '') {
            $errors = 'URL не должен быть пустым';
            return $errors;
        }
        $patern =  '/^https?:\/\//';
        if (!preg_match($patern, $url['name'])) {
            $errors = 'Некорректный URL';
        }
        return $errors;
    }
}
