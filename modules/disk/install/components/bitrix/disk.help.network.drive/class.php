<?php
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Driver;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CDiskHelpNetworkDriveComponent extends BaseComponent
{
	protected function processActionDefault()
	{
		$serverParams = array();
		$serverParams["AUTH_MODE"] = "DIGEST";
		$serverParams["SECURE"] = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || $_SERVER["SERVER_PORT"] == 443);
		$serverParams["CLIENT_OS"] = $this->getClientOS();

		$this->arResult["SERVER_PARAMS"] = $serverParams;
		$this->arResult["NETWORK_DRIVE_LINK"] = Driver::getInstance()->getUrlManager()->getHostUrl().$this->application->GetCurPage(false);
		$this->arResult["TEMPLATE_LINK"] = $this->application->GetCurPage();
		$this->arResult["USER_LOGIN"] = $this->getUser()->GetLogin();

		$this->includeComponentTemplate();
	}

	protected function getClientOS()
	{
		$clientOs = null;
		$client = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match("/(msie) ([0-9]{1,2}.[0-9]{1,3})/i", $client, $match))
		{
			$browser['name'] = "MSIE";
			$browser['version'] = $match[2];
		}
		if(preg_match("/linux/i", $client))
		{
			$clientOs = "Linux";
		}
		elseif(preg_match("/(windows nt)( ){0,1}([0-9]{1,2}.[0-9]{1,2}){0,1}/i", $client, $match))
		{
			if (isset($match[3]))
			{
				if ($match[3] == '5.0') $clientOs = "Windows 2000";
				elseif ($match[3] == '5.1') $clientOs = "Windows XP";
				elseif ($match[3] == '5.2') $clientOs = "Windows 2003";
				elseif ($match[3] == '6.0' && strpos($client, 'SLCC1') !== false) $clientOs = "Windows Vista";
				elseif ($match[3] == '6.0' && strpos($client, 'SLCC2') !== false) $clientOs = "Windows 2008";
				elseif ($match[3] == '6.0') $clientOs = "Windows Vista"; // may be 2008
				elseif ($match[3] == '6.1') $clientOs = "Windows 7";
				elseif ($match[3] == '6.2') $clientOs = "Windows 8";
				else $clientOs = "Windows";
			}
			else
			{
				$clientOs = "Windows";
			}
		}
		elseif(!!preg_match("/mac/i", $client) || !!preg_match("/darwin/i", $client))
		{
			$clientOs = "Mac";
		}
		return $clientOs;
	}
}