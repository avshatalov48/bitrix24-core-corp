<?php

namespace Bitrix\SalesCenter\Delivery\Wizard;

use Bitrix\Catalog\VatTable;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sale\Internals\SiteCurrencyTable;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\SalesCenter\Delivery\Handlers\HandlerContract;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 * @package Bitrix\SalesCenter\DeliveryServiceInstallator
 */
abstract class Base implements WizardContract
{
	/** @var HandlerContract */
	protected $handler;

	/**
	 * @inheritDoc
	 */
	public function install(array $settings): Result
	{
		$result = new Result();

		$buildFieldsResult = $this->buildFieldsFromSettings($settings);
		if (!$buildFieldsResult->isSuccess())
		{
			return $result->addErrors($buildFieldsResult->getErrors());
		}

		$fields = $buildFieldsResult->getData()['FIELDS'];

		$prepareFieldsResult = $this->prepareFieldsForSaving($fields);
		if (!$prepareFieldsResult->isSuccess())
		{
			return $result->addErrors($prepareFieldsResult->getErrors());
		}

		$addResult = Manager::add($prepareFieldsResult->getData()['FIELDS']);
		if (!$addResult->isSuccess())
		{
			return $result->addErrors($addResult->getErrors());
		}

		return $result->setData(['ID' => $addResult->getId()]);
	}

	/**
	 * @inheritDoc
	 */
	public function update(int $id, array $settings): Result
	{
		$result = new Result();

		$buildFieldsResult = $this->buildFieldsFromSettings($settings);
		if (!$buildFieldsResult->isSuccess())
		{
			return $result->addErrors($buildFieldsResult->getErrors());
		}

		$fields = $buildFieldsResult->getData()['FIELDS'];

		$prepareFieldsResult = $this->prepareFieldsForSaving($fields);
		if (!$prepareFieldsResult->isSuccess())
		{
			return $result->addErrors($prepareFieldsResult->getErrors());
		}

		$updateResult = Manager::update(
			$id,
			$prepareFieldsResult->getData()['FIELDS']
		);
		if (!$updateResult->isSuccess())
		{
			return $result->addErrors($updateResult->getErrors());
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function delete(int $id): Result
	{
		return Manager::delete($id);
	}

	/**
	 * @param array $settings
	 * @return Result
	 */
	protected function validateSettings(array $settings): Result
	{
		$result = new Result();

		if (!isset($settings['NAME']) || empty($settings['NAME']))
		{
			return $result->addError(
				new Error(Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_NAME_NOT_SPECIFIED'))
			);
		}

		return $result;
	}

	/**
	 * @param array $settings
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function buildFieldsFromSettings(array $settings): Result
	{
		$result = new Result();

		$validationResult = $this->validateSettings($settings);
		if (!$validationResult->isSuccess())
		{
			return $result->addErrors($validationResult->getErrors());
		}

		if (!Loader::includeModule('currency'))
		{
			return $result->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_CURRENCY_MODULE_NOT_INSTALLED')));
		}

		$currency = SiteCurrencyTable::getSiteCurrency(SITE_ID);
		if (empty($currency))
		{
			$currency = CurrencyManager::getBaseCurrency();
		}

		/** @var \Bitrix\Sale\Delivery\Services\Base $handlerClass */
		$handlerClass = $this->handler->getHandlerClass();

		$vatRate = $handlerClass::getDefaultVatRate();

		return $result->setData(
			[
				'FIELDS' => [
					'NAME' => $settings['NAME'],
					'CURRENCY' => $currency,
					'ACTIVE' => $settings['ACTIVE'],
					'CLASS_NAME' => $handlerClass,
					'LOGOTIP' => \CFile::SaveFile(
						\CFile::MakeFileArray(
							Application::getDocumentRoot() . $this->handler->getWorkingImagePath()
						),
						'sale/delivery/logotip'
					),
					'VAT_ID' => (!is_null($vatRate) && Loader::includeModule('catalog'))
						? VatTable::getActiveVatIdByRate($vatRate, true)
						: null,
				]
			]
		);
	}

	/**
	 * @param array $fields
	 * @return Result
	 */
	private function prepareFieldsForSaving(array $fields)
	{
		$result = new Result();

		try
		{
			$service = Manager::createObject($fields);

			if (!$service)
			{
				return $result->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_ERROR')));
			}

			return $result->setData(['FIELDS' => $service->prepareFieldsForSaving($fields)]);
		}
		catch(SystemException $e)
		{
			return $result->addError(new Error($e->getMessage()));
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setHandler(HandlerContract $handler): WizardContract
	{
		$this->handler = $handler;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getHandler(): HandlerContract
	{
		return $this->handler;
	}
}
