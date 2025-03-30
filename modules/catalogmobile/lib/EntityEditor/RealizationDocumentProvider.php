<?php

namespace Bitrix\CatalogMobile\EntityEditor;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Config\State;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product\RealizationProduct;

Loader::requireModule('crm');

class RealizationDocumentProvider extends \Bitrix\UI\EntityEditor\BaseProvider
{
	use \Bitrix\Crm\Component\EntityDetails\SaleProps\ComponentTrait;

	protected const NOT_DEDUCTED = 'N';
	protected const DEDUCTED = 'Y';
	protected const CANCELED = 'C';

	private ?\Bitrix\Sale\Shipment $shipment;
	private ?\Bitrix\Crm\Order\Order $order;
	private ?\Bitrix\Crm\Order\Payment $payment;
	private array $context;

	public function __construct(?int $documentId, array $context = [])
	{
		$this->context = $context;
		if ($documentId)
		{
			$this->shipment = \Bitrix\Sale\Repository\ShipmentRepository::getInstance()->getById($documentId);
			if (!$this->shipment)
			{
				throw new \DomainException('Realization document not found!');
			}
			$this->order = $this->shipment->getOrder();
		}
		else if ($context['paymentId'])
		{
			$this->payment = \Bitrix\Sale\Repository\PaymentRepository::getInstance()->getById($context['paymentId']);
			if ($this->payment)
			{
				$this->order = $this->payment->getOrder();
				$this->shipment = $this->order->getShipmentCollection()->createItem();
			}
			else
			{
				throw new \DomainException('Payment document not found!');
			}
		}
		elseif ($context['orderId'] && $context['orderId'] > 0)
		{
			$this->order = \Bitrix\Crm\Order\Order::load($context['orderId']);
			if ($this->order)
			{
				$this->shipment = $this->order->getShipmentCollection()->createItem();
			}
			else
			{
				throw new \DomainException('Order not found!');
			}
		}
		else
		{
			$this->order = \Bitrix\Crm\Order\Manager::createEmptyOrder(SITE_ID);
			$this->shipment = $this->order->getShipmentCollection()->createItem();
		}
	}

	public function isReadOnly(): bool
	{
		return
			!$this->checkDocumentReadRight()
			|| !$this->checkDocumentModifyRight()
			|| $this->shipment->getField('DEDUCTED') === 'Y'
		;
	}

	private function checkDocumentModifyRight(): bool
	{
		return AccessController::getCurrent()->checkByValue(
			ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
			\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
		);
	}

	private function checkDocumentReadRight(): bool
	{
		return
			AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
			&& AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
				\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
			)
		;
	}

	public function getGUID(): string
	{
		return 'MOBILE_REALIZATION_DOCUMENT_DETAIL_W';
	}

	public function getEntityId(): ?int
	{
		return $this->shipment->getId();
	}

	public function getEntityTypeName(): string
	{
		return 'store_document';
	}

	public function getEntityFields(): array
	{
		$entityFields = [
			[
				'name' => 'CLIENT',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_CLIENT'),
				'type' => 'client_light',
				'editable' => true,
				'requiredConditionally' => true,
				'required' => true,
				'multiple' => false,
				'data' => [
					'compound' => [
						[
							'name' => 'COMPANY_ID',
							'type' => 'company',
							'entityTypeName' => \CCrmOwnerType::CompanyName,
							'tagName' => \CCrmOwnerType::CompanyName,
						],
						[
							'name' => 'CONTACT_IDS',
							'type' => 'multiple_contact',
							'entityTypeName' => \CCrmOwnerType::ContactName,
							'tagName' => \CCrmOwnerType::ContactName,
						],
					],
					'map' => ['data' => 'CLIENT_DATA'],
					'info' => 'CLIENT_INFO',
					'lastCompanyInfos' => 'LAST_COMPANY_INFOS',
					'lastContactInfos' => 'LAST_CONTACT_INFOS',
					'loaders' => [
						'primary' => [
							\CCrmOwnerType::CompanyName => [
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
							],
							\CCrmOwnerType::ContactName => [
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
							],
						],
						'secondary' => [
							\CCrmOwnerType::CompanyName => [
								'action' => 'GET_SECONDARY_ENTITY_INFOS',
								'url' => '/bitrix/components/bitrix/crm.store.document.detail/ajax.php?'.bitrix_sessid_get(),
							],
						],
					],
					'clientEditorFieldsParams' => $this->prepareClientEditorFieldsParams(),
					'useExternalRequisiteBinding' => true,
					'permissions' => $this->getClientPermissions(),
					'hasSolidBorder' => true,
				],
			],
			[
				'name' => 'ID',
				'title' => 'ID',
				'type' => 'hidden',
				'editable' => false,
			],
			[
				'name' => 'ORDER_ID',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_ORDER_ID'),
				'type' => 'hidden',
				'editable' => false,
			],
			[
				'name' => 'XML_ID',
				'title' => 'XML_ID',
				'type' => 'hidden',
				'editable' => false,
			],
			[
				'name' => 'DELIVERY_NAME',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_DELIVERY_NAME'),
				'type' => 'text',
				'editable' => false,
				'showNew' => true,
			],
			[
				'name' => 'PRICE_DELIVERY_CALCULATED_WITH_CURRENCY',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'),
				'type' => 'opportunity',
				'editable' => false,
				'showNew' => true,
				'data' => [
					'largeFormat' => true,
					'affectedFields' => ['CURRENCY', 'PRICE_DELIVERY_CALCULATED'],
					'currency' => [
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(\CCrmCurrencyHelper::PrepareListItems()),
					],
					'amount' => 'PRICE_DELIVERY_CALCULATED',
					'formatted' => 'FORMATTED_PRICE_DELIVERY_CALCULATED',
					'formattedWithCurrency' => 'FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY',
				],
			],
			[
				'name' => 'PRICE_DELIVERY_WITH_CURRENCY',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_PRICE_DELIVERY_WITH_CURRENCY'),
				'type' => 'opportunity',
				'editable' => false,
				'showNew' => true,
				'data' => [
					'largeFormat' => true,
					'affectedFields' => ['CURRENCY', 'PRICE_DELIVERY'],
					'currency' => [
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(\CCrmCurrencyHelper::PrepareListItems()),
					],
					'amount' => 'PRICE_DELIVERY',
					'formatted' => 'FORMATTED_PRICE_DELIVERY',
					'formattedWithCurrency' => 'FORMATTED_PRICE_DELIVERY_WITH_CURRENCY',
				],
			],
			[
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_COMMENTS'),
				'type' => 'text',
				'editable' => false,
			],
			[
				'name' => 'EXTRA_SERVICES_DATA',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_EXTRA_SERVICES_DATA'),
				'type' => 'shipment_extra_services',
				'editable' => false,
			],
			[
				'name' => 'RESPONSIBLE_ID',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_RESPONSIBLE_ID'),
				'type' => 'user',
				'editable' => true,
				'required' => true,
				'multiple' => false,
				'data' => [
					'entityListField' => 'RESPONSIBLE_ID_ENTITY_LIST',
					'provider' => 'CATALOG_DOCUMENT',
					'hasSolidBorder' => true,
				],
			],
			[
				'name' => 'DOCUMENT_PRODUCTS',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_DOCUMENT_PRODUCTS'),
				'type' => 'product_row_summary',
				'editable' => false,
			],
			[
				'name' => 'DOC_STATUS',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_DOC_STATUS'),
				'type' => 'status',
				'editable' => false,
				'showAlways' => true,
			],
		];

		if ($this->needDeliveryBlock())
		{
			$entityFields = [...$entityFields, ...$this->getShipmentPropertiesFields()];
		}

		return $entityFields;
	}

	private function prepareClientEditorFieldsParams(): array
	{
		$result = [
			\CCrmOwnerType::ContactName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact, 'requisite'),
			],
			\CCrmOwnerType::CompanyName => [
				'REQUISITES' => \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company, 'requisite'),
			],
		];
		if (\Bitrix\Main\Loader::includeModule('location'))
		{
			$result[\CCrmOwnerType::ContactName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Contact,'requisite_address');
			$result[\CCrmOwnerType::CompanyName]['ADDRESS'] = \CCrmComponentHelper::getFieldInfoData(\CCrmOwnerType::Company,'requisite_address');
		}

		return $result;
	}

	protected function getClientPermissions(): array
	{
		$entityTypeIds = [\CCrmOwnerType::Contact, \CCrmOwnerType::Company];
		$permissions = [];
		foreach ($entityTypeIds as $entityTypeId)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			$serviceUserPermissions = Container::getInstance()->getUserPermissions();
			$permissions[$entityTypeName] = [
				'read' => $serviceUserPermissions->checkReadPermissions($entityTypeId),
				'add' => $serviceUserPermissions->checkAddPermissions($entityTypeId),
			];
		}

		return $permissions;
	}

	public function getEntityConfig(): array
	{
		$entityConfig = [
			[
				'name' => 'main',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_TAB_MAIN'),
				'type' => 'section',
				'elements' => [
					['name' => 'DOC_STATUS'],
					['name' => 'CLIENT'],
				],
				'data' => [
					'isRemovable' => 'false',
				],
			],
		];

		if ($this->needDeliveryBlock())
		{
			$entityConfig[] = [
				'name' => 'delivery',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_TAB_DELIVERY'),
				'type' => 'section',
				'elements' => [
					['name' => 'DELIVERY_NAME'],
					['name' => 'PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'],
					['name' => 'PRICE_DELIVERY_WITH_CURRENCY'],
					['name' => 'COMMENTS'],
					['name' => 'EXTRA_SERVICES_DATA'],
				],
				'data' => [
					'isRemovable' => 'false',
				],
			];

			$entityConfig[] = [
				'name' => 'properties',
				'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_TAB_PROPERTIES'),
				'type' => 'section',
				'elements' => $this->getShipmentPropertiesConfigElements(),
				'data' => [
					'showButtonPanel' => false,
					'isRemovable' => 'false',
				],
			];
		}

		$entityConfig[] = [
			'name' => 'products',
			'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_TAB_PRODUCTS'),
			'type' => 'section',
			'elements' => [
				['name' => 'DOCUMENT_PRODUCTS'],
			],
			'data' => [
				'isRemovable' => 'false',
			],
		];
		$entityConfig[] = [
			'name' => 'extra',
			'title' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_TAB_EXTRA'),
			'type' => 'section',
			'elements' => [
				['name' => 'RESPONSIBLE_ID'],
			],
			'data' => [
				'isRemovable' => 'false',
			],
		];

		return $entityConfig;
	}

	private function getShipmentPropertiesConfigElements(): array
	{
		$elements = [];
		foreach ($this->shipment->getPropertyCollection() as $property)
		{
			$elements[] = ['name' => $property->getField('CODE')];
		}

		return $elements;
	}

	private function getShipmentPropertiesFields(): array
	{
		$fields = [];
		foreach ($this->shipment->getPropertyCollection() as $property)
		{
			$fields[] = [
				'name' => $property->getField('CODE'),
				'title' => $property->getName(),
				'type' => mb_strtolower($property->getType()),
				'editable' => false,
			];
		}

		return $fields;
	}

	private function getShipmentPropertiesData(): array
	{
		$data = [];
		foreach ($this->shipment->getPropertyCollection() as $property)
		{
			if ($property->getType() === 'ADDRESS')
			{
				$data[$property->getField('CODE')] = [
					[
						$property->getValue() ? str_replace('<br />', "\r\n", $property->getViewHtml()) : "",
					],
					[
						$property->getValue()['latitude'] ?? null,
						$property->getValue()['longitude'] ?? null,
					],
					$property->getValue()['id'] ?? null,
				];
			}
			else
			{
				$data[$property->getField('CODE')] = $property->getValue();
			}
		}

		return $data;
	}

	public function getEntityData(): array
	{
		$entityData = $this->shipment->getFieldValues();

		if (isset($entityData['DATE_INSERT']))
		{
			$entityData['DATE_INSERT'] = \CCrmComponentHelper::TrimZeroTime($entityData['DATE_INSERT']);
		}

		if (!isset($this->entityData['CURRENCY']) || $this->entityData['CURRENCY'] === '')
		{
			if ($this->order->getCurrency())
			{
				$entityData['CURRENCY'] = $this->order->getCurrency();
			}
			else
			{
				$entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();
			}
		}

		if (!$this->shipment->getId())
		{
			$entityData['RESPONSIBLE_ID'] = \Bitrix\Main\Engine\CurrentUser::get()->getId();

			$bindingEntity = $this->getOwnerEntity();
			if ($bindingEntity)
			{
				$entityData['RESPONSIBLE_ID'] = $bindingEntity->getAssignedById();
			}
		}

		$entityData['DOC_TYPE'] = 'W';
		$entityData['RESPONSIBLE_ID_ENTITY_LIST'] = $this->getResponsibleIdEntityList($entityData);
		$entityData['CLIENT_INFO'] = $this->getClientInfo();
		$entityData['DOCUMENT_PRODUCTS'] = $this->getDocumentProductsPreview($entityData);
		$entityData['DOC_STATUS'] = $this->getDocStatus($entityData);
		if ($this->needDeliveryBlock())
		{
			$entityData['DELIVERY_NAME'] = $this->getDeliveryName((int)$entityData['DELIVERY_ID']);

			$calcPrice = $this->shipment->calculateDelivery();
			if (!$calcPrice->isSuccess())
			{
				$entityData['ERRORS'] = $calcPrice->getErrorMessages();
			}

			if ($entityData['CUSTOM_PRICE_DELIVERY'] !== 'Y' && $this->shipment->getId() <= 0)
			{
				$entityData['PRICE_DELIVERY'] = $calcPrice->getPrice();
			}

			$entityData['FORMATTED_PRICE_DELIVERY_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
				$entityData['PRICE_DELIVERY'],
				$entityData['CURRENCY'],
				''
			);
			$entityData['FORMATTED_PRICE_DELIVERY'] = \CCrmCurrency::MoneyToString(
				$entityData['PRICE_DELIVERY'],
				$entityData['CURRENCY'],
				'#'
			);

			$entityData['PRICE_DELIVERY_CALCULATED'] = $calcPrice->getPrice();

			$entityData['FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
				$entityData['PRICE_DELIVERY_CALCULATED'],
				$entityData['CURRENCY'],
				''
			);
			$entityData['FORMATTED_PRICE_DELIVERY_CALCULATED'] = \CCrmCurrency::MoneyToString(
				$entityData['PRICE_DELIVERY_CALCULATED'],
				$entityData['CURRENCY'],
				'#'
			);

			if ($entityData['DELIVERY_ID'] > 0)
			{
				$extraServiceManager = new \Bitrix\Sale\Delivery\ExtraServices\Manager($entityData['DELIVERY_ID']);
				$extraServiceManager->setOperationCurrency($entityData['CURRENCY']);
				$extraServiceManager->setValues($this->shipment->getExtraServices());
				$extraService = $extraServiceManager->getItems();

				$entityData['EXTRA_SERVICES_DATA'] = $this->getExtraServices(
					$extraService,
					$this->shipment
				);
			}

			$entityData = array_merge($entityData, $this->getShipmentPropertiesData());
		}

		/*
		 * perhaps this is not the best idea, but I don't really know how else can we pass it to the detail card without
		 * having to specify it in every place we open the card from
		 */
		$entityData['IS_EXTERNAL_CATALOG'] = State::isExternalCatalog();

		return $entityData;
	}

	public function getExtraServices($extraService, \Bitrix\Crm\Order\Shipment $shipment)
	{
		$result = [];

		foreach ($extraService as $item)
		{
			$result[] = [
				'name' => htmlspecialcharsbx($item->getName()),
				'value' => html_entity_decode($item->getViewControl()),
			];
		}

		return $result;
	}

	public function getEntityControllers(): array
	{
		return [

			[
				'name' => 'PRODUCT_LIST_CONTROLLER',
				'type' => 'catalog_store_document_product_list',
				'config' => [
					'currencyFieldName' => 'CURRENCY',
					'priceWithCurrencyFieldName' => 'TOTAL_WITH_CURRENCY',
					'productSummaryFieldName' => 'DOCUMENT_PRODUCTS',
				],
			],
		];
	}

	private function needDeliveryBlock(): bool
	{
		$isOwnerContext = $this->context['ownerTypeId'] && $this->context['ownerId'];
		$hasBinding = $this->order->getEntityBinding() && !empty($this->order->getEntityBinding()->toArray());

		return $isOwnerContext || $hasBinding;
	}

	private function getDeliveryName(int $deliveryId): ?string
	{
		$delivery = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId);
		if (!$delivery)
		{
			return null;
		}

		return $delivery->getNameWithParent();
	}

	private function getDocStatus(array $document): array
	{
		$statusesList = [
			self::DEDUCTED => [
				'name' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_STATUS_DEDUCTED'),
				'backgroundColor' => '#e0f5c2',
				'color' => '#589309',
			],
			self::NOT_DEDUCTED => [
				'name' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_STATUS_NOT_DEDUCTED'),
				'backgroundColor' => '#e0e2e4',
				'color' => '#79818b',
			],
			self::CANCELED => [
				'name' => Loc::getMessage('REALIZATION_DOCUMENT_PROVIDER_STATUS_CANCELED'),
				'backgroundColor' => '#faf4a0',
				'color' => '#9d7e2b',
			],
		];

		$value = [];

		if ($document['DEDUCTED'] === 'N' && !empty($document['EMP_DEDUCTED_ID']))
		{
			$value[] = $statusesList[self::CANCELED];
		}

		if ($document['DEDUCTED'] === 'N' && empty($document['EMP_DEDUCTED_ID']))
		{
			$value[] = $statusesList[self::NOT_DEDUCTED];
		}

		if ($document['DEDUCTED'] === 'Y')
		{
			$value[] = $statusesList[self::DEDUCTED];
		}

		return $value;
	}

	private function getDocumentProductsPreview(array $entityData): array
	{
		$documentProductSummaryInfo = $this->getProductSummaryInfo($entityData);
		$documentProductSummaryInfo['isReadOnly'] = $this->isReadOnly();

		return $documentProductSummaryInfo;
	}

	private function getProductSummaryInfo(array $entityData): array
	{
		$total = 0.0;
		$count = 0;
		if (!\CCrmSaleHelper::isWithOrdersMode())
		{
			if (!$entityData['ID'])
			{
				$entityProducts = RealizationProduct::getEntityProducts($this->context);
				foreach ($entityProducts as $entityProduct)
				{
					$total += $entityProduct['price']['vat']['priceWithVat'] * $entityProduct['amount'];
					$count++;
				}
			}
			else
			{
				foreach ($this->shipment->getShipmentItemCollection() as $shipmentItem)
				{
					$basketItem = $shipmentItem->getBasketItem();
					$total += $basketItem->getPriceWithVat() * $shipmentItem->getQuantity();
					$count++;
				}
			}
		}

		return [
			'count' => $count,
			'total' => \CCurrencyLang::CurrencyFormat($total, $entityData['CURRENCY']),
			'totalRaw' => [
				'amount' => $total,
				'currency' => $entityData['CURRENCY'],
			],
		];
	}

	private function getOwnerEntity(): ?\Bitrix\Crm\Item
	{
		static $item = null;

		if ($item)
		{
			return $item;
		}

		$ownerTypeId = $this->context['ownerTypeId'];
		$ownerId = $this->context['ownerId'];

		$isOwnerContext = $ownerTypeId && $ownerId;
		if (!$isOwnerContext)
		{
			$entityBinding = $this->order->getEntityBinding();
			if ($entityBinding)
			{
				$ownerTypeId = $entityBinding->getOwnerTypeId();
				$ownerId = $entityBinding->getOwnerId();
			}
		}

		if ($ownerTypeId && $ownerId)
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($ownerTypeId);
			if ($factory)
			{
				$item = $factory->getItem($ownerId);
				if ($item)
				{
					return $item;
				}
			}
		}

		return null;
	}

	protected function getResponsibleIdEntityList(array $entityData): array
	{
		$userId = isset($entityData['RESPONSIBLE_ID']) ? (int)$entityData['RESPONSIBLE_ID'] : 0;
		if ($userId <= 0)
		{
			return [];
		}

		$user = \Bitrix\Crm\Service\Container::getInstance()->getUserBroker()->getById($userId);
		if (!is_array($user))
		{
			return [];
		}

		$formattedName =
			\CUser::FormatName(
				\CSite::GetNameFormat(false),
				[
					'LOGIN' => $user['LOGIN'] ?? '',
					'NAME' => $user['NAME'] ?? '',
					'LAST_NAME' => $user['LAST_NAME'] ?? '',
					'SECOND_NAME' => $user['SECOND_NAME'] ?? '',
				],
				true,
				false
			)
		;

		$imageUrl = null;
		if ((int)$user['PERSONAL_PHOTO'] > 0)
		{
			$fileInfo = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'],
				[
					'width' => 60,
					'height'=> 60,
				],
				BX_RESIZE_IMAGE_EXACT
			);
			if (is_array($fileInfo) && isset($fileInfo['src']))
			{
				$imageUrl = $fileInfo['src'];
			}
		}

		return [
			[
				'id' => (int)$user['ID'],
				'title' => $formattedName,
				'imageUrl' => $imageUrl,
			],
		];
	}

	protected function getClientInfo(): array
	{
		$companyId = 0;
		$contactIds = [];

		if (!$this->order)
		{
			return [];
		}

		if (!$this->shipment->getId())
		{
			$bindingEntity = $this->getOwnerEntity();
			if ($bindingEntity)
			{
				$companyId = $bindingEntity->getCompanyId();

				$contacts = $bindingEntity->getContacts();
				foreach ($contacts as $contact)
				{
					$contactIds[] = $contact->getId();
				}
			}
		}
		else
		{
			$clientCollection = $this->order->getContactCompanyCollection();
			if ($clientCollection && !$clientCollection->isEmpty())
			{
				/** @var \Bitrix\Crm\Order\Company $company */
				if ($company = $clientCollection->getPrimaryCompany())
				{
					$companyId = $company->getField('ENTITY_ID');
				}

				$contacts = $clientCollection->getContacts();
				/** @var \Bitrix\Crm\Order\Contact $contact */
				foreach ($contacts as $contact)
				{
					$contactIds[] = $contact->getField('ENTITY_ID');
				}
			}
		}

		$clientInfo = [
			'COMPANY_DATA' => [],
			'CONTACT_DATA' => [],
		];

		if ($companyId > 0)
		{
			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyId, \CCrmPerms::GetCurrentUserPermissions());
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				\CCrmOwnerType::CompanyName,
				$companyId,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
				]
			);

			$clientInfo['COMPANY_DATA'] = [$companyInfo];
		}

		$iteration = 0;

		foreach ($contactIds as $contactID)
		{
			$isEntityReadPermitted = \CCrmContact::CheckReadPermission($contactID, \CCrmPerms::GetCurrentUserPermissions());
			$clientInfo['CONTACT_DATA'][] = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				\CCrmOwnerType::ContactName,
				$contactID,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => ($iteration === 0), // load full requisite data for first item only (due to performance optimisation)
					'REQUIRE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
					'NORMALIZE_MULTIFIELDS' => true,
				]
			);
			$iteration++;
		}

		return $clientInfo;
	}
}
