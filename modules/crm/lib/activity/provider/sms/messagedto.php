<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

final class MessageDto extends \Bitrix\Crm\Dto\Dto
{
	public ?string $senderId = null;
	public ?string $from = null;
	public ?string $to = null;
	public ?string $body = null;
	public ?string $template = null;
	public ?array $placeholders = null;
	public ?int $templateOriginalId = null;
}
