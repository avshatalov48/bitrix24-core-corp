<?
/**
 * @access private
 */
namespace Bitrix\Tasks\Integration\Rest\Item;

final class UserField extends \Bitrix\Tasks\Integration\Rest\UserField
{
	public static function getTargetEntityId()
	{
		return 'TASKS_TASK';
	}
}