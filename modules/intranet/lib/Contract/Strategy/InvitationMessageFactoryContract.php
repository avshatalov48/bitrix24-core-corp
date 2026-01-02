<?php

namespace Bitrix\Intranet\Contract\Strategy;

use Bitrix\Intranet\Contract\SendableContract;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Service\EmailMessage;

interface InvitationMessageFactoryContract
{
	public function createEmailEvent(): SendableContract;

	public function createSmsEvent(): SendableContract;
}