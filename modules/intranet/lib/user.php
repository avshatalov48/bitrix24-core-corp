<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\UserAccessTable;

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
	}
}

class User
{
	private CurrentUser $currentUser;
	private int $userId;

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function __construct(?int $userId)
	{
		if ($userId <= 0)
		{
			throw new ArgumentOutOfRangeException('userId', 1);
		}
		$this->currentUser = CurrentUser::get();
		$this->userId = $userId;
	}

	public function isIntranet(): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		return $this->hasDepartment();
	}

	private function hasDepartment(): bool
	{
		$fields = $this->getFields();

		return isset($fields["UF_DEPARTMENT"])
			&& (
				(
					is_array($fields["UF_DEPARTMENT"])
					&& (int)$fields["UF_DEPARTMENT"][0] > 0
				)
				|| (
					!is_array($fields["UF_DEPARTMENT"])
					&& (int)$fields["UF_DEPARTMENT"] > 0
				)
			);
	}

	public function hasAccessToDepartment(): bool
	{
		$accessManager = new \CAccess;
		$accessManager->UpdateCodes(['USER_ID' => $this->userId]);

		$accessResult = UserAccessTable::query()
			->where('USER_ID', $this->userId)
			->whereLike('ACCESS_CODE', 'D%')
			->whereNotLike('ACCESS_CODE', 'DR%')
			->setLimit(1)
			->fetch();

		return !($accessResult === false);
	}

	public function isAdmin(): bool
	{
		if ($this->currentUser->getId() === $this->userId)
		{
			return (
					Loader::includeModule('bitrix24')
					&& \CBitrix24::IsPortalAdmin($this->userId)
				)
				|| $this->currentUser->isAdmin();
		}
		else
		{
			$groupIds = (new \CUser())->GetUserGroup($this->userId);

			return in_array(1, $groupIds);
		}
	}

	public function getFields(): array
	{
		$result = \CUser::GetById($this->userId)->fetch();
		return is_array($result) ? $result : [];
	}
}