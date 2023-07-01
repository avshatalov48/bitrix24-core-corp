<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class ResultTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Result_Query query()
 * @method static EO_Result_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Result_Result getById($id)
 * @method static EO_Result_Result getList(array $parameters = [])
 * @method static EO_Result_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Result createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Result_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Result wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Result_Collection wakeUpCollection($rows)
 */
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
		$tableName = ResultEntityTable::getTableName();
		$sql = "delete from {$tableName} where RESULT_ID = " . intval($data['primary']['ID']);
		Application::getConnection()->query($sql);

		return $result;
	}
}
