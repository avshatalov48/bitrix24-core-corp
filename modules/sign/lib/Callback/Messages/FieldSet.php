<?php

namespace Bitrix\Sign\Callback\Messages;

use Bitrix\Sign\Callback;

class FieldSet extends Callback\Message
{
	public const Type = 'FieldSet';
	protected array $data = [
		'fields' => [],
		'documentCode' => '',
		'memberCode' => '',
		'securityCode' => '',
		'version' => 1,
	];

	public function getDocumentCode(): string
	{
		return $this->data['documentCode'];
	}

	public function setDocumentCode(string $documentCode): static
	{
		$this->data['documentCode'] = $documentCode;

		return $this;
	}

	public function getMemberCode(): string
	{
		return $this->data['memberCode'];
	}

	public function setMemberCode(string $memberCode): static
	{
		$this->data['memberCode'] = $memberCode;

		return $this;
	}

	public function getSecurityCode(): string
	{
		return $this->data['securityCode'];
	}

	public function setSecurityCode(string $securityCode): static
	{
		$this->data['securityCode'] = $securityCode;

		return $this;
	}

	public function getFields(): array
	{
		return $this->data['fields'];
	}

	public function setFields(array $fields): static
	{
		$this->data['fields'] = $fields;

		return $this;
	}

	public function getVersion(): int
	{
		return $this->data['version'];
	}

	public function setVersion(int $version): static
	{
		$this->data['version'] = $version;

		return $this;
	}

}