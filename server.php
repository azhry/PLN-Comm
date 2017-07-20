<?php
use Ratchet\Server\IoServer;
use MyApp\Chat;

    require dirname(__DIR__) . '/wwwroot/vendor/autoload.php';

    $server = IoServer::factory(
        new Chat(),
        8080
    );
    
    echo "Server is running ....";
    $server->run();