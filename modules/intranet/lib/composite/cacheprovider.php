<?
namespace Bitrix\Intranet\Composite;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\StaticCacheProvider;
use Bitrix\Main\Data\StaticHtmlCache;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Page\AssetMode;
use Bitrix\Main\UserTable;

//RegisterModuleDependences("main", "OnGetStaticCacheProvider", "intranet", "\Bitrix\Intranet\Composite\CacheProvider", "getObject");
class CacheProvider extends StaticCacheProvider
{
	function __construct()
	{
	}

	public static  function getObject()
	{
		return new self();
	}

	public function isCacheable()
	{
		global $USER;
		return is_object($USER) && $USER->IsAuthorized() && Option::get("intranet", "composite_enabled", "N") === "Y";
	}

	public function setUserPrivateKey()
	{
		global $USER;
		if ($USER->IsAuthorized())
		{
			\CHTMLPagesCache::setUserPrivateKey(self::getUserPrefix($USER->GetID()), 0);
		}
	}

	public function getCachePrivateKey()
	{
		global $USER;
		if (is_object($USER) && $USER->IsAuthorized())
		{
			return self::getUserPrefix($USER->GetID());
		}

		return null;
	}

	public function onBeforeEndBufferContent()
	{
		global $USER;
		if (is_object($USER) && $USER->IsAuthorized())
		{
			Asset::getInstance()->addString(
				"<script>(window.BX||top.BX).message({ 'USER_ID': '".$USER->GetID()."'});</script>",
				$unique = false,
				AssetLocation::AFTER_JS,
				AssetMode::ALL
			);
		}
	}

	public static function getUserPrefix($userId)
	{
		$userId = intval($userId);
		return "/".$userId."/".md5(\CMain::GetServerUniqID().$userId);
	}

	public static function deleteUserCache($userId = false, $sendHeader = true)
	{
		global $USER;

		if ($userId === false && is_object($USER) && $USER->IsAuthorized())
		{
			$userId = $USER->GetID();
		}

		$userId = intval($userId);
		if ($userId > 0)
		{
			$postfix = \CHTMLPagesCache::getSpaPostfix();
			$privateKey = self::getUserPrefix($userId);
			foreach ($postfix as $item)
			{
				$realPrivateKey = \CHTMLPagesCache::getRealPrivateKey($privateKey, $item);
				$staticCache = new StaticHtmlCache("/", null, $realPrivateKey);
				$staticCache->delete();
			}

			if ($sendHeader === true)
			{
				header("X-Bitrix-Composite-Delete:".md5(\CHTMLPagesCache::getHttpHost().$privateKey));
			}
		}
	}

	/**
	 * Removes all composite cache
	 * Be careful with this method
	 * @return void
	 */
	public static function deleteAllCache()
	{
		$users = UserTable::getList(array(
			"select" => array("ID"),
			"filter" => array(
				"=ACTIVE" => "Y",
				"=CONFIRM_CODE" => false,
				"!UF_DEPARTMENT" => false,
				"!=EXTERNAL_AUTH_ID" => array("replica", "email", "bot", "imconnector")
			)
		));

		while ($user = $users->fetch())
		{
			self::deleteUserCache($user["ID"], false);
		}

		header("X-Bitrix-Composite-Delete:All");
	}
}
