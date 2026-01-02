<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Type;

use Bitrix\Intranet\Enum\InvitationType;

abstract class BaseInvitation
{
	public function __construct(
		private readonly ?string $name = null,
		private readonly ?string $lastName = null,
		private readonly ?string $formType = null,
	)
	{}

	abstract public function toArray(): array;

	abstract public function getLogin(): string;

	abstract public function getType(): InvitationType;

	public function getName(): ?string
	{
		return $this->name;
	}

	public function getLastName(): ?string
	{
		return $this->lastName;
	}

	/**
	 * @return string|null
	 */
	public function getFormType(): ?string
	{
		return $this->formType;
	}
}