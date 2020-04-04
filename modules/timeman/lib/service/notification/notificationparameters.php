<?php
namespace Bitrix\Timeman\Service\Notification;

class NotificationParameters
{
	public $messageType;
	public $fromUserId;
	public $toUserId;
	public $notifyType;
	public $notifyModule;
	public $notifyEvent;
	public $notifyTag;
	public $notifyMessage;
	public $notifyMessageOut;

	public function convertFieldsToArray()
	{
		return [
			'MESSAGE_TYPE' => $this->messageType,
			'FROM_USER_ID' => $this->fromUserId,
			'TO_USER_ID' => $this->toUserId,
			'NOTIFY_TYPE' => $this->notifyType,
			'NOTIFY_MODULE' => $this->notifyModule,
			'NOTIFY_EVENT' => $this->notifyEvent,
			'NOTIFY_TAG' => $this->notifyTag,
			'NOTIFY_MESSAGE' => $this->notifyMessage,
			'NOTIFY_MESSAGE_OUT' => $this->notifyMessageOut,
		];
	}
}