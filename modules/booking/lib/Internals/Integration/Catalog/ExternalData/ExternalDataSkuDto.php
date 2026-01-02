<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Catalog\ExternalData;

use Bitrix\Booking\Internals\Integration\Catalog\Sku;
use Bitrix\Main\Type\Contract\Arrayable;

class ExternalDataSkuDto implements Arrayable
{
	public function __construct(
		public readonly SkuDataDto $data,
		public readonly PermissionDto $permissions,
	)
	{
	}

	public static function fromSku(Sku $sku): self
	{
		return new self(
			new SkuDataDto(
				id: $sku->getId(),
				name: $sku->getName()
			),
			new PermissionDto(true),
		);
	}

	public static function buildEmpty(int $id): self
	{
		return new self(
			new SkuDataDto($id),
			new PermissionDto(),
		);
	}

	public function toArray(): array
	{
		return [
			'data' => $this->data->toArray(),
			'permissions' => $this->permissions->toArray(),
		];
	}
}
