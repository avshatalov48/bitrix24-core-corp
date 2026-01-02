<?php

declare(strict_types=1);

namespace Bitrix\Mobile\Feature;

use Bitrix\Mobile\Config\FeatureFlag;
use Bitrix\Main\Config\Option;

final class SettingsV2Feature extends FeatureFlag
{
	public function isEnabled(): bool
	{
		return (bool)Option::get('mobile', 'feature_settings_v2_enabled', false);
	}

	public function enable(): void
	{
		Option::set('mobile', 'feature_settings_v2_enabled', true);
	}

	public function disable(): void
	{
		Option::delete('mobile', ['name' => 'feature_settings_v2_enabled']);
	}
}
