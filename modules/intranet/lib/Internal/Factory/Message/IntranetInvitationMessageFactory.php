<?php

namespace Bitrix\Intranet\Internal\Factory\Message;

use Bitrix\Intranet\Contract\SendableContract;
use Bitrix\Intranet\Contract\Strategy\InvitationMessageFactoryContract;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Public\Type\BaseInvitation;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Integration\HumanResources\DepartmentAssigner;
use Bitrix\Intranet\Service\EmailMessage;
use Bitrix\Intranet\Service\PhoneMessage;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class IntranetInvitationMessageFactory implements InvitationMessageFactoryContract
{

	public function __construct(
		private readonly User $user,
		private readonly DepartmentCollection $departmentCollection,
	)
	{}


	public function createEmailEvent(): SendableContract
	{
		Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intranet/lib/invitation/register.php');
		$isCloud = Loader::includeModule('bitrix24');

		if ($isCloud && $this->user->getId() !== null)
		{
			$networkEmail = (new \Bitrix\Bitrix24\Integration\Network\Invitation())->getEmailByUserId($this->user->getId());
			$emailTo = $networkEmail ?? $this->user->getEmail();
		}
		else
		{
			$emailTo = $this->user->getEmail();
		}

		$siteIdByDepartmentId = \CIntranetInviteDialog::getUserSiteId([
			"UF_DEPARTMENT" => $this->departmentCollection->map(fn($department) => $department->getId()),
			"SITE_ID" => SITE_ID,
		]);
		if ($isCloud)
		{
			$messageId = \CIntranetInviteDialog::getMessageId("BITRIX24_USER_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			$eventName = 'BITRIX24_USER_INVITATION';
			$params = [
				"EMAIL_FROM" => CurrentUser::get()->getEmail(),
				"USER_ID_FROM" => CurrentUser::get()->getId(),
				"EMAIL_TO" => $emailTo,
				"LINK" => \CIntranetInviteDialog::getInviteLink(['ID' => $this->user->getId(), 'CONFIRM_CODE' => $this->user->getConfirmCode()], $siteIdByDepartmentId),
				"USER_TEXT" => Loc::getMessage("INTRANET_INVITATION_INVITE_MESSAGE_TEXT"),
			];
		}
		else
		{
			$messageId = \CIntranetInviteDialog::getMessageId("INTRANET_USER_INVITATION", $siteIdByDepartmentId, LANGUAGE_ID);
			$eventName = 'INTRANET_USER_INVITATION';
			$params = [
				"EMAIL_TO" => $emailTo,
				"USER_ID_FROM" => CurrentUser::get()->getId(),
				"LINK" => \CIntranetInviteDialog::getInviteLink(['ID' => $this->user->getId(), 'CONFIRM_CODE' => $this->user->getConfirmCode()], $siteIdByDepartmentId),
				"USER_TEXT" => Loc::getMessage("INTRANET_INVITATION_INVITE_MESSAGE_TEXT"),
			];
		}

		return new EmailMessage(
			$eventName,
			$siteIdByDepartmentId,
			$params,
			$messageId,
		);
	}

	public function createSmsEvent(): SendableContract
	{
		return new PhoneMessage($this->user, true);
	}
}