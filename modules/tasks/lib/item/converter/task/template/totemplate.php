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
				'class' => Converter\Stub::getClass(),
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
			'ALLOW_CHANGE_DEADLINE' => true,
			'ALLOW_TIME_TRACKING' => true,
			'TASK_CONTROL' => true,
			'MATCH_WORK_TIME' => true,
			'GROUP_ID' => true,
			'PARENT_ID' => true,
			'SITE_ID' => true,
			'DEPENDS_ON' => true,
			'CREATED_BY' => true,
			'ACCOMPLICES' => true,
			'AUDITORS' => true,
			'RESPONSIBLES' => true,
			'DEADLINE_AFTER' => true,
			'START_DATE_PLAN_AFTER' => true,
			'END_DATE_PLAN_AFTER' => true,
			'BASE_TEMPLATE_ID' => true,
			'REPLICATE' => true,
			'REPLICATE_PARAMS' => true,
			'TPARAM_TYPE' => true
		));

		// do not spawn template with description in html format
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