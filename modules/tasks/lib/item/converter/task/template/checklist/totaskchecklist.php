<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @internal
 */

namespace Bitrix\Tasks\Item\Converter\Task\Template\CheckList;

use Bitrix\Tasks\Item\Converter;
use Bitrix\Tasks\Item\Task\CheckList;

final class ToTaskCheckList extends Converter
{
	public static function getTargetItemClass()
	{
		return CheckList::getClass();
	}

	protected function transformData(array $data, $srcInstance, $dstInstance, $result)
	{
		return array(
			'TITLE' => $data['TITLE'],
			'IS_COMPLETE' => $this->checkYN($data['CHECKED']),
			'SORT_INDEX' => $data['SORT']
		);
	}

	private function checkYN($value)
	{
		return $value === 'Y' || $value === true || $value == '1' ? 'Y' : 'N';
	}
}