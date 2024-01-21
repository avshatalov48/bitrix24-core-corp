<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field\Dto;

class UserCard
{
	public function __construct(
		protected string $name,
		protected string $lastName,
		protected string $secondName,
		protected string $email,
		protected string $login,
		protected string $photo,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLastName(): string
	{
		return $this->lastName;
	}

	public function getSecondName(): string
	{
		return $this->secondName;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getLogin(): string
	{
		return $this->login;
	}

	public function getPhoto(): string
	{
		return $this->photo;
	}
}
