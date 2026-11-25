<?php

namespace Controllers;

class NotFoundController
{
    public function index(): void
    {
        http_response_code(404);
        echo 'Página não encontrada (404).';
    }
}
