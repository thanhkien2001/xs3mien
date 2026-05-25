<?php
namespace App\Models;
class LotteryResults extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    protected $id;

    /**
     *
     * @var integer
     */
    protected $province_id;

    /**
     *
     * @var string
     */
    protected $draw_date;

    /**
     *
     * @var string
     */
    protected $draw_type;

    /**
     *
     * @var string
     */
    protected $special_prize;

    /**
     *
     * @var string
     */
    protected $first_prize;

    /**
     *
     * @var string
     */
    protected $second_prize;

    /**
     *
     * @var string
     */
    protected $third_prize;

    /**
     *
     * @var string
     */
    protected $fourth_prize;

    /**
     *
     * @var string
     */
    protected $fifth_prize;

    /**
     *
     * @var string
     */
    protected $sixth_prize;

    /**
     *
     * @var string
     */
    protected $seventh_prize;

    /**
     *
     * @var string
     */
    protected $eighth_prize;

    /**
     *
     * @var string
     */
    protected $lv;


    /**
     *
     * @var string
     */
    protected $created_at;

    /**
     *
     * @var string
     */
    protected $updated_at;

    /**
     *
     * @var integer
     */
    protected $status;

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
     * Method to set the value of field province_id
     *
     * @param integer $province_id
     * @return $this
     */
    public function setProvinceId($province_id)
    {
        $this->province_id = $province_id;

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
     * Method to set the value of field special_prize
     *
     * @param string $special_prize
     * @return $this
     */
    public function setSpecialPrize($special_prize)
    {
        $this->special_prize = $special_prize;

        return $this;
    }

    /**
     * Method to set the value of field first_prize
     *
     * @param string $first_prize
     * @return $this
     */
    public function setFirstPrize($first_prize)
    {
        $this->first_prize = $first_prize;

        return $this;
    }

    /**
     * Method to set the value of field second_prize
     *
     * @param string $second_prize
     * @return $this
     */
    public function setSecondPrize($second_prize)
    {
        $this->second_prize = $second_prize;

        return $this;
    }

    /**
     * Method to set the value of field third_prize
     *
     * @param string $third_prize
     * @return $this
     */
    public function setThirdPrize($third_prize)
    {
        $this->third_prize = $third_prize;

        return $this;
    }

    /**
     * Method to set the value of field fourth_prize
     *
     * @param string $fourth_prize
     * @return $this
     */
    public function setFourthPrize($fourth_prize)
    {
        $this->fourth_prize = $fourth_prize;

        return $this;
    }

    /**
     * Method to set the value of field fifth_prize
     *
     * @param string $fifth_prize
     * @return $this
     */
    public function setFifthPrize($fifth_prize)
    {
        $this->fifth_prize = $fifth_prize;

        return $this;
    }

    /**
     * Method to set the value of field sixth_prize
     *
     * @param string $sixth_prize
     * @return $this
     */
    public function setSixthPrize($sixth_prize)
    {
        $this->sixth_prize = $sixth_prize;

        return $this;
    }

    /**
     * Method to set the value of field seventh_prize
     *
     * @param string $seventh_prize
     * @return $this
     */
    public function setSeventhPrize($seventh_prize)
    {
        $this->seventh_prize = $seventh_prize;

        return $this;
    }

    /**
     * Method to set the value of field eighth_prize
     *
     * @param string $eighth_prize
     * @return $this
     */
    public function setEighthPrize($eighth_prize)
    {
        $this->eighth_prize = $eighth_prize;

        return $this;
    }

    /**
     * Method to set the value of field lv
     *
     * @param string $lv
     * @return $this
     */
    public function setLv($lv)
    {
        $this->lv = $lv;

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
     * Method to set the value of field updated_at
     *
     * @param string $updated_at
     * @return $this
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Method to set the value of field status
     *
     * @param integer $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
     * Returns the value of field province_id
     *
     * @return integer
     */
    public function getProvinceId()
    {
        return $this->province_id;
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
     * Returns the value of field draw_type
     *
     * @return string
     */
    public function getDrawType()
    {
        return $this->draw_type;
    }

    /**
     * Returns the value of field special_prize
     *
     * @return string
     */
    public function getSpecialPrize()
    {
        return $this->special_prize;
    }

    /**
     * Returns the value of field first_prize
     *
     * @return string
     */
    public function getFirstPrize()
    {
        return $this->first_prize;
    }

    /**
     * Returns the value of field second_prize
     *
     * @return string
     */
    public function getSecondPrize()
    {
        return $this->second_prize;
    }

    /**
     * Returns the value of field third_prize
     *
     * @return string
     */
    public function getThirdPrize()
    {
        return $this->third_prize;
    }

    /**
     * Returns the value of field fourth_prize
     *
     * @return string
     */
    public function getFourthPrize()
    {
        return $this->fourth_prize;
    }

    /**
     * Returns the value of field fifth_prize
     *
     * @return string
     */
    public function getFifthPrize()
    {
        return $this->fifth_prize;
    }

    /**
     * Returns the value of field sixth_prize
     *
     * @return string
     */
    public function getSixthPrize()
    {
        return $this->sixth_prize;
    }

    /**
     * Returns the value of field seventh_prize
     *
     * @return string
     */
    public function getSeventhPrize()
    {
        return $this->seventh_prize;
    }

    /**
     * Returns the value of field eighth_prize
     *
     * @return string
     */
    public function getEighthPrize()
    {
        return $this->eighth_prize;
    }

    /**
     * Returns the value of field lv
     *
     * @return string
     */
    public function getLv()
    {
        return $this->lv;
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
     * Returns the value of field updated_at
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Returns the value of field status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("xoso_db");
        $this->setSource("lottery_results");
        $this->belongsTo('province_id', 'App\\Models\\Provinces', 'id', ['alias' => 'Provinces']);
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return LotteryResults[]|LotteryResults|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return LotteryResults|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
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
            'province_id' => 'province_id',
            'draw_date' => 'draw_date',
            'draw_type' => 'draw_type',
            'special_prize' => 'special_prize',
            'first_prize' => 'first_prize',
            'second_prize' => 'second_prize',
            'third_prize' => 'third_prize',
            'fourth_prize' => 'fourth_prize',
            'fifth_prize' => 'fifth_prize',
            'sixth_prize' => 'sixth_prize',
            'seventh_prize' => 'seventh_prize',
            'eighth_prize' => 'eighth_prize',
            'lv' => 'lv',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'status' => 'status'
        ];
    }
    function parsePrizeString($prizeStr)
    {
        if (empty($prizeStr)) return [];

        // Xóa dấu ngoặc vuông và dấu nháy
        $clean = trim($prizeStr, "[]\"");
        // Thêm dấu phẩy ngăn cách nếu đang dính nhau
        $clean = str_replace(['""', '"'], [',', ''], $clean);

        // Tách thành mảng số
        $numbers = array_filter(array_map('trim', explode(',', $clean)));

        return $numbers;
    }

    /**
     * Get the latest result for a specific draw type
     */
    public static function getLatestResult($drawType, $provinceId = null)
    {
        $conditions = 'draw_type = :draw_type:';
        $bind = ['draw_type' => $drawType];
        
        if ($provinceId !== null) {
            $conditions .= ' AND province_id = :province_id:';
            $bind['province_id'] = $provinceId;
        }
        
        return self::findFirst([
            'conditions' => $conditions,
            'bind' => $bind,
            'order' => 'draw_date DESC, id DESC'
        ]);
    }

    /**
     * Get latest results for a specific draw type with limit
     */
    public static function getLatestResults($drawType, $limit = 4, $provinceId = null)
    {
        $conditions = 'draw_type = :draw_type:';
        $bind = ['draw_type' => $drawType];
        
        if ($provinceId !== null) {
            $conditions .= ' AND province_id = :province_id:';
            $bind['province_id'] = $provinceId;
        }
        
        return self::find([
            'conditions' => $conditions,
            'bind' => $bind,
            'order' => 'draw_date DESC, id DESC',
            'limit' => $limit
        ]);
    }

}
