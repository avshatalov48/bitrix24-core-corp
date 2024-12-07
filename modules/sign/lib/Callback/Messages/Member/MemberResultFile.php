<?php

namespace Bitrix\Sign\Callback\Messages\Member;

use Bitrix\Sign\Callback;

class MemberResultFile extends Callback\Message
{
	public const Type = 'memberResultFile';

	protected array $data = [
		'documentUid' => '',
		'memberUid' => '',
		'securityCode' => '',
		'file' => [
			'content' => '',
			'name' => '',
			'contentType' => '',
		],
	];

	public function getDocumentUid(): string
	{
		return $this->data['documentUid'];
	}

	public function setDocumentUid(string $documentUid): self
	{
		$this->data['documentUid'] = $documentUid;
		return $this;
	}

	public function getMemberUid(): string
	{
		return $this->data['memberUid'];
	}

	public function setMemberUid(string $memberUid): self
	{
		$this->data['memberUid'] = $memberUid;
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
