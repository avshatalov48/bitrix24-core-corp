<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/lib/cashbox/inputs/file.php");

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Helpers\Admin;
use Bitrix\SalesCenter\Integration\SaleManager;

class SalesCenterPaySystemComponent extends CBitrixComponent
{
	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
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

		if(!SaleManager::getInstance()->isFullAccess())
		{
			$this->showError(Loc::getMessage("SP_SALESCENTER_SALE_ACCESS_DENIED"));
			return;
		}

		$this->arResult['isCashboxEnabled'] = \Bitrix\SalesCenter\Driver::getInstance()->isCashboxEnabled();

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

		$this->arResult['IFRAME'] = $this->request->get('IFRAME') == 'Y';
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
			$this->initYandexAuthAdapter();

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

		$this->checkAvailabilityCashbox();
		$this->initBusinessValue($this->arResult['PAYSYSTEM_ID'], $this->arResult['PAYSYSTEM_HANDLER']);
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
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private function initYandexAuthAdapter()
	{
		$authAdapter = Bitrix\Seo\Checkout\Service::getAuthAdapter('yandex');
		$this->arResult['AUTH']['URL'] = $authAdapter->getAuthUrl();
		$this->arResult['AUTH']['HAS_AUTH'] = $authAdapter->hasAuth();

		if ($this->arResult['AUTH']['HAS_AUTH'])
		{
			$yandex = new Bitrix\Seo\Checkout\Services\AccountYandex();
			$yandex->setService(Bitrix\Seo\Checkout\Service::getInstance());
			$this->arResult['AUTH']['PROFILE'] = $yandex->getProfile();
		}
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
}