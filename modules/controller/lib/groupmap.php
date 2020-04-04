<?php
namespace Bitrix\Controller;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class GroupMapTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONTROLLER_GROUP_ID int optional
 * <li> REMOTE_GROUP_CODE string(30) optional
 * <li> LOCAL_GROUP_CODE string(30) optional
 * <li> CONTROLLER_GROUP reference to {@link \Bitrix\Controller\GroupTable}
 * </ul>
 *
 * @package Bitrix\Controller
 **/

class GroupMapTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_group_map';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('GROUP_MAP_ENTITY_ID_FIELD'),
			),
			'CONTROLLER_GROUP_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('GROUP_MAP_ENTITY_CONTROLLER_GROUP_ID_FIELD'),
			),
			'REMOTE_GROUP_CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateRemoteGroupCode'),
				'title' => Loc::getMessage('GROUP_MAP_ENTITY_REMOTE_GROUP_CODE_FIELD'),
			),
			'LOCAL_GROUP_CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLocalGroupCode'),
				'title' => Loc::getMessage('GROUP_MAP_ENTITY_LOCAL_GROUP_CODE_FIELD'),
			),
			'CONTROLLER_GROUP' => array(
				'data_type' => 'Bitrix\Controller\GroupTable',
				'reference' => array('=this.CONTROLLER_GROUP_ID' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for REMOTE_GROUP_CODE field.
	 *
	 * @return array
	 */
	public static function validateRemoteGroupCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 30),
		);
	}
	/**
	 * Returns validators for LOCAL_GROUP_CODE field.
	 *
	 * @return array
	 */
	public static function validateLocalGroupCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 30),
		);
	}

	/**
	 * Returns true if the mapping is exists.
	 * 
	 * @param array $fields Filter array.
	 * @return boolean
	 * @throws Main\ArgumentException
	 */
	public static function isExists($fields)
	{
		$filter = array();
		foreach ($fields as $name => $value)
			$filter["=".$name] = $value;

		$match = false;
		$list = self::getList(array("filter" => $filter));
		while (($result = $list->fetch()) && !$match)
		{
			$match = true;
			foreach ($fields as $name => $value)
			{
				$match = $match && ($result[$name] === $value);
			}
		}

		return $match;
	}

	/**
	 * Returns array of mapping arrays in form of array("FROM"=>NN, "TO"=>MM).
	 * 
	 * @param string $from Mapping field.
	 * @param string $to Mapping field.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getMapping($from, $to)
	{
		$result = array();
		$filter = array(
			"!=".$from => false,
			"!=".$to => false,
		);
		$list = self::getList(array("filter" => $filter));
		while ($item = $list->fetch())
		{
			$result[] = array(
				"FROM" => $item[$from],
				"TO" => $item[$to],
			);
		}
		return $result;
	}
}