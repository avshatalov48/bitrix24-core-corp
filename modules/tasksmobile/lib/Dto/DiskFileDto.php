<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;

final class DiskFileDto extends Dto
{
	public int $id;
	public string $objectId;
	public string $name;
	public string $size;
	public string $url;
	public string $type;
	public bool $isImage = false;

	protected function getDecoders(): array
	{
		return [
			new ToCamelCase(),
		];
	}
}
