<?php

namespace Bitrix\Intranet\Internal\Entity;

use Bitrix\Intranet\Internal\Entity\Collection\UserFieldCollection;
use Bitrix\Main\Entity\EntityInterface;

class UserFieldSection implements EntityInterface
{
	public function __construct(
		public readonly string $id,
		public readonly string $title,
		public readonly bool $isEditable,
		public readonly bool $isRemovable,
		public readonly UserFieldCollection $userFieldCollection,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}
}
