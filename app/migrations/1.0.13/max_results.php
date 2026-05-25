<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class MaxResultsMigration_113
 */
class MaxResultsMigration_113 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('max_results', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'size' => 1,
                        'first' => true
                    ]
                ),
                new Column(
                    'draw_type',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'id'
                    ]
                ),
                new Column(
                    'draw_number',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 20,
                        'after' => 'draw_type'
                    ]
                ),
                new Column(
                    'draw_date',
                    [
                        'type' => Column::TYPE_DATE,
                        'notNull' => true,
                        'after' => 'draw_number'
                    ]
                ),
                new Column(
                    'jackpot_numbers',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'after' => 'draw_date'
                    ]
                ),
                new Column(
                    'first_numbers',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'after' => 'jackpot_numbers'
                    ]
                ),
                new Column(
                    'second_numbers',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'after' => 'first_numbers'
                    ]
                ),
                new Column(
                    'third_numbers',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'after' => 'second_numbers'
                    ]
                ),
                new Column(
                    'jackpot_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'third_numbers'
                    ]
                ),
                new Column(
                    'first_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'jackpot_winners'
                    ]
                ),
                new Column(
                    'second_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'first_winners'
                    ]
                ),
                new Column(
                    'third_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'second_winners'
                    ]
                ),
                new Column(
                    'secondary_prize_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'third_winners'
                    ]
                ),
                new Column(
                    'fourth_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'secondary_prize_winners'
                    ]
                ),
                new Column(
                    'fifth_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'fourth_winners'
                    ]
                ),
                new Column(
                    'sixth_winners',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'fifth_winners'
                    ]
                ),
                new Column(
                    'created_at',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'default' => "CURRENT_TIMESTAMP",
                        'notNull' => false,
                        'after' => 'sixth_winners'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('idx_draw_unique', ['draw_number', 'draw_type'], 'UNIQUE'),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '64',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8mb4_0900_ai_ci',
            ],
        ]);
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up(): void
    {
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void
    {
    }
}
