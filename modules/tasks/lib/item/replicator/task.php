<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 *
 *
 *
 */

namespace Bitrix\Tasks\Item\Replicator;

class Task extends \Bitrix\Tasks\Item\Replicator
{
	protected static function getItemClass()
	{
		return '\\Bitrix\\Tasks\\Item\\Task';
	}

	protected static function getConverterClass()
	{
		return '\\Bitrix\\Tasks\\Item\\Converter\\Task\\ToTask';
	}
}