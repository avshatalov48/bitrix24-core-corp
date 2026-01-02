<?php

declare(strict_types=1);

namespace Bitrix\Baas\Internal\Service;

use Bitrix\Baas;
use Bitrix\Main;
use Bitrix\Baas\Service\PurchaseService;

class BillingSynchronizationService
{
	use Baas\Internal\Trait\SingletonConstructor;

	private const LOCK_NAME = 'baas_sync_where_appropriate';
	private const LOCK_LIMIT = 0;

	private PurchaseService $purchaseService;

	private static bool $synchronized = false;

	protected function __construct(
		private Baas\Service\BillingService $billingService,
		private Baas\Config\Client $configs,
	)
	{
	}

	public function syncIfNeeded(): Main\Result
	{
		$result = new Main\Result();
		if (self::$synchronized === false && $this->isTimeToSynchronize())
		{
			self::$synchronized = true;

			try
			{
				$connection = Main\Application::getConnection();
				$locked = $connection->lock(
					self::LOCK_NAME,
					self::LOCK_LIMIT,
				);
				if ($locked && $this->isTimeToSynchronize())
				{
					$result = $this->sync();
				}
			}
			finally
			{
				if (isset($connection) && isset($locked) && $locked)
				{
					$connection->unlock(self::LOCK_NAME);
				}
			}
		}

		return $result;
	}

	public function sync(): Main\Result
	{
		try
		{
			$syncResult = $this->billingService->synchronizeWithBilling();

			PurchaseService::getInstance()->notifyAboutUnnotifiedPurchases();
		}
		catch (Baas\UseCase\BaasException $e)
		{
			$this->delaySyncForSec();
			$syncResult = new Main\Result();
			$syncResult->addError(new Main\Error($e->getMessage()));
		}

		return $syncResult;
	}

	protected function delaySyncForSec(int $sec = 300): void
	{
		$this->configs->setNextSyncTime(
			time() + $sec,
		);
	}

	protected function isTimeToSynchronize(?int $now = null): bool
	{
		$nextSyncTime = $this->configs->getNextSyncTime();
		$now = $now ?? time();
		// If the next sync time is in the future, we should not sync
		if ($nextSyncTime > $now)
		{
			return false;
		}

		$lastTimeToSync = $this->billingService->getLastSyncTime();
		// If we have never synced, we should sync
		if ($lastTimeToSync <= 0)
		{
			return true;
		}

		$lastSyncDate = strtotime(date('Y-m-d', $lastTimeToSync));
		$nowDate = strtotime(date('Y-m-d', $now - $this->configs->getSyncDelta()));

		return $lastSyncDate < $nowDate;
	}

	public static function getInstance(): static
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static(
				Baas\Service\BillingService::getInstance(),
				new Baas\Config\Client(),
			);
		}

		return static::$instance;
	}
}
