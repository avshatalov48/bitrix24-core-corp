<?php
namespace Bitrix\Crm\Kanban;

use Bitrix\Main\Entity;


class SortTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_kanban_sort';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			'ENTITY_TYPE_ID' => new Entity\IntegerField('ENTITY_TYPE_ID', array(
				'required' => true,
			)),
			'ENTITY_ID' => new Entity\IntegerField('ENTITY_ID', array(
				'required' => true,
			)),
			'PREV_ENTITY_ID' => new Entity\IntegerField('PREV_ENTITY_ID', array(
				'required' => true,
			)),
			'USER_ID' => new Entity\IntegerField('USER_ID', array(
				'required' => true,
			))
		);
	}

	/**
	 * Set previous item.
	 * @param array $fields Item:
	 * - ENTITY_TYPE_ID = LEAD, DEAL, QUOTE, INVOICE
	 * - ENTITY_ID = id of entity
	 * - PREV_ENTITY_ID - previous id of entity (optional)
	 * - USER_ID - user id (optionaly)
	 * @return void
	 */
	public static function setPrevious(array $fields)
	{
		if (!isset($fields['ENTITY_ID']) || $fields['ENTITY_ID']<=0)
		{
			return;
		}
		if (!($fields['ENTITY_TYPE_ID'] = \CCrmOwnerType::ResolveID($fields['ENTITY_TYPE_ID'])))
		{
			return;
		}
		if (!isset($fields['PREV_ENTITY_ID']) || $fields['PREV_ENTITY_ID']<=0)
		{
			$fields['PREV_ENTITY_ID'] = 0;
		}
		if (!isset($fields['USER_ID']))
		{
			$fields['USER_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		}
		//action
		$res = self::getList(array(
			'select' => array(
				'ID'
			),
			'filter' => array(
				'ENTITY_TYPE_ID' => $fields['ENTITY_TYPE_ID'],
				'ENTITY_ID' => $fields['ENTITY_ID'],
				'USER_ID' => $fields['USER_ID'],
			)
		));
		if ($row = $res->fetch())
		{
			self::delete($row['ID']);
		}
		if ($fields['PREV_ENTITY_ID'] >= 0)
		{
			self::add(array(
				'ENTITY_TYPE_ID' => $fields['ENTITY_TYPE_ID'],
				'ENTITY_ID' => $fields['ENTITY_ID'],
				'PREV_ENTITY_ID' => $fields['PREV_ENTITY_ID'],
				'USER_ID' => $fields['USER_ID'],
			));
		}
	}

	/**
	 * Set previous item(s).
	 * @param array $filter Item:
	 * - ENTITY_TYPE_ID = LEAD, DEAL, QUOTE, INVOICE
	 * - ENTITY_ID = id(s) of entity
	 * - USER_ID - user id (optionaly)
	 * @return array
	 */
	public static function getPrevious(array $filter)
	{
		$return = array();

		if (!isset($filter['ENTITY_ID']))
		{
			return $return;
		}
		if (!($filter['ENTITY_TYPE_ID'] = \CCrmOwnerType::ResolveID($filter['ENTITY_TYPE_ID'])))
		{
			return $return;
		}
		if (!isset($filter['USER_ID']))
		{
			$filter['USER_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		}
		$res = self::getList(array(
			'order' => array(
				'ID' => 'ASC'
			),
			'select' => array(
				'ENTITY_ID', 'PREV_ENTITY_ID'
			),
			'filter' => array(
				'ENTITY_TYPE_ID' => $filter['ENTITY_TYPE_ID'],
				'ENTITY_ID' => $filter['ENTITY_ID'],
				'USER_ID' => $filter['USER_ID'],
			)
		));
		while ($row = $res->fetch())
		{
			$return[$row['ENTITY_ID']] = $row['PREV_ENTITY_ID'];
		}
		return $return;
	}

	/**
	 * Delete all sorts for entity.
	 * @param int $entityId Entity id.
	 * @param string $entityType Entity type (LEAD, DEAL, QUOTE, INVOICE).
	 * @return void
	 */
	public static function clearEntity($entityId, $entityType)
	{
		$entityType = \CCrmOwnerType::ResolveID($entityType);
		$res = self::getList(array(
			'select' => array(
				'ID'
			),
			'filter' => array(
				'ENTITY_TYPE_ID' => $entityType,
				'ENTITY_ID' => $entityId
			)
		));
		while ($row = $res->fetch())
		{
			self::delete($row['ID']);
		}
	}

	/**
	 * Delete all sorts for user.
	 * @param int $userId User id.
	 * @return void
	 */
	public static function clearUser($userId)
	{
		$res = self::getList(array(
			'select' => array(
				'ID'
			),
			'filter' => array(
				'USER_ID' => $userId
			)
		));
		while ($row = $res->fetch())
		{
			self::delete($row['ID']);
		}
	}
}