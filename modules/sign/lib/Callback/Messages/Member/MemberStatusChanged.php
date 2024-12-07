<?php

namespace Bitrix\Sign\Callback\Messages\Member;

use Bitrix\Sign\Callback;

final class MemberStatusChanged extends Callback\Message
{
	public const Type = 'memberStatusChanged';

	protected array $data = [
		'memberUid' => '',
		'documentUid' => '',
		'status' => '',
		'errorCode' => '',
		'sesUsername' => '',
		'sesSign' => '',
		'provider' => '',
		'initiatorUid' => '',
	];

	public function getMemberUid(): string
	{
		return $this->data['memberUid'] ?? '';
	}

	public function getDocumentUid(): string
	{
		return $this->data['documentUid'] ?? '';
	}

	public function getStatus(): string
	{
		return $this->data['status'] ?? '';
	}

	public function getErrorCode(): string
	{
		return $this->data['errorCode'] ?? '';
	}

	public function getSesUsername(): string
	{
		return $this->data['sesUsername'] ?? '';
	}

	public function getSesSign(): string
	{
		return $this->data['sesSign'] ?? '';
	}

	public function getProvider(): string
	{
		return $this->data['provider'] ?? '';
	}

	public function getInitiatorUid(): string
	{
		return $this->data['initiatorUid'] ?? '';
	}
}
