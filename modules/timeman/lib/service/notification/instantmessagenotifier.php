<?php
namespace Bitrix\Timeman\Service\Notification;

class InstantMessageNotifier
{
	public function sendMessage(NotificationParameters $notificationParameters)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return false;
		}
		return \CIMMessenger::add($notificationParameters->convertFieldsToArray());
	}
}