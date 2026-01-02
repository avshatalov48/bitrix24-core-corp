<?php

declare(strict_types=1);

namespace Bitrix\Baas\Service;

use Bitrix\Baas;
use Bitrix\Main;

class InstantMessageService
{
	public function sendBalanceMessage(
		Baas\Entity\Service $service,
		/** @var Baas\Contract\Purchase[] $packages */
		array $packages,
		/** @var Baas\Contract\Purchase[] $purchases */
		array $purchases,
	): void
	{
		Main\Application::getInstance()->addBackgroundJob(static function () use ($service, $packages, $purchases) {
			(new Baas\Integration\Pull\UpdateServiceMessage(
				$service,
				$packages,
				$purchases,
			))->send();
		});
	}
}
