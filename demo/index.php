<?php
require_once __DIR__ .'/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$view = new \Roolith\Template\Engine\View(__DIR__ . '/views');
try {
    $data = [
        'content' => 'home content',
        'title' => 'home page',
    ];

    echo $view->compile('home', $data);
} catch (\Roolith\Template\Engine\Exceptions\Exception $e) {
    echo $e->getMessage();
}