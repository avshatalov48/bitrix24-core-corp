<?php

namespace Bitrix\Tasks\Internals\Notification;

class User
{
	private int $id;
	private string $name;
	private string $lang;
	private ?string $gender;
	private ?string $lastName;
	private ?string $secondName;
	private ?string $email;
	private ?string $externalAuthId;

	public function __construct(int $id, string $name, string $lang, array $optional = [])
	{
		$this->id = $id;
		$this->name = $name;
		$this->lang = $lang;

		if (isset($optional['gender']))
		{
			$this->gender = $optional['gender'];
		}

		if (isset($optional['last_name']))
		{
			$this->lastName = $optional['last_name'];
		}

		if (isset($optional['second_name']))
		{
			$this->secondName = $optional['second_name'];
		}

		if (isset($optional['email']))
		{
			$this->email = $optional['email'];
		}

		if(isset($optional['external_auth_id']))
		{
			$this->externalAuthId = $optional['external_auth_id'];
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLang(): string
	{
		return $this->lang;
	}

	public function getGender(): ?string
	{
		return $this->gender;
	}

	public function getLastName(): ?string
	{
		return $this->lastName;
	}

	public function getSecondName(): ?string
	{
		return $this->secondName;
	}

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function getExternalAuthId(): ?string
	{
		return $this->externalAuthId;
	}

	public function toArray(): array
	{
		return [
			'ID' => $this->getId(),
			'NAME' => $this->getName(),
			'SECOND_NAME' => $this->getSecondName(),
			'LAST_NAME' => $this->getLastName(),
			'GENDER' => $this->getGender(),
			'EXTERNAL_AUTH_ID' => $this->getExternalAuthId()
		];
	}

	public function toString(?string $template = null): string
	{
		$nameTemplate = $template ?? \CSite::GetNameFormat(false);

		return \CUser::FormatName(
			$nameTemplate,
			$this->toArray()
		);
	}
}