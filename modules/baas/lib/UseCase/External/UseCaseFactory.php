<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

use Bitrix\Baas;

class UseCaseFactory
{
	public function __construct(
		protected Baas\UseCase\External\Entity\Client $client,
		protected Baas\UseCase\External\Entity\Server $server,
		protected Baas\Repository\ServiceRepositoryInterface $serviceRepository,
		protected Baas\Repository\PackageRepositoryInterface $packageRepository,
		protected Baas\Repository\PurchaseRepositoryInterface $purchaseRepository,
	)
	{
	}

	/**
	 * @param string $languageId
	 * @return BillingDataGet
	 * @throws Exception\ClientIsNotRegistered
	 */
	public function createBillingDataGet(string $languageId): BillingDataGet
	{
		return new Baas\UseCase\External\BillingDataGet(
			new Baas\UseCase\External\Request\BillingDataGetRequest(
				$this->server,
				$this->client,
				languageId: $languageId,
			),
		);
	}

	/**
	 * @return BillingDataPurge
	 */
	public function createBillingDataPurge(): BillingDataPurge
	{
		return new Baas\UseCase\External\BillingDataPurge(
			new Baas\UseCase\External\Request\BillingDataPurgeRequest(
				server: $this->server,
				client: $this->client,
				serviceRepository: $this->serviceRepository,
				packageRepository: $this->packageRepository,
				purchaseRepository: $this->purchaseRepository,
			),
		);
	}

	/**
	 * @param Baas\Model\EO_Service_Collection $services
	 * @param Baas\Model\EO_ServiceAds_Collection $servicesAds
	 * @param Baas\Model\EO_Package_Collection $packages
	 * @param Baas\Model\EO_ServiceInPackage_Collection $servicesInPackages
	 * @param Baas\Model\EO_Purchase_Collection $purchases
	 * @param Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages
	 * @param Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages
	 * @return BillingDataSave
	 */
	public function createBillingDataSave(
		Baas\Model\EO_Service_Collection $services,
		Baas\Model\EO_ServiceAds_Collection $servicesAds,
		Baas\Model\EO_Package_Collection $packages,
		Baas\Model\EO_ServiceInPackage_Collection $servicesInPackages,
		Baas\Model\EO_Purchase_Collection $purchases,
		Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages,
		Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages,
	): BillingDataSave
	{
		return new Baas\UseCase\External\BillingDataSave(
			new Baas\UseCase\External\Request\BillingDataSaveRequest(
				server: $this->server,
				client: $this->client,

				serviceRepository: $this->serviceRepository,
				packageRepository: $this->packageRepository,
				purchaseRepository: $this->purchaseRepository,

				services: $services,
				servicesAds: $servicesAds,
				packages: $packages,
				servicesInPackages: $servicesInPackages,
				purchases: $purchases,
				purchasedPackages: $purchasedPackages,
				servicesInPurchasedPackages: $servicesInPurchasedPackages,
			),
		);
	}

	/**
	 * @param array $billingBalance
	 * @return BillingBalanceParse
	 * @throws Exception\ClientIsNotRegistered
	 */
	public function createBillingBalanceParse(array $billingBalance): BillingBalanceParse
	{
		return new Baas\UseCase\External\BillingBalanceParse(
			new Baas\UseCase\External\Request\BillingBalanceParseRequest(
				server: $this->server,
				client: $this->client,
				data: $billingBalance,
			),
		);
	}

	/**
	 * @param Baas\Model\EO_Purchase_Collection $purchases
	 * @param Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages
	 * @param Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages
	 * @return BillingBalanceSave
	 */
	public function createBillingBalanceSave(
		Baas\Model\EO_Purchase_Collection $purchases,
		Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages,
		Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages,
	): BillingBalanceSave
	{
		return new Baas\UseCase\External\BillingBalanceSave(
			new Baas\UseCase\External\Request\BillingBalanceSaveRequest(
				server: $this->server,
				client: $this->client,

				serviceRepository: $this->serviceRepository,
				packageRepository: $this->packageRepository,
				purchaseRepository: $this->purchaseRepository,

				purchases: $purchases,
				purchasedPackages: $purchasedPackages,
				servicesInPurchasedPackages: $servicesInPurchasedPackages,
			),
		);
	}

	public function createRegisterDomain(): RegisterDomain
	{
		return new Baas\UseCase\External\RegisterDomain(
			new Baas\UseCase\External\Request\BaseRequest(
				$this->server,
				$this->client,
			),
		);
	}

	public function createVerifyDomain(): VerifyDomain
	{
		return new Baas\UseCase\External\VerifyDomain(
			new Baas\UseCase\External\Request\BaseRequest(
				$this->server,
				$this->client,
			),
		);
	}

	public function createVerifyAckDomain(string $syn, string $ack): VerifyAckDomain
	{
		return new Baas\UseCase\External\VerifyAckDomain(
			new Baas\UseCase\External\Request\VerifyAckRequest(
				server: $this->server,
				client: $this->client,
				ack: $ack,
				syn: $syn,
			),
		);
	}

	/**
	 * @param Baas\Entity\Service $service
	 * @param int $units
	 * @param bool $force
	 * @param array|null $attributes
	 * @return ConsumeService
	 * @throws Exception\ClientIsNotRegistered
	 */
	public function createConsumeService(
		Baas\Entity\Service $service,
		int $units,
		bool $force = false,
		?array $attributes = null,
	): ConsumeService
	{
		return new Baas\UseCase\External\ConsumeService(
			new Baas\UseCase\External\Request\ConsumeServiceRequest(
				server: $this->server,
				client: $this->client,
				service: $service,
				units: $units,
				force: $force,
				attributes: $attributes,
			),
		);
	}

	/**
	 * @param Baas\Entity\Service $service
	 * @param string $consumptionId
	 * @param array|null $attributes
	 * @return RefundService
	 * @throws Exception\ClientIsNotRegistered
	 */
	public function createRefundService(
		Baas\Entity\Service $service,
		string $consumptionId,
		?array $attributes = null,
	): RefundService
	{
		return new Baas\UseCase\External\RefundService(
			new Baas\UseCase\External\Request\RefundServiceRequest(
				server: $this->server,
				client: $this->client,
				service: $service,
				consumptionId: $consumptionId,
				attributes: $attributes,
			),
		);
	}

	public function createGetSalesStatus(): GetBaasSalesStatus
	{
		return new Baas\UseCase\External\GetBaasSalesStatus(
			new Baas\UseCase\External\Request\BaseRequest(
				$this->server,
				$this->client,
			),
		);
	}

	public function createBillingServiceBalanceParse(
		Baas\Entity\Service $service,
		array $balanceStateRawResponse,
	): Baas\UseCase\External\BillingServiceBalanceParse
	{
		return new Baas\UseCase\External\BillingServiceBalanceParse(
			new Baas\UseCase\External\Request\BillingServiceBalanceParseRequest(
				server: $this->server,
				client: $this->client,
				service: $service,
				rawData: $balanceStateRawResponse,
			),
		);
	}

	public function createGetPurchasedPackageReport(
		string $packageCode,
		string $purchaseCode,
		?string $serviceCode = null,
	): Baas\UseCase\External\GetPurchaseReport
	{
		return new Baas\UseCase\External\GetPurchaseReport(
			new Baas\UseCase\External\Request\GetPurchaseReportRequest(
				server: $this->server,
				client: $this->client,
				packageCode: $packageCode,
				purchaseCode: $purchaseCode,
				serviceCode: $serviceCode,
			),
		);
	}
}
