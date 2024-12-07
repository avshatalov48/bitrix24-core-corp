<?php

namespace Bitrix\Sign\Callback\Messages\Mobile;

use Bitrix\Sign\Callback;

final class SigningConfirm extends Callback\Message
{
	public const Type = 'SigningConfirm';

	protected array $data = [
		'provider' => '',
		'memberUid' => '',
		'documentUid' => '',
		'signLinkUri' => '',
		'providerExtraData' => [],
	];

	public function getMemberUid(): string
	{
		return $this->data['memberUid'];
	}

	public function getDocumentUid(): string
	{
		return $this->data['documentUid'];
	}
}
