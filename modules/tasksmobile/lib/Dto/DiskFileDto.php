<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;

final class DiskFileDto extends Dto
{
	public int $id;
	public int $objectId;

	public ?string $name = null;
	public ?string $type = null;

	public ?string $url = null;
	public ?int $height = null;
	public ?int $width = null;

	public ?string $previewUrl = null;

	protected function getDecoders(): array
	{
		return [
			new ToCamelCase(),
		];
	}
}
