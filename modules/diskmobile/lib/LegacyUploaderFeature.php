<?php

declare(strict_types=1);

namespace Bitrix\DiskMobile;

use Bitrix\Main\Config\Option;
use Bitrix\Mobile\Config\FeatureFlag;

final class LegacyUploaderFeature extends FeatureFlag
{
	public function isEnabled(): bool
	{
		return (bool)Option::get('diskmobile', 'feature_legacy_uploader', false);
	}

	public function enable(): void
	{
		Option::set('diskmobile', 'feature_legacy_uploader', true);
	}

	public function disable(): void
	{
		Option::delete('diskmobile', ['name' => 'feature_legacy_uploader']);
	}
}
