<?php

namespace Bitrix\Crm\Controller\Mail;

use Bitrix\Main\Engine\Controller;
use Bitrix\Crm\Activity\Mail\Message;

class ActivityMailMessage extends Controller
{
	public function getMessageNeighborsAction(int $ownerId, int $ownerTypeId, int $elementId, bool $requiredWebUrl = true): ?array
	{
		return Message::getNeighbors($ownerId, $ownerTypeId, $elementId, $requiredWebUrl);
	}
}