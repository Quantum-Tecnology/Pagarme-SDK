<?php

declare(strict_types = 1);

namespace QuantumTecnology\PagarmeSDK;

abstract class BaseRepository
{
    public bool $success            = false;
    public int $http_code           = 0;
    public string $message          = 'not found';
    public array | object $errors   = [];
    public array | object $data     = [];
    protected string $urlApi        = 'localhost';
    protected string $authorization = '';

    public function map(object | array $data)
    {
        foreach ($data as $index => $attribute) {
            if (is_array($attribute)) {
                $data->$index = collect($attribute);
            }
        }

        return $data;
    }
}
