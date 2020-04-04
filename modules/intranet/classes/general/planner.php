<?
IncludeModuleLangFile(__FILE__);

class CIntranetPlanner
{
	const CACHE_TTL = 86400;
	const CACHE_TAG = 'intranet_planner_';
	const JS_CORE_EXT_RANDOM_NAME = 'planner_handler_';

	public static function getData($SITE_ID = SITE_ID, $bFull = false, $userId = null)
	{
		global $USER, $CACHE_MANAGER;

		if (!$userId)
		{
			if (!is_object($USER))
			{
				return false;
			}
			$userId = $USER->GetID();
		}

		$obCache = new CPHPCache();

		$today = ConvertTimeStamp();

		$cache_dir = '/intranet/planner/'.$userId;
		$cache_id = 'intranet|planner|'.$userId.'|'.$SITE_ID.'|'.intval($bFull).'|'.$today.'|'.FORMAT_DATETIME.'|'.FORMAT_DATE.'|'.LANGUAGE_ID;

		$arData = null;

		if ($obCache->InitCache(self::CACHE_TTL, $cache_id, $cache_dir))
		{
			$arData = $obCache->GetVars();

			if(is_array($arData['SCRIPTS']))
			{
				foreach($arData['SCRIPTS'] as $key => $script)
				{
					if(is_array($script))
					{
						$arData['SCRIPTS'][$key] = self::JS_CORE_EXT_RANDOM_NAME.RandString(5);
						CJSCore::RegisterExt($arData['SCRIPTS'][$key], $script);
					}
				}
			}
		}
		else
		{
			// cache expired or there's no cache
			$obCache->StartDataCache();

			$arData = array(
				'SCRIPTS' => array(),
				'STYLES' => array(),
				'DATA' => array()
			);

			$CACHE_MANAGER->StartTagCache($cache_dir);
			$CACHE_MANAGER->RegisterTag(self::CACHE_TAG.$userId);

			$events = GetModuleEvents("intranet", "OnPlannerInit");
			while($arEvent = $events->Fetch())
			{
				$arEventData = ExecuteModuleEventEx(
					$arEvent,
					array(
						array(
							'SITE_ID' => SITE_ID,
							'FULL' => $bFull,
							'USER_ID' => $userId
						)
					)
				);


				if(is_array($arEventData))
				{
					if(is_array($arEventData['SCRIPTS']))
						$arData['SCRIPTS'] = array_merge($arData['SCRIPTS'], $arEventData['SCRIPTS']);
					if(is_array($arEventData['STYLES']))
						$arData['STYLES'] = array_merge($arData['STYLES'], $arEventData['STYLES']);
					if(is_array($arEventData['DATA']))
						$arData['DATA'] = array_merge($arData['DATA'], $arEventData['DATA']);
				}
			}

			$arCacheData = $arData;

			if(is_array($arCacheData['SCRIPTS']))
			{
				foreach($arCacheData['SCRIPTS'] as $key => $script)
				{
					if(CJSCore::IsExtRegistered($script))
					{
						$arCacheData['SCRIPTS'][$key] = CJSCore::getExtInfo($script);
					}
				}
			}

			$CACHE_MANAGER->EndTagCache();
			$obCache->EndDataCache($arCacheData);
		}

		return $arData;
	}

	public static function initScripts($arData)
	{
		global $APPLICATION;

		$arExt = array('planner');
		$arScripts = array();

		if(is_array($arData['SCRIPTS']))
		{
			foreach($arData['SCRIPTS'] as $script)
			{
				if(CJSCore::IsExtRegistered($script))
				{
					$arExt[] = $script;
				}
				else
				{
					$arScripts[] = $script;
				}
			}
		}

		if(is_array($arData['STYLES']))
		{
			foreach($arData['STYLES'] as $style)
			{
				$APPLICATION->SetAdditionalCSS($style);
			}
		}

		\Bitrix\Main\Page\Asset::getInstance()->addJsKernelInfo('calendar_planner_handler', array('/bitrix/js/calendar/core_planner_handler.js'));
		\Bitrix\Main\Page\Asset::getInstance()->addCssKernelInfo('calendar_planner_handler', array('/bitrix/js/calendar/core_planner_handler.css'));

		CJSCore::Init($arExt);
		foreach ($arScripts as $script)
		{
			$APPLICATION->AddHeadScript($script);
		}
	}

	public static function callAction($action, $site_id)
	{
		global $USER, $CACHE_MANAGER;

		$res = array();

		$events = GetModuleEvents("intranet", "OnPlannerAction");
		while($arEvent = $events->Fetch())
		{
			$eventRes = ExecuteModuleEventEx(
				$arEvent,
				array(
					$action,
					array(
						'SITE_ID' => $site_id
					)
				)
			);


			if(is_array($eventRes))
			{
				$res = array_merge($res, $eventRes);
			}
		}

		$CACHE_MANAGER->ClearByTag(self::CACHE_TAG.$USER->GetID());

		return $res;
	}
}
?>