<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Type;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Public\Type\BaseInvitation;
use Bitrix\Socialnetwork\Collab\Collab;

class EmailInvitation extends BaseInvitation
{
	public function __construct(
		private readonly string $email,
		readonly ?string $name = null,
		readonly ?string $lastName = null,
		readonly ?string $formType = null,
		readonly ?string $languageId = null,
	)
	{
		parent::__construct($name, $lastName, $formType);
	}

	public function toArray(): array
	{
		return [
			'LOGIN' => $this->email,
			'EMAIL' => $this->email,
			'NAME' => $this->name,
			'LAST_NAME' => $this->lastName,
			'LANGUAGE_ID' => $this->languageId,
		];
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getType(): InvitationType
	{
		return InvitationType::EMAIL;
	}

	public function getLogin(): string
	{
		return $this->email;
	}
}
