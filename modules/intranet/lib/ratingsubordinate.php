<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */
namespace Bitrix\Intranet;

use Bitrix\Main\Entity;

class RatingSubordinateTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_rating_subordinate';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'RATING_ID' => array(
				'data_type' => 'integer',
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
			),
			'VOTES' => array(
				'data_type' => 'float',
			),
		);
	}
}