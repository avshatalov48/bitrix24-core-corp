<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @internal
 */

namespace Bitrix\Tasks\Item\Converter\Task\CheckList;

use Bitrix\Tasks\Item\Converter;
use Bitrix\Tasks\Item\Task\Template\CheckList;

final class ToTaskTemplateCheckList extends Converter
{
	public static function getTargetItemClass()
	{
		return CheckList::getClass();
	}

	protected function transformData(array $data, $srcInstance, $dstInstance, $result)
	{
		return array(
			'TITLE' => $data['TITLE'],
			'CHECKED' => $data['IS_COMPLETE'] == 'Y',
			'SORT' => $data['SORT_INDEX'],
		);
	}
}