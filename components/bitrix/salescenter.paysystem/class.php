<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/lib/cashbox/inputs/file.php");

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Cashbox;
use Bitrix\Sale\Helpers\Admin;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Seo;
use Bitrix\Sale\Internals\Input;

class SalesCenterPaySystemComponent extends CBitrixComponent implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	/** @var Main\ErrorCollection */
	protected $errorCollection;

	public function configureActions()
	{
		Loader::includeModule('sale');

		return [];
	}

	/**
	 * Getting array of errors.
	 * @return Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Main\Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		$this->errorCollection = new Main\ErrorCollection();

		$this->arResult = [
			'PAYSYSTEM_HANDLER' => ($arParams["PAYSYSTEM_HANDLER"] ? $arParams["PAYSYSTEM_HANDLER"] : null),
			'PAYSYSTEM_PS_MODE' => ($arParams["PAYSYSTEM_PS_MODE"] ? $arParams["PAYSYSTEM_PS_MODE"] : null),
			'PAYSYSTEM_ID' => (($arParams["PAYSYSTEM_ID"] && $arParams["PAYSYSTEM_ID"] > 0) ? $arParams["PAYSYSTEM_ID"] : 0),
			'ERROR' => [],
		];

		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @return mixed|void
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function executeComponent()
	{
		if(!Loader::includeModule('salescenter'))
		{
			$this->showError(Loc::getMessage('SP_SALESCENTER_MODULE_ERROR'));
			return;
		}

		if(!Loader::includeModule('seo'))
		{
			$this->showError(Loc::getMessage('SP_SEO_MODULE_ERROR'));
			return;
		}

		if(!SaleManager::getInstance()->isFullAccess(true))
		{
			$this->showError(Loc::getMessage("SP_SALESCENTER_SALE_ACCESS_DENIED"));
			return;
		}

		$this->prepareResultArray();
		$this->includeComponentTemplate();
	}

	/**
	 * @param $error
	 */
	private function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private function prepareResultArray()
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $APPLICATION;

		$this->arResult['IFRAME'] = $this->request->get('IFRAME') === 'Y';
		$this->arResult['ACTION_URL'] = $APPLICATION->GetCurPageParam();

		if ($this->arResult['PAYSYSTEM_ID'])
		{
			$this->arResult['PAYSYSTEM'] = $this->getPaySystemData($this->arResult['PAYSYSTEM_ID']);
			$this->arResult['PAYSYSTEM_HANDLER'] = $this->arResult['PAYSYSTEM']['ACTION_FILE'];
			$this->arResult['PAYSYSTEM_PS_MODE'] = $this->arResult['PAYSYSTEM']['PS_MODE'];
		}

		if ($this->arResult['PAYSYSTEM_HANDLER'])
		{
			$this->arResult['PAYSYSTEM']['HANDLER_DESCRIPTION'] = $this->getPaySystemDescription(
				$this->arResult['PAYSYSTEM_HANDLER'],
				$this->arResult['PAYSYSTEM_PS_MODE']
			);
		}

		if (!isset($this->arResult['PAYSYSTEM']['ACTION_FILE']))
		{
			$this->arResult['PAYSYSTEM']['ACTION_FILE'] = $this->arResult['PAYSYSTEM_HANDLER'];
		}

		if (!isset($this->arResult['PAYSYSTEM']['ACTIVE']))
		{
			$this->arResult['PAYSYSTEM']['ACTIVE'] = 'Y';
		}

		if (!isset($this->arResult['PAYSYSTEM']['SORT']))
		{
			$this->arResult['PAYSYSTEM']['SORT'] = 100;
		}

		if (!isset($this->arResult['PAYSYSTEM']['IS_CASH']))
		{
			$this->arResult['PAYSYSTEM']['IS_CASH'] = 'N';
		}

		if (!isset($this->arResult['PAYSYSTEM']['XML_ID']))
		{
			$this->arResult['PAYSYSTEM']['XML_ID'] = PaySystem\Manager::generateXmlId();
		}

		if (!isset($this->arResult['PAYSYSTEM']['CAN_PRINT_CHECK']))
		{
			$this->arResult['PAYSYSTEM']['CAN_PRINT_CHECK'] = 'Y';
		}

		$this->arResult['AUTH']['URL'] = '';
		$this->arResult['AUTH']['HAS_AUTH'] = false;
		$this->arResult['AUTH']['CAN_AUTH'] = false;
		$this->arResult['AUTH']['PROFILE'] = false;

		[$className] = PaySystem\Manager::includeHandler($this->arResult['PAYSYSTEM_HANDLER']);
		$this->arResult['PAYSYSTEM_HANDLER_CLASS_NAME'] = $className;
		if (mb_strtolower($className) === mb_strtolower(\Sale\Handlers\PaySystem\YandexCheckoutHandler::class))
		{
			if ($this->isYandexOauth())
			{
				$this->initYandexProfile();
			}
			elseif ($this->isYookassaOauth())
			{
				$this->initYookassaProfile();
			}

			if ($this->arResult['PAYSYSTEM_ID'] === 0)
			{
				$this->arResult['AUTH']['CAN_AUTH'] = true;
			}
			else
			{
				if ($this->arResult['AUTH']['HAS_AUTH'] === false && $this->isOAuth($this->arResult['PAYSYSTEM_ID']))
				{
					$this->arResult['AUTH']['CAN_AUTH'] = true;
				}
			}
		}

		$this->arResult['PAYSYSTEM_HANDLER_STYLE'] = mb_strtolower($this->arResult['PAYSYSTEM_HANDLER']);
		$this->arResult['PAYSYSTEM_HANDLER_FULL'] = mb_strtoupper($this->arResult['PAYSYSTEM_HANDLER']);
		if ($this->arResult['PAYSYSTEM_PS_MODE'])
		{
			$this->arResult['PAYSYSTEM_HANDLER_STYLE'] = mb_strtolower($this->arResult['PAYSYSTEM_HANDLER'].'-'.$this->arResult['PAYSYSTEM_PS_MODE']);
			$this->arResult['PAYSYSTEM_HANDLER_FULL'] = mb_strtoupper($this->arResult['PAYSYSTEM_HANDLER'].'_'.$this->arResult['PAYSYSTEM_PS_MODE']);
		}

		$this->arResult['HELPDESK_DOCUMENTATION_CODE'] = $this->getHelpdeskDocumentationCode($className);

		$this->checkAvailabilityCashbox();
		$this->initBusinessValue($this->arResult['PAYSYSTEM_ID'], $this->arResult['PAYSYSTEM_HANDLER']);

		$this->arResult['IS_CASHBOX_ENABLED'] = \Bitrix\SalesCenter\Driver::getInstance()->isCashboxEnabled();

		$this->arResult['IS_CAN_PRINT_CHECK_SELF'] = false;
		$this->arResult['CASHBOX'] = [];

		$service = new PaySystem\Service($this->arResult['PAYSYSTEM']);
		if ($service->isSupportPrintCheck())
		{
			$initCashboxResult = $this->initCashbox($service);
			if ($initCashboxResult)
			{
				$this->arResult = array_merge($this->arResult, $initCashboxResult);
			}
		}
	}

	/**
	 * @param $paySystemId
	 * @param $handler
	 * @throws Main\SystemException
	 */
	private function initBusinessValue($paySystemId, $handler)
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/lib/helpers/admin/businessvalue.php');

		$data = PaySystem\Manager::getHandlerDescription($handler);

		if ($paySystemId <= 0)
		{
			$consumerKey = 'PAYSYSTEM_NEW';
			BusinessValue::addConsumer($consumerKey, $data);
		}
		else
		{
			$consumerKey = 'PAYSYSTEM_'.$paySystemId;
			try
			{
				BusinessValue::changeConsumer($consumerKey, $data);
			}
			catch(Main\SystemException $e)
			{
				BusinessValue::addConsumer($consumerKey, $data);
			}
		}

		$businessValueControl = new Admin\BusinessValueControl('PAYSYSTEM');

		ob_start();
		$businessValueControl->renderMap(array('CONSUMER_KEY' => $consumerKey));
		$businessValueContent = ob_get_contents();
		ob_end_clean();
		$this->arResult["BUS_VAL"] = $businessValueContent;
	}

	/**
	 * @return bool
	 * @throws Main\SystemException
	 */
	private function isYandexOauth(): bool
	{
		$authAdapter = Seo\Checkout\Service::getAuthAdapter(Seo\Checkout\Service::TYPE_YANDEX);
		$this->arResult['AUTH']['TYPE'] = Seo\Checkout\Service::TYPE_YANDEX;
		$this->arResult['AUTH']['URL'] = $authAdapter->getAuthUrl();
		$this->arResult['AUTH']['HAS_AUTH'] = $authAdapter->hasAuth();

		return (bool)$this->arResult['AUTH']['HAS_AUTH'];
	}

	private function initYandexProfile()
	{
		$yandex = new Seo\Checkout\Services\AccountYandex();
		$yandex->setService(Seo\Checkout\Service::getInstance());
		$this->arResult['AUTH']['PROFILE'] = $yandex->getProfile();
	}

	/**
	 * @return bool
	 * @throws Main\SystemException
	 */
	private function isYookassaOauth()
	{
		$authAdapter = Seo\Checkout\Service::getAuthAdapter(Seo\Checkout\Service::TYPE_YOOKASSA);
		$this->arResult['AUTH']['TYPE'] = Seo\Checkout\Service::TYPE_YOOKASSA;
		$this->arResult['AUTH']['URL'] = $authAdapter->getAuthUrl();
		$this->arResult['AUTH']['HAS_AUTH'] = $authAdapter->hasAuth();

		return (bool)$this->arResult['AUTH']['HAS_AUTH'];
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private function initYookassaProfile()
	{
		$yookassa = new Seo\Checkout\Services\AccountYookassa();
		$yookassa->setService(Seo\Checkout\Service::getInstance());
		$this->arResult['AUTH']['PROFILE'] = $yookassa->getProfile();
	}

	/**
	 * @param $id
	 * @return array|false
	 */
	private function getPaySystemData($id)
	{
		return PaySystem\Manager::getById($id);
	}

	/**
	 * @param $handler
	 * @param null $psMode
	 * @return array
	 */
	private function getPaySystemDescription($handler, $psMode = null)
	{
		$paySystemDescription = [
			'FULL_NAME' => '',
			'NAME' => '',
			'MODE_NAME' => '',
			'DESCRIPTION' => '',
			'LOGO' => '',
		];

		$pathToHandler = PaySystem\Manager::getPathToHandlerFolder($handler);

		$documentRoot = Application::getDocumentRoot();
		if (Main\IO\File::isFileExists($documentRoot.$pathToHandler.'/.description.php'))
		{
			$pathToDesc = $documentRoot.$pathToHandler.'/.description.php';
			require_once $pathToDesc;
		}

		if (isset($data))
		{
			$paySystemDescription["NAME"] = $paySystemDescription["FULL_NAME"] = $data['NAME'];
		}
		else
		{
			$handlerList = PaySystem\Manager::getHandlerList();
			foreach ($handlerList as $handlerType)
			{
				if (array_key_exists($handler, $handlerType))
				{
					$paySystemDescription["NAME"] = $paySystemDescription["FULL_NAME"] = $handlerType[$handler];
				}
			}
		}

		if ($psMode)
		{
			$psModeName = $this->getHandlerModeName($handler, $psMode);
			if ($psModeName)
			{
				$paySystemDescription['MODE_NAME'] = $psModeName;
				$paySystemDescription["FULL_NAME"] = $paySystemDescription["NAME"].' '.Loc::getMessage(
					'SP_PAYMENT_SUB_TITLE',
					[
						'#SUB_TITLE#' => $psModeName
					]
				);
			}
		}

		if (isset($description))
		{
			if (is_array($description))
			{
				$paySystemDescription['DESCRIPTION'] = (array_key_exists('MAIN', $description)) ? $description['MAIN'] : implode("\n", $description);
			}
			else
			{
				$paySystemDescription['DESCRIPTION'] = $description;
			}
		}

		if ($psMode)
		{
			$fullPath = $handler.'/'.$psMode;
			if (Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].'/bitrix/images/sale/sale_payments/'.$fullPath.'.png'))
			{
				$paySystemDescription['LOGO'] = '/bitrix/images/sale/sale_payments/'.$fullPath.'.png';
			}
		}

		if ((!$paySystemDescription['LOGO'])
			&& Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].'/bitrix/images/sale/sale_payments/'.$handler.'.png'))
		{
			$paySystemDescription['LOGO'] = '/bitrix/images/sale/sale_payments/'.$handler.'.png';
		}

		return $paySystemDescription;
	}

	/**
	 * @param $handler
	 * @param $psMode
	 * @return string|null
	 */
	private function getHandlerModeName($handler, $psMode)
	{
		/** @var PaySystem\BaseServiceHandler $className */
		$className = PaySystem\Manager::getClassNameFromPath($handler);
		if (!class_exists($className))
		{
			$documentRoot = Main\Application::getDocumentRoot();
			$path = PaySystem\Manager::getPathToHandlerFolder($handler);
			$fullPath = $documentRoot.$path.'/handler.php';
			if ($path && Main\IO\File::isFileExists($fullPath))
			{
				require_once $fullPath;
			}
		}

		$handlerModeName = null;
		if (class_exists($className))
		{
			$handlerModeList = $className::getHandlerModeList();
			if ($handlerModeList)
			{
				$handlerModeName = $handlerModeList[$psMode];
			}
		}

		return $handlerModeName;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function checkAvailabilityCashbox()
	{
		$filter = SaleManager::getInstance()->getCashboxFilter();
		$cashboxItem = Bitrix\Sale\Cashbox\Internals\CashboxTable::getList([
			'select' => ['ACTIVE', 'HANDLER'],
			'filter' => $filter,
		])->fetch();

		if ($cashboxItem)
		{
			$this->arResult['SHOW_CASHBOX_HINT'] = false;
		}
		else
		{
			$this->arResult['SHOW_CASHBOX_HINT'] = true;
		}
	}

	private function initCashbox(PaySystem\Service $service, string $kkmId = ''): array
	{
		$result = [];

		Cashbox\Cashbox::init();

		$result['IS_CAN_PRINT_CHECK_SELF'] = true;
		$result['IS_FISCALIZATION_ENABLE'] = true;

		/** @var Cashbox\CashboxPaySystem $cashboxHandler */
		$cashboxHandler = $service->getCashboxClass();

		$supportedKkmModels = BusinessValue::getValuesByCode(
			$service->getConsumerName(),
			$cashboxHandler::getPaySystemCodeForKkm()
		);
		$result['SUPPORTED_KKM_MODELS'] = $supportedKkmModels;

		if (!$kkmId && $supportedKkmModels)
		{
			$kkmId = (string)current($supportedKkmModels);
		}

		$handlerDescription = $service->getHandlerDescription();
		$result['PAY_SYSTEM_CODE_NAME'] = $handlerDescription['CODES'][$cashboxHandler::getPaySystemCodeForKkm()]['NAME'];

		$cashbox = Cashbox\Manager::getList([
			'filter' => [
				'=HANDLER' => $cashboxHandler,
				'=KKM_ID' => $kkmId,
			],
		])->fetch();
		if (!$cashbox)
		{
			$result['IS_FISCALIZATION_ENABLE'] = false;

			$cashbox = [
				'HANDLER' => $cashboxHandler,
				'OFD' => '',
			];
		}

		$result['CASHBOX'] = $this->getCashboxSettings($cashbox);
		$result['CASHBOX']['code'] = mb_strtoupper($cashboxHandler::getCode());
		$result['CASHBOX']['documentationCode'] = $this->getHelpdeskDocumentationCashboxCode($cashboxHandler);

		return $result;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	private function isOAuth($id)
	{
		$shopId = BusinessValue::getMapping(
			'YANDEX_CHECKOUT_SHOP_ID',
			"PAYSYSTEM_".$id,
			null,
			[
				'MATCH' => BusinessValue::MATCH_EXACT
			]
		);

		if (isset($shopId['PROVIDER_VALUE']) && $shopId['PROVIDER_VALUE'])
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string $handlerClass
	 * @return string
	 */
	private function getHelpdeskDocumentationCode(string $handlerClass): string
	{
		$defaultCode = '11864562';

		try
		{
			$reflection = new \ReflectionClass($handlerClass);
			$className = $reflection->getName();
		}
		catch (\ReflectionException $ex)
		{
			return $defaultCode;
		}

		$helpdeskCodeMap = [
			\Sale\Handlers\PaySystem\SkbHandler::class => '11538458',
			\Sale\Handlers\PaySystem\BePaidHandler::class => '11538452',
			\Sale\Handlers\PaySystem\LiqPayHandler::class => '11814321',
			\Sale\Handlers\PaySystem\UaPayHandler::class => '11825299',
			\Sale\Handlers\PaySystem\WooppayHandler::class => '12183852',
			\Sale\Handlers\PaySystem\AlfaBankHandler::class => '12595422',
			\Sale\Handlers\PaySystem\RoboxchangeHandler::class => '12595360',
			\Sale\Handlers\PaySystem\PlatonHandler::class => '13920167',
		];

		return $helpdeskCodeMap[$className] ?? $defaultCode;
	}

	/**
	 * @param string $cashboxHandler
	 * @return string|null
	 */
	private function getHelpdeskDocumentationCashboxCode(string $cashboxHandler): ?string
	{
		$helpdeskCodeMap = [
			'\\'.Cashbox\CashboxRobokassa::class => '12849128',
		];

		return $helpdeskCodeMap[$cashboxHandler] ?? null;
	}

	private function getCashboxSettings(array $cashbox)
	{
		$result = [];

		/** @var Cashbox\Cashbox $handler */
		$handler = $cashbox['HANDLER'];

		if (class_exists($handler))
		{
			$commonSettingsTitle = Loc::getMessage(
				'SALESCENTER_SP_CASHBOX_COMMON_SETTINGS_'.mb_strtoupper($handler::getCode())
			);
			if (!$commonSettingsTitle)
			{
				$commonSettingsTitle = Loc::getMessage('SALESCENTER_SP_CASHBOX_COMMON_SETTINGS');
			}

			$result['section']['common'] = [
				'title' => $commonSettingsTitle,
				'type' => 'settings',
			];
			$result['fields']['common'][] = [
				'label' => 'Email',
				'type' => 'string',
				'input' => Input\Manager::getEditHtml(
					'CASHBOX[EMAIL]',
					[
						'TYPE' => 'STRING',
						'CLASS' => 'ui-ctl-element'
					],
					$cashbox['EMAIL']
				),
				'hint' => Loc::getMessage('SALESCENTER_SP_CASHBOX_EMAIL_HINT'),
				'required' => true,
			];

			$cashboxSettings = $cashbox['SETTINGS'] ?? [];
			$settings = $handler::getSettings($cashbox['KKM_ID']);
			if ($settings)
			{
				foreach ($settings as $group => $block)
				{
					$warning = '';
					if ($group === 'VAT')
					{
						$warning = Loc::getMessage('SALESCENTER_SP_CASHBOX_VAT_ATTENTION');
					}

					$result['section'][$group] = [
						'title' => htmlspecialcharsbx($block['LABEL']),
						'type' => 'cashboxSettings',
						'warning' => $warning,
					];

					foreach ($block['ITEMS'] as $code => $item)
					{
						$item['CLASS'] = 'ui-ctl-element';
						$value = $cashboxSettings[$group][$code] ?? null;

						$type = 'string';
						if ($item['TYPE'] === 'ENUM')
						{
							$type = 'select';
						}
						elseif ($item['TYPE'] === 'Y/N')
						{
							$type = 'checkbox';
						}

						$isRequired = (
							(isset($block['REQUIRED']) && $block['REQUIRED'] === 'Y')
							|| (isset($item['REQUIRED']) && $item['REQUIRED'] === 'Y')
						);

						$result['fields'][$group][] = [
							'label' => htmlspecialcharsbx($item['LABEL']),
							'type' => $type,
							'input' => Input\Manager::getEditHtml(
								'SETTINGS['.$group.']['.$code.']',
								$item,
								$value
							),
							'required' => $isRequired,
						];
					}
				}
			}
		}

		return $result;
	}

	public function reloadCashboxSettingsAction(int $paySystemId, string $kkmId): ?array
	{
		$service = PaySystem\Manager::getObjectById($paySystemId);
		if (!$service)
		{
			return null;
		}

		return $this->initCashbox($service, $kkmId);
	}
}