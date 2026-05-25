<?php

namespace App\Models;
class HeadTailStats extends \Phalcon\Mvc\Model
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
    protected $draw_type;

    /**
     *
     * @var integer
     */
    protected $head_number;

    /**
     *
     * @var integer
     */
    protected $tail_number;

    /**
     *
     * @var integer
     */
    protected $frequency;

    /**
     *
     * @var string
     */
    protected $period;

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
     * Method to set the value of field head_number
     *
     * @param integer $head_number
     * @return $this
     */
    public function setHeadNumber($head_number)
    {
        $this->head_number = $head_number;

        return $this;
    }

    /**
     * Method to set the value of field tail_number
     *
     * @param integer $tail_number
     * @return $this
     */
    public function setTailNumber($tail_number)
    {
        $this->tail_number = $tail_number;

        return $this;
    }

    /**
     * Method to set the value of field frequency
     *
     * @param integer $frequency
     * @return $this
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Method to set the value of field period
     *
     * @param string $period
     * @return $this
     */
    public function setPeriod($period)
    {
        $this->period = $period;

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
     * Returns the value of field draw_type
     *
     * @return string
     */
    public function getDrawType()
    {
        return $this->draw_type;
    }

    /**
     * Returns the value of field head_number
     *
     * @return integer
     */
    public function getHeadNumber()
    {
        return $this->head_number;
    }

    /**
     * Returns the value of field tail_number
     *
     * @return integer
     */
    public function getTailNumber()
    {
        return $this->tail_number;
    }

    /**
     * Returns the value of field frequency
     *
     * @return integer
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * Returns the value of field period
     *
     * @return string
     */
    public function getPeriod()
    {
        return $this->period;
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
        $this->setSource("head_tail_stats");
        $this->belongsTo('province_id', '\Provinces', 'id', ['alias' => 'Provinces']);
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return HeadTailStats[]|HeadTailStats|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return HeadTailStats|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
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
            'draw_type' => 'draw_type',
            'head_number' => 'head_number',
            'tail_number' => 'tail_number',
            'frequency' => 'frequency',
            'period' => 'period',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];
    }

}
