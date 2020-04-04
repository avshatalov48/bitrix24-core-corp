<?php
namespace Bitrix\Crm\Order\Matcher\Internals;

use Bitrix\Crm\Order\Matcher\BaseEntityMatcher;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FormTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_order_props_form';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'PERSON_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'DUPLICATE_MODE' => array(
				'data_type' => 'enum',
				'default_value' => BaseEntityMatcher::getDefaultDuplicateMode(),
				'values' => BaseEntityMatcher::DUPLICATE_CONTROL_MODES
			)
		);
	}

	public static function getDuplicateModeByPersonType($personTypeId)
	{
		$form = static::getRow([
			'select' => ['DUPLICATE_MODE'],
			'filter' => ['PERSON_TYPE_ID' => $personTypeId]
		]);

		return isset($form['DUPLICATE_MODE']) ? $form['DUPLICATE_MODE'] : BaseEntityMatcher::getDefaultDuplicateMode();
	}
}
