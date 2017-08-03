<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use \PDO;

// define database credentials
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_DATABASE", "db_pln");

class Chat implements MessageComponentInterface {
    protected $clients;
    private static $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        try 
        {
            self::$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) 
        {
            echo $e->getMessage();
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $client_msg = json_decode($msg);
        if ($client_msg->type == 'establishing_connection')
        {
            $sql    = 'UPDATE users SET CONNECTION_ID=:CONNECTION_ID WHERE USER_ID=:USER_ID';
            $stmt   = self::$db->prepare($sql);
            $stmt->bindparam(':CONNECTION_ID', $from->resourceId);
            $stmt->bindparam(':USER_ID', $client_msg->user_id);
            $stmt->execute();
            echo $client_msg->user . $client_msg->msg . "\n";
        }
        else if ($client_msg->type == 'sending_info')
        {
            echo $client_msg->user . ' says: ' . $client_msg->msg . "\n";
        }
        else if ($client_msg->type == 'invite_notification')
        {
            $sql    = 'SELECT * FROM todo_lists WHERE LIST_ID=:LIST_ID';
            $stmt   = self::$db->prepare($sql);
            $stmt->bindparam(':LIST_ID', $client_msg->list_id);
            $stmt->execute();
            $todo_list = $stmt->fetch(PDO::FETCH_ASSOC);
            $response = $todo_list;

            $sql    = 'SELECT CONNECTION_ID FROM users WHERE EMAIL=:EMAIL';
            $stmt   = self::$db->prepare($sql);
            $stmt->bindparam(':EMAIL', $client_msg->email);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "\nSomeone is inviting someone\n";

            foreach ($this->clients as $client)
            {
                if ($client->resourceId == $result['CONNECTION_ID'])
                {
                    $response['action']     = 'invite_notification';
                    $client->send(json_encode($response));
                    echo json_encode($response) . "\n\n";
                }
            }
        }

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $conn->send(json_encode(['action' => 'connection_status', 'connected' => false]));
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}