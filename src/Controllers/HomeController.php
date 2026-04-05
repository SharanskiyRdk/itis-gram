<?php


namespace App\Controllers;

class HomeController extends AbstractController
{
    public function index(): void
    {
        echo 'Главная страница';
    }
}