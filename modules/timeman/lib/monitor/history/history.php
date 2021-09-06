<?php
namespace Bitrix\Timeman\Monitor\History;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Model\Monitor\MonitorAbsenceTable;
use Bitrix\Timeman\Model\Monitor\MonitorCommentTable;
use Bitrix\Timeman\Model\Monitor\MonitorEntityTable;
use Bitrix\Timeman\Model\Monitor\MonitorUserLogTable;
use Bitrix\Timeman\Monitor\Constant\EntityType;
use Bitrix\Timeman\Monitor\Utils\User;

class History
{
	public static function getForPeriod(int $userId, Date $dateStart, Date $dateFinish): array
	{
		$query = MonitorUserLogTable::query();

		$query->setSelect([
			'TYPE' => 'entity.TYPE',
			'TITLE' => 'entity.TITLE',
			'ENTITY_ID',
			'TIME_SPEND',
			'COMMENT' => 'monitor_comment.COMMENT',
			'ABSENCE_TIME_START' => 'absence.TIME_START'
		]);

		$query->registerRuntimeField(new ReferenceField(
			'entity',
			MonitorEntityTable::class,
			Join::on('this.ENTITY_ID', 'ref.ID')
		));

		$query->registerRuntimeField(new ReferenceField(
			'monitor_comment',
			MonitorCommentTable::class,
			Join::on('this.ID', 'ref.USER_LOG_ID')
				->whereColumn('this.USER_ID', 'USER_ID')
		));

		$query->registerRuntimeField(new ReferenceField(
			'absence',
			MonitorAbsenceTable::class,
			Join::on('this.ID', 'ref.USER_LOG_ID')
		));

		$query->addFilter('=USER_ID', $userId);
		$query->whereBetween('DATE_LOG', $dateStart, $dateFinish);

		$history = $query->exec()->fetchAll();

		foreach ($history as $index => $entity)
		{
			if ($entity['ABSENCE_TIME_START'])
			{
				$history[$index]['TITLE'] =
					$entity['TITLE']
					. ' '
					. Loc::getMessage('TIMEMAN_MONITOR_HISTORY_FROM_TIME')
					. ' '
					. DateTime::createFromUserTime($entity['ABSENCE_TIME_START'])->format('H:i')
				;
			}
		}

		return $history;
	}

	public static function getOnDate(int $userId, Date $date): array
	{
		$query = MonitorUserLogTable::query();

		$query->setSelect([
			'type' => 'entity.TYPE',
			'title' => 'entity.TITLE',
			'entityId' => 'ENTITY_ID',
			'time' => 'TIME_SPEND',
			'comment' => 'monitor_comment.COMMENT',
			'timeStart' => 'absence.TIME_START',
			'desktopCode' => 'DESKTOP_CODE',
		]);

		$query->registerRuntimeField(new ReferenceField(
			'entity',
			MonitorEntityTable::class,
			Join::on('this.ENTITY_ID', 'ref.ID')
		));

		$query->registerRuntimeField(new ReferenceField(
			'monitor_comment',
			MonitorCommentTable::class,
			Join::on('this.ID', 'ref.USER_LOG_ID')
				->whereColumn('this.USER_ID', 'USER_ID')
		));

		$query->registerRuntimeField(new ReferenceField(
			'absence',
			MonitorAbsenceTable::class,
			Join::on('this.ID', 'ref.USER_LOG_ID')
		));

		$query->addFilter('=USER_ID', $userId);
		$query->addFilter('=DATE_LOG', $date);

		$query->addOrder('TIME_SPEND', 'DESC');

		$history = $query->exec()->fetchAll();

		foreach ($history as $index => $entity)
		{
			$history[$index]['time'] = (int)$history[$index]['time'];
			$history[$index]['entityId'] = (int)$history[$index]['entityId'];

			if ($entity['ABSENCE_TIME_START'])
			{
				$history[$index]['TITLE'] =
					$entity['TITLE']
					. ' '
					. Loc::getMessage('TIMEMAN_MONITOR_HISTORY_FROM_TIME')
					. ' '
					. DateTime::createFromUserTime($entity['ABSENCE_TIME_START'])->format('H:i')
				;
			}
		}

		return $history;
	}

	public static function record(array $queue): bool
	{
		if (!$queue)
		{
			return true;
		}

		foreach ($queue as $history)
		{
			if (is_array($history['historyPackage']))
			{
				$entities = self::getEntities($history);
				if ($entities)
				{
					$entitiesWithIds = Entity::record($entities);
					$history = self::addEntityIdsToHistory($entitiesWithIds, $history);
				}

				$history = UserLog::record($history);

				$comments = self::getComments($history);
				if ($comments)
				{
					Comment::record($comments);
				}

				$absence = self::getAbsence($history);
				if ($absence)
				{
					Absence::record($absence);
				}
			}

			//TODO: refactor, transfer only what is needed instead of the whole history
			if (is_array($history['chartPackage']))
			{
				UserChart::record($history);
			}

			if ($history['comment'] !== '')
			{
				ReportComment::record($history);
			}
		}

		return true;
	}

	public static function deleteForCurrentUser(string $dateLog, string $desktopCode): bool
	{
		$userId = User::getCurrentUserId();

		Absence::remove($userId, $dateLog, $desktopCode);

		Comment::remove($userId, $dateLog, $desktopCode);

		ReportComment::remove($userId, $dateLog, $desktopCode);

		UserLog::remove($userId, $dateLog, $desktopCode);

		UserChart::remove($userId, $dateLog, $desktopCode);

		return true;
	}

	private static function getEntities(array $history): array
	{
		$entities = [];
		foreach ($history['historyPackage'] as $entity)
		{
			$entities[] = [
				'TYPE' => $entity['type'],
				'TITLE' => $entity['title'],
				'PUBLIC_CODE' => $entity['publicCode']
			];
		}

		return $entities;
	}

	private static function getComments(array $history): array
	{
		$comments = [];
		foreach ($history['historyPackage'] as $entity)
		{
			if ($entity['comment'] === '' || $entity['comment'] === null)
			{
				continue;
			}

			$comments[] = [
				'USER_LOG_ID' => $entity['USER_LOG_ID'],
				'USER_ID' => User::getCurrentUserId(),
				'COMMENT' => $entity['comment'],
			];
		}

		return $comments;
	}

	private static function getAbsence(array $history): array
	{
		$absence = [];
		foreach ($history['historyPackage'] as $entity)
		{
			if ($entity['type'] !== EntityType::ABSENCE)
			{
				continue;
			}

			$absence[] = [
				'USER_LOG_ID' => $entity['USER_LOG_ID'],
				'TIME_START' => new DateTime($entity['timeStart'], \DateTimeInterface::RFC3339),
			];
		}

		return $absence;
	}

	private static function addEntityIdsToHistory(array $entitiesWithIds, array $history): array
	{
		foreach ($history['historyPackage'] as $index => $entry)
		{
			$entityWithIdIndex = array_search(
				$entry['publicCode'],
				array_column($entitiesWithIds, 'PUBLIC_CODE'),
				true
			);

			$history['historyPackage'][$index]['ENTITY_ID'] = $entitiesWithIds[$entityWithIdIndex]['ENTITY_ID'];
		}

		return $history;
	}
}