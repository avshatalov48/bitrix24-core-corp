<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

final class SenderExtra
{
	public const SENT_MESSAGE_TAG_GROUP_WHATSAPP_MESSAGE = 'GROUP_WHATSAPP_MESSAGE';

	public function __construct(
		public readonly ?string $sentMessageTag = null
	)
	{
	}
}