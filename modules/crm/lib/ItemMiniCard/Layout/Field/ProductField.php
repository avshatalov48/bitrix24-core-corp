<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Product;

final class ProductField extends AbstractField
{
	public function __construct(
		public string $title,
		public array $products = [],
		public ?int $productsLeftCount = null,
		public ?string $productsLeftUrl = null,
	)
	{
	}

	public function addValue(Product $product): self
	{
		$this->products[] = $product;

		return $this;
	}

	public function setProductsLeftCount(?int $productsLeftCount): self
	{
		$this->productsLeftCount = $productsLeftCount;

		return $this;
	}

	public function setProductsLeftUrl(?string $productsLeftUrl): self
	{
		$this->productsLeftUrl = $productsLeftUrl;

		return $this;
	}

	public function getName(): string
	{
		return 'ProductField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'products' => array_map(static fn (Product $product) => $product->toArray(), $this->products),
			'productsLeftCount' => $this->productsLeftCount,
			'productsLeftUrl' => $this->productsLeftUrl,
		];
	}
}
