<?php

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Intranet\Internal\Entity\User\Field\FieldCollection;
use Bitrix\Main\Entity\EntityInterface;
use Bitrix\Main\Type\Contract\Arrayable;

class FieldSection implements EntityInterface, Arrayable
{
	public function __construct(
		public readonly string $id,
		public readonly string $title,
		public readonly bool $isEditable,
		public readonly bool $isRemovable,
		public readonly FieldCollection $userFieldCollection,
		public readonly bool $isDefault,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'isEditable' => $this->isEditable,
			'isRemovable' => $this->isRemovable,
			'userFieldCollection' => $this->userFieldCollection->toArray(),
			'isDefault' => $this->isDefault,
		];
	}
}
