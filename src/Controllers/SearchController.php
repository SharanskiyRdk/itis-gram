<?php

namespace App\Controllers;

class SearchController extends AbstractController
{
    public function users(): void
    {
        $this->requireAuth();

        $q = trim((string)($_GET['q'] ?? ''));

        if ($q !== '' && mb_strlen($q) > 100) {
            http_response_code(422);
            exit('Слишком длинный поисковый запрос');
        }

        $this->render('search/users', ['q' => $q]);
    }

    public function messages(): void
    {
        $this->requireAuth();
        echo 'Поиск сообщений';
    }
}