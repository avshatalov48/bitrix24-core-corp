<?php

declare(strict_types=1);

namespace Bitrix\Baas\Repository;

use Bitrix\Baas;

interface ServiceRepositoryInterface {
	public function findByCode(string $code): ?Baas\Model\EO_Service;

	public function purge(): void;

	public function save(
		Baas\Model\EO_Service_Collection $services,
		Baas\Model\EO_ServiceAds_Collection $servicesAds,
	): void;

	public function getAdsInfo(
		Baas\Entity\Service $service,
		string $languageId,
	): ?Baas\Model\EO_ServiceAds;

	public function getList(
		int $offset,
		int $limit,
	): Baas\Model\EO_Service_Collection;
}
