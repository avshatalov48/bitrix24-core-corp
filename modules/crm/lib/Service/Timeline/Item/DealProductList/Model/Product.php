<?php

namespace Bitrix\Crm\Service\Timeline\Item\DealProductList\Model;

use Bitrix\Main\Type\Contract\Arrayable;

/**
 * @internal
 */
final class Product implements Arrayable
{
	private ?int $offerId = null;
	private ?string $name = null;
	private ?string $adminLink = null;
	private ?string $imageSource = null;
	private ?string $variationInfo = null;
	private ?float $price = null;
	private ?string $currency = null;

	public function getOfferId(): ?int
	{
		return $this->offerId;
	}

	public function setOfferId(?int $offerId): self
	{
		$this->offerId = $offerId;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(?string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getAdminLink(): ?string
	{
		return $this->adminLink;
	}

	public function setAdminLink(?string $adminLink): self
	{
		$this->adminLink = $adminLink;

		return $this;
	}

	public function getImageSource(): ?string
	{
		return $this->imageSource;
	}

	public function setImageSource(?string $imageSource): self
	{
		$this->imageSource = $imageSource;

		return $this;
	}

	public function getVariationInfo(): ?string
	{
		return $this->variationInfo;
	}

	public function setVariationInfo(?string $variationInfo): self
	{
		$this->variationInfo = $variationInfo;

		return $this;
	}

	public function getPrice(): ?float
	{
		return $this->price;
	}

	public function setPrice(?float $price): self
	{
		$this->price = $price;

		return $this;
	}

	public function getCurrency(): ?string
	{
		return $this->currency;
	}

	public function setCurrency(?string $currency): self
	{
		$this->currency = $currency;

		return $this;
	}

	public function toArray()
	{
		return Converter::convertToArray($this);
	}
}
