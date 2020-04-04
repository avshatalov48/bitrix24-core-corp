<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @internal
 */

namespace Bitrix\Tasks\Item\Converter\Task;

use Bitrix\Tasks\Item\Converter;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\UI;

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
				'class' => Converter\Task\CheckList\ToTaskCheckList::getClass(),
			),
			'SE_TAG' => array(
				'class' => Converter\Stub::getClass(),
			),
			'SE_MEMBER' => array(
				'class' => Converter\Stub::getClass(),
			),
			'SE_PARAMETER' => array(
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
			'ALLOW_CHANGE_DEADLINE' => true,
			'ALLOW_TIME_TRACKING' => true,
			'TASK_CONTROL' => true,
			'MATCH_WORK_TIME' => true,
			'GROUP_ID' => true,
			'PARENT_ID' => true,
			'SITE_ID' => true,
			'DURATION_PLAN' => true,
			'DEADLINE' => true,
			'START_DATE_PLAN' => true,
			'END_DATE_PLAN' => true,

			'DEPENDS_ON' => true,
		));

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
}