<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Main\Loader;

class InviteMessageFactory
{
	public function __construct(
		private string $message
	)
	{
	}

	public function create(User $user)
	{
		$bExtranet = $user->isExtranet();
		$isCloud = Loader::includeModule('bitrix24');

		if ($isCloud && $user->getId() !== null)
		{
			$networkEmail = (new \Bitrix\Bitrix24\Integration\Network\Invitation())->getEmailByUserId($user->getId());
			$emailTo = $networkEmail ?? $user->getEmail();
		}
		else
		{
			$emailTo = $user->getEmail();
		}

		$siteIdByDepartmentId = \CIntranetInviteDialog::getUserSiteId([
			"UF_DEPARTMENT" => $user->getDepartmetnsIds(),
			"SITE_ID" => SITE_ID
		]);

		if ($bExtranet)
		{
			$messageId = \CIntranetInviteDialog::getMessageId("EXTRANET_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			$eventName = 'EXTRANET_INVITATION';
			$params = [
				"USER_ID" => $user->getId(),
				"USER_ID_FROM" => CurrentUser::get()->getId(),
				"CHECKWORD" => $user->getConfirmCode(),
				"EMAIL" => $emailTo,
				"USER_TEXT" => $this->message
			];
		}
		elseif ($isCloud)
		{
			$messageId = \CIntranetInviteDialog::getMessageId("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			$eventName = 'BITRIX24_USER_INVITATION';
			$params = [
				"EMAIL_FROM" => CurrentUser::get()->getEmail(),
				"USER_ID_FROM" => CurrentUser::get()->getId(),
				"EMAIL_TO" => $emailTo,
				"LINK" => \CIntranetInviteDialog::getInviteLink(['ID' => $user->getId(), 'CONFIRM_CODE' => $user->getConfirmCode()], $siteIdByDepartmentId),
				"USER_TEXT" => $this->message,
			];
		}
		else
		{
			$messageId = \CIntranetInviteDialog::getMessageId("INTRANET_USER_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			$eventName = 'INTRANET_USER_INVITATION';
			$params = [
				"EMAIL_TO" => $emailTo,
				"USER_ID_FROM" => CurrentUser::get()->getId(),
				"LINK" => \CIntranetInviteDialog::getInviteLink(['ID' => $user->getId(), 'CONFIRM_CODE' => $user->getConfirmCode()], $siteIdByDepartmentId),
				"USER_TEXT" => $this->message,
			];
		}

		return new EmailMessage(
			$eventName,
			$siteIdByDepartmentId,
			$params,
			$messageId
		);
	}
}