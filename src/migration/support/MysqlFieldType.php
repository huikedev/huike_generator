<?php


namespace huikedev\huike_generator\migration\support;


class MysqlFieldType
{
    const TYPE=[
        'tinyint'=>'tinyInteger',
        'int'=>'integer',
        'bigint'=>'bigInteger',
        'double'=>'float',
        'decimal'=>'decimal',
        'char'=>'char',
        'varchar'=>'string',
        'mediumtext'=>'mediumText',
        'test'=>'text',
        'longtext'=>'longText',
        'json'=>'json',
        'date'=>'date',
        'time'=>'time',
        'datetime'=>'dateTime',
        'timestamp'=>'timestamp',
        ];
}