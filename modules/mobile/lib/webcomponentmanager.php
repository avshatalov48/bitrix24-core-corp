<?
namespace Bitrix\Mobile;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

class WebComponentManager
{
	private static $webComponentPath = "/bitrix/components/bitrix/immobile.webcomponent/webcomponents/";
	public static function getWebComponentVersion($componentName)
	{
		$componentFolder = new Directory(Application::getDocumentRoot() . self::$webComponentPath . $componentName);
		$versionFile = new File($componentFolder->getPath() . "/version.php");
		if ($versionFile->isExists())
		{
			$versionDesc = include($versionFile->getPath());
			return $versionDesc["version"];
		}

		return 1;
	}

	public static function getWebComponentPath($componentName)
	{
		return "/mobile/web_mobile_component/$componentName/?version=" . self::getWebComponentVersion($componentName);
	}
}