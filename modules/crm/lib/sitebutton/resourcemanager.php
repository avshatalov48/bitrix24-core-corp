<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Uri;

/**
 * Class ResourceManager
 * @package Bitrix\Crm\SiteButton
 */
class ResourceManager
{
	public static function getServerAddress()
	{
		$server = Context::getCurrent()->getServer();
		$url = $server->getHttpHost();

		$canSave = !empty($url);
		$isRestored = false;

		if (!$url)
		{
			$url = Option::get('crm', 'last_site_button_res_url', null);
			if ($url)
			{
				$isRestored = true;
			}
			else
			{
				$url = $server->getServerName();
			}
		}

		if (!$isRestored)
		{
			if (mb_strpos($url, ':') === false && $server->getServerPort())
			{
				if (!in_array($server->getServerPort(), array('80', '443')))
				{
					$url .= ':' . $server->getServerPort();
				}
			}

			$url = (Context::getCurrent()->getRequest()->isHttps() ? "https" : "http")
				. "://" . $url;
		}

		$uri = new Uri($url);
		$url = $uri->getLocator();
		if (mb_substr($url, -1) == '/')
		{
			$url = mb_substr($url, 0, -1);
		}

		if ($canSave)
		{
			Option::set('crm', 'last_site_button_res_url', $url);
		}

		return $url;
	}

	protected static function getAgentName($providerFunction, $providerParams = array(), $providerModuleId = 'crm')
	{
		$params = var_export($providerParams, true);

		$agentName = $providerFunction . '(' . $params . ')';
		return '\\Bitrix\\Crm\\SiteButton\ResourceManager::uploadFileAgent(' . $agentName . ', "' . $providerModuleId . '");';
	}

	protected static function addAgent($providerFunction, $providerParams = array(), $providerModuleId = 'crm')
	{
		$agentName = self::getAgentName($providerFunction, $providerParams, $providerModuleId);
		\CAgent::AddAgent(
			$agentName,
			"crm", "N", 60, "", "Y",
			\ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL")
		);
	}
}