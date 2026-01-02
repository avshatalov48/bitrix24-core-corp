<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

class AttachmentSaveResult
{
	public function __construct(
		public readonly BannedAttachmentCollection $bannedAttachments,
		public readonly StorageAttachmentIdCollection $storageAttachmentIds,
	) {}
}