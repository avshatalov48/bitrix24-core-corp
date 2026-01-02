<?php

namespace Bitrix\Baas;

use Bitrix\Baas\Public\Provider;
use Bitrix\Baas\Internal\Service\BillingSynchronizationService;
use Bitrix\Baas\Internal\Service\BaasService;
use Bitrix\Baas\Service\PurchaseService;
use Bitrix\Main;

class Baas
{
	use Internal\Trait\SingletonConstructor;

	protected bool $registered;
	protected Contract\License $license;
	protected Config\Client $config;
	protected Service\ServiceService $serviceManager;
	protected Service\InstantMessageService $imService;
	protected Provider\PackageProvider $packageProvider;

	protected function __construct(Contract\License $license, Config\Client $config)
	{
		$this->license = $license;
		$this->config = $config;
		$this->registered = $this->config->isRegistered();

		$eventManager = Main\EventManager::getInstance();
		$eventManager->addEventHandler('baas', 'onServiceBalanceChanged', function ($event) {$this->sendMessage($event);});
	}

	public function isAvailable(): bool
	{
		return $this->license->isBaasAvailable();
	}

	public function isActive(): bool
	{
		return $this->license->isActive();
	}

	public function isRegistered(): bool
	{
		return $this->registered;
	}

	public function isSellableToAll(): bool
	{
		return $this->license->isSellableToAll();
	}

	/** Fast snippet:
if (\Bitrix\Main\Loader::includeModule('baas'))
{
	\Bitrix\Baas\Baas::getInstance()->sync();
}
	 */
	public function sync(bool $force = true): Main\Result
	{
		if ($this->isAvailable())
		{
			if ($force === true)
			{
				return BillingSynchronizationService::getInstance()->sync();
			}

			return BillingSynchronizationService::getInstance()->syncIfNeeded();
		}

		return (new Main\Result())->addError(new Main\Error('Baas is not available'));
	}



	/**
	 * @return array<Contract\Purchase>
	 */
	public function getNotExpiredPurchases(): array
	{
		if ($this->isAvailable())
		{
			return PurchaseService::getInstance()->getNotExpired();
		}

		return [];
	}

	/**
	 * @return array<Contract\Package>
	 */
	public function getPackagesWithActivePurchases(): array
	{
		$allPackages = $this->getPackageProvider()->getDistributedByBaas();
		$packagesWithActivePurchases = [];

		foreach ($allPackages as $package)
		{
			if (count($package->getPurchasedServices()) > 0)
			{
				$packagesWithActivePurchases[] = $package;
			}
		}

		return $packagesWithActivePurchases;
	}

	/**
	 * @return array<Contract\Package>
	 */
	public function getPackagesWithoutActivePurchases(): array
	{
		$allPackages = $this->getPackageProvider()->getDistributedByBaas();
		$packagesWithoutActivePurchases = [];

		foreach ($allPackages as $package)
		{
			if ($package->getPurchaseInfo()->getCount() === 0)
			{
				$packagesWithoutActivePurchases[] = $package;
			}
		}

		return $packagesWithoutActivePurchases;
	}

	/**
	 * @deprecated use Provider\ServiceProvider()
	 */
	public function getServiceManager(): Contract\ServiceService
	{
		if (!isset($this->serviceManager))
		{
			$this->serviceManager = new Service\ServiceService(
				Service\BillingService::getInstance(),
				Service\ProxyService::getInstance(),
				Repository\ServiceRepository::getInstance(),
				Repository\PurchaseRepository::getInstance(),
			);
		}

		return $this->serviceManager;
	}

	public function getService(string $serviceCode): Contract\Service
	{
		return $this->getServiceManager()->getByCode($serviceCode);
	}

	protected function getPackageProvider(): Provider\PackageProvider
	{
		if (!isset($this->packageProvider))
		{
			$this->packageProvider = Provider\PackageProvider::create();
		}

		return $this->packageProvider;
	}

	protected function getIMService(): Service\InstantMessageService
	{
		if (!isset($this->imService))
		{
			$this->imService = new Service\InstantMessageService();
		}

		return$this->imService;
	}

	protected function sendMessage(Main\Event $event): void
	{
		/**@var Entity\Service $service */
		$service = $event->getParameter('service');
		$purchases = $this->getNotExpiredPurchases();
		$packages = $this->getPackagesWithActivePurchases();
		$this
			->getIMService()
			->sendBalanceMessage(
				$service,
				$packages,
				$purchases,
			)
		;
	}

	/**
	 * @return mixed
	 * @throws Main\LoaderException
	 */
	public static function getInstance(): static
	{
		if (!isset(static::$instance))
		{
			static::$instance = new self(BaasService::getInstance()->getLicense(), new Config\Client());
		}

		return static::$instance;
	}

	/**
	 * @deprecated
	 * @return Contract\Purchase[]
	 */
	public function getPurchases(): array
	{
		return $this->getNotExpiredPurchases();
	}
}
