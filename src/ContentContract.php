<?php

namespace DavidFricker\DataAbstracter;

// declare final class?
class ContentContract {
    const DATA_TYPE_INT = 'int';
    const DATA_TYPE_TEXT = 'text';
    const DATA_TYPE_DATETIME = 'datetime';
    
    const TABLE = [
        'customers' => 'customers'
    ];

    const SCHEMA = [
        'customers' => [
            'id' => 'customerid',
            'email' => 'email',
            'password' => 'pass'
        ]
    ];

    const TYPE = [
         'customers' => [
            'id' => self::DATA_TYPE_INT,
            'email' => self::DATA_TYPE_TEXT,
            'password' => self::DATA_TYPE_TEXT
        ]
    ];
}

//var_dump(ContentContract::TABLE['customers'], ContentContract::SCHEMA['customers']['id'], ContentContract::TYPE['customers']['id']);
