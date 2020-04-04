<?
/**
 * Class implements all further interactions with "socialservices" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\SocialServices;

use Bitrix\Socialservices\Network;
use Bitrix\Tasks\Util\Error\Collection;

final class User extends \Bitrix\Tasks\Integration
{
	private static $network = null;
	private static $networkUsers = array();

	const MODULE_NAME = 'socialservices';

	public static function isNetworkId($id)
	{
		return preg_match("/^P\\d+(\\|\\d+)?$/", $id);
	}

	public static function create(array $data, Collection $errors = null)
	{
		if(!static::includeModule())
		{
			if($errors)
			{
				$errors->add('MODULE_NOT_INSTALLED', 'Module not installed: ' . static::MODULE_NAME);
			}
			return 0;
		}

		if (!self::$network)
		{
			self::$network = new Network();
		}

		if (!self::$network->isEnabled())
		{
			if($errors)
			{
				$errors->add('MODULE_NOT_INSTALLED', 'Module is not enabled');
			}
			return 0;
		}

		if (!isset(self::$networkUsers[$data['ID']]))
		{
			self::$networkUsers[$data['ID']] = self::$network->addUserById(preg_replace("/\\|[0-9]+\$/", "" , $data['ID']));
		}

		return self::$networkUsers[$data['ID']];
	}
}