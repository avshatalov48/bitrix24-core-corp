<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Query\Query;

/**
 * Class UserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_User_Query query()
 * @method static EO_User_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_User_Result getById($id)
 * @method static EO_User_Result getList(array $parameters = array())
 * @method static EO_User_Entity getEntity()
 * @method static \Bitrix\Intranet\EO_User createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\EO_User_Collection createCollection()
 * @method static \Bitrix\Intranet\EO_User wakeUpObject($row)
 * @method static \Bitrix\Intranet\EO_User_Collection wakeUpCollection($rows)
 */
class UserTable extends \Bitrix\Main\UserTable
{
	public static function postInitialize(\Bitrix\Main\ORM\Entity $entity)
	{
		parent::postInitialize($entity);

		// add intranet user type expression
		$conditionList = [];
		$externalUserTypesUsed = [];

		if (ModuleManager::isModuleInstalled('sale'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s IN ('sale', 'saleanonymous', 'shop') THEN 'sale'"
			];
			$externalUserTypesUsed[] = 'sale';
			$externalUserTypesUsed[] = 'saleanonymous';
			$externalUserTypesUsed[] = 'shop';
		}
		if (ModuleManager::isModuleInstalled('imconnector'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'imconnector' THEN 'imconnector'"
			];
			$externalUserTypesUsed[] = 'imconnector';
		}
		if (ModuleManager::isModuleInstalled('im'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'bot' THEN 'bot'"
			];
			$externalUserTypesUsed[] = 'bot';
		}
		if (ModuleManager::isModuleInstalled('mail'))
		{
			$conditionList[] = [
				'PATTERN' => 'EXTERNAL_AUTH_ID',
				'VALUE' => "WHEN %s = 'email' THEN 'email'"
			];
			$externalUserTypesUsed[] = 'email';
		}

		$externalUserTypes = \Bitrix\Main\UserTable::getExternalUserTypes();
		$externalUserTypesAdditional = array_diff($externalUserTypes, $externalUserTypesUsed);
		if (!empty($externalUserTypesAdditional))
		{
			$sqlHelper = \Bitrix\Main\Application::getInstance()->getConnection()->getSqlHelper();
			foreach($externalUserTypesAdditional as $externalAuthId)
			{
				$value = $sqlHelper->convertToDbText($externalAuthId);
				$conditionList[] = [
					'PATTERN' => 'EXTERNAL_AUTH_ID',
					'VALUE' => "WHEN %s = ".$value." THEN ".$value.""
				];
			}
		}

		// duplicate for inner join
		$conditionListInner = $conditionList;

		$extranetUserType = (
			ModuleManager::isModuleInstalled('extranet')
				? 'extranet'
				: 'shop'
		);

		$serializedValue = serialize([]);

		$conditionList[] = [
			'PATTERN' => 'UF_DEPARTMENT',
			'VALUE' => "WHEN %s = '".$serializedValue."' THEN '".$extranetUserType."'"
		];
		$conditionList[] = [
			'PATTERN' => 'UF_DEPARTMENT',
			'VALUE' => "WHEN %s IS NULL THEN '".$extranetUserType."'"
		];
		$conditionListInner[] = [
			'PATTERN' => 'UTS_OBJECT_INNER.UF_DEPARTMENT',
			'VALUE' => "WHEN %s = '".$serializedValue."' THEN '".$extranetUserType."'"
		];
		$conditionListInner[] = [
			'PATTERN' => 'UTS_OBJECT_INNER.UF_DEPARTMENT',
			'VALUE' => "WHEN %s IS NULL THEN '".$extranetUserType."'"
		];

		// add USER_TYPE with left join
		$condition = "CASE ";
		$patternList = [];

		foreach($conditionList as $conditionFields)
		{
			$condition .= ' '.$conditionFields['VALUE'].' ';
			$patternList[] = $conditionFields['PATTERN'];
		}
		$condition .= "ELSE 'employee' END";

		$entity->addField(new ExpressionField('USER_TYPE',
			$condition,
			$patternList
		));

		if (Loader::includeModule('socialnetwork'))
		{
			$entity->addField(new \Bitrix\Main\ORM\Fields\Relations\OneToMany('TAGS', \Bitrix\Socialnetwork\UserTagTable::class, 'USER'));
		}

		// add USER_TYPE with inner join
		$condition = "CASE ";
		$patternList = [];

		foreach($conditionListInner as $conditionFields)
		{
			$condition .= ' '.$conditionFields['VALUE'].' ';
			$patternList[] = $conditionFields['PATTERN'];
		}
		$condition .= "ELSE 'employee' END";

		$entity->addField(new ExpressionField('USER_TYPE_INNER',
			$condition,
			$patternList
		));

		// add other fields
		$entity->addField(new ExpressionField('USER_TYPE_IS_EMPLOYEE',
			"CASE WHEN %s = 'employee' THEN 1 ELSE 0 END",
			'USER_TYPE_INNER'
		));

		$entity->addField(
			new \Bitrix\Main\ORM\Fields\Relations\Reference(
				'INVITATION',
				\Bitrix\Intranet\Internals\InvitationTable::class,
				\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.USER_ID')
			)
		);

		$entity->addField(new DatetimeField('CHECKWORD_TIME'));

		$entity->addField(new ExpressionField(
			'INVITED_SORT',
			"CASE WHEN %s = 'Y' AND %s <> '' THEN 0 ELSE 1 END",
			['ACTIVE', 'CONFIRM_CODE']
		));

		$entity->addField(new ExpressionField(
			'WAITING_CONFIRMATION_SORT',
			"CASE WHEN %s = 'N' AND %s <> '' THEN 0 ELSE 1 END",
			['ACTIVE', 'CONFIRM_CODE']
		));

		$entity->addField(new ExpressionField(
			'INVITATION_DATE_SORT',
			"CASE WHEN %s <> '' THEN %s ELSE NULL END",
			['CONFIRM_CODE', 'DATE_REGISTER'],
		));

		$entity->addField(
			(new \Bitrix\Main\ORM\Fields\Relations\Reference(
			'UG',
			\Bitrix\Socialnetwork\UserToGroupTable::class,
			\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.USER_ID'))
			)->configureJoinType(\Bitrix\Main\ORM\Query\Join::TYPE_INNER)
		);


		$entity->addField(new ExpressionField(
			'PERSONAL_MOBILE_FORMATTED',
			'%s',
			['PERSONAL_MOBILE'],
			[
				'data_type' => '\Bitrix\Main\ORM\Fields\StringField',
				'fetch_data_modification' => function () {
					return [
						function ($value, $query, $data, $alias) {
							if ($value) {
								$parsedPhoneNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($value);
								$value = $parsedPhoneNumber->isValid()
									? $parsedPhoneNumber->format(\Bitrix\Main\PhoneNumber\Format::E164)
									: $value;
							}
							return $value;
						}
					];
				}
			]
		));

		if (Loader::includeModule('extranet'))
		{
			$entity->addField(
				(new \Bitrix\Main\ORM\Fields\Relations\Reference(
					'EXTRANET',
					\Bitrix\Extranet\Model\ExtranetUserTable::class,
					\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.USER_ID'))
				)->configureJoinType(\Bitrix\Main\ORM\Query\Join::TYPE_LEFT)
			);

			// remove this after b_extranet_user migration
			$entity->addField(
				(new \Bitrix\Main\ORM\Fields\Relations\Reference(
					'EXTRANET_GROUP',
					\Bitrix\Main\UserGroupTable::class,
					\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.USER_ID')->where('ref.GROUP_ID', \CExtranet::getExtranetUserGroupId())
				))->configureJoinType(\Bitrix\Main\ORM\Query\Join::TYPE_LEFT),
			);
		}
	}

	public static function createInvitedQuery(): Query
	{
		return static::query()->addFilter('!CONFIRM_CODE', false)->where('IS_REAL_USER', true);
	}
}
