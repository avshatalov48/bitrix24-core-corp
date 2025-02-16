<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Service\Sign\B2e\TypeService;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Document;
use Bitrix\Sign\Type\Document\EntityType;

class SmartB2eDocument extends Dynamic
{
	protected $itemClassName = Item\SmartB2eDocument::class;

	public const USER_FIELD_ENTITY_ID = 'CRM_SMART_B2E_DOC';
	public const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_SMART_B2E_DOC_SPD';
	public const NUMERATOR_TYPE = 'CRM_SMART_B2E_DOC';

	public function getEntityDescription(): string
	{
		return \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartB2eDocument);
	}

	public function getUserFieldEntityId(): string
	{
		return static::USER_FIELD_ENTITY_ID;
	}

	public function isAutomationEnabled(): bool
	{
		return true;
	}

	public function isBizProcEnabled(): bool
	{
		return false;
	}

	public function isLinkWithProductsEnabled(): bool
	{
		return false;
	}

	public static function createTypeIfNotExists(): void
	{
		$type = TypeTable::getByEntityTypeId(\CCrmOwnerType::SmartB2eDocument)->fetchObject();
		if ($type)
		{
			return;
		}
		if (TypeTable::isCreatingInProgress(\CCrmOwnerType::SmartB2eDocument))
		{
			return;
		}

		Container::getInstance()->getLocalization()->loadMessages();

		$type =
			TypeTable::createObject()
				->setName('SmartB2eDocument')
				->setEntityTypeId(\CCrmOwnerType::SmartB2eDocument)
				->setTitle(Loc::getMessage('CRM_TYPE_SMART_B2E_DOC_TYPE_TITLE'))
				->setCode('BX_SMART_B2E_DOC')
				->setCreatedBy(0)
				->setIsCategoriesEnabled(true)
				->setIsStagesEnabled(true)
				->setIsBeginCloseDatesEnabled(true)
				->setIsClientEnabled(true)
				->setIsUseInUserfieldEnabled(false)
				->setIsLinkWithProductsEnabled(false)
				->setIsCrmTrackingEnabled(true)
				->setIsMycompanyEnabled(true)
				->setIsDocumentsEnabled(false)
				->setIsSourceEnabled(true)
				->setIsObserversEnabled(true)
				->setIsRecyclebinEnabled(false)
				->setIsAutomationEnabled(true)
				->setIsBizProcEnabled(true)
				->setIsPaymentsEnabled(false)
				->setIsSetOpenPermissions(false)
		;

		/** @var AddResult $result */
		$result = $type->save();
		if (!$result->isSuccess())
		{
			AddMessage2Log(
				'Error while trying to create SmartB2eDocument type: ' . implode(', ', $result->getErrorMessages()),
				'crm',
			);
		}

		if (!self::updateDefaultCategory())
		{
			AddMessage2Log(
				'Error while trying to update SmartB2eDocument default category',
				'crm',
			);
		}

		if (!self::addFromEmployeeCategory())
		{
			AddMessage2Log(
				'Error while trying to update SmartB2eDocument from employee category',
				'crm',
			);
		}

		\Bitrix\Crm\Service\Container::getInstance()
			->getDynamicTypesMap()
			->invalidateTypesCollectionCache()
		;
	}

	private static function updateDefaultCategory(): bool
	{
		//TODO fast solution. will be removed in future
		$typeService = Container::getInstance()->getSignB2eTypeService();

		$updateDefaultCategoryResult = $typeService->updateDefaultCategory(
			Loc::getMessage('CRM_TYPE_SMART_B2E_DOC_TO_EMPLOYEE_CATEGORY_NAME') ?? '',
			TypeService::SIGN_B2E_ITEM_CATEGORY_CODE,
		);

		return $updateDefaultCategoryResult->isSuccess();
	}

	private static function addFromEmployeeCategory(): bool
	{
		$typeService = Container::getInstance()->getSignB2eTypeService();

		$addCategoryResult = $typeService->addCategory(
			Loc::getMessage('CRM_TYPE_SMART_B2E_DOC_FROM_EMPLOYEE_CATEGORY_NAME') ?? '',
			TypeService::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE,
		);
		if (!$addCategoryResult->isSuccess())
		{
			return false;
		}

		$categoryId = $addCategoryResult->getId();

		if (!is_int($categoryId))
		{
			return false;
		}

		$addCategoryDefaultPermissionsResult = $typeService->addCategoryDefaultPermissions($categoryId);

		return $addCategoryDefaultPermissionsResult->isSuccess();
	}


	public function getEditorAdapter(): EditorAdapter
	{
		$adapter = parent::getEditorAdapter();

		$locationField = $this->getFieldsCollection()->getField(Item::FIELD_NAME_LOCATION_ID);
		if ($locationField && $locationField->isDisplayed())
		{
			$adapter->addEntityField(EditorAdapter::getLocationFieldDescription($locationField));
		}

		$clientField = $adapter->getAdditionalField($adapter::FIELD_CLIENT);
		$clientField['data']['fixedLayoutType'] = 'CONTACT';

		unset($clientField['data']['duplicateControl']);
		$adapter->addEntityField($clientField);

		return $adapter;
	}

	public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
	{
		$operation = parent::getDeleteOperation($item, $context);

		$operation->addAction(
			Operation::ACTION_BEFORE_SAVE,
			new class extends Operation\Action {
				public function process(Item $item): Result
				{
					$result = new Result();

					if (\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled())
					{
						$documentServiceContainer = \Bitrix\Sign\Service\Container::instance()->getDocumentService();
						//todo check remove if after install sign23.600
						if (
							defined('\Bitrix\Sign\Type\Document\EntityType::SMART_B2E')
							&& method_exists($documentServiceContainer, 'canBeChanged')
						)
						{
							$document = \Bitrix\Sign\Service\Container::instance()
								->getDocumentRepository()
								->getByEntityIdAndType($item->getId(), EntityType::SMART_B2E)
							;
							if (
								$document !== null
								&& !$documentServiceContainer->canBeChanged($document)
							)
							{
								$result->addError(
									new \Bitrix\Main\Error(
										Loc::getMessage('CRM_TYPE_SMART_B2E_DOC_DELETE_DENIED'),
										'SIGN_ERROR_DENIED'
									)
								);
							}
						}
						else // todo remove after install sign 23.600
						{
							$document = Document::resolveByEntity('SMART_B2E', $item->getId());
							if ($document && !$document->canBeChanged())
							{
								$result->addError(new \Bitrix\Main\Error(
									Loc::getMessage('CRM_TYPE_SMART_B2E_DOC_DELETE_DENIED'),
									'SIGN_ERROR_DENIED'
								));
							}
						}
					}

					return $result;
				}
			}
		);

		$operation->addAction(
			Operation::ACTION_AFTER_SAVE,
			new class extends Operation\Action {
				public function process(Item $item): Result
				{
					if (!\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled())
					{
						return new Result();
					}

					$itemOld = \Bitrix\Crm\Service\Operation\Action::getItemBeforeSave();
					if (!$itemOld)
					{
						return new Result();
					}

					$documentRepository = \Bitrix\Sign\Service\Container::instance()
						->getDocumentRepository();
					if (method_exists($documentRepository, 'getByEntityIdAndType'))
					{
						$document = $documentRepository
							->getByEntityIdAndType($itemOld->getId(), 'SMART_B2E')
						;
						if (!$document)
						{
							return new Result();
						}

						$documentRepository->delete($document);
					}
					else
					{
						$document = Document::resolveByEntity('SMART_B2E', $itemOld->getId());
						$document?->unlink();
					}

					return new Result();
				}
			}
		);

		return $operation;
	}

	public function getFieldsSettings(): array
	{
		$settings = parent::getFieldsSettings();
		$settings[Item::FIELD_NAME_MYCOMPANY_ID]['SETTINGS']['isEmbeddedEditorEnabled'] = true;
		$settings[Item::FIELD_NAME_MYCOMPANY_ID]['SETTINGS']['usePermissionToken'] = true;
		$settings[Item::FIELD_NAME_MYCOMPANY_ID]['SETTINGS']['enableCreationByOwnerEntity'] = true;
		$settings[Item::FIELD_NAME_MYCOMPANY_ID]['SETTINGS']['ownerEntityTypeId'] = $this->getEntityTypeId();

		$settings[Item\SmartB2eDocument::FIELD_NAME_NUMBER] = [
			'TYPE' => Field::TYPE_STRING,
			'CLASS' => Field\Number::class,
			'ATTRIBUTES' => [
				\CCrmFieldInfoAttr::AutoGenerated,
				\CCrmFieldInfoAttr::Unique,
			],
			'SETTINGS' => [
				'numeratorType' => static::NUMERATOR_TYPE,
				'numeratorIdSettings' => Item::FIELD_NAME_ID,
				'tableClassName' => $this->getDataClass(),
				// 'fieldValueNotUniqueErrorMessage' =>
				// 	Loc::getMessage('CRM_SERVICE_FACTORY_SMART_INVOICE_NUMBER_NOT_UNIQUE_ERROR'),
			],
		];

		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function getAdditionalTableFields(): array
	{
		return [
			(new Fields\StringField(Item\SmartB2eDocument::FIELD_NAME_NUMBER))
				->configureTitle(Loc::getMessage('CRM_TYPE_SMART_B2E_DOC_FIELD_NUMBER'))
				->configureDefaultValue('')
			,
		];
	}

	protected function configureAddOperation(Operation $operation): void
	{
		parent::configureAddOperation($operation);

		$operation->addAction(
			Operation::ACTION_AFTER_SAVE,
			new class extends Operation\Action {
				public function process(Item $item): Result
				{
					$provider = new \Bitrix\Crm\Activity\Provider\SignB2eDocument();

					$bindings = [[
						'OWNER_TYPE_ID' => $item->getEntityTypeId(),
						'OWNER_ID' => $item->getId(),
					]];

					$parent = $item->get('PARENT_ID_' . \CCrmOwnerType::Deal);
					if ($parent)
					{
						$bindings[] = [
								'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
								'OWNER_ID' => $parent,
							];
					}

					return $provider->createActivity(
						\Bitrix\Crm\Activity\Provider\SignB2eDocument::PROVIDER_TYPE_ID_SIGN,
						[
							'BINDINGS' => $bindings,
							'ASSOCIATED_ENTITY_ID' => $item->getId(),
							'SUBJECT' => $item->getHeading(),
							'COMPLETED' => 'N',
							'RESPONSIBLE_ID' => $item->getAssignedById(),
							'START_TIME' => (new DateTime())
								->add('+365 DAYS'),
						]
					);
				}
			}
		);
	}

	public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
	{
		$operation = parent::getUpdateOperation($item, $context);

		$operation->addAction(
			Operation::ACTION_AFTER_SAVE,
			//todo remove anonymous action
			new class extends Operation\Action {
				public function process(Item $item): Result
				{
					\Bitrix\Crm\Activity\Provider\SignB2eDocument::onDocumentUpdate(
						$item->getId(),
					);

					return new Result();
				}
			}
		);

		return $operation;
	}

	public function isCommunicationRoutingSupported(): bool
	{
		return false;
	}
}
