<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Bitrix24\Integration\Network\ProfileService;
use Bitrix\Intranet\Contract\SendableContract;
use Bitrix\Intranet\Entity\User;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

class PhoneMessage implements SendableContract
{
	private bool $canSendInvite;

	public function __construct(
		private User $user,
		private bool $checkPolicies
	)
	{
		$this->canSendInvite = !(
			Loader::includeModule('bitrix24')
			&& !\CBitrix24::IsNfrLicense()
			&& (
				!\CBitrix24::IsLicensePaid()
				|| \CBitrix24::IsDemoLicense()
			)
		);
	}

	public function send(): void
	{
		if (
			Loader::includeModule('bitrix24')
			&& (
				!$this->checkPolicies
				|| $this->canSendInvite
			)
		)
		{
			$result = ProfileService::getInstance()
				->reInviteUserByPhone($this->user->getId())
			;
			if (!$result->isSuccess())
			{
				throw new SystemException('Error sending SMS: '.implode(', ', $result->getErrorMessages()));
			}
		}
	}

	public function sendImmediately(): void
	{
		$this->send();
	}
}