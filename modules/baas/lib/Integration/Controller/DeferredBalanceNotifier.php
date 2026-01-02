<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Controller;

use Bitrix\Main;

class DeferredBalanceNotifier
{
	protected const ATTEMPTS_TO_NOTIFY = 3;

	public function __construct(protected string $packageCode, protected string $purchaseCodeOrPurchasedPackage)
	{
	}

	public function bind()
	{
		\CTimeZone::Disable();
		\CAgent::AddAgent(
			$this->makeAgentName(1),
			'baas',
			'Y',
			1,
			'',
			'Y',
			date(Main\Type\Date::convertFormatToPhp(\CSite::GetDateFormat()), time()),
			100,
			false,
			false
		);
		\CTimeZone::Enable();
	}

	protected function makeAgentName(int $attempt = 0): string
	{
		return self::class . '::execAgent(\''
		. EscapePHPString($this->packageCode, "'") . '\', \''
		. EscapePHPString($this->purchaseCodeOrPurchasedPackage, "'") . '\', '
		. intval($attempt).');';
	}

	public static function execAgent(string $packageCode, string $purchaseCodeOrPurchasedPackage, int $attempt = 1): string
	{
		$notifier = new BalanceNotifier();

		try
		{
			$notifier->runNotification($packageCode, $purchaseCodeOrPurchasedPackage);
		}
		catch (Main\SystemException $e)
		{
			if ($e->getMessage() === 'TooEarly')
			{
				if ($attempt < self::ATTEMPTS_TO_NOTIFY)
				{
					return (new DeferredBalanceNotifier($packageCode, $purchaseCodeOrPurchasedPackage))->makeAgentName(++$attempt);
				}
			}
			else
			{
				throw $e;
			}
		}

		return '';
	}
}
