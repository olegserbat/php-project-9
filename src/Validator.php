<?php

namespace App;

class Validator {
    public function validate(array $url) {
        $errors = [];
        if($url['name'] === '') {
            $errors[] = 'URL не должен быть пустым';
            return $errors;
        }
        $patern1 = '/^https:\/\//';
        $patern2 = '/^http:\/\//';
        if (!preg_match($patern1, $url['name']) AND !preg_match($patern2, $url['name'])) {
            $errors [] = 'Некорректный URL';
        }
        return $errors;
    }
}

