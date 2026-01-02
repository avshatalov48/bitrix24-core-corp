<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Controller;

use Bitrix\Baas\Integration;
/* Snippet
\Bitrix\Main\Loader::includeModule('baas');
\Bitrix\Baas\Integration\Controller\Facade::notifyAboutPurchase('DOCGENERATION_P1', 'realPurchaseCode');
*/
class Facade
{
	public static function getBalanceReportSummary(): string
	{
		return (new Integration\Controller\BalanceReport())->get();
	}

	public static function notifyAboutPurchase(mixed $packageCode, mixed $purchaseCode): void
	{
		(new Integration\Controller\BalanceNotifier())->sendAboutPurchase((string)$packageCode, (string)$purchaseCode);
	}
}
