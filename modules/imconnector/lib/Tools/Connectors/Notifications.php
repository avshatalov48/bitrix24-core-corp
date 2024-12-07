<?php
namespace Bitrix\ImConnector\Tools\Connectors;

use Bitrix\Crm\SiteButton;
use Bitrix\ImConnector\Result;
use Bitrix\ImOpenlines\Preset;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Internals\AgreementTable;

use Bitrix\ImConnector\Limit;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Status;
use Bitrix\Main\Web\WebPacker\Builder;
use Bitrix\Notifications\FeatureStatus;
use Bitrix\Notifications\Settings;

class Notifications
{
	protected const CODE_TERMS_AGREEMENT = 'imconnector_terms_notifications_v2';
	protected const DATA_PROVIDER_CODE = 'imconnector/notifications';

	/**
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		if (!Loader::includeModule('notifications'))
		{
			return false;
		}

		return
			Settings::getScenarioAvailability(Settings::SCENARIO_CRM_PAYMENT) !== FeatureStatus::UNAVAILABLE
			|| Settings::getScenarioAvailability(Settings::SCENARIO_VIRTUAL_WHATSAPP) !== FeatureStatus::UNAVAILABLE
		;
	}

	/**
	 * @return bool
	 */
	public function canUse(): bool
	{
		return Loader::includeModule('notifications') && Limit::canUseConnector('notifications');
	}

	//region Agreement Terms

	/**
	 * @return int
	 */
	protected static function getIdAgreementTerms(): int
	{
		$agreementRow = AgreementTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=CODE' => self::CODE_TERMS_AGREEMENT,
				'=ACTIVE' => Agreement::ACTIVE
			],
		]);

		$id = $agreementRow ? (int)$agreementRow['ID'] : 0;

		if (!$id)
		{
			$addResult = AgreementTable::add([
				'CODE' => self::CODE_TERMS_AGREEMENT,
				'NAME' => Loc::getMessage('IMCONNECTOR_NOTIFICATIONS_TOS_AGREEMENT_NAME'),
				'AGREEMENT_TEXT' => Loc::getMessage('IMCONNECTOR_NOTIFICATIONS_TOS_AGREEMENT_TEXT'),
				'TYPE' => Agreement::TYPE_CUSTOM,
				'DATA_PROVIDER' => self::DATA_PROVIDER_CODE,
				'IS_AGREEMENT_TEXT_HTML' => 'Y',
			]);
			if ($addResult->isSuccess())
			{
				$id = $addResult->getId();
			}
		}

		return (int)$id;
	}

	/**
	 * @return array
	 */
	public function getAgreementTerms(): ?array
	{
		$result = [];
		$agreementId = self::getIdAgreementTerms();

		if(!empty($agreementId))
		{
			$agreement = new Agreement($agreementId);
			if($agreement->isExist())
			{
				$result = [
					'title' => $agreement->getData()['NAME'],
					'html' => $agreement->getHtml()
				];
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function addUserConsentAgreementTerms(): bool
	{
		$result = false;

		$agreementId = self::getIdAgreementTerms();
		if(!empty($agreementId))
		{
			$result = Consent::addByContext(
				$agreementId,
				self::DATA_PROVIDER_CODE
			);
		}

		return (bool)$result;
	}

	/**
	 * @return bool
	 */
	public function isUserConsentAgreementTerms(): bool
	{
		$result = false;

		$agreementId = self::getIdAgreementTerms();
		if(!empty($agreementId))
		{
			$result = Consent::getByContext(
				$agreementId,
				self::DATA_PROVIDER_CODE
			);
		}

		return (bool)$result;
	}

	//endregion

	//region Widget
	public static function getWidgetScript(): string
	{
		if (!Loader::includeModule('imconnector'))
		{
			return false;
		}

		$resources = \Bitrix\Main\UI\Extension::getResourceList(
			['imconnector.notifications.widget'],
		);
		$externalizeUrl = function($url)
		{
			return "'" . Builder::getDefaultSiteUri() . \CUtil::GetAdditionalFileURL($url) . "'";
		};
		$jsList = "[" . implode(',', array_map($externalizeUrl, $resources['js'])) . "]";
		$cssList = "[" . implode(',', array_map($externalizeUrl, $resources['css'])) . "]";

		return <<<JS
(function(d) {
	function loadCSS(url) {
		return new Promise(function(resolve) {
			var l=d.createElement("link");l.type="text/css";l.rel="stylesheet";l.onload = function(){resolve();};l.href=url;
			d.head.appendChild(l);
		});
	}
	function loadJS(url) {
		return new Promise(function(resolve) {
			var s=d.createElement('script');s.type='text/javascript';s.async=true;s.onload=function(){resolve();};s.src=url;
    		d.head.appendChild(s);
		});
	}
	function loadAll(jsList, cssList) {
		var l=[];
		jsList.forEach(function(url){l.push(loadJS(url))});
		cssList.forEach(function(url){l.push(loadCSS(url))});
		return Promise.all(l);
	}
	if(!BX)BX={};BX.NotificationsWidgetLoader={
		init: function(params) {
			return new Promise(function(resolve) {
				loadAll({$jsList}, {$cssList}).then(function() {
					BX.NotificationsWidget.Instance = new BX.NotificationsWidget(params);
					resolve();
				})
			});
		}
	};
})(document);
JS;
	}

	public static function getWidgetLocalization($langId = LANGUAGE_ID)
	{
		$resources = \Bitrix\Main\UI\Extension::getResourceList('imconnector.notifications.widget', ['skip_core_js' => true]);
		$messages = [];
		foreach ($resources['lang'] as $file)
		{
			$fileMessages = Loc::loadLanguageFile(Application::getDocumentRoot().$file, $langId);
			if ($fileMessages)
			{
				$messages = array_merge($messages, $fileMessages);
			}
		}

		return $messages;
	}

	public static function getVirtualWhatsappLink(string $portalCode, $langId = LANGUAGE_ID)
	{
		$phoneNumber = \Bitrix\Notifications\VirtualWhatsApp::getPhoneNumberByRegion();
		$text = Loc::getMessage(
			'IMCONNECTOR_NOTIFICATIONS_VIRTUAL_WHATSAPP_DEFAULT_MESSAGE',
			[
				'#PORTAL_CODE#' => '#' . $portalCode,
			],
			$langId
		);

		return "https://api.whatsapp.com/send/?phone={$phoneNumber}&text=" . rawurlencode($text);
	}

	public static function getVirtualWhatsappOnClick(string $url): string
	{
		if(Loader::includeModule('bitrix24') && \CBitrix24::getPortalZone() === 'ru')
		{
			$url=str_replace("https://api.whatsapp.com/", "whatsapp://", $url);
			return "document.location.href='{$url}';event.preventDefault();";
		}
		else
		{
			return "";
		}
	}

	public static function getWidgetDisclaimerUrl($langId = LANGUAGE_ID)
	{
		// TODO: replace with actual url when known
		return 'https://bitrix24.com';
	}

	//endregion

	/**
	 * @param int $lineId
	 * @param string $scenarioCode
	 * @return Result
	 */
	public static function addToLine(int $lineId, string $scenarioCode): Result
	{
		$result = new Result();

		$connectorOutput = new Output(Library::ID_NOTIFICATIONS_CONNECTOR, $lineId);
		$resultRegister = $connectorOutput->register([
			'LINE_ID' => $lineId,
			'SKIP_TOS' => true,
		]);
		$status = Status::getInstance(Library::ID_NOTIFICATIONS_CONNECTOR, $lineId);
		if (!$resultRegister->isSuccess())
		{
			return $result->addErrors($resultRegister->getErrors());
		}
		$scenarioEnableResult = Settings::setScenarioStatus($scenarioCode, true);
		if (!$scenarioEnableResult->isSuccess())
		{
			$connectorOutput->delete();
			return $result->addErrors($scenarioEnableResult->getErrors());
		}

		$status
			->setActive(true)
			->setConnection(true)
			->setRegister(true)
			->setData($resultRegister->getResult())
			->save()
		;

		Status::deleteLinesExcept(Library::ID_NOTIFICATIONS_CONNECTOR, $lineId);

		\Bitrix\ImConnector\InfoConnectors::updateInfoConnectors($lineId);
		if (Loader::includeModule('crm') && method_exists(\Bitrix\Crm\SiteButton\Manager::class, 'updateScriptCacheWithLineId'))
		{
			\Bitrix\Crm\SiteButton\Manager::updateScriptCacheWithLineId($lineId, true);
		}

		return $result;
	}

	/**
	 * Temporary method to enable and connect Virtual WhatsApp by request.
	 * Should be called by request from controller.
	 * Delete after 2022-01-01
	 */
	public static function enableVirtualWhatsapp()
	{
		if (Loader::includeModule('notifications') && Loader::includeModule('imopenlines'))
		{
			Option::set('notifications', 'enable_virtual_whatsapp', 'Y');
			$lineId = static::detectActiveLineByButton() ?? Preset::findActiveLineId();
			if ($lineId)
			{
				static::addToLine($lineId, Settings::SCENARIO_VIRTUAL_WHATSAPP);
			}
		}
	}

	private static function detectActiveLineByButton(): ?int
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$preInstalledButtonData = SiteButton\Internals\ButtonTable::getRow([
			'select' => ['ID', 'ITEMS'],
			'filter' => [
				'=IS_SYSTEM' => 'Y',
				'=ACTIVE' => 'Y'
			]
		]);

		if (!$preInstalledButtonData)
		{
			return null;
		}

		$preInstalledButton = new SiteButton\Button();
		$preInstalledButton->loadByData($preInstalledButtonData);
		$openLine = $preInstalledButton->getOpenLine();
		if ($openLine && isset($openLine['EXTERNAL_ID']) && $openLine['EXTERNAL_ID'] > 0)
		{
			return (int)$openLine['EXTERNAL_ID'];
		}

		return null;
	}
}