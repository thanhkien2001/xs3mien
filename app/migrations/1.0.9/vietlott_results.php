<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class VietlottResultsMigration_109
 */
class VietlottResultsMigration_109 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('vietlott_results', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_INTEGER,
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
                    'numbers',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 50,
                        'after' => 'draw_date'
                    ]
                ),
                new Column(
                    'jackpot_amount',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'numbers'
                    ]
                ),
                new Column(
                    'created_at',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'default' => "CURRENT_TIMESTAMP",
                        'notNull' => false,
                        'after' => 'jackpot_amount'
                    ]
                ),
                new Column(
                    'jackpot_2',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'created_at'
                    ]
                ),
                new Column(
                    'giai_nhat_gia_tri',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'default' => "0",
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'jackpot_2'
                    ]
                ),
                new Column(
                    'giai_nhi_gia_tri',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'default' => "0",
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'giai_nhat_gia_tri'
                    ]
                ),
                new Column(
                    'giai_ba_gia_tri',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'default' => "0",
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'giai_nhi_gia_tri'
                    ]
                ),
                new Column(
                    'jackpot_1_sl',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'giai_ba_gia_tri'
                    ]
                ),
                new Column(
                    'jackpot_2_sl',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'jackpot_1_sl'
                    ]
                ),
                new Column(
                    'giai_nhat_sl',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'jackpot_2_sl'
                    ]
                ),
                new Column(
                    'giai_nhi_sl',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'giai_nhat_sl'
                    ]
                ),
                new Column(
                    'giai_ba_sl',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => false,
                        'size' => 1,
                        'after' => 'giai_nhi_sl'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('unique_draw', ['draw_type', 'draw_number', 'draw_date'], 'UNIQUE'),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '325',
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
