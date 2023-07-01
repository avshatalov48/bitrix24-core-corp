<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class FormCounterDailyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FormCounterDaily_Query query()
 * @method static EO_FormCounterDaily_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FormCounterDaily_Result getById($id)
 * @method static EO_FormCounterDaily_Result getList(array $parameters = [])
 * @method static EO_FormCounterDaily_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormCounterDaily createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormCounterDaily_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormCounterDaily wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_FormCounterDaily_Collection wakeUpCollection($rows)
 */
class FormCounterDailyTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_counter_daily';
	}

	public static function getMap()
	{
		return [
			'DATE_STAT' => [
				'data_type' => 'date',
				'default_value' => new Date(),
				'primary' => true,
			],
			'FORM_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'VIEWS' => [
				'data_type' => 'integer',
				'default_value' => 0,
			],
			'START_FILL' => [
				'data_type' => 'integer',
				'default_value' => 0,
			],
			'END_FILL' => [
				'data_type' => 'integer',
				'default_value' => 0,
			],
		];
	}

	public static function resetCounters(Date $date, int $formId)
	{
		return \Bitrix\Main\Application::getInstance()->getConnection()->query('
			DELETE FROM '.self::getTableName().'
				WHERE FORM_ID = '.$formId.'
				AND DATE_STAT <= "'.$date->format('Y-m-d').'"
		');
	}

	public static function incrementViews(Date $date, int $formId, int $count = 1)
	{
		return \Bitrix\Main\Application::getInstance()->getConnection()->query('
			INSERT INTO '.self::getTableName().' (DATE_STAT, FORM_ID, VIEWS)
				VALUES("'.$date->format('Y-m-d').'", '.$formId.', '. $count .')
				ON DUPLICATE KEY UPDATE VIEWS = VIEWS + '.$count.'
		');
	}

	public static function incrementStartFill(Date $date, int $formId, int $count = 1)
	{
		return \Bitrix\Main\Application::getInstance()->getConnection()->query('
			INSERT INTO '.self::getTableName().' (DATE_STAT, FORM_ID, START_FILL)
				VALUES("'.$date->format('Y-m-d').'", '.$formId.', '.$count.')
				ON DUPLICATE KEY UPDATE START_FILL = START_FILL + '.$count.'
		');
	}

	public static function incrementEndFill(Date $date, int $formId, int $count = 1)
	{
		return \Bitrix\Main\Application::getInstance()->getConnection()->query('
			INSERT INTO '.self::getTableName().' (DATE_STAT, FORM_ID, END_FILL)
				VALUES("'.$date->format('Y-m-d').'", '.$formId.', '.$count.')
				ON DUPLICATE KEY UPDATE END_FILL = END_FILL + '.$count.'
		');
	}

	public static function deleteByFormId(int $formId)
	{
		return \Bitrix\Main\Application::getInstance()->getConnection()->query(
			'DELETE FROM '.self::getTableName().' WHERE FORM_ID = '.$formId
		);
	}
}
