<?php date_default_timezone_set('Asia/Ho_Chi_Minh');

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use \Redis;
use \RedisException;

require dirname(__DIR__) . '/vendor/autoload.php';

class LotteryWebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $redis;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->redis = new Redis();
        try {
            $this->redis->connect('127.0.0.1', 6379);
            echo "Kết nối Redis thành công\n";
        } catch (\Exception $e) {
            echo "Kết nối Redis thất bại: " . $e->getMessage() . "\n";
            exit;
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "Kết nối mới: {$conn->resourceId}\n";
        $this->sendLatestData($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Không xử lý tin nhắn từ client
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Ngắt kết nối: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Lỗi: {$e->getMessage()}\n";
        $conn->close();
    }

    public function sendLatestData($conn = null)
    {
        try {
            $data = $this->redis->get('xs_test_' . date('Y-m-d'));
            echo 'xs_live_mb_' . date('Y-m-d');
            $lotteryData = $data ? json_decode($data, true) : ['status' => 0, 'results' => []];
            echo "Gửi dữ liệu: " . json_encode($lotteryData) . "\n";

            if ($conn) {
                $conn->send(json_encode($lotteryData));
            } else {
                foreach ($this->clients as $client) {
                    $client->send(json_encode($lotteryData));
                }
            }
        } catch (\Exception $e) {
            $error = ['error' => 'Lỗi Redis: ' . $e->getMessage()];
            echo "Lỗi Redis: " . $e->getMessage() . "\n";
            if ($conn) {
                $conn->send(json_encode($error));
            } else {
                foreach ($this->clients as $client) {
                    $client->send(json_encode($error));
                }
            }
        }
    }
}

// Tạo WebSocket server
$lotteryWebSocket = new LotteryWebSocket();
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $lotteryWebSocket
        )
    ),
    8080
);

// Thêm timer để gửi dữ liệu mỗi giây
$server->loop->addPeriodicTimer(5, function () use ($lotteryWebSocket) {
    echo "Kiểm tra dữ liệu Redis...\n";
    $lotteryWebSocket->sendLatestData();
});

$server->run();
