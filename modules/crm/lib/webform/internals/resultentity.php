<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Helper;

Loc::loadMessages(__FILE__);

class ResultEntityTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_result_entity';
	}

	public static function getMap()
	{
		return array(
			'FORM_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'RESULT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'ENTITY_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'ITEM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			)
		);
	}

	public static function addBatch($formId, array $list)
	{
		$counterEntities = array();
		foreach($list as $item)
		{
			$result = static::add(array(
				'FORM_ID' => $formId,
				'RESULT_ID' => $item['RESULT_ID'],
				'ENTITY_NAME' => $item['ENTITY_NAME'],
				'ITEM_ID' => $item['ITEM_ID'],
			));
			if($result->isSuccess() && !$item['IS_DUPLICATE'])
			{
				$counterEntities[] = $item['ENTITY_NAME'];
			}
		}

		if(count($counterEntities) > 0)
		{
			FormCounterTable::incEntityCounters($formId, $counterEntities);
		}
	}
}
