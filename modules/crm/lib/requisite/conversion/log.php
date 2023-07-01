<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class LogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CREATED datetime mandatory
 * <li> MSEC string(6) mandatory
 * <li> TYPE string(20) mandatory
 * <li> TAG string(255) mandatory
 * <li> MESSAGE string(4095) optional
 * </ul>
 *
 * @package Bitrix\Composite
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Log_Query query()
 * @method static EO_Log_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Log_Result getById($id)
 * @method static EO_Log_Result getList(array $parameters = [])
 * @method static EO_Log_Entity getEntity()
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_Log createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_Log_Collection createCollection()
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_Log wakeUpObject($row)
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_Log_Collection wakeUpCollection($rows)
 */
class LogTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return "b_crm_rq_conv_log";
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			"ID" => [
				"data_type" => "integer",
				"primary" => true,
				"autocomplete" => true,
				"title" => "ID"
			],
			"CREATED" => [
				"data_type" => "datetime",
				"required" => true
			],
			"MSEC" => [
				"data_type" => "integer",
				"required" => true,
				"validation" => [__CLASS__, "validateMsec"]
			],
			"TYPE" => [
				"data_type" => "enum",
				"required" => true,
				"values" => Logger::getTypes(),
				"default_value" => Logger::TYPE_INFO
			],
			"TAG" => [
				"data_type" => "string",
				"required" => true,
				"validation" => [__CLASS__, "validateTag"]
			],
			"MESSAGE" => [
				"data_type" => "string",
				"validation" => [__CLASS__, "validateMessage"]
			]
		];
	}

	/**
	 * Returns validators for MSEC field.
	 *
	 * @return array
	 */
	public static function validateMsec()
	{
		return array(
			new Main\Entity\Validator\Range(0, 999999),
		);
	}
	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}
	/**
	 * Returns validators for TAG field.
	 *
	 * @return array
	 */
	public static function validateTag()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for MESSAGE field.
	 *
	 * @return array
	 */
	public static function validateMessage()
	{
		return array(
			new Main\Entity\Validator\Length(null, 4095),
		);
	}

	/**
	 * Clears all logging data
	 */
	public static function deleteAll()
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$connection->queryExecute("DELETE FROM {$tableName}");
	}
}