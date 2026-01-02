<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

class AttachmentFilesResult
{
	public function __construct(
		public readonly AttachmentFileCollection $files,
		public readonly BannedAttachmentCollection $bannedAttachments,
	) {}
}