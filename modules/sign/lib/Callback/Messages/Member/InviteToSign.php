<?php

namespace Bitrix\Sign\Callback\Messages\Member;

use Bitrix\Sign\Callback;

final class InviteToSign extends Callback\Message
{
	public const Type = 'InviteToSign';

	protected array $data = [
		'provider' => '',
		'memberUid' => '',
		'documentUid' => '',
		'signLinkUri' => '',
		'providerExtraData' => [],
		'secondarySigning' => false,
	];

	public function getMemberUid(): string
	{
		return $this->data['memberUid'];
	}

	public function getDocumentUid(): string
	{
		return $this->data['documentUid'];
	}

	public function getSignLinkUri(): string
	{
		return $this->data['signLinkUri'];
	}

	public function getProvider(): string
	{
		return $this->data['provider'];
	}

	public function getProviderExtraData(): array
	{
		return $this->data['providerExtraData'] ?? [];
	}

	public function isSecondarySigning(): bool
	{
		return $this->data['secondarySigning'];
	}
}
