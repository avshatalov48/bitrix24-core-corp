<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

class BannedAttachment
{
	public function __construct(
		public readonly string $name,
		public readonly int $size,
	) {}
}