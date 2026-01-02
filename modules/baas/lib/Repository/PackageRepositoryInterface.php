<?php

declare(strict_types=1);

namespace Bitrix\Baas\Repository;

use Bitrix\Baas;

interface PackageRepositoryInterface {
	public function purge(): void;

	public function save(
		Baas\Model\EO_Package_Collection $packages,
		Baas\Model\EO_ServiceInPackage_Collection $packageServices,
	): void;
}
