<?php

namespace Bitrix\Crm\Activity\Mail\Attachment;

use Bitrix\Main\Application;

class MailActivityAttachmentLock
{
	public function __construct(
		private readonly int $activityId,
	)
	{}

	public function lock(): bool
	{
		return Application::getConnection()->lock($this->getLockName());
	}

	public function release(): void
	{
		Application::getConnection()->unlock($this->getLockName());
	}

	private function getLockName(): string
	{
		return "crm_mail_activity_attachment_load_$this->activityId";
	}
}