<?php

namespace Bitrix\Sign\Callback\Messages;

use Bitrix\Sign\Callback;

class ResultFile extends Callback\Message
{
	public const Type = 'ResultFile';

	protected array $data = [
		'documentCode' => '',
		'securityCode' => '',
		'file' => [
			'content' => '',
			'name' => '',
			'contentType' => '',
		],
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

	public function getSecurityCode(): string
	{
		return $this->data['securityCode'];
	}

	public function setSecurityCode(string $securityCode): self
	{
		$this->data['securityCode'] = $securityCode;
		return $this;
	}
}
