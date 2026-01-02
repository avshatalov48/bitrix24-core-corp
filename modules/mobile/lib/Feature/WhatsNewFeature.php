<?php

declare(strict_types=1);

namespace Bitrix\Mobile\Feature;

use Bitrix\Mobile\Config\FeatureFlag;
use Bitrix\Main\Config\Option;

final class WhatsNewFeature extends FeatureFlag
{
	public function isEnabled(): bool
	{
		return (isModuleInstalled('bitrix24') || $this->isMobileWhatsNewEnabled());
	}

	public function isMobileWhatsNewEnabled(): bool
	{
		return (bool)Option::get('mobile', 'feature_whats_new_enabled', false);
	}

	public function enable(): void
	{
		Option::set('mobile', 'feature_whats_new_enabled', true);
	}

	public function disable(): void
	{
		Option::delete('mobile', ['name' => 'feature_whats_new_enabled']);
	}
}
