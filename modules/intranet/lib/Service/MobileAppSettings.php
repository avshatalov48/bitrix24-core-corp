<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract\OptionContract;
use Bitrix\Main\Config\Option;

class MobileAppSettings
{
	public function __construct(
		private OptionContract $option
	)
	{
	}

	public function isReady(): bool
	{
		return $this->option->get('mobile_app_is_ready_to_ban_screenshots', 'N') === 'Y';
	}

	public function canTakeScreenshot(): bool
	{
		return $this->option->get('copy_screenshot_disabled', 'N') !== 'Y';
	}

	public function canCopyText(): bool
	{
		return $this->option->get('copy_text_disabled', 'N') !== 'Y';
	}

	public function setAllowScreenshot(bool $allow): void
	{
		$this->option->set(
			'copy_screenshot_disabled',
			$allow ? 'N' : 'Y'
		);
	}

	public function setAllowCopyText(bool $allow): void
	{
		$this->option->set(
			'copy_text_disabled',
			$allow ? 'N' : 'Y'
		);
	}
}
