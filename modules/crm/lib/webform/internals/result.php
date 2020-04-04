<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class ResultTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_result';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'DATE_INSERT' => array(
				'data_type' => 'date',
				'required' => true,
				'default_value' => new DateTime(),
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'ORIGIN_ID' => array(
				'data_type' => 'string'
			),
			'FORM' => array(
				'data_type' => 'Bitrix\Crm\WebForm\Internals\FormTable',
				'reference' => array('=this.FORM_ID' => 'ref.ID'),
			),
		);
	}

	/**
	 * @param Entity\Event $event Event
	 * @return Entity\EventResult Result
	 */
	public static function onAfterDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		// delete result entities
		ResultEntityTable::delete(array('RESULT_ID' => $data['primary']['ID']));

		return $result;
	}
}
