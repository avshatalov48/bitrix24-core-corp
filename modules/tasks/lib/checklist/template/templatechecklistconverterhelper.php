<?php
namespace Bitrix\Tasks\CheckList\Template;

use Bitrix\Tasks\CheckList\Internals\CheckListConverterHelper;
use Bitrix\Tasks\Update\TemplateCheckListConverter;

/**
 * Class TemplateCheckListConverterHelper
 *
 * @package Bitrix\Tasks\CheckList\Template
 */
class TemplateCheckListConverterHelper extends CheckListConverterHelper
{
	protected static $facade = TemplateCheckListFacade::class;

	/**
	 * @return string
	 */
	protected static function getNeedOptionName()
	{
		return TemplateCheckListConverter::$needOptionName;
	}
}