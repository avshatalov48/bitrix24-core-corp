<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Field;

use Bitrix\Intranet\Internal\Entity\IdentifiableEntityCollection;
use Bitrix\Main\Type\Contract\Arrayable;

class FieldCollection extends IdentifiableEntityCollection implements Arrayable
{
	protected static function getEntityClass(): string
	{
		return Field::class;
	}

	public function toArray(): array
	{
		return $this->map(fn (Field $item) => $item->toArray());
	}
}
