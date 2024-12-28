<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Collab\Collab;

class InviteMessageFactory
{
	public function __construct(
		private string $message,
		private ?Collab $collab = null,
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

		if ($this->collab)
		{
			$messageId = \CIntranetInviteDialog::getMessageId("COLLAB_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			$eventName = 'COLLAB_INVITATION';
			$params = [
				"USER_ID" => $user->getId(),
				"USER_ID_FROM" => CurrentUser::get()->getId(),
				"CHECKWORD" => $user->getConfirmCode(),
				"EMAIL" => $emailTo,
				"USER_TEXT" => $this->message,
				"COLLAB_NAME" => $this->collab->getName(),
				"ACTIVE_USER" => $user->getInviteStatus() === InvitationStatus::ACTIVE,
				'COLLAB_INVITATION_SUBJECT' => $this->createCollabSubject()
			];
		}
		elseif ($bExtranet)
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

	private function createCollabSubject()
	{
		Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/components/bitrix/intranet.template.mail/templates/.default/collab.php');

		if (Loader::includeModule('bitrix24') && \CBitrix24::isLicenseNeverPayed())
		{
			return Loc::getMessage('INTRANET_INVITATION_COLLAB_TITLE');
		}

		$formattedName = \CUser::FormatName('#NAME# #LAST_NAME#', [
			'NAME' => CurrentUser::get()->getFirstName(),
			'LAST_NAME' => CurrentUser::get()->getLastName(),
			'SECOND_NAME' => CurrentUser::get()->getSecondName(),
			'LOGIN' => CurrentUser::get()->getLogin()
		], true);

		return $formattedName.' '.Loc::getMessage('INTRANET_INVITATION_COLLAB_INVITE_YOU').' '.$this->collab->getName();
	}
}