<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest;

class WebHookTrigger extends BaseTrigger
{
	protected static function areDynamicTypesSupported(): bool
	{
		return false;
	}

	public static function isEnabled()
	{
		return (
			Main\Loader::includeModule('rest')
			&& (
				!class_exists(Rest\Engine\Access::class)
				|| Rest\Engine\Access::isAvailable()
			)
		);
	}

	public static function getCode()
	{
		return 'WEBHOOK';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_WEBHOOK_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['code'])
		)
		{
			return (string)$trigger['APPLY_RULES']['code'] === (string)$this->getInputData('code');
		}

		return true;
	}

	public static function canExecute($entityTypeId, $entityId)
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return \CCrmLead::CheckUpdatePermission($entityId);
		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal)
		{
			return \CCrmDeal::CheckUpdatePermission($entityId);
		}
		elseif ($entityTypeId === \CCrmOwnerType::Order)
		{
			return \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($entityId);
		}

		return false;
	}

	private static function getPassword($userId)
	{
		$result = null;

		$userId = (int)$userId;
		$passwordId = (int)\CUserOptions::GetOption(
			'crm',
			'webhook_trigger_password_id',
			0,
			$userId
		);

		if ($passwordId > 0)
		{
			$res = Rest\APAuth\PasswordTable::getList([
				'filter' => [
					'=ID' => $passwordId,
					'=USER_ID' => $userId,
				],
				'select' => ['ID', 'PASSWORD'],
			]);

			$result = $res->fetch();
		}

		return $result ? $result['PASSWORD'] : null;
	}

	public static function touchPassword($userId): ?string
	{
		$password = static::getPassword($userId);
		if ($password)
		{
			return $password;
		}

		$password = Rest\APAuth\PasswordTable::generatePassword();

		$res = Rest\APAuth\PasswordTable::add([
			'USER_ID' => $userId,
			'PASSWORD' => $password,
			'DATE_CREATE' => new Main\Type\DateTime(),
			'TITLE' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_PASSWORD_TITLE'),
			'COMMENT' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_PASSWORD_COMMENT'),
		]);

		if ($res->isSuccess())
		{
			Rest\APAuth\PermissionTable::add([
				'PASSWORD_ID' => $res->getId(),
				'PERM' => 'crm',
			]);

			\CUserOptions::SetOption(
				'crm',
				'webhook_trigger_password_id',
				$res->getId(),
				false,
				$userId
			);

			return $password;
		}

		return null;
	}

	public static function toArray()
	{
		$user = Main\Engine\CurrentUser::get();
		$result = parent::toArray();

		if (static::isEnabled())
		{
			$passwd = self::getPassword($user->getId());

			$result['HANDLER'] = sprintf(
				'%srest/%s/%s/crm.automation.trigger/?target={{DOCUMENT_TYPE}}_{{ID}}',
				SITE_DIR,
				$user->getId(),
				$passwd ?? '{{PASSWORD}}'
			);
		}

		return $result;
	}
}
