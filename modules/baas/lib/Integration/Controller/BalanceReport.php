<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Controller;

use Bitrix\Baas;

final class BalanceReport
{
	private Baas\Baas $baas;

	public function __construct()
	{
		$this->baas = Baas\Baas::getInstance();
	}

	public function get(): string
	{
		try
		{
			$this->baas->sync();
		}
		catch (\Exception $exception)
		{
			return implode(PHP_EOL, ['Baas sync failed. ', '<pre>' , print_r($exception, true), '</pre>']);
		}

		if ($this->baas->isAvailable() !== true)
		{
			return $this->getUnavailableInfo();
		}

		return $this->getSummaryInfo();
	}

	private function getUnavailableInfo(): string
	{
		return 'Baas is not available for this portal.';
	}

	private function getSummaryInfo(): string
	{
		$noticeCollection = [];

		$noticeCollection['isAvailable'] = 'Baas is available.';
		$noticeCollection['isActive'] = $this->baas->isActive() ?
			'Baas is active.' :
			'Baas is not active: boosts are not enabled.'
		;

		$noticeCollection['packages'] = [[
			'Service', 'Current val', 'Maximal val',
			'Package', 'Purchase', 'Purchased Pack',
			'Start date', 'Expiration date'
        ]];
		$services = [];

		foreach (Baas\Public\Provider\PackageProvider::create()->getDistributedByBaas() as $package)
		{
			$formattedPurchases = [];
			foreach ($package->getPurchases() as $purchase)
			{
				/** @var Baas\Model\Dto\PurchasedPackage $purchasedPack */
				foreach ($purchase->getPurchasedPackages() as $purchasedPack)
				{
					$packBalance = [];
					foreach ($purchasedPack->getPurchasedServices() as $service)
					{
						$packBalance[$service->getServiceCode()] ??= ['current' => 'not actual', 'max' => 0];
						$packBalance[$service->getServiceCode()]['max'] += $service->getInitialValue();
						if ($purchasedPack->isActual())
						{
							if (!is_integer($packBalance[$service->getServiceCode()]['current']))
							{
								$packBalance[$service->getServiceCode()]['current'] = 0;
							}
							$packBalance[$service->getServiceCode()]['current'] += $service->getCurrentValue();
						}
					}

					foreach ($packBalance as $serviceCode => $balance)
					{
						$formattedPurchases[] = [$serviceCode, ...$balance,  ...[
							$package->getCode(),
							$purchase->getCode(),
							$purchasedPack->getCode(),
							$purchasedPack->getStartDate()->format(
								Baas\Contract\DateTimeFormat::LOCAL_DATETIME->value
							),
							$purchasedPack->getExpirationDate()->format(
								Baas\Contract\DateTimeFormat::LOCAL_DATETIME->value
							),
						]];
					}
				}
			}

			if (empty($formattedPurchases))
			{
				$noticeCollection['packages'][] = [
					'', '', '',
					$package->getCode(), 'Not bought', '',
					'', ''
				];
			}
			else
			{
				$noticeCollection['packages'] = array_merge(
					$noticeCollection['packages'],
					$formattedPurchases
				);
			}
		}
		$cb = fn($value) => str_pad(substr((string)$value, 0, 20), 20, '_', STR_PAD_RIGHT);
		array_walk(
			$noticeCollection['packages'],
			function (&$row) use ($cb) {
				$row = implode(' | ', array_map($cb, $row));
			}
		);
		$noticeCollection['packages'] = implode(PHP_EOL, $noticeCollection['packages']);

		return implode(PHP_EOL, $noticeCollection);
	}
}
