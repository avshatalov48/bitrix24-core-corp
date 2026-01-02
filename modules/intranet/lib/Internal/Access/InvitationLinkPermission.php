<?php

namespace Bitrix\Intranet\Internal\Access;

use Bitrix\Intranet\Invitation;

class InvitationLinkPermission
{
	public function isEnabled(): bool
	{
		return Invitation::getRegisterSettings()['REGISTER'] === 'Y';
	}
}