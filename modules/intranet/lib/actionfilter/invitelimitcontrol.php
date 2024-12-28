<?php

namespace Bitrix\Intranet\ActionFilter;

use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Bitrix24;

class InviteLimitControl extends Main\Engine\ActionFilter\Base
{
	public function onBeforeAction(Main\Event $event)
	{
		if (Main\Loader::includeModule("bitrix24"))
		{
			if (
				\Bitrix\Bitrix24\Service\PortalSettings::getInstance()
					->getEmailConfirmationRequirements()
					->isRequiredByType(Bitrix24\Portal\Settings\EmailConfirmationRequirements\Type::INVITE_USERS)
			)
			{
				$this->addError(new Main\Error(
					Main\Localization\Loc::getMessage('INTRANET_INVITE_LIMIT_CONTROL_CREATORS_EMAIL_IS_NOT_CONFIRMED'),
				));
			}
			else
			{
				$license = Bitrix24\License::getCurrent();
				$licensePrefix = \CBitrix24::getLicensePrefix();
				if (
					in_array($license->getCode(), \CBitrix24::BASE_EDITIONS)
					&& in_array($licensePrefix, ['cn', 'en', 'vn', 'jp'])
					&& Intranet\Internals\InvitationTable::getCount(['>=DATE_CREATE' => new Main\Type\Date]) >= 10
				)
				{
					$this->addError(
						new Main\Error(
							Main\Localization\Loc::getMessage('INTRANET_INVITE_LIMIT_CONTROL_TOO_MANY_INVITATIONS')
						)
					);
				}
			}
		}

		if ($this->errorCollection->isEmpty())
		{
			return null;
		}

		return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
	}
}
