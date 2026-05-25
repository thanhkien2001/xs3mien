<?php

namespace App\Models;

use Phalcon\Di\Di;
class MaxResults extends \Phalcon\Mvc\Model
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
    protected $jackpot_numbers;

    /**
     *
     * @var string
     */
    protected $first_numbers;

    /**
     *
     * @var string
     */
    protected $second_numbers;

    /**
     *
     * @var string
     */
    protected $third_numbers;

    /**
     *
     * @var string
     */
    protected $jackpot_winners;

    /**
     *
     * @var string
     */
    protected $first_winners;

    /**
     *
     * @var string
     */
    protected $second_winners;

    /**
     *
     * @var string
     */
    protected $third_winners;

    /**
     *
     * @var string
     */
    protected $secondary_prize_winners;

    /**
     *
     * @var string
     */
    protected $fourth_winners;

    /**
     *
     * @var string
     */
    protected $fifth_winners;

    /**
     *
     * @var string
     */
    protected $sixth_winners;

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
     * Method to set the value of field jackpot_numbers
     *
     * @param string $jackpot_numbers
     * @return $this
     */
    public function setJackpotNumbers($jackpot_numbers)
    {
        $this->jackpot_numbers = $jackpot_numbers;

        return $this;
    }

    /**
     * Method to set the value of field first_numbers
     *
     * @param string $first_numbers
     * @return $this
     */
    public function setFirstNumbers($first_numbers)
    {
        $this->first_numbers = $first_numbers;

        return $this;
    }

    /**
     * Method to set the value of field second_numbers
     *
     * @param string $second_numbers
     * @return $this
     */
    public function setSecondNumbers($second_numbers)
    {
        $this->second_numbers = $second_numbers;

        return $this;
    }

    /**
     * Method to set the value of field third_numbers
     *
     * @param string $third_numbers
     * @return $this
     */
    public function setThirdNumbers($third_numbers)
    {
        $this->third_numbers = $third_numbers;

        return $this;
    }

    /**
     * Method to set the value of field jackpot_winners
     *
     * @param string $jackpot_winners
     * @return $this
     */
    public function setJackpotWinners($jackpot_winners)
    {
        $this->jackpot_winners = $jackpot_winners;

        return $this;
    }

    /**
     * Method to set the value of field first_winners
     *
     * @param string $first_winners
     * @return $this
     */
    public function setFirstWinners($first_winners)
    {
        $this->first_winners = $first_winners;

        return $this;
    }

    /**
     * Method to set the value of field second_winners
     *
     * @param string $second_winners
     * @return $this
     */
    public function setSecondWinners($second_winners)
    {
        $this->second_winners = $second_winners;

        return $this;
    }

    /**
     * Method to set the value of field third_winners
     *
     * @param string $third_winners
     * @return $this
     */
    public function setThirdWinners($third_winners)
    {
        $this->third_winners = $third_winners;

        return $this;
    }

    /**
     * Method to set the value of field secondary_prize_winners
     *
     * @param string $secondary_prize_winners
     * @return $this
     */
    public function setSecondaryPrizeWinners($secondary_prize_winners)
    {
        $this->secondary_prize_winners = $secondary_prize_winners;

        return $this;
    }

    /**
     * Method to set the value of field fourth_winners
     *
     * @param string $fourth_winners
     * @return $this
     */
    public function setFourthWinners($fourth_winners)
    {
        $this->fourth_winners = $fourth_winners;

        return $this;
    }

    /**
     * Method to set the value of field fifth_winners
     *
     * @param string $fifth_winners
     * @return $this
     */
    public function setFifthWinners($fifth_winners)
    {
        $this->fifth_winners = $fifth_winners;

        return $this;
    }

    /**
     * Method to set the value of field sixth_winners
     *
     * @param string $sixth_winners
     * @return $this
     */
    public function setSixthWinners($sixth_winners)
    {
        $this->sixth_winners = $sixth_winners;

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
     * Returns the value of field jackpot_numbers
     *
     * @return string
     */
    public function getJackpotNumbers()
    {
        return $this->jackpot_numbers;
    }

    /**
     * Returns the value of field first_numbers
     *
     * @return string
     */
    public function getFirstNumbers()
    {
        return $this->first_numbers;
    }

    /**
     * Returns the value of field second_numbers
     *
     * @return string
     */
    public function getSecondNumbers()
    {
        return $this->second_numbers;
    }

    /**
     * Returns the value of field third_numbers
     *
     * @return string
     */
    public function getThirdNumbers()
    {
        return $this->third_numbers;
    }

    /**
     * Returns the value of field jackpot_winners
     *
     * @return string
     */
    public function getJackpotWinners()
    {
        return $this->jackpot_winners;
    }

    /**
     * Returns the value of field first_winners
     *
     * @return string
     */
    public function getFirstWinners()
    {
        return $this->first_winners;
    }

    /**
     * Returns the value of field second_winners
     *
     * @return string
     */
    public function getSecondWinners()
    {
        return $this->second_winners;
    }

    /**
     * Returns the value of field third_winners
     *
     * @return string
     */
    public function getThirdWinners()
    {
        return $this->third_winners;
    }

    /**
     * Returns the value of field secondary_prize_winners
     *
     * @return string
     */
    public function getSecondaryPrizeWinners()
    {
        return $this->secondary_prize_winners;
    }

    /**
     * Returns the value of field fourth_winners
     *
     * @return string
     */
    public function getFourthWinners()
    {
        return $this->fourth_winners;
    }

    /**
     * Returns the value of field fifth_winners
     *
     * @return string
     */
    public function getFifthWinners()
    {
        return $this->fifth_winners;
    }

    /**
     * Returns the value of field sixth_winners
     *
     * @return string
     */
    public function getSixthWinners()
    {
        return $this->sixth_winners;
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
        $this->setSource("max_results");
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return MaxResults[]|MaxResults|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return MaxResults|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
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
            'jackpot_numbers' => 'jackpot_numbers',
            'first_numbers' => 'first_numbers',
            'second_numbers' => 'second_numbers',
            'third_numbers' => 'third_numbers',
            'jackpot_winners' => 'jackpot_winners',
            'first_winners' => 'first_winners',
            'second_winners' => 'second_winners',
            'third_winners' => 'third_winners',
            'secondary_prize_winners' => 'secondary_prize_winners',
            'fourth_winners' => 'fourth_winners',
            'fifth_winners' => 'fifth_winners',
            'sixth_winners' => 'sixth_winners',
            'created_at' => 'created_at'
        ];
    }
}
