<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * THIS IS AN EXPERIMENTAL CLASS, DONT EXTEND
 *
 * @access private
 */

namespace Bitrix\Tasks\Manager\Task;

use Bitrix\Tasks\Integration;
use \Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Integration\SocialServices;
use Bitrix\Tasks\Util\Type;

abstract class Member extends \Bitrix\Tasks\Manager
{
	public static function getLegacyFieldName()
	{
		return '';
	}

	public static function adaptSet(array &$data)
	{
		$from = static::getCode(true);
		$to = static::getLegacyFieldName();

		if (array_key_exists($from, $data))
		{
			if (!is_array($data[$to] ?? null))
			{
				$data[$to] = [];
			}

			if (static::getIsMultiple())
			{
				$items = Type::normalizeArray($data[$from]);
				foreach ($items as $item)
				{
					$data[$to][] = (int)$item['ID'];
				}
			}
			else
			{
				$data[$to] = (int)$data[$from]['ID'];
			}
		}
	}

	public static function formatSet(array &$data)
	{
		$from = static::getLegacyFieldName();
		$to = static::getCode(true);

		if(static::getIsMultiple())
		{
			$items = (isset($data[$from]) && is_array($data[$from])) ? Type::normalizeArray($data[$from]) : [];
			foreach($items as $item)
			{
				$item = intval($item);
				if($item > 0)
				{
					$data[$to][] = array('ID' => $item);
				}
			}
		}
		else
		{
			$item = isset($data[$from]) ? intval($data[$from]) : 0;
			if($item > 0)
			{
				$data[$to] = array('ID' => $item);
			}
		}
	}

	public static function extendData(array &$data, array $knownMembers = array())
	{
		$code = static::getCode(true);

		if(!array_key_exists($code, $data))
		{
			static::formatSet($data);
		}

		if(static::getIsMultiple())
		{
			$data[$code] = (isset($data[$code]) && is_array($data[$code])) ? Type::normalizeArray($data[$code]) : [];
			foreach($data[$code] as $k => $item)
			{
				if(isset($knownMembers[$item['ID']]))
				{
					$data[$code][$k] = \Bitrix\Tasks\Util\User::extractPublicData($knownMembers[$item['ID']]);
				}
				else
				{
					// user might be is about to invite, do not erase
					//unset($data[$code][$k]);
				}
			}
		}
		else
		{
			if(isset($knownMembers[$data[$code]['ID']]))
			{
				$data[$code] = \Bitrix\Tasks\Util\User::extractPublicData($knownMembers[$data[$code]['ID']]);
			}
			else
			{
				// user might be is about to invite, do not erase
				//$data[$code] = array();
			}
		}
	}

	public static function inviteMembers(array &$data, Collection $errors)
	{
		$code = static::getCode(true);

		if(array_key_exists($code, $data) && is_array($data[$code]))
		{
			if(static::getIsMultiple())
			{
				foreach($data[$code] as $i => $user)
				{
					if(!intval($user['ID']))
					{
						if((string) $user['EMAIL'] != '' && \check_email($user['EMAIL']))
						{
							$newUserId = static::inviteUser($user, $errors);
							$data[$code][$i]['ID'] = $newUserId;
							Integration\SocialNetwork::setLogDestinationLast(['U' => [$newUserId]]);
						}
						elseif ($newUserId = static::addNetworkUser($user, $errors))
						{
							$data[$code][$i]['ID'] = $newUserId;
							Integration\SocialNetwork::setLogDestinationLast(['U' => [$newUserId]]);
						}
						else
						{
							unset($data[$code][$i]); // bad structure
						}
					}
				}
			}
			else
			{
				$user =& $data[$code];

				if((string) $user['EMAIL'] != '' && \check_email($user['EMAIL']))
				{
					$user['ID'] = static::inviteUser($user, $errors);
					Integration\SocialNetwork::setLogDestinationLast(['U' => [$user['ID']]]);
				}
				elseif ($newUserId = static::addNetworkUser($user, $errors))
				{
					$user['ID'] = $newUserId;
					Integration\SocialNetwork::setLogDestinationLast(['U' => [$newUserId]]);
				}
				else
				{
					$user = array(); // bad structure
				}
			}
		}
	}

	private static function inviteUser($user, Collection $errors)
	{
		$newId = \Bitrix\Tasks\Integration\Mail\User::create($user);
		if($newId)
		{
			return $newId;
		}
		else
		{
			$errors->add('USER_INVITE_FAIL', 'User has not been invited');
		}

		return false;
	}

	private static function addNetworkUser($user, Collection $errors)
	{
		if(!SocialServices\User::isNetworkId($user['ID']))
		{
			return 0;
		}
		
		return SocialServices\User::create($user, $errors);
	}
}