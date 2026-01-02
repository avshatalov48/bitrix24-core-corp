<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\License\UrlProvider;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Service\GeoIp;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\SalesCenter\Model\PageTable;
use Bitrix\UI\Form\FormsProvider;

class SalesCenterFeedbackComponent extends CBitrixComponent implements Controllerable, Errorable
{
	use ErrorableImplementation;

	private string $template = '';

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new ErrorCollection();
	}

	public function configureActions(): array
	{
		return [];
	}

	public function getFormParamsAction(?string $feedback_type): ?array
	{
		if (!Loader::includeModule('salescenter'))
		{
			$this->addErrorMessage(Loc::getMessage('SALESCENTER_FEEDBACK_MODULE_ERROR'));

			return null;
		}

		$manager = Bitrix24Manager::getInstance();
		if (!$manager->isEnabled())
		{
			$this->addErrorMessage(Loc::getMessage('SALESCENTER_FEEDBACK_BITRIX24_ERROR'));

			return null;
		}

		$feedbackType = trim((string)$feedback_type);
		if ($feedbackType === '')
		{
			$feedbackType = Bitrix24Manager::FEEDBACK_TYPE_FEEDBACK;
		}

		$forms =
			$feedbackType === Bitrix24Manager::FEEDBACK_TYPE_INTEGRATION_REQUEST
				? FormsProvider::getForms()
				: $this->getRegionSettings($manager, $feedbackType)
		;
		if ($forms === null)
		{
			$this->addErrorMessage('Unknown feedback type id');

			return null;
		}
		$result = [
			'id' => 'B24SalesCenterFeedback',
			'forms' => $forms,
		];
		$title = $this->getTitle($feedbackType);
		if ($title !== null)
		{
			$result['title'] = $title;
		}
		$result['type'] = $this->getType();
		$result['fields'] = $this->getFields();
		if ($feedbackType === Bitrix24Manager::FEEDBACK_TYPE_INTEGRATION_REQUEST)
		{
			$result['portalUri'] = $this->getPartnerPortalUrl();
			$result['presets'] = $this->getIntegrationPresets();
		}
		else
		{
			$result['presets'] = $this->getProductFeedbackPresets($manager);
		}

		return $result;
	}

	protected function getRegionSettings(Bitrix24Manager $manager, string $action): ?array
	{
		$region = Application::getInstance()->getLicense()->getRegion();
		if ($region === null)
		{
			return null;
		}
		$result =  match ($action)
		{
			Bitrix24Manager::FEEDBACK_TYPE_FEEDBACK => $manager->getFeedbackFormInfo($region),
			Bitrix24Manager::FEEDBACK_TYPE_PAYSYSTEM_OFFER => $manager->getFeedbackPaySystemOfferFormInfo($region),
			Bitrix24Manager::FEEDBACK_TYPE_SMSPROVIDER_OFFER => $manager->getFeedbackSmsProviderOfferFormInfo($region),
			Bitrix24Manager::FEEDBACK_TYPE_PAY_ORDER => $manager->getFeedbackPayOrderFormInfo($region),
			Bitrix24Manager::FEEDBACK_TYPE_DELIVERY_OFFER => $manager->getFeedbackDeliveryOfferFormInfo($region),
			Bitrix24Manager::FEEDBACK_TYPE_TERMINAL_OFFER => $manager->getFeedbackTerminalOfferFormInfo($region),
			Bitrix24Manager::FEEDBACK_TYPE_PAYSYSTEM_SBP_OFFER => $manager->getFeedbackPaySystemSbpOfferFormInfo($region),
			Bitrix24Manager::FEEDBACK_TYPE_INTEGRATION_REQUEST => $manager->getIntegrationRequestFormInfo($region),
			default => null,
		};

		if ($result === null)
		{
			return null;
		}

		$result['zones'] = [$result['lang']];

		return [$result];
	}

	protected function getTitle(string $action): ?string
	{
		return match ($action)
		{
			Bitrix24Manager::FEEDBACK_TYPE_INTEGRATION_REQUEST => Loc::getMessage('SALESCENTER_FEEDBACK_INTEGRATION_REQUEST_TITLE'),
			default => null,
		};
	}

	protected function getType(): string
	{
		return 'slider_inline';
	}

	protected function getFields(): array
	{
		return [
			'values' => [
				'CONTACT_EMAIL' => CurrentUser::get()->getEmail(),
			],
		];
	}

	protected function getIntegrationPresets(): array
	{
		return [
			'city' => implode(' / ', $this->getUserGeoData()),
			'source' => 'salescenter',
		];
	}

	protected function getProductFeedbackPresets(Bitrix24Manager $manager): array
	{
		$user = CurrentUser::get();

		return [
			'from_domain' => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : Option::get('main', 'server_name', ''),
			'b24_plan' => $manager->getLicenseType(),
			'b24_zone' => $manager->getPortalZone(),
			'c_name' => $user->getFullName(),
			'user_status' => $user->isAdmin() ? 'yes' : 'no',
			'is_created_eshop' => LandingManager::getInstance()->isSiteExists() ? 'yes' : 'no',
			'is_payment_system' => $this->hasPaymentSystemConfigured() ? 'yes' : 'no',
			'is_cashbox' => $this->hasCashboxConfigured() ? 'yes' : 'no',
			'is_own_url' => $this->hasPagesWithCustomUrl() ? 'yes' : 'no',
			'is_other_website_url' => $this->hasPagesFromAnotherSite() ? 'yes' : 'no',
		];
	}

	protected function addErrorMessage(string $message): void
	{
		$this->errorCollection->setError(new Error($message));
	}

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['FEEDBACK_TYPE'] = trim((string)($arParams['FEEDBACK_TYPE'] ?? ''));

		$arParams['SENDER_PAGE'] ??= '';
		if (!is_string($arParams['SENDER_PAGE']))
		{
			$arParams['SENDER_PAGE'] = '';
		}
		if (!preg_match("/^[A-Za-z_]+$/", $arParams['SENDER_PAGE']))
		{
			$arParams['SENDER_PAGE'] = '';
		}

		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @deprecated
	 * @see \SalesCenterFeedbackComponent::getFormParamsAction
	 * @return void
	 */
	public function executeComponent(): void
	{
		if (!Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SALESCENTER_FEEDBACK_MODULE_ERROR'));
			$this->includeComponentTemplate();

			return;
		}
		if (!Bitrix24Manager::getInstance()->isEnabled())
		{
			ShowError(Loc::getMessage('SALESCENTER_FEEDBACK_BITRIX24_ERROR'));
			$this->includeComponentTemplate();

			return;
		}

		if (empty($this->arParams['FEEDBACK_TYPE']))
		{
			$this->arParams['FEEDBACK_TYPE'] = Bitrix24Manager::FEEDBACK_TYPE_FEEDBACK;
		}

		if ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_PAYSYSTEM_SBP_OFFER)
		{
			$this->template = 'newform';
		}

		if ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_FEEDBACK)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_PAYSYSTEM_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackPaySystemOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_SMSPROVIDER_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackSmsProviderOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_PAY_ORDER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackPayOrderFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_DELIVERY_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackDeliveryOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_TERMINAL_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackTerminalOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_PAYSYSTEM_SBP_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackPaySystemSbpOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_INTEGRATION_REQUEST)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getIntegrationRequestFormInfo(Bitrix24Manager::getInstance()->getPortalZone());
		}

		$this->arResult['type'] = 'slider_inline';
		$this->arResult['fields']['values']['CONTACT_EMAIL'] = CurrentUser::get()->getEmail();
		if ($this->arParams['FEEDBACK_TYPE'] === Bitrix24Manager::FEEDBACK_TYPE_INTEGRATION_REQUEST)
		{
			$this->arResult['domain'] = $this->getPartnerPortalUrl();
			$this->arResult['presets'] = [
				'url' => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : $_SERVER['SERVER_NAME'],
				'tarif' => Bitrix24Manager::getInstance()->getLicenseType(),
				'c_email' => CurrentUser::get()->getEmail(),
				'city' => implode(' / ', $this->getUserGeoData()),
				'partner_id' => \Bitrix\Main\Config\Option::get('bitrix24', 'partner_id', 0),
				'sender_page' => $this->arParams['SENDER_PAGE'],
			];
		}
		else
		{
			$this->arResult['domain'] = 'https://' . (new UrlProvider())->getFeedbackDomain();
			$this->arResult['presets'] = [
				'from_domain' => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : Option::get('main', 'server_name', ''),
				'b24_plan' => Bitrix24Manager::getInstance()->getLicenseType(),
				'b24_zone' => Bitrix24Manager::getInstance()->getPortalZone(),
				'c_name' => CurrentUser::get()->getFullName(),
				'user_status' => CurrentUser::get()->isAdmin() ? 'yes' : 'no',
				'is_created_eshop' => LandingManager::getInstance()->isSiteExists() ? 'yes' : 'no',
				'is_payment_system' => $this->hasPaymentSystemConfigured() ? 'yes' : 'no',
				'is_cashbox' => $this->hasCashboxConfigured() ? 'yes' : 'no',
				'is_own_url' => $this->hasPagesWithCustomUrl() ? 'yes' : 'no',
				'is_other_website_url' => $this->hasPagesFromAnotherSite() ? 'yes' : 'no',
				'sender_page' => $this->arParams['SENDER_PAGE'],
			];
		}

		$this->includeComponentTemplate($this->template);
	}

	private function getUserGeoData(): array
	{
		$countryName = GeoIp\Manager::getCountryName('', 'ru');
		if (!$countryName)
		{
			$countryName = GeoIp\Manager::getCountryName();
		}

		$cityName = GeoIp\Manager::getCityName('', 'ru');
		if (!$cityName)
		{
			$cityName = GeoIp\Manager::getCityName();
		}

		return [
			'country' => $countryName,
			'city' => $cityName,
		];
	}

	/**
	 * @return bool
	 */
	protected function hasPaymentSystemConfigured(): bool
	{
		if (SaleManager::getInstance()->isEnabled())
		{
			$filter = SaleManager::getInstance()->getPaySystemFilter();

			return PaySystemActionTable::getCount($filter) > 0;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function hasCashboxConfigured(): bool
	{
		if (SaleManager::getInstance()->isEnabled())
		{
			$filter = SaleManager::getInstance()->getCashboxFilter();

			return CashboxTable::getCount($filter) > 0;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function hasPagesWithCustomUrl(): bool
	{
		return (PageTable::getCount(Driver::getInstance()->getFilterForCustomUrlPages()) > 0);
	}

	/**
	 * @return bool
	 */
	protected function hasPagesFromAnotherSite(): bool
	{
		return PageTable::getCount(Driver::getInstance()->getFilterForAnotherSitePages()) > 0;
	}

	protected function getPartnerPortalUrl(): string
	{
		Loader::includeModule('ui');

		return (new \Bitrix\UI\Form\UrlProvider())->getPartnerPortalUrl();
	}
}
