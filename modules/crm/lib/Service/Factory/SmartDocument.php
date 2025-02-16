<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Document;
use Bitrix\Sign\Type\Document\EntityType;

class SmartDocument extends Dynamic
{
	protected $itemClassName = Item\SmartDocument::class;

	public const USER_FIELD_ENTITY_ID = 'CRM_SMART_DOCUMENT';
	public const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_SMART_DOCUMENT_SPD';
	public const NUMERATOR_TYPE = 'CRM_SMART_DOCUMENT';
	public const CONTACT_CATEGORY_CODE = 'SMART_DOCUMENT_CONTACT';

	public function getEntityDescription(): string
	{
		return \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartDocument);
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

	public static function createTypeIfNotExists(): void
	{
		$type = TypeTable::getByEntityTypeId(\CCrmOwnerType::SmartDocument)->fetchObject();
		if ($type)
		{
			return;
		}
		if (TypeTable::isCreatingInProgress(\CCrmOwnerType::SmartDocument))
		{
			return;
		}

		Container::getInstance()->getLocalization()->loadMessages();

		$type =
			TypeTable::createObject()
				->setName('SmartDocument')
				->setEntityTypeId(\CCrmOwnerType::SmartDocument)
				->setTitle(Loc::getMessage('CRM_TYPE_SMART_DOCUMENT_TYPE_TITLE'))
				->setCode('BX_SMART_DOCUMENT')
				->setCreatedBy(0)
				->setIsCategoriesEnabled(false)
				->setIsStagesEnabled(true)
				->setIsBeginCloseDatesEnabled(true)
				->setIsClientEnabled(true)
				->setIsUseInUserfieldEnabled(false)
				->setIsLinkWithProductsEnabled(true)
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
		if ($result->isSuccess())
		{
			// this call creates additional type info. now it's not needed, but it is required for complete signing scenario
			// may be this call will be uncommented later
			// \Bitrix\Crm\Settings\Crm::setDocumentSigningEnabled(true);
		}

		if (!$result->isSuccess())
		{
			AddMessage2Log(
				'Error while trying to create SmartDocument type: ' . implode(', ', $result->getErrorMessages()),
				'crm',
			);
		}
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
						// todo check remove if after install sign 23.600
						if (
							defined('\Bitrix\Sign\Type\Document\EntityType::SMART')
							&& method_exists($documentServiceContainer, 'canBeChanged')
						)
						{
							$document = \Bitrix\Sign\Service\Container::instance()
								->getDocumentRepository()
								->getByEntityIdAndType($item->getId(), EntityType::SMART)
							;
							if (
								$document
								&& !$documentServiceContainer->canBeChanged($document)
							)
							{
								$result->addError(
									new \Bitrix\Main\Error(
										Loc::getMessage('CRM_TYPE_SMART_DOCUMENT_DELETE_DENIED'),
										'SIGN_ERROR_DENIED'
									)
								);
							}
						}
						else // todo remove after install sign 23.600
						{
							$document = Document::resolveByEntity('SMART', $item->getId());
							if ($document && !$document->canBeChanged())
							{
								$result->addError(new \Bitrix\Main\Error(
									Loc::getMessage('CRM_TYPE_SMART_DOCUMENT_DELETE_DENIED'),
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
					if (\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled())
					{
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
								->getByEntityIdAndType($itemOld->getId(), 'SMART')
							;
							if (!$document)
							{
								return new Result();
							}

							$documentRepository->delete($document);
						}
						else
						{
							$document = Document::resolveByEntity('SMART', $itemOld->getId());
							$document?->unlink();
						}
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

		$settings[Item\SmartDocument::FIELD_NAME_NUMBER] = [
			'TYPE' => Field::TYPE_STRING,
			'CLASS' => Field\Number::class,
			'ATTRIBUTES' => [\CCrmFieldInfoAttr::AutoGenerated, \CCrmFieldInfoAttr::Unique],
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
			(new Fields\StringField(Item\SmartDocument::FIELD_NAME_NUMBER))
				->configureTitle(Loc::getMessage('CRM_TYPE_SMART_DOCUMENT_FIELD_NUMBER'))
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
					$provider = new \Bitrix\Crm\Activity\Provider\SignDocument();

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
						\Bitrix\Crm\Activity\Provider\SignDocument::PROVIDER_TYPE_ID_SIGN,
						[
							'BINDINGS' => $bindings,
							'ASSOCIATED_ENTITY_ID' => $item->getId(),
							'SUBJECT' => $item->getHeading(),
							'COMPLETED' => 'N',
							'RESPONSIBLE_ID' => $item->getAssignedById(),
							'START_TIME' => (new DateTime())
								->add('+30 DAYS'),
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
			Operation::ACTION_BEFORE_SAVE,
			new Operation\Action\EnsureMyCompanyRequisitesNotEmpty()
		);

		$operation->addAction(
			Operation::ACTION_AFTER_SAVE,
			//todo remove anonymous action
			new class extends Operation\Action {
				public function process(Item $item): Result
				{
					\Bitrix\Crm\Activity\Provider\SignDocument::onDocumentUpdate(
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
