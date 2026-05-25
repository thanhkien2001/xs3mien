<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\Server as ReactServer;
// use React\Socket\SecureServer; // Prod dùng WSS

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/** Helper log có timestamp */
function dbg(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . "] $msg\n";
}

final class LotteryWebSocket implements MessageComponentInterface
{
    private \SplObjectStorage $clients;
    private array $clientRegions = [];

    // Local and VPS origins
    private array $allowedOriginsLocal = [
        'http://xs-clone.code',
        'https://xs-clone.code', // Local HTTPS
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        // VPS domains
        'http://soicau247.com',
        'https://soicau247.com',
        'http://www.soicau247.com',
        'https://www.soicau247.com',
    ];

    // Tên cookie token ở local (controller cũng đang dùng tên này)
    private string $tokenCookieName = 'ws_token';

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->redis   = new Redis();

        try {
            // Thử kết nối Redis với timeout
            $this->redis->connect('127.0.0.1', 6379, 5);
            $this->redis->ping();
            dbg("Kết nối Redis thành công");
        } catch (\Exception $e) {
            dbg("Kết nối Redis thất bại: " . $e->getMessage());
            dbg("Đảm bảo Redis đang chạy: sudo systemctl start redis");
            exit(1);
        }
    }

    /** Chuẩn hóa IP từ remoteAddress kiểu tcp://127.0.0.1:54321 */
    private static function normalizeIp(string $ip): string
    {
        if (str_starts_with($ip, 'tcp://')) {
            $ip = substr($ip, 6);
        }
        if (strpos($ip, ':') !== false) {
            $parts = explode(':', $ip);
            $ip = $parts[0];
        }
        return $ip;
    }

    private static function todayStr(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    }

    public function onOpen(ConnectionInterface $conn)
    {
        dbg("=== onOpen: resourceId={$conn->resourceId} ===");

        // 1) Origin check (log lại Origin)
        $origin = $conn->httpRequest->getHeaderLine('Origin');
        dbg("Origin nhận được: " . ($origin ?: 'none'));
        if (!in_array($origin, $this->allowedOriginsLocal, true)) {
            dbg("Origin không được phép -> đóng kết nối");
            $conn->send(json_encode(['error' => 'Origin không được phép'], JSON_UNESCAPED_UNICODE));
            $conn->close();
            return;
        }

        // 2) Region từ query
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        $region = $params['region'] ?? null;
        dbg("Query string: $query");
        dbg("Region: " . ($region ?? 'null'));
        if (!in_array($region, ['mn', 'mt', 'mb'], true)) {
            dbg("Region không hợp lệ -> đóng kết nối");
            $conn->send(json_encode(['error' => 'Region không hợp lệ'], JSON_UNESCAPED_UNICODE));
            $conn->close();
            return;
        }

        // 3) Lấy cookie token từ header (log toàn bộ header Cookie để debug)
        $cookies = $conn->httpRequest->getHeader('Cookie') ?? [];
        if ($cookies) {
            foreach ($cookies as $line) {
                dbg("Cookie header: $line");
            }
        } else {
            dbg("Không nhận được header Cookie");
        }

        $token = null;
        foreach ($cookies as $cookieLine) {
            if (preg_match('/\b' . preg_quote($this->tokenCookieName, '/') . '=([^;]+)/', $cookieLine, $m)) {
                $token = $m[1];
                break;
            }
        }
        dbg("Token đọc từ cookie {$this->tokenCookieName}: " . ($token ?? 'none'));

        if (!$token) {
            $conn->send(json_encode(['error' => 'Thiếu token'], JSON_UNESCAPED_UNICODE));
            $conn->close();
            return;
        }

        // 4) Kiểm tra token trong Redis + so khớp IP/UA/region
        $redisKey = 'ws_token_' . $token;
        dbg("Kiểm tra Redis key: $redisKey");
        if (!$this->redis->exists($redisKey)) {
            dbg("Token hết hạn hoặc không tồn tại -> đóng kết nối");
            $conn->send(json_encode(['error' => 'Token hết hạn hoặc không hợp lệ'], JSON_UNESCAPED_UNICODE));
            $conn->close();
            return;
        }

        $storedRaw = (string)$this->redis->get($redisKey);
        dbg("Meta trong Redis: " . $storedRaw);
        $stored = json_decode($storedRaw, true) ?: [];

        // Lấy IP/UA thực tế từ kết nối
        $ipHdr  = $conn->httpRequest->getHeaderLine('X-Forwarded-For');
        $ipConn = $ipHdr ? trim(explode(',', $ipHdr)[0]) : ($conn->remoteAddress ?? '');
        $ua     = $conn->httpRequest->getHeaderLine('User-Agent') ?: '';
        $ipNorm = self::normalizeIp((string)$ipConn);

        dbg("IP từ kết nối: " . ($ipConn ?: 'none') . " | Chuẩn hoá: $ipNorm");
        dbg("User-Agent: " . ($ua ?: 'none'));

        // Tạm thời tắt kiểm tra IP do Cloudflare proxy
        $storedIp = $stored['ip'] ?? '';
        $ipOk = true; // Tắt kiểm tra IP
        
        $uaOk     = (($stored['ua'] ?? '') === $ua);
        $regionOk = (($stored['region'] ?? null) === $region);

        dbg("So khớp IP: " . ($ipOk ? 'OK' : 'FAIL'));
        dbg("So khớp UA: " . ($uaOk ? 'OK' : 'FAIL'));
        dbg("So khớp region: " . ($regionOk ? 'OK' : 'FAIL'));

        if (!$ipOk || !$uaOk || !$regionOk) {
            dbg("Ngữ cảnh không khớp -> đóng kết nối");
            $conn->send(json_encode(['error' => 'Ngữ cảnh không khớp'], JSON_UNESCAPED_UNICODE));
            $conn->close();
            return;
        }

        // 5) One-time: xoá token tránh replay
        $this->redis->del($redisKey);
        dbg("Đã xoá Redis key (one-time): $redisKey");

        // 6) Gắn region và attach client
        $this->clientRegions[$conn->resourceId] = $region;
        $this->clients->attach($conn);
        dbg("New connection attached: {$conn->resourceId} (region=$region)");

        // 7) Gửi dữ liệu lần đầu
        $this->sendLatestData($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Không xử lý tin nhắn từ client
        dbg("onMessage từ {$from->resourceId}: " . substr((string)$msg, 0, 200));
    }

    public function onClose(ConnectionInterface $conn)
    {
        dbg("Ngắt kết nối: {$conn->resourceId}");
        unset($this->clientRegions[$conn->resourceId]);
        if ($this->clients->contains($conn)) {
            $this->clients->detach($conn);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        dbg("Lỗi kết nối {$conn->resourceId}: " . $e->getMessage());
        $conn->close();
    }

    public function sendLatestData(?ConnectionInterface $conn = null): void
    {
        try {
            $date = self::todayStr();

            if ($conn) {
                $region = $this->clientRegions[$conn->resourceId] ?? null;
                if (!$region) {
                    dbg("sendLatestData: không có region cho client {$conn->resourceId}");
                    return;
                }

                $key = "xs_live_{$region}_{$date}";
                dbg("Lấy dữ liệu từ Redis key: $key (client={$conn->resourceId})");
                $data = $this->redis->get($key);

                $payload = $data ?: json_encode(['status' => 0, 'results' => []], JSON_UNESCAPED_UNICODE);
                dbg("Gửi dữ liệu cho client {$conn->resourceId}: " . substr($payload, 0, 300));
                $conn->send($payload);
                return;
            }

            // Broadcast theo region
            $regions = array_unique(array_values($this->clientRegions));
            dbg("Broadcast regions: " . implode(',', $regions));
            foreach ($regions as $region) {
                $key = "xs_live_{$region}_{$date}";
                dbg("Lấy dữ liệu từ Redis key: $key");
                $data = $this->redis->get($key);

                $payload = $data ?: json_encode(['status' => 0, 'results' => []], JSON_UNESCAPED_UNICODE);
                foreach ($this->clients as $client) {
                    if (($this->clientRegions[$client->resourceId] ?? null) === $region) {
                        dbg("Gửi dữ liệu cho client {$client->resourceId} (region=$region): " . substr($payload, 0, 300));
                        $client->send($payload);
                    }
                }
            }
        } catch (\Exception $e) {
            $error = ['error' => 'Lỗi Redis: ' . $e->getMessage()];
            $jsonError = json_encode($error, JSON_UNESCAPED_UNICODE);
            dbg("Lỗi Redis: " . $e->getMessage());

            if ($conn) {
                $conn->send($jsonError);
            } else {
                foreach ($this->clients as $client) {
                    $client->send($jsonError);
                }
            }
        }
    }
}

// ===== Bootstrap server =====
dbg("Bootstrapping WebSocket server...");
$ws   = new LotteryWebSocket();
$loop = Loop::get();

// Bind to port 9001
$tcp  = new React\Socket\Server('127.0.0.1:9001', $loop);
dbg("Listening on ws://127.0.0.1:9001");

// Prod (tham khảo):
// $secure = new SecureServer($tcp, $loop, [
//     'local_cert'  => '/etc/letsencrypt/live/your-domain.com/fullchain.pem',
//     'local_pk'    => '/etc/letsencrypt/live/your-domain.com/privkey.pem',
//     'verify_peer' => false,
// ]);

$httpServer = new HttpServer(new WsServer($ws));
$server     = new IoServer($httpServer, $tcp, $loop);

// Timer gửi dữ liệu định kỳ
$server->loop->addPeriodicTimer(5, function () use ($ws) {
    dbg("Kiểm tra dữ liệu Redis và broadcast...");
    $ws->sendLatestData();
});

dbg("Server started. Waiting for connections...");
$server->run();
