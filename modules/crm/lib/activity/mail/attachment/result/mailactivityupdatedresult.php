<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Result;

use Bitrix\Main\Result;

class MailActivityUpdatedResult extends Result
{
	public function __construct(
		public readonly array $activityUpdatedFields
	)
	{
		parent::__construct();
	}
}