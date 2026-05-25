<?php
namespace App\Models;
class KenoResults extends \Phalcon\Mvc\Model
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
    protected $even_count;

    /**
     *
     * @var integer
     */
    protected $odd_count;

    /**
     *
     * @var integer
     */
    protected $large_count;

    /**
     *
     * @var integer
     */
    protected $small_count;

    /**
     *
     * @var string
     */
    protected $prize_level;

    /**
     *
     * @var double
     */
    protected $prize_amount;

    /**
     *
     * @var integer
     */
    protected $winner_count;

    /**
     *
     * @var string
     */
    protected $created_at;

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
     * Method to set the value of field even_count
     *
     * @param integer $even_count
     * @return $this
     */
    public function setEvenCount($even_count)
    {
        $this->even_count = $even_count;

        return $this;
    }

    /**
     * Method to set the value of field odd_count
     *
     * @param integer $odd_count
     * @return $this
     */
    public function setOddCount($odd_count)
    {
        $this->odd_count = $odd_count;

        return $this;
    }

    /**
     * Method to set the value of field large_count
     *
     * @param integer $large_count
     * @return $this
     */
    public function setLargeCount($large_count)
    {
        $this->large_count = $large_count;

        return $this;
    }

    /**
     * Method to set the value of field small_count
     *
     * @param integer $small_count
     * @return $this
     */
    public function setSmallCount($small_count)
    {
        $this->small_count = $small_count;

        return $this;
    }

    /**
     * Method to set the value of field prize_level
     *
     * @param string $prize_level
     * @return $this
     */
    public function setPrizeLevel($prize_level)
    {
        $this->prize_level = $prize_level;

        return $this;
    }

    /**
     * Method to set the value of field prize_amount
     *
     * @param double $prize_amount
     * @return $this
     */
    public function setPrizeAmount($prize_amount)
    {
        $this->prize_amount = $prize_amount;

        return $this;
    }

    /**
     * Method to set the value of field winner_count
     *
     * @param integer $winner_count
     * @return $this
     */
    public function setWinnerCount($winner_count)
    {
        $this->winner_count = $winner_count;

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
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * Returns the value of field even_count
     *
     * @return integer
     */
    public function getEvenCount()
    {
        return $this->even_count;
    }

    /**
     * Returns the value of field odd_count
     *
     * @return integer
     */
    public function getOddCount()
    {
        return $this->odd_count;
    }

    /**
     * Returns the value of field large_count
     *
     * @return integer
     */
    public function getLargeCount()
    {
        return $this->large_count;
    }

    /**
     * Returns the value of field small_count
     *
     * @return integer
     */
    public function getSmallCount()
    {
        return $this->small_count;
    }

    /**
     * Returns the value of field prize_level
     *
     * @return string
     */
    public function getPrizeLevel()
    {
        return $this->prize_level;
    }

    /**
     * Returns the value of field prize_amount
     *
     * @return double
     */
    public function getPrizeAmount()
    {
        return $this->prize_amount;
    }

    /**
     * Returns the value of field winner_count
     *
     * @return integer
     */
    public function getWinnerCount()
    {
        return $this->winner_count;
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
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("xoso_db");
        $this->setSource("keno_results");
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return KenoResults[]|KenoResults|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return KenoResults|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
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
            'draw_number' => 'draw_number',
            'draw_date' => 'draw_date',
            'numbers' => 'numbers',
            'even_count' => 'even_count',
            'odd_count' => 'odd_count',
            'large_count' => 'large_count',
            'small_count' => 'small_count',
            'prize_level' => 'prize_level',
            'prize_amount' => 'prize_amount',
            'winner_count' => 'winner_count',
            'created_at' => 'created_at'
        ];
    }


    public static function getLatest()
    {
        return self::findFirst([
            'order' => 'draw_date DESC',
            'limit' => 1
        ]);
    }

    /**
     * Lấy danh sách kết quả với phân trang
     */
    public static function getResults($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        return self::find([
            'order' => 'draw_number DESC',
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Thống kê tần suất số trong 100 kỳ gần nhất
     */
    public static function getStatistics()
    {
        $query = "
            SELECT number, COUNT(*) as frequency
            FROM (
                SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(k.numbers, ',', n.n), ',', -1) AS number
                FROM (
                    SELECT id, numbers
                    FROM keno_results
                    ORDER BY draw_date DESC
                    LIMIT 100
                ) k
                CROSS JOIN (
                    SELECT n
                    FROM (
                        SELECT a.N + 1 AS n
                        FROM (
                            SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                            UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                            UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14
                            UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
                        ) a
                    ) AS numbers
                ) n
                WHERE n.n <= 20
            ) t
            WHERE number BETWEEN 1 AND 80
            GROUP BY number
            ORDER BY number ASC
        ";

        $model = new self();
        return $model->getReadConnection()->query($query)->fetchAll();
    }
}
