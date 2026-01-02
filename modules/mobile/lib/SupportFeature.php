<?php

declare(strict_types=1);

namespace Bitrix\Mobile;

use Bitrix\Mobile\Config\FeatureFlag;
use Bitrix\Main\Config\Option;
use Bitrix\Mobile\Provider\SupportProvider;

final class SupportFeature extends FeatureFlag
{
	public function isEnabled(): bool
	{
		$supportProvider = new SupportProvider();

		return $supportProvider->isEnabled() && $this->isMobileSupportIsOn();
	}

	public function isMobileSupportIsOn(): bool
	{
		return (bool)Option::get('mobile', 'feature_support_enabled', true);
	}

	public function enable(): void
	{
		Option::set('mobile', 'feature_support_enabled', true);
	}

	public function disable(): void
	{
		Option::delete('mobile', ['name' => 'feature_support_enabled']);
	}
}