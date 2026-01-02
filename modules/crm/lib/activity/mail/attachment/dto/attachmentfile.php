<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

use Bitrix\Main\Type\Contract\Arrayable;

class AttachmentFile implements Arrayable
{
	public function __construct(
		public readonly string $name,
		public readonly string $type,
		public readonly string $content,
		public readonly int $attachmentId,
	) {}

	public function toArray(): array
	{
		return [
			'name' => $this->name,
			'type' => $this->type,
			'content' => $this->content,
			'MODULE_ID' => 'crm',
			'attachment_id' => $this->attachmentId,
		];
	}
}