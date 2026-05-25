<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class LotteryResultsMigration_101
 */
class LotteryResultsMigration_101 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('lottery_results', [
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
                    'province_id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'id'
                    ]
                ),
                new Column(
                    'draw_date',
                    [
                        'type' => Column::TYPE_DATE,
                        'notNull' => true,
                        'after' => 'province_id'
                    ]
                ),
                new Column(
                    'draw_type',
                    [
                        'type' => Column::TYPE_ENUM,
                        'notNull' => true,
                        'size' => "'XSMB','XSMN','XSMT','Power 6/55','Mega 6/45','Max 3D','Max 3D Pro','Keno'",
                        'after' => 'draw_date'
                    ]
                ),
                new Column(
                    'special_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 20,
                        'after' => 'draw_type'
                    ]
                ),
                new Column(
                    'first_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 20,
                        'after' => 'special_prize'
                    ]
                ),
                new Column(
                    'second_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'first_prize'
                    ]
                ),
                new Column(
                    'third_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'second_prize'
                    ]
                ),
                new Column(
                    'fourth_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'third_prize'
                    ]
                ),
                new Column(
                    'fifth_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'fourth_prize'
                    ]
                ),
                new Column(
                    'sixth_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'fifth_prize'
                    ]
                ),
                new Column(
                    'seventh_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'sixth_prize'
                    ]
                ),
                new Column(
                    'eighth_prize',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 255,
                        'after' => 'seventh_prize'
                    ]
                ),
                new Column(
                    'created_at',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'default' => "CURRENT_TIMESTAMP",
                        'notNull' => false,
                        'after' => 'eighth_prize'
                    ]
                ),
                new Column(
                    'updated_at',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'default' => "CURRENT_TIMESTAMP DEFAULT_GENERATED on update CURRENT_TIMESTAMP",
                        'notNull' => false,
                        'after' => 'created_at'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('province_id', ['province_id'], ''),
                new Index('idx_draw_date', ['draw_date'], ''),
                new Index('idx_draw_type', ['draw_type'], ''),
            ],
            'references' => [
                new Reference(
                    'lottery_results_ibfk_1',
                    [
                        'referencedSchema' => 'xoso_db',
                        'referencedTable' => 'provinces',
                        'columns' => ['province_id'],
                        'referencedColumns' => ['id'],
                        'onUpdate' => 'NO ACTION',
                        'onDelete' => 'NO ACTION'
                    ]
                ),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '11',
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
