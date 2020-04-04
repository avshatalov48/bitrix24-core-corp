<?
use Bitrix\Main;
use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$result = array("success" => false);
$request = Main\Application::getInstance()->getContext()->getRequest();

if (check_bitrix_sessid() && $USER->isAuthorized() && $request->getPost("templateId") && $request->getPost("siteId"))
{
	$theme = null;
	try
	{
		$theme = new ThemePicker($request->getPost("templateId"), $request->getPost("siteId"));
	}
	catch (ArgumentException $exception)
	{
		$result["error"] = $exception->getMessage();
	}

	if ($theme && $request->getPost("action") === "save")
	{
		$success = $theme->setCurrentThemeId($request->getPost("themeId"));
		if ($success && $request->getPost("setDefaultTheme") === "true" && ThemePicker::isAdmin())
		{
			$theme->setDefaultTheme($request->getPost("themeId"));
		}

		$result = array("success" => $success);

		if (Bitrix\Main\Loader::includeModule("intranet"))
		{
			Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
		}
	}
	else if ($theme && $request->getPost("action") === "getlist")
	{
		$result = array(
			"success" => true,
			"themes" => $theme->getList(),
			"baseThemes" => $theme->getBaseThemes()
		);
	}
	else if ($theme && $request->getPost("action") === "create")
	{
		try
		{
			$customThemeId = $theme->create(
				array(
					"bgColor" => $request->getPost("bgColor"),
					"bgImage" => $request->getFile("bgImage"),
					"textColor" => $request->getPost("textColor"),
				)
			);

			$result = array(
				"success" => true,
				"theme" => $theme->getCustomTheme($customThemeId)
			);
		}
		catch (Exception $e)
		{
			$result = array(
				"success" => false,
				"error" => $e->getMessage()
			);
		}
	}
	else if ($theme && $request->getPost("action") === "remove")
	{
		$success = $theme->remove($request->getPost("themeId"));

		$result = array(
			"success" => $success,
		);
	}
	else if ($theme && $request->getPost("action") === "hide-hint")
	{
		if (Bitrix\Main\Loader::includeModule("intranet"))
		{
			Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
		}

		$theme->hideHint();

		$result = array(
			"success" => true
		);
	}
}

echo Json::encode($result);

\CMain::FinalActions();
die();