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

class CollabJoinMessageFactory implements InvitationMessageFactoryContract
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

		$eventName = 'COLLAB_JOIN';
		$messageId = \CIntranetInviteDialog::getMessageId($eventName, $siteId, LANGUAGE_ID);

		$params = [
			'USER_ID' => $this->user->getId(),
			'USER_ID_FROM' => CurrentUser::get()->getId(),
			'EMAIL' => $emailTo,
			'COLLAB_NAME' => $this->collab->getName(),
			'COLLAB_CHAT_URL' => '/extranet/online/?IM_COLLAB=chat' . $this->collab->getChatId(),
			'ACTIVE_USER' => $this->user->getInviteStatus() === InvitationStatus::ACTIVE,
			'COLLAB_JOIN_SUBJECT' => $this->createCollabSubject(),
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

	private function createCollabSubject()
	{
		$messages = Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/components/bitrix/intranet.template.mail/templates/.default/collab_join.php');

		return $messages['INTRANET_COLLAB_JOIN_SUBJECT'];
	}
}