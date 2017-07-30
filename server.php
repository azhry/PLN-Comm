<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Chat;

	//require dirname(__DIR__) . '/wwwroot/vendor/autoload.php';
    require dirname(__DIR__) . '/PLN/vendor/autoload.php';

    $server = IoServer::factory(
    	new HttpServer (
    		new WsServer(
				new Chat()
    		)
    	),
        2000
    );
    
    echo "Server is running ....";
    $server->run();