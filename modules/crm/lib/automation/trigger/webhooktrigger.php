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
			&& Rest\Engine\Access::isFeatureEnabled()
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

	private static function getPassword($userId = null): ?string
	{
		if ($userId === null)
		{
			$user = Main\Engine\CurrentUser::get();
			$userId = $user->getId();
		}

		$userId = (int)$userId;
		$passwordId = (int)\CUserOptions::GetOption(
			'crm',
			'webhook_trigger_password_id',
			0,
			$userId
		);

		$passwordService = Rest\Service\ServiceContainer::getInstance()->getAPAuthPasswordService();
		$password = $passwordId > 0 ? $passwordService->getPasswordById($passwordId) : null;

		return $password && $password->getUserId() === $userId ? $password->getPasswordString() : null;
	}

	public static function touchPassword($userId): ?string
	{
		$password = static::getPassword($userId);
		if ($password)
		{
			return $password;
		}

		$passwordService = Rest\Service\ServiceContainer::getInstance()->getAPAuthPasswordService();
		$createPasswordDto = new Rest\Dto\APAuth\CreatePasswordDto(
			userId: $userId,
			type: Rest\Enum\APAuth\PasswordType::System,
			title: Loc::getMessage('CRM_AUTOMATION_TRIGGER_PASSWORD_TITLE'),
			comment: Loc::getMessage('CRM_AUTOMATION_TRIGGER_PASSWORD_COMMENT'),
			permissions: ['crm'],
		);
		$password = $passwordService->create($createPasswordDto);

		if ($password?->getId() > 0)
		{
			\CUserOptions::SetOption(
				'crm',
				'webhook_trigger_password_id',
				$password->getId(),
				false,
				$userId
			);

			return $password->getPasswordString();
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
