<?php

namespace Bitrix\Crm\Order\TradingPlatform\Telegram;

use Bitrix\Crm;
use Bitrix\Sale;

class Telegram
	extends Crm\Order\TradingPlatform\Platform
	implements Sale\TradingPlatform\IRestriction
{
	const TRADING_PLATFORM_CODE = 'telegram';

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		return 'Telegram';
	}

	public function install()
	{
		$installResult = parent::install();
		if ($installResult)
		{
			$this->installEventHandlers();
		}

		return $installResult;
	}

	public function uninstall()
	{
		$uninstallResult = parent::uninstall();
		if ($uninstallResult)
		{
			$this->uninstallEventHandlers();
		}
	}

	private function installEventHandlers(): void
	{
		(new EventHandlerInstaller())->onInstall();
	}

	private function uninstallEventHandlers(): void
	{
		(new EventHandlerInstaller())->onUninstall();
	}
}
