<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

/**
 * Class TmpFileTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TOKEN string(32) optional
 * <li> FILENAME string(255) optional
 * <li> CONTENT_TYPE string(255) optional
 * <li> BUCKET_ID int optional
 * <li> SIZE int optional
 * <li> RECEIVED_SIZE int optional
 * <li> WIDTH int optional
 * <li> HEIGHT int optional
 * <li> IS_CLOUD int optional
 * <li> CREATED_BY int optional
 * <li> CREATE_TIME datetime optional
 * <li> PATH string optional
 * </ul>
 *
 * @package Bitrix\Disk
 **/

final class TmpFileTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_tmp_file';
	}

	public static function getMap()
	{
		$date = new DateTime();
		$yesterday = Application::getConnection()
			->getSqlHelper()
			->getCharToDateFunction($date->add('-1 DAY')->format("Y-m-d H:i:s"));

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TOKEN' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateToken'),
			),
			'FILENAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateFilename'),
			),
			'CONTENT_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateContentType'),
			),
			'PATH' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validatePath'),
			),
			'BUCKET_ID' => array(
				'data_type' => 'integer',
			),
			'SIZE' => array(
				'data_type' => 'integer',
			),
			'RECEIVED_SIZE' => array(
				'data_type' => 'integer',
			),
			'WIDTH' => array(
				'data_type' => 'integer',
			),
			'HEIGHT' => array(
				'data_type' => 'integer',
			),
			'IS_CLOUD' => array(
				'data_type' => 'boolean',
				'values' => array(0, 1),
				'default_value' => 0,
			),
			'IRRELEVANT' => array(
				'data_type' => 'boolean',
				'expression' => array(
					"CASE WHEN (%s < {$yesterday}) THEN 1 ELSE 0 END",
					'CREATE_TIME',
				),
				'values' => array(0, 1),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
			),
			'CREATE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.CREATED_BY' => 'ref.ID'
				),
			),
			'CREATE_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
			),
		);
	}

	public static function validateToken()
	{
		return array(
			new Entity\Validator\Length(1, 32),
		);
	}

	public static function validateFilename()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function validateContentType()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function validatePath()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
