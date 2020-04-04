<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CExtranet;
use CPHPCache;
use CSocNetLogDestination;

Loc::loadMessages(__FILE__);

final class Destination
{
	public static function getSocNetDestination($userId, $selected = array())
	{
		if(!Loader::includeModule('socialnetwork'))
		{
			return array();
		}

		global $CACHE_MANAGER;

		if (!is_array($selected))
		{
			$selected = array();
		}

		if (method_exists('CSocNetLogDestination','getDestinationSort'))
		{
			$destination = array(
				'LAST' => array()
			);

			$lastDestination = CSocNetLogDestination::getDestinationSort(array(
				"DEST_CONTEXT" => "DISK_SHARE"
			));

			CSocNetLogDestination::fillLastDestination($lastDestination, $destination['LAST']);
		}
		else
		{
			$destination = array(
				'LAST' => array(
					'SONETGROUPS' => CSocNetLogDestination::getLastSocnetGroup(),
					'DEPARTMENT' => CSocNetLogDestination::getLastDepartment(),
					'USERS' => CSocNetLogDestination::getLastUser()
				)
			);
		}

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = 'dest_group_'.$userId;
		$cacheDir = '/disk/dest/'.$userId;

		$cache = new CPHPCache;
		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$destination['SONETGROUPS'] = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$destination['SONETGROUPS'] = CSocNetLogDestination::getSocnetGroup(array('GROUP_CLOSED' => 'N', 'features' => array("files", array("view"))));
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->startTagCache($cacheDir);
				foreach($destination['SONETGROUPS'] as $val)
				{
					$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
				}
				$CACHE_MANAGER->registerTag("sonet_user2group_U".$userId);
				$CACHE_MANAGER->endTagCache();
			}
			$cache->endDataCache($destination['SONETGROUPS']);
		}

		$destUser = array();
		$destination['SELECTED'] = array();
		foreach ($selected as $ind => $code)
		{
			if (substr($code, 0 , 2) == 'DR')
			{
				$destination['SELECTED'][$code] = "department";
			}
			elseif (substr($code, 0 , 2) == 'UA')
			{
				$destination['SELECTED'][$code] = "groups";
			}
			elseif (substr($code, 0 , 2) == 'SG')
			{
				$destination['SELECTED'][$code] = "sonetgroups";
			}
			elseif (substr($code, 0 , 1) == 'U')
			{
				$destination['SELECTED'][$code] = "users";
				$destUser[] = str_replace('U', '', $code);
			}
		}

		// intranet structure
		$structure = CSocNetLogDestination::getStucture();
		//$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
		$destination['DEPARTMENT'] = $structure['department'];
		$destination['DEPARTMENT_RELATION'] = $structure['department_relation'];
		$destination['DEPARTMENT_RELATION_HEAD'] = $structure['department_relation_head'];

		if (Loader::includeModule('extranet') && !CExtranet::isIntranetUser())
		{
			$destination['EXTRANET_USER'] = 'Y';
			$destination['USERS'] = CSocNetLogDestination::getExtranetUser();
		}
		else
		{
			if ($destination['LAST']['USERS'])
			{
				foreach ($destination['LAST']['USERS'] as $value)
				{
					$destUser[] = str_replace('U', '', $value);
				}
			}

			$destination['EXTRANET_USER'] = 'N';
			$destination['USERS'] = CSocNetLogDestination::getUsers(array('id' => $destUser));
		}

		return $destination;
	}

	public static function getRightsDestination($userId, $selected = array())
	{
		if(!Loader::includeModule('socialnetwork'))
		{
			return array();
		}

		global $CACHE_MANAGER;

		if (!is_array($selected))
		{
			$selected = array();
		}

		if (method_exists('CSocNetLogDestination','getDestinationSort'))
		{
			$destination = array(
				'LAST' => array()
			);

			$lastDestination = CSocNetLogDestination::getDestinationSort(array(
				"DEST_CONTEXT" => "DISK_SHARE"
			));

			CSocNetLogDestination::fillLastDestination($lastDestination, $destination['LAST']);
		}
		else
		{
			$destination = array(
				'LAST' => array(
					'SONETGROUPS' => CSocNetLogDestination::getLastSocnetGroup(),
					'DEPARTMENT' => CSocNetLogDestination::getLastDepartment(),
					'USERS' => CSocNetLogDestination::getLastUser()
				)
			);
		}
		
		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = 'dest_group_'.$userId;
		$cacheDir = '/disk/dest_rights/'.$userId;

		$cache = new CPHPCache;
		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$destination['SONETGROUPS'] = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$destination['SONETGROUPS'] = CSocNetLogDestination::getSocnetGroup(array('features' => array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post"))));
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->startTagCache($cacheDir);
				foreach($destination['SONETGROUPS'] as $val)
				{
					$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
				}
				$CACHE_MANAGER->registerTag("sonet_user2group_U".$userId);
				$CACHE_MANAGER->endTagCache();
			}
			$cache->endDataCache($destination['SONETGROUPS']);
		}

		$destUser = array();
		$destination['SELECTED'] = array();
		foreach ($selected as $ind => $code)
		{
			if (substr($code, 0 , 2) == 'DR')
			{
				$destination['SELECTED'][$code] = "department";
			}
			elseif (substr($code, 0 , 2) == 'UA')
			{
				$destination['SELECTED'][$code] = "groups";
			}
			elseif (substr($code, 0 , 2) == 'AU')
			{
				$destination['SELECTED'][$code] = "groups";
				$destination['SELECTED']['UA'] = "groups";
			}
			elseif (substr($code, 0 , 2) == 'SG')
			{
				$destination['SELECTED'][$code] = "sonetgroups";
			}
			elseif (substr($code, 0 , 1) == 'U')
			{
				$destination['SELECTED'][$code] = "users";
				$destUser[] = str_replace('U', '', $code);
			}
		}

		// intranet structure
		$structure = CSocNetLogDestination::getStucture();
		//$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
		$destination['DEPARTMENT'] = $structure['department'];
		$destination['DEPARTMENT_RELATION'] = $structure['department_relation'];
		$destination['DEPARTMENT_RELATION_HEAD'] = $structure['department_relation_head'];

		if (Loader::includeModule('extranet') && !CExtranet::isIntranetUser())
		{
			$destination['EXTRANET_USER'] = 'Y';
			$destination['USERS'] = CSocNetLogDestination::getExtranetUser();
		}
		else
		{
			foreach ($destination['LAST']['USERS'] as $value)
				$destUser[] = str_replace('U', '', $value);

			$destination['EXTRANET_USER'] = 'N';
			$destination['USERS'] = CSocNetLogDestination::getUsers(array('id' => $destUser));
		}

		return $destination;
	}

	public static function getLocMessageToAllEmployees()
	{
		return Loc::getMessage('DISK_DESTINATION_TO_ALL_EMPLOYEES');
	}

	public static function getLocMessageToAllUsers()
	{
		return Loc::getMessage('DISK_DESTINATION_TO_ALL_USERS');
	}
}