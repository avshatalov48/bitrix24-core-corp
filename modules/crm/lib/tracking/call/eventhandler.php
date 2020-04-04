<?
namespace Bitrix\Crm\Tracking\Call;

use Bitrix\Main\Loader;
use Bitrix\Crm\Tracking;

/**
 * Class EventHandler
 *
 * @package Bitrix\Crm\Tracking\Call
 */
class EventHandler
{
	const PullTagCallEnd = 'tracking-call-end';
	/**
	 * Handler of VoxImplant call end event.
	 *
	 * @param array $data Event data.
	 * @return void
	 */
	public static function onCallEnd($data)
	{
		$numberFrom = !empty($data['PHONE_NUMBER']) ? $data['PHONE_NUMBER'] : null;
		$numberTo = !empty($data['PORTAL_NUMBER']) ? trim($data['PORTAL_NUMBER']) : null;

		Tracking\Call\Tester::updateStatus($numberTo);
		Tracking\Call\Tester::stop($numberFrom, $numberTo);
	}
}