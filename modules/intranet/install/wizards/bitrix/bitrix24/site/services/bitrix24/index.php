<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();



if(!Bitrix\Main\Loader::includeModule("bitrix24"))
{
	return;
}

//Dev Environment
$devUpdater = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bitrix24/dev/environment.php";
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/.dev") && file_exists($devUpdater))
{
	include($devUpdater);
}

// GOOGLE KEY FOR ADDRESS UF

\CBitrix24::setFilemanGoogleKey();
\CBitrix24::setLocationGoogleKey();

if(!Bitrix\Main\Loader::includeModule("rest"))
{
	return;
}

// APPLICATIONS

$appArray = \CBitrix24::getAppsForWizard();
if (!is_array($appArray) || empty($appArray))
{
	return;
}

if(!\Bitrix\Rest\OAuthService::getEngine()->isRegistered())
{
	try
	{
		\Bitrix\Rest\OAuthService::register();
	}
	catch(\Bitrix\Main\SystemException $e)
	{
	}
}

// INSTALL

if(\Bitrix\Rest\OAuthService::getEngine()->isRegistered())
{
	foreach($appArray as $app)
	{
		if(isset($app["MODULE_DEPENDENCY"]) && !empty($app["MODULE_DEPENDENCY"]))
		{
			foreach($app["MODULE_DEPENDENCY"] as $moduleId)
			{
				if(!IsModuleInstalled($moduleId))
				{
					continue 2;
				}
			}
		}

		if(isset($app["LANGUAGE_DEPENDENCY"]) && !empty($app["LANGUAGE_DEPENDENCY"]))
		{
			if(!in_array(CBitrix24::getLicensePrefix(), $app["LANGUAGE_DEPENDENCY"]))
			{
				continue;
			}
		}

		$result = \Bitrix\Rest\AppTable::add($app["INSTALL"]);
		if($result->isSuccess())
		{
			$ID = $result->getId();

			if(is_array($app['MENU_NAME']))
			{
				foreach($app['MENU_NAME'] as $lang => $menuName)
				{
					\Bitrix\Rest\AppLangTable::add(array(
						'APP_ID' => $ID,
						'LANGUAGE_ID' => $lang,
						'MENU_NAME' => trim($menuName)
					));
				}
			}

			if(is_array($app['OPTIONS']['CLEAR_CACHE']) && !empty($app['OPTIONS']['CLEAR_CACHE']) && defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				foreach($app['OPTIONS']['CLEAR_CACHE'] as $cacheTag)
				{
					$CACHE_MANAGER->ClearByTag($cacheTag);
				}
			}

			if(is_array($app['EXECUTE']) && !empty($app['EXECUTE']))
			{
				foreach($app['EXECUTE'] as $func)
				{
					call_user_func($func, Array("APP_ID" => $ID, "APP" => $app["INSTALL"]));
				}
			}
		}
	}
}

CAgent::AddAgent('\\Bitrix\\ImOpenLines\\Security\\Helper::installRolesAgent();', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));

wizardInstallImopenlinesChatApps();

/** @see \Bitrix\SalesCenter\Driver::installImApplicationAgent() */
\CAgent::AddAgent('\\Bitrix\\SalesCenter\\Driver::installImApplicationAgent();', 'salescenter', "N", 450, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+450, "FULL"));

// wizard functions

function wizardInstallBotGiphy($params)
{
	\Bitrix\Main\Loader::includeModule('imbot');
	\Bitrix\ImBot\Bot\Giphy::register(Array("APP_ID" => $params["APP"]["CLIENT_ID"]));
}
function wizardInstallBotProperties($params)
{
	\Bitrix\Main\Loader::includeModule('imbot');
	\Bitrix\ImBot\Bot\Properties::register(Array("APP_ID" => $params["APP"]["CLIENT_ID"]));
}
function wizardInstallBotPropertiesUa($params)
{
	\Bitrix\Main\Loader::includeModule('imbot');
	\Bitrix\ImBot\Bot\PropertiesUa::register(Array("APP_ID" => $params["APP"]["CLIENT_ID"]));
}

function wizardInstallImopenlinesChatApps()
{
	if (!\CModule::IncludeModule("im"))
	{
		return false;
	}

	$result = \Bitrix\Im\Model\AppTable::getList(Array(
		'filter' => Array('=MODULE_ID' => 'imopenlines', '=CODE' => 'quick')
	))->fetch();

	if (!$result)
	{
		\Bitrix\Im\App::register(Array(
			'MODULE_ID' => 'imopenlines',
			'BOT_ID' => 0,
			'CODE' => 'quick',
			'REGISTERED' => 'Y',
			'ICON_ID' => wizardChatAppsUploadIcon('quick'),
			'IFRAME' => '/desktop_app/iframe/imopenlines_quick.php',
			'IFRAME_WIDTH' => '512',
			'IFRAME_HEIGHT' => '234',
			'CONTEXT' => 'lines',
			'CLASS' => '\Bitrix\ImOpenLines\Chat',
			'METHOD_LANG_GET' => 'onAppLang',
		));
	}

	return true;
}

function wizardChatAppsUploadIcon($iconName)
{
	if ($iconName == '')
		return false;

	$iconId = false;
	if (\Bitrix\Main\IO\File::isFileExists(\Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imopenlines/install/icon/icon_'.$iconName.'.png'))
	{
		$iconId = \Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/imopenlines/install/icon/icon_'.$iconName.'.png';
	}

	if ($iconId)
	{
		$iconId = \CFile::SaveFile(\CFile::MakeFileArray($iconId), 'imopenlines');
	}

	return $iconId;
}
?>