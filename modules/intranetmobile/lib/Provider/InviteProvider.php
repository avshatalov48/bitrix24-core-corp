<?php

namespace Bitrix\IntranetMobile\Provider;

use Bitrix\Bitrix24\Portal\Settings\EmailConfirmationRequirements\Type;
use Bitrix\Bitrix24\Service\PortalSettings;
use Bitrix\Intranet\Invitation;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

class InviteProvider
{
	public function getInviteSettings(): array
	{
		$canCurrentUserInvite = Invitation::canCurrentUserInvite();
		$isBitrix24Included = Loader::includeModule('bitrix24');
		$creatorEmailConfirmed = $this->isCreatorEmailConfirmed();
		$isInviteWithLocalEmailAppEnabled = (
			Option::get('intranetmobile', 'invite_with_local_email_app_enabled', 'Y') === 'Y'
		);

		return [
			'adminConfirm' => $canCurrentUserInvite ? Invitation::getRegisterAdminConfirm() : null,
			'canInviteBySMS' => Invitation::canCurrentUserInviteByPhone(),
			'canInviteByLink' => Invitation::canCurrentUserInviteByLink(),
			'canInviteByEmail' => $canCurrentUserInvite,
			'canCurrentUserInvite' => $canCurrentUserInvite,
			'creatorEmailConfirmed' => $creatorEmailConfirmed,
			'isBitrix24Included' => $isBitrix24Included,
			'adminInBoxRedirectLink' => '/company/',
			'isInviteWithLocalEmailAppEnabled' => $isInviteWithLocalEmailAppEnabled,
		];
	}

	public function isCreatorEmailConfirmed(): bool
	{
		return (
			!Loader::includeModule('bitrix24')
			|| !PortalSettings::getInstance()->getEmailConfirmationRequirements()->isRequiredByType(Type::INVITE_USERS)
		);
	}
}
