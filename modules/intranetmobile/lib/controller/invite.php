<?php

namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Intranet\ActionFilter\UserType;
use Bitrix\IntranetMobile\ActionFilter\InviteIntranetMobileAccessControl;
use Bitrix\IntranetMobile\Provider\InviteProvider;

class Invite extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new UserType(['employee', 'extranet']);
		$preFilters[] = new InviteIntranetMobileAccessControl();

		return $preFilters;
	}

	/**
	 * @restMethod intranetmobile.invite.getInviteSettings
	 * @return array
	 */
	public function getInviteSettingsAction(): array
	{
		return (new InviteProvider())->getInviteSettings();
	}
}
