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

final class ToTemplate extends Converter
{
	public static function getTargetItemClass()
	{
		return Task\Template::getClass();
	}

	protected static function getSubEntityConverterClassMap()
	{
		// only the following sub-entities will be converted
		return array(
			'SE_CHECKLIST' => array(
				'class' => Converter\Task\CheckList\ToTaskTemplateCheckList::getClass(),
			),
			'SE_TAG' => array(
				'class' => Converter\Stub::getClass(),
			),
			// we do not inherit access rights, so do not mention SE_ACCESS here
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
			'REPLICATE' => true,
			'REPLICATE_PARAMS' => true,
			'MULTITASK' => true,
			'DEPENDS_ON' => true,
			'ACCOMPLICES' => true,
			'AUDITORS' => true,
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

		$newData['TASK_ID'] = $srcInstance->getId();

		return $newData;
	}
}