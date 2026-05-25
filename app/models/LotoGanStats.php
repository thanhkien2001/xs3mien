<?php

namespace App\Models;
class LotoGanStats extends \Phalcon\Mvc\Model
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
    protected $number;

    /**
     *
     * @var string
     */
    protected $draw_type;

    /**
     *
     * @var string
     */
    protected $last_appeared_date;

    /**
     *
     * @var integer
     */
    protected $days_since_last;

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
     * Method to set the value of field number
     *
     * @param string $number
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;

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
     * Method to set the value of field last_appeared_date
     *
     * @param string $last_appeared_date
     * @return $this
     */
    public function setLastAppearedDate($last_appeared_date)
    {
        $this->last_appeared_date = $last_appeared_date;

        return $this;
    }

    /**
     * Method to set the value of field days_since_last
     *
     * @param integer $days_since_last
     * @return $this
     */
    public function setDaysSinceLast($days_since_last)
    {
        $this->days_since_last = $days_since_last;

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
     * Returns the value of field number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
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
     * Returns the value of field last_appeared_date
     *
     * @return string
     */
    public function getLastAppearedDate()
    {
        return $this->last_appeared_date;
    }

    /**
     * Returns the value of field days_since_last
     *
     * @return integer
     */
    public function getDaysSinceLast()
    {
        return $this->days_since_last;
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
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("xoso_db");
        $this->setSource("loto_gan_stats");
        $this->belongsTo('province_id', '\Provinces', 'id', ['alias' => 'Provinces']);
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return LotoGanStats[]|LotoGanStats|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return LotoGanStats|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
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
            'number' => 'number',
            'draw_type' => 'draw_type',
            'last_appeared_date' => 'last_appeared_date',
            'days_since_last' => 'days_since_last',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];
    }

}
