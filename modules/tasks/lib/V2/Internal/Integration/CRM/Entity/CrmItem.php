<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Entity;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class CrmItem extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?string $id = null,
		public readonly ?int $entityId = null,
		public readonly ?Type $type = null,
		public readonly ?string $typeName = null,
		public readonly ?string $title = null,
		public readonly ?string $link = null,
		public readonly ?int $linkedEntityId = null,
		public readonly ?LinkedType $linkedEntityType = null,
	)
	{

	}
	public function getId(): ?string
	{
		return $this->id;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapString($props, 'id'),
			entityId: static::mapInteger($props, 'entityId'),
			type: static::mapBackedEnum($props, 'type', Type::class),
			typeName: static::mapString($props, 'typeName'),
			title: static::mapString($props, 'title'),
			link: static::mapString($props, 'link'),
			linkedEntityId: static::mapInteger($props, 'linkedEntityId'),
			linkedEntityType: static::mapBackedEnum($props, 'linkedEntityType', LinkedType::class),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'entityId' => $this->entityId,
			'type' => $this->type?->value,
			'typeName' => $this->typeName,
			'title' => $this->title,
			'link' => $this->link,
			'linkedEntityId' => $this->linkedEntityId,
			'linkedEntityType' => $this->linkedEntityType?->value,
		];
	}
}
