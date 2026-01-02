<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Field;

use Bitrix\Main\Entity\EntityInterface;
use Bitrix\Main\Type\Contract\Arrayable;

interface Field extends EntityInterface, Arrayable
{
	public function getValue(): mixed;

	public function getTitle(): string;

	public function getId(): string;

	public function getType(): string;

	public function isEditable(): bool;

	public function isShowAlways(): bool;

	public function isValid(mixed $value = null): bool;

	public function isMultiple(): bool;
}
