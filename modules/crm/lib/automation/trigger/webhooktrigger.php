<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rest;

class WebHookTrigger extends BaseTrigger
{
	public static function isEnabled()
	{
		return (
			Main\Loader::includeModule('rest')
			&& Rest\Engine\Access::isAvailable()
		);
	}

	public static function getCode()
	{
		return 'WEBHOOK';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_WEBHOOK_NAME_1');
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
		return \CCrmAuthorizationHelper::CheckUpdatePermission(
			\CCrmOwnerType::ResolveName($entityTypeId),
			$entityId
		);
	}

	private static function getPassword($userId = null)
	{
		if ($userId === null)
		{
			$user = Main\Engine\CurrentUser::get();
			$userId = $user->getId();
		}

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

	protected static function getPropertiesMap(): array
	{
		return [
			[
				'Id' => 'code',
				'Type' => '@webhook-code',
				'Name' => 'URL',
				'Copyable' => false,
				'Settings' => [
					'Handler' => sprintf(
						'%srest/{{USER_ID}}/{{PASSWORD}}/crm.automation.trigger/?target={{DOCUMENT_TYPE}}_{{ID}}',
						SITE_DIR,
					),
					'Password' => self::getPassword(),
					'PasswordLoader' => [
						'type' => 'component',
						'component' => 'bitrix:crm.automation',
						'action' => 'generateWebhookPassword',
						'mode' => 'class',
					],
				],
			],
		];
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

	public static function getGroup(): array
	{
		return ['other'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_WEBHOOK_DESCRIPTION') ?? '';
	}
}
