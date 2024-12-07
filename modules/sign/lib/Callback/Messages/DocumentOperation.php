<?php

namespace Bitrix\Sign\Callback\Messages;

use Bitrix\Sign\Callback;

class DocumentOperation extends Callback\Message
{
	public const Type = 'DocumentOperation';
	protected array $data = [
		'operationCode' => '',
		'operationEventType' => '',
		'documentCode' => '',
		'memberCode' => '',
		'securityCode' => '',
		'version' => 1,
	];

	public function getDocumentCode(): string
	{
		return $this->data['documentCode'];
	}

	public function setDocumentCode(string $documentCode): self
	{
		$this->data['documentCode'] = $documentCode;
		return $this;
	}

	public function getMemberCode(): string
	{
		return $this->data['memberCode'];
	}

	public function setMemberCode(string $memberCode): self
	{
		$this->data['memberCode'] = $memberCode;
		return $this;
	}

	public function getSecurityCode(): string
	{
		return $this->data['securityCode'];
	}

	public function setSecurityCode(string $securityCode): self
	{
		$this->data['securityCode'] = $securityCode;
		return $this;
	}

	public function getOperationCode()
	{
		return $this->data['operationCode'];
	}

	public function setVersion(int $version): self
	{
		$this->data['version'] = $version;
		return $this;
	}

	public function getVersion(): int
	{
		return $this->data['version'];
	}
}
