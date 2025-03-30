<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Feature;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Type\DateTime;

final class PermissionsOnboardingPopup extends Base
{
	protected const OPTION_NAME = 'permissions-onboarding-popup';

	protected function canShow(): bool
	{
		return
			Feature::enabled(Feature\PermissionsLayoutV2::class)
			&& !$this->isUserSeenTour()
		;
	}

	protected function getShowDeadline(): ?DateTime
	{
		return new DateTime('01.04.2025', 'd.m.Y');
	}

	protected function getPortalMaxCreatedDate(): ?DateTime
	{
		return new DateTime('01.11.2024', 'd.m.Y');
	}

	protected function getComponentTemplate(): string
	{
		return 'permissions_onboarding';
	}
}
