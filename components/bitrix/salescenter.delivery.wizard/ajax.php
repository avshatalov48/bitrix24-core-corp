<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Controller;
use Bitrix\SalesCenter\Delivery\Handlers\HandlersRepository;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\SalesCenter\Integration\SaleManager;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Loader::requireModule('salescenter');

Loc::loadMessages(__FILE__);

/**
 * Class CSalesCenterDeliveryWizardComponentAjaxController
 */
class CSalesCenterDeliveryWizardComponentAjaxController extends Controller
{
	/**
	 * @param JsonPayload $settings
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function installAction(JsonPayload $settings)
	{
		if (!SaleManager::getInstance()->isFullAccess())
		{
			$this->addError(new Error('Access denied'));
			return null;
		}

		$data = $settings->getData();
		$code = $data['code'];

		$wizard = $this->makeWizard($code);
		if (!$wizard)
		{
			return null;
		}

		parse_str(http_build_query($data), $data);

		$installationResult = $wizard->install($data);
		if (!$installationResult->isSuccess())
		{
			$this->addErrors($installationResult->getErrors());
			return null;
		}
		
		AddEventToStatFile(
			'salescenter',
			'deliveryServiceInstallation',
			$installationResult->getData()['ID'],
			$wizard->getHandler()->getCode(),
			'delivery_service'
		);

		return [];
	}

	/**
	 * @param JsonPayload $settings
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function updateAction(JsonPayload $settings)
	{
		if (!SaleManager::getInstance()->isFullAccess())
		{
			$this->addError(new Error('Access denied'));
			return null;
		}

		$data = $settings->getData();
		$id = $data['id'];
		$code = $data['code'];

		$wizard = $this->makeWizard($code);
		if (!$wizard)
		{
			return null;
		}

		parse_str(http_build_query($data), $data);

		$updateResult = $wizard->update($id, $data);
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());
			return null;
		}

		return [];
	}

	/**
	 * @param int $id
	 * @param string $code
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function deleteAction(int $id, string $code)
	{
		if (!SaleManager::getInstance()->isFullAccess())
		{
			$this->addError(new Error('Access denied'));
			return null;
		}

		$wizard = $this->makeWizard($code);
		if (!$wizard)
		{
			return null;
		}

		$deleteResult = $wizard->delete($id);
		if (!$deleteResult->isSuccess())
		{
			$this->addErrors($deleteResult->getErrors());
			return null;
		}

		return [];
	}

	/**
	 * @param string $code
	 * @return \Bitrix\SalesCenter\Delivery\Wizard\WizardContract|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function makeWizard(string $code)
	{
		if (!Loader::includeModule('salescenter'))
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_DELIVERY_INSTALLATION_MODULE_ERROR')));
			return null;
		}
		if (!Loader::includeModule('sale'))
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_DELIVERY_INSTALLATION_SALE_MODULE_ERROR')));
			return null;
		}

		$handler = (new HandlersRepository())->getByCode($code);

		if (!$handler)
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_DELIVERY_INSTALLATION_HANDLER_NOT_FOUND_ERROR')));
			return null;
		}

		$wizard = $handler->getWizard();
		if (!$wizard)
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_DELIVERY_INSTALLATION_WIZARD_NOT_FOUND_ERROR')));
			return null;
		}

		$wizard->setHandler($handler);

		return $wizard;
	}
}
