<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork\LogDestination;

Loc::loadMessages(__FILE__);

abstract class SocialNetwork extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'socialnetwork';

	private static $enabled = true;

	public static function enable()
	{
		static::$enabled = true;
	}
	public static function disable()
	{
		static::$enabled = false;
	}
	public static function isEnabled()
	{
		return static::$enabled;
	}

	public static function getUserEntityPrefix()
	{
		return 'U';
	}
	public static function getGroupEntityPrefix()
	{
		return 'SG';
	}
	public static function getDepartmentEntityPrefix()
	{
		return 'DR';
	}

    /**
     * Get data for user selector dialog
     *
     * @param string $context
     * @param array $parameters
     * @return array
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
	public static function getLogDestination($context = 'TASKS', array $parameters = array())
	{
		if(!static::includeModule())
		{
			return array();
		}

		return (new LogDestination($context, $parameters))->getData();
	}

    /**
     * Save last selected items in user selector dialog
     *
     * @param array $items
     * @param string $context
     */
	public static function setLogDestinationLast(array $items = array(), $context = 'TASKS')
	{
		if(!static::includeModule())
		{
			return;
		}

		$result = array();

		static::reformatLastItems($result, 'U', 'U', $items);
		static::reformatLastItems($result, 'SG', 'SG', $items);
		static::reformatLastItems($result, 'DR', 'DR', $items);

		// for compatibility
		static::reformatLastItems($result, 'USER', 'U', $items);
		static::reformatLastItems($result, 'SGROUP', 'SG', $items);

		\Bitrix\Main\FinderDestTable::merge(array(
			"CONTEXT" => $context,
			"CODE" => $result
		));
	}

	public static function getParser(array $parameters = array())
	{
		if(!static::includeModule())
		{
			return null;
		}

		static $parser;
		if($parser == null)
		{
			$parser = new \logTextParser(false, $parameters["PATH_TO_SMILE"]);
		}

		return $parser;
	}

	public static function formatDateTimeToGMT($time, $userId)
	{
		if(!static::includeModule())
		{
			return $time;
		}

		return \Bitrix\Socialnetwork\ComponentHelper::formatDateTimeToGMT($time, $userId);
	}

	private static function reformatLastItems(&$result, $from, $to, $items)
	{
		if(isset($items[$from]) && is_array($items[$from]))
		{
			$items[$from] = array_unique($items[$from]);
			foreach($items[$from] as $userId)
			{
				if(intval($userId))
				{
					$result[] = $to.$userId;
				}
			}
		}
	}

	public static function getMemberList($groupId)
	{
		self::includeModule();

		$out = array();
		try
		{
			$res = \Bitrix\Socialnetwork\UserToGroupTable::getList(
				array(
					'order' => array(
						'DATE_CREATE' => 'ASC'
					),
					'filter' => array(
						'GROUP_ID' => $groupId,
						'USER.ACTIVE' => 'Y',
						'@ROLE' => array(
							\Bitrix\Socialnetwork\UserToGroupTable::ROLE_MODERATOR,
							\Bitrix\Socialnetwork\UserToGroupTable::ROLE_USER
						)
					),
					'select' => array(
						'USER_ID',
						'USER_PERSONAL_PHOTO' => 'USER.PERSONAL_PHOTO',

						'USER_LAST_NAME' => 'USER.LAST_NAME',
						'USER_NAME' => 'USER.NAME',
						'USER_SECOND_NAME' => 'USER.SECOND_NAME',
						'USER_WORK_POSITION' => 'USER.WORK_POSITION'
					)
				)
			)->fetchAll();

			$users = [];
			foreach ($res as $item)
			{
				$users[] = $item['USER_ID'];
				$user = array(
					'ID'            => $item['USER_ID'],
					'PHOTO'         => self::getUserPictureSrc($item['USER_PERSONAL_PHOTO']),
					'USER_ID'       => $item['USER_ID'],
					//					'FORMATTED_NAME' => \Bitrix\Tasks\Util\User::getUserName($item['USER_ID']),
					'HREF'          => \CComponentEngine::MakePathFromTemplate(
						'/company/personal/user/#user_id#/',
						array('user_id' => $item['USER_ID'])
					),
					'WORK_POSITION' => $item['USER_WORK_POSITION'],
					'IS_HEAD'       => false
				);
				$out[$item['USER_ID']] = $user;
			}

			$names = \Bitrix\Tasks\Util\User::getUserName(array_unique($users));

			foreach ($users as $userId)
			{
				$out[$userId]['FORMATTED_NAME'] = $names[$userId];
			}


		}
		catch (\Bitrix\Main\ArgumentException $e)
		{
			dd($e);
		}

		return $out;
	}

	private static function getUserPictureSrc($photoId, $gender = '?', $width = 100, $height = 100)
	{
		static $cache = array();

		$key = $photoId.'.'.$width.'.'.$height;

		if (!array_key_exists($key, $cache))
		{
			$src = false;

			if ($photoId > 0)
			{
				$imageFile = \CFile::GetFileArray($photoId);
				if ($imageFile !== false)
				{
					$tmpImage = \CFile::ResizeImageGet(
						$imageFile,
						array("width" => $width, "height" => $height),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$src = $tmpImage["src"];
				}

				$cache[$key] = $src;
			}
		}

		return $cache[$key];
	}
}