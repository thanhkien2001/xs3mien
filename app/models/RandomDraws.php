<?php

namespace App\Models;
class RandomDraws extends \Phalcon\Mvc\Model
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
    protected $result;

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
     * Method to set the value of field result
     *
     * @param string $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

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
     * Returns the value of field draw_type
     *
     * @return string
     */
    public function getDrawType()
    {
        return $this->draw_type;
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
     * Returns the value of field result
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
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
        $this->setSource("random_draws");
        $this->belongsTo('province_id', '\Provinces', 'id', ['alias' => 'Provinces']);
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return RandomDraws[]|RandomDraws|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return RandomDraws|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
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
            'province_id' => 'province_id',
            'draw_date' => 'draw_date',
            'result' => 'result',
            'created_at' => 'created_at'
        ];
    }

}
