<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;
use Bitrix\Tasks\V2\Internal\Entity\User\Gender;

class User extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		#[PositiveNumber]
		public readonly ?int $id = null,
		public readonly ?string $name = null,
		public readonly ?string $role = null, // role depends on context
		public readonly ?File $image = null,
		public readonly ?Gender $gender = null,
		public readonly ?string $email = null,
		public readonly ?string $externalAuthId = null,
		public readonly ?array $rights = null,
	)
	{

	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getGender(): Gender
	{
		return $this->gender ?? Gender::Male;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			name: static::mapString($props, 'name'),
			role: static::mapString($props, 'role'),
			image: static::mapEntity($props, 'image', File::class),
			gender: static::mapBackedEnum($props, 'gender', Gender::class),
			email: static::mapString($props, 'email'),
			externalAuthId: static::mapString($props, 'externalAuthId'),
			rights: static::mapArray($props, 'rights'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'role' => $this->role,
			'image' => $this->image?->toArray(),
			'gender' => $this->gender?->value,
			'email' => $this->email,
			'externalAuthId' => $this->externalAuthId,
			'rights' => $this->rights,
		];
	}
}
