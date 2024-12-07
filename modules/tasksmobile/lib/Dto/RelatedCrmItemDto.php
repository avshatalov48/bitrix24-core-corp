<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;

final class RelatedCrmItemDto extends Dto
{
	public string $id;
	public string $type;
	public string $title;
	public string $subtitle;
	public bool $hidden;
}
