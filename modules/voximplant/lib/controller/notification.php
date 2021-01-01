<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Controller;

class Notification extends Controller
{
	public function setNotificationSeenAction()
	{
		$ls = \Bitrix\Main\Application::getInstance()->getLocalSession('telephony_notification_free_plan');
		$ls->set('closed', 'Y');

		return null;
	}
}