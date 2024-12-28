<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\UserField\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;

final class UserFieldDto extends Dto
{
	public int $id;
	public string $type;
	public string $entityId;
	public string $fieldName;
	public string $title;
	public string|array $value;
	public int $sort;
	public bool $isMandatory;
	public bool $isMultiple;
	public bool $isVisible;
	public bool $isEditable;
	public array $settings;

	protected function getDecoders(): array
	{
		return [
			new ToCamelCase(),
		];
	}
}
