<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Crm\Contact;
use Bitrix\Crm\Conversion\ConversionManager;
use Bitrix\Crm\Conversion\DealConversionWizard;
use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\EO_Company;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Kanban\Entity\Deadlines\DeadlinesStageManager;
use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Tracking;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\ButtonLocation;

Loader::includeModule('crm');

/**
 * Class CrmQuoteDetailsComponent
 * @property Item\Quote $item
 */
class CrmQuoteDetailsComponent extends FactoryBased
{
	public const TAB_NAME_DEALS = 'tab_deals';
	public const TAB_NAME_INVOICES = 'tab_invoices';

	protected const OPEN_IN_NEW_WINDOW = true;
	protected const OPEN_IN_SAME_WINDOW = false;
	protected const WITH_SIGNATURE_AND_STAMP = false;
	protected const WITHOUT_SIGNATURE_AND_STAMP = true;

	protected const FILES_DATA = 'DISK_FILES';

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['ENTITY_TYPE_ID'] = CCrmOwnerType::Quote;
		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$this->init();

		if ($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		if ($this->isPreviewItemBeforeCopyMode())
		{
			$this->item->unset(Item\Quote::FIELD_NAME_NUMBER);
		}

		if (!\CCrmOwnerType::IsSliderEnabled($this->getEntityTypeID()))
		{
			$url = Container::getInstance()->getRouter()->getItemDetailUrl($this->getEntityTypeID(), $this->getEntityID());

			$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
			$url->addParams($request->getQueryList()->toArray());

			LocalRedirect($url, false, '301 Moved Permanently');
		}

		$this->executeBaseLogic();

		$this->includeComponentTemplate();
	}

	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::Quote;
	}

	protected function getSelect(): array
	{
		return array_merge(parent::getSelect(), [
			Item\Quote::FIELD_NAME_LEAD.'.EMAIL',
			Item\Quote::FIELD_NAME_LEAD.'.TITLE',
			Item\Quote::FIELD_NAME_DEAL.'.TITLE',
		]);
	}

	protected function getTitle(): string
	{
		if ($this->isPreviewItemBeforeCopyMode())
		{
			return Loc::getMessage('CRM_QUOTE_DETAILS_TITLE_COPY');
		}

		if($this->item->isNew())
		{
			return Loc::getMessage('CRM_QUOTE_DETAILS_TITLE_NEW');
		}

		return (string)$this->item->getHeading();
	}

	protected function isPageTitleEditable(): bool
	{
		return true;
	}

	protected function getEntityEditorMessages(): array
	{
		return [
			'COPY_PAGE_URL' => Loc::getMessage('CRM_QUOTE_DETAILS_COPY_PAGE_URL'),
			'PAGE_URL_COPIED' => Loc::getMessage('CRM_QUOTE_DETAILS_PAGE_URL_COPIED'),
			'MANUAL_OPPORTUNITY_CHANGE_MODE_TITLE' => Loc::getMessage('CRM_QUOTE_DETAILS_MANUAL_OPPORTUNITY_CHANGE_MODE_TITLE'),
			'MANUAL_OPPORTUNITY_CHANGE_MODE_TEXT' => Loc::getMessage('CRM_QUOTE_DETAILS_MANUAL_OPPORTUNITY_CHANGE_MODE_TEXT'),
		];
	}

	protected function getActivityEditorConfig(): array
	{
		return array_merge(parent::getActivityEditorConfig(),
		[
			'ENABLE_EMAIL_ADD' => true,
		]);
	}

	protected function getJsParams(): array
	{
		return array_merge(parent::getJsParams(),
		[
			'activityEditorId' => $this->getActivityEditorId(),
			'emailSettings' => $this->getEmailSettings($this->item->getPrimaryContact(), $this->item->getCompany()),
			'printTemplates' => $this->getPrintTemplates(),
			'conversion' => $this->getJsConversionParams(),
		]);
	}

	public function getEmailSettings(?Contact $contact, ?EO_Company $company): array
	{
		$isClientEmpty = is_null($contact) && is_null($company);
		$hasLead = ($this->item->getLead() !== null && !$this->item->isNew());

		$communications = [];
		if (!$isClientEmpty)
		{
			/** @noinspection PhpParamsInspection */
			$communications = array_merge($this->getCommunications($company), $this->getCommunications($contact));
			if (!empty($communications))
			{
				$communications['type'] = 'EMAIL';
			}
		}
		elseif ($hasLead && !empty($this->item->getLead()->getEmail()))
		{
			$communications = [
				'entityId' => $this->item->getLeadId(),
				'entityType' => \CCrmOwnerType::LeadName,
				'title' => $this->item->getLead()->getTitle(),
				'type' => 'EMAIL',
				'value' => $this->item->getLead()->getEmail(),
			];
		}

		return [
			'communications' => [
				$communications
			],
			'subject' => $this->item->getTitle() ?? $this->item->getId(),
			'ownerType' => $this->factory->getEntityName(),
			'ownerId' => $this->item->getId(),
		];
	}

	protected function getCommunications(?EntityObject $client): array
	{
		if (is_null($client))
		{
			return [];
		}

		$communications = [];
		if ($client instanceof Contact)
		{
			$communications = $this->getContactCommunications($client);
		}
		elseif ($client instanceof EO_Company)
		{
			$communications = $this->getCompanyCommunications($client);
		}

		return array_filter($communications, static function($value)
		{
			return !is_null($value);
		});
	}

	protected function getContactCommunications(Contact $contact): array
	{
		return [
			'entityType' => \CCrmOwnerType::ContactName,
			'entityId' => $contact->getId(),
			'entityTitle' => $contact->getFullName(),
			'value' => $contact->getEmail(),
		];
	}

	protected function getCompanyCommunications(EO_Company $company): array
	{
		return [
			'entityType' => \CCrmOwnerType::CompanyName,
			'entityId' => $company->getId(),
			'entityTitle' => $company->getTitle(),
			'value' => $company->getEmail(),
		];
	}

	protected function getPrintTemplates(): array
	{
		$paySystems = \CCrmPaySystem::GetPaySystems($this->item->getPersonTypeId());

		if (!is_array($paySystems))
		{
			return [];
		}

		$printTemplates = [];
		foreach($paySystems as $paySystem)
		{
			$file = $paySystem['~PSA_ACTION_FILE'] ?? '';
			if(preg_match('/quote(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $file))
			{
				$printTemplates[] = [
					'id' => (int)$paySystem['~ID'],
					'name' => $paySystem['~NAME'] ?? $paySystem['~ID']
				];
			}
		}

		return $printTemplates;
	}

	protected function initializeConversionWizardFromRequest(
		\Bitrix\Main\Request $request
	): ?\Bitrix\Crm\Conversion\EntityConversionWizard
	{
		$dealId = (int)$request->get(DealConversionWizard::QUERY_PARAM_SRC_ID);
		if ($dealId <= 0)
		{
			return null;
		}

		return DealConversionWizard::load($dealId);
	}

	protected function getJsConversionParams(): array
	{
		if ($this->item->isNew())
		{
			return [];
		}

		if (!ConversionManager::isConversionPossible($this->item))
		{
			return [];
		}

		$convertButton = $this->getConversionToolbarButton();

		$conversionConfig = ConversionManager::getConfig($this->factory->getEntityTypeId());
		if (!$conversionConfig)
		{
			return [];
		}

		$conversionParams = [
			'schemeSelector' => [
				'entityId' => $this->item->getId(),
				'containerId' => $convertButton->getMainButton()->getAttribute('id'),
				'labelId' => $convertButton->getMainButton()->getAttribute('id'),
				'buttonId' => $convertButton->getMenuButton()->getAttribute('id'),
			],
			'converter' => [
				'configItems' => $conversionConfig->toJson(),
				'scheme' => $conversionConfig->getScheme()->toJson(true),
				'params' => [
					'serviceUrl' => \Bitrix\Main\Engine\UrlManager::getInstance()->createByBitrixComponent($this, 'convert', [
						'entityTypeId' => $this->factory->getEntityTypeId(),
						'entityId' => $this->item->getId(),
						'sessid' => bitrix_sessid(),
					]),
					'messages' => [
						'accessDenied' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_ACCESS_DENIED'),
						'generalError' => Loc::getMessage('CRM_COMMON_ERROR_GENERAL'),
						'dialogTitle' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_DIALOG_TITLE'),
						'syncEditorLegend' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_SYNC_LEGEND'),
						'syncEditorFieldListTitle' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_SYNC_FIELD_LIST'),
						'syncEditorEntityListTitle' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_SYNC_ENTITY_LIST'),
						'continueButton' => Loc::getMessage('CRM_COMMON_CONTINUE'),
						'cancelButton' => Loc::getMessage('CRM_COMMON_CANCEL'),
					],
				],
			],
		];

		$restrictions = \Bitrix\Crm\Restriction\RestrictionManager::getConversionRestriction();
		// Restrictions are relevant only to quote conversion
		if (!$restrictions->hasPermission())
		{
			$conversionParams['lockScript'] = $restrictions->prepareInfoHelperScript();
		}

		return $conversionParams;


		// return [
		// 	'scheme' => [
		// 		'messages' => $conversionConfig->getScheme()->getJavaScriptDescriptions(),
		// 	],
		// 	'schemeSelector' => [
		// 		'entityId' => $this->item->getId(),
		// 		'entityTypeId' => $this->getEntityTypeID(),
		// 		'scheme' => $conversionSchemeName,
		// 		'containerId' => $convertButton->getMainButton()->getAttribute('id'),
		// 		'labelId' => $convertButton->getMainButton()->getAttribute('id'),
		// 		'buttonId' => $convertButton->getMenuButton()->getAttribute('id'),
		// 		'originUrl' => $this->getApplication()->getCurPage(),
		// 	],
		// 	'converter' => [
		// 		'messages' => [
		// 			'accessDenied' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_ACCESS_DENIED'),
		// 			'generalError' => Loc::getMessage('CRM_COMMON_ERROR_GENERAL'),
		// 			'dialogTitle' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_DIALOG_TITLE'),
		// 			'syncEditorLegend' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_SYNC_LEGEND'),
		// 			'syncEditorFieldListTitle' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_SYNC_FIELD_LIST'),
		// 			'syncEditorEntityListTitle' => Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_SYNC_ENTITY_LIST'),
		// 			'continueButton' => Loc::getMessage('CRM_COMMON_CONTINUE'),
		// 			'cancelButton' => Loc::getMessage('CRM_COMMON_CANCEL'),
		// 		],
		// 		'permissions' => ConversionManager::getConversionPermissions($this->item),
		// 		'settings' => [
		// 			'serviceUrl' => \Bitrix\Main\Engine\UrlManager::getInstance()->createByBitrixComponent($this, 'convert', [
		// 				'entityTypeId' => $this->factory->getEntityTypeId(),
		// 				'entityId' => $this->item->getId(),
		// 				'sessid' => bitrix_sessid(),
		// 			]),
		// 			'config' => $conversionConfig->toJavaScript(),
		// 			'scheme' => $conversionConfig->getScheme()->toJson(),
		// 		],
		// 	],
		// ];
	}

	protected function getJsMessages(): array
	{
		return array_merge(parent::getJsMessages(), [
			'deleteItemTitle' => Loc::getMessage('CRM_QUOTE_DETAILS_DELETE_CONFIRMATION_TITLE'),
			'deleteItemMessage' => Loc::getMessage('CRM_QUOTE_DETAILS_DELETE_CONFIRMATION_MESSAGE'),
			'template' => Loc::getMessage('CRM_QUOTE_DETAILS_JS_TEMPLATE'),
			'selectTemplate' => Loc::getMessage('CRM_QUOTE_DETAILS_JS_SELECT_TEMPLATE'),
			'print' => Loc::getMessage('CRM_QUOTE_DETAILS_JS_PRINT'),
			'errorNoPrintTemplates' => Loc::getMessage('CRM_QUOTE_DETAILS_JS_ERROR_NO_TEMPLATES'),
			'errorNoEmailSettings' => Loc::getMessage('CRM_QUOTE_DETAILS_JS_ERROR_NO_EMAIL_SETTINGS'),
		]);
	}

	protected function getToolbarParameters(): array
	{
		$parameters = parent::getToolbarParameters();
		$buttons = $parameters['buttons'] ?? [];

		if(!$this->item->isNew())
		{
			$buttons[ButtonLocation::AFTER_TITLE][] = $this->getActionsToolbarButton();

			$buttons[ButtonLocation::AFTER_TITLE][] = $this->getConversionToolbarButton();
		}

		$parameters['buttons'] = $buttons;

		return $parameters;
	}

	protected function getSettingsToolbarButton(): Buttons\SettingsButton
	{
		$items = [];
		$itemCopyUrl = Container::getInstance()->getRouter()->getItemCopyUrl(
			$this->getEntityTypeID(),
			$this->item->getId(),
		);
		$userPermissions = Container::getInstance()->getUserPermissions();
		if ($itemCopyUrl && $userPermissions->canAddItem($this->item))
		{
			$items[] = [
				'text' => Loc::getMessage('CRM_COMMON_ACTION_COPY'),
				'href' => $itemCopyUrl,
			];
		}
		if ($userPermissions->canDeleteItem($this->item))
		{
			$items[] = [
				'text' => Loc::getMessage('CRM_QUOTE_DETAILS_DELETE'),
				'onclick' => new Buttons\JsEvent('BX.Crm.ItemDetailsComponent:onClickDelete'),
			];
		}

		return new Buttons\SettingsButton([
			'menu' => [
				'items' => $items,
			],
		]);
	}

	protected function getActionsToolbarButton(): Buttons\Button
	{
		return new Buttons\Button([
			'text' => Loc::getMessage('CRM_QUOTE_DETAILS_BUTTON_ACTIONS'),
			'color' => Buttons\Color::LIGHT_BORDER,
			'menu' => [
				'items' => [
					[
						'text' => Loc::getMessage('CRM_QUOTE_DETAILS_BUTTON_ACTIONS_PRINT'),
						'onclick' => $this->compileJsForPrintButton(static::WITH_SIGNATURE_AND_STAMP),
					],
					[
						'text' => Loc::getMessage('CRM_QUOTE_DETAILS_BUTTON_ACTIONS_PRINT_BLANK'),
						'onclick' => $this->compileJsForPrintButton(static::WITHOUT_SIGNATURE_AND_STAMP),
					],
					[
						'text' => Loc::getMessage('CRM_QUOTE_DETAILS_BUTTON_ACTIONS_PDF'),
						'onclick' => $this->compileJsForPdfButton(static::WITH_SIGNATURE_AND_STAMP),
					],
					[
						'text' => Loc::getMessage('CRM_QUOTE_DETAILS_BUTTON_ACTIONS_PDF_BLANK'),
						'onclick' => $this->compileJsForPdfButton(static::WITHOUT_SIGNATURE_AND_STAMP),
					],
					[
						'text' => Loc::getMessage('CRM_QUOTE_DETAILS_BUTTON_ACTIONS_EMAIL'),
						'onclick' => new Buttons\JsEvent('BX.Crm.ItemDetailsComponent:onClickEmail'),
					],
				]
			],
		]);
	}

	protected function compileJsForPrintButton(bool $isBlank): Buttons\JsCode
	{
		$linkPrint = Container::getInstance()->getRouter()->getQuotePrintUrl($this->item->getId(), $isBlank);

		$eventData = $this->getJsEventData($linkPrint, static::OPEN_IN_NEW_WINDOW);
		return new Buttons\JsCode(
			"BX.Event.EventEmitter.emit('BX.Crm.ItemDetailsComponent:onClickPrint', $eventData)"
		);
	}

	protected function compileJsForPdfButton(bool $isBlank): Buttons\JsCode
	{
		$linkPdf = Container::getInstance()->getRouter()->getQuotePdfUrl($this->item->getId(), $isBlank);

		$eventData = $this->getJsEventData($linkPdf, static::OPEN_IN_SAME_WINDOW);
		return new Buttons\JsCode(
			"BX.Event.EventEmitter.emit('BX.Crm.ItemDetailsComponent:onClickPdf', $eventData)"
		);
	}

	protected function getJsEventData(Uri $link, bool $openInNewWindow): string
	{
		$eventData = [
			'link' => $link,
			'openInNewWindow' => $openInNewWindow
		];

		return \CUtil::PhpToJSObject($eventData);
	}

	protected function getConversionToolbarButton(): Buttons\Split\Button
	{
		$convertButton = new Bitrix\UI\Buttons\Split\Button([
			'color' => Buttons\Color::PRIMARY,
		]);

		$convertButton->getMainButton()->addAttribute('id', 'crm-quote-details-convert-main-button');
		$convertButton->getMenuButton()->addAttribute('id', 'crm-quote-details-convert-button');

		return $convertButton;
	}

	public function getInlineEditorEntityConfig(): array
	{
		$sections = [];
		$sections[] = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_QUOTE_DETAILS_EDITOR_MAIN_SECTION_TITLE'),
			'type' => 'section',
			'elements' => [
				['name' => Item\Quote::FIELD_NAME_TITLE],
				['name' => EditorAdapter::FIELD_OPPORTUNITY],
				['name' => EditorAdapter::FIELD_CLIENT],
			],
		];
		return $sections;
	}

	public function getEditorEntityConfig(): array
	{
		$sectionMain = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_QUOTE_DETAILS_EDITOR_MAIN_SECTION_TITLE'),
			'type' => 'section',
			'elements' => [
				['name' => Item\Quote::FIELD_NAME_STAGE_ID],
				['name' => Item\Quote::FIELD_NAME_NUMBER],
				['name' => Item\Quote::FIELD_NAME_TITLE],
				['name' => EditorAdapter::FIELD_OPPORTUNITY],
				['name' => EditorAdapter::FIELD_CLIENT],
				['name' => Item\Quote::FIELD_NAME_MYCOMPANY_ID],
				['name' => EditorAdapter::FIELD_FILES],
			],
		];
		if (Container::getInstance()->getAccounting()->isTaxMode())
		{
			$sectionMain['elements'][] = [
				'name' => Item::FIELD_NAME_LOCATION_ID,
			];
		}
		$sections[] = $sectionMain;

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => [
				['name' => Item\Quote::FIELD_NAME_LEAD_ID],
				['name' => Item\Quote::FIELD_NAME_DEAL_ID],
				['name' => Item\Quote::FIELD_NAME_BEGIN_DATE],
				['name' => Item\Quote::FIELD_NAME_ACTUAL_DATE],
				['name' => Item\Quote::FIELD_NAME_CLOSE_DATE],
				['name' => Item\Quote::FIELD_NAME_OPENED],
				['name' => Item\Quote::FIELD_NAME_ASSIGNED],
				['name' => Item\Quote::FIELD_NAME_CONTENT],
				['name' => Item\Quote::FIELD_NAME_TERMS],
				['name' => Item\Quote::FIELD_NAME_COMMENTS],
				['name' => Item\Quote::FIELD_NAME_CLOSED],
				['name' => EditorAdapter::FIELD_UTM],
			],
		];
		foreach($this->prepareEntityUserFields() as $fieldName => $userField)
		{
			$sectionAdditional['elements'][] = [
				'name' => $fieldName,
			];
		}
		$sections[] = $sectionAdditional;

		$sections[] = [
			'name' => 'products',
			'title' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY],
			],
		];

		return $sections;
	}

	protected function getDefaultTabInfoByCode(string $tabCode): ?array
	{
		if ($tabCode === static::TAB_NAME_DEALS)
		{
			if ($this->item->getDealId() > 0 && $this->item->getDeal() && !$this->item->isNew())
			{
				$dealTitle = htmlspecialcharsbx($this->item->getDeal()->getTitle());
				$linkToDealDetails = Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Deal, $this->item->getDealId());

				return [
					'id' => static::TAB_NAME_DEALS,
					'name' => Loc::getMessage('CRM_COMMON_DEALS'),
					'html' => Loc::getMessage(
						'CRM_QUOTE_DETAILS_CONVERTED_FROM_DEAL',
						['#TITLE#' => $dealTitle, '#URL#' => $linkToDealDetails]
					),
					'enabled' => !$this->item->isNew(),
				];
			}

			return [
				'id' => static::TAB_NAME_DEALS,
				'name' => Loc::getMessage('CRM_COMMON_DEALS'),
				'loader' => [
					'serviceUrl' => '/bitrix/components/bitrix/crm.deal.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => [
						'template' => '',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'DEAL_COUNT' => static::MAX_ENTITIES_IN_TAB,
							'INTERNAL_FILTER' => [$this->getEntityName().'_ID' => $this->item->getId()],
							'GRID_ID_SUFFIX' => $this->getGuid(),
							'TAB_ID' => static::TAB_NAME_DEALS,
							'ENABLE_TOOLBAR' => true,
							'PRESERVE_HISTORY' => true,
							// compatible entity-specific event name
							'ADD_EVENT_NAME' => 'CrmCreateDealFrom'.mb_convert_case($this->getEntityName(), MB_CASE_TITLE)
						], 'crm.deal.list')
					],
				],
				'enabled' => !$this->item->isNew(),
			];
		}

		if ($tabCode === static::TAB_NAME_INVOICES)
		{
			return [
				'id' => static::TAB_NAME_INVOICES,
				'name' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Invoice),
				'loader' => [
					'serviceUrl' => '/bitrix/components/bitrix/crm.invoice.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => [
						'template' => '',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'INVOICE_COUNT' => static::MAX_ENTITIES_IN_TAB,
							'INTERNAL_FILTER' => ['UF_'.$this->getEntityName().'_ID' => $this->item->getId()],
							'SUM_PAID_CURRENCY' => $this->item->getCurrencyId(),
							'GRID_ID_SUFFIX' => $this->getGuid(),
							'TAB_ID' => static::TAB_NAME_INVOICES,
							'ENABLE_TOOLBAR' => 'Y',
							'PRESERVE_HISTORY' => true,
							// compatible entity-specific event name
							'ADD_EVENT_NAME' => 'CrmCreateInvoiceFrom'.mb_convert_case($this->getEntityName(), MB_CASE_TITLE)
						], 'crm.invoice.list')
					],
				],
				'enabled' => !$this->item->isNew(),
			];
		}

		return parent::getDefaultTabInfoByCode($tabCode);
	}

	protected function getTabCodes(): array
	{
		$codes = parent::getTabCodes();

		$ownCodes = [
			static::TAB_NAME_DEALS => [],
		];

		if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
		{
			$ownCodes[static::TAB_NAME_INVOICES] = [];
		}

		return array_merge($codes, $ownCodes);
	}

	public function initializeEditorAdapter(): void
	{
		parent::initializeEditorAdapter();

		$filesData = [];
		$storageElementIds = array_unique($this->item->getStorageElementIds() ?? []);
		foreach($storageElementIds as $fileId)
		{
			if($fileId > 0)
			{
				$fileInfo = StorageManager::getFileInfo($fileId);
				if($fileInfo)
				{
					$filesData[] = $fileInfo;
				}
			}

		}

		$additionalData = [];
		Tracking\UI\Details::prepareEntityData(
			$this->item->getEntityTypeId(),
			$this->item->getId(),
			$additionalData
		);
		foreach ($additionalData as $name => $value)
		{
			$this->editorAdapter->addEntityData($name, $value);
		}
		$this->editorAdapter->addEntityData('UTM_VIEW_HTML', EditorAdapter::getUtmEntityData($this->item));
		$locationString = CCrmLocations::getLocationStringByCode($this->item->getLocationId());
		if (empty($locationString))
		{
			$locationString = '';
		}
		$this->editorAdapter->addEntityData(Item\Quote::FIELD_NAME_LOCATION_ID . '_VIEW_HTML', $locationString);
		$this->editorAdapter->addEntityData(
			Item\Quote::FIELD_NAME_LOCATION_ID . '_EDIT_HTML',
			EditorAdapter::getLocationFieldHtml(
				$this->item,
				Item\Quote::FIELD_NAME_LOCATION_ID
			)
		);
		$this->editorAdapter->addEntityData(
			'IS_USE_NUMBER_IN_TITLE_PLACEHOLDER',
			\Bitrix\Crm\Settings\QuoteSettings::getCurrent()->isUseNumberInTitlePlaceholder(),
		);

		$this->editorAdapter
			->addEntityData(Item\Quote::FIELD_NAME_STORAGE_TYPE, $this->item->getStorageTypeId())
			->addEntityData(Item\Quote::FIELD_NAME_STORAGE_ELEMENTS, $storageElementIds)
			->addEntityData(static::FILES_DATA, $filesData)
		;
	}

	public function createEmailAttachmentAction(int $paymentSystemId): ?array
	{
		$this->init();
		if ($this->getErrors())
		{
			return null;
		}

		$errorText = null;
		$fileId = \CCrmQuote::savePdf($this->item->getId(), $paymentSystemId, $errorText);
		if (!$fileId)
		{
			$this->errorCollection[] = new Error('Error while saving file: '.$errorText);
			return null;
		}

		$attachmentFileId = StorageManager::saveEmailAttachment(\CFile::GetFileArray($fileId));

		$storageTypeId = StorageType::getDefaultTypeID();

		return array_merge(StorageManager::getFileInfo($attachmentFileId), ['STORAGE_TYPE_ID' => $storageTypeId]);
	}

	public function convertAction(int $entityTypeId, int $entityId): Response\Json
	{
		$this->arParams['ENTITY_TYPE_ID'] = $entityTypeId;
		$this->arParams['ENTITY_ID'] = $entityId;
		$this->init();
		if ($this->getErrors())
		{
			return $this->returnErrorJson();
		}

		$configJsParams = $this->request->getPost('CONFIG') ?? [];
		if (!empty($configJsParams))
		{
			$configs = ConversionManager::getConfigFromJavaScript($this->factory->getEntityTypeId(), $configJsParams);
			if ($configs)
			{
				$configs->save();
			}
		}
		else
		{
			$configs = ConversionManager::getConfig($this->factory->getEntityTypeId());
		}

		if (is_null($configs))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_QUOTE_DETAILS_CONVERSION_NO_CONFIG_ERROR'));
			return $this->returnErrorJson();
		}

		$originUrl = $this->request->getPost('ORIGIN_URL') ?? '';
		$configs->setOriginUrl(new Uri($originUrl));

		$wereFieldsChecked = ($this->request->getPost('ENABLE_SYNCHRONIZATION') === 'Y');
		if (!$wereFieldsChecked)
		{
			$fieldNames = $this->getUserFieldNamesToSync($configs);
			if (!empty($fieldNames))
			{
				return $this->returnSyncRequiredJson($configs, $fieldNames);
			}
		}

		$operation = $this->factory->getConversionOperation($this->item, $configs);
		$conversionResult = $operation->launch();
		if (!$conversionResult->isSuccess())
		{
			$this->errorCollection->add($conversionResult->getErrors());
		}

		return $this->returnResultJson($conversionResult);
	}

	protected function returnErrorJson(): Response\Json
	{
		return new Response\Json([
			'ERROR' => [
				'MESSAGE' => implode(PHP_EOL, $this->getErrorMessages()),
			]
		]);
	}

	protected function returnSyncRequiredJson(EntityConversionConfig $configs, array $fieldNames): Response\Json
	{
		return new Response\Json([
			'REQUIRED_ACTION' => [
				'NAME' => 'SYNCHRONIZE',
				'DATA' => [
					'CONFIG' => $configs->toJson(),
					'FIELD_NAMES' => $fieldNames,
				]
			]
		]);
	}

	protected function returnResultJson(\Bitrix\Crm\Service\Operation\ConversionResult $result): Response\Json
	{
		if ($result->getRedirectUrl())
		{
			return new Response\Json([
				'DATA' => [
					'URL' => $result->getRedirectUrl(),
					'IS_FINISHED' => $result->isConversionFinished() ? 'Y' : 'N',
				],
			]);
		}

		return $this->returnErrorJson();
	}

	protected function getUserFieldNamesToSync(EntityConversionConfig $config): array
	{
		$fieldNamesToSync = [];

		foreach ($config->getActiveItems() as $dstEntityTypeId => $configItem)
		{
			if (!\Bitrix\Crm\Security\EntityAuthorization::checkCreatePermission($dstEntityTypeId))
			{
				continue;
			}

			if (UserFieldSynchronizer::needForSynchronization($this->factory->getEntityTypeId(), $dstEntityTypeId))
			{
				$configItem->enableSynchronization(true);

				$fieldsToSync = UserFieldSynchronizer::getSynchronizationFields($this->factory->getEntityTypeId(), $dstEntityTypeId);
				foreach ($fieldsToSync as $field)
				{
					$fieldNamesToSync[] = UserFieldSynchronizer::getFieldLabel($field);
				}
			}
		}

		return $fieldNamesToSync;
	}

	protected function getTimelineHistoryStubMessage(): ?string
	{
		return $this->arParams['CRM_TIMELINE_HISTORY_STUB_MESSAGE']
			?? Loc::getMessage('CRM_QUOTE_DETAILS_TIMELINE_HISTORY_STUB');
	}

	public function prepareKanbanConfiguration(): array
	{
		$scheme = [];
		$scheme[] = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_QUOTE_DETAILS_SECTION_MAIN'),
			'type' => 'section',
			'elements' => [
				['name' => 'QUOTE_NUMBER'],
				['name' => 'TITLE'],
				['name' => 'OPPORTUNITY_WITH_CURRENCY'],
				['name' => 'MYCOMPANY_ID']
			]
		];
		$scheme[] = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_QUOTE_DETAILS_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => []
		];
		return $scheme;
	}

	protected function getProductRowsFromParent(ItemIdentifier $identifier): array
	{
		return \CCrmProductRow::LoadRows(\CCrmOwnerTypeAbbr::ResolveByTypeID($identifier->getEntityTypeId()), $identifier->getEntityId());
	}

	protected function getParentEntityTypeIdsToLoadProducts(): array
	{
		return [
			\CCrmOwnerType::Lead => Item\Quote::FIELD_NAME_LEAD_ID,
			\CCrmOwnerType::Deal => Item\Quote::FIELD_NAME_DEAL_ID
		];
	}

	protected function getProductsData(): ?array
	{
		if ($this->item->isNew() && !$this->isCopyMode() && !$this->isConversionMode())
		{
			$parentFieldNamesToPassProducts = $this->getParentEntityTypeIdsToLoadProducts();
			foreach ($parentFieldNamesToPassProducts as $entityTypeId => $fieldName)
			{
				$entityId = $this->item->get($fieldName);
				if ($entityId > 0)
				{
					$parentRows = $this->getProductRowsFromParent(new ItemIdentifier($entityTypeId, $entityId));
					if (!empty($parentRows))
					{
						return $parentRows;
					}
				}
			}
		}

		return parent::getProductsData();
	}

	protected function fillParentFields(): void
	{
		parent::fillParentFields();

		$parentFieldNamesToPassProducts = $this->getParentEntityTypeIdsToLoadProducts();
		foreach ($parentFieldNamesToPassProducts as $parentEntityTypeId => $parentFieldName)
		{
			$entityId = $this->item->get($parentFieldName);
			if ($entityId > 0)
			{
				$parentIdentifier = new ItemIdentifier($parentEntityTypeId, $entityId);

				$factory = Container::getInstance()->getFactory($parentEntityTypeId);
				if (!$factory)
				{
					return;
				}
				$entityObject = $factory->getDataClass()::getById($entityId)->fetchObject();
				if (!$entityObject)
				{
					return;
				}

				$fieldsToPass = [
					Item::FIELD_NAME_CURRENCY_ID,
					Item::FIELD_NAME_COMPANY_ID,
					Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY,
					Item::FIELD_NAME_OPPORTUNITY,
				];
				foreach ($fieldsToPass as $fieldName)
				{
					$value = $entityObject->get($fieldName);
					if ($value)
					{
						$this->item->set($fieldName, $value);
					}
				}

				$parentRows = $this->getProductRowsFromParent($parentIdentifier);
				if (!empty($parentRows))
				{
					$this->item->setProductRowsFromArrays($parentRows);
					$productRows = $this->item->getProductRows();
					if ($productRows && !$this->item->getIsManualOpportunity())
					{
						$this->item->set(
							Item::FIELD_NAME_OPPORTUNITY,
							Container::getInstance()->getAccounting()->calculateByItem($this->item)->getPrice()
						);
					}
				}

				$contactRelation = Container::getInstance()->getRelationManager()->getRelation(new RelationIdentifier(
					\CCrmOwnerType::Contact,
					$parentEntityTypeId
				));
				if ($contactRelation)
				{
					$contactIds = [];
					$contactIdentifiers = $contactRelation->getParentElements($parentIdentifier);
					foreach ($contactIdentifiers as $contactIdentifier)
					{
						$contactIds[] = $contactIdentifier->getEntityId();
					}
					if (!empty($contactIds))
					{
						$bindings = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
							\CCrmOwnerType::Contact,
							$contactIds
						);
						$this->item->bindContacts($bindings);
					}
				}

				return;
			}
		}
	}

	public function saveAction(array $data): ?array
	{
		$data = $this->calculateDefaultDataValues($data);
		return parent::saveAction($data);
	}


	private function calculateDefaultDataValues(array $data): array
	{
		$deadlineStage = $data['DEADLINE_STAGE'] ?? '';
		$viewMode = $data['VIEW_MODE'] ?? '';
		$isNew = (int) $this->arParams['ENTITY_ID'] === 0;
		if (
			$isNew &&
			$viewMode === ViewMode::MODE_DEADLINES &&
			!empty($deadlineStage)
		)
		{
			$data = (new DeadlinesStageManager($this->getEntityTypeID()))
				->fillDeadlinesDefaultValues($data, $deadlineStage);
		}
		return $data;
	}
}
