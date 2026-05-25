<?php

namespace App\Models;
class VietlottResults extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $draw_type;

    /**
     *
     * @var string
     */
    protected $draw_number;

    /**
     *
     * @var string
     */
    protected $draw_date;

    /**
     *
     * @var string
     */
    protected $numbers;

    /**
     *
     * @var integer
     */
    protected $jackpot_amount;

    /**
     *
     * @var string
     */
    protected $created_at;

    /**
     *
     * @var integer
     */
    protected $jackpot_2;

    /**
     *
     * @var integer
     */
    protected $giai_nhat_gia_tri;

    /**
     *
     * @var integer
     */
    protected $giai_nhi_gia_tri;

    /**
     *
     * @var integer
     */
    protected $giai_ba_gia_tri;

    /**
     *
     * @var integer
     */
    protected $jackpot_1_sl;

    /**
     *
     * @var integer
     */
    protected $jackpot_2_sl;

    /**
     *
     * @var integer
     */
    protected $giai_nhat_sl;

    /**
     *
     * @var integer
     */
    protected $giai_nhi_sl;

    /**
     *
     * @var integer
     */
    protected $giai_ba_sl;

    /**
     * Method to set the value of field id
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Method to set the value of field draw_type
     *
     * @param string $draw_type
     * @return $this
     */
    public function setDrawType($draw_type)
    {
        $this->draw_type = $draw_type;

        return $this;
    }

    /**
     * Method to set the value of field draw_number
     *
     * @param string $draw_number
     * @return $this
     */
    public function setDrawNumber($draw_number)
    {
        $this->draw_number = $draw_number;

        return $this;
    }

    /**
     * Method to set the value of field draw_date
     *
     * @param string $draw_date
     * @return $this
     */
    public function setDrawDate($draw_date)
    {
        $this->draw_date = $draw_date;

        return $this;
    }

    /**
     * Method to set the value of field numbers
     *
     * @param string $numbers
     * @return $this
     */
    public function setNumbers($numbers)
    {
        $this->numbers = $numbers;

        return $this;
    }

    /**
     * Method to set the value of field jackpot_amount
     *
     * @param integer $jackpot_amount
     * @return $this
     */
    public function setJackpotAmount($jackpot_amount)
    {
        $this->jackpot_amount = $jackpot_amount;

        return $this;
    }

    /**
     * Method to set the value of field created_at
     *
     * @param string $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Method to set the value of field jackpot_2
     *
     * @param integer $jackpot_2
     * @return $this
     */
    public function setJackpot2($jackpot_2)
    {
        $this->jackpot_2 = $jackpot_2;

        return $this;
    }

    /**
     * Method to set the value of field giai_nhat_gia_tri
     *
     * @param integer $giai_nhat_gia_tri
     * @return $this
     */
    public function setGiaiNhatGiaTri($giai_nhat_gia_tri)
    {
        $this->giai_nhat_gia_tri = $giai_nhat_gia_tri;

        return $this;
    }

    /**
     * Method to set the value of field giai_nhi_gia_tri
     *
     * @param integer $giai_nhi_gia_tri
     * @return $this
     */
    public function setGiaiNhiGiaTri($giai_nhi_gia_tri)
    {
        $this->giai_nhi_gia_tri = $giai_nhi_gia_tri;

        return $this;
    }

    /**
     * Method to set the value of field giai_ba_gia_tri
     *
     * @param integer $giai_ba_gia_tri
     * @return $this
     */
    public function setGiaiBaGiaTri($giai_ba_gia_tri)
    {
        $this->giai_ba_gia_tri = $giai_ba_gia_tri;

        return $this;
    }

    /**
     * Method to set the value of field jackpot_1_sl
     *
     * @param integer $jackpot_1_sl
     * @return $this
     */
    public function setJackpot1Sl($jackpot_1_sl)
    {
        $this->jackpot_1_sl = $jackpot_1_sl;

        return $this;
    }

    /**
     * Method to set the value of field jackpot_2_sl
     *
     * @param integer $jackpot_2_sl
     * @return $this
     */
    public function setJackpot2Sl($jackpot_2_sl)
    {
        $this->jackpot_2_sl = $jackpot_2_sl;

        return $this;
    }

    /**
     * Method to set the value of field giai_nhat_sl
     *
     * @param integer $giai_nhat_sl
     * @return $this
     */
    public function setGiaiNhatSl($giai_nhat_sl)
    {
        $this->giai_nhat_sl = $giai_nhat_sl;

        return $this;
    }

    /**
     * Method to set the value of field giai_nhi_sl
     *
     * @param integer $giai_nhi_sl
     * @return $this
     */
    public function setGiaiNhiSl($giai_nhi_sl)
    {
        $this->giai_nhi_sl = $giai_nhi_sl;

        return $this;
    }

    /**
     * Method to set the value of field giai_ba_sl
     *
     * @param integer $giai_ba_sl
     * @return $this
     */
    public function setGiaiBaSl($giai_ba_sl)
    {
        $this->giai_ba_sl = $giai_ba_sl;

        return $this;
    }

    /**
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field draw_type
     *
     * @return string
     */
    public function getDrawType()
    {
        return $this->draw_type;
    }

    /**
     * Returns the value of field draw_number
     *
     * @return string
     */
    public function getDrawNumber()
    {
        return $this->draw_number;
    }

    /**
     * Returns the value of field draw_date
     *
     * @return string
     */
    public function getDrawDate()
    {
        return $this->draw_date;
    }

    /**
     * Returns the value of field numbers
     *
     * @return string
     */
    public function getNumbers()
    {
        return $this->numbers;
    }

    /**
     * Returns the value of field jackpot_amount
     *
     * @return integer
     */
    public function getJackpotAmount()
    {
        return $this->jackpot_amount;
    }

    /**
     * Returns the value of field created_at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Returns the value of field jackpot_2
     *
     * @return integer
     */
    public function getJackpot2()
    {
        return $this->jackpot_2;
    }

    /**
     * Returns the value of field giai_nhat_gia_tri
     *
     * @return integer
     */
    public function getGiaiNhatGiaTri()
    {
        return $this->giai_nhat_gia_tri;
    }

    /**
     * Returns the value of field giai_nhi_gia_tri
     *
     * @return integer
     */
    public function getGiaiNhiGiaTri()
    {
        return $this->giai_nhi_gia_tri;
    }

    /**
     * Returns the value of field giai_ba_gia_tri
     *
     * @return integer
     */
    public function getGiaiBaGiaTri()
    {
        return $this->giai_ba_gia_tri;
    }

    /**
     * Returns the value of field jackpot_1_sl
     *
     * @return integer
     */
    public function getJackpot1Sl()
    {
        return $this->jackpot_1_sl;
    }

    /**
     * Returns the value of field jackpot_2_sl
     *
     * @return integer
     */
    public function getJackpot2Sl()
    {
        return $this->jackpot_2_sl;
    }

    /**
     * Returns the value of field giai_nhat_sl
     *
     * @return integer
     */
    public function getGiaiNhatSl()
    {
        return $this->giai_nhat_sl;
    }

    /**
     * Returns the value of field giai_nhi_sl
     *
     * @return integer
     */
    public function getGiaiNhiSl()
    {
        return $this->giai_nhi_sl;
    }

    /**
     * Returns the value of field giai_ba_sl
     *
     * @return integer
     */
    public function getGiaiBaSl()
    {
        return $this->giai_ba_sl;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("xoso_db");
        $this->setSource("vietlott_results");
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return VietlottResults[]|VietlottResults|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return VietlottResults|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
     */
    public static function findFirst($parameters = null): ?\Phalcon\Mvc\ModelInterface
    {
        return parent::findFirst($parameters);
    }

    /**
     * Independent Column Mapping.
     * Keys are the real names in the table and the values their names in the application
     *
     * @return array
     */
    public function columnMap()
    {
        return [
            'id' => 'id',
            'draw_type' => 'draw_type',
            'draw_number' => 'draw_number',
            'draw_date' => 'draw_date',
            'numbers' => 'numbers',
            'jackpot_amount' => 'jackpot_amount',
            'created_at' => 'created_at',
            'jackpot_2' => 'jackpot_2',
            'giai_nhat_gia_tri' => 'giai_nhat_gia_tri',
            'giai_nhi_gia_tri' => 'giai_nhi_gia_tri',
            'giai_ba_gia_tri' => 'giai_ba_gia_tri',
            'jackpot_1_sl' => 'jackpot_1_sl',
            'jackpot_2_sl' => 'jackpot_2_sl',
            'giai_nhat_sl' => 'giai_nhat_sl',
            'giai_nhi_sl' => 'giai_nhi_sl',
            'giai_ba_sl' => 'giai_ba_sl'
        ];
    }

    /**
     * Lấy kết quả mới nhất của Mega 6/45
     */
    public static function getLatestDraw645()
    {
        return self::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC'
        ]);
    }

    /**
     * Lấy kết quả mới nhất của Power 6/55
     */
    public static function getLatestDraw655()
    {
        return self::findFirst([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC'
        ]);
    }

    /**
     * Phân tích mẫu số Mega 6/45
     */
    public static function analyzePatterns645()
    {
        $results = self::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC',
            'limit' => 50
        ]);

        $patterns = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            $oddCount = 0;
            $evenCount = 0;
            
            foreach ($numbers as $number) {
                if ((int)$number % 2 == 0) {
                    $evenCount++;
                } else {
                    $oddCount++;
                }
            }
            
            $patterns[] = [
                'date' => $result->draw_date,
                'odd_count' => $oddCount,
                'even_count' => $evenCount
            ];
        }
        
        return $patterns;
    }

    /**
     * Phân tích mẫu số Power 6/55
     */
    public static function analyzePatterns655()
    {
        $results = self::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC',
            'limit' => 50
        ]);

        $patterns = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            $oddCount = 0;
            $evenCount = 0;
            
            foreach ($numbers as $number) {
                if ((int)$number % 2 == 0) {
                    $evenCount++;
                } else {
                    $oddCount++;
                }
            }
            
            $patterns[] = [
                'date' => $result->draw_date,
                'odd_count' => $oddCount,
                'even_count' => $evenCount
            ];
        }
        
        return $patterns;
    }

    /**
     * Lấy tần suất số Mega 6/45
     */
    public static function getNumberFrequency645($limit = 20, $order = 'DESC')
    {
        $results = self::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC',
            'limit' => 100
        ]);

        $numberFrequency = [];
        
        // Khởi tạo mảng tần suất cho 45 số
        for ($i = 1; $i <= 45; $i++) {
            $numberFrequency[str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
        }

        // Tính tần suất
        foreach ($results as $result) {
            if (empty($result->numbers)) {
                continue;
            }

            $numbers = explode(',', trim($result->numbers));
            $mainNumbers = array_slice($numbers, 0, 6);

            foreach ($mainNumbers as $num) {
                $num = trim($num);
                if ($num && is_numeric($num) && $num >= 1 && $num <= 45) {
                    $numberFrequency[str_pad((int)$num, 2, '0', STR_PAD_LEFT)]++;
                }
            }
        }

        // Sắp xếp theo tần suất
        if ($order === 'ASC') {
            asort($numberFrequency);
        } else {
            arsort($numberFrequency);
        }

        // Lấy top theo limit và format lại
        $topNumbers = array_slice($numberFrequency, 0, $limit, true);
        
        $formattedNumbers = [];
        foreach ($topNumbers as $number => $frequency) {
            $formattedNumbers[] = [
                'number' => $number,
                'frequency' => $frequency
            ];
        }
        
        return $formattedNumbers;
    }

    /**
     * Lấy tần suất số Power 6/55
     */
    public static function getNumberFrequency655($limit = 20, $order = 'DESC')
    {
        $results = self::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC',
            'limit' => 100
        ]);

        $numberFrequency = [];
        
        // Khởi tạo mảng tần suất cho 55 số
        for ($i = 1; $i <= 55; $i++) {
            $numberFrequency[str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
        }

        // Tính tần suất
        foreach ($results as $result) {
            if (empty($result->numbers)) {
                continue;
            }

            $numbers = explode(',', trim($result->numbers));
            $mainNumbers = array_slice($numbers, 0, 6);

            foreach ($mainNumbers as $num) {
                $num = trim($num);
                if ($num && is_numeric($num) && $num >= 1 && $num <= 55) {
                    $numberFrequency[str_pad((int)$num, 2, '0', STR_PAD_LEFT)]++;
                }
            }
        }

        // Sắp xếp theo tần suất
        if ($order === 'ASC') {
            asort($numberFrequency);
        } else {
            arsort($numberFrequency);
        }

        // Lấy top theo limit và format lại
        $topNumbers = array_slice($numberFrequency, 0, $limit, true);
        
        $formattedNumbers = [];
        foreach ($topNumbers as $number => $frequency) {
            $formattedNumbers[] = [
                'number' => $number,
                'frequency' => $frequency
            ];
        }
        
        return $formattedNumbers;
    }

    /**
     * Dự đoán số Mega 6/45
     */
    public static function predictNumbers645()
    {
        $results = self::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Mega645'],
            'order' => 'draw_date DESC',
            'limit' => 100
        ]);

        $numberFrequency = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            foreach ($numbers as $number) {
                $number = trim($number);
                if (!isset($numberFrequency[$number])) {
                    $numberFrequency[$number] = 0;
                }
                $numberFrequency[$number]++;
            }
        }

        arsort($numberFrequency);
        return array_slice($numberFrequency, 0, 10, true);
    }

    /**
     * Dự đoán số Power 6/55
     */
    public static function predictNumbers655()
    {
        $results = self::find([
            'conditions' => 'draw_type = :draw_type:',
            'bind' => ['draw_type' => 'Power655'],
            'order' => 'draw_date DESC',
            'limit' => 100
        ]);

        $numberFrequency = [];
        foreach ($results as $result) {
            $numbers = explode(',', $result->numbers);
            foreach ($numbers as $number) {
                $number = trim($number);
                if (!isset($numberFrequency[$number])) {
                    $numberFrequency[$number] = 0;
                }
                $numberFrequency[$number]++;
            }
        }

        arsort($numberFrequency);
        return array_slice($numberFrequency, 0, 10, true);
    }
    
}
