<?php

namespace App\Controllers;

class FileController extends AbstractController
{
    public function upload(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            exit('Файл не передан');
        }

        echo 'Файл загружен';
    }

    public function download(): void
    {
        $this->requireAuth();
        echo 'Скачивание файла';
    }

    public function delete(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        echo 'Файл удалён';
    }
}