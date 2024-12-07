<?php

namespace Bitrix\Intranet\ActionFilter;

use Bitrix\Main;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Intranet;

class InviteExtranetAccessControl extends Engine\ActionFilter\Base
{
	private array $socialNetworkGroupIds;

	public function __construct(?array $socialNetworkGroupIds = null)
	{
		parent::__construct();

		$this->socialNetworkGroupIds = is_array($socialNetworkGroupIds) ? $socialNetworkGroupIds : [];
	}

	public function onBeforeAction(Event $event)
	{
		if (Main\Loader::includeModule('extranet') === false || \CExtranet::GetExtranetSiteID() === false)
		{
			$this->addError(new Error(
				Main\Localization\Loc::getMessage('INTRANET_INVITE_ACCESS_CONTROL_EXTRANET_IS_NOT_INSTALLED')
			));
		}
		else if (
			Main\Loader::includeModule('bitrix24') && !Intranet\Invitation::canCurrentUserInvite()
			|| $this->checkSocialNetworkGroupExistanse() === false
		)
		{
			$this->addError(new Error(
				Main\Localization\Loc::getMessage('INTRANET_INVITE_ACCESS_CONTROL_ACCESS_DENIED')
			));
		}

		return empty($this->getErrors()) ? null
			: new EventResult(EventResult::ERROR, null, 'intranet', $this)
		;
	}

	private function checkSocialNetworkGroupExistanse(): bool
	{
		if (!Main\Loader::includeModule('socialnetwork'))
		{
			$this->addError(new Error(
				Main\Localization\Loc::getMessage('INTRANET_INVITE_ACCESS_CONTROL_SOCNET_IS_NOT_INSTALLED')
			));
		}
		else if (empty($this->socialNetworkGroupIds))
		{
			$this->addError(new Error(
				Main\Localization\Loc::getMessage('BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_INVITE')
			));
		}
		else
		{
			foreach ($this->socialNetworkGroupIds as $socialNetworkGroupId)
			{
				if (\CSocNetGroup::GetByID($socialNetworkGroupId) === false)
				{
					$this->addError(new Error(
						Main\Localization\Loc::getMessage('INTRANET_INVITE_ACCESS_CONTROL_GROUP_DOES_NOT_EXIST')
					));
					break;
				}
			}
		}

		return true;
	}
}
