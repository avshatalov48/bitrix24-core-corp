<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Converter\Task\Template;

use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Item\Converter;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\User;

final class ToTask extends Converter
{
	public static function getTargetItemClass()
	{
		return Task::getClass();
	}

	protected static function getSubEntityConverterClassMap()
	{
		// only the following sub-entities will be converted
		return array(
			'SE_CHECKLIST' => array(
				'class' => Converter\Task\Template\CheckList\ToTaskCheckList::getClass(),
			),
			'SE_TAG' => array(
				'class' => Converter\Stub::getClass(),
			),
		);
	}

	protected function transformData(array $data, $srcInstance, $dstInstance, $result)
	{
		$newData = array_intersect_key($data, array(
			'TITLE' => true,
			'DESCRIPTION' => true,
			'DESCRIPTION_IN_BBCODE' => true,
			'PRIORITY' => true,
			'TIME_ESTIMATE' => true,
			'XML_ID' => true,
			'CREATED_BY' => true,
			'RESPONSIBLE_ID' => true,
			'REQUIRE_RESULT' => true,
			'ALLOW_CHANGE_DEADLINE' => true,
			'ALLOW_TIME_TRACKING' => true,
			'TASK_CONTROL' => true,
			'MATCH_WORK_TIME' => true,
			'GROUP_ID' => true,
			'PARENT_ID' => true,
			'SITE_ID' => true,

			'DEPENDS_ON' => true,
			'ACCOMPLICES' => true,
			'AUDITORS' => true,
		));

		$newData['FORKED_BY_TEMPLATE_ID'] = $srcInstance->getId();

		if(!intval($data['RESPONSIBLE_ID']) && count($data['RESPONSIBLES'])) // for broken templates
		{
			$newData['RESPONSIBLE_ID'] = $newData['RESPONSIBLES'][0];
		}

		$now = $this->getContext()->getNow();
		$matchWorkTime = $data['MATCH_WORK_TIME'] == 'Y';

		if((string) $data['DEADLINE_AFTER'] != '')
		{
			$newData['DEADLINE'] = static::getNewDate($matchWorkTime, $now, $data['DEADLINE_AFTER']);
		}

		if((string) $data['START_DATE_PLAN_AFTER'] != '')
		{
			$newData['START_DATE_PLAN'] = static::getNewDate($matchWorkTime, $now, $data['START_DATE_PLAN_AFTER']);
		}

		if((string) $data['END_DATE_PLAN_AFTER'] != '')
		{
			$newData['END_DATE_PLAN'] = static::getNewDate($matchWorkTime, $now, $data['END_DATE_PLAN_AFTER']);
		}

		// do not spawn tasks with description in html format
		if($data['DESCRIPTION_IN_BBCODE'] != 'Y')
		{
			if($data['DESCRIPTION'] != '')
			{
				$newData['DESCRIPTION'] = UI::convertHtmlToBBCode($data['DESCRIPTION']);
			}

			$newData['DESCRIPTION_IN_BBCODE'] = 'Y';
		}

		return $newData;
	}

	private static function getNewDate($matchWorkTime, $now, $seconds)
	{
		$seconds = intval($seconds);

		if (!$seconds)
		{
			return '';
		}

		if ($matchWorkTime)
		{
			return static::getDateMatchedWorkTime($now, $seconds);
		}
		else
		{
			return static::getDateAfter($now, $seconds);
		}
	}

	private static function getDateMatchedWorkTime(DateTime $now, $seconds)
	{
		$dateInst = DateTime::createFromUserTimeGmt($now);
		$calendar = Calendar::getInstance();

		$nextDate = $calendar->calculateEndDate($dateInst, $seconds);
		$clone = clone $nextDate;

		if (!$calendar->isWorkTime($clone))
		{
			$nextDate = $calendar->getClosestWorkTime($clone);
		}

		$nextDate = $nextDate->convertToLocalTime()->getTimestamp();
		$nextDate = DateTime::createFromTimestamp($nextDate - User::getTimeZoneOffsetCurrentUser());

		return $nextDate;
	}

	private static function getDateAfter(DateTime $now, $seconds)
	{
		$then = clone $now;
		$then->add('T'.$seconds.'S');
		$then->stripSeconds();

		return $then;
	}
}
