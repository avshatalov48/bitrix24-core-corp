<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

class MailActivityDescription
{
	public function __construct(
		public string $description,
		public bool $mayContainInlineFiles,
	) {}
}