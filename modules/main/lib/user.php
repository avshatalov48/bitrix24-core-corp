<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Search\MapBuilder;

Loc::loadMessages(__FILE__);

class UserTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_user';
	}

	public static function getUfId()
	{
		return 'USER';
	}

	public static function getMap()
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'LOGIN' => array(
				'data_type' => 'string'
			),
			'PASSWORD' => array(
				'data_type' => 'string'
			),
			'EMAIL' => array(
				'data_type' => 'string'
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'DATE_REGISTER' => array(
				'data_type' => 'datetime'
			),
			'DATE_REG_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$helper->getDatetimeToDateFunction('%s'), 'DATE_REGISTER'
				)
			),
			'LAST_LOGIN' => array(
				'data_type' => 'datetime'
			),
			'LAST_LOGIN_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$helper->getDatetimeToDateFunction('%s'), 'LAST_LOGIN'
				)
			),
			'LAST_ACTIVITY_DATE' => array(
				'data_type' => 'datetime'
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'EXTERNAL_AUTH_ID' => array(
				'data_type' => 'string'
			),
			'XML_ID' => array(
				'data_type' => 'string'
			),
			'BX_USER_ID' => array(
				'data_type' => 'string'
			),
			'CONFIRM_CODE' => array(
				'data_type' => 'string'
			),
			'LID' => array(
				'data_type' => 'string'
			),
			'LANGUAGE_ID' => array(
				'data_type' => 'string'
			),
			'TIME_ZONE_OFFSET' => array(
				'data_type' => 'integer'
			),
			'PERSONAL_PROFESSION' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PHONE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_MOBILE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_WWW' => array(
				'data_type' => 'string'
			),
			'PERSONAL_ICQ' => array(
				'data_type' => 'string'
			),
			'PERSONAL_FAX' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PAGER' => array(
				'data_type' => 'string'
			),
			'PERSONAL_STREET' => array(
				'data_type' => 'text'
			),
			'PERSONAL_MAILBOX' => array(
				'data_type' => 'string'
			),
			'PERSONAL_CITY' => array(
				'data_type' => 'string'
			),
			'PERSONAL_STATE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_ZIP' => array(
				'data_type' => 'string'
			),
			'PERSONAL_COUNTRY' => array(
				'data_type' => 'string'
			),
			'PERSONAL_BIRTHDAY' => array(
				'data_type' => 'date'
			),
			'PERSONAL_GENDER' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PHOTO' => array(
				'data_type' => 'integer'
			),
			'PERSONAL_NOTES' => array(
				'data_type' => 'text'
			),
			'WORK_COMPANY' => array(
				'data_type' => 'string'
			),
			'WORK_DEPARTMENT' => array(
				'data_type' => 'string'
			),
			'WORK_PHONE' => array(
				'data_type' => 'string'
			),
			'WORK_POSITION' => array(
				'data_type' => 'string'
			),
			'WORK_WWW' => array(
				'data_type' => 'string'
			),
			'WORK_FAX' => array(
				'data_type' => 'string'
			),
			'WORK_PAGER' => array(
				'data_type' => 'string'
			),
			'WORK_STREET' => array(
				'data_type' => 'text'
			),
			'WORK_MAILBOX' => array(
				'data_type' => 'string'
			),
			'WORK_CITY' => array(
				'data_type' => 'string'
			),
			'WORK_STATE' => array(
				'data_type' => 'string'
			),
			'WORK_ZIP' => array(
				'data_type' => 'string'
			),
			'WORK_COUNTRY' => array(
				'data_type' => 'string'
			),
			'WORK_PROFILE' => array(
				'data_type' => 'text'
			),
			'WORK_LOGO' => array(
				'data_type' => 'integer'
			),
			'WORK_NOTES' => array(
				'data_type' => 'text'
			),
			'ADMIN_NOTES' => array(
				'data_type' => 'text'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("%s","' '", "UPPER(".$helper->getSubstrFunction("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'IS_ONLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'expression' => array(
					'CASE WHEN %s > '.$helper->addSecondsToDateTime('(-'.self::getSecondsForLimitOnline().')').' THEN \'Y\' ELSE \'N\' END',
					'LAST_ACTIVITY_DATE',
				)
			),
			'IS_REAL_USER' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'expression' => array(
					'CASE WHEN %s IN ("'.join('", "', static::getExternalUserTypes()).'") THEN \'N\' ELSE \'Y\' END',
					'EXTERNAL_AUTH_ID',
				)
			),
			'INDEX' => array(
				'data_type' => 'Bitrix\Main\UserIndex',
				'reference' => array('=this.ID' => 'ref.USER_ID'),
				'join_type' => 'INNER',
			),
			'INDEX_SELECTOR' => array(
				'data_type' => 'Bitrix\Main\UserIndexSelector',
				'reference' => array('=this.ID' => 'ref.USER_ID'),
				'join_type' => 'INNER',
			),
			(new Entity\ReferenceField(
				'COUNTER',
				\Bitrix\Main\UserCounterTable::class,
				Entity\Query\Join::on('this.ID', 'ref.USER_ID')->where('ref.CODE', 'tasks_effective')
			)),
			(new Reference(
				'PHONE_AUTH',
				UserPhoneAuthTable::class,
				Join::on('this.ID', 'ref.USER_ID')
			)),
			(new OneToMany('GROUPS', UserGroupTable::class, 'USER'))
				->configureJoinType(Entity\Query\Join::TYPE_INNER),
		);
	}

	public static function getSecondsForLimitOnline()
	{
		$seconds = intval(ini_get("session.gc_maxlifetime"));

		if ($seconds == 0)
		{
			$seconds = 1440;
		}
		else if ($seconds < 120)
		{
			$seconds = 120;
		}

		return intval($seconds);
	}

	public static function getActiveUsersCount()
	{
		if (ModuleManager::isModuleInstalled("intranet"))
		{
			$sql = "SELECT COUNT(U.ID) ".
				"FROM b_user U ".
				"WHERE U.ACTIVE = 'Y' ".
				"   AND U.LAST_LOGIN IS NOT NULL ".
				"   AND EXISTS(".
				"       SELECT 'x' ".
				"       FROM b_utm_user UF, b_user_field F ".
				"       WHERE F.ENTITY_ID = 'USER' ".
				"           AND F.FIELD_NAME = 'UF_DEPARTMENT' ".
				"           AND UF.FIELD_ID = F.ID ".
				"           AND UF.VALUE_ID = U.ID ".
				"           AND UF.VALUE_INT IS NOT NULL ".
				"           AND UF.VALUE_INT <> 0".
				"   )";
		}
		else
		{
			$sql = "SELECT COUNT(ID) ".
				"FROM b_user ".
				"WHERE ACTIVE = 'Y' ".
				"   AND LAST_LOGIN IS NOT NULL";
		}

		$connection = Application::getConnection();
		return $connection->queryScalar($sql);
	}

	public static function getUserGroupIds($userId)
	{
		$groups = array();

		// anonymous groups
		$result = GroupTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=ANONYMOUS' => 'Y',
				'=ACTIVE' => 'Y'
			)
		));

		while ($row = $result->fetch())
		{
			$groups[] = $row['ID'];
		}

		if(!in_array(2, $groups))
			$groups[] = 2;

		if($userId > 0)
		{
			// private groups
			$nowTimeExpression = new SqlExpression(
				static::getEntity()->getConnection()->getSqlHelper()->getCurrentDateTimeFunction()
			);

			$result = GroupTable::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'=UserGroup:GROUP.USER_ID' => $userId,
					'=ACTIVE' => 'Y',
					array(
						'LOGIC' => 'OR',
						'=UserGroup:GROUP.DATE_ACTIVE_FROM' => null,
						'<=UserGroup:GROUP.DATE_ACTIVE_FROM' => $nowTimeExpression,
					),
					array(
						'LOGIC' => 'OR',
						'=UserGroup:GROUP.DATE_ACTIVE_TO' => null,
						'>=UserGroup:GROUP.DATE_ACTIVE_TO' => $nowTimeExpression,
					),
					array(
						'LOGIC' => 'OR',
						'!=ANONYMOUS' => 'Y',
						'=ANONYMOUS' => null
					)
				)
			));

			while ($row = $result->fetch())
			{
				$groups[] = $row['ID'];
			}
		}

		sort($groups);

		return $groups;
	}

	public static function getExternalUserTypes()
	{
		static $types = array("bot", "email", "controller", "replica", "imconnector", "sale", "saleanonymous", "shop");
		return $types;
	}

	private static function getUserSelectorContentFields()
	{
		static $cache = null;

		if ($cache === null)
		{
			$result = Option::get('main', 'user_selector_search_fields', '');
			if (empty($result))
			{
				$result = [];
			}
			else
			{
				$result = unserialize($result);
			}

			if (!is_array($result))
			{
				$result = [];
			}

			$result = array_intersect(array_keys(self::getEntity()->getFields()), $result);
			$result = array_merge($result, [ 'NAME', 'LAST_NAME'] );

			$cache = $result;
		}
		else
		{
			$result = $cache;
		}

		return $result;
	}

	public static function indexRecord($id)
	{
		$id = intval($id);
		if($id == 0)
		{
			return false;
		}

		$intranetInstalled = ModuleManager::isModuleInstalled('intranet');

		$select = [
			'ID',
			'NAME',
			'SECOND_NAME',
			'LAST_NAME',
			'WORK_POSITION',
			'PERSONAL_PROFESSION',
			'PERSONAL_WWW',
			'LOGIN',
			'EMAIL',
			'PERSONAL_MOBILE',
			'PERSONAL_PHONE',
			'PERSONAL_CITY',
			'PERSONAL_STREET',
			'PERSONAL_STATE',
			'PERSONAL_COUNTRY',
			'PERSONAL_ZIP',
			'PERSONAL_MAILBOX',
			'WORK_CITY',
			'WORK_STREET',
			'WORK_STATE',
			'WORK_ZIP',
			'WORK_COUNTRY',
			'WORK_MAILBOX',
			'WORK_PHONE',
			'WORK_COMPANY'
		];

		if ($intranetInstalled)
		{
			$select[] = 'UF_DEPARTMENT';
		}

		$userSelectorContentFields = self::getUserSelectorContentFields();

		$record = parent::getList(array(
			'select' => array_unique(array_merge($select, $userSelectorContentFields)),
			'filter' => array('=ID' => $id)
		))->fetch();

		if(!is_array($record))
		{
			return false;
		}

		$record['UF_DEPARTMENT_NAMES'] = array();
		if ($intranetInstalled)
		{
			$departmentNames = UserUtils::getDepartmentNames($record['UF_DEPARTMENT']);
			foreach ($departmentNames as $departmentName)
			{
				$record['UF_DEPARTMENT_NAMES'][] = $departmentName['NAME'];
			}
		}

		$departmentName = isset($record['UF_DEPARTMENT_NAMES'][0])? $record['UF_DEPARTMENT_NAMES'][0]: '';
		$searchDepartmentContent = implode(' ', $record['UF_DEPARTMENT_NAMES']);

		UserIndexTable::merge(array(
			'USER_ID' => $id,
			'NAME' => (string)$record['NAME'],
			'SECOND_NAME' => (string)$record['SECOND_NAME'],
			'LAST_NAME' => (string)$record['LAST_NAME'],
			'WORK_POSITION' => (string)$record['WORK_POSITION'],
			'UF_DEPARTMENT_NAME' => (string)$departmentName,
			'SEARCH_USER_CONTENT' => self::generateSearchUserContent($record),
			'SEARCH_ADMIN_CONTENT' => self::generateSearchAdminContent($record),
			'SEARCH_DEPARTMENT_CONTENT' => MapBuilder::create()->addText($searchDepartmentContent)->build()
		));

		self::indexRecordSelector($id, $record);

		return true;
	}

	public static function indexRecordSelector($id, array $record = [])
	{
		$id = intval($id);
		if($id == 0)
		{
			return false;
		}

		if (empty($record))
		{
			$select = array('ID', 'NAME', 'LAST_NAME');
			$userSelectorContentFields = self::getUserSelectorContentFields();

			$record = parent::getList(array(
				'select' => array_unique(array_merge($select, $userSelectorContentFields)),
				'filter' => array('=ID' => $id)
			))->fetch();
		}

		if(!is_array($record))
		{
			return false;
		}

		UserIndexSelectorTable::merge(array(
			'USER_ID' => $id,
			'SEARCH_SELECTOR_CONTENT' => self::generateSearchSelectorContent($record),
		));

		return true;
	}

	public static function deleteIndexRecord($id)
	{
		UserIndexTable::delete($id);
		UserIndexSelectorTable::delete($id);
	}

	private static function generateSearchUserContent(array $fields)
	{
		$result = MapBuilder::create()
			->addInteger($fields['ID'])
			->addText($fields['NAME'])
			->addText($fields['SECOND_NAME'])
			->addText($fields['LAST_NAME'])
			->addText($fields['WORK_POSITION'])
			->addText(implode(' ', $fields['UF_DEPARTMENT_NAMES']))
			->build();

		return $result;
	}

	private static function generateSearchAdminContent(array $fields)
	{
		$personalCountry = (
			isset($fields['PERSONAL_COUNTRY'])
			&& intval($fields['PERSONAL_COUNTRY'])
				? \Bitrix\Main\UserUtils::getCountryValue([
					'VALUE' => intval($fields['PERSONAL_COUNTRY'])
				])
				: ''
		);
		$workCountry = (
			isset($fields['WORK_COUNTRY'])
			&& intval($fields['WORK_COUNTRY'])
				? \Bitrix\Main\UserUtils::getCountryValue([
					'VALUE' => intval($fields['WORK_COUNTRY'])
				])
				: ''
		);
		$department = (
			isset($fields['UF_DEPARTMENT_NAMES'])
			&& is_array($fields['UF_DEPARTMENT_NAMES'])
				? implode(' ', $fields['UF_DEPARTMENT_NAMES'])
				: ''
		);

		$ufContent = \Bitrix\Main\UserUtils::getUFContent($fields['ID']);
		$tagsContent = \Bitrix\Main\UserUtils::getTagsContent($fields['ID']);

		$result = MapBuilder::create()
			->addInteger($fields['ID'])
			->addText($fields['NAME'])
			->addText($fields['SECOND_NAME'])
			->addText($fields['LAST_NAME'])
			->addEmail($fields['EMAIL'])
			->addText($fields['WORK_POSITION'])
			->addText($fields['PERSONAL_PROFESSION'])
			->addText($fields['PERSONAL_WWW'])
			->addText($fields['LOGIN'])
			->addPhone($fields['PERSONAL_MOBILE'])
			->addPhone($fields['PERSONAL_PHONE'])
			->addText($fields['PERSONAL_CITY'])
			->addText($fields['PERSONAL_STREET'])
			->addText($fields['PERSONAL_STATE'])
			->addText($fields['PERSONAL_ZIP'])
			->addText($fields['PERSONAL_MAILBOX'])
			->addText($fields['WORK_CITY'])
			->addText($fields['WORK_STREET'])
			->addText($fields['WORK_STATE'])
			->addText($fields['WORK_ZIP'])
			->addText($fields['WORK_MAILBOX'])
			->addPhone($fields['WORK_PHONE'])
			->addText($fields['WORK_COMPANY'])
			->addText($personalCountry)
			->addText($workCountry)
			->addText($department)
			->addText($ufContent)
			->addText($tagsContent)
			->build();

		return $result;
	}

	private static function generateSearchSelectorContent(array $userFields)
	{
		static $fieldsList = null;

		$userSelectorContentFields = self::getUserSelectorContentFields();

		if ($fieldsList === null)
		{
			$fieldsList = self::getEntity()->getFields();
		}

		$result = MapBuilder::create();
		foreach($userSelectorContentFields as $fieldCode)
		{
			if (
				isset($fieldsList[$fieldCode])
				&& isset($userFields[$fieldCode])
			)
			{
				if ($fieldsList[$fieldCode] instanceof \Bitrix\Main\ORM\Fields\IntegerField)
				{
					$result = $result->addInteger($userFields[$fieldCode]);
				}
				elseif($fieldsList[$fieldCode] instanceof \Bitrix\Main\ORM\Fields\StringField)
				{
					$value = $userFields[$fieldCode];
					if (in_array($fieldCode, ['NAME', 'LAST_NAME']))
					{
						$value = str_replace(['(', ')'], '', $value);
						$value = str_replace('-', ' ', $value);
					}
					$result = $result->addText($value);
				}
				elseif ($fieldsList[$fieldCode] instanceof \Bitrix\Main\ORM\Fields\UserTypeField)
				{
					if ($fieldsList[$fieldCode]->isMultiple())
					{
						foreach($fieldsList[$fieldCode] as $value)
						{
							if ($fieldsList[$fieldCode]->getValueType() == 'Bitrix\Main\ORM\Fields\IntegerField')
							{
								$result = $result->addInteger($value);
							}
							elseif ($fieldsList[$fieldCode]->getValueType() == 'Bitrix\Main\ORM\Fields\StringField')
							{
								$result = $result->addText($value);
							}
						}
					}
					else
					{
						if ($fieldsList[$fieldCode]->getValueType() == 'Bitrix\Main\ORM\Fields\IntegerField')
						{
							$result = $result->addInteger($userFields[$fieldCode]);
						}
						elseif ($fieldsList[$fieldCode]->getValueType() == 'Bitrix\Main\ORM\Fields\StringField')
						{
							$result = $result->addText($userFields[$fieldCode]);
						}
					}
				}
			}
		}

		return $result->build();
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use CUser class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CUser class.");
	}

	public static function delete($primary)
	{
		throw new NotImplementedException("Use CUser class.");
	}

	public static function onAfterAdd(Entity\Event $event)
	{
		$id = $event->getParameter("id");
		static::indexRecord($id);
		return new Entity\EventResult();
	}

	public static function onAfterUpdate(Entity\Event $event)
	{
		$primary = $event->getParameter("id");
		$id = $primary["ID"];
		static::indexRecord($id);
		return new Entity\EventResult();
	}

	public static function onAfterDelete(Entity\Event $event)
	{
		$primary = $event->getParameter("id");
		$id = $primary["ID"];
		static::deleteIndexRecord($id);
		return new Entity\EventResult();
	}

	public static function postInitialize(\Bitrix\Main\ORM\Entity $entity)
	{
		// add uts inner reference

		if ($entity->hasField('UTS_OBJECT'))
		{
			/** @var Reference $leftUtsRef */
			$leftUtsRef = $entity->getField('UTS_OBJECT');

			$entity->addField((
				new Reference(
					'UTS_OBJECT_INNER', $leftUtsRef->getRefEntity(), $leftUtsRef->getReference()
				))
				->configureJoinType('inner')
			);
		}
	}
}
