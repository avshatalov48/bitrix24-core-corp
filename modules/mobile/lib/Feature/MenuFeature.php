<?php


declare(strict_types=1);

namespace Bitrix\Mobile\Feature;

use Bitrix\Mobile\Config\FeatureFlag;
use Bitrix\Main\Config\Option;

final class MenuFeature extends FeatureFlag
{
	public function isEnabled(): bool
	{
		return ($this->clientHasApiVersion(61) && $this->isNewMenuEnabled());
	}

	public function isNewMenuEnabled()
	{
		return Option::get('mobile', 'new_menu_enabled', 'N') === 'Y';
	}

	public function enable(): void
	{
		Option::set('mobile', 'new_menu_enabled', 'Y');
	}


	public function disable(): void
	{
		Option::delete('mobile', ['name' => 'new_menu_enabled']);
	}
}
