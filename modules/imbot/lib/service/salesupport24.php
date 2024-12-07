<?php declare(strict_types=1);

namespace Bitrix\ImBot\Service;

class SaleSupport24 extends Notifier
{
	protected static function detectBot(): ?string
	{
		static $classSupport = null;

		if (\Bitrix\ImBot\Bot\SaleSupport24::isEnabled())
		{
			$classSupport = \Bitrix\ImBot\Bot\SaleSupport24::class;
		}

		return $classSupport;
	}
}
