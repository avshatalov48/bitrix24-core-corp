<?php

declare(strict_types=1);

namespace Bitrix\Baas\Contract;

interface Package
{
	public function getCode(): string;

	public function setLanguage(string $languageId): static;

	public function getTitle(): string;

	public function getDescription(): ?string;

	public function getPriceDescription(): ?string;

	public function getPrice(): string;

	public function getPurchaseUrl(): ?string;

	/**
	 * @return array<PurchasedServiceInPackage>
	 */
	public function getPurchasedServices(): array;

	public function getPurchaseInfo(bool $includeDepleted = false): PurchasesSummary;

	public function getPurchases(): array;
}
