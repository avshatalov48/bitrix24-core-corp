<?php

use Bitrix\Main;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Localization;
use Bitrix\Crm\Order;
use Bitrix\Sale;
use Bitrix\Catalog;
use Bitrix\Iblock;
use Bitrix\Crm\CompanyTable;
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Main\Loader::includeModule('sale');

class SalesCenterOrderDetails extends CBitrixComponent implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	/** @var Order\Order $order */
	protected $order;
	/**  @var Main\ErrorCollection */
	protected $errorCollection;

	public function onPrepareComponentParams($params)
	{
		$params["CACHE_TIME"] = 3600;
		$params['CACHE_GROUPS'] = (isset($params['CACHE_GROUPS']) && $params['CACHE_GROUPS'] == 'N' ? 'N' : 'Y');

		$params['ID'] = (int)$params['ID'];
		$params['PAYMENT_ID'] = (int)$params['PAYMENT_ID'];

		$params['ALLOW_INNER'] = 'N';

		if (empty($params["ACTIVE_DATE_FORMAT"]))
		{
			$params["ACTIVE_DATE_FORMAT"] = Main\Type\Date::getFormat();
		}

		if (!isset($params["CUSTOM_SELECT_PROPS"]) || !is_array($params["CUSTOM_SELECT_PROPS"]))
		{
			$params["CUSTOM_SELECT_PROPS"] = [];
		}
		if (!in_array('PROPERTY_MORE_PHOTO', $params['CUSTOM_SELECT_PROPS']))
		{
			$params['CUSTOM_SELECT_PROPS'][] = 'PROPERTY_MORE_PHOTO';
		}

		// resample sizes
		$params["PICTURE_WIDTH"] = $params["PICTURE_WIDTH"] ?? 110;
		$params["PICTURE_HEIGHT"] = $params["PICTURE_HEIGHT"] ?? 110;

		// resample type for images
		if (!isset($params["RESAMPLE_TYPE"]) || !in_array($params['RESAMPLE_TYPE'], [BX_RESIZE_IMAGE_EXACT, BX_RESIZE_IMAGE_PROPORTIONAL, BX_RESIZE_IMAGE_PROPORTIONAL_ALT]))
		{
			$params['RESAMPLE_TYPE'] = BX_RESIZE_IMAGE_PROPORTIONAL;
		}

		if (empty($params['HEADER_TITLE']))
		{
			$title = '';
			if (
				Loader::includeModule('salescenter')
				&& Loader::includeModule('crm')
			)
			{
				$title = \Bitrix\SalesCenter\Integration\CrmManager::getPublishedCompanyName();
			}

			$params['HEADER_TITLE'] = $title ?? 'Company 24';
		}

		$params['TEMPLATE_MODE'] ??= '';

		return $params;
	}

	/**
	 * @return void
	 */
	protected function checkOrder()
	{
		if (!$this->order)
		{
			$this->doCaseOrderIdNotSet();
		}
	}

	/**
	 * Function could describe what to do when order ID not set. By default, component will redirect to list page.
	 *
	 * @throws Main\SystemException
	 * @return void
	 */
	protected function doCaseOrderIdNotSet()
	{
		throw new Main\SystemException(
			Localization\Loc::getMessage("SPOD_NO_ORDER", array("#ID#" => $this->arParams["ID"]))
		);
	}

	protected function checkAuthorized()
	{
		$context = Main\Context::getCurrent();
		$request = $context->getRequest();

		if ($request->get('access') !== $this->order->getHash())
		{
			throw new Main\SystemException(
				Localization\Loc::getMessage("SPOD_ACCESS_DENIED")
			);
		}
	}

	protected function loadOrder($id)
	{
		$registry = Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
		/** @var Order\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();

		if (!$this->order)
		{
			$this->order = $orderClassName::load($id);
		}
	}

	protected function obtainData()
	{
		$this->obtainOrder();
		$this->obtainBasket();
		$this->obtainShipment();
		$this->obtainPrice();
		$this->obtainPayment();
		$this->obtainDocument();
	}

	protected function obtainPayment()
	{
		$payment = null;

		if ($this->arParams['PAYMENT_ID'])
		{
			$payment = $this->order->getPaymentCollection()->getItemById($this->arParams['PAYMENT_ID']);
		}
		else
		{
			/** @var Order\Payment $item */
			foreach ($this->order->getPaymentCollection() as $item)
			{
				if (!$item->isPaid())
				{
					$payment = $item;
					break;
				}
			}
		}

		if ($payment)
		{
			$this->arResult['ACCOUNT_NUMBER'] = $payment->getField('ACCOUNT_NUMBER');
			$this->arResult['PAYMENT'] = $payment->getFieldValues();

			$dateBill = $payment->getField('DATE_BILL');
			if ($dateBill instanceof Main\Type\DateTime)
			{
				$date = new Main\Type\Date($dateBill);
				$this->arResult['DATE_BILL_FORMATTED'] = $date;
			}
			else
			{
				$this->arResult['DATE_BILL_FORMATTED'] = $dateBill;
			}
		}
	}

	protected function getPaymentId()
	{
		if (
			isset($this->arParams['PAYMENT_ID'])
			&& $this->arParams['PAYMENT_ID'] > 0
		)
		{
			return $this->arParams['PAYMENT_ID'];
		}

		return 0;
	}

	protected function obtainOrder()
	{
		$this->arResult['CURRENCY'] = $this->order->getCurrency();
	}

	protected function obtainBasket()
	{
		$this->arResult['BASKET'] = [];

		if ($this->arParams['PAYMENT_ID'])
		{
			/** @var Order\Payment $payment */
			$payment = $this->order->getPaymentCollection()->getItemById($this->arParams['PAYMENT_ID']);
			if ($payment)
			{
				/** @var Order\PayableBasketItem $item */
				foreach ($payment->getPayableItemCollection()->getBasketItems() as $item)
				{
					/** @var Order\BasketItem $basketItem */
					$basketItem = $item->getEntityObject();

					$basketValues = $this->extractBasketItemData($basketItem);
					$basketValues['QUANTITY'] = $item->getQuantity();

					$this->arResult['BASKET'][$basketValues['ID']] = $basketValues;
				}
			}
		}
		elseif ($this->order)
		{
			foreach ($this->order->getBasket() as $basketItem)
			{
				$basketValues = $this->extractBasketItemData($basketItem);

				$this->arResult['BASKET'][$basketValues['ID']] = $basketValues;
			}
		}
	}

	protected function extractBasketItemData(Order\BasketItem $basketItem)
	{
		$discounts = $this->order->getDiscount();
		$showPrices = $discounts->getShowPrices();

		$data = $showPrices['BASKET'][$basketItem->getBasketCode()];

		$basketItem->setFieldNoDemand('BASE_PRICE', $data['SHOW_BASE_PRICE']);
		$basketItem->setFieldNoDemand('PRICE', $data['SHOW_PRICE']);
		$basketItem->setFieldNoDemand('DISCOUNT_PRICE', $data['SHOW_DISCOUNT']);

		$basketValues = $basketItem->getFieldValues();
		$basketValues['BASE_PRICE_WITH_VAT'] = $basketItem->getBasePriceWithVat();
		$basketValues['PRICE_WITH_VAT'] = $basketItem->getPriceWithVat();

		$propertyCollection = $basketItem->getPropertyCollection();
		$basketValues['PROPERTIES'] = $propertyCollection ? $propertyCollection->getPropertyValues() : [];
		unset(
			$basketValues['PROPERTIES']['CATALOG.XML_ID'],
			$basketValues['PROPERTIES']['PRODUCT.XML_ID'],
			$basketValues['PROPERTIES'][\CIBlockPropertyTools::CODE_ARTNUMBER]
		);

		$basketValues['FORMATED_PRICE'] = SaleFormatCurrency($basketValues["PRICE_WITH_VAT"], $basketValues["CURRENCY"]);
		$basketValues['FORMATED_BASE_PRICE'] = SaleFormatCurrency($basketValues["BASE_PRICE_WITH_VAT"], $basketValues["CURRENCY"]);

		$iblockId = static::getIblockId($basketValues['PRODUCT_ID']);
		if ($iblockId)
		{
			$productRepository = Catalog\v2\IoC\ServiceContainer::getProductRepository($iblockId);
			if ($productRepository)
			{
				$product = $productRepository->getEntityById($basketValues['PRODUCT_ID']);
				if (!$product)
				{
					$skuRepository = Catalog\v2\IoC\ServiceContainer::getSkuRepository($iblockId);
					if ($skuRepository)
					{
						/** @var Catalog\v2\BaseEntity $product */
						$product = $skuRepository->getEntityById($basketValues['PRODUCT_ID']);
					}
				}

				$imageCollection = $product->getFrontImageCollection();
				$frontImage = $imageCollection->getFrontImage();

				$frontImageData = null;
				if ($frontImage)
				{
					$frontImageData = $frontImage->getFields();
				}

				if ($frontImageData
					&& isset($frontImageData['FILE_STRUCTURE'])
					&&
					(
						$this->arParams['PICTURE_WIDTH']
						|| $this->arParams['PICTURE_HEIGHT']
					)
				)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$frontImageData['FILE_STRUCTURE'],
						array("width" => $this->arParams['PICTURE_WIDTH'], "height" => $this->arParams['PICTURE_HEIGHT']),
						$this->arParams['RESAMPLE_TYPE'],
						true
					);

					$basketValues["PICTURE"] = array_change_key_case($arFileTmp, CASE_UPPER);
				}
				else
				{
					$basketValues["PICTURE"] = $frontImageData['FILE_STRUCTURE'];
				}
			}

		}

		return $basketValues;
	}

	protected function obtainShipment()
	{
		if ($this->arParams['PAYMENT_ID'])
		{
			/** @var Order\Payment $payment */
			$payment = $this->order->getPaymentCollection()->getItemById($this->arParams['PAYMENT_ID']);
			if ($payment)
			{
				/** @var Sale\PayableShipmentItem $item */
				foreach ($payment->getPayableItemCollection()->getShipments() as $item)
				{
					/** @var Order\Shipment $shipment */
					$shipment = $item->getEntityObject();
					if (!$shipment)
					{
						continue;
					}

					$this->arResult['SHIPMENT'] = $this->extractShipmentData($shipment);
				}
			}
		}
		elseif ($this->order)
		{
			foreach ($this->order->getShipmentCollection() as $shipment)
			{
				$this->arResult['SHIPMENT'] = $this->extractShipmentData($shipment);
			}
		}
	}

	protected function extractShipmentData(Order\Shipment $shipment)
	{
		$fields = $shipment->getFieldValues();

			$fields["PRICE_DELIVERY_FORMATTED"] = SaleFormatCurrency(
			$fields['PRICE_DELIVERY'],
			$fields['CURRENCY']
		);

		return $fields;
	}

	public function obtainPrice()
	{
		if (isset($this->arResult['BASKET']))
		{
			$this->arResult['BASE_PRODUCT_SUM'] = 0;
			$this->arResult['PRODUCT_SUM'] = 0;
			$this->arResult['DISCOUNT_VALUE'] = 0;

			foreach ($this->arResult['BASKET'] as $item)
			{
				$this->arResult['BASE_PRODUCT_SUM'] += $item["BASE_PRICE_WITH_VAT"] * $item['QUANTITY'];
				$this->arResult['PRODUCT_SUM'] += $item["PRICE_WITH_VAT"] * $item['QUANTITY'];
				$this->arResult['DISCOUNT_VALUE'] += $item["DISCOUNT_PRICE"] * $item['QUANTITY'];
			}
		}
	}

	private static function getIblockId($productId)
	{
		$iblockData = Iblock\ElementTable::getList([
			'select' => ['IBLOCK_ID'],
			'filter' => ['=ID' => $productId],
			'cache' => ['ttl' => 86400],
			'limit' => 1,
		])->fetch();

		return $iblockData['IBLOCK_ID'] ?? null;
	}

	/**
	 * Function implements all the life cycle of the component
	 * @return void
	 */
	public function executeComponent()
	{
		Localization\Loc::loadMessages(__FILE__);

		try
		{
			$this->setFrameMode(false);
			$this->checkRequiredModules();

			$this->loadOrder(urldecode(urldecode($this->arParams["ID"])));

			$this->checkOrder();
			$this->checkAuthorized();

			$this->obtainData();

			$this->formatResultPrices();
		}
		catch(Exception $e)
		{
			$this->arResult['ERRORS']['FATAL'][$e->getCode()] = $e->getMessage();
		}

		$this->includeComponentTemplate($this->getComponentTemplateNameByTemplateMode($this->arParams['TEMPLATE_MODE']));
	}

	private function getComponentTemplateNameByTemplateMode(string $mode): string
	{
		if ($mode === 'graymode')
		{
			return 'graytheme';
		}

		return '';
	}

	/**
	 * Function formats price info in arResult
	 * @return void
	 */
	protected function formatResultPrices()
	{
		$this->arResult["PRICE_FORMATED"] = SaleFormatCurrency($this->arResult["PAYMENT"]['SUM'], $this->arResult['CURRENCY']);
		$this->arResult["PRODUCT_SUM_FORMATED"] = SaleFormatCurrency($this->arResult["PRODUCT_SUM"], $this->arResult["CURRENCY"]);
		$this->arResult["BASE_PRODUCT_SUM_FORMATED"] = SaleFormatCurrency($this->arResult["BASE_PRODUCT_SUM"], $this->arResult["CURRENCY"]);
		$this->arResult["PRODUCT_SUM_DISCOUNT_FORMATED"] = SaleFormatCurrency(
			$this->arResult["BASE_PRODUCT_SUM"] - $this->arResult["PRODUCT_SUM"],
			$this->arResult["CURRENCY"]
		);

		if (doubleval($this->arResult["DISCOUNT_VALUE"]))
		{
			$this->arResult["DISCOUNT_VALUE_FORMATED"] = SaleFormatCurrency(
				$this->arResult["DISCOUNT_VALUE"],
				$this->arResult["CURRENCY"]
			);
		}
	}

	/**
	 * Function checks if required modules installed. If not, throws an exception
	 * @throws Main\SystemException
	 * @return void
	 */
	protected function checkRequiredModules()
	{
		if (!Loader::includeModule('sale'))
		{
			throw new Main\SystemException(
				Localization\Loc::getMessage("SPOD_SALE_MODULE_NOT_INSTALL")
			);
		}
	}

	protected function obtainDocument(): void
	{
		if (!Loader::includeModule('crm'))
		{
			return;
		}

		$documentGeneratorManager = \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance();
		if (!$documentGeneratorManager->isEnabled())
		{
			return;
		}

		$paymentId = (int)($this->arResult['PAYMENT']['ID'] ?? 0);
		if (!$paymentId)
		{
			return;
		}

		$documentId = $documentGeneratorManager->getPaymentBoundDocumentId($paymentId);
		if (!$documentId)
		{
			return;
		}

		$document = \Bitrix\DocumentGenerator\Document::loadById($documentId);
		if (!$document)
		{
			return;
		}

		$downloadUrl = Main\Engine\UrlManager::getInstance()->createByBitrixComponent(
			$this,
			'downloadDocument',
			[
				'orderId' => $this->order->getId(),
				'paymentId' => $paymentId,
				'hash' => $this->order->getHash(),
			],
			true
		);

		$this->arResult['DOCUMENT'] = [
			'docx' => [
				'id' => $document->FILE_ID,
				'fileName' => $document->getFileName(),
				'url' => $downloadUrl->getLocator(),
			],
			'pdf' => [
				'id' => $document->PDF_ID,
				'fileName' => $document->getFileName('pdf'),
				'url' => $downloadUrl->addParams(['extension' => 'pdf'])->getLocator(),
			],
			'title' => $document->getTitle(),
			'showUrl' => Main\Engine\UrlManager::getInstance()->createByBitrixComponent(
				$this,
				'showPdf',
				[
					'orderId' => $this->order->getId(),
					'paymentId' => $paymentId,
					'hash' => $this->order->getHash(),
				],
				true
			),
		];
	}

	public function configureActions(): array
	{
		$configureActions = [];

		$documentActionConfiguration = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Authentication::class,
				Csrf::class,
			],
		];

		$configureActions['downloadDocument'] = $documentActionConfiguration;
		$configureActions['showPdf'] = $documentActionConfiguration;

		return $configureActions;
	}

	protected function initBeforeDocumentAction(int $orderId, int $paymentId, string $hash): void
	{
		$this->errorCollection = new Main\ErrorCollection();

		$this->checkRequiredModules();
		$this->loadOrder(urldecode($orderId));
		$this->checkOrder();
		if ($this->order->getHash() !== $hash)
		{
			throw new Main\SystemException(
				Localization\Loc::getMessage("SPOD_ACCESS_DENIED")
			);
		}

		$payment = $this->order->getPaymentCollection()->getItemById($paymentId);
		if (!$payment)
		{
			throw new Main\SystemException(
				Localization\Loc::getMessage("SPOD_NO_ORDER")
			);
		}

		$this->arParams['PAYMENT_ID'] = $paymentId;

		$this->obtainPayment();
		$this->obtainDocument();

		if (empty($this->arResult['DOCUMENT']))
		{
			throw new Main\SystemException('Document not found');
		}
	}

	public function downloadDocumentAction(int $orderId, int $paymentId, string $hash, string $extension = 'docx'): ?Main\Engine\Response\BFile
	{
		try
		{
			$this->initBeforeDocumentAction($orderId, $paymentId, $hash);

			$fileId = $this->arResult['DOCUMENT'][$extension]['id'] ?? 0;
			$bFileId = \Bitrix\DocumentGenerator\Model\FileTable::getBFileId((int)$fileId);
			$fileName = $this->arResult['DOCUMENT'][$extension]['fileName'];

			if ($bFileId > 0)
			{
				return Main\Engine\Response\BFile::createByFileId($bFileId, $fileName);
			}

			throw new Main\SystemException('Document file not found');
		}
		catch(Main\SystemException $e)
		{
			$this->errorCollection[] = new Bitrix\Main\Error($e->getMessage(), $e->getCode());
		}

		return null;
	}

	public function showPdfAction(int $orderId, int $paymentId, string $hash): ?Main\Response
	{
		try
		{
			$this->initBeforeDocumentAction($orderId, $paymentId, $hash);

			$path = null;
			$fileId = $this->arResult['DOCUMENT']['pdf']['id'] ?? null;
			if ($fileId > 0)
			{
				$path = $this->arResult['DOCUMENT']['pdf']['url'];
			}

			if ($path)
			{
				$response = new Main\HttpResponse();
				global $APPLICATION;
				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:pdf.viewer',
					'',
					[
						'PATH' => $path,
						'IFRAME' => 'Y',
						'PRINT' => 'N',
						'TITLE' => $this->arResult['DOCUMENT']['title'],
						'WIDTH' => 900,
						'HEIGHT' => 700,
					]
				);
				$response->setContent(ob_get_contents());
				ob_end_clean();

				return $response;
			}

			throw new Main\SystemException('Document file not found');
		}
		catch(Main\SystemException $e)
		{
			$this->errorCollection[] = new Main\Error($e->getMessage(), $e->getCode());
		}

		return null;
	}

	public function getErrors()
	{
		return $this->errorCollection ? $this->errorCollection->getValues() : [];
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection ? $this->errorCollection->getErrorByCode($code) : null;
	}
}
