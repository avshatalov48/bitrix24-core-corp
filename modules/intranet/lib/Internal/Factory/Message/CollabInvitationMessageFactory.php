<?php

namespace Bitrix\Intranet\Internal\Factory\Message;

use Bitrix\Bitrix24\Integration\Network\Invitation;
use Bitrix\Intranet\Contract\SendableContract;
use Bitrix\Intranet\Contract\Strategy\InvitationMessageFactoryContract;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Service\EmailMessage;
use Bitrix\Intranet\Service\PhoneMessage;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Collab\Collab;

class CollabInvitationMessageFactory implements InvitationMessageFactoryContract
{
	public function __construct(
		private readonly User $user,
		private readonly Collab $collab,
	)
	{
	}


	public function createEmailEvent(): EmailMessage
	{
		Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intranet/lib/Command/Invitation/InviteUserCollectionToGroupCommand.php');
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
		$userLang = $this->user->getLanguageId() ?? LANGUAGE_ID;

		$messageId = \CIntranetInviteDialog::getMessageId("COLLAB_INVITATION", $siteId, LANGUAGE_ID);
		$eventName = 'COLLAB_INVITATION';
		$params = [
			"USER_ID" => $this->user->getId(),
			"USER_ID_FROM" => CurrentUser::get()->getId(),
			"CHECKWORD" => $this->user->getConfirmCode(),
			"EMAIL" => $emailTo,
			"USER_TEXT" => Loc::getMessage('INTRANET_COMMAND_INVITATION_USER_COLLECTION_TO_GROUP_COLLAB_EMAIL_TEXT'),
			"COLLAB_NAME" => $this->collab->getName(),
			"ACTIVE_USER" => $this->user->getInviteStatus() === InvitationStatus::ACTIVE,
			'COLLAB_INVITATION_SUBJECT' => $this->createCollabSubject($userLang),
			'USER_LANG' => $userLang,
		];

		return new EmailMessage(
			$eventName,
			$siteId,
			$params,
			$messageId,
			null,
			$userLang,
		);
	}

	public function createSmsEvent(): SendableContract
	{
		return new PhoneMessage($this->user, true);
	}

	private function createCollabSubject(string $lang = LANGUAGE_ID): ?string
	{
		Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/bitrix/intranet.template.mail/templates/.default/collab.php', $lang);

		if (Loader::includeModule('bitrix24') && \CBitrix24::isLicenseNeverPayed())
		{
			return Loc::getMessage('INTRANET_INVITATION_COLLAB_TITLE', null, $lang);
		}

		$formattedName = \CUser::FormatName('#NAME# #LAST_NAME#', [
			'NAME' => CurrentUser::get()->getFirstName(),
			'LAST_NAME' => CurrentUser::get()->getLastName(),
			'SECOND_NAME' => CurrentUser::get()->getSecondName(),
			'LOGIN' => CurrentUser::get()->getLogin(),
		], true);

		return $formattedName . ' ' . Loc::getMessage('INTRANET_INVITATION_COLLAB_INVITE_YOU', null, $lang) . ' ' . $this->collab->getName();
	}
}