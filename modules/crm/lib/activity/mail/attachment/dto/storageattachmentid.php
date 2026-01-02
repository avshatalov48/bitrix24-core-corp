<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

class StorageAttachmentId
{
	public function __construct(
		public readonly int $attachmentId,
		public readonly int $storageElementId,
	) {}
}