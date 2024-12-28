<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Flow\Notification\Config\Caption;
use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Notification\Config\Message;
use Bitrix\Tasks\Flow\Notification\Config\Recipient;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\Config\Where;

class Presets
{
	public function getAll(): array
	{
		return [
			// Notify responsible half-time before task expires
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_HALF_TIME_BEFORE_EXPIRE'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_HALF_TIME_BEFORE_EXPIRE'),
				new When(When::BEFORE_EXPIRE_HALF_TIME),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(RoleDictionary::ROLE_RESPONSIBLE),
				]
			),
			// Notify flow manager when queue is busy
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_BUSY_QUEUE'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_BUSY_QUEUE'),
				new When(When::SLOW_QUEUE, 10),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::FLOW_OWNER),
				]
			),
			// Notify flow manager when busy responsible
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_BUSY_RESPONSIBLE'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_BUSY_RESPONSIBLE'),
				new When(When::BUSY_RESPONSIBLE, 5),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::FLOW_OWNER),
				]
			),
			// Notify flow manager when efficiency is lower than
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_EFFICIENCY_LOWER'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_EFFICIENCY_LOWER'),
				new When(When::SLOW_EFFICIENCY, 70),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::FLOW_OWNER),
				]
			),
			// Notify flow manager when flow responsible queue is empty
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION'),
				new When(When::FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::FLOW_OWNER),
				]
			),
			// Notify flow manager when all users in flows' responsible queue are absent
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT'),
				new When(When::FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::FLOW_OWNER),
				]
			),
			// Notify flow manager when flow manual distributor was fired
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE'),
				new When(When::FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::FLOW_OWNER),
				]
			),
			// Notify flow manager when flow manual distributor forcibly absent
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT'),
				new When(When::FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::FLOW_OWNER),
				]
			),
			// Notify himself distribution flow manager when task wasn't taken
			new Item(
				new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_HIMSELF_ADMIN_TASK_NOT_TAKEN'),
				new Message('TASKS_FLOW_NOTIFICATION_MESSAGE_HIMSELF_ADMIN_TASK_NOT_TAKEN'),
				new When(When::HIMSELF_FLOW_TASK_NOT_TAKEN),
				new Where(Where::NOTIFICATION_CENTER),
				[
					new Recipient(Recipient::TASK_FLOW_OWNER),
				]
			),
		];
	}

	public function getItemByCaption(Caption $caption, int $offset = 0): ?Item
	{
		foreach ($this->getAll() as $preset)
		{
			if ($preset->getCaption()->getValue() === $caption->getValue())
			{
				return new Item(
					$preset->getCaption(),
					$preset->getMessage(),
					new When($preset->getWhen()->getType(), $offset),
					$preset->getWhere(),
					$preset->getRecipients(),
				);
			}
		}

		return null;
	}
}