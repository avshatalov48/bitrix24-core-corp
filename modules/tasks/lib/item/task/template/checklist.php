<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task\Template;

use Bitrix\Tasks\Internals\Task\Template\CheckListTable;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\UI;

/**
 * Class CheckList
 * @package Bitrix\Tasks\Item\Task\Template
 *
 * todo: implement here additional fields: IS_COMPLETE and SORT_INDEX, to be able to copy directly task checklist to template checklist
 */

final class CheckList extends \Bitrix\Tasks\Item\SubItem
{
	public static function getParentConnectorField()
	{
		return 'TEMPLATE_ID';
	}

	public static function getDataSourceClass()
	{
		return CheckListTable::getClass();
	}

	public static function getCollectionClass()
	{
		return \Bitrix\Tasks\Item\Task\Template\Collection\CheckList::getClass();
	}

	protected static function getParentClass()
	{
		return Template::getClass();
	}

	public static function findByParent($parentId, array $parameters = array(), $settings = null)
	{
		if(!array_key_exists('order', $parameters))
		{
			$parameters['order'] = array('SORT' => 'asc');
		}

		return parent::findByParent($parentId, $parameters, $settings);
	}

	public function getFieldTitleHTML()
	{
		return UI::convertBBCodeToHtmlSimple($this['TITLE']);
	}

	public function canToggle()
	{
		return $this->canUpdate();
	}

	public function isCompleted()
	{
		return !!$this['CHECKED'];
	}
}