<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task\Template;

use Bitrix\Tasks\Util\Type;

final class SystemLog extends \Bitrix\Tasks\Item\SubItem
{
	protected static function getParentConnectorField()
	{
		return 'TEMPLATE_ID';
	}

	public static function getDataSourceClass()
	{
		return '\\Bitrix\\Tasks\\Internals\\Task\\Template\\SystemLogTable';
	}

	public function externalizeFieldValue($name, $value)
	{
		if($name == 'DATA')
		{
			return Type::unSerializeArray($value);
		}

		return parent::externalizeFieldValue($name, $value);
	}

	public function internalizeFieldValue($name, $value)
	{
		if($name == 'DATA')
		{
			return Type::serializeArray($value);
		}

		return $value;
	}
}