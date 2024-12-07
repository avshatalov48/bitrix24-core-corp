<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\Provider\ToDo\ToDo;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Entity\MessageBuilder\ProcessTodoActivity;
use Bitrix\Crm\Model\ActivityPingQueueTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\LogMessageController;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use CCrmActivity;
use CCrmDateTimeHelper;
use CCrmOwnerType;
use CIMNotify;

class PingAgent extends AgentBase
{
	private const DEADLINE_HIGH_BOUND_LENGTH = 20; // in seconds

	public static function doRun(): bool
	{
		$highBound = (new DateTime())->add('+' . self::DEADLINE_HIGH_BOUND_LENGTH . ' seconds');
		$result = ActivityPingQueueTable::getList([
			'select' => ['*'],
			'filter' => [
				'<=PING_DATETIME' => $highBound,
			],
			'order' => ['PING_DATETIME' => 'ASC']
		])->fetchAll();

		if (empty($result))
		{
			return true; // nothing to do
		}

		foreach ($result as $item)
		{
			$activity = ActivityTable::query()
				->where('ID', $item['ACTIVITY_ID'])
				->setSelect([
					'ID',
					'COMPLETED',
					'SUBJECT',
					'AUTHOR_ID',
					'RESPONSIBLE_ID',
					'DEADLINE',
				])
				->fetch()
			;

			$bindings = CCrmActivity::GetBindings($item['ACTIVITY_ID']);
			if (
				!is_array($activity)
				|| !(is_array($bindings) && !empty($bindings))
				|| static::isActivityPassed($activity)
			)
			{
				ActivityPingQueueTable::delete($item['ID']);

				continue;
			}

			$authorId = $activity['RESPONSIBLE_ID'] ?? null;
			$deadline = $activity['DEADLINE'] ?? null;
			if ($deadline && CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
			{
				$deadline = null;
			}

			foreach ($bindings as $binding)
			{
				static::addPing((int)$item['ACTIVITY_ID'], $item['PING_DATETIME'], $deadline, $binding, $authorId);
				static::sendNotification((int)$binding['OWNER_TYPE_ID'], (int)$binding['OWNER_ID'], $activity);

				ActivityPingQueueTable::delete($item['ID']);
			}
		}

		return true;
	}

	private static function isActivityPassed($activity): bool
	{
		if (!is_array($activity))
		{
			return true; // no activity
		}

		if (isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y')
		{
			return true;
		}

		return false;
	}

	private static function addPing(int $activityId, DateTime $created, ?DateTime $deadline, array $binding, ?int $authorId): void
	{
		$settings = [];
		if ($created && $deadline)
		{
			$settings['PING_OFFSET'] = $deadline->getTimestamp() - $created->getTimestamp();
		}

		LogMessageController::getInstance()->onCreate(
			[
				'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
				'ENTITY_ID' => $binding['OWNER_ID'],
				'ASSOCIATED_ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ASSOCIATED_ENTITY_ID' => $activityId,
				'CREATED' => $created,
				'SETTINGS' => $settings,
			],
			LogMessageType::PING,
			$authorId
		);
	}

	private static function sendNotification(int $ownerTypeId, int $ownerId, array $activity): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		if (empty($activity))
		{
			return;
		}

		$url = Container::getInstance()->getRouter()->getItemDetailUrl($ownerTypeId, $ownerId);
		if (!isset($url))
		{
			return;
		}

		$entityName = (string)$ownerId; // ID by default
		if (CCrmOwnerType::isUseFactoryBasedApproach($ownerTypeId))
		{
			$entityName = Container::getInstance()->getFactory($ownerTypeId)?->getItem($ownerId)?->getHeading();
		}

		$subject = ToDo::getActivityTitle($activity);

		$getMessageCallback = static fn (?Uri $url) =>
			(new ProcessTodoActivity($ownerTypeId))
				->getMessageCallback([
					'#subject#' => htmlspecialcharsbx($subject),
					'#url#' => $url,
					'#title#' => htmlspecialcharsbx(trim($entityName)),
					'#deadline#' => static::transformDateTime($activity['DEADLINE'] ?? null, $activity['RESPONSIBLE_ID'] ?? null),
				])
		;

		CIMNotify::Add([
			'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
			'TO_USER_ID' => $activity['RESPONSIBLE_ID'] ?? null,
			'FROM_USER_ID' => $activity['AUTHOR_ID'] ?? null,
			'NOTIFY_TYPE' => IM_NOTIFY_FROM,
			'NOTIFY_MODULE' => 'crm',
			'NOTIFY_EVENT' => 'pingTodoActivity',
			'NOTIFY_TAG' => 'CRM|PING_TODO_ACTIVITY|' . $activity['ID'],
			'NOTIFY_MESSAGE' => $getMessageCallback($url),
			'NOTIFY_MESSAGE_OUT' => $getMessageCallback(static::transformRelativeUrlToAbsolute($url)),
		]);
	}

	private static function transformDateTime(?DateTime $deadline, ?int $responsibleUserId): string
	{
		if ($deadline === null)
		{
			return '';
		}

		$culture = Application::getInstance()->getContext()->getCulture();
		$dateFormat = $culture?->getShortDateFormat();
		$timeFormat = $culture?->getShortTimeFormat();
		$datetimeFormat = $dateFormat . ' ' . $timeFormat;

		return CCrmDateTimeHelper::getUserTime($deadline, $responsibleUserId, true)
			->format($datetimeFormat)
		;
	}

	private static function transformRelativeUrlToAbsolute(Uri $url): Uri
	{
		$host = Application::getInstance()->getContext()->getRequest()->getServer()->getHttpHost();

		return (new Uri($url))->setHost($host);
	}
}
