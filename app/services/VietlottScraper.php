<?php

namespace App\Services;

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Di\Injectable;

class VietlottScraper extends Injectable
{
    private $db;

    public function __construct()
    {
        $this->db = $this->getDI()->getShared('db');
    }

    /**
     * Lưu kết quả xổ số vào database
     * @param array $results Dữ liệu từ Puppeteer
     * @return array Kết quả xử lý
     */
    public function saveResults($results)
    {
        $response = ['success' => [], 'errors' => []];

        if (empty($results['mega'])) {
            $response['errors'][] = 'No Mega 6/45 data provided';
            return $response;
        }

        foreach ($results['mega'] as $result) {
            try {
                // Kiểm tra trùng lặp
                $exists = $this->db->fetchOne(
                    'SELECT id FROM vietlott_results WHERE draw_type = :draw_type AND draw_number = :draw_number AND draw_date = :draw_date',
                    \Phalcon\Db\Enum::FETCH_ASSOC,
                    [
                        'draw_type' => $result['draw_type'],
                        'draw_number' => $result['draw_number'],
                        'draw_date' => $result['draw_date']
                    ]
                );

                if ($exists) {
                    $response['success'][] = "Result for draw {$result['draw_number']} on {$result['draw_date']} already exists";
                    continue;
                }

                // Lưu dữ liệu mới
                $this->db->insert(
                    'vietlott_results',
                    [
                        $result['draw_type'],
                        $result['draw_number'],
                        $result['draw_date'],
                        $result['numbers'],
                        $result['jackpot_amount']
                    ],
                    ['draw_type', 'draw_number', 'draw_date', 'numbers', 'jackpot_amount']
                );

                $response['success'][] = "Saved result for draw {$result['draw_number']} on {$result['draw_date']}";
            } catch (\Exception $e) {
                $response['errors'][] = "Error saving draw {$result['draw_number']}: " . $e->getMessage();
            }
        }

        return $response;
    }
}
