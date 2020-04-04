<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Util\User;

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

		$destinationParams = array(
			'useProjects' => (isset($parameters['USE_PROJECTS']) && $parameters['USE_PROJECTS'] == 'Y'? 'Y' : 'N'),
			'CRM_ENTITY' => 'Y'
		);
		if(intval($parameters['AVATAR_HEIGHT']) && intval($parameters['AVATAR_WIDTH']))
		{
			$destinationParams['THUMBNAIL_SIZE_WIDTH'] = intval($parameters['AVATAR_WIDTH']);
			$destinationParams['THUMBNAIL_SIZE_HEIGHT'] = intval($parameters['AVATAR_HEIGHT']);
		}

		if(!is_object(User::get()))
		{
			throw new \Bitrix\Main\SystemException('Global user is not defined');
		}

		$userId = User::getId();

		$structure = \CSocNetLogDestination::GetStucture(array());
		$dataAdditional = array();
		$destination = array(
			"DEST_SORT" => \CSocNetLogDestination::GetDestinationSort(array(
				"DEST_CONTEXT" => $context,
				"ALLOW_EMAIL_INVITATION" => ModuleManager::isModuleInstalled("mail"),
			), $dataAdditional),
			"LAST" => array(
				"USERS" => array(),
				"SONETGROUPS" => array(),
				"PROJECTS" => array(),
				"DEPARTMENT" => array()
			),
			"DEPARTMENT" => $structure["department"],
			"DEPARTMENT_RELATION" => $structure["department_relation"],
			"DEPARTMENT_RELATION_HEAD" => $structure["department_relation_head"],
			/*
			"SELECTED" => array(
				"USERS" => array(User::getId())
			)
			*/
		);

		\CSocNetLogDestination::fillLastDestination(
			$destination["DEST_SORT"],
			$destination["LAST"],
			array(
				"EMAILS" => ModuleManager::isModuleInstalled("mail"),
				"PROJECTS" => (isset($parameters['USE_PROJECTS']) && $parameters['USE_PROJECTS'] == 'Y' ? 'Y' : 'N'),
				"DATA_ADDITIONAL" => $dataAdditional
			)
		);

		if (\Bitrix\Tasks\Integration\Extranet\User::isExtranet())
		{
			$destination["EXTRANET_USER"] = "Y";
			$destination["USERS"] = \CSocNetLogDestination::getExtranetUser($destinationParams);
		}
		else
		{
			$destUser = array();
			foreach ($destination["LAST"]["USERS"] as $value)
			{
				$destUser[] = str_replace("U", "", $value);
			}

			$destination["EXTRANET_USER"] = "N";
			$destination["USERS"] = \CSocNetLogDestination::getUsers(array_merge($destinationParams, array("id" => $destUser)));
			\CSocNetLogDestination::fillEmails($destination);
		}

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = "dest_project_".$userId.md5(serialize($parameters)).SITE_ID;
		$cacheDir = "/tasks/dest/".$userId;
		$cache = new \CPHPCache;
		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$cacheVars = $cache->getVars();
			$destination["SONETGROUPS"] = $cacheVars["SONETGROUPS"];
			$destination["PROJECTS"] = (isset($cacheVars["PROJECTS"]) ? $cacheVars["PROJECTS"] : array());
			$destination["SONETGROUPS_LIMITED"] = $cacheVars["SONETGROUPS_LIMITED"];
		}
		else
		{
			$cache->startDataCache();

			$limitReached = false;
			$destination["SONETGROUPS"] = \CSocNetLogDestination::getSocnetGroup(array_merge($destinationParams, array(
				"ALL" => "Y",
				"GROUP_CLOSED" => "N",
				"features" => array(
					"tasks", array("create_tasks")
				)
			)), $limitReached);

			if (isset($destination['SONETGROUPS']['PROJECTS']))
			{
				$destination['PROJECTS'] = $destination['SONETGROUPS']['PROJECTS'];
			}
			if (isset($destination['SONETGROUPS']['SONETGROUPS']))
			{
				$destination['SONETGROUPS'] = $destination['SONETGROUPS']['SONETGROUPS'];
			}

			$destination["SONETGROUPS_LIMITED"] = ($limitReached ? 'Y' : 'N');

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->startTagCache($cacheDir);
				$CACHE_MANAGER->registerTag("sonet_group");
				foreach($destination["SONETGROUPS"] as $val)
				{
					$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
				}
				if (!empty($destination['PROJECTS']))
				{
					foreach($destination["PROJECTS"] as $val)
					{
						$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
						$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
					}
				}
				$CACHE_MANAGER->registerTag("sonet_user2group_U".$userId);
				$CACHE_MANAGER->endTagCache();
			}
			$cache->endDataCache(array(
				"SONETGROUPS" => $destination["SONETGROUPS"],
				"PROJECTS" => $destination["PROJECTS"],
				"SONETGROUPS_LIMITED" => $destination["SONETGROUPS_LIMITED"]
			));
		}

		// add virtual department: extranet
		if (\Bitrix\Tasks\Integration\Extranet::isConfigured())
		{
			$destination['DEPARTMENT']['EX'] = array(
				'id' => 'EX',
				'entityId' => 'EX',
				'name' => Loc::getMessage("TASKS_INTEGRATION_EXTRANET_ROOT"),
				'parent' => 'DR0',
			);
			$destination['DEPARTMENT_RELATION']['EX'] = array(
				'id' => 'EX',
				'type' => 'category',
				'items' => array(),
			);
		}

		$destination['NETWORK_ENABLED'] = Option::get('tasks', 'network_enabled') == 'Y';
		$destination['SHOW_VACATIONS'] = ModuleManager::isModuleInstalled('intranet');
		if ($destination['SHOW_VACATIONS'])
		{
			$destination['USERS_VACATION'] = \Bitrix\Socialnetwork\Integration\Intranet\Absence\User::getDayVacationList();
		}

		$destination['CAN_ADD_MAIL_USERS'] = (
			ModuleManager::isModuleInstalled('mail')
		    && ModuleManager::isModuleInstalled('intranet')
		    && (
				!\Bitrix\Main\Loader::includeModule('bitrix24')
			    || \CBitrix24::isEmailConfirmed()
		    )
	    );

		return $destination;
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
		if(is_array($items[$from]))
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