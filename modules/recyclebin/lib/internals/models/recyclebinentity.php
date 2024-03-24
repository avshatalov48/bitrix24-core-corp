<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Recyclebin\Internals\Type\TypeDictionary;

class RecyclebinEntity extends EO_Recyclebin
{
	public function isTask(): bool
	{
		return $this->is(TypeDictionary::TASK);
	}

	public function isTemplate(): bool
	{
		return $this->is(TypeDictionary::TEMPLATE);
	}

	public function is(string $type): bool
	{
		return $type === $this->getEntityType();
	}
}