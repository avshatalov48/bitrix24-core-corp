<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main;
Use Bitrix\Main\Localization\Loc;
use Bitrix\Rest;

Loc::loadMessages(__FILE__);

class WebHookTrigger extends BaseTrigger
{
	public static function isEnabled()
	{
		return (Main\Loader::includeModule('rest'));
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
		return false;
	}

	private static function generatePassword($userId)
	{
		$result = null;

		$userId = (int)$userId;
		$passwordId = (int)\CUserOptions::GetOption('crm', 'webhook_trigger_password_id', 0, $userId);

		if ($passwordId > 0)
		{
			$res = Rest\APAuth\PasswordTable::getList(array(
				'filter' => array(
					'=ID' => $passwordId,
					'=USER_ID' => $userId,
				),
				'select' => array('ID', 'PASSWORD')
			));

			$result = $res->fetch();
		}

		if (!$result)
		{
			$result = static::createPassword($userId);
			if ($result)
			{
				\CUserOptions::SetOption('crm', 'webhook_trigger_password_id', $result['ID'], false, $userId);
			}
		}

		return $result;
	}

	private static function createPassword($userId)
	{
		$password = Rest\APAuth\PasswordTable::generatePassword();

		$res = Rest\APAuth\PasswordTable::add(array(
			'USER_ID' => $userId,
			'PASSWORD' => $password,
			'DATE_CREATE' => new Main\Type\DateTime(),
			'TITLE' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_PASSWORD_TITLE'),
			'COMMENT' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_PASSWORD_COMMENT'),
		));

		if($res->isSuccess())
		{
			Rest\APAuth\PermissionTable::add(array(
				'PASSWORD_ID' => $res->getId(),
				'PERM' => 'crm',
			));

			return array('ID' => $res->getId(), 'PASSWORD' => $password);
		}

		return false;
	}

	public static function toArray()
	{
		//TODO: ref
		global $USER;
		$userId = isset($USER) && is_object($USER) ? (int)$USER->getId() : 0;

		$result = parent::toArray();

		if (static::isEnabled())
		{
			$passwd = self::generatePassword($userId);
			if ($passwd)
			{
				$result['HANDLER'] = SITE_DIR.'rest/'.$userId.'/'.$passwd['PASSWORD'].'/crm.automation.trigger/?target={{DOCUMENT_TYPE}}_{{ID}}';
			}
		}

		return $result;
	}
}