<?php

namespace Bitrix\Intranet\Internal\Factory\Message;

use Bitrix\Bitrix24\Integration\Network\Invitation;
use Bitrix\Intranet\Contract\SendableContract;
use Bitrix\Intranet\Contract\Strategy\InvitationMessageFactoryContract;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Service\EmailMessage;
use Bitrix\Intranet\Service\PhoneMessage;
use Bitrix\Main\Loader;

class ExtranetInvitationMessageFactory implements InvitationMessageFactoryContract
{
	public function __construct(
		private readonly User $user,
	)
	{}

	public function createEmailEvent(): EmailMessage
	{
		$isCloud = Loader::includeModule('bitrix24');

		if ($isCloud && $this->user->getId() !== null)
		{
			$networkEmail = (new Invitation())->getEmailByUserId($this->user->getId());
			$emailTo = $networkEmail ?? $this->user->getEmail();
		}
		else
		{
			$emailTo = $this->user->getEmail();
		}

		$siteId = \CExtranet::GetExtranetSiteID();
		$messageId = \CIntranetInviteDialog::getMessageId("EXTRANET_INVITATION", $siteId, LANGUAGE_ID);
		$eventName = 'EXTRANET_INVITATION';
		$params = [
			"USER_ID" => $this->user->getId(),
			"USER_ID_FROM" => CurrentUser::get()->getId(),
			"CHECKWORD" => $this->user->getConfirmCode(),
			"EMAIL" => $emailTo,
			"USER_TEXT" => \CIntranetInviteDialog::getInviteMessageText(),
		];

		return new EmailMessage(
			$eventName,
			$siteId,
			$params,
			$messageId,
		);
	}

	public function createSmsEvent(): SendableContract
	{
		return new PhoneMessage($this->user, true);
	}
}