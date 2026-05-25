<?php

namespace App\Controllers;

use App\Routing\Attributes\Route;

class FileController extends AbstractController
{
    #[Route('/file/upload', 'POST')]
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

    #[Route('/file/download', 'GET')]
    public function download(): void
    {
        $this->requireAuth();
        echo 'Скачивание файла';
    }

    #[Route('/file/delete', 'POST')]
    public function delete(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        echo 'Файл удалён';
    }
}
