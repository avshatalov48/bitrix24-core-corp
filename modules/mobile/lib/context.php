<?php
namespace Bitrix\Mobile;

class Context
{
	public $userId;
	public $extranet;
	public $siteId;
	public $siteDir;
	public $version;

	private static $defaultContext;

	public function __construct(array $options = [])
	{
		global $USER;

		if(empty($options))
		{
			$options = self::autodetectContext();
		}

		$this->userId = isset($options["userId"]) ? $options["userId"] : $USER->getId();
		$this->siteId = isset($options["siteId"]) ? $options["siteId"] : SITE_ID;
		$this->siteDir = isset($options["siteDir"]) ? $options["siteDir"] : SITE_DIR;
		$this->extranet = isset($options["extranet"]) ? (bool)$options["extranet"] : false;
		$this->version = isset($options["version"]) ? $options["version"] : "1";
	}


	public static function autodetectContext()
	{

		global $USER;
		if(!self::$defaultContext)
		{
			$isExtranetModuleInstalled = \Bitrix\Main\Loader::includeModule("extranet");
			$siteDir = SITE_DIR;
			if ($isExtranetModuleInstalled)
			{
				$extranetSiteId = \CExtranet::getExtranetSiteId();
				if (!$extranetSiteId)
				{
					$isExtranetModuleInstalled = false;
				}
			}
			$users = \CUser::GetList(
				($by = ["last_name" => "asc", "name" => "asc"]),
				($order = false),
				["ID" => $USER->GetID()],
				["SELECT"=>["UF_DEPARTMENT"]]
			);
			$user = $users->Fetch();
			$isExtranetUser = ($isExtranetModuleInstalled && intval($user["UF_DEPARTMENT"][0]) <= 0);
			$siteId = (
			$isExtranetUser
				? $extranetSiteId
				: SITE_ID
			);

			if ($isExtranetUser)
			{
				$res = \CSite::getById($extranetSiteId);
				if(
					($extranetSiteFields = $res->fetch())
					&& ($extranetSiteFields["ACTIVE"] != "N")
				)
				{
					$siteDir = $extranetSiteFields["DIR"];
				}
			}

			$moduleVersion = (defined("MOBILE_MODULE_VERSION") ? MOBILE_MODULE_VERSION : "default");
			if(array_key_exists("IS_WKWEBVIEW", $_COOKIE) && $_COOKIE["IS_WKWEBVIEW"] == "Y")
			{
				$moduleVersion .= "_wkwebview";
			}

			self::$defaultContext = [
				"extranet"=>$isExtranetUser,
				"siteId"=>$siteId,
				"siteDir"=>$siteDir,
				"version"=>$moduleVersion,
			];
		}

		return self::$defaultContext;
	}

}