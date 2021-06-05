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

		if (empty($options))
		{
			$options = self::autodetectContext();
		}

		$this->userId = $options['userId'] ?? $USER->getId();
		$this->siteId = $options['siteId'] ?? SITE_ID;
		$this->siteDir = $options['siteDir'] ?? SITE_DIR;
		$this->extranet = isset($options['extranet']) ? (bool)$options['extranet'] : false;
		$this->version = $options['version'] ?? '1';
	}

	public static function autodetectContext()
	{
		global $USER;

		if (!self::$defaultContext)
		{
			$siteId = SITE_ID;
			$siteDir = SITE_DIR;
			$isExtranetUser = false;

			if ($USER->isAuthorized())
			{
				$isExtranetModuleInstalled = \Bitrix\Main\Loader::includeModule('extranet');

				if ($isExtranetModuleInstalled)
				{
					$extranetSiteId = \CExtranet::getExtranetSiteId();
					if (!$extranetSiteId)
					{
						$isExtranetModuleInstalled = false;
					}
				}
				$users = \CUser::GetList(
					["last_name" => "asc", "name" => "asc"],
					'',
					[ 'ID' => $USER->GetID() ],
					[ 'SELECT' => [ 'UF_DEPARTMENT' ]]
				);
				$user = $users->fetch();
				$isExtranetUser = ($isExtranetModuleInstalled && (int)$user['UF_DEPARTMENT'][0] <= 0);

				if ($isExtranetUser)
				{
					$siteId = $extranetSiteId;
					$res = \CSite::getById($extranetSiteId);
					if(
						($extranetSiteFields = $res->fetch())
						&& ($extranetSiteFields['ACTIVE'] !== 'N')
					)
					{
						$siteDir = $extranetSiteFields['DIR'];
					}
				}
			}

			$arModuleVersion = [ 'VERSION' => 'default' ];
			include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/mobile/install/version.php');
			$moduleVersion = $arModuleVersion['VERSION'];
			if(array_key_exists('IS_WKWEBVIEW', $_COOKIE) && $_COOKIE['IS_WKWEBVIEW'] === "Y")
			{
				$moduleVersion .= '_wkwebview';
			}

			self::$defaultContext = [
				'extranet' => $isExtranetUser,
				'siteId' => $siteId,
				'siteDir' => $siteDir,
				'version' => $moduleVersion,
			];
		}

		return self::$defaultContext;
	}
}
