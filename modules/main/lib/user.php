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
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Search\MapBuilder;

Loc::loadMessages(__FILE__);

class UserTable extends DataManager
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
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			new StringField('LOGIN'),

			(new StringField('PASSWORD'))
				->configurePrivate(),

			new StringField('EMAIL'),

			(new BooleanField('ACTIVE'))
				->configureValues('N','Y'),

			(new BooleanField('BLOCKED'))
				->configureValues('N','Y'),

			new DatetimeField('DATE_REGISTER'),

			(new ExpressionField(
				'DATE_REG_SHORT',
				$helper->getDatetimeToDateFunction('%s'),
				'DATE_REGISTER')
			)->configureValueType(DatetimeField::class),


			new DatetimeField('LAST_LOGIN'),

			(new ExpressionField(
				'LAST_LOGIN_SHORT',
				$helper->getDatetimeToDateFunction('%s'),
				'LAST_LOGIN')
			)->configureValueType(DatetimeField::class),

			new DatetimeField('LAST_ACTIVITY_DATE'),

			new DatetimeField('TIMESTAMP_X'),

			new StringField('NAME'),
			new StringField('SECOND_NAME'),
			new StringField('LAST_NAME'),
			new StringField('TITLE'),
			new StringField('EXTERNAL_AUTH_ID'),
			new StringField('XML_ID'),
			new StringField('BX_USER_ID'),
			new StringField('CONFIRM_CODE'),
			new StringField('LID'),
			new StringField('LANGUAGE_ID'),
			new StringField('TIME_ZONE'),
			new IntegerField('TIME_ZONE_OFFSET'),
			new StringField('PERSONAL_PROFESSION'),
			new StringField('PERSONAL_PHONE'),
			new StringField('PERSONAL_MOBILE'),
			new StringField('PERSONAL_WWW'),
			new StringField('PERSONAL_ICQ'),
			new StringField('PERSONAL_FAX'),
			new StringField('PERSONAL_PAGER'),
			new TextField('PERSONAL_STREET'),
			new StringField('PERSONAL_MAILBOX'),
			new StringField('PERSONAL_CITY'),
			new StringField('PERSONAL_STATE'),
			new StringField('PERSONAL_ZIP'),
			new StringField('PERSONAL_COUNTRY'),
			new DateField('PERSONAL_BIRTHDAY'),
			new StringField('PERSONAL_GENDER'),
			new IntegerField('PERSONAL_PHOTO'),
			new TextField('PERSONAL_NOTES'),
			new StringField('WORK_COMPANY'),
			new StringField('WORK_DEPARTMENT'),
			new StringField('WORK_PHONE'),
			new StringField('WORK_POSITION'),
			new StringField('WORK_WWW'),
			new StringField('WORK_FAX'),
			new StringField('WORK_PAGER'),
			new TextField('WORK_STREET'),
			new StringField('WORK_MAILBOX'),
			new StringField('WORK_CITY'),
			new StringField('WORK_STATE'),
			new StringField('WORK_ZIP'),
			new StringField('WORK_COUNTRY'),
			new TextField('WORK_PROFILE'),
			new IntegerField('WORK_LOGO'),
			new TextField('WORK_NOTES'),
			new TextField('ADMIN_NOTES'),

			new ExpressionField(
				'SHORT_NAME',
				$helper->getConcatFunction(
					"%s",
					"' '",
					"UPPER(" . $helper->getSubstrFunction("%s", 1, 1) . ")", "'.'"
				),
				['LAST_NAME', 'NAME']
			),

			(new ExpressionField(
				'IS_ONLINE',
				'CASE WHEN %s > '
					. $helper->addSecondsToDateTime('(-' . self::getSecondsForLimitOnline() . ')')
					. ' THEN \'Y\' ELSE \'N\' END',
				'LAST_ACTIVITY_DATE',
				['values' => ['N', 'Y']]
			))->configureValueType(BooleanField::class),

			(new ExpressionField(
				'IS_REAL_USER',
				'CASE WHEN %s IN ("'
					. join('", "', static::getExternalUserTypes())
					. '") THEN \'N\' ELSE \'Y\' END',
				'EXTERNAL_AUTH_ID',
				['values' => ['N', 'Y']]
			))->configureValueType(BooleanField::class),

			(new Reference(
				'INDEX',
				UserIndexTable::class,
				Join::on('this.ID', 'ref.USER_ID')
			))->configureJoinType(Join::TYPE_INNER),

			(new Reference(
				'INDEX_SELECTOR',
				UserIndexSelectorTable::class,
				Join::on('this.ID', 'ref.USER_ID')
			))->configureJoinType(Join::TYPE_INNER),

			(new Reference(
				'COUNTER',
				\Bitrix\Main\UserCounterTable::class,
				Join::on('this.ID', 'ref.USER_ID')->where('ref.CODE', 'tasks_effective')
			)),
			(new Reference(
				'PHONE_AUTH',
				UserPhoneAuthTable::class,
				Join::on('this.ID', 'ref.USER_ID')
			)),
			(new OneToMany('GROUPS', UserGroupTable::class, 'USER'))
				->configureJoinType(Join::TYPE_INNER),
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

	/**
	 * @param Type\Date|null $lastLoginDate
	 * @return int
	 */
	public static function getActiveUsersCount(Type\Date $lastLoginDate = null)
	{
		$connection = Application::getConnection();

		if ($lastLoginDate !== null)
		{
			// logged in today
			$filter = "AND U.LAST_LOGIN > ".$connection->getSqlHelper()->convertToDbDate($lastLoginDate);
		}
		else
		{
			// logged in in total
			$filter = "AND U.LAST_LOGIN IS NOT NULL";
		}

		if (ModuleManager::isModuleInstalled("intranet"))
		{
			$sql = "
				SELECT COUNT(DISTINCT U.ID)
				FROM
					b_user U
					INNER JOIN b_user_field F ON F.ENTITY_ID = 'USER' AND F.FIELD_NAME = 'UF_DEPARTMENT'
					INNER JOIN b_utm_user UF ON
						UF.FIELD_ID = F.ID
						AND UF.VALUE_ID = U.ID
						AND UF.VALUE_INT > 0
				WHERE U.ACTIVE = 'Y'
					{$filter}
			";
		}
		else
		{
			$sql = "
				SELECT COUNT(ID) 
				FROM b_user U 
				WHERE U.ACTIVE = 'Y' 
				   {$filter}
			";
		}

		return (int)$connection->queryScalar($sql);
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
		static $types = [
			'bot',
			'email',
			'__controller',
			'replica',
			'imconnector',
			'sale',
			'saleanonymous',
			'shop',
			'call',
			'document_editor',
		];

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
				$result = unserialize($result, ['allowed_classes' => false]);
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

	public static function onAfterAdd(ORM\Event $event)
	{
		$id = $event->getParameter("id");
		static::indexRecord($id);
		return new ORM\EventResult();
	}

	public static function onAfterUpdate(ORM\Event $event)
	{
		$primary = $event->getParameter("id");
		$id = $primary["ID"];
		static::indexRecord($id);
		return new ORM\EventResult();
	}

	public static function onAfterDelete(ORM\Event $event)
	{
		$primary = $event->getParameter("id");
		$id = $primary["ID"];
		static::deleteIndexRecord($id);
		return new ORM\EventResult();
	}

	public static function postInitialize(ORM\Entity $entity)
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
