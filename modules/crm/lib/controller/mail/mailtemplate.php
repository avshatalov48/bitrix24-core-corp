<?php

namespace Bitrix\Crm\Controller\Mail;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class MailTemplate extends Controller
{
	const SAVE_TEMPLATE_OPTION_NAME = 'save_last_used_mail_template';

	public function toggleSaveLastUsedTemplateAction(): bool
	{
		if(!$this->checkAccess())
		{
			return false;
		}

		if(!$this->checkSaveTemplateOption())
		{
			\CUserOptions::SetOption('crm', self::SAVE_TEMPLATE_OPTION_NAME, 'Y');
		}
		else
		{
			\CUserOptions::DeleteOption('crm',self::SAVE_TEMPLATE_OPTION_NAME);
		}

		return true;
	}

	public function getTitleListAction(int $ownerTypeId): array
	{
		if(!$this->checkAccess())
		{
			return [];
		}

		$userID = \CCrmPerms::GetCurrentUserID();

		$templates = [];
		$res = \CCrmMailTemplate::getUserAvailableTemplatesList($ownerTypeId);

		while ($item = $res->fetch())
		{
			$entityType = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
			$templates[] = [
				'id'         => $item['ID'],
				'title'      => $item['TITLE'],
				'entityType' => $entityType,
			];
		}

		return $templates;
	}

	private function checkSaveTemplateOption():bool
	{
		return \CUserOptions::GetOption('crm', self::SAVE_TEMPLATE_OPTION_NAME) === 'Y';
	}
	private function checkAccess(): bool
	{
		$userID = \CCrmPerms::GetCurrentUserID();
		if($userID < 0)
		{
			return false;
		}

		if (!\Bitrix\Main\Loader::includeModule('mail'))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_MAIL_MODULE_NOT_INSTALLED')));
			return false;
		}

		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_CRM_MODULE_NOT_INSTALLED')));
			return false;
		}

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->addError(new Error(Loc::getMessage('CRM_MAIL_CONTROLLER_PERMISSION_DENIED')));
			return false;
		}

		return true;
	}
}