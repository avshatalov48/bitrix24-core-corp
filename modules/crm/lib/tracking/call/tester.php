<?
namespace Bitrix\Crm\Tracking\Call;

use Bitrix\Crm\Communication;
use Bitrix\Main\Loader;
use Bitrix\Crm\Tracking;

/**
 * Class Tester
 *
 * @package Bitrix\Crm\Tracking\Call
 */
class Tester
{
	const PullTagCallEnd = 'tracking-call-end';
	const PullCommandCallEnd = 'tracking-call-end';
	/**
	 * Start testing call.
	 *
	 * @return void
	 */
	public static function start()
	{
		if(!Loader::includeModule('pull'))
		{
			return;
		}

		global $USER;
		if (!is_object($USER))
		{
			return;
		}

		\CPullWatch::Add($USER->getID(), self::PullTagCallEnd);
	}

	public static function stop($numberFrom, $numberTo = null)
	{
		if (!$numberFrom)
		{
			return;
		}

		if(!Loader::includeModule('pull'))
		{
			return;
		}

		$numberTo = Communication\Normalizer::normalizePhone($numberTo);
		$numberFrom = Communication\Normalizer::normalizePhone($numberFrom);

		\CPullWatch::addToStack(
			self::PullTagCallEnd,
			array(
				'module_id' => 'crm',
				'command' => self::PullCommandCallEnd,
				'params' => [
					'numberFrom' => $numberFrom,
					'numberTo' => $numberTo,
				],
			)
		);
	}

	public static function updateStatus($numberTo)
	{
		if (!$numberTo)
		{
			return;
		}

		Tracking\Internals\PhoneNumberTable::appendNumber($numberTo);
	}
}