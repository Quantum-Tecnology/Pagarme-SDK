<?php

namespace QuantumCode\PagarmeSDK;

abstract class BaseRepository
{
    protected string $urlApi        = 'localhost';
    protected string $authorization = '';
    public bool $success            = false;
    public int $http_code           = 0;
    public string $message          = 'not found';
    public array|object $errors     = [];
    public array|object $data       = [];

    public function map(object|array $data)
    {
        foreach ($data as $index => $attribute) {
            if (is_array($attribute)) {
                $data->$index = collect($attribute);
            }
        }

        return $data;
    }
}
