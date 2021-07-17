<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Main\Engine\Controller;

/**
 * Class Notification
 * @package Bitrix\Crm\Controller\Timeline
 */
class Notification extends Controller
{
	/**
	 * @param int $messageId
	 * @param array $options
	 * @return array
	 */
	public function getMessageInfoAction(int $messageId, array $options = [])
	{
		return NotificationsManager::getMessageByInfoId($messageId, $options);
	}
}
