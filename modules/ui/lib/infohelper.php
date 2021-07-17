<?
namespace Bitrix\UI;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\ModuleManager;
use Bitrix\ImBot\Bot\Partner24;

/**
 * Class InfoHelper
 * @package Bitrix\UI
 */
class InfoHelper
{
	public static function getInitParams()
	{
		return [
			'frameUrlTemplate' => self::getUrl(),
			'trialableFeatureList' => self::getTrialableFeatureList()
		];
	}

	public static function getUrl()
	{
		global $USER;

		$isBitrix24Cloud = Loader::includeModule("bitrix24");
		$notifyUrl = Util::getHelpdeskUrl()."/widget2/show/code/";
		$host = self::getHostName();

		$parameters = [
			"is_admin" => Loader::includeModule("bitrix24") && \CBitrix24::isPortalAdmin($USER->getId()) || !$isBitrix24Cloud && $USER->isAdmin() ? 1 : 0,
			"tariff" => Option::get("main", "~controller_group_name", ""),
			"is_cloud" => $isBitrix24Cloud ? "1" : "0",
			"host"  => $host,
			"languageId" => LANGUAGE_ID,
			"user_name" => Encoding::convertEncoding($USER->getFirstName(), SITE_CHARSET, 'utf-8'),
			"user_last_name" => Encoding::convertEncoding($USER->getLastName(), SITE_CHARSET, 'utf-8'),
		];
		if(Loader::includeModule('imbot'))
		{
			$parameters['support_partner_code'] = Partner24::getBotCode();
			$partnerName = Encoding::convertEncoding(Partner24::getPartnerName(), SITE_CHARSET, 'utf-8');
			$parameters['support_partner_name'] = $partnerName;
		}

		if (!$isBitrix24Cloud)
		{
			$parameters["head"] = md5("BITRIX".LICENSE_KEY."LICENCE");
			$parameters["key"] = md5($host.$USER->getId().$parameters["head"]);
		}
		else
		{
			$parameters["key"] = \CBitrix24::requestSign($host.$USER->getId());
		}

		return \CHTTP::urlAddParams($notifyUrl, $parameters, array("encode" => true));
	}

	private static function getTrialableFeatureList(): array
	{
		if (
			Loader::includeModule('bitrix24')
			&& method_exists(\Bitrix\Bitrix24\Feature::class, 'getTrialableFeatureList')
		)
		{
			return \Bitrix\Bitrix24\Feature::getTrialableFeatureList();
		}

		return [];
	}

	private static function getHostName()
	{
		if (ModuleManager::isModuleInstalled("bitrix24") && defined('BX24_HOST_NAME'))
		{
			return BX24_HOST_NAME;
		}

		$site = \Bitrix\Main\SiteTable::getList(array(
			'filter' => defined('SITE_ID') ? array('=LID' => SITE_ID) : array(),
			'order'  => array('ACTIVE' => 'DESC', 'DEF' => 'DESC', 'SORT' => 'ASC'),
			'select' => array('SERVER_NAME'),
			'cache'	 => array('ttl' => 86400)
		))->fetch();

		return $site['SERVER_NAME'] ?: Option::get('main', 'server_name', '');
	}
}

