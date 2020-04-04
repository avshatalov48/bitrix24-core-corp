<?
namespace Bitrix\Crm\Integration\VoxImplant;

use Bitrix\Crm\Tracking;

/**
 * Class EventHandler of VoxImplant events
 *
 * @package Bitrix\Crm\Integration\VoxImplant
 */
class EventHandler
{
	/**
	 * Handler of call end event.
	 *
	 * @param array $data Event data.
	 * @return void
	 */
	public static function onCallEnd($data)
	{
		Tracking\Call\EventHandler::onCallEnd($data);
	}
}