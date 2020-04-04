<?php
namespace Bitrix\Scale;
use Bitrix\Main\SiteDomainTable;

/**
 * Class SitesData
 * @package Bitrix\Scale *
 */
class SitesData
{
	/**
	 * @param $siteName
	 * @return array site's param
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getSite($siteName, $dbName = false)
	{
		if(strlen($siteName) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("siteName");

		$result = array();
		$sites = self::getList($dbName);

		if(isset($sites[$siteName]))
			$result = $sites[$siteName];

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getKernelSite()
	{
		foreach(self::getList() as $siteId => $siteParams)
			if($siteParams['SiteInstall'] == 'kernel')
				return $siteId;

		return '';
	}

	/**
	 * @return array
	 */
	public static function getKernelsList()
	{
		$result = array();

		foreach(self::getList() as $siteId => $siteParams)
			if($siteParams['SiteInstall'] == 'kernel')
				$result[$siteId] = isset($siteParams['NAME']) ? $siteParams['NAME'] : $siteId;

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getKernelRoot()
	{
		foreach(self::getList() as $siteId => $siteParams)
			if($siteParams['SiteInstall'] == 'kernel')
				return $siteParams['DocumentRoot'];

		return '';
	}

	/**
	 * @param string $dbName
	 * @return array List of all sites & their params
	 */
	public static function getList($dbName = false)
	{
		static $hitCache = null;

		if($hitCache === null)
		{
			$resSite = array();
			$shellAdapter = new ShellAdapter();
			$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-sites -o json -a list --hiden");
			$sitesData = $shellAdapter->getLastOutput();

			if($execRes)
			{
				$arData = json_decode($sitesData, true);

				if(isset($arData["params"]))
					$resSite = $arData["params"];

				$domains = array();
				$sdRes = SiteDomainTable::getList();

				while($dom = $sdRes->fetch())
				{
					if(isset($domains[$dom['LID']]))
						$domains[$dom['LID']] .= ', ';
					else
						$domains[$dom['LID']] = '';

					$domains[$dom['LID']] .= $dom['DOMAIN'];
				}

				$rsSite = \Bitrix\Main\SiteTable::getList();

				while ($site = $rsSite->fetch())
				{
					foreach($resSite as $siteId => $siteInfo)
					{
						$docRoot = strlen($site["DOC_ROOT"]) > 0 ? $site["DOC_ROOT"] : \Bitrix\Main\Application::getDocumentRoot();

						if($siteInfo["DocumentRoot"] == $docRoot)
						{
							$resSite[$siteId]["NAME"] = $site["NAME"]." (".$site["LID"].") ";
							$resSite[$siteId]["LID"] = $site["LID"];
							$resSite[$siteId]["EMAIL"] = $site["EMAIL"];
							$resSite[$siteId]["DOMAINS"] = isset($domains[$site["LID"]]) ? $domains[$site["LID"]] : '';
						}
						else
						{
							$resSite[$siteId]["NAME"] = $siteId;
						}

						$resSite[$siteId]["SMTP_USE_AUTH"] = ($siteInfo['SMTPPassword'] !== null && $siteInfo['SMTPUser'] !== null) ? 'Y' : 'N';
					}
				}
			}

			$hitCache = $resSite;
		}

		if($dbName != false && !empty($hitCache))
		{
			$result = array();

			foreach($hitCache as $siteId => $siteInfo)
				if($siteInfo['DBName'] == $dbName)
					$result[$siteId] = $siteInfo;
		}
		else
		{
			$result = $hitCache;
		}

		return $result;
	}
}