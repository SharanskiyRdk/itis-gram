<?php

namespace App\Controllers;

class AboutController extends AbstractController
{
    public function index(): void
    {
        $this->render('about/index');
    }
}