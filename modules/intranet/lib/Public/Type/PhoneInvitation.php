<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Type;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Entity\Type\Phone;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Public\Type\BaseInvitation;
use Bitrix\Socialnetwork\Collab\Collab;

class PhoneInvitation extends BaseInvitation
{
	private Phone $phone;

	public function __construct(
		string $phone,
		readonly ?string $name = null,
		readonly ?string $lastName = null,
		private readonly ?string $phoneCountry = null,
		readonly ?string $formType = null,
	)
	{
		parent::__construct($name, $lastName, $formType);
		$this->phone = new Phone($phone, $this->phoneCountry);
	}

	public function toArray(): array
	{
		return [
			'LOGIN' => $this->getLogin(),
			'PHONE' => $this->phone,
			'NAME' => $this->name,
			'LAST_NAME' => $this->lastName,
			'PHONE_COUNTRY' => $this->phoneCountry,
			'PERSONAL_MOBILE' => $this->phone->defaultFormat(),
			'PHONE_NUMBER' => $this->phone->defaultFormat(),
		];
	}

	public function getPhone(): string
	{
		return $this->phone->getRawNumber();
	}

	public function getType(): InvitationType
	{
		return InvitationType::PHONE;
	}

	public function getLogin(): string
	{
		return $this->phone->defaultFormat();
	}
}