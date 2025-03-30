<?php

use Bitrix\Catalog;
use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Integration\DiskManager;
use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Integration\StorageFileType;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Requisite;
use Bitrix\Crm\Rest;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service;
use Bitrix\Crm\Settings\RestSettings;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\WebForm;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Rest\UserFieldProxy;

if (!Loader::includeModule('rest'))
{
	return;
}

Loc::loadMessages(__FILE__);

final class CCrmRestService extends IRestService
{
	const SCOPE_NAME = 'crm';
	private static $METHOD_NAMES = array(
		//region Mode
		'crm.settings.mode.get',
		//endregion

		//region Status
		'crm.status.fields',
		'crm.status.add',
		'crm.status.get',
		'crm.status.list',
		'crm.status.update',
		'crm.status.delete',
		'crm.status.entity.types',
		'crm.status.entity.items',
		'crm.status.extra.fields',

		'crm.invoice.status.fields',
		'crm.invoice.status.add',
		'crm.invoice.status.get',
		'crm.invoice.status.list',
		'crm.invoice.status.update',
		'crm.invoice.status.delete',
		//endregion
		//region Enumeration
		'crm.enum.settings.mode',
		'crm.enum.fields',
		'crm.enum.ownertype',
		'crm.enum.addresstype',
		'crm.enum.contenttype',
		'crm.enum.activitytype',
		'crm.enum.activitypriority',
		'crm.enum.activitydirection',
		'crm.enum.activitynotifytype',
		'crm.enum.activitystatus',
		'crm.enum.entityeditor.configuration.scope',
		//endregion
		//region Lead
		'crm.lead.fields',
		'crm.lead.add',
		'crm.lead.get',
		'crm.lead.list',
		'crm.lead.update',
		'crm.lead.delete',
		'crm.lead.productrows.set',
		'crm.lead.productrows.get',
		'crm.lead.contact.fields',
		'crm.lead.contact.add',
		'crm.lead.contact.delete',
		'crm.lead.contact.items.get',
		'crm.lead.contact.items.set',
		'crm.lead.contact.items.delete',
		//endregion
		//region Deal
		'crm.deal.fields',
		'crm.deal.add',
		'crm.deal.get',
		'crm.deal.list',
		'crm.deal.update',
		'crm.deal.delete',
		'crm.deal.productrows.set',
		'crm.deal.productrows.get',
		'crm.deal.contact.fields',
		'crm.deal.contact.add',
		'crm.deal.contact.delete',
		'crm.deal.contact.items.get',
		'crm.deal.contact.items.set',
		'crm.deal.contact.items.delete',
		//endregion
		//region Deal Category
		'crm.dealcategory.fields',
		'crm.dealcategory.list',
		'crm.dealcategory.add',
		'crm.dealcategory.get',
		'crm.dealcategory.update',
		'crm.dealcategory.delete',
		'crm.dealcategory.status',
		'crm.dealcategory.stage.list',
		'crm.dealcategory.default.get',
		'crm.dealcategory.default.set',
		//endregion
		//region Deal Recurring
		'crm.deal.recurring.fields',
		'crm.deal.recurring.list',
		'crm.deal.recurring.add',
		'crm.deal.recurring.get',
		'crm.deal.recurring.update',
		'crm.deal.recurring.delete',
		'crm.deal.recurring.expose',
		//endregion
		//region Invoce Recurring
		'crm.invoice.recurring.fields',
		'crm.invoice.recurring.list',
		'crm.invoice.recurring.add',
		'crm.invoice.recurring.get',
		'crm.invoice.recurring.update',
		'crm.invoice.recurring.delete',
		'crm.invoice.recurring.expose',
		//endregion
		//region Company
		'crm.company.fields',
		'crm.company.add',
		'crm.company.get',
		'crm.company.list',
		'crm.company.update',
		'crm.company.delete',
		'crm.company.contact.fields',
		'crm.company.contact.add',
		'crm.company.contact.delete',
		'crm.company.contact.items.get',
		'crm.company.contact.items.set',
		'crm.company.contact.items.delete',
		//endregion
		//region Contact
		'crm.contact.fields',
		'crm.contact.add',
		'crm.contact.get',
		'crm.contact.list',
		'crm.contact.update',
		'crm.contact.delete',
		'crm.contact.company.fields',
		'crm.contact.company.add',
		'crm.contact.company.delete',
		'crm.contact.company.items.get',
		'crm.contact.company.items.set',
		'crm.contact.company.items.delete',
		//endregion
		//region Currency
		'crm.currency.fields',
		'crm.currency.add',
		'crm.currency.get',
		'crm.currency.list',
		'crm.currency.update',
		'crm.currency.delete',
		'crm.currency.localizations.fields',
		'crm.currency.localizations.get',
		'crm.currency.localizations.set',
		'crm.currency.localizations.delete',
		'crm.currency.base.set',
		'crm.currency.base.get',
		//endregion
		//region Catalog
		'crm.catalog.fields',
		'crm.catalog.get',
		'crm.catalog.list',
		//endregion
		//region Product
		'crm.product.fields',
		'crm.product.add',
		'crm.product.get',
		'crm.product.list',
		'crm.product.update',
		'crm.product.delete',
		//endregion
		//region Product Property
		'crm.product.property.types',
		'crm.product.property.fields',
		'crm.product.property.settings.fields',
		'crm.product.property.enumeration.fields',
		'crm.product.property.add',
		'crm.product.property.get',
		'crm.product.property.list',
		'crm.product.property.update',
		'crm.product.property.delete',
		//endregion
		//region Product Section
		'crm.productsection.fields',
		'crm.productsection.add',
		'crm.productsection.get',
		'crm.productsection.list',
		'crm.productsection.update',
		'crm.productsection.delete',
		//endregion
		//region Product Row
		'crm.productrow.fields',
		'crm.productrow.add',
		'crm.productrow.get',
		'crm.productrow.list',
		'crm.productrow.update',
		'crm.productrow.delete',
		//endregion
		//region Activity
		'crm.activity.fields',
		'crm.activity.add',
		'crm.activity.get',
		'crm.activity.list',
		'crm.activity.update',
		'crm.activity.delete',
		'crm.activity.communication.fields',
		//endregion
		//region Activity Type
		'crm.activity.type.add',
		'crm.activity.type.list',
		'crm.activity.type.delete',
		//endregion
		//region Quote
		'crm.quote.fields',
		'crm.quote.add',
		'crm.quote.get',
		'crm.quote.list',
		'crm.quote.update',
		'crm.quote.delete',
		'crm.quote.productrows.set',
		'crm.quote.productrows.get',
		'crm.quote.contact.fields',
		'crm.quote.contact.add',
		'crm.quote.contact.delete',
		'crm.quote.contact.items.get',
		'crm.quote.contact.items.set',
		'crm.quote.contact.items.delete',
		//endregion
		//region Requisite
		'crm.requisite.fields',
		'crm.requisite.add',
		'crm.requisite.get',
		'crm.requisite.list',
		'crm.requisite.update',
		'crm.requisite.delete',
		//
		'crm.requisite.userfield.add',
		'crm.requisite.userfield.get',
		'crm.requisite.userfield.list',
		'crm.requisite.userfield.update',
		'crm.requisite.userfield.delete',
		//
		'crm.requisite.preset.fields',
		'crm.requisite.preset.add',
		'crm.requisite.preset.get',
		'crm.requisite.preset.list',
		'crm.requisite.preset.update',
		'crm.requisite.preset.delete',
		'crm.requisite.preset.countries',
		//
		'crm.requisite.preset.field.fields',
		'crm.requisite.preset.field.availabletoadd',
		'crm.requisite.preset.field.add',
		'crm.requisite.preset.field.get',
		'crm.requisite.preset.field.list',
		'crm.requisite.preset.field.update',
		'crm.requisite.preset.field.delete',
		//
		'crm.requisite.bankdetail.fields',
		'crm.requisite.bankdetail.add',
		'crm.requisite.bankdetail.get',
		'crm.requisite.bankdetail.list',
		'crm.requisite.bankdetail.update',
		'crm.requisite.bankdetail.delete',
		//
		'crm.requisite.link.fields',
		'crm.requisite.link.list',
		'crm.requisite.link.get',
		'crm.requisite.link.register',
		'crm.requisite.link.unregister',
		//endregion Requisite
		//region Address
		'crm.address.fields',
		'crm.address.add',
		'crm.address.update',
		'crm.address.list',
		'crm.address.delete',
		'crm.address.getzoneid',
		'crm.address.setzoneid',
		//endregion Address
		//region Address type
		'crm.addresstype.getavailable',
		'crm.addresstype.getzonemap',
		'crm.addresstype.getdefaultbyzone',
		'crm.addresstype.getbyzonesorvalues',
		//endregion Address type
		//region Measures
		'crm.measure.fields',
		'crm.measure.add',
		'crm.measure.get',
		'crm.measure.list',
		'crm.measure.update',
		'crm.measure.delete',
		//endregion Measures

		//region User Field
		'crm.lead.userfield.add',
		'crm.lead.userfield.get',
		'crm.lead.userfield.list',
		'crm.lead.userfield.update',
		'crm.lead.userfield.delete',

		'crm.deal.userfield.add',
		'crm.deal.userfield.get',
		'crm.deal.userfield.list',
		'crm.deal.userfield.update',
		'crm.deal.userfield.delete',

		'crm.company.userfield.add',
		'crm.company.userfield.get',
		'crm.company.userfield.list',
		'crm.company.userfield.update',
		'crm.company.userfield.delete',

		'crm.contact.userfield.add',
		'crm.contact.userfield.get',
		'crm.contact.userfield.list',
		'crm.contact.userfield.update',
		'crm.contact.userfield.delete',

		'crm.quote.userfield.add',
		'crm.quote.userfield.get',
		'crm.quote.userfield.list',
		'crm.quote.userfield.update',
		'crm.quote.userfield.delete',

		'crm.invoice.userfield.add',
		'crm.invoice.userfield.get',
		'crm.invoice.userfield.list',
		'crm.invoice.userfield.update',
		'crm.invoice.userfield.delete',

		'crm.userfield.fields',
		'crm.userfield.types',
		'crm.userfield.enumeration.fields',
		'crm.userfield.settings.fields',
		//endregion

		//region Externalchannel connector.
		'crm.externalchannel.connector.fields',
		'crm.externalchannel.connector.list',
		'crm.externalchannel.connector.register',
		'crm.externalchannel.connector.unregister',
		//endregion

		//region Misc.
		'crm.multifield.fields',
		'crm.duplicate.findbycomm',
		'crm.livefeedmessage.add',
		'crm.externalchannel.company',
		'crm.externalchannel.contact',
		'crm.externalchannel.activity.company',
		'crm.externalchannel.activity.contact',
		'crm.webform.configuration.get',
		'crm.sitebutton.configuration.get',
		'crm.persontype.fields',
		'crm.persontype.list',
		'crm.paysystem.fields',
		'crm.paysystem.list',
		//endregion
		//region Automation
		'crm.automation.trigger',
		'crm.automation.trigger.add',
		'crm.automation.trigger.list',
		'crm.automation.trigger.delete',
		'crm.automation.trigger.execute',
		//endregion
		//region Timeline
		'crm.timeline.comment.fields',
		'crm.timeline.comment.list',
		'crm.timeline.comment.get',
		'crm.timeline.bindings.fields',
		'crm.timeline.bindings.list',
		'crm.timeline.bindings.bind',
		'crm.timeline.bindings.unbind',
		//endregion
		//region Details Page Configuration
		'crm.lead.details.configuration.get',
		'crm.lead.details.configuration.set',
		'crm.lead.details.configuration.reset',
		'crm.lead.details.configuration.forceCommonScopeForAll',

		'crm.deal.details.configuration.get',
		'crm.deal.details.configuration.set',
		'crm.deal.details.configuration.reset',
		'crm.deal.details.configuration.forceCommonScopeForAll',

		'crm.contact.details.configuration.get',
		'crm.contact.details.configuration.set',
		'crm.contact.details.configuration.reset',
		'crm.contact.details.configuration.forceCommonScopeForAll',

		'crm.company.details.configuration.get',
		'crm.company.details.configuration.set',
		'crm.company.details.configuration.reset',
		'crm.company.details.configuration.forceCommonScopeForAll',

		'crm.item.details.configuration.get',
		'crm.item.details.configuration.set',
		'crm.item.details.configuration.reset',
		'crm.item.details.configuration.forceCommonScopeForAll',
		//endregion
	);
	private static $DESCRIPTION = null;
	private static $PROXIES = array();

	public static function onRestServiceBuildDescription()
	{
		if(!self::$DESCRIPTION)
		{
			$bindings = array();
			// There is one entry point
			$callback = array('CCrmRestService', 'onRestServiceMethod');
			foreach(self::$METHOD_NAMES as $name)
			{
				$bindings[$name] = $callback;
			}

			$allActivityPlacementsCodes = AppPlacement::getAllDetailActivityCodes();
			$bindings[\CRestUtil::PLACEMENTS] = [];
			foreach(AppPlacement::getAll() as $name)
			{
				if (
					$name === AppPlacement::REQUISITE_AUTOCOMPLETE
					|| $name === AppPlacement::BANK_DETAIL_AUTOCOMPLETE
				)
				{
					$bindings[\CRestUtil::PLACEMENTS][$name] = [
						'options' => [
							'countries' => 'string',
						],
					];
				}
				elseif (in_array($name, $allActivityPlacementsCodes, true))
				{
					$bindings[\CRestUtil::PLACEMENTS][$name] = [
						'options' => [
							'useBuiltInInterface' => 'string',
							'newUserNotificationText' => 'string',
							'newUserNotificationTitle' => 'string',
						]
					];
				}
				else
				{
					$bindings[\CRestUtil::PLACEMENTS][$name] = [];
				}
			}

			CCrmLeadRestProxy::registerEventBindings($bindings);
			CCrmDealRestProxy::registerEventBindings($bindings);
			CCrmCompanyRestProxy::registerEventBindings($bindings);
			CCrmContactRestProxy::registerEventBindings($bindings);
			CCrmQuoteRestProxy::registerEventBindings($bindings);
			CCrmInvoiceRestProxy::registerEventBindings($bindings);
			CCrmCurrencyRestProxy::registerEventBindings($bindings);
			CCrmProductRestProxy::registerEventBindings($bindings);
			CCrmProductPropertyRestProxy::registerEventBindings($bindings);
			CCrmProductSectionRestProxy::registerEventBindings($bindings);
			CCrmActivityRestProxy::registerEventBindings($bindings);
			CCrmRequisiteRestProxy::registerEventBindings($bindings);
			CCrmRequisiteBankDetailRestProxy::registerEventBindings($bindings);
			CCrmAddressRestProxy::registerEventBindings($bindings);
			CCrmMeasureRestProxy::registerEventBindings($bindings);
			CCrmDealRecurringRestProxy::registerEventBindings($bindings);
			CCrmInvoiceRecurringRestProxy::registerEventBindings($bindings);
			CCrmTimelineCommentRestProxy::registerEventBindings($bindings);
			Service\Container::getInstance()->getRestEventManager()->registerEventBindings($bindings);

			Tracking\Rest::register($bindings);
			WebForm\Embed\Rest::register($bindings);
			\Bitrix\Crm\Controller\CallList::register($bindings);
			\Bitrix\Crm\Activity\Entity\ConfigurableRestApp\EventHandler::register($bindings);

			self::$DESCRIPTION = array('crm' => $bindings);
		}

		return self::$DESCRIPTION;
	}
	public static function onRestServiceMethod($arParams, $nav, CRestServer $server)
	{
		if(!CCrmPerms::IsAccessEnabled())
		{
			throw new RestException('Access denied.');
		}

		$methodName = $server->getMethod();

		$parts = explode('.', $methodName);
		$partCount = count($parts);
		if($partCount < 3 || $parts[0] !== 'crm')
		{
			throw new RestException("Method '{$methodName}' is not supported in current context.");
		}

		$typeName = mb_strtoupper($parts[1]);
		$proxy = null;

		$subType = isset($parts[2])? mb_strtoupper($parts[2]) : '';

		if (isset(self::$PROXIES[$typeName.'.'.$subType]))
		{
			$proxy = self::$PROXIES[$typeName.'.'.$subType];
		}
		else if(isset(self::$PROXIES[$typeName]))
		{
			$proxy = self::$PROXIES[$typeName];
		}

		if(!$proxy)
		{
			if($typeName === 'SETTINGS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmSettingsRestProxy();
			}
			elseif($typeName === 'ENUM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmEnumerationRestProxy();
			}
			elseif($typeName === 'MULTIFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmMultiFieldRestProxy();
			}
			elseif($typeName === 'CURRENCY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCurrencyRestProxy();
			}
			elseif($typeName === 'CATALOG')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCatalogRestProxy();
			}
			elseif($typeName === 'PRODUCT' && $subType === 'PROPERTY')
			{
				$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmProductPropertyRestProxy();
			}
			elseif($typeName === 'PRODUCT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRestProxy();
			}
			elseif($typeName === 'PRODUCTSECTION')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductSectionRestProxy();
			}
			elseif($typeName === 'PRODUCTROW')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRowRestProxy();
			}
			elseif($typeName === 'STATUS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmStatusRestProxy();
			}
			elseif($typeName === 'LEAD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLeadRestProxy();
			}
			elseif($typeName === 'DEAL')
			{
				if($subType === 'RECURRING')
				{
					$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmDealRecurringRestProxy();
				}
				else
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmDealRestProxy();
				}
			}
			elseif($typeName === 'DEALCATEGORY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDealCategoryProxy();
			}
			elseif($typeName === 'COMPANY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCompanyRestProxy();
			}
			elseif($typeName === 'CONTACT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmContactRestProxy();
			}
			elseif($typeName === 'QUOTE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmQuoteRestProxy();
			}
			elseif($typeName === 'ITEM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmItemRestProxy();
			}
			elseif($typeName === 'INVOICE' && $subType === 'STATUS')
			{
				$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmStatusInvoiceRestProxy();
			}
			elseif($typeName === 'INVOICE')
			{
				if($subType === 'RECURRING')
				{
					$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmInvoiceRecurringRestProxy();
				}
				else
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmInvoiceRestProxy();
				}
			}
			elseif($typeName === 'REQUISITE')
			{
				if($subType === 'LINK')
				{
					$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmRequisiteLinkRestProxy();
				}
				else
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmRequisiteRestProxy();
				}
			}
			elseif($typeName === 'ADDRESS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmAddressRestProxy();
			}
			elseif($typeName === 'ADDRESSTYPE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmAddressTypeRestProxy();
			}
			elseif($typeName === 'ACTIVITY')
			{
				if($subType === 'TYPE')
				{
					$proxy = self::$PROXIES[$typeName.'.'.$subType] = new \Bitrix\Crm\Activity\Rest\TypeProxy();
				}
				else
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmActivityRestProxy();
				}
			}
			elseif($typeName === 'DUPLICATE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDuplicateRestProxy();
			}
			elseif($typeName === 'LIVEFEEDMESSAGE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLiveFeedMessageRestProxy();
			}
			elseif($typeName === 'USERFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmUserFieldRestProxy(CCrmOwnerType::Undefined);
			}
			elseif($typeName === 'EXTERNALCHANNEL')
			{
				if($subType === 'CONNECTOR')
				{
					$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmExternalChannelConnectorRestProxy();
				}
				else
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmExternalChannelRestProxy();
				}
			}
			elseif($typeName === 'WEBFORM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmWebformRestProxy();
			}
			elseif($typeName === 'SITEBUTTON')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmSiteButtonRestProxy();
			}
			elseif($typeName === 'PERSONTYPE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmPersonTypeRestProxy();
			}
			elseif($typeName === 'PAYSYSTEM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmPaySystemRestProxy();
			}
			elseif($typeName === 'MEASURE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmMeasureRestProxy();
			}
			elseif($typeName === 'AUTOMATION')
			{
				$proxy = self::$PROXIES[$typeName] = new \Bitrix\Crm\Automation\Rest\Proxy();
			}
			elseif($typeName === 'TIMELINE')
			{
				if ($subType === 'COMMENT')
				{
					$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmTimelineCommentRestProxy();
				}
				elseif ($subType === 'BINDINGS')
				{
					$proxy = self::$PROXIES[$typeName.'.'.$subType] = new CCrmTimelineBindingRestProxy();
				}
			}
			else
			{
				throw new RestException("Could not find proxy for method '{$methodName}'.");
			}
			$proxy->setServer($server);
		}
		return $proxy->processMethodRequest(
			$parts[2],
			$partCount > 3 ? array_slice($parts, 3) : array(),
			$arParams,
			$nav,
			$server
		);
	}
	public static function getNavData($start, $isOrm = false)
	{
		return parent::getNavData($start, $isOrm);
	}
	public static function setNavData($result, $dbRes)
	{
		return parent::setNavData($result, $dbRes);
	}
}

class CCrmRestHelper
{
	const PARAM_KEY_SCHEME_LOWER_CASE = 1;
	const PARAM_KEY_SCHEME_UPPER_CASE = 2;
	const PARAM_KEY_SCHEME_LOWER_CAMEL_CASE = 3;
	const PARAM_KEY_SCHEME_UPPER_CAMEL_CASE = 4;

	public static function resolveEntityID(array &$arParams)
	{
		return isset($arParams['ID']) ? (int)$arParams['ID'] : (isset($arParams['id']) ? (int)$arParams['id'] : 0);
	}
	public static function resolveArrayParam(array &$arParams, $name, array $default = null)
	{
		if(isset($arParams[$name]))
		{
			return $arParams[$name];
		}

		// Check for upper case notation (FILTER, SORT, SELECT, etc)
		$upper = mb_strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for lower case notation (filter, sort, select, etc)
		$lower = mb_strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for capitalized notation (Filter, Sort, Select, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		// Check for hungary notation (arFilter, arSort, arSelect, etc)
		$hungary = "ar{$capitalized}";
		if(isset($arParams[$hungary]))
		{
			return $arParams[$hungary];
		}

		return $default;
	}
	public static function resolveParam(array &$arParams, $name, $default = null)
	{
		if(!is_string($name))
		{
			$name = (string)$name;
		}

		if($name === '')
		{
			return $default;
		}

		if(isset($arParams[$name]))
		{
			return $arParams[$name];
		}

		// Check for lower case notation (type, etc)
		$lower = mb_strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case notation (TYPE, etc)
		$upper = mb_strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for capitalized notation (Type, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		return $default;
	}

	public static function resolveParamName(array &$arParams, array $nameParts)
	{
		if(empty($nameParts))
		{
			return '';
		}

		$keys = self::prepareKeys(
			$nameParts,
			array(
				self::PARAM_KEY_SCHEME_LOWER_CASE,
				self::PARAM_KEY_SCHEME_UPPER_CASE,
				self::PARAM_KEY_SCHEME_LOWER_CAMEL_CASE,
				self::PARAM_KEY_SCHEME_UPPER_CAMEL_CASE
			)
		);

		foreach($keys as $key)
		{
			if(isset($arParams[$key]))
			{
				return $key;
			}
		}

		return '';
	}

	public static function renameParam(array &$arParams, array $oldNameParts, $newName)
	{
		if(empty($arParams))
		{
			return;
		}

		$oldName = self::resolveParamName($arParams, $oldNameParts);
		if($oldName !== '' && isset($arParams[$oldName]))
		{
			$arParams[$newName] = $arParams[$oldName];
			unset($arParams[$oldName]);
		}
	}

	public static function prepareKeys(array $parts, array $schemes)
	{
		$map = array();
		foreach($schemes as $scheme)
		{
			$map[self::prepareKey($parts, $scheme)] = true;
		}
		return array_keys($map);
	}
	public static function prepareKey(array $parts, $scheme)
	{
		if(empty($parts))
		{
			return '';
		}

		if($scheme === self::PARAM_KEY_SCHEME_LOWER_CASE)
		{
			return implode('', array_map('mb_strtolower', $parts));
		}

		if($scheme === self::PARAM_KEY_SCHEME_UPPER_CASE)
		{
			return implode('', array_map('mb_strtoupper', $parts));
		}

		if($scheme === self::PARAM_KEY_SCHEME_LOWER_CAMEL_CASE)
		{
			$first = array_shift($parts);

			return implode(
				'',
				array_merge(
					array(mb_strtolower($first)),
					array_map(
						function($s){ return ucfirst(mb_strtolower($s)); },
						$parts
					)
				)
			);
		}

		if($scheme === self::PARAM_KEY_SCHEME_UPPER_CAMEL_CASE)
		{
			return implode(
				'',
				array_map(
					function($s){ return ucfirst(mb_strtolower($s)); },
					$parts
				)
			);
		}

		return implode('', $parts);
	}

	protected function tryResolveParam(array $arParams, $name, &$value)
	{
		$key = '';

		// Check for lower case notation (type, etc)
		$nameLC = mb_strtolower($name);
		if(isset($arParams[$nameLC]))
		{
			$value = $arParams[$nameLC];
			return true;
		}

		// Check for upper case notation (TYPE, etc)
		$nameUC = mb_strtoupper($name);
		if(isset($arParams[$nameUC]))
		{
			$value = $arParams[$nameUC];
			return true;
		}

		// Check for capitalized notation (Type, etc)
		$capitalized = ucfirst($nameLC);
		if(isset($arParams[$capitalized]))
		{
			$value = $arParams[$capitalized];
			return true;
		}

		return false;
	}

	public static function prepareFieldInfos(array &$fieldsInfo)
	{
		$result = array();

		foreach($fieldsInfo as $fieldID => &$fieldInfo)
		{
			$attrs = $fieldInfo['ATTRIBUTES'] ?? array();
			// Skip hidden fields
			if(in_array(CCrmFieldInfoAttr::Hidden, $attrs, true))
			{
				continue;
			}

			$fieldType = $fieldInfo['TYPE'];
			$field = array(
				'type' => $fieldType,
				'isRequired' => in_array(CCrmFieldInfoAttr::Required, $attrs, true),
				'isReadOnly' => in_array(CCrmFieldInfoAttr::ReadOnly, $attrs, true),
				'isImmutable' => in_array(CCrmFieldInfoAttr::Immutable, $attrs, true),
				'isMultiple' => in_array(CCrmFieldInfoAttr::Multiple, $attrs, true),
				'isDynamic' => in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true)
			);

			if(in_array(CCrmFieldInfoAttr::Deprecated, $attrs, true))
			{
				$field['isDeprecated'] = true;
			}

			if($fieldType === 'enumeration')
			{
				$field['items'] = $fieldInfo['ITEMS'] ?? array();
			}
			elseif($fieldType === 'crm_status')
			{
				$field['statusType'] = $fieldInfo['CRM_STATUS_TYPE'] ?? '';
			}
			elseif ($fieldType === 'product_property')
			{
				$field['propertyType'] = $fieldInfo['PROPERTY_TYPE'] ?? '';
				$field['userType'] = $fieldInfo['USER_TYPE'] ?? '';
				$field['title'] = $fieldInfo['NAME'] ?? '';
				if ($field['propertyType'] === 'L')
					$field['values'] = $fieldInfo['VALUES'] ?? array();
			}
			elseif ($fieldType === 'recurring_params')
			{
				$field['definition'] = [];
				if (is_array($fieldInfo['FIELDS']))
				{
					$paramFields = self::prepareFieldInfos($fieldInfo['FIELDS']);
					$field['definition'] = is_array($paramFields) ? $paramFields : [];
				}
			}

			if (empty($field['title']))
			{
				$field['title'] = isset($fieldInfo['CAPTION']) && $fieldInfo['CAPTION'] <> '' ? $fieldInfo['CAPTION'] : $fieldID;
			}

			if(isset($fieldInfo['LABELS']) && is_array($fieldInfo['LABELS']))
			{
				$labels = $fieldInfo['LABELS'];
				if(isset($labels['LIST']))
				{
					$field['listLabel'] = $labels['LIST'];
				}
				if(isset($labels['FORM']))
				{
					$field['formLabel'] = $labels['FORM'];
				}
				if(isset($labels['FILTER']))
				{
					$field['filterLabel'] = $labels['FILTER'];
				}
			}
			if (isset($fieldInfo['SETTINGS']))
			{
				$field['settings'] = $fieldInfo['SETTINGS'];
			}

			$result[$fieldID] = &$field;
			unset($field);
		}
		unset($fieldInfo);

		return $result;
	}
}

interface ICrmRestProxy
{
	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server);
	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer();
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
}

class CCrmRestEventDispatcher
{
	protected static $entityIds = null;

	protected static function ensureEntityIdsDefined()
	{
		if (self::$entityIds === null)
		{
			self::$entityIds = [
				CCrmOwnerType::Lead => CCrmLead::$sUFEntityID,
				CCrmOwnerType::Company => CCrmCompany::$sUFEntityID,
				CCrmOwnerType::Contact => CCrmContact::$sUFEntityID,
				CCrmOwnerType::Requisite => Bitrix\Crm\EntityRequisite::$sUFEntityID,
				CCrmOwnerType::Deal => CCrmDeal::$sUFEntityID,
				CCrmOwnerType::Quote => CCrmQuote::$sUFEntityID,
				CCrmOwnerType::Invoice => CCrmInvoice::$sUFEntityID,
				CCrmOwnerType::SmartInvoice => Service\Factory\SmartInvoice::USER_FIELD_ENTITY_ID,
			];

			$dynamicTypesMap = Service\Container::getInstance()->getDynamicTypesMap()->load([
				'isLoadStages' => false,
				'isLoadCategories' => false,
			]);
			$typeFactory = ServiceLocator::getInstance()->get('crm.type.factory');
			foreach ($dynamicTypesMap->getTypes() as $type)
			{
				self::$entityIds[$type->getEntityTypeId()] = $typeFactory->getUserFieldEntityId($type->getId());
			}
		}
	}

	protected static function verifyEntityId($entityId)
	{
		static::ensureEntityIdsDefined();

		return in_array($entityId, self::$entityIds, true);
	}

	protected static function getOwnerTypeNameByEntityId($entityId)
	{
		$result = '';

		static::ensureEntityIdsDefined();

		$ownerTypeId = array_search($entityId, self::$entityIds, true);
		if (CCrmOwnerType::IsDefined($ownerTypeId))
		{
			$result = CCrmOwnerType::ResolveName($ownerTypeId);
		}

		return $result;
	}

	protected static function getOwnerTypeIdByEntityId(string $entityId): ?int
	{
		$result = '';

		static::ensureEntityIdsDefined();

		return array_search($entityId, self::$entityIds, true);
	}

	public static function onUserFieldAdd($fields)
	{
		if (is_array($fields) && isset($fields['ID']) && $fields['ID'] > 0
			&& isset($fields['FIELD_NAME']) && is_string($fields['FIELD_NAME']) && $fields['FIELD_NAME'] <> ''
			&& isset($fields['ENTITY_ID']) && static::verifyEntityId($fields['ENTITY_ID']))
		{
			$id = (int)$fields['ID'];
			$entityId = $fields['ENTITY_ID'];
			$fieldName = $fields['FIELD_NAME'];

			self::sendEvent(
				'Add',
				array('id' => $id, 'entityId' => $entityId, 'fieldName' => $fieldName),
				array('UF_ENTITY_ID' => $entityId)
			);
		}
	}
	public static function onUserFieldUpdate($fields, $id)
	{
		if ($id > 0)
		{
			$id = (int)$id;
			$fields = CUserTypeEntity::GetByID($id);
			if (is_array($fields)
				&& isset($fields['FIELD_NAME']) && is_string($fields['FIELD_NAME']) && $fields['FIELD_NAME'] <> ''
				&& isset($fields['ENTITY_ID']) && static::verifyEntityId($fields['ENTITY_ID']))
			{
				$entityId = $fields['ENTITY_ID'];
				$fieldName = $fields['FIELD_NAME'];

				self::sendEvent(
					'Update',
					[
						'id' => $id,
						'entityId' => $entityId,
						'fieldName' => $fieldName
					],
					[
						'UF_ENTITY_ID' => $entityId,
					],
				);
			}
		}
	}
	public static function onUserFieldDelete($fields, $id)
	{
		if ($id > 0 && is_array($fields)
			&& isset($fields['FIELD_NAME']) && is_string($fields['FIELD_NAME']) && $fields['FIELD_NAME'] <> ''
			&& isset($fields['ENTITY_ID']) && static::verifyEntityId($fields['ENTITY_ID']))
		{
			$id = (int)$id;
			$entityId = $fields['ENTITY_ID'];
			$fieldName = $fields['FIELD_NAME'];

			self::sendEvent(
				'Delete',
				array('id' => $id, 'entityId' => $entityId, 'fieldName' => $fieldName),
				array('UF_ENTITY_ID' => $entityId)
			);
		}
	}
	public static function onUserFieldSetEnumValues($event)
	{
		if ($event instanceof Bitrix\Main\Event)
		{
			$id = (int)$event->getParameter(0);
			if ($id > 0)
			{
				$fields = CUserTypeEntity::GetByID($id);
				if (is_array($fields)
					&& isset($fields['FIELD_NAME']) && is_string($fields['FIELD_NAME']) && $fields['FIELD_NAME'] <> ''
					&& isset($fields['ENTITY_ID']) && static::verifyEntityId($fields['ENTITY_ID']))
				{
					$entityId = $fields['ENTITY_ID'];
					$fieldName = $fields['FIELD_NAME'];

					self::sendEvent(
						'SetEnumValues',
						array('id' => $id, 'entityId' => $entityId, 'fieldName' => $fieldName),
						array('UF_ENTITY_ID' => $entityId)
					);
				}
			}
		}
	}
	protected static function sendEvent($action, $params, $options)
	{
		if (is_array($options) && isset($options['UF_ENTITY_ID']))
		{
			$ufEntityId = $options['UF_ENTITY_ID'];
			if (is_string($ufEntityId) && $ufEntityId !== '')
			{
				$entityTypeId = static::getOwnerTypeIdByEntityId($ufEntityId);
				if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
				{
					$eventName = 'onCrmTypeUserField' . $action;
				}
				else
				{
					$entityName = static::getOwnerTypeNameByEntityId($ufEntityId);
					$entityName = ucfirst(mb_strtolower($entityName));
					$eventName = 'OnAfterCrmRest' . $entityName . 'UserField' . $action;
				}
				$event = new Main\Event(
					'crm',
					$eventName,
					$params
				);
				$event->send();
			}
		}
	}
}

abstract class CCrmRestProxyBase implements ICrmRestProxy
{
	private $currentUser = null;
	private $webdavSettings = null;
	private $webdavIBlock = null;
	/** @var CRestServer  */
	private $server = null;
	private $sanitizer = null;
	private static $MULTIFIELD_TYPE_IDS = null;
	public function getFields()
	{
		$fildsInfo = $this->getFieldsInfo();
		return self::prepareFields($fildsInfo);
	}
	public function isValidID($ID)
	{
		return is_int($ID) && $ID > 0;
	}
	public function add(&$fields, array $params = null)
	{
		$fieldsInfo = $this->getFieldsInfo();

		$isImportMode = (bool)($params['IMPORT'] ?? false);
		if ($isImportMode)
		{
			// allow set system fields in import
			$systemFields = [
				'DATE_CREATE',
				'DATE_MODIFY',
				'CREATED_BY_ID',
				'MODIFY_BY_ID',
			];

			foreach ($systemFields as $systemField)
			{
				if (isset($fieldsInfo[$systemField]) && is_array($fieldsInfo[$systemField]['ATTRIBUTES']))
				{
					$readonlyAttrPos = array_search(\CCrmFieldInfoAttr::ReadOnly, $fieldsInfo[$systemField]['ATTRIBUTES']);
					if ($readonlyAttrPos !== false)
					{
						unset($fieldsInfo[$systemField]['ATTRIBUTES'][$readonlyAttrPos]);
					}
				}
			}
		}
		$fieldsInfo['TRACE'] = [
			'TYPE' => 'string',
			'ATTRIBUTES' => [\CCrmFieldInfoAttr::Immutable]
		];
		$this->internalizeFields($fields, $fieldsInfo, array());

		$errors = array();
		$result = $this->innerAdd($fields, $errors, $params);
		if(!$this->isValidID($result))
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function get($ID)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}


		$errors = array();
		$result = $this->innerGet($ID, $errors);
		if(!is_array($result))
		{
			throw new RestException(implode("\n", $errors));
		}

		$result = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToRead(
			$this->getOwnerTypeID(),
			(int)$ID,
			$result,
		);

		$fieldsInfo = $this->getFieldsInfo();
		$this->externalizeFields($result, $fieldsInfo);
		return $result;

	}
	public function getList($order, $filter, $select, $start)
	{
		$this->prepareListParams($order, $filter, $select);

		$navigation = CCrmRestService::getNavData($start);

		$selectedFmTypeIDs = $this->findSelectedFmTypeIds((array)$select);
		$wasIdentityFieldAdded = false;
		if ($this->shouldAddIdentityFieldToSelect((array)$select, !empty($selectedFmTypeIDs)))
		{
			$select = $this->addIdentityFieldInSelect((array)$select);
			$wasIdentityFieldAdded = true;
		}

		$fieldsInfo = $this->getFieldsInfo();
		$this->internalizeFilterFields($filter, $fieldsInfo);
		$errors = array();
		$result = $this->innerGetList($order, $filter, $select, $navigation, $errors);

		if($result instanceOf CDBResult)
		{
			return $this->prepareListFromDbResult(
				$result,
				[
					'SELECTED_FM_TYPES' => $selectedFmTypeIDs,
					'USE_ENTITY_MAP_APPROACH' => $wasIdentityFieldAdded,
				]
			);
		}
		elseif(is_array($result))
		{
			return $this->prepareListFromArray(
				$result,
				[
					'SELECTED_FM_TYPES' => $selectedFmTypeIDs,
					'USE_ENTITY_MAP_APPROACH' => $wasIdentityFieldAdded,
				]
			);
		}

		if(empty($errors))
		{
			$errors[] = "Failed to get list. General error.";
		}

		throw new RestException(implode("\n", $errors));
	}

	private function findSelectedFmTypeIds(array $select): array
	{
		if (empty($select))
		{
			return [];
		}

		$supportedFmTypeIDs = $this->getSupportedMultiFieldTypeIDs();

		$selectedFmTypeIDs = [];
		if(is_array($supportedFmTypeIDs) && !empty($supportedFmTypeIDs))
		{
			foreach($supportedFmTypeIDs as $fmTypeID)
			{
				if(in_array($fmTypeID, $select, true))
				{
					$selectedFmTypeIDs[] = $fmTypeID;
				}
			}
		}

		return $selectedFmTypeIDs;
	}

	private function shouldAddIdentityFieldToSelect(array $select, bool $isMultifieldsEnabled): bool
	{
		if (empty($this->getIdentityFieldName()))
		{
			// identity is not supported
			return false;
		}

		if ($isMultifieldsEnabled)
		{
			return true;
		}

		if (empty($select) || in_array('*', $select, true))
		{
			// empty select essentially means SELECT *
			return true;
		}

		$isFlexibleFieldsSelected = (bool)array_intersect($select, \Bitrix\Crm\Entity\CommentsHelper::getFieldsWithFlexibleContentType($this->getOwnerTypeID()));

		return $isFlexibleFieldsSelected;
	}

	private function addIdentityFieldInSelect(array $select): ?array
	{
		if (empty($select))
		{
			// empty select essentially means SELECT *
			return $select;
		}

		$identityFieldName = $this->getIdentityFieldName();
		if($identityFieldName === '')
		{
			throw new RestException('Could not find identity field name.');
		}

		if(!in_array($identityFieldName, $select, true))
		{
			$select[] = $identityFieldName;
		}

		return $select;
	}

	protected function prepareListFromDbResult(CDBResult $dbResult, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		if ($options['USE_ENTITY_MAP_APPROACH'] ?? false)
		{
			$entityMap = array();
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$selectedFmTypeIDs = $options['SELECTED_FM_TYPES'] ?? array();
			if (!empty($selectedFmTypeIDs))
			{
				$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);
			}
			$entityMap = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToReadInList(
				$this->getOwnerTypeID(),
				$entityMap,
			);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}
		else
		{
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}
	protected function prepareListFromArray(array $list, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		if ($options['USE_ENTITY_MAP_APPROACH'] ?? false)
		{
			$entityMap = array();
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$selectedFmTypeIDs = $options['SELECTED_FM_TYPES'] ?? array();
			if (!empty($selectedFmTypeIDs))
			{
				$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);
			}
			$entityMap = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToReadInList(
				$this->getOwnerTypeID(),
				$entityMap,
			);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}
		else
		{
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}

		return CCrmRestService::setNavData($result, array('offset' => 0, 'count' => count($result)));
	}
	public function update($ID, &$fields, array $params = null)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$fieldsInfo = $this->getFieldsInfo();
		$this->internalizeFields(
			$fields,
			$fieldsInfo,
			array(
				'IGNORED_ATTRS' => array(
					CCrmFieldInfoAttr::Immutable,
					CCrmFieldInfoAttr::UserPKey
				)
			)
		);

		$errors = array();
		$result = $this->innerUpdate($ID, $fields, $errors, $params);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function delete($ID, array $params = null)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$errors = array();
		$result = $this->innerDelete($ID, $errors, $params);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	protected function prepareListParams(&$order, &$filter, &$select)
	{
	}
	protected function prepareListItemFields(&$fields)
	{
	}
	protected function getCurrentUser()
	{
		return $this->currentUser !== null
			? $this->currentUser
			: ($this->currentUser = CCrmSecurityHelper::GetCurrentUser());
	}
	protected function getCurrentUserID()
	{
		return $this->getCurrentUser()->GetID();
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}
	public function getOwnerTypeID()
	{
		return CCrmOwnerType::Undefined;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$ownerTypeID = $this->getOwnerTypeID();

		$name = mb_strtoupper($name);
		if($name === 'FIELDS')
		{
			return $this->getFields();
		}
		elseif($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			if(!is_array($fields))
			{
				throw new RestException("Parameter 'fields' must be array.");
			}

			$methodParams = $this->resolveArrayParam($arParams, 'params');
			if(!is_array($methodParams))
			{
				throw new RestException("Parameter 'params' must be array.");
			}

			return $this->add($fields, $methodParams);
		}
		elseif($name === 'GET')
		{
			return $this->get($this->resolveEntityID($arParams));
		}
		elseif($name === 'LIST')
		{
			$order = $this->resolveArrayParam($arParams, 'order');
			if(!is_array($order))
			{
				throw new RestException("Parameter 'order' must be array.");
			}

			$filter = $this->resolveArrayParam($arParams, 'filter');
			if(!is_array($filter))
			{
				throw new RestException("Parameter 'filter' must be array.");
			}

			$select = $this->resolveArrayParam($arParams, 'select');
			return $this->getList($order, $filter, $select, $nav);
		}
		elseif($name === 'UPDATE')
		{
			$ID = $this->resolveEntityID($arParams);

			$fields = $fields = $this->resolveArrayParam($arParams, 'fields');
			if(!is_array($fields))
			{
				throw new RestException("Parameter 'fields' must be array.");
			}

			$methodParams = $this->resolveArrayParam($arParams, 'params');
			if(!is_array($methodParams))
			{
				throw new RestException("Parameter 'params' must be array.");
			}

			return $this->update($ID, $fields, $methodParams);
		}
		elseif($name === 'DELETE')
		{
			$ID = $this->resolveEntityID($arParams);
			$methodParams = $this->resolveArrayParam($arParams, 'params');
			return $this->delete($ID, $methodParams);
		}
		elseif($name === 'USERFIELD' && $ownerTypeID !== CCrmOwnerType::Undefined)
		{
			$ufProxy = new CCrmUserFieldRestProxy($ownerTypeID);

			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'ADD')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields', null);
				return $ufProxy->add(is_array($fields) ? $fields : $arParams);
			}
			elseif($nameSuffix === 'GET')
			{
				return $ufProxy->get($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'LIST')
			{
				$order = $this->resolveArrayParam($arParams, 'order', array());
				if(!is_array($order))
				{
					throw new RestException("Parameter 'order' must be array.");
				}

				$filter = $this->resolveArrayParam($arParams, 'filter', array());
				if(!is_array($filter))
				{
					throw new RestException("Parameter 'filter' must be array.");
				}

				return $ufProxy->getList($order, $filter);
			}
			elseif($nameSuffix === 'UPDATE')
			{
				$ID = $this->resolveEntityID($arParams);

				$fields = $fields = $this->resolveArrayParam($arParams, 'fields');
				if(!is_array($fields))
				{
					throw new RestException("Parameter 'fields' must be array.");
				}

				return $ufProxy->update($ID, $fields);
			}
			elseif($nameSuffix === 'DELETE')
			{
				return $ufProxy->delete($this->resolveEntityID($arParams));
			}
		}
		elseif($name === 'DETAILS')
		{
			$editorRequestDetails = $nameDetails;
			$editorRequestName = array_shift($editorRequestDetails);
			$entityEditorProxy = new CCrmEntityEditorRestProxy($ownerTypeID);
			return $entityEditorProxy->processMethodRequest($editorRequestName, $editorRequestDetails, $arParams, $nav, $server);
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function resolveParam(&$arParams, $name)
	{
		return CCrmRestHelper::resolveParam($arParams, $name, '');
	}
	protected function resolveMultiPartParam(&$arParams, array $nameParts, $default = '')
	{
		if(empty($nameParts))
		{
			return $default;
		}

		$upperUnderscoreName = mb_strtoupper(implode('_', $nameParts));
		if(isset($arParams[$upperUnderscoreName]))
		{
			return $arParams[$upperUnderscoreName];
		}

		$lowerUnderscoreName = mb_strtolower($upperUnderscoreName);
		if(isset($arParams[$lowerUnderscoreName]))
		{
			return $arParams[$lowerUnderscoreName];
		}

		$hungaryName = '';
		foreach($nameParts as $namePart)
		{
			$hungaryName .= ucfirst($namePart);
		}

		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		$hungaryName = "ar{$hungaryName}";
		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		return $default;
	}
	protected function resolveArrayParam(&$arParams, $name, $default = array())
	{
		return CCrmRestHelper::resolveArrayParam($arParams, $name, $default);
	}
	protected function resolveEntityID(&$arParams)
	{
		return CCrmRestHelper::resolveEntityID($arParams);
	}
	protected function resolveRelationID(&$arParams, $relationName)
	{
		$nameLowerCase = mb_strtolower($relationName);
		// Check for camel case (entityId or entityID)
		$camel = "{$nameLowerCase}Id";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		$camel = "{$nameLowerCase}ID";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		// Check for lower case (entity_id)
		$lower = "{$nameLowerCase}_id";
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case (ENTITY_ID)
		$upper = mb_strtoupper($lower);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		return '';
	}
	protected function checkEntityID($ID)
	{
		return is_int($ID) && $ID > 0;
	}

	protected function getAuthToken()
	{
		if(!$this->server)
		{
			return '';
		}

		$auth = $this->server->getAuth();
		return is_array($auth) && isset($auth['auth']) ? $auth['auth'] : '';
	}

	protected static function prepareMultiFieldsInfo(&$fieldsInfo)
	{
		$typesID = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		foreach($typesID as $typeID)
		{
			$fieldsInfo[$typeID] = array(
				'TYPE' => 'crm_multifield',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);
		}
	}
	protected static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeID)
	{
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}
	protected static function prepareFields(array &$fieldsInfo)
	{
		return CCrmRestHelper::prepareFieldInfos($fieldsInfo);
	}
	protected function internalizeFields(&$fields, &$fieldsInfo, $options = array())
	{

		if(!is_array($fields))
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$ignoredAttrs = $options['IGNORED_ATTRS'] ?? array();
		if(!in_array(CCrmFieldInfoAttr::Hidden, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::Hidden;
		}
		if(!in_array(CCrmFieldInfoAttr::ReadOnly, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::ReadOnly;
		}

		$multifields = array();
		foreach($fields as $k => $v)
		{
			$info = $fieldsInfo[$k] ?? null;
			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) && is_array($info['ATTRIBUTES'])
				? $info['ATTRIBUTES']
				: [];
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);

			$ary = array_intersect($ignoredAttrs, $attrs);
			if(!empty($ary))
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = $info['TYPE'] ?? '';

			switch ($fieldType)
			{
				case 'integer':
				case 'user':
					if (!$isMultiple && !is_scalar($v))
					{
						$fields[$k] = (int)$fields[$k];
					}
					break;
				case 'char':
				case 'string':
				case 'date':
				case 'datetime':
				case 'crm_status':
					if (!$isMultiple && !is_scalar($v))
					{
						$fields[$k] = (string)$fields[$k];
					}
					break;
			}

			if($fieldType === 'date' || $fieldType === 'datetime')
			{
				if($v === '')
				{
					$date = '';
				}
				else
				{
					$date = $fieldType === 'date'
						? CRestUtil::unConvertDate($v) : CRestUtil::unConvertDateTime($v, true);
				}

				if($isMultiple)
				{
					if(!is_array($date))
					{
						$date = array($date);
					}

					$dates = array();
					foreach($date as $item)
					{
						if(is_string($item))
						{
							$dates[] = $item;
						}
					}

					if(!empty($dates))
					{
						$fields[$k] = $dates;
					}
					else
					{
						unset($fields[$k]);
					}
				}
				elseif(is_string($date))
				{
					$fields[$k] = $date;
				}
				else
				{
					unset($fields[$k]);
				}
			}
			elseif($fieldType === 'file')
			{
				$this->tryInternalizeFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryInternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryInternalizeDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'crm_multifield')
			{
				$this->tryInternalizeMultiFields($fields, $k, $multifields);
			}
			elseif($fieldType === 'product_file')
			{
				$this->tryInternalizeProductFileField($fields, $k);
			}
			elseif($fieldType === 'product_property')
			{
				$this->tryInternalizeProductPropertyField($fields, $fieldsInfo, $k);
			}
		}

		if(!empty($multifields))
		{
			$fields['FM'] = $multifields;
		}
	}
	protected function tryInternalizeMultiFields(array &$fields, $fieldName, array &$data)
	{
		if(!isset($fields[$fieldName]) && is_array($fields[$fieldName]))
		{
			return false;
		}

		$qty = 0;
		$result = array();
		$values = $fields[$fieldName];
		foreach($values as &$v)
		{
			$ID = $v['ID'] ?? 0;
			$value = isset($v['VALUE']) ? trim((string)$v['VALUE']) : '';
			//Allow empty values for persistent fields for support deletion operation.
			if($ID <= 0 && $value === '')
			{
				continue;
			}

			if($ID > 0 && isset($v['DELETE']) && mb_strtoupper((string)$v['DELETE']) === 'Y')
			{
				//Empty fields will be deleted.
				$value = '';
			}

			$valueType = isset($v['VALUE_TYPE']) ? trim((string)$v['VALUE_TYPE']) : '';
			if($valueType === '')
			{
				$valueType = CCrmFieldMulti::GetDefaultValueType($fieldName);
			}

			$key = $ID > 0 ? $ID : 'n'.(++$qty);
			$result[$key] = array('VALUE_TYPE' => $valueType, 'VALUE' => $value);
		}
		unset($v, $fields[$fieldName]);

		if(empty($result))
		{
			return false;
		}

		$data[$fieldName] = $result;
		return true;
	}
	protected function tryInternalizeFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeFile = isset($v['remove']) && is_string($v['remove']) && mb_strtoupper($v['remove']) === 'Y';
			$fileData = $v['fileData'] ?? '';

			if(!self::isIndexedArray($fileData))
			{
				$fileName = '';
				$fileContent = $fileData;
			}
			else
			{
				$fileDataLength = count($fileData);

				if($fileDataLength > 1)
				{
					$fileName = $fileData[0];
					$fileContent = $fileData[1];
				}
				elseif($fileDataLength === 1)
				{
					$fileName = '';
					$fileContent = $fileData[0];
				}
				else
				{
					$fileName = '';
					$fileContent = '';
				}
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				// Add/replace file
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					if($fileID > 0)
					{
						$fileInfo['old_id'] = $fileID;
					}

					//In this case 'del' flag does not make sense - old file will be replaced by new one.
					/*if($removeFile)
					{
						$fileInfo['del'] = true;
					}*/

					$result[] = &$fileInfo;
					unset($fileInfo);
				}
			}
			elseif($fileID > 0 && $removeFile)
			{
				// Remove file
				$result[] = array(
					'old_id' => $fileID,
					'del' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeProductFileField(&$fields, $fieldName)
	{
		if(!(isset($fields[$fieldName]) && self::isAssociativeArray($fields[$fieldName])))
			return false;

		$result = array();

		//$fileID = isset($fields[$fieldName]['id']) ? intval($fields[$fieldName]['id']) : 0;
		$removeFile = isset($fields[$fieldName]['remove']) && is_string($fields[$fieldName]['remove'])
			&& mb_strtoupper($fields[$fieldName]['remove']) === 'Y';
		$fileData = $fields[$fieldName]['fileData'] ?? '';

		if(!self::isIndexedArray($fileData))
		{
			$fileName = '';
			$fileContent = $fileData;
		}
		else
		{
			$fileDataLength = count($fileData);

			if($fileDataLength > 1)
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}
			elseif($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = '';
				$fileContent = '';
			}
		}

		if(is_string($fileContent) && $fileContent !== '')
		{
			// Add/replace file
			$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
			if(is_array($fileInfo))
			{
				$result = &$fileInfo;
				unset($fileInfo);
			}
		}
		elseif($removeFile)
		{
			// Remove file
			$result = array(
				'del' => 'Y'
			);
		}

		if(!empty($result))
		{
			$fields[$fieldName] = $result;
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$elementID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && mb_strtoupper($v['remove']) === 'Y';
			$fileData = $v['fileData'] ?? '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);

				$settings = $this->getWebDavSettings();
				$iblock = $this->prepareWebDavIBlock($settings);
				$fileName = $iblock->CorrectName($fileName);

				$filePath = $fileInfo['tmp_name'];
				$options = array(
					'new' => true,
					'dropped' => false,
					'arDocumentStates' => array(),
					'arUserGroups' => $iblock->USER['GROUPS'],
					'TMP_FILE' => $filePath,
					'FILE_NAME' => $fileName,
					'IBLOCK_ID' => $settings['IBLOCK_ID'],
					'IBLOCK_SECTION_ID' => $settings['IBLOCK_SECTION_ID'],
					'WF_STATUS_ID' => 1
				);
				$options['arUserGroups'][] = 'Author';

				global $DB;
				$DB->StartTransaction();
				if (!$iblock->put_commit($options))
				{
					$DB->Rollback();
					unlink($filePath);
					throw new RestException($iblock->LAST_ERROR);
				}
				$DB->Commit();
				unlink($filePath);

				if(!isset($options['ELEMENT_ID']))
				{
					throw new RestException('Could not save webdav element.');
				}

				$elementData = array(
					'ELEMENT_ID' => $options['ELEMENT_ID']
				);

				if($elementID > 0)
				{
					$elementData['OLD_ELEMENT_ID'] = $elementID;
				}

				$result[] = &$elementData;
				unset($elementData);
			}
			elseif($elementID > 0 && $removeElement)
			{
				$result[] = array(
					'OLD_ELEMENT_ID' => $elementID,
					'DELETE' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && mb_strtoupper($v['remove']) === 'Y';
			$fileData = $v['fileData'] ?? '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$folder = DiskManager::ensureFolderCreated(StorageFileType::Rest);
				if(!$folder)
				{
					throw new RestException('Could not create disk folder for rest files.');
				}

				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					if($fileName === '' && isset($fileInfo['name']))
					{
						$fileName = $fileInfo['name'];
					}

					try
					{
						$file = $folder->uploadFile(
							$fileInfo,
							array('NAME' => $fileName, 'CREATED_BY' => $this->getCurrentUserID()),
							array(),
							true
						);
						unlink($fileInfo['tmp_name']);
					}
					catch (Main\ArgumentException $e)
					{
						$file = null;
					}

					if(!$file)
					{
						throw new RestException('Could not create disk file.');
					}

					$result[] = array('FILE_ID' => $file->getId());
				}
			}
			elseif($fileID > 0 && $removeElement)
			{
				$result[] = array('OLD_FILE_ID' => $fileID, 'DELETE' => true);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeProductPropertyField(&$fields, &$fieldsInfo, $fieldName)
	{
		static $sanitizer = null;

		if(!is_array($fields) || !isset($fields[$fieldName]))
		{
			return;
		}

		$info = $fieldsInfo[$fieldName] ?? null;
		$rawValue = $fields[$fieldName] ?? null;

		if(!$info)
		{
			unset($fields[$fieldName]);
			return;
		}

		$attrs = $info['ATTRIBUTES'] ?? array();

		$fieldType = $info['TYPE'] ?? '';
		$propertyType = $info['PROPERTY_TYPE'] ?? '';
		$userType = $info['USER_TYPE'] ?? '';

		if ($fieldType === 'product_property')
		{
			$value = array();
			$newIndex = 0;
			$valueId = 'n'.$newIndex;
			if (!self::isIndexedArray($rawValue))
				$rawValue = array($rawValue);
			foreach ($rawValue as &$valueElement)
			{
				if (is_array($valueElement) && isset($valueElement['value']))
				{
					$valueId = (isset($valueElement['valueId']) && intval($valueElement['valueId']) > 0) ?
						intval($valueElement['valueId']) : 'n'.$newIndex++;
					$value[$valueId] = &$valueElement['value'];
				}
				else
				{
					$valueId = 'n'.$newIndex++;
					$value[$valueId] = &$valueElement;
				}
			}
			unset($newIndex, $valueElement);
			foreach ($value as $valueId => $v)
			{
				if($propertyType === 'S' && $userType === 'Date')
				{
					$date = CRestUtil::unConvertDate($v);
					if(is_string($date))
						$value[$valueId] = $date;
					else
						unset($value[$valueId]);
				}
				elseif($propertyType === 'S' && $userType === 'DateTime')
				{
					$datetime = CRestUtil::unConvertDateTime($v, true);
					if(is_string($datetime))
						$value[$valueId] = $datetime;
					else
						unset($value[$valueId]);
				}
				elseif($propertyType === 'F' && empty($userType))
				{
					$this->tryInternalizeProductFileField($value, $valueId);
				}
				elseif($propertyType === 'S' && $userType === 'HTML')
				{
					if (is_array($v) && isset($v['TYPE']) && isset($v['TEXT'])
						&& mb_strtolower($v['TYPE']) === 'html' && !empty($v['TEXT']))
					{
						if ($sanitizer === null)
						{
							$sanitizer = new CBXSanitizer();
							$sanitizer->ApplyDoubleEncode(false);
							$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
						}
						$value[$valueId]['TEXT'] = $sanitizer->SanitizeHtml($v['TEXT']);
					}
				}
			}
			$fields[$fieldName] = $value;
		}
		else
		{
			unset($fields[$fieldName]);
		}
	}

	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		if(!is_array($fields))
		{
			return;
		}

		//Multi fields processing
		if(isset($fields['FM']))
		{
			foreach($fields['FM'] as $fmTypeID => &$fmItems)
			{
				foreach($fmItems as &$fmItem)
				{
					$fmItem['TYPE_ID'] = $fmTypeID;
					unset($fmItem['ENTITY_ID'], $fmItem['ELEMENT_ID']);
				}
				unset($fmItem);
				$fields[$fmTypeID] = $fmItems;
			}
			unset($fmItems);
			unset($fields['FM']);
		}

		foreach($fields as $k => $v)
		{
			$info = $fieldsInfo[$k] ?? null;
			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = $info['ATTRIBUTES'] ?? array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
			$isHidden = in_array(CCrmFieldInfoAttr::Hidden, $attrs, true);
			$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

			if($isHidden)
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = $info['TYPE'] ?? '';
			if($fieldType === 'date')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDate($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDate($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'datetime')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDateTime($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDateTime($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'file')
			{
				$this->tryExternalizeFileField($fields, $k, $isMultiple, $isDynamic);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryExternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryExternalizeDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'attached_diskfile')
			{
				$this->tryExternalizeAttachedDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'product_file')
			{
				$this->tryExternalizeProductFileField($fields, $k, false, false);
			}
			elseif($fieldType === 'product_property')
			{
				$this->tryExternalizeProductPropertyField($fields, $fieldsInfo, $k);
			}
			elseif($fieldType === 'recurring_params' || $fieldType === 'crm_timeline_bindings')
			{
				$this->externalizeFields($fields[$k], $fieldsInfo[$k]['FIELDS']);
			}
		}
	}
	protected function tryExternalizeFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$ownerTypeID = $this->getOwnerTypeID();
		$ownerID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			if($fileID <= 0)
			{
				unset($fields[$fieldName]);
				return false;
			}

			$fields[$fieldName] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
		}
		else
		{
			$result = array();
			$filesID = $fields[$fieldName];
			if(!is_array($filesID))
			{
				$filesID = array($filesID);
			}

			foreach($filesID as $fileID)
			{
				$fileID = intval($fileID);
				if($fileID > 0)
				{
					$result[] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}
	protected function tryExternalizeProductFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
			return false;

		$productID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			if (!$dynamic)
			{
				$fileID = intval($fields[$fieldName]);
				if($fileID <= 0)
				{
					unset($fields[$fieldName]);
					return false;
				}

				$fields[$fieldName] = $this->externalizeProductFile($productID, $fieldName, 0, $fileID, $dynamic);
			}
			else
			{
				if (!(is_array($fields[$fieldName]) && isset($fields[$fieldName]['VALUE_ID'])
					&& isset($fields[$fieldName]['VALUE'])))
				{
					unset($fields[$fieldName]);
					return false;
				}

				$valueID = intval($fields[$fieldName]['VALUE_ID']);
				$fileID = intval($fields[$fieldName]['VALUE']);
				if($fileID <= 0)
				{
					unset($fields[$fieldName]);
					return false;
				}

				$fields[$fieldName] = $this->externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic);
			}
		}
		else
		{
			if (!self::isIndexedArray($fields[$fieldName]))
			{
				unset($fields[$fieldName]);
				return false;
			}

			$result = array();
			foreach($fields[$fieldName] as $element)
			{
				if (!(isset($element['VALUE_ID']) && isset($element['VALUE'])))
					continue;

				$valueID = intval($element['VALUE_ID']);
				$fileID = intval($element['VALUE']);
				if($fileID > 0)
				{
					$result[] = $this->externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}
	protected function tryExternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		if(!$multiple)
		{
			$elementID = intval($fields[$fieldName]);
			$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $elementID,
					'url' => $info['SHOW_URL'] ?? ''
				);

				return true;
			}
		}

		$result = array();
		$elementsID = $fields[$fieldName];
		if(is_array($elementsID))
		{
			foreach($elementsID as $elementID)
			{
				$elementID = intval($elementID);
				$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $elementID,
					'url' => $info['SHOW_URL'] ?? ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryExternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$options = array(
			'OWNER_TYPE_ID' => $this->getOwnerTypeID(),
			'OWNER_ID' => $fields['ID'],
			'VIEW_PARAMS' => array('auth' => $this->getAuthToken()),
			'USE_ABSOLUTE_PATH' => true
		);

		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			$info = DiskManager::getFileInfo($fileID, false, $options);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $fileID,
					'url' => $info['VIEW_URL'] ?? ''
				);

				return true;
			}
		}

		$result = array();
		$fileIDs = $fields[$fieldName];
		if(is_array($fileIDs))
		{
			foreach($fileIDs as $fileID)
			{
				$info = DiskManager::getFileInfo($fileID, false, $options);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $fileID,
					'url' => $info['VIEW_URL'] ?? ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryExternalizeAttachedDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		if (
			!empty($fields[$fieldName])
			&& is_array($fields[$fieldName])
			&& \Bitrix\Main\Loader::includeModule('disk')
		)
		{
			$fileInfos = [];
			$res = Bitrix\Disk\AttachedObject::getList(array(
				'filter' => array(
					'@ID' => array_unique($fields[$fieldName])
				),
				'select' => array('ID', 'OBJECT_ID')
			));
			while($attachedObjectFields = $res->fetch())
			{
				$diskObjectId = $attachedObjectFields['OBJECT_ID'];

				if ($fileData = self::getFileData($diskObjectId))
				{
					$attachedObjectList[$attachedObjectFields['ID']] = $diskObjectId;
					$fileInfos[$diskObjectId] = $fileData;
				}
			}
			$result = self::convertFileData($fileInfos);
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	private static function getFileData($diskObjectId)
	{
		$result = false;

		$diskObjectId = intval($diskObjectId);
		if ($diskObjectId <= 0)
		{
			return $result;
		}

		if ($fileModel = \Bitrix\Disk\File::getById($diskObjectId))
		{
			/** @var \Bitrix\Disk\File $fileModel */
			$contentType = 'file';
			$imageParams = false;
			if (\Bitrix\Disk\TypeFile::isImage($fileModel))
			{
				$contentType = 'image';
				$params = $fileModel->getFile();
				$imageParams = Array(
					'width' => (int)$params['WIDTH'],
					'height' => (int)$params['HEIGHT'],
				);
			}
			else if (\Bitrix\Disk\TypeFile::isVideo($fileModel->getName()))
			{
				$contentType = 'video';
				$params = $fileModel->getView()->getPreviewData();
				$imageParams = Array(
					'width' => (int)$params['WIDTH'],
					'height' => (int)$params['HEIGHT'],
				);
			}

			$isImage = \Bitrix\Disk\TypeFile::isImage($fileModel);
			$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();

			$result = array(
				'id' => (int)$fileModel->getId(),
				'date' => $fileModel->getCreateTime(),
				'type' => $contentType,
				'name' => $fileModel->getName(),
				'size' => (int)$fileModel->getSize(),
				'image' => $imageParams,
				'authorId' => (int)$fileModel->getCreatedBy(),
				'authorName' => CUser::FormatName(CSite::getNameFormat(false), $fileModel->getCreateUser(), true, true),
				'urlPreview' => (
				$fileModel->getPreviewId()
					? $urlManager->getUrlForShowPreview($fileModel, [ 'width' => 640, 'height' => 640])
					: (
				$isImage
					? $urlManager->getUrlForShowFile($fileModel, [ 'width' => 640, 'height' => 640])
					: null
				)
				),
				'urlShow' => ($isImage ? $urlManager->getUrlForShowFile($fileModel) : $urlManager->getUrlForDownloadFile($fileModel)),
				'urlDownload' => $urlManager->getUrlForDownloadFile($fileModel)
			);
		}

		return $result;
	}
	private static function convertFileData($fileData)
	{
		if (!is_array($fileData))
		{
			return array();
		}

		foreach ($fileData as $key => $value)
		{
			if ($value['date'] instanceof \Bitrix\Main\Type\DateTime)
			{
				$fileData[$key]['date'] = date('c', $value['date']->getTimestamp());
			}

			foreach (['urlPreview', 'urlShow', 'urlDownload'] as $field)
			{
				$url = $fileData[$key][$field];
				if (is_string($url) && $url && mb_strpos($url, 'http') !== 0)
				{
					$fileData[$key][$field] = self::getPublicDomain().$url;
				}
			}
		}

		return $fileData;
	}

	private static function getPublicDomain()
	{
		static $result = null;
		if ($result === null)
		{
			$result = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : \Bitrix\Main\Config\Option::get("main", "server_name", $_SERVER['SERVER_NAME']));
		}

		return $result;
	}
	protected function tryExternalizeProductPropertyField(&$fields, &$fieldsInfo, $fieldName)
	{
		if(!is_array($fields) || !isset($fields[$fieldName]))
		{
			return;
		}

		$info = $fieldsInfo[$fieldName] ?? null;
		$value = $fields[$fieldName] ?? null;

		if(!$info)
		{
			unset($fields[$fieldName]);
			return;
		}

		$attrs = $info['ATTRIBUTES'] ?? array();
		$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
		$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

		$fieldType = $info['TYPE'] ?? '';
		$propertyType = $info['PROPERTY_TYPE'] ?? '';
		$userType = $info['USER_TYPE'] ?? '';
		if($fieldType === 'product_property' && $propertyType === 'S' && $userType === 'Date')
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => CRestUtil::ConvertDate($valueElement['VALUE'])
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => CRestUtil::ConvertDate($value['VALUE'])
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
		elseif($fieldType === 'product_property' && $propertyType === 'S' && $userType === 'DateTime')
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => CRestUtil::ConvertDateTime($valueElement['VALUE'])
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => CRestUtil::ConvertDateTime($value['VALUE'])
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
		elseif($fieldType === 'product_property' && $propertyType === 'F' && empty($userType))
		{
			$this->tryExternalizeProductFileField($fields, $fieldName, $isMultiple, $isDynamic);
		}
		else
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => $valueElement['VALUE']
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => $value['VALUE']
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
	}
	protected function internalizeFilterFields(&$filter, &$fieldsInfo)
	{
		if(!is_array($filter))
		{
			return;
		}

		foreach($filter as $k => $v)
		{
			$operationInfo =  CSqlUtil::GetFilterOperation($k);
			$fieldName = $operationInfo['FIELD'];

			$info = $fieldsInfo[$fieldName] ?? null;
			if(!$info)
			{
				unset($filter[$k]);
				continue;
			}

			$operation = mb_substr($k, 0, mb_strlen($k) - mb_strlen($fieldName));
			if(isset($info['FORBIDDEN_FILTERS'])
				&& is_array($info['FORBIDDEN_FILTERS'])
				&& in_array($operation, $info['FORBIDDEN_FILTERS'], true))
			{
				unset($filter[$k]);
				continue;
			}

			$fieldType = $info['TYPE'] ?? '';
			if(($fieldType === 'crm_status' || $fieldType === 'crm_company' || $fieldType === 'crm_contact')
				&& ($operation === '%' || $operation === '%=' || $operation === '=%'))
			{
				//Prevent filtration by LIKE due to performance considerations
				$filter["={$fieldName}"] = $v;
				unset($filter[$k]);
				continue;
			}
			if (in_array($operation, ['<', '>', '>=', '<=']) && $fieldType === 'integer')
			{
				$filter[$k] = (int)$v;
			}

			if($fieldType === 'datetime')
			{
				$filter[$k] = CRestUtil::unConvertDateTime($v, true);
			}
			elseif($fieldType === 'date')
			{
				$filter[$k] = CRestUtil::unConvertDate($v);
			}
		}

		CCrmEntityHelper::PrepareMultiFieldFilter($filter, array(), '=%', true);
	}
	protected static function isAssociativeArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return true;
			}
		}
		return false;
	}
	protected static function isIndexedArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return false;
			}
		}
		return true;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$errors[] = 'The operation "ADD" is not supported by this entity.';
		return false;
	}
	protected function innerGet($ID, &$errors)
	{
		$errors[] = 'The operation "GET" is not supported by this entity.';
		return false;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$errors[] = 'The operation "LIST" is not supported by this entity.';
		return null;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$errors[] = 'The operation "UPDATE" is not supported by this entity.';
		return false;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$errors[] = 'The operation "DELETE" is not supported by this entity.';;
		return false;
	}
	protected function externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic = true)
	{
		$ownerTypeName = mb_strtolower(CCrmOwnerType::ResolveName($ownerTypeID));
		if($ownerTypeName === '')
		{
			return '';
		}

		$handlerUrl = "/bitrix/components/bitrix/crm.{$ownerTypeName}.show/show_file.php";
		$showUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => $this->getAuthToken(),
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		return array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);
	}
	protected function externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic = true)
	{
		$handlerUrl = "/bitrix/components/bitrix/crm.product.file/download.php";
		$showUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?productId=#product_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'product_id' => $productID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&productId=#product_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => $this->getAuthToken(),
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'product_id' => $productID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$result = array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);

		if ($dynamic)
			$result = array(
				'valueId' => $valueID,
				'value' => $result
			);

		return $result;
	}
	// WebDav -->
	protected function prepareWebDavIBlock($settings = null)
	{
		if($this->webdavIBlock !== null)
		{
			return $this->webdavIBlock;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		if(!is_array($settings) || empty($settings))
		{
			$settings = $this->getWebDavSettings();
		}

		$iblockID = $settings['IBLOCK_ID'] ?? 0;
		if($iblockID <= 0)
		{
			throw new RestException('Could not find webdav iblock.');
		}

		$sectionId = $settings['IBLOCK_SECTION_ID'] ?? 0;
		if($sectionId <= 0)
		{
			throw new RestException('Could not find webdav section.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();
		$this->webdavIBlock = new CWebDavIblock(
			$iblockID,
			'',
			array(
				'ROOT_SECTION_ID' => $sectionId,
				'DOCUMENT_TYPE' => array('webdav', 'CIBlockDocumentWebdavSocnet', 'iblock_'.$sectionId.'_user_'.$user->GetID())
			)
		);

		return $this->webdavIBlock;
	}
	protected function getWebDavSettings()
	{
		if($this->webdavSettings !== null)
		{
			return $this->webdavSettings;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		$opt = COption::getOptionString('webdav', 'user_files', null);
		if($opt == null)
		{
			throw new RestException('Could not find webdav settings.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();

		$opt = unserialize($opt, ['allowed_classes' => false]);
		$iblockID = intval($opt[CSite::GetDefSite()]['id']);
		$userSectionID = CWebDavIblock::getRootSectionIdForUser($iblockID, $user->GetID());
		if(!is_numeric($userSectionID) || $userSectionID <= 0)
		{
			throw new RestException('Could not find webdav section for user '.$user->GetLastName().'.');
		}

		return ($this->webdavSettings =
			array(
				'IBLOCK_ID' => $iblockID,
				'IBLOCK_SECTION_ID' => intval($userSectionID),
			)
		);
	}
	// <-- WebDav
	/**
	 * @return array
	 * @throws RestException
	 */
	protected function getFieldsInfo()
	{
		throw new RestException('The method is not implemented.');
	}
	protected function sanitizeHtml($html)
	{
		return \Bitrix\Crm\Format\TextHelper::sanitizeHtml($html);
	}
	protected function getIdentityFieldName()
	{
		return '';
	}
	protected function getIdentity(&$fields)
	{
		return 0;
	}
	protected static function getMultiFieldTypeIDs()
	{
		if(self::$MULTIFIELD_TYPE_IDS === null)
		{
			self::$MULTIFIELD_TYPE_IDS = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		}

		return self::$MULTIFIELD_TYPE_IDS;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return null;
	}
	protected function prepareListItemMultiFields(&$entityMap, $entityTypeID, $typeIDs)
	{
		$entityIDs = array_keys($entityMap);
		if(empty($entityIDs))
		{
			return;
		}

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetListEx(
			array(),
			array(
				'=ENTITY_ID' => $entityTypeName,
				'@ELEMENT_ID' => $entityIDs,
				'@TYPE_ID' => $typeIDs
			)
		);

		while($fm = $dbResult->Fetch())
		{
			$typeID = $fm['TYPE_ID'] ?? '';
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			$entityID = isset($fm['ELEMENT_ID']) ? intval($fm['ELEMENT_ID']) : 0;
			if(!isset($entityMap[$entityID]))
			{
				continue;
			}

			$entity = &$entityMap[$entityID];
			if(!isset($entity['FM']))
			{
				$entity['FM'] = array();
			}

			if(!isset($entity['FM'][$typeID]))
			{
				$entity['FM'][$typeID] = array();
			}

			$entity['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
			unset($entity);
		}
	}
	protected function prepareMultiFieldData($entityTypeID, $entityID, &$entityFields, $typeIDs = null)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		if(!CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
				'ELEMENT_ID' => $entityID
			)
		);

		if(!is_array($typeIDs) || empty($typeIDs))
		{
			$typeIDs = self::getMultiFieldTypeIDs();
		}

		$entityFields['FM'] = array();
		while($fm = $dbResult->Fetch())
		{
			$typeID = $fm['TYPE_ID'];
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			if(!isset($entityFields['FM'][$typeID]))
			{
				$entityFields['FM'][$typeID] = array();
			}

			$entityFields['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}
	}
	protected static function isBizProcEnabled()
	{
		return !Bitrix24Manager::isEnabled() || Bitrix24Manager::isRestBizProcEnabled();
	}
	protected static function isRequiredUserFieldCheckEnabled()
	{
		return RestSettings::getCurrent()->isRequiredUserFieldCheckEnabled();
	}
	public static function processEntityEvent($entityTypeID, array $arParams, array $arHandler)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			throw new RestException("The 'entityTypeName' is not specified");
		}

		$eventName = $arHandler['EVENT_NAME'];

		$eventNamePrefix = 'ONCRM'.$entityTypeName;
		if(mb_strpos(mb_strtoupper($eventName), $eventNamePrefix) !== 0)
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		$eventNamePrefix .= 'USERFIELD';
		if(mb_strpos(mb_strtoupper($eventName), $eventNamePrefix) === 0)
		{
			return self::processEntityUserFieldEvent($entityTypeID, $arParams, $arHandler);
		}

		$action = mb_substr($eventName, 5 + mb_strlen($entityTypeName));
		if($action === false || $action === '')
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		switch(mb_strtoupper($action))
		{
			case 'ADD':
			case 'UPDATE':
				{
					$fields = $arParams[0] ?? null;
					$ID = is_array($fields) && isset($fields['ID'])? (int)$fields['ID'] : 0;
				}
				break;
			case 'DELETE':
				{
					$ID = isset($arParams[0])? (int)$arParams[0] : 0;
				}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID <= 0)
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
	public static function processEntityUserFieldEvent($entityTypeID, array $arParams, array $arHandler)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			throw new RestException("The 'entityTypeName' is not specified");
		}

		$eventName = $arHandler['EVENT_NAME'];

		$eventNamePrefix = 'ONCRM'.$entityTypeName.'USERFIELD';
		if(mb_strpos(mb_strtoupper($eventName), $eventNamePrefix) !== 0)
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if (!($arParams[0] instanceof Bitrix\Main\Event))
		{
			throw new RestException("Invalid parameters of event \"{$eventName}\"");
		}

		$params = $arParams[0]->getParameters();

		$action = mb_substr($eventName, mb_strlen($eventNamePrefix));
		switch ($action)
		{
			case 'ADD':
			case 'UPDATE':
			case 'DELETE':
			case 'SETENUMVALUES':
				{
					$id = isset($params['id']) ? (int)$params['id'] : 0;
					$entityId = isset($params['entityId']) && is_string($params['entityId']) ?
						$params['entityId'] : '';
					$fieldName = isset($params['fieldName']) && is_string($params['fieldName']) ?
						$params['fieldName'] : '';

					if($id <= 0)
					{
						throw new RestException("Could not find parameter \"ID\" of event \"{$eventName}\"");
					}

					if($entityId === '')
					{
						throw new RestException("Could not find parameter \"ENTITY_ID\" of event \"{$eventName}\"");
					}

					if($fieldName === '')
					{
						throw new RestException("Could not find parameter \"FIELD_NAME\" of event \"{$eventName}\"");
					}

					return array('FIELDS' => array('ID' => $id, 'ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName));
				}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
	protected static function getDefaultEventSettings()
	{
		return array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM);
	}
	protected static function createEventInfo($moduleName, $eventName, array $callback)
	{
		return array($moduleName, $eventName, $callback, array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM));
	}
	protected function traceEntity($entityTypeId, $entityId, $fields, $isUpdate = false)
	{
		if ($isUpdate)
		{
			Tracking\Entity::onRestAfterUpdate($entityTypeId, $entityId, $fields);
		}
		else
		{
			Tracking\Entity::onRestAfterAdd($entityTypeId, $entityId, $fields);
		}
	}
}

class CCrmSettingsRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
		if($name === 'MODE')
		{
			if($nameSuffix === 'GET')
			{
				return \Bitrix\Crm\Settings\Mode::getCurrent();
			}
		}

		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmEnumerationRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;

	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
					'CAPTION' => Loc::getMessage('CRM_REST_FIELD_ID')
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
					'CAPTION' => Loc::getMessage('CRM_REST_FIELD_NAME')
				),
				'SYMBOL_CODE' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
					'CAPTION' => Loc::getMessage('CRM_REST_ENUM_FIELD_SYMBOL_CODE')
				],
				'SYMBOL_CODE_SHORT' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
					'CAPTION' => Loc::getMessage('CRM_REST_ENUM_FIELD_SYMBOL_CODE_SHORT')
				],
			);
		}
		return $this->FIELDS_INFO;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$descriptions = null;
		$codes = [];

		$name = mb_strtoupper($name);
		$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
		if($name === 'SETTINGS')
		{
			if($nameSuffix === 'MODE')
			{
				$descriptions = \Bitrix\Crm\Settings\Mode::getAllDescriptions();
			}
		}
		elseif($name === 'OWNERTYPE')
		{
			$allDescriptions = CCrmOwnerType::GetAllDescriptions();
			$types = [
				CCrmOwnerType::Lead => CCrmOwnerType::Lead,
				CCrmOwnerType::Deal => CCrmOwnerType::Deal,
				CCrmOwnerType::Contact => CCrmOwnerType::Contact,
				CCrmOwnerType::Company => CCrmOwnerType::Company,
				CCrmOwnerType::Quote => CCrmOwnerType::Quote,
				CCrmOwnerType::Invoice => CCrmOwnerType::Invoice,
				CCrmOwnerType::Requisite => CCrmOwnerType::Requisite,
			];
			foreach ($allDescriptions as $entityTypeId => $description)
			{
				if (isset($types[$entityTypeId]) || \CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
				{
					$descriptions[$entityTypeId] = $description;
				}
			}

			foreach ((array)$descriptions as $entityTypeId => $description)
			{
				$codes[$entityTypeId] = [
					'SYMBOL_CODE' => \CCrmOwnerType::ResolveName($entityTypeId),
					'SYMBOL_CODE_SHORT' => \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId),
				];
			}
		}
		elseif($name === 'ADDRESSTYPE')
		{
			$descriptions = EntityAddressType::getDescriptions(EntityAddressType::getAvailableIds());
		}
		elseif($name === 'CONTENTTYPE')
		{
			$descriptions = CCrmContentType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYTYPE')
		{
			$descriptions = CCrmActivityType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYPRIORITY')
		{
			$descriptions = CCrmActivityPriority::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYDIRECTION')
		{
			$descriptions = CCrmActivityDirection::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYNOTIFYTYPE')
		{
			$descriptions = CCrmActivityNotifyType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYSTATUS')
		{
			$descriptions = CCrmActivityStatus::GetAllDescriptions();
		}
		elseif($name === 'ENTITYEDITOR')
		{
			if($nameSuffix === 'CONFIGURATION_SCOPE')
			{
				$descriptions = \Bitrix\Crm\Entity\EntityEditorConfigScope::getCaptions();
			}
		}

		if(!is_array($descriptions))
		{
			return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}

		$result = array();
		foreach($descriptions as $k => $v)
		{
			$result[] = [
				'ID' => $k,
				'NAME' => $v,
				'SYMBOL_CODE' => $codes[$k]['SYMBOL_CODE'] ?? null,
				'SYMBOL_CODE_SHORT' => $codes[$k]['SYMBOL_CODE_SHORT'] ?? null,
			];
		}
		unset($v);
		return $result;
	}
}

class CCrmMultiFieldRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
					'CAPTION' => Loc::getMessage('CRM_REST_FIELD_ID')
				),
				'TYPE_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
					'CAPTION' => Loc::getMessage('CRM_REST_MULTIFIELD_FIELD_TYPE_ID')
				),
				'VALUE' => array(
					'TYPE' => 'string',
					'CAPTION' => Loc::getMessage('CRM_REST_MULTIFIELD_FIELD_VALUE')
				),
				'VALUE_TYPE' => array(
					'TYPE' => 'string',
					'CAPTION' => Loc::getMessage('CRM_REST_MULTIFIELD_FIELD_VALUE_TYPE')
				)
			);
		}
		return $this->FIELDS_INFO;
	}
}

class CCrmCatalogRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCatalog::GetFieldsInfo();
			foreach ($this->FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = CCrmCatalog::GetFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}

	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCatalog::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Catalog is not found.';
			return null;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCatalog::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
}

class CCrmProductRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;

	private $userTypes = null;
	private $properties = null;

	protected function initializePropertiesInfo($catalogID)
	{
		if ($this->userTypes === null)
			$this->userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		if ($this->properties === null)
			$this->properties = CCrmProductPropsHelper::GetProps($catalogID, $this->userTypes);
	}

	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProduct::GetFieldsInfo();
			foreach ($this->FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = CCrmProduct::GetFieldCaption($code);
			}
			$this->preparePropertyFieldsInfo($this->FIELDS_INFO);
		}
		return $this->FIELDS_INFO;
	}

	protected function preparePropertyFieldsInfo(&$fieldsInfo)
	{
		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
			return;
		$this->initializePropertiesInfo($catalogID);
		foreach($this->properties as $propertyName => $propertyInfo)
		{
			$propertyType = $propertyInfo['PROPERTY_TYPE'];
			$info = array(
				'TYPE' => 'product_property',
				'PROPERTY_TYPE' => $propertyType,
				'USER_TYPE' => $propertyInfo['USER_TYPE'],
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Dynamic),
				'NAME' => $propertyInfo['NAME']
			);

			$isMultuple = isset($propertyInfo['MULTIPLE']) && $propertyInfo['MULTIPLE'] === 'Y';
			$isRequired = isset($propertyInfo['IS_REQUIRED']) && $propertyInfo['IS_REQUIRED'] === 'Y';
			if($isMultuple || $isRequired)
			{
				if($isMultuple)
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Multiple;
				if($isRequired)
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Required;
			}

			if ($propertyInfo['PROPERTY_TYPE'] === 'L')
			{
				$values = array();
				$resEnum = CIBlockProperty::GetPropertyEnum($propertyInfo['ID'], array('SORT' => 'ASC','ID' => 'ASC'));
				while($enumValue = $resEnum->Fetch())
				{
					$values[intval($enumValue['ID'])] = array(
						'ID' => $enumValue['ID'],
						'VALUE' => $enumValue['VALUE']
					);
				}
				$info['VALUES'] = $values;
			}

			$fieldsInfo[$propertyName] = $info;
		}
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = intval(CCrmCatalog::EnsureDefaultExists());
		if($catalogID <= 0)
		{
			$errors[] = 'Default catalog is not exists.';
			return false;
		}

		if (isset($fields['DESCRIPTION']))
		{
			$descriptionType = (isset($fields['DESCRIPTION_TYPE']) && $fields['DESCRIPTION_TYPE'] === 'html') ?
				'html' : 'text';
			$fields['DESCRIPTION_TYPE'] = $descriptionType;
			$description =
				(isset($fields['DESCRIPTION']) && is_string($fields['DESCRIPTION']))
					? trim($fields['DESCRIPTION'])
					: ''
			;
			$isNeedSanitize = (
				$descriptionType === 'html'
				&& $description !== ''
				&& mb_strpos($description, '<') !== false
			);
			if ($isNeedSanitize)
			{
				$description = self::sanitizeHtml($description);
			}
			$fields['DESCRIPTION'] = $description;
			unset($descriptionType, $description, $isNeedSanitize);
		}

		// Product properties
		$this->initializePropertiesInfo($catalogID);
		$propertyValues = array();
		foreach ($this->properties as $propId => $property)
		{
			if (isset($fields[$propId]))
				$propertyValues[$property['ID']] = $fields[$propId];
			unset($fields[$propId]);
		}
		if(count($propertyValues) > 0)
			$fields['PROPERTY_VALUES'] = $propertyValues;

		$conn = Application::getConnection();
		$conn->startTransaction();
		$result = false;
		$internalError = '';
		try
		{
			$result = CCrmProduct::Add($fields);
			if (!is_int($result))
			{
				$internalError = CCrmProduct::GetLastError();
				if ($internalError === '')
				{
					$internalError = 'Unable to create product.';
				}
			}
		}
		catch (SqlQueryException)
		{
			$internalError = 'Internal error adding product. Try adding again.';
		}
		if ($internalError === '')
		{
			$conn->commitTransaction();
		}
		else
		{
			$conn->rollbackTransaction();
			$errors[] = $internalError;
		}

		return $result;
	}

	protected function innerGet($ID, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$errors[] = 'Product is not found.';
			return null;
		}

		$filter = array('ID' => $ID, 'CATALOG_ID'=> $catalogID);
		$dbResult = CCrmProduct::GetList(array(), $filter, array('*'), array('nTopCount' => 1));
		$result = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Product is not found.';
			return null;
		}

		$this->initializePropertiesInfo($catalogID);
		$this->getProperties($catalogID, $result, array('PROPERTY_*'));

		return $result;
	}

	public function getList($order, $filter, $select, $start)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$result = array();
			$dbResult = new CDBResult();
			$dbResult->InitFromArray($result);
			return CCrmRestService::setNavData($result, $dbResult);
		}

		$navigation = CCrmRestService::getNavData($start);

		if(!is_array($order) || empty($order))
		{
			$order = array('sort' => 'asc', 'id' => 'asc');
		}
		else
		{
			$idInSort = false;
			foreach (array_keys($order) as $by)
			{
				if (mb_strtoupper($by) === 'ID')
				{
					$idInSort = true;
					break;
				}
			}
			if (!$idInSort)
			{
				$order['id'] = 'asc';
			}
			unset($idInSort, $by);
		}

		if(!isset($navigation['bShowAll']))
		{
			$navigation['bShowAll'] = false;
		}

		$enableCatalogData = false;
		$catalogSelect = null;
		$priceSelect = null;
		$vatSelect = null;
		$propertiesSelect = array();

		$selectAll = false;
		if(is_array($select))
		{
			if(!empty($select))
			{
				// Remove '*' for get rid of inefficient construction of price data
				foreach($select as $k => $v)
				{
					if (!is_string($v))
					{
						unset($select[$k]);
						continue;
					}
					if($v === '*')
					{
						$selectAll = true;
						unset($select[$k]);
					}
					else if (preg_match('/^PROPERTY_(\d+|\*)$/', $v))
					{
						$propertiesSelect[] = $v;
						unset($select[$k]);
					}
				}
			}

			if (!empty($propertiesSelect) && empty($select) && !$selectAll)
				$select = array('ID');

			if(empty($select))
			{
				$priceSelect = array('PRICE', 'CURRENCY_ID');
				$vatSelect = array('VAT_ID', 'VAT_INCLUDED', 'MEASURE');
			}
			else
			{
				$priceSelect = array();
				$vatSelect = array();

				$select = CCrmProduct::DistributeProductSelect($select, $priceSelect, $vatSelect);
			}

			$catalogSelect = array_merge($priceSelect, $vatSelect);
			$enableCatalogData = !empty($catalogSelect);
		}

		if (empty($propertiesSelect) && $selectAll)
		{
			$propertiesSelect[] = 'PROPERTY_*';
		}

		$fieldsInfo = $this->getFieldsInfo();
		$this->internalizeFilterFields($filter, $fieldsInfo);

		$filter['CATALOG_ID'] = $catalogID;
		$dbResult = CCrmProduct::GetList($order, $filter, $select, $navigation);
		if(!$enableCatalogData)
		{
			$result = array();
			$fieldsInfo = $this->getFieldsInfo();
			while($fields = $dbResult->Fetch())
			{
				$selectedFields = array();
				if (!empty($select))
				{
					$selectedFields['ID'] = $fields['ID'];
					foreach ($select as $k)
						$selectedFields[$k] = &$fields[$k];
					$fields = &$selectedFields;
				}
				unset($selectedFields);

				$this->getProperties($catalogID, $fields, $propertiesSelect);
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$itemMap = array();
			$itemIDs = array();
			while($fields = $dbResult->Fetch())
			{
				$selectedFields = array();
				if (!empty($select))
				{
					$selectedFields['ID'] = $fields['ID'];
					foreach ($select as $k)
						$selectedFields[$k] = &$fields[$k];
					$fields = &$selectedFields;
				}
				unset($selectedFields);

				foreach ($catalogSelect as $fieldName)
				{
					$fields[$fieldName] = null;
				}

				$itemID = isset($fields['ID']) ? intval($fields['ID']) : 0;
				if($itemID > 0)
				{
					$itemIDs[] = $itemID;
					$itemMap[$itemID] = $fields;
				}

			}
			CCrmProduct::ObtainPricesVats($itemMap, $itemIDs, $priceSelect, $vatSelect, true);

			$result = array_values($itemMap);
			$fieldsInfo = $this->getFieldsInfo();
			foreach($result as &$fields)
			{
				$this->getProperties($catalogID, $fields, $propertiesSelect);
				$this->externalizeFields($fields, $fieldsInfo);
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}

	public function getProperties($catalogID, &$fields, $propertiesSelect)
	{
		if ($catalogID <= 0)
			return;

		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		$productID = isset($fields['ID']) ? intval($fields['ID']) : 0;

		if ($productID <= 0)
			return;

		if (empty($propertiesSelect))
		{
			return;
		}

		$this->initializePropertiesInfo($catalogID);

		$selectAll = false;
		foreach($propertiesSelect as $k => $v)
		{
			if($v === 'PROPERTY_*')
			{
				$selectAll = true;
				unset($propertiesSelect[$k]);
				break;
			}
		}

		$propertyValues = array();
		if ($productID > 0 && count($this->properties) > 0)
		{
			$rsProperties = CIBlockElement::GetProperty(
				$catalogID,
				$productID,
				array(
					'sort' => 'asc',
					'id' => 'asc',
					'enum_sort' => 'asc',
					'value_id' => 'asc',
				),
				array(
					'ACTIVE' => 'Y',
					'EMPTY' => 'N',
					'CHECK_PERMISSIONS' => 'N'
				)
			);
			while ($property = $rsProperties->Fetch())
			{
				if (isset($property['USER_TYPE']) && !empty($property['USER_TYPE'])
					&& !array_key_exists($property['USER_TYPE'], $this->userTypes))
					continue;

				$propId = 'PROPERTY_' . $property['ID'];
				if(!isset($propertyValues[$propId]))
					$propertyValues[$propId] = array();
				$propertyValues[$propId][] =
					array('VALUE_ID' => $property['PROPERTY_VALUE_ID'], 'VALUE' => $property['VALUE']);
			}
			unset($rsProperties, $property, $propId);
		}
		foreach ($this->properties as $propId => $prop)
		{
			if ($selectAll || in_array($propId, $propertiesSelect, true))
			{
				$value = null;
				if (isset($propertyValues[$propId]))
				{
					if ($prop['MULTIPLE'] === 'Y')
						$value = $propertyValues[$propId];
					else if (count($propertyValues[$propId]) > 0)
						$value = end($propertyValues[$propId]);
				}
				$fields[$propId] = $value;
			}
		}
	}

	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$errors[] = 'Product catalog is not found.';
			return false;
		}

		//EnsureDefaultCatalogScope will check if product exists in default catalog
		if(!CCrmProduct::EnsureDefaultCatalogScope($ID))
		{
			$errors[] = 'Product is not found';
			return false;
		}

		if (isset($fields['DESCRIPTION']))
		{
			$descriptionType = '';
			if (isset($fields['DESCRIPTION_TYPE']))
			{
				$descriptionType = $fields['DESCRIPTION_TYPE'];
			}
			if ($descriptionType !== 'html' && $descriptionType !== 'text')
			{
				$res = CCrmProduct::GetList(array(), array('ID' => $ID), array('DESCRIPTION_TYPE'));
				if ($row = $res->Fetch())
				{
					$descriptionType = $row['DESCRIPTION_TYPE'];
				}
				unset($res, $row);
			}
			if ($descriptionType !== 'html' && $descriptionType !== 'text')
			{
				$descriptionType = 'text';
			}

			$description =
				(isset($fields['DESCRIPTION']) && is_string($fields['DESCRIPTION']))
					? trim($fields['DESCRIPTION'])
					: ''
			;
			$isNeedSanitize = (
				$descriptionType === 'html'
				&& $description !== ''
				&& mb_strpos($description, '<') !== false
			);
			if ($isNeedSanitize)
			{
				$description = self::sanitizeHtml($description);
			}
			$fields['DESCRIPTION'] = $description;
			unset($descriptionType, $description, $isNeedSanitize);
		}

		// Product properties
		$this->initializePropertiesInfo($catalogID);
		$propertyValues = array();
		foreach ($this->properties as $propId => $property)
		{
			if (isset($fields[$propId]))
				$propertyValues[$property['ID']] = $fields[$propId];
			unset($fields[$propId]);
		}
		if(!empty($propertyValues))
		{
			$fields['PROPERTY_VALUES'] = $propertyValues;
			$rsProperties = CIBlockElement::GetProperty(
				$catalogID,
				$ID,
				'sort', 'asc',
				array('ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
			);
			while($property = $rsProperties->Fetch())
			{
				if($property['PROPERTY_TYPE'] !== 'F' && !array_key_exists($property['ID'], $propertyValues))
				{
					if(!array_key_exists($property['ID'], $fields['PROPERTY_VALUES']))
						$fields['PROPERTY_VALUES'][$property['ID']] = array();

					$fields['PROPERTY_VALUES'][$property['ID']][$property['PROPERTY_VALUE_ID']] = array(
						'VALUE' => $property['VALUE'],
						'DESCRIPTION' => $property['DESCRIPTION']
					);
				}
			}
		}

		$conn = Application::getConnection();
		$conn->startTransaction();
		$result = false;
		$internalError = '';
		try
		{
			$result = CCrmProduct::Update($ID, $fields);
			if ($result !== true)
			{
				$internalError = CCrmProduct::GetLastError();
				if ($internalError === '')
				{
					$internalError = 'Unable to update product.';
				}
			}
		}
		catch (SqlQueryException)
		{
			$internalError = 'Internal error updating product. Try updating again.';
		}
		if ($internalError === '')
		{
			$conn->commitTransaction();
		}
		else
		{
			$conn->rollbackTransaction();
			$errors[] = $internalError;
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!(CCrmProduct::CheckDeletePermission($ID) && CCrmProduct::EnsureDefaultCatalogScope($ID)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$conn = Application::getConnection();
		$conn->startTransaction();
		$result = false;
		$internalError = '';
		try
		{
			$result = CCrmProduct::Delete($ID);
			if ($result !== true)
			{
				$internalError = CCrmProduct::GetLastError();
				if ($internalError === '')
				{
					$internalError = 'Unable to delete product.';
				}
			}
		}
		catch (SqlQueryException)
		{
			$internalError = 'Internal error deleting product. Try deleting again.';
		}
		if ($internalError === '')
		{
			$conn->commitTransaction();
		}
		else
		{
			$conn->rollbackTransaction();
			$errors[] = $internalError;
		}

		return $result;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmProductRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmProductAdd'] = self::createEventInfo('catalog', 'OnProductAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductUpdate'] = self::createEventInfo('crm', 'OnAfterCrmProductUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductDelete'] = self::createEventInfo('iblock', 'OnAfterIBlockElementDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmproductadd':
			case 'oncrmproductupdate':
				{
					$ID = isset($arParams[0])? (int)$arParams[0] : 0;

					if($ID <= 0)
					{
						throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
					}

					$fields = CCrmProduct::GetByID($ID);
					$catalogID = is_array($fields) && isset($fields['CATALOG_ID'])? (int)$fields['CATALOG_ID'] : 0;
					if($catalogID !== CCrmCatalog::GetDefaultID())
					{
						throw new RestException("Outside CRM product event is detected");
					}
					return array('FIELDS' => array('ID' => $ID));
				}
				break;
			case 'oncrmproductdelete':
				{
					$fields = isset($arParams[0]) && is_array($arParams[0])? $arParams[0] : array();
					$ID = isset($fields['ID'])? (int)$fields['ID'] : 0;

					if($ID <= 0)
					{
						throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
					}

					$catalogID = isset($fields['IBLOCK_ID'])? (int)$fields['IBLOCK_ID'] : 0;
					if($catalogID !== CCrmCatalog::GetDefaultID())
					{
						throw new RestException("Outside CRM product event is detected");
					}
					return array('FIELDS' => array('ID' => $ID));
				}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}

class CCrmProductPropertyRestProxy extends CCrmRestProxyBase
{
	private $TYPES_INFO = null;
	private $FIELDS_INFO = null;
	private $SETTINGS_FIELDS_INFO = null;
	private $ENUMERATION_FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'IBLOCK_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'XML_ID' => array(
					'TYPE' => 'string'
				),
				'CODE' => array(
					'TYPE' => 'string'
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'ACTIVE' => array(
					'TYPE' => 'char'
				),
				'IS_REQUIRED' => array(
					'TYPE' => 'char'
				),
				'SORT' => array(
					'TYPE' => 'integer'
				),
				'PROPERTY_TYPE' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required, CCrmFieldInfoAttr::Immutable)
				),
				'MULTIPLE' => array(
					'TYPE' => 'char'
				),
				'DEFAULT_VALUE' => array(
					'TYPE' => 'object'
				),
				'ROW_COUNT' => array(
					'TYPE' => 'integer'
				),
				'COL_COUNT' => array(
					'TYPE' => 'integer'
				),
				'FILE_TYPE' => array(
					'TYPE' => 'string'
				),
				'LINK_IBLOCK_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'USER_TYPE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'USER_TYPE_SETTINGS' => array(
					'TYPE' => 'object'
				),
				'VALUES' => array(
					'TYPE' => 'product_property_enum_element',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				)
			);

			foreach ($this->FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = $this->getFieldCaption($code);
			}
		}

		return $this->FIELDS_INFO;
	}

	protected function getSettingsFieldsInfo($propertyType, $userType)
	{
		$fieldsInfo = array();

		if(!$this->SETTINGS_FIELDS_INFO)
		{
			$this->SETTINGS_FIELDS_INFO = array(
				'S' => array(
					'HTML' => array(
						'HEIGHT' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 200*/
						)
					)
				),
				'E' => array(
					'Elist' => array(
						'SIZE' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 1*/
						),
						'WIDTH' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 0*/
						),
						'GROUP' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						),
						'MULTIPLE' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						)
					)
				),
				'N' => array(
					'Sequence' => array(
						'WRITE' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						),
						'CURRENT_VALUE' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => '1'*/
						)
					)
				),
			);
		}

		if (isset($this->SETTINGS_FIELDS_INFO[$propertyType])
			&& isset($this->SETTINGS_FIELDS_INFO[$propertyType][$userType]))
		{
			$fieldsInfo = $this->SETTINGS_FIELDS_INFO[$propertyType][$userType];
		}

		return self::prepareFields($fieldsInfo);
	}
	protected function getFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_REST_PRODUCT_PROPERTY_FIELD_".$fieldName);
		return is_string($result) ? $result : '';
	}
	protected function getEnumerationFieldsInfo()
	{
		if(!$this->ENUMERATION_FIELDS_INFO)
		{
			$this->ENUMERATION_FIELDS_INFO = array(
				'ID' => array('TYPE' => 'integer'),
				'VALUE' => array('TYPE' => 'string'),
				'XML_ID' => array('TYPE' => 'string'),
				'SORT' => array('TYPE' => 'integer'),
				'DEF' => array('TYPE' => 'char')
			);
			foreach ($this->ENUMERATION_FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = $this->getFieldCaption($code);
			}
		}

		return self::prepareFields($this->ENUMERATION_FIELDS_INFO);
	}

	protected function getTypesInfo()
	{
		$typesInfo = array();

		if(!$this->TYPES_INFO)
		{
			if(!CModule::IncludeModule('iblock'))
			{
				throw new RestException('Could not load iblock module.');
			}

			$descriptions = CCrmProductPropsHelper::GetPropsTypesDescriptions();
			$typesInfo = array(
				array('PROPERTY_TYPE' => 'S', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['S']),
				array('PROPERTY_TYPE' => 'N', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['N']),
				array('PROPERTY_TYPE' => 'L', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['L']),
				array('PROPERTY_TYPE' => 'F', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['F']),
				/*array('PROPERTY_TYPE' => 'G', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['G']),*/
				array('PROPERTY_TYPE' => 'E', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['E'])
			);
			$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
			if (is_array($userTypes))
			{
				foreach ($userTypes as $propertyInfo)
				{
					$typesInfo[] = array(
						'PROPERTY_TYPE' => $propertyInfo['PROPERTY_TYPE'],
						'USER_TYPE' => $propertyInfo['USER_TYPE'],
						'DESCRIPTION' => $propertyInfo['DESCRIPTION']
					);
				}
			}

			$this->TYPES_INFO = $typesInfo;
		}

		return $this->TYPES_INFO;
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!Loader::includeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = (int)CCrmCatalog::EnsureDefaultExists();

		if (!isset($fields['PROPERTY_TYPE']))
		{
			$fields['PROPERTY_TYPE'] = Iblock\PropertyTable::TYPE_STRING;
		}

		$userTypeSettings = [];
		if (isset($fields['USER_TYPE_SETTINGS']) && is_array($fields['USER_TYPE_SETTINGS']))
			foreach ($fields['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[mb_strtolower($key)] = $value;

		$arFields = [
			'ACTIVE' => isset($fields['ACTIVE']) ? ($fields['ACTIVE'] === 'Y' ? 'Y' : 'N') : 'Y',
			'IBLOCK_ID' => $iblockId,
			'PROPERTY_TYPE' => $fields['PROPERTY_TYPE'],
			'USER_TYPE' => $fields['USER_TYPE'] ?? '',
			'LINK_IBLOCK_ID' => $fields['PROPERTY_TYPE'] === Iblock\PropertyTable::TYPE_ELEMENT ? $iblockId : 0,
			'NAME' => $fields['NAME'],
			'SORT' => (int)$fields['SORT'] ?? 500,
			'CODE' => $fields['CODE'] ?? '',
			'MULTIPLE' => isset($fields['MULTIPLE']) ? ($fields['MULTIPLE'] === 'Y' ? 'Y' : 'N') : 'N',
			'IS_REQUIRED' => isset($fields['IS_REQUIRED']) ? ($fields['IS_REQUIRED'] === 'Y' ? 'Y' : 'N') : 'N',
			'SEARCHABLE' => 'N',
			'FILTRABLE' => 'N',
			'WITH_DESCRIPTION' => '',
			'MULTIPLE_CNT' => $fields['MULTIPLE_CNT'] ?? 0,
			'HINT' => '',
			'ROW_COUNT' => $fields['ROW_COUNT'] ?? 1,
			'COL_COUNT' => $fields['COL_COUNT'] ?? 30,
			'DEFAULT_VALUE' => $fields['DEFAULT_VALUE'] ?? null,
			'LIST_TYPE' => 'L',
			'USER_TYPE_SETTINGS' => $userTypeSettings,
			'FILE_TYPE' => $fields['FILE_TYPE'] ?? '',
			'XML_ID' => $fields['XML_ID'] ?? '',
		];

		if ($arFields['PROPERTY_TYPE'].':'.$arFields['USER_TYPE'] === 'S:map_yandex')
		{
			$arFields['MULTIPLE'] = 'N';
		}

		if (
			$fields['PROPERTY_TYPE'] === Iblock\PropertyTable::TYPE_LIST
			&& isset($fields['VALUES'])
			&& is_array($fields['VALUES'])
		)
		{
			$values = array();

			$newKey = 0;
			foreach ($fields['VALUES'] as $key => $value)
			{
				if (!is_array($value) || !isset($value['VALUE']) || trim($value['VALUE']) === '')
				{
					continue;
				}
				$valueId = (int)$key;
				if ($valueId <= 0)
				{
					$valueId = 'n'.$newKey;
					$newKey++;
				}
				$values[$valueId] = [
					'ID' => $valueId,
					'VALUE' => (string)$value['VALUE'],
					'XML_ID' => $value['XML_ID'] ?? '',
					'SORT' => (int)$value['SORT'] ?? 500,
					'DEF' => (isset($value['DEF']) ? ($value['DEF'] === 'Y' ? 'Y' : 'N') : 'N'),
				];
			}

			$arFields['VALUES'] = $values;
		}

		$property = new CIBlockProperty;
		$result = $property->Add($arFields);

		if ((int)$result <= 0)
		{
			if (!empty($property->LAST_ERROR))
			{
				$errors[] = $property->LAST_ERROR;
			}
			else if ($e = $APPLICATION->GetException())
			{
				$errors[] = $e->GetString();
			}
		}

		return $result;
	}

	protected function innerGet($id, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = false;
		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		if (is_object($res))
			$result = $res->Fetch();
		unset($res);
		if(!is_array($result)
			|| (isset($result['USER_TYPE']) && !empty($result['USER_TYPE'])
				&& !array_key_exists($result['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		$values = null;
		if ($result['PROPERTY_TYPE'] === 'L')
		{
			$values = array();
			$resEnum = CIBlockProperty::GetPropertyEnum($result['ID'], array('SORT' => 'ASC','ID' => 'ASC'));
			while($enumValue = $resEnum->Fetch())
			{
				$values[intval($enumValue['ID'])] = array(
					'ID' => $enumValue['ID'],
					'VALUE' => $enumValue['VALUE'],
					'XML_ID' => $enumValue['XML_ID'],
					'SORT' => $enumValue['SORT'],
					'DEF' => $enumValue['DEF']
				);
			}
		}
		$result['VALUES'] = $values;

		$userTypeSettings = array();
		if (isset($result['USER_TYPE_SETTINGS']) && is_array($result['USER_TYPE_SETTINGS']))
		{
			foreach ($result['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[mb_strtoupper($key)] = $value;
			$result['USER_TYPE_SETTINGS'] = $userTypeSettings;
		}

		return $result;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');

		$filter['IBLOCK_ID'] = intval(CCrmCatalog::EnsureDefaultExists());
		$filter['CHECK_PERMISSIONS'] = 'N';
		$res = CIBlockProperty::GetList($order, $filter);
		$result = array();
		while ($row = $res->Fetch())
		{
			if ($row['PROPERTY_TYPE'] !== 'G'
				&& ($row['USER_TYPE'] == '' || array_key_exists($row['USER_TYPE'], $userTypes)))
			{
				$values = null;
				if ($row['PROPERTY_TYPE'] === 'L')
				{
					$values = array();
					$resEnum = CIBlockProperty::GetPropertyEnum($row['ID'], array('SORT' => 'ASC','ID' => 'ASC'));
					while($enumValue = $resEnum->Fetch())
					{
						$values[intval($enumValue['ID'])] = array(
							'ID' => $enumValue['ID'],
							'VALUE' => $enumValue['VALUE'],
							'XML_ID' => $enumValue['XML_ID'],
							'SORT' => $enumValue['SORT'],
							'DEF' => $enumValue['DEF']
						);
					}
				}
				$row['VALUES'] = $values;
				$result[] = $row;
			}
		}

		return $result;
	}

	protected function innerUpdate($id, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		$prop = false;
		if (is_object($res))
			$prop = $res->Fetch();
		unset($res);
		if(!is_array($prop)
			|| (isset($prop['USER_TYPE']) && !empty($prop['USER_TYPE'])
				&& !array_key_exists($prop['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		$fields['IBLOCK_ID'] = $iblockId;
		$fields['PROPERTY_TYPE'] = $prop['PROPERTY_TYPE'];
		$fields['USER_TYPE'] = $prop['USER_TYPE'];

		if (isset($fields['USER_TYPE_SETTINGS']) && is_array($fields['USER_TYPE_SETTINGS']))
		{
			$userTypeSettings = array();
			foreach ($fields['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[mb_strtolower($key)] = $value;
			$fields['USER_TYPE_SETTINGS'] = $userTypeSettings;
			unset($userTypeSettings);
		}

		if ($prop['PROPERTY_TYPE'] === 'L' && isset($fields['VALUES']) && is_array($fields['VALUES']))
		{
			$values = array();

			$newKey = 0;
			foreach ($fields['VALUES'] as $key => $value)
			{
				if (!is_array($value) || !isset($value['VALUE']) || '' == trim($value['VALUE']))
					continue;
				$values[(0 < intval($key) ? $key : 'n'.$newKey)] = array(
					'ID' => (0 < intval($key) ? $key : 'n'.$newKey),
					'VALUE' => strval($value['VALUE']),
					'XML_ID' => (isset($value['XML_ID']) ? strval($value['XML_ID']) : ''),
					'SORT' => (isset($value['SORT']) ? intval($value['SORT']) : 500),
					'DEF' => (isset($value['DEF']) ? ($value['DEF'] === 'Y' ? 'Y' : 'N') : 'N')
				);
				$newKey++;
			}
			$fields['VALUES'] = $values;
			unset($values);
		}

		if ($fields['PROPERTY_TYPE'].':'.$fields['USER_TYPE'] === 'S:map_yandex'
			&& isset($fields['MULTIPLE']) && $fields['MULTIPLE'] !== 'N')
		{
			$fields['MULTIPLE'] = 'N';
		}

		$property = new CIBlockProperty;
		$result = $property->Update($id, $fields);

		if (!$result)
		{
			if (!empty($property->LAST_ERROR))
				$errors[] = $property->LAST_ERROR;
			else if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
		}

		return $result;
	}

	protected function innerDelete($id, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		$result = false;
		if (is_object($res))
			$result = $res->Fetch();
		unset($res);
		if(!is_array($result)
			|| (isset($result['USER_TYPE']) && !empty($result['USER_TYPE'])
				&& !array_key_exists($result['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		if(!CIBlockProperty::Delete($id))
		{
			if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on deleting product property.';
			return false;
		}

		return true;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'PROPERTY')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return self::getFields();
			}
			elseif($nameSuffix === 'TYPES')
			{
				return $this->getTypesInfo();
			}
			else if($nameSuffix === 'SETTINGS_FIELDS')
			{
				$propertyType = $userType = '';
				foreach ($arParams as $name => $value)
				{
					switch(mb_strtolower($name))
					{
						case 'propertytype':
							$propertyType = strval($value);
							break;
						case 'usertype':
							$userType = strval($value);
							break;
					}
				}
				if($propertyType === '')
				{
					throw new RestException("Parameter 'propertyType' is not specified or empty.");
				}
				if($userType === '')
				{
					throw new RestException("Parameter 'userType' is not specified or empty.");
				}

				return $this->getSettingsFieldsInfo($propertyType, $userType);
			}
			else if($nameSuffix === 'ENUMERATION_FIELDS')
			{
				return $this->getEnumerationFieldsInfo();
			}
			else if(in_array($nameSuffix, array('ADD', 'GET', 'LIST', 'UPDATE', 'DELETE'), true))
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmProductPropertyRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmProductPropertyAdd'] = self::createEventInfo('iblock', 'OnAfterIBlockPropertyAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductPropertyUpdate'] = self::createEventInfo('iblock', 'OnAfterIBlockPropertyUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductPropertyDelete'] = self::createEventInfo('iblock', 'OnAfterIBlockPropertyDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmproductpropertyadd':
			case 'oncrmproductpropertyupdate':
			case 'oncrmproductpropertydelete':
				{
					$fields = isset($arParams[0]) && is_array($arParams[0])? $arParams[0] : array();

					$id = isset($fields['ID'])? (int)$fields['ID'] : 0;
					if($id <= 0)
					{
						throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
					}

					$iblockId = isset($fields['IBLOCK_ID'])? (int)$fields['IBLOCK_ID'] : 0;
					if($iblockId <= 0)
					{
						throw new RestException("Could not find IBLOCK_ID in fields of event \"{$eventName}\"");
					}

					return array('FIELDS' => array('ID' => $id));
				}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}

class CCrmProductSectionRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductSection::GetFieldsInfo();
			foreach ($this->FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = CCrmProductSection::GetFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$conn = Application::getConnection();
		$conn->startTransaction();
		$internalError = '';
		$result = false;
		try
		{
			$result = CCrmProductSection::Add($fields);
			if (!(is_int($result) && $result > 0))
			{
				$internalError = CCrmProductSection::GetLastError();
			}
		}
		catch (SqlQueryException)
		{
			$internalError = 'Internal error adding product section. Try adding again.';
		}
		if ($internalError === '')
		{
			$conn->commitTransaction();
		}
		else
		{
			$conn->rollbackTransaction();
			$errors[] = $internalError;

		}

		return $result;
	}

	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Product section is not found.';
			return null;
		}

		return $result;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductSection::GetList($order, $filter, $select, $navigation);
	}

	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$conn = Application::getConnection();
		$conn->startTransaction();
		$internalError = '';
		$result = false;
		try
		{
			$result = CCrmProductSection::Update($ID, $fields);
			if (!$result)
			{
				$internalError = CCrmProductSection::GetLastError();
			}
		}
		catch (SqlQueryException)
		{
			$internalError = 'Internal error updating product section. Try updating again.';
		}
		if ($internalError === '')
		{
			$conn->commitTransaction();
		}
		else
		{
			$conn->rollbackTransaction();
			$errors[] = $internalError;
		}

		return $result;
	}

	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$conn = Application::getConnection();
		$conn->startTransaction();
		$internalError = '';
		$result = false;
		try
		{
			$result = CCrmProductSection::Delete($ID);
			if (!$result)
			{
				$internalError = CCrmProductSection::GetLastError();
			}
		}
		catch (SqlQueryException)
		{
			$internalError = 'Internal error deleting product section. Try deleting again.';
		}
		if ($internalError === '')
		{
			$conn->commitTransaction();
		}
		else
		{
			$conn->rollbackTransaction();
			$errors[] = $internalError;
		}

		return $result;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmProductSectionRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmProductSectionAdd'] = self::createEventInfo('iblock', 'OnAfterIBlockSectionAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductSectionUpdate'] = self::createEventInfo('iblock', 'OnAfterIBlockSectionUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductSectionDelete'] = self::createEventInfo('iblock', 'OnAfterIBlockSectionDelete', $callback);
	}

	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmproductsectionadd':
			case 'oncrmproductsectionupdate':
			case 'oncrmproductsectiondelete':
				$fields = isset($arParams[0]) && is_array($arParams[0])? $arParams[0] : array();

				$id = isset($fields['ID'])? (int)$fields['ID'] : 0;
				if($id <= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}

				$iblockId = isset($fields['IBLOCK_ID'])? (int)$fields['IBLOCK_ID'] : 0;
				if($iblockId <= 0)
				{
					throw new RestException("Could not find IBLOCK_ID in fields of event \"{$eventName}\"");
				}

				if($iblockId !== CCrmCatalog::GetDefaultID())
				{
					throw new RestException("Outside CRM product property event is detected");
				}
				return array('FIELDS' => array('ID' => $id));

				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}

class CCrmProductRowRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductRow::GetFieldsInfo();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = CCrmProductRow::GetFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		$ownerType = $fields['OWNER_TYPE'] ?? '';

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required.';
			}
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckCreatePermission(
			CCrmProductRow::ResolveOwnerTypeName($ownerType)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Add($fields, true, true);
		if(!is_int($result))
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		$result = CCrmProductRow::GetByID($ID, array('EXTENDED_FIELDS' => true));
		if(!is_array($result))
		{
			$errors[] = "Product Row not found";
		}

		if(!CCrmAuthorizationHelper::CheckReadPermission(
			CCrmProductRow::ResolveOwnerTypeName($result['OWNER_TYPE']),
			$result['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$ownerID = $filter['OWNER_ID'] ?? 0;
		$ownerType = $filter['OWNER_TYPE'] ?? '';
		$ownerTypeName = CCrmProductRow::ResolveOwnerTypeName($ownerType);
		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required in filer.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required in filer.';
			}
			return false;
		}

		if($ownerType === 'I')
		{
			//Crutch for Invoices
			if(!CCrmInvoice::CheckReadPermission($ownerID))
			{
				$errors[] = 'Access denied.';
				return false;
			}

			$result = array();
			$productRows = CCrmInvoice::GetProductRows($ownerID);
			foreach($productRows as $productRow)
			{
				$price = isset($productRow['PRICE']) ? round((double)$productRow['PRICE'], 2) : 0.0;
				$discountSum = isset($productRow['DISCOUNT_PRICE']) ?
					round((double)$productRow['DISCOUNT_PRICE'], 2) : 0.0;
				$vatRate = isset($productRow['VAT_RATE']) ? (double)$productRow['VAT_RATE'] * 100 : 0.0;
				$taxRate = isset($productRow['VAT_RATE']) ? round((double)$productRow['VAT_RATE'] * 100, 2) : 0.0;

				if(isset($productRow['VAT_INCLUDED']) && $productRow['VAT_INCLUDED'] === 'N')
				{
					$exclusivePrice = $price;
					$price = round(CCrmProductRow::CalculateInclusivePrice($exclusivePrice, $vatRate), 2);
				}
				else
				{
					$exclusivePrice = round(CCrmProductRow::CalculateExclusivePrice($price, $vatRate), 2);
				}
				unset($vatRate);

				$discountRate = \Bitrix\Crm\Discount::calculateDiscountRate(($exclusivePrice + $discountSum), $exclusivePrice);

				$result[] = array(
					'ID' => $productRow['ID'],
					'OWNER_ID' => $productRow['ORDER_ID'],
					'OWNER_TYPE' => 'I',
					'PRODUCT_ID' => $productRow['PRODUCT_ID'] ?? 0,
					'PRODUCT_NAME' => $productRow['PRODUCT_NAME'] ?? '',
					'PRICE' => $price,
					'QUANTITY' => $productRow['QUANTITY'] ?? 0,
					'DISCOUNT_TYPE_ID' => \Bitrix\Crm\Discount::MONETARY,
					'DISCOUNT_RATE' => $discountRate,
					'DISCOUNT_SUM' => $discountSum,
					'TAX_RATE' => $taxRate,
					'TAX_INCLUDED' => $productRow['VAT_INCLUDED'] ?? 'N',
					'MEASURE_CODE' => $productRow['MEASURE_CODE'] ?? '',
					'MEASURE_NAME' => $productRow['MEASURE_NAME'] ?? '',
					'CUSTOMIZED' => $productRow['CUSTOM_PRICE'] ?? 'N',
				);
			}
			return $result;
		}

		if(!EntityAuthorization::checkReadPermission($ownerTypeID, $ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductRow::GetList(
			$order, $filter, false, $navigation, $select,
			array('IS_EXTERNAL_CONTEXT' => true, 'EXTENDED_FIELDS' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row is not found";
			return false;
		}

		$ownerTypeName = CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']);
		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
		$ownerID = (int)$entity['OWNER_ID'];

		if(!EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		// The fields OWNER_ID and OWNER_TYPE can not be changed.
		if(isset($fields['OWNER_ID']))
		{
			unset($fields['OWNER_ID']);
		}

		if(isset($fields['OWNER_TYPE']))
		{
			unset($fields['OWNER_TYPE']);
		}

		$result = CCrmProductRow::Update($ID, $fields, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row is not found";
			return false;
		}

		$ownerTypeName = CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']);
		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
		$ownerID = (int)$entity['OWNER_ID'];

		if(!EntityAuthorization::checkDeletePermission($ownerTypeID, $ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Delete($ID, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}

	public function prepareForSave(&$fields)
	{
		$fieldsInfo = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldsInfo);

		$productId = (int)($fields['PRODUCT_ID'] ?? null);
		if ($productId && Loader::includeModule('catalog'))
		{
			$productRow = Catalog\ProductTable::getRow([
				'select' => [
					'PRODUCT_NAME' => 'IBLOCK_ELEMENT.NAME',
					'TYPE',
				],
				'filter' => [
					'=ID' => $productId,
				],
			]);
			if ($productRow)
			{
				$fields['TYPE'] = (int)$productRow['TYPE'];
				$fields['PRODUCT_NAME'] ??= $productRow['PRODUCT_NAME'];
			}
		}

		if (isset($fields['TAX_RATE']))
		{
			if (
				(float)$fields['TAX_RATE'] > 0
				|| $fields['TAX_RATE'] === 0
				|| (
					is_string($fields['TAX_RATE'])
					&& isset($fields['TAX_RATE'][0])
					&& $fields['TAX_RATE'][0] === '0'
				)
			)
			{
				$fields['TAX_RATE'] = (float)$fields['TAX_RATE'];
			}
			else
			{
				$fields['TAX_RATE'] = null;
			}
		}
	}
}

class CCrmLeadRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Lead;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmLead(true);
		}

		return self::$ENTITY;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmLead::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = CCrmLead::GetFieldCaption($code);
			}
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmLead::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$isImportMode = is_array($params) && isset($params['IMPORT']) && $params['IMPORT'];

		if(!($isImportMode ? CCrmLead::CheckImportPermission() : CCrmLead::CheckCreatePermission()))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		$entity = self::getEntity();
		$options = [];
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		if($isImportMode)
		{
			$options['ALLOW_SET_SYSTEM_FIELDS'] = true;
			$fields['PERMISSION'] = 'IMPORT';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			self::traceEntity(\CCrmOwnerType::Lead, $result, $fields);
			if (self::isBizProcEnabled() && !$isImportMode)
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Lead,
					$result,
					CCrmBizProcEventType::Create,
					$errors
				);
			}
			//Region automation
			$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $result);
			$starter->setContextToRest()->setUserId($this->getCurrentUserID())->runOnAdd();
			//End region
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmLead::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmLead::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToRead(
			\CCrmOwnerType::Lead,
			$ID,
			$result,
		);

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmLead::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = $ufData['VALUE'] ?? '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmLead::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STATUS_ID']))
			{
				$order['STATUS_SORT'] = $order['STATUS_ID'];
				unset($order['STATUS_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STATUS_SORT'));
			}
		}

		return CCrmLead::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmLead::Exists($ID))
		{
			$errors[] = 'Lead is not found';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = mb_strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		//check STATUS_ID changes
		$arPresentFields = [];
		if (isset($fields['STATUS_ID']))
		{
			$dbDocumentList = CCrmLead::GetListEx(
				array(),
				array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STATUS_ID')
			);
			$arPresentFields = $dbDocumentList->Fetch();
			if (!is_array($arPresentFields))
			{
				$arPresentFields = [];
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			self::traceEntity(\CCrmOwnerType::Lead, $ID, $fields, true);
			if(self::isBizProcEnabled())
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Lead,
					$ID,
					CCrmBizProcEventType::Edit,
					$errors
				);
			}
			//Region automation
			$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $ID);
			$starter->setContextToRest()->setUserId($this->getCurrentUserID());
			$starter->runOnUpdate($fields, $arPresentFields);
			//End region
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID, array('CHECK_DEPENDENCIES' => true));
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmLead::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmLead::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		global $APPLICATION;

		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmLead::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$rows = array_values($rows);
		$actualRows = array();
		for($i = 0, $qty = count($rows); $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		$result = CCrmLead::SaveProductRows($ID, $actualRows, true, true, true);
		if(!$result)
		{
			$exp = $APPLICATION->GetException();
			if($exp)
			{
				throw new RestException($exp->GetString());
			}
		}
		return $result;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		elseif($name === 'CONTACT')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Lead, CCrmOwnerType::Contact);

			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmLeadRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmLeadAdd'] = self::createEventInfo('crm', 'OnAfterCrmLeadAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadUpdate'] = self::createEventInfo('crm', 'OnAfterCrmLeadUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadDelete'] = self::createEventInfo('crm', 'OnAfterCrmLeadDelete', $callback);

		// user field events
		$bindings[CRestUtil::EVENTS]['onCrmLeadUserFieldAdd'] = self::createEventInfo('crm', 'OnAfterCrmRestLeadUserFieldAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadUserFieldUpdate'] = self::createEventInfo('crm', 'OnAfterCrmRestLeadUserFieldUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadUserFieldDelete'] = self::createEventInfo('crm', 'OnAfterCrmRestLeadUserFieldDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadUserFieldSetEnumValues'] = self::createEventInfo('crm', 'OnAfterCrmRestLeadUserFieldSetEnumValues', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Lead, $arParams, $arHandler);
	}
}

class CCrmDealRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Deal;
	}
	private static function getEntity($checkPermissions = true)
	{
		return new CCrmDeal($checkPermissions);
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmDeal::GetFieldsInfo();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = CCrmDeal::GetFieldCaption($code);
			}
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmDeal::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$categoryID = isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0;
		$fields['CATEGORY_ID'] = $categoryID = max($categoryID, 0);
		if(!CCrmDeal::CheckCreatePermission(\CCrmPerms::GetCurrentUserPermissions(), $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		$defaultRequisiteLinkParams = Requisite\EntityLink::determineRequisiteLinkBeforeSave(
			CCrmOwnerType::Deal, 0, Requisite\EntityLink::ENTITY_OPERATION_ADD, $fields
		);

		$entity = self::getEntity(false);
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			Requisite\EntityLink::register(
				CCrmOwnerType::Deal, (int)$result,
				$defaultRequisiteLinkParams['REQUISITE_ID'],
				$defaultRequisiteLinkParams['BANK_DETAIL_ID']
			);

			self::traceEntity(\CCrmOwnerType::Deal, $result, $fields);
			if (self::isBizProcEnabled())
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Deal,
					$result,
					CCrmBizProcEventType::Create,
					$errors
				);
			}
			//Region automation
			$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $result);
			$starter->setContextToRest()->setUserId($this->getCurrentUserID())->runOnAdd();
			//End region
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			$errors[] = !CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found';
			return false;
		}
		elseif(!CCrmDeal::CheckReadPermission($ID, CCrmPerms::GetCurrentUserPermissions(), $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmDeal::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmDeal::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = $ufData['VALUE'] ?? '';
		}
		unset($ufData);

		$result = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToRead(
			\CCrmOwnerType::Deal,
			$ID,
			$result,
		);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STAGE_ID']))
			{
				$order['STAGE_SORT'] = $order['STAGE_ID'];
				unset($order['STAGE_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STAGE_SORT'));
			}
		}

		return CCrmDeal::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			$errors[] = !CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied' : 'Not found';
			return false;
		}
		elseif(!CCrmDeal::CheckUpdatePermission($ID, $userPermissions, $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		$entity = self::getEntity(false);
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = mb_strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		//check STAGE_ID changes
		$arPresentFields = [];
		if (isset($fields['STAGE_ID']))
		{
			$dbDocumentList = CCrmDeal::GetListEx(
				array(),
				array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STAGE_ID')
			);
			$arPresentFields = $dbDocumentList->Fetch();
			if (!is_array($arPresentFields))
			{
				$arPresentFields = [];
			}
		}

		$defaultRequisiteLinkParams = Requisite\EntityLink::determineRequisiteLinkBeforeSave(
			CCrmOwnerType::Deal, $ID, Requisite\EntityLink::ENTITY_OPERATION_UPDATE, $fields
		);

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			Requisite\EntityLink::register(
				CCrmOwnerType::Deal, (int)$ID,
				$defaultRequisiteLinkParams['REQUISITE_ID'],
				$defaultRequisiteLinkParams['BANK_DETAIL_ID']
			);

			self::traceEntity(\CCrmOwnerType::Deal, $ID, $fields, true);
			if(self::isBizProcEnabled())
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Deal,
					$ID,
					CCrmBizProcEventType::Edit,
					$errors
				);
			}
			//Region automation
			$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $ID);
			$starter->setContextToRest()->setUserId($this->getCurrentUserID());
			$starter->runOnUpdate($fields, $arPresentFields);
			//End region
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			$errors[] = !CCrmDeal::CheckDeletePermission(0, $userPermissions) ? 'Access denied' : 'Not found';
			return false;
		}
		elseif(!CCrmDeal::CheckDeletePermission($ID, $userPermissions, $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity(false);
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			throw new RestException(
				!CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found'
			);
		}
		elseif(!CCrmDeal::CheckReadPermission($ID, $userPermissions, $categoryID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmDeal::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		global $APPLICATION;

		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			throw new RestException(
				!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied' : 'Not found'
			);
		}
		elseif(!CCrmDeal::CheckUpdatePermission($ID, $userPermissions, $categoryID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmDeal::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$rows = array_values($rows);
		$actualRows = array();
		for($i = 0, $qty = count($rows); $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		$result = CCrmDeal::SaveProductRows($ID, $actualRows, true, true, true);
		if(!$result)
		{
			$exp = $APPLICATION->GetException();
			if($exp)
			{
				throw new RestException($exp->GetString());
			}
		}
		return $result;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
		if($name === 'PRODUCTROWS')
		{
			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		elseif($name === 'CONTACT')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Deal, CCrmOwnerType::Contact);
			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmDealRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmDealAdd'] = self::createEventInfo('crm', 'OnAfterCrmDealAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealUpdate'] = self::createEventInfo('crm', 'OnAfterCrmDealUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealDelete'] = self::createEventInfo('crm', 'OnAfterCrmDealDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealMoveToCategory'] = self::createEventInfo(
			'crm',
			'OnAfterDealMoveToCategory',
			[
				'CCrmDealRestProxy',
				'processMoveToCategoryEvent',
			]
		);

		// user field events
		$bindings[CRestUtil::EVENTS]['onCrmDealUserFieldAdd'] = self::createEventInfo('crm', 'OnAfterCrmRestDealUserFieldAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealUserFieldUpdate'] = self::createEventInfo('crm', 'OnAfterCrmRestDealUserFieldUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealUserFieldDelete'] = self::createEventInfo('crm', 'OnAfterCrmRestDealUserFieldDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealUserFieldSetEnumValues'] = self::createEventInfo('crm', 'OnAfterCrmRestDealUserFieldSetEnumValues', $callback);
	}
	public static function processMoveToCategoryEvent(array $arParams, array $arHandler)
	{
		if (!isset($arParams[0]) || !($arParams[0] instanceof \Bitrix\Main\Event))
		{
			throw new RestException("Wrong event");
		}
		/** @var \Bitrix\Main\Event $event */
		$event = $arParams[0];

		return [
			'FIELDS' => [
				'ID' => $event->getParameter('id'),
				'CATEGORY_ID' => $event->getParameter('categoryId'),
				'STAGE_ID' => $event->getParameter('stageId'),
			]
		];
	}

	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Deal, $arParams, $arHandler);
	}
}

class CCrmDealCategoryProxy extends CCrmRestProxyBase
{
	protected $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = DealCategory::getFieldsInfo();
			foreach ($this->FIELDS_INFO as $code=>&$field)
			{
				$field['CAPTION'] = DealCategory::getFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		/** @var Main\DB\Result $dbResult */
		$dbResult = DealCategory::getList(array('filter' => array('=ID' => $ID)));
		$result = $dbResult->fetch();
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}
		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$params = array();
		if(is_array($order) && !empty($order))
		{
			$params['order'] = $order;
		}

		if(is_array($filter) && !empty($filter))
		{
			$params['filter'] = $filter;
		}

		if(is_array($select) && !empty($select))
		{
			$params['select'] = $select;
		}

		/** @var Main\DB\Result $dbResult */
		$dbResult = DealCategory::getList($params);
		$items = array();
		while($fields = $dbResult->fetch())
		{
			$items[] = $fields;
		}
		return $items;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			return DealCategory::add($fields);
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!DealCategory::exists($ID))
		{
			$errors[] = 'Not found.';
			return false;
		}

		try
		{
			DealCategory::update($ID, $fields);
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!DealCategory::exists($ID))
		{
			$errors[] = 'Not found.';
			return false;
		}

		try
		{
			DealCategory::delete($ID);
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	public function resolveStatusEntityID($ID)
	{
		return $ID > 0 ? DealCategory::convertToStatusEntityID($ID) : 'DEAL_STAGE';
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'DEFAULT')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return array('ID' => 0, 'NAME' => \Bitrix\Crm\Category\DealCategory::getDefaultCategoryName());
			}
			elseif($nameSuffix === 'SET')
			{
				$name = $this->resolveParam($arParams, 'name');
				if(is_string($name))
				{
					\Bitrix\Crm\Category\DealCategory::setDefaultCategoryName($name);
				}
				return true;
			}
		}
		elseif($name === 'STATUS')
		{
			return $this->resolveStatusEntityID(CCrmRestHelper::resolveEntityID($arParams));
		}
		elseif($name === 'STAGE')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'LIST')
			{
				$statusProxy = new CCrmStatusRestProxy();
				return $statusProxy->getEntityItems(
					$this->resolveStatusEntityID(CCrmRestHelper::resolveEntityID($arParams))
				);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmDealRecurringRestProxy extends CCrmRestProxyBase
{
	protected $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$restInstance = \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestDeal::getInstance();
			$fieldParameters = $restInstance->getFieldsInfo();
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DEAL_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'BASED_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTIVE' => array(
					'TYPE' => 'char'
				),
				'NEXT_EXECUTION' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'LAST_EXECUTION' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'COUNTER_REPEAT' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'START_DATE' => array(
					'TYPE' => 'date'
				),
				'CATEGORY_ID' => array(
					'TYPE' => 'char'
				),
				'IS_LIMIT' => array(
					'TYPE' => 'char'
				),
				'LIMIT_REPEAT' => array(
					'TYPE' => 'integer'
				),
				'LIMIT_DATE' => array(
					'TYPE' => 'date'
				),
				'PARAMS' => array(
					'TYPE' => 'recurring_params',
					'FIELDS' => $fieldParameters
				)
			);
			foreach ($this->FIELDS_INFO as $code=>&$field)
			{
				$field['CAPTION'] = \Bitrix\Crm\DealRecurTable::getFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGet($ID, &$errors)
	{
		$recurringInstance = \Bitrix\Crm\Recurring\Entity\Deal::getInstance();
		if (!$recurringInstance->isAllowedExpose())
		{
			$errors[] = 'Recurring is not allowed';
			return false;
		}

		$recurringDataRaw = $recurringInstance->getList([
			'filter' => ['ID' => (int)$ID],
			'limit' => 1
		]);

		$fields = $recurringDataRaw->fetch();

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (empty($fields) || (int)$fields['DEAL_ID'] <= 0)
		{
			$errors[] = 'Recurring deal is not found.';
			return false;
		}
		$categoryID = CCrmDeal::GetCategoryID($fields['DEAL_ID']);
		if($categoryID < 0)
		{
			if (!CCrmDeal::CheckReadPermission(0, $userPermissions))
			{
				$errors[] =  'Access denied';
				return false;
			}
		}
		elseif(!CCrmDeal::CheckReadPermission($ID, CCrmPerms::GetCurrentUserPermissions(), $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = array_intersect_key($fields, $this->getFieldsInfo());
		$params = $fields['PARAMS'];
		$formParamsMapper = \Bitrix\Crm\Recurring\Entity\Deal::getParameterMapper($params);
		$formParamsMapper->fillMap($params);
		$restParamsMapper = \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestDeal::getInstance();
		$restParamsMapper->convert($formParamsMapper);
		$result['PARAMS'] = $restParamsMapper->getFormattedMap();
		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$params = array();
		if(is_array($order) && !empty($order))
		{
			$fieldInfo = $this->getFieldsInfo();
			foreach ($order as $code => $direction)
			{
				if (!empty($fieldInfo[$code]) && $code !== 'PARAMS')
				{
					$params['order'][$code] = $direction;
				}
			}
		}

		unset($filter['PARAMS']);
		$params['filter'] = $filter;

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;

		$restParamsMapper = \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestDeal::getInstance();
		/** @var Main\DB\Result $dataRaw */
		$dataRaw = \Bitrix\Crm\DealRecurTable::getList($params);
		$items = array();
		while($fields = $dataRaw->fetch())
		{
			$params = $fields['PARAMS'];
			$mapper = \Bitrix\Crm\Recurring\Entity\Deal::getParameterMapper($params);
			$mapper->fillMap($params);
			$restParamsMapper->convert($mapper);
			$fields['PARAMS'] = $restParamsMapper->getFormattedMap();
			$items[] = $fields;
		}

		$dbResult = new CDBResult();
		$dbResult->InitFromArray($items);
		$dbResult->NavStart($limit, false, $page);
		return $dbResult;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ID = null;
		$dealId = (int)$fields['DEAL_ID'];
		if ($dealId <= 0)
		{
			$errors[] = 'Deal ID is empty.';
			return false;
		}
		if(
			!CCrmDeal::CheckUpdatePermission($dealId, CCrmPerms::GetCurrentUserPermissions())
			|| !CCrmDeal::CheckCreatePermission(CCrmPerms::GetCurrentUserPermissions())
		)
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			if (
				$fields['IS_LIMIT'] !== \Bitrix\Crm\Recurring\Entity\Deal::LIMITED_BY_TIMES
				&& $fields['IS_LIMIT'] !== \Bitrix\Crm\Recurring\Entity\Deal::LIMITED_BY_DATE
			)
			{
				$fields['IS_LIMIT'] = \Bitrix\Crm\Recurring\Entity\Deal::NO_LIMITED;
			}
			if (!empty($fields['PARAMS']) && is_array($fields['PARAMS']))
			{
				$fields['PARAMS'] = $this->prepareParams($fields);
			}
			else
			{
				$fields['PARAMS'] = [];
			}
			if(!empty($fields['START_DATE']))
			{
				$fields['START_DATE'] = new \Bitrix\Main\Type\Date($fields['START_DATE']);
			}
			if(!empty($fields['LIMIT_DATE']))
			{
				$fields['LIMIT_DATE'] = new \Bitrix\Main\Type\Date($fields['LIMIT_DATE']);
			}
			$newRecurringFields = $fields;

			$dealRecurringInstance = \Bitrix\Crm\Recurring\Entity\Deal::getInstance();
			$dealFields = \CCrmDeal::GetByID($dealId, false);
			if ($dealFields['IS_RECURRING'] === 'Y')
			{
				$recurringRawSearch = $dealRecurringInstance->getList([
					'filter' => ['DEAL_ID' => $dealId],
					'limit' => 1
				]);
				if ($recurringRawSearch->fetch())
				{
					$errors[] = 'Deal already have had recurring settings.';
					return false;
				}

				$result = $dealRecurringInstance->add($newRecurringFields);
				if ($result->isSuccess())
				{
					$ID = $result->getId();
				}
			}
			else
			{
				unset($newRecurringFields['DEAL_ID']);
				$dealUserType = new \CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmDeal::GetUserFieldEntityID());
				$userFields = $dealUserType->GetEntityFields($dealId);
				foreach ($userFields as $key => $ufField)
				{
					$dealFields[$key] = $ufField['VALUE'];
				}
				$result = $dealRecurringInstance->createEntity($dealFields, $newRecurringFields);
				if ($result->isSuccess())
				{
					$data = $result->getData();
					$ID = $data['ID'];
				}
			}

			if (!$result->isSuccess())
			{
				$errors = $result->getErrorMessages();
				return false;
			}

			return $ID;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$result = $this->innerGet($ID, $errors);
		if (!$result)
		{
			return false;
		}
		elseif(!CCrmDeal::CheckUpdatePermission($result['DEAL_ID'], CCrmPerms::GetCurrentUserPermissions()))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (
			!empty($fields['PARAMS']) && is_array($fields['PARAMS'])
			|| !empty($fields['START_DATE'])
			|| !empty($fields['LIMIT_DATE'])
			|| !empty($fields['IS_LIMIT'])
		)
		{
			$merged = array_merge($result, $fields);
			$fields['PARAMS'] = $this->prepareParams($merged);
		}

		if(!empty($fields['START_DATE']))
		{
			$fields['START_DATE'] = new \Bitrix\Main\Type\Date($fields['START_DATE']);
		}
		if(!empty($fields['LIMIT_DATE']))
		{
			$fields['LIMIT_DATE'] = new \Bitrix\Main\Type\Date($fields['LIMIT_DATE']);
		}

		try
		{
			$dealRecurring = \Bitrix\Crm\Recurring\Entity\Deal::getInstance();
			$r = $dealRecurring->update($ID, $fields);
			if (!$r->isSuccess())
			{
				$errors = $r->getErrorMessages();
				return false;
			}
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$recurringInstance = \Bitrix\Crm\Recurring\Entity\Deal::getInstance();
		if (!$recurringInstance->isAllowedExpose())
		{
			$errors[] = 'Recurring is not allowed';
			return false;
		}

		$recurringDataRaw = $recurringInstance->getList([
			'filter' => ['ID' => (int)$ID],
			'limit' => 1
		]);

		if (!($recurringDataRaw->fetch()))
		{
			$errors[] = 'Recurring deal is not found.';
			return false;
		}
		elseif(!CCrmDeal::CheckDeletePermission(0, CCrmPerms::GetCurrentUserPermissions()))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			$dealRecurring = \Bitrix\Crm\Recurring\Entity\Deal::getInstance();
			$r = $dealRecurring->delete($ID);
			if (!$r->isSuccess())
			{
				$errors = $r->getErrorMessages();
				return false;
			}
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	public function expose($ID)
	{
		$ID = (int)$ID;
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$errors = array();
		$fields = $this->innerGet($ID, $errors);
		if(!is_array($fields))
		{
			throw new RestException(implode("\n", $errors));
		}

		$categoryId = -1;
		if ((int)$fields['CATEGORY_ID'] >= 0)
		{
			$categoryId = (int)$fields['CATEGORY_ID'];
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (
			!CCrmDeal::CheckReadPermission($fields['DEAL_ID'], $userPermissions)
			|| !CCrmDeal::CheckCreatePermission($userPermissions, $categoryId)
		)
		{
			throw new RestException(implode("\n", ['Access denied.']));
		}

		$dealRecurring = \Bitrix\Crm\Recurring\Entity\Deal::getInstance();
		$result = $dealRecurring->expose(['=ID' => $ID], 1, false);
		if (!$result->isSuccess())
		{
			throw new RestException(implode("\n", $result->getErrorMessages()));
		}

		$exposeData = $result->getData();
		return ['DEAL_ID' => $exposeData['ID'][0]];
	}
	protected function prepareParams(array $fields)
	{
		$restParamsMapper = new \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestDeal();
		$restParamsMapper->fillMap($fields['PARAMS']);
		$formParamsMapper = \Bitrix\Crm\Recurring\Entity\Deal::getParameterMapper();
		$formParamsMapper->convert($restParamsMapper);
		$params = $formParamsMapper->getFormattedMap();
		$params['MULTIPLE_TYPE_LIMIT'] = $fields['IS_LIMIT'];
		$params['MULTIPLE_TIMES_LIMIT'] = (int)$fields['LIMIT_REPEAT'];
		if(!empty($fields['START_DATE']))
		{
			$params['MULTIPLE_DATE_START'] = $fields['START_DATE'];
			$params['SINGLE_DATE_BEFORE'] = $fields['START_DATE'];
		}
		if(!empty($fields['LIMIT_DATE']))
		{
			$params['MULTIPLE_DATE_LIMIT'] = $fields['LIMIT_DATE'];
		}

		return $params;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'RECURRING')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			switch ($nameSuffix)
			{
				case 'EXPOSE':
					return $this->expose($this->resolveEntityID($arParams));
				case 'FIELDS':
				case 'ADD':
				case 'GET':
				case 'LIST':
				case 'UPDATE':
				case 'DELETE':
					return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = ['CCrmDealRecurringRestProxy', 'processEvent'];

		$bindings[CRestUtil::EVENTS]['onCrmDealRecurringAdd'] = self::createEventInfo('crm', 'OnAfterCrmDealRecurringAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealRecurringUpdate'] = self::createEventInfo('crm', 'OnAfterCrmDealRecurringUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealRecurringDelete'] = self::createEventInfo('crm', 'OnAfterCrmDealRecurringDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealRecurringExpose'] = self::createEventInfo('crm', 'OnAfterCrmDealRecurringExpose', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$event = $arParams[0];
		$eventFields = [];
		if ($event instanceof Main\Event)
		{
			$eventFields = $event->getParameters();
		}
		$eventName = mb_strtolower($arHandler['EVENT_NAME']);
		if ($eventName === 'oncrmdealrecurringexpose')
		{
			$id = isset($eventFields['ID']) ? (int)$eventFields['ID'] : 0;
			if($id <= 0)
			{
				throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
			}

			$newDealId = isset($eventFields['DEAL_ID']) ? (int)$eventFields['DEAL_ID'] : 0;
			if($newDealId <= 0)
			{
				throw new RestException("Could not find new deal ID in fields of event \"{$eventName}\"");
			}

			$recurringId = isset($eventFields['RECURRING_ID']) ? (int)$eventFields['RECURRING_ID'] : 0;

			return [
				'FIELDS' => [
					'ID' => $id,
					'RECURRING_DEAL_ID' => $recurringId,
					'DEAL_ID' => $newDealId,
				]
			];
		}
		else
		{
			switch ($eventName)
			{
				case 'oncrmdealrecurringadd':
				case 'oncrmdealrecurringupdate':
					{
						$ID = isset($eventFields['ID']) ? (int)$eventFields['ID'] : 0;
						$dealId = isset($eventFields['DEAL_ID']) ? (int)$eventFields['DEAL_ID'] : 0;
						$resultFields = [
							'ID' => $ID,
							'RECURRING_DEAL_ID' => $dealId
						];
					}
					break;
				case 'oncrmdealrecurringdelete':
					{
						$ID = isset($eventFields['ID']) ? (int)$eventFields['ID'] : 0;
						$resultFields['ID'] = $ID;
					}
					break;
				default:
					throw new RestException("The Event \"{$eventName}\" is not supported in current context");
			}

			if($ID <= 0)
			{
				throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
			}

			return ['FIELDS' => $resultFields];
		}
	}
}

class CCrmInvoiceRecurringRestProxy extends CCrmRestProxyBase
{
	protected $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$restInstance = \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestInvoice::getInstance();
			$fieldParameters = $restInstance->getFieldsInfo();
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'INVOICE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'ACTIVE' => array(
					'TYPE' => 'char'
				),
				'NEXT_EXECUTION' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'LAST_EXECUTION' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'COUNTER_REPEAT' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'START_DATE' => array(
					'TYPE' => 'date'
				),
				'IS_LIMIT' => array(
					'TYPE' => 'char'
				),
				'SEND_BILL' => array(
					'TYPE' => 'char'
				),
				'EMAIL_ID' => array(
					'TYPE' => 'integer'
				),
				'LIMIT_REPEAT' => array(
					'TYPE' => 'integer'
				),
				'LIMIT_DATE' => array(
					'TYPE' => 'date'
				),
				'PARAMS' => array(
					'TYPE' => 'recurring_params',
					'FIELDS' => $fieldParameters
				)
			);
			foreach ($this->FIELDS_INFO as $code=>&$field)
			{
				$field['CAPTION'] = \Bitrix\Crm\InvoiceRecurTable::getFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGet($ID, &$errors)
	{
		$recurringInstance = \Bitrix\Crm\Recurring\Entity\Invoice::getInstance();
		if (!$recurringInstance->isAllowedExpose())
		{
			$errors[] = 'Recurring is not allowed';
			return false;
		}

		$recurringDataRaw = $recurringInstance->getList([
			'filter' => ['ID' => (int)$ID],
			'limit' => 1
		]);

		$fields = $recurringDataRaw->fetch();

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (empty($fields) || (int)$fields['INVOICE_ID'] <= 0)
		{
			$errors[] = 'Recurring invoice is not found';
			return false;
		}

		if(!CCrmInvoice::CheckReadPermission($fields['INVOICE_ID'], $userPermissions))
		{
			$errors[] = 'Access denied';
			return false;
		}

		$result = array_intersect_key($fields, $this->getFieldsInfo());
		$params = $fields['PARAMS'];
		$formParamsMapper = \Bitrix\Crm\Recurring\Entity\Invoice::getParameterMapper($params);
		$formParamsMapper->fillMap($params);
		$restParamsMapper = \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestInvoice::getInstance();
		$restParamsMapper->convert($formParamsMapper);
		$result['PARAMS'] = $restParamsMapper->getFormattedMap();
		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmInvoice::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$params = array();
		if(is_array($order) && !empty($order))
		{
			$fieldInfo = $this->getFieldsInfo();
			foreach ($order as $code => $direction)
			{
				if (!empty($fieldInfo[$code]) && $code !== 'PARAMS')
				{
					$params['order'][$code] = $direction;
				}
			}
		}

		unset($filter['PARAMS']);
		$params['filter'] = $filter;

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;

		$restParamsMapper = \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestInvoice::getInstance();
		/** @var Main\DB\Result $dataRaw */
		$dataRaw = \Bitrix\Crm\InvoiceRecurTable::getList($params);
		$items = array();
		while($fields = $dataRaw->fetch())
		{
			$params = $fields['PARAMS'];
			$mapper = \Bitrix\Crm\Recurring\Entity\Invoice::getParameterMapper($params);
			$mapper->fillMap($params);
			$restParamsMapper->convert($mapper);
			$fields['PARAMS'] = $restParamsMapper->getFormattedMap();
			$items[] = $fields;
		}
		$dbResult = new CDBResult();
		$dbResult->InitFromArray($items);
		$dbResult->NavStart($limit, false, $page);
		return $dbResult;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ID = null;
		$invoiceId = (int)$fields['INVOICE_ID'];
		if ($invoiceId <= 0)
		{
			$errors[] = 'Invoice ID is empty.';
			return false;
		}
		if(
			!CCrmInvoice::CheckUpdatePermission($invoiceId, CCrmPerms::GetCurrentUserPermissions())
			|| !CCrmInvoice::CheckCreatePermission(CCrmPerms::GetCurrentUserPermissions())
		)
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			if (
				$fields['IS_LIMIT'] !== \Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_TIMES
				&& $fields['IS_LIMIT'] !== \Bitrix\Crm\Recurring\Entity\Invoice::LIMITED_BY_DATE
			)
			{
				$fields['IS_LIMIT'] = \Bitrix\Crm\Recurring\Entity\Invoice::NO_LIMITED;
			}
			if (!empty($fields['PARAMS']) && is_array($fields['PARAMS']))
			{
				$fields['PARAMS'] = $this->prepareParams($fields);
			}
			else
			{
				$fields['PARAMS'] = [];
			}
			if(!empty($fields['START_DATE']))
			{
				$fields['START_DATE'] = new \Bitrix\Main\Type\Date($fields['START_DATE']);
			}
			if(!empty($fields['LIMIT_DATE']))
			{
				$fields['LIMIT_DATE'] = new \Bitrix\Main\Type\Date($fields['LIMIT_DATE']);
			}
			$newRecurringFields = $fields;

			$invoiceRecurringInstance = \Bitrix\Crm\Recurring\Entity\Invoice::getInstance();
			$invoiceFields = \CCrmInvoice::GetByID($invoiceId, false);
			if ($invoiceFields['IS_RECURRING'] === 'Y')
			{
				$recurringRawSearch = $invoiceRecurringInstance->getList([
					'filter' => ['INVOICE_ID' => $invoiceId],
					'limit' => 1
				]);
				if ($recurringRawSearch->fetch())
				{
					$errors[] = 'Invoice already have had recurring settings.';
					return false;
				}

				$result = $invoiceRecurringInstance->add($newRecurringFields);
				if ($result->isSuccess())
				{
					$ID = $result->getId();
				}
			}
			else
			{
				unset($newRecurringFields['INVOICE_ID']);
				$invoiceFields['PRODUCT_ROWS'] = \CCrmInvoice::GetProductRows($invoiceId);
				$invoiceFields['INVOICE_PROPERTIES'] = \CCrmInvoice::GetProperties($invoiceId, $invoiceFields['PERSON_TYPE_ID']);
				$result = $invoiceRecurringInstance->createEntity($invoiceFields, $newRecurringFields);
				if ($result->isSuccess())
				{
					$data = $result->getData();
					$ID = $data['ID'];
				}
			}

			if (!$result->isSuccess())
			{
				$errors = $result->getErrorMessages();
				return false;
			}

			return $ID;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$result = $this->innerGet($ID, $errors);
		if (!$result)
		{
			return false;
		}
		elseif(!CCrmInvoice::CheckUpdatePermission($result['INVOICE_ID'], CCrmPerms::GetCurrentUserPermissions()))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (
			!empty($fields['PARAMS']) && is_array($fields['PARAMS'])
			|| !empty($fields['START_DATE'])
			|| !empty($fields['LIMIT_DATE'])
			|| !empty($fields['IS_LIMIT'])
		)
		{
			$merged = array_merge($result, $fields);
			$fields['PARAMS'] = $this->prepareParams($merged);
		}

		if(!empty($fields['START_DATE']))
		{
			$fields['START_DATE'] = new \Bitrix\Main\Type\Date($fields['START_DATE']);
		}
		if(!empty($fields['LIMIT_DATE']))
		{
			$fields['LIMIT_DATE'] = new \Bitrix\Main\Type\Date($fields['LIMIT_DATE']);
		}

		try
		{
			$invoiceRecurring = \Bitrix\Crm\Recurring\Entity\Invoice::getInstance();
			$invoiceRecurring->update($ID, $fields);
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$invoiceRecurring = \Bitrix\Crm\Recurring\Entity\Invoice::getInstance();
		if (!$invoiceRecurring->isAllowedExpose())
		{
			$errors[] = 'Recurring is not allowed';
			return false;
		}

		$recurringDataRaw = $invoiceRecurring->getList([
			'filter' => ['ID' => (int)$ID],
			'limit' => 1
		]);

		if (!($recurringDataRaw->fetch()))
		{
			$errors[] = 'Recurring invoice is not found';
			return false;
		}

		elseif(!CCrmInvoice::CheckDeletePermission(0, CCrmPerms::GetCurrentUserPermissions()))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			$r = $invoiceRecurring->delete($ID);
			if (!$r->isSuccess())
			{
				$errors = $r->getErrorMessages();
				return false;
			}
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function prepareParams(array $fields)
	{
		$restParamsMapper = new \Bitrix\Crm\Recurring\Entity\ParameterMapper\RestInvoice();
		$restParamsMapper->fillMap($fields['PARAMS']);
		$formParamsMapper = \Bitrix\Crm\Recurring\Entity\Invoice::getParameterMapper();
		$formParamsMapper->convert($restParamsMapper);
		$params = $formParamsMapper->getFormattedMap();
		$params['RECURRING_SWITCHER'] = 'Y';
		$params['MULTIPLE_TYPE_LIMIT'] = $fields['IS_LIMIT'];
		$params['MULTIPLE_TIMES_LIMIT'] = (int)$fields['LIMIT_REPEAT'];
		if(!empty($fields['START_DATE']))
		{
			$params['MULTIPLE_DATE_START'] = $fields['START_DATE'];
			$params['SINGLE_DATE_BEFORE'] = $fields['START_DATE'];
		}
		if(!empty($fields['LIMIT_DATE']))
		{
			$params['MULTIPLE_DATE_LIMIT'] = $fields['LIMIT_DATE'];
		}

		return $params;
	}
	public function expose($ID)
	{
		$ID = (int)$ID;
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$errors = array();
		$fields = $this->innerGet($ID, $errors);
		if(!is_array($fields))
		{
			throw new RestException(implode("\n", $errors));
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (
			!CCrmInvoice::CheckReadPermission($fields['INVOICE_ID'], $userPermissions)
			|| !CCrmInvoice::CheckCreatePermission($userPermissions)
		)
		{
			throw new RestException(implode("\n", ['Access denied.']));
		}

		$invoiceRecurring = \Bitrix\Crm\Recurring\Entity\Invoice::getInstance();
		$result = $invoiceRecurring->expose(['=ID' => $ID], 1, false);
		if (!$result->isSuccess())
		{
			throw new RestException(implode("\n", $result->getErrorMessages()));
		}

		$exposeData = $result->getData();
		return ['INVOICE_ID' => $exposeData['ID'][0]];
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'RECURRING')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			switch ($nameSuffix)
			{
				case 'EXPOSE':
					return $this->expose($this->resolveEntityID($arParams));
				case 'FIELDS':
				case 'ADD':
				case 'GET':
				case 'LIST':
				case 'UPDATE':
				case 'DELETE':
					return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = ['CCrmInvoiceRecurringRestProxy', 'processEvent'];

		$bindings[CRestUtil::EVENTS]['onCrmInvoiceRecurringAdd'] = self::createEventInfo('crm', 'OnAfterCrmInvoiceRecurringAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmInvoiceRecurringUpdate'] = self::createEventInfo('crm', 'OnAfterCrmInvoiceRecurringUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmInvoiceRecurringDelete'] = self::createEventInfo('crm', 'OnAfterCrmInvoiceRecurringDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmInvoiceRecurringExpose'] = self::createEventInfo('crm', 'OnAfterCrmInvoiceRecurringExpose', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = mb_strtolower($arHandler['EVENT_NAME']);
		$event = $arParams[0];
		$eventFields = [];
		if ($event instanceof Main\Event)
		{
			$eventFields = $event->getParameters();
		}
		if ($eventName === 'oncrminvoicerecurringexpose')
		{
			$id = isset($eventFields['ID']) ? (int)$eventFields['ID'] : 0;
			if($id <= 0)
			{
				throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
			}

			$newInvoiceId = isset($eventFields['INVOICE_ID']) ? (int)$eventFields['INVOICE_ID'] : 0;
			if($newInvoiceId <= 0)
			{
				throw new RestException("Could not find new invoice ID in fields of event \"{$eventName}\"");
			}

			$recurringId = isset($eventFields['RECURRING_ID']) ? (int)$eventFields['RECURRING_ID'] : 0;

			return [
				'FIELDS' => [
					'ID' => $id,
					'RECURRING_INVOICE_ID' => $recurringId,
					'INVOICE_ID' => $newInvoiceId,
				]
			];
		}
		else
		{
			switch ($eventName)
			{
				case 'oncrminvoicerecurringadd':
				case 'oncrminvoicerecurringupdate':
					{
						$ID = isset($eventFields['ID']) ? (int)$eventFields['ID'] : 0;
						$invoiceId = isset($eventFields['INVOICE_ID']) ? (int)$eventFields['INVOICE_ID'] : 0;
						$resultFields = [
							'ID' => $ID,
							'RECURRING_INVOICE_ID' => $invoiceId
						];
					}
					break;
				case 'oncrminvoicerecurringdelete':
					{
						$ID = isset($eventFields['ID']) ? (int)$eventFields['ID'] : 0;
						$resultFields['ID'] = $ID;
					}
					break;
				default:
					throw new RestException("The Event \"{$eventName}\" is not supported in current context");
			}

			if($ID <= 0)
			{
				throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
			}

			return ['FIELDS' => $resultFields];
		}
	}
}
class CCrmCompanyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;
	protected static $isMyCompany = false;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Company;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCompany::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = CCrmCompany::GetFieldCaption($code);
			}
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmCompany::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeID)
	{
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID, ['isMyCompany' => static::$isMyCompany]);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmCompany(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$isImportMode = is_array($params) && isset($params['IMPORT']) && $params['IMPORT'];

		if(!($isImportMode ? CCrmCompany::CheckImportPermission() : CCrmCompany::CheckCreatePermission()))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		$entity = self::getEntity();
		$options = [];
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}

		if($isImportMode)
		{
			$options['ALLOW_SET_SYSTEM_FIELDS'] = true;
			$fields['PERMISSION'] = 'IMPORT';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			self::traceEntity(\CCrmOwnerType::Company, $result, $fields);
			if (self::isBizProcEnabled() && !$isImportMode)
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Company,
					$result,
					CCrmBizProcEventType::Create,
					$errors
				);
			}
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmCompany::GetListEx(
			array(),
			array('=ID' => $ID, '@CATEGORY_ID' => 0,),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		if($result['IS_MY_COMPANY'] === 'Y')
		{
			static::$isMyCompany = true;
		}

		$result = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToRead(
			$this->getOwnerTypeID(),
			$ID,
			$result,
		);

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmCompany::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = $ufData['VALUE'] ?? '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		$filter['@CATEGORY_ID'] = 0;

		return CCrmCompany::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmCompany::Exists($ID))
		{
			$errors[] = 'Company is not found';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		if(isset($fields['LOGO']) )
		{
			$fields['LOGO_del'] = 'Y';
		}

		$arRow = array();
		$this->prepareMultiFieldData($this->getOwnerTypeID(), $ID, $arRow);

		if (isset($fields['FM']) && is_array($fields['FM']))
		{
			CCrmFieldMulti::CompareValuesFields($arRow['FM'], $fields['FM']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = mb_strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			self::traceEntity(\CCrmOwnerType::Company, $ID, $fields, true);
			if(self::isBizProcEnabled())
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Company,
					$ID,
					CCrmBizProcEventType::Edit,
					$errors
				);
			}
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'CONTACT')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Company, CCrmOwnerType::Contact);
			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmCompanyRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmCompanyAdd'] = self::createEventInfo('crm', 'OnAfterCrmCompanyAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyUpdate'] = self::createEventInfo('crm', 'OnAfterCrmCompanyUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyDelete'] = self::createEventInfo('crm', 'OnAfterCrmCompanyDelete', $callback);

		// user field events
		$bindings[CRestUtil::EVENTS]['onCrmCompanyUserFieldAdd'] = self::createEventInfo('crm', 'OnAfterCrmRestCompanyUserFieldAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyUserFieldUpdate'] = self::createEventInfo('crm', 'OnAfterCrmRestCompanyUserFieldUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyUserFieldDelete'] = self::createEventInfo('crm', 'OnAfterCrmRestCompanyUserFieldDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyUserFieldSetEnumValues'] = self::createEventInfo('crm', 'OnAfterCrmRestCompanyUserFieldSetEnumValues', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Company, $arParams, $arHandler);
	}
}

class CCrmContactRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Contact;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmContact::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = CCrmContact::GetFieldCaption($code);
			}
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmContact::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmContact(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$isImportMode = is_array($params) && isset($params['IMPORT']) && $params['IMPORT'];

		if(!($isImportMode ? CCrmContact::CheckImportPermission() : CCrmContact::CheckCreatePermission()))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		$entity = self::getEntity();
		$options = [];
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}

		if($isImportMode)
		{
			$options['ALLOW_SET_SYSTEM_FIELDS'] = true;
			$fields['PERMISSION'] = 'IMPORT';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			self::traceEntity(\CCrmOwnerType::Contact, $result, $fields);
			if (self::isBizProcEnabled() && !$isImportMode)
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Contact,
					$result,
					CCrmBizProcEventType::Create,
					$errors
				);
			}
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmContact::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmContact::GetListEx(
			array(),
			array('=ID' => $ID, '@CATEGORY_ID' => 0,),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToRead(
			$this->getOwnerTypeID(),
			$ID,
			$result,
		);

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmContact::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = $ufData['VALUE'] ?? '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmContact::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		$filter['@CATEGORY_ID'] = 0;

		return CCrmContact::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmContact::Exists($ID))
		{
			$errors[] = 'Contact is not found';
			return false;
		}

		$diskQuotaRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDiskQuotaRestriction();
		if (!$diskQuotaRestriction->hasPermission())
		{
			$errors[] = $diskQuotaRestriction->getErrorMessage();
			return false;
		}

		if(isset($fields['PHOTO']) )
		{
			$fields['PHOTO_del'] = 'Y';
		}

		$arRow = array();
		$this->prepareMultiFieldData($this->getOwnerTypeID(), $ID, $arRow);

		if (isset($fields['FM']) && is_array($fields['FM']))
		{
			CCrmFieldMulti::CompareValuesFields($arRow['FM'], $fields['FM']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = mb_strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = mb_strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		else
		{
			self::traceEntity(\CCrmOwnerType::Contact, $ID, $fields, true);
			if(self::isBizProcEnabled())
			{
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Contact,
					$ID,
					CCrmBizProcEventType::Edit,
					$errors
				);
			}
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'COMPANY')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Contact, CCrmOwnerType::Company);
			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmContactRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmContactAdd'] = self::createEventInfo('crm', 'OnAfterCrmContactAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactUpdate'] = self::createEventInfo('crm', 'OnAfterCrmContactUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactDelete'] = self::createEventInfo('crm', 'OnAfterCrmContactDelete', $callback);

		// user field events
		$bindings[CRestUtil::EVENTS]['onCrmContactUserFieldAdd'] = self::createEventInfo('crm', 'OnAfterCrmRestContactUserFieldAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactUserFieldUpdate'] = self::createEventInfo('crm', 'OnAfterCrmRestContactUserFieldUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactUserFieldDelete'] = self::createEventInfo('crm', 'OnAfterCrmRestContactUserFieldDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactUserFieldSetEnumValues'] = self::createEventInfo('crm', 'OnAfterCrmRestContactUserFieldSetEnumValues', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Contact, $arParams, $arHandler);
	}
}

class CCrmCurrencyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private $LOC_FIELDS_INFO = null;

	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if (!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCurrency::GetFieldsInfo();
			foreach (array_keys($this->FIELDS_INFO) as $code)
			{
				$this->FIELDS_INFO[$code]['CAPTION'] = CCrmCurrency::GetFieldCaption($code);
			}

			$this->FIELDS_INFO['LANG'] = [
				'TYPE' => 'currency_localization',
				'ATTRIBUTES' => [
					CCrmFieldInfoAttr::Multiple,
				],
				'CAPTION' => Loc::getMessage('CRM_REST_CURRENCY_FIELD_LANG'),
			];
		}

		return $this->FIELDS_INFO;
	}

	public function getLocalizationFieldsInfo()
	{
		if (!$this->LOC_FIELDS_INFO)
		{
			$this->LOC_FIELDS_INFO = CCrmCurrency::GetCurrencyLocalizationFieldsInfo();
			foreach (array_keys($this->LOC_FIELDS_INFO) as $code)
			{
				$this->LOC_FIELDS_INFO[$code]['CAPTION'] = CCrmCurrency::GetFieldCaption($code);
			}
		}

		return $this->LOC_FIELDS_INFO;
	}

	public function isValidID($ID)
	{
		return is_string($ID) && $ID !== '';
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if (!CCrmCurrency::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';

			return false;
		}

		$result = CCrmCurrency::Add($fields);
		if ($result === false)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}

		return $result;
	}

	private function getClearedLocalizations(array $rawLocalizations): array
	{
		if (empty($rawLocalizations))
		{
			return [];
		}

		$result = [];

		$whiteList = $this->getLocalizationFieldsInfo();

		foreach (array_keys($rawLocalizations) as $langId)
		{
			$result[$langId] = array_intersect_key(
				$rawLocalizations[$langId],
				$whiteList
			);
		}

		return $result;
	}

	protected function innerGet($ID, &$errors)
	{
		if (!CCrmCurrency::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';

			return false;
		}

		$result = CCrmCurrency::GetByID($ID);
		if (!is_array($result))
		{
			$errors[] = 'Not found';

			return false;
		}

		$result['LANG'] = $this->getClearedLocalizations(\CCrmCurrency::GetCurrencyLocalizations($ID));

		return $result;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if (!CCrmCurrency::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';

			return false;
		}

		return CCrmCurrency::GetList($order);
	}

	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if (!CCrmCurrency::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';

			return false;
		}

		if (!CCrmCurrency::IsExists($ID))
		{
			$errors[] = 'Currency is not found';

			return false;
		}

		$result = CCrmCurrency::Update($ID, $fields);
		if ($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}

		return $result;
	}

	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmCurrency::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';

			return false;
		}

		$result = CCrmCurrency::Delete($ID);
		if ($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}

		return $result;
	}

	protected function resolveEntityID(&$arParams)
	{
		$currencyId = '';
		if (isset($arParams['ID']))
		{
			$currencyId = $arParams['ID'];
		}
		elseif (isset($arParams['id']))
		{
			$currencyId = $arParams['id'];
		}

		if (!is_string($currencyId))
		{
			throw new RestException('The parameter id is not string.');
		}

		return mb_strtoupper(trim($currencyId));
	}

	protected function checkEntityID($ID)
	{
		return is_string($ID) && $ID !== '';
	}

	public function getLocalizations($ID)
	{
		$ID = (string)$ID;
		if ($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if (!CCrmCurrency::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return $this->getClearedLocalizations(CCrmCurrency::GetCurrencyLocalizations($ID));
	}

	public function setLocalizations($ID, $localizations)
	{
		$ID = (string)$ID;
		if ($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if (!is_array($localizations) || empty($localizations))
		{
			return false;
		}

		if (!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::SetCurrencyLocalizations($ID, $localizations);
	}

	public function deleteLocalizations($ID, $langs)
	{
		$ID = (string)$ID;
		if ($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if (!is_array($langs) || empty($langs))
		{
			return false;
		}

		if (!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::DeleteCurrencyLocalizations($ID, $langs);
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if ($name === 'LOCALIZATIONS')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if ($nameSuffix === 'FIELDS')
			{
				$fildsInfo = $this->getLocalizationFieldsInfo();

				return parent::prepareFields($fildsInfo);
			}
			elseif ($nameSuffix === 'GET')
			{
				return $this->getLocalizations($this->resolveEntityID($arParams));
			}
			elseif ($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$localizations = $this->resolveArrayParam($arParams, 'localizations');

				return $this->setLocalizations($ID, $localizations);
			}
			elseif ($nameSuffix === 'DELETE')
			{
				$ID = $this->resolveEntityID($arParams);
				$lids = $this->resolveArrayParam($arParams, 'lids');

				return $this->deleteLocalizations($ID, $lids);
			}
		}
		elseif ($name === 'BASE')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if ($nameSuffix === 'GET')
			{
				return \CCrmCurrency::GetBaseCurrencyID();
			}
			elseif ($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				if (!CCrmCurrency::CheckUpdatePermission($ID))
				{
					throw new RestException('Access denied.');
				}

				return \CCrmCurrency::SetBaseCurrencyID($ID);
			}
		}

		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}

	public static function registerEventBindings(array &$bindings)
	{
		if (!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = [];
		}

		$callback = ['CCrmCurrencyRestProxy', 'processEvent'];

		$bindings[CRestUtil::EVENTS]['onCrmCurrencyAdd'] = self::createEventInfo(
			'currency',
			'OnCurrencyAdd',
			$callback
		);
		$bindings[CRestUtil::EVENTS]['onCrmCurrencyUpdate'] = self::createEventInfo(
			'currency',
			'OnCurrencyUpdate',
			$callback
		);
		$bindings[CRestUtil::EVENTS]['onCrmCurrencyDelete'] = self::createEventInfo(
			'currency',
			'OnCurrencyDelete',
			$callback
		);
	}

	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch (mb_strtolower($eventName))
		{
			case 'oncrmcurrencyadd':
			case 'oncrmcurrencyupdate':
			case 'oncrmcurrencydelete':
				{
					$ID = isset($arParams[0]) && is_string($arParams[0])? $arParams[0] : '';
				}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if ($ID === '')
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}

		return [
			'FIELDS' => [
				'ID' => $ID,
			],
		];
	}
}

class CCrmStatusRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY_TYPES = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmStatus::GetFieldsInfo();
			foreach ($this->FIELDS_INFO as $code=>&$field)
			{
				$field['CAPTION'] = CCrmStatus::getFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entityID = $fields['ENTITY_ID'] ?? '';
		$statusID = $fields['STATUS_ID'] ?? '';
		$color = $fields['COLOR'] ?? $fields['EXTRA']['COLOR'] ?? '';
		if($entityID === '' || $statusID === '')
		{
			if($entityID === '')
			{
				$errors[] = 'The field ENTITY_ID is required.';
			}

			if($statusID === '')
			{
				$errors[] = 'The field STATUS_ID is required.';
			}

			return false;
		}

		$entityTypes = self::prepareEntityTypes();
		if(!isset($entityTypes[$entityID]))
		{
			$errors[] = 'Specified entity type is not supported.';
			return false;
		}

		$fields['SYSTEM'] = 'N';
		$fields['COLOR'] = $color;
		$entity = new CCrmStatus($entityID);
		$result = $entity->Add($fields, true);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$result = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'CRM Status is not found.';
			return null;
		}

		self::prepareExtra($result);
		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['sort'] = 'asc';
		}

		$stringOnlyFields = [
			'ENTITY_ID',
			'STATUS_ID',
			'SORT',
			'SEMANTICS',
		];
		foreach ($stringOnlyFields as $stringOnlyField)
		{
			if (is_array($filter[$stringOnlyField] ?? null))
			{
				$errors[] = "Filter by {$stringOnlyField} must be a string";
				return false;
			}
		}

		$results = array();
		$dbResult = CCrmStatus::GetList($order, $filter);
		if(is_object($dbResult))
		{
			while($item = $dbResult->Fetch())
			{
				self::prepareExtra($item);
				$results[] = $item;
			}
		}
		return $results;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		if(!isset($fields['COLOR']) && isset($fields['EXTRA']['COLOR']))
		{
			$fields['COLOR'] = $fields['EXTRA']['COLOR'];
		}
		$result = true;
		if(isset($fields['NAME']) || isset($fields['SORT']) || isset($fields['STATUS_ID']) || isset($fields['COLOR']))
		{
			if(!isset($fields['NAME']))
			{
				$fields['NAME'] = $currentFields['NAME'];
			}

			if(!isset($fields['SORT']))
			{
				$fields['SORT'] = $currentFields['SORT'];
			}
			$entity = new CCrmStatus($currentFields['ENTITY_ID']);
			$result = $entity->Update($ID, $fields);
			if($result === false)
			{
				$errors[] = $entity->GetLastError();
			}
		}

		return $result !== false;

	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$isSystem = isset($currentFields['SYSTEM']) && $currentFields['SYSTEM'] === 'Y';
		$forced = is_array($params) && isset($params['FORCED']) && $params['FORCED'] === 'Y';

		if($isSystem && !$forced)
		{
			$errors[] = 'CRM System Status can be deleted only if parameter FORCED is specified and equal to "Y".';
			return false;
		}

		$entity = new CCrmStatus($currentFields['ENTITY_ID']);
		if(isset($currentFields['STATUS_ID']) && $entity->existsEntityWithStatus($currentFields['STATUS_ID']))
		{
			$errors[] = 'There are active items in this status.';
			return false;
		}

		$result = $entity->Delete($ID);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		return $result !== false;
	}
	private static function prepareExtra(array &$fields)
	{
		$statusID = $fields['STATUS_ID'] ?? '';
		if($statusID === '')
		{
			return null;
		}

		$result = null;
		$entityID = $fields['ENTITY_ID'] ?? '';
		if($entityID === 'STATUS')
		{
			$result = array('SEMANTICS' => CCrmLead::GetStatusSemantics($statusID));
		}
		elseif($entityID === 'QUOTE_STATUS')
		{
			$result = array('SEMANTICS' => CCrmQuote::GetStatusSemantics($statusID));
		}
		elseif($entityID === 'DEAL_STAGE')
		{
			$result = array('SEMANTICS' => CCrmDeal::GetStageSemantics($statusID, 0));
		}
		elseif(DealCategory::hasStatusEntity($entityID))
		{
			$categoryID = DealCategory::convertFromStatusEntityID($entityID);
			$result = array('SEMANTICS' => CCrmDeal::GetStageSemantics($statusID, $categoryID));
		}

		if(is_array($result))
		{
			$result['COLOR'] = $fields['COLOR'] ?? '';
			$fields['EXTRA'] = $result;
		}
	}
	private static function prepareEntityTypes()
	{
		if(!self::$ENTITY_TYPES)
		{
			self::$ENTITY_TYPES = CCrmStatus::GetEntityTypes();
		}

		return self::$ENTITY_TYPES;
	}
	public function getEntityTypes()
	{
		return array_values(self::prepareEntityTypes());
	}
	public function getEntityItems($entityID)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		if($entityID === '')
		{
			throw new RestException('The parameter entityId is not defined or invalid.');
		}

		//return CCrmStatus::GetStatusList($entityID);
		$dbResult = CCrmStatus::GetList(array('sort' => 'asc'), array('ENTITY_ID' => mb_strtoupper($entityID)));
		if(!$dbResult)
		{
			return array();
		}

		$result = array();
		while($fields = $dbResult->Fetch())
		{
			$result[] = array(
				'NAME' => $fields['NAME'],
				'SORT' => intval($fields['SORT']),
				'STATUS_ID' => $fields['STATUS_ID']
			);
		}

		return $result;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'ENTITY')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'TYPES')
			{
				return $this->getEntityTypes();
			}
			elseif($nameSuffix === 'ITEMS')
			{
				return $this->getEntityItems($this->resolveRelationID($arParams, 'entity'));
			}
			elseif($name === 'EXTRA')
			{
				$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
				if($nameSuffix === 'FIELDS')
				{
					return CCrmStatus::GetFieldExtraTypeInfo();
				}
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmStatusInvoiceRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmStatusInvoice::GetFieldsInfo();
			foreach ($this->FIELDS_INFO as $code=>&$field)
			{
				$field['CAPTION'] = CCrmStatus::getFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$result = $statusInvoice->Add($fields);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on creating status.';
		}
		elseif(is_string($result))
		{
			$result = ord($result);
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$crmStatus = new CCrmStatus('INVOICE_STATUS');
		$result = $crmStatus->getStatusById($ID);
		if($result === false)
		{
			$errors[] = 'Status is not found.';
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!is_array($filter))
		{
			$filter = [];
		}

		$filter['ENTITY_ID'] = 'INVOICE_STATUS';

		return CCrmStatusInvoice::GetList($order, $filter);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$currentFields = $statusInvoice->getStatusById($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$result = $statusInvoice->Update($ID, $fields);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on updating status.';
		}

		return $result !== false;

	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$currentFields = $statusInvoice->getStatusById($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		if (isset($currentFields['SYSTEM']) && $currentFields['SYSTEM'] === 'Y')
		{
			$errors[] = "Can't delete system status";
			return false;
		}

		$result = $statusInvoice->Delete($ID);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on deleting status.';
		}
		return $result !== false;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'STATUS')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');

			switch ($nameSuffix)
			{
				case 'FIELDS':
				case 'ADD':
				case 'GET':
				case 'LIST':
				case 'UPDATE':
				case 'DELETE':
					return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
					break;
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmActivityRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private $COMM_FIELDS_INFO = null;
	public function getOwnerTypeID()
	{
		return CCrmOwnerType::Activity;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmActivity::GetFieldsInfo();
			$this->FIELDS_INFO['BINDINGS'] = array(
				'TYPE' => 'crm_activity_binding',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple, CCrmFieldInfoAttr::ReadOnly)
			);

			foreach ($this->FIELDS_INFO as $code=>&$field)
			{
				$field['CAPTION'] = \Bitrix\Crm\ActivityTable::getFieldCaption($code);
			}

			$this->FIELDS_INFO['COMMUNICATIONS'] = array(
				'TYPE' => 'crm_activity_communication',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple, CCrmFieldInfoAttr::Required),
				'CAPTION' => Loc::getMessage('CRM_REST_ACTIVITY_FIELD_COMMUNICATIONS')
			);

			$storageTypeID =  CCrmActivity::GetDefaultStorageTypeID();
			if($storageTypeID === StorageType::Disk)
			{
				$this->FIELDS_INFO['FILES'] = array(
					'TYPE' => 'diskfile',
					'ALIAS' => 'WEBDAV_ELEMENTS',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple),
					'CAPTION' => Loc::getMessage('CRM_REST_ACTIVITY_FIELD_FILES')
				);
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'diskfile',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Deprecated, CCrmFieldInfoAttr::Multiple),
					'CAPTION' => Loc::getMessage('CRM_REST_ACTIVITY_FIELD_WEBDAV_ELEMENTS')
				);
			}
			else
			{
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'webdav',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple),
					'CAPTION' => Loc::getMessage('CRM_REST_ACTIVITY_FIELD_WEBDAV_ELEMENTS')
				);
			}

			$this->FIELDS_INFO['IS_INCOMING_CHANNEL'] = [
				'TYPE' => 'char',
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
			];
		}
		return $this->FIELDS_INFO;
	}
	protected function getCommunicationFieldsInfo()
	{
		if(!$this->COMM_FIELDS_INFO)
		{
			$this->COMM_FIELDS_INFO = CCrmActivity::GetCommunicationFieldsInfo();
			foreach ($this->COMM_FIELDS_INFO as $code=>&$field)
			{
				$field['CAPTION'] = \Bitrix\Crm\ActivityTable::getFieldCaption($code);
			}
		}
		return $this->COMM_FIELDS_INFO;
	}
	protected function internalizeCommunications($ownerTypeID, $ownerID, $typeID, &$communications, &$bindings)
	{
		$communicationFieldInfos = self::getCommunicationFieldsInfo();
		foreach($communications as $k => &$v)
		{
			self::internalizeFields($v, $communicationFieldInfos);

			$commEntityTypeID = $v['ENTITY_TYPE_ID'] ? intval($v['ENTITY_TYPE_ID']) : 0;
			$commEntityID = $v['ENTITY_ID'] ? intval($v['ENTITY_ID']) : 0;
			$commValue = $v['VALUE'] ?: '';
			$commType = $v['TYPE'] ?: '';

			if($commValue !== '' && ($commEntityTypeID <= 0 || $commEntityID <= 0))
			{
				// Push owner info into communication (if ommited)
				$commEntityTypeID = $v['ENTITY_TYPE_ID'] = $ownerTypeID;
				$commEntityID = $v['ENTITY_ID'] = $ownerID;
			}

			if($commEntityTypeID <= 0 || $commEntityID <= 0)
			{
				unset($communications[$k]);
				continue;
			}

			// value can be empty for meetings and tasks
			if ($commValue === '' && !in_array($typeID, [CCrmActivityType::Meeting, CCrmActivityType::Task]))
			{
				unset($communications[$k]);
				continue;
			}

			if($commType === '')
			{
				if($typeID === CCrmActivityType::Call)
				{
					$v['TYPE'] = 'PHONE';
				}
				elseif($typeID === CCrmActivityType::Email)
				{
					$v['TYPE'] = 'EMAIL';
				}
			}
			elseif(($typeID === CCrmActivityType::Call && $commType !== 'PHONE')
				|| ($typeID === CCrmActivityType::Email && $commType !== 'EMAIL'))
			{
				// Invalid communication type is specified
				unset($communications[$k]);
				continue;
			}

			$bindings["{$commEntityTypeID}_{$commEntityID}"] = array(
				'OWNER_TYPE_ID' => $commEntityTypeID,
				'OWNER_ID' => $commEntityID
			);
		}
		unset($v);
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;

		$bindings = array();
		if($ownerTypeID > 0 && $ownerID > 0)
		{
			$bindings["{$ownerTypeID}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		if(!isset($fields['SUBJECT']) || trim($fields['SUBJECT']) === '')
		{
			$errors[] = 'The field SUBJECT is not defined or empty.';
			return false;
		}

		$responsibleID = isset($fields['RESPONSIBLE_ID']) ? intval($fields['RESPONSIBLE_ID']) : 0;
		if($responsibleID <= 0 && $ownerTypeID > 0 && $ownerID > 0)
		{
			$fields['RESPONSIBLE_ID'] = $responsibleID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID);
		}

		if($responsibleID <= 0)
		{
			$responsibleID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if($responsibleID <= 0)
		{
			$errors[] = 'The field RESPONSIBLE_ID is not defined or invalid.';
			return false;
		}

		if (isset($fields['PROVIDER_ID']) && empty($fields['TYPE_ID']))
			$fields['TYPE_ID'] = CCrmActivityType::Provider;

		$typeID = isset($fields['TYPE_ID']) ? intval($fields['TYPE_ID']) : CCrmActivityType::Undefined;
		if(!CCrmActivityType::IsDefined($typeID))
		{
			$errors[] = 'The field TYPE_ID is not defined or invalid.';
			return false;
		}

		if ($typeID === CCrmActivityType::Provider && ($provider = CCrmActivity::GetActivityProvider($fields)) === null)
		{
			$errors[] = 'The custom activity without provider is not supported in current context.';
			return false;
		}

		if(!in_array($typeID, array(CCrmActivityType::Call, CCrmActivityType::Meeting, CCrmActivityType::Email, CCrmActivityType::Provider), true))
		{
			$errors[] = 'The activity type "'.CCrmActivityType::ResolveDescription($typeID).' is not supported in current context".';
			return false;
		}

		$isRestActivity = isset($fields['PROVIDER_ID']) && $fields['PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\RestApp::getId();

		if (($fields['PROVIDER_ID'] ?? null) === \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::getId())
		{
			$errors[] = 'Use crm.activity.configurable.add for this activity provider';

			return false;
		}

		if ($isRestActivity)
		{
			$clientId = $this->getServer()->getClientId();
			$application = $clientId ? \Bitrix\Rest\AppTable::getByClientId($clientId) : null;

			if (!$application)
			{
				$errors[] = 'Application context required.';
				return false;
			}

			$fields['ASSOCIATED_ENTITY_ID'] = $application['ID'];
		}

		$description = $fields['DESCRIPTION'] ?? '';
		$descriptionType = isset($fields['DESCRIPTION_TYPE']) ? intval($fields['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;
		if($description !== '' && !$isRestActivity && CCrmActivity::AddEmailSignature($description, $descriptionType))
		{
			$fields['DESCRIPTION'] = $description;
		}

		$direction = isset($fields['DIRECTION']) ? intval($fields['DIRECTION']) : CCrmActivityDirection::Undefined;
		$completed = isset($fields['COMPLETED']) && mb_strtoupper($fields['COMPLETED']) === 'Y';
		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : array();

		$this->internalizeCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

		if(empty($communications) && $typeID !== CCrmActivityType::Provider)
		{
			$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
			return false;
		}

		if(($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting)
			&& count($communications) > 1)
		{
			$errors[] = 'The only one communication is allowed for activity of specified type.';
			return false;
		}

		if(empty($bindings))
		{
			$errors[] = 'Could not build binding. Please ensure that owner info and communications are defined correctly.';
			return false;
		}

		foreach($bindings as $binding)
		{
			$bindingOwnerTypeName = CCrmOwnerType::ResolveName($binding['OWNER_TYPE_ID']);
			if($bindingOwnerTypeName === '')
			{
				$bindingOwnerTypeName = "[{$binding['OWNER_TYPE_ID']}]";
			}

			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($binding['OWNER_TYPE_ID']);
			if($factory === null)
			{
				$errors[] = "Entity type '{$bindingOwnerTypeName}' is not supported in current context";
				return false;
			}

			if(!$factory->getItem($binding['OWNER_ID'], ['ID']))
			{
				$errors[] = "Could not find '{$bindingOwnerTypeName}' with ID: {$binding['OWNER_ID']}";
				return false;
			}

			if(!CCrmActivity::CheckUpdatePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		$fields['BINDINGS'] = array_values($bindings);
		$fields['COMMUNICATIONS'] = $communications;
		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		$fields['STORAGE_ELEMENT_IDS'] = array();

		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}
			}
			unset($element);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		if(!($ID = CCrmActivity::Add($fields)))
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
			return false;
		}

		if($completed
			&& $typeID === CCrmActivityType::Email
			&& $direction === CCrmActivityDirection::Outgoing)
		{
			$sendErrors = array();
			if(!CCrmActivityEmailSender::TrySendEmail($ID, $fields, $sendErrors))
			{
				foreach($sendErrors as &$error)
				{
					$code = $error['CODE'];
					if($code === CCrmActivityEmailSender::ERR_CANT_LOAD_SUBSCRIBE)
					{
						$errors[] = 'Email send error. Failed to load module "subscribe".';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_DATA)
					{
						$errors[] = 'Email send error. Invalid data.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_EMAIL)
					{
						$errors[] = 'Email send error. Invalid email is specified.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_FROM)
					{
						$errors[] = 'Email send error. "From" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_TO)
					{
						$errors[] = 'Email send error. "To" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_ADD_POSTING)
					{
						$errors[] = 'Email send error. Failed to add posting. Please see details below.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_SAVE_POSTING_FILE)
					{
						$errors[] = 'Email send error. Failed to save posting file. Please see details below.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_UPDATE_ACTIVITY)
					{
						$errors[] = 'Email send error. Failed to update activity.';
					}
					else
					{
						$errors[] = 'Email send error. General error.';
					}

					$msg = $error['MESSAGE'] ?? '';
					if($msg !== '')
					{
						$errors[] = $msg;
					}
				}
				unset($error);
				return false;
			}

			addEventToStatFile(
				'crm',
				'send_email_message',
				sprintf('rest_%s', $this->getServer()->getClientId() ?: 'undefined'),
				trim(trim($fields['SETTINGS']['MESSAGE_HEADERS']['Message-Id']), '<>')
			);
		}
		return $ID;
	}
	protected function innerGet($ID, &$errors)
	{
		// Permissions will be checked by default
		$dbResult = CCrmActivity::GetList(array(), array('ID' => $ID));
		if($dbResult)
		{
			$result = $dbResult->Fetch();
			if ($result['PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::getId())
			{
				$errors[] = 'Use crm.activity.configurable.get for this activity provider';

				return null;
			}

			return $result;
		}

		$errors[] = 'Activity is not found.';
		return null;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['START_TIME'] = 'ASC';
		}

		if(!is_array($select))
		{
			$select = array();
		}

		//Proces storage aliases
		if(array_search('STORAGE_ELEMENT_IDS', $select, true) === false
			&& (array_search('FILES', $select, true) !== false || array_search('WEBDAV_ELEMENTS', $select, true) !== false))
		{
			$select[] = 'STORAGE_ELEMENT_IDS';
		}

		// Permissions will be checked by default
		return CCrmActivity::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		CCrmActivity::PrepareStorageElementIDs($currentFields);

		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		$typeID = intval($currentFields['TYPE_ID']);
		$currentOwnerID = intval($currentFields['OWNER_ID']);
		$currentOwnerTypeID = intval($currentFields['OWNER_TYPE_ID']);

		if(!CCrmActivity::CheckUpdatePermission($currentOwnerTypeID, $currentOwnerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		if($ownerID <= 0)
		{
			$ownerID = $currentOwnerID;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		if($ownerTypeID <= 0)
		{
			$ownerTypeID = $currentOwnerTypeID;
		}

		if(($ownerTypeID !== $currentOwnerTypeID || $ownerID !== $currentOwnerID)
			&& !CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if ($currentFields['PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\RestApp::getId())
		{
			$clientId = $this->getServer()->getClientId();
			$application = $clientId ? \Bitrix\Rest\AppTable::getByClientId($clientId) : null;

			if (!$application)
			{
				$errors[] = 'Application context required.';
				return false;
			}

			if ((int)$currentFields['ASSOCIATED_ENTITY_ID'] !== $application['ID'])
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}
		if (($currentFields['PROVIDER_ID'] ?? null) === \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::getId())
		{
			$errors[] = 'Use crm.activity.configurable.update for this activity provider';

			return false;
		}

		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : null;

		if(is_array($communications))
		{
			$bindings = array();
			if($ownerTypeID > 0 && $ownerID > 0)
			{
				$bindings["{$ownerTypeID}_{$ownerID}"] = array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID
				);
			}

			$this->internalizeCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

			if(empty($communications))
			{
				$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
				return false;
			}

			$fields['BINDINGS'] = array_values($bindings);
			$fields['COMMUNICATIONS'] = $communications;
		}


		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		unset($fields['STORAGE_ELEMENT_IDS']);
		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			$prevStorageElementIDs = $currentFields['STORAGE_ELEMENT_IDS'] ?? array();
			$oldStorageElementIDs = array();
			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					if(!isset($fields['STORAGE_ELEMENT_IDS']))
					{
						$fields['STORAGE_ELEMENT_IDS'] = array();
					}
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}

				$oldElementID = isset($element['OLD_ELEMENT_ID']) ? intval($element['OLD_ELEMENT_ID']) : 0;
				if($oldElementID > 0
					&& ($elementID > 0 || (isset($element['DELETE']) && $element['DELETE'] === true)))
				{
					if(in_array($oldElementID, $prevStorageElementIDs))
					{
						$oldStorageElementIDs[] = $oldElementID;
					}
				}
			}
			unset($element);
		}
		else if($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					if(!isset($fields['STORAGE_ELEMENT_IDS']))
					{
						$fields['STORAGE_ELEMENT_IDS'] = array();
					}
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		$regEvent = true;
		if(is_array($params) && isset($params['REGISTER_HISTORY_EVENT']))
		{
			$regEvent = mb_strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
		}
		$result = CCrmActivity::Update($ID, $fields, false, $regEvent, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}
		else
		{
			if(is_array($communications))
			{
				CCrmActivity::SaveCommunications($ID, $communications, $fields, false, false);
			}

			if(!empty($oldStorageElementIDs))
			{
				$webdavIBlock = $this->prepareWebDavIBlock();
				foreach($oldStorageElementIDs as $elementID)
				{
					$webdavIBlock->Delete(array('element_id' => $elementID));
				}
			}
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		if(!CCrmActivity::CheckDeletePermission(
			$currentFields['OWNER_TYPE_ID'], $currentFields['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if ($currentFields['PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\RestApp::getId())
		{
			$clientId = $this->getServer()->getClientId();
			$application = $clientId ? \Bitrix\Rest\AppTable::getByClientId($clientId) : null;

			if (!$application)
			{
				$errors[] = 'Application context required.';
				return false;
			}

			if ($currentFields['ASSOCIATED_ENTITY_ID'] !== $application['ID'])
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		$result = CCrmActivity::Delete($ID, false, true, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}

		return $result;
	}
	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		$storageTypeID = isset($fields['STORAGE_TYPE_ID'])
			? (int)$fields['STORAGE_TYPE_ID'] : CCrmActivity::GetDefaultStorageTypeID();

		if(isset($fields['STORAGE_ELEMENT_IDS']))
		{
			CCrmActivity::PrepareStorageElementIDs($fields);
			if($storageTypeID === Bitrix\Crm\Integration\StorageType::Disk)
			{
				$fields['FILES'] = $fields['STORAGE_ELEMENT_IDS'];
			}
			elseif($storageTypeID === Bitrix\Crm\Integration\StorageType::WebDav)
			{
				$fields['WEBDAV_ELEMENTS'] = $fields['STORAGE_ELEMENT_IDS'];
			}
			unset($fields['STORAGE_ELEMENT_IDS']);
		}
		parent::externalizeFields($fields, $fieldsInfo);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'COMMUNICATION')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$fieldsInfo = $this->getCommunicationFieldsInfo();
				return parent::prepareFields($fieldsInfo);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmActivityRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmActivityAdd'] = self::createEventInfo('crm', 'OnActivityAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmActivityUpdate'] = self::createEventInfo('crm', 'OnActivityUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmActivityDelete'] = self::createEventInfo('crm', 'OnActivityDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmactivityadd':
			case 'oncrmactivityupdate':
			case 'oncrmactivitydelete':
				{
					$ID = isset($arParams[0])? (int)$arParams[0] : 0;
				}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID <= 0)
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
}

class CCrmDuplicateRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if(!CCrmLead::CheckReadPermission(0, $userPerms)
			&& !CCrmContact::CheckReadPermission(0, $userPerms)
			&& !CCrmCompany::CheckReadPermission(0, $userPerms))
		{
			throw new RestException('Access denied.');
		}

		if(mb_strtoupper($name) === 'FINDBYCOMM')
		{
			$type = mb_strtoupper($this->resolveParam($arParams, 'type'));
			if($type !== 'EMAIL' && $type !== 'PHONE')
			{
				if($type === '')
				{
					throw new RestException("Communication type is not defined.");
				}
				else
				{
					throw new RestException("Communication type '{$type}' is not supported in current context.");
				}
			}

			$values = $this->resolveArrayParam($arParams, 'values');
			if(!is_array($values) || count($values) === 0)
			{
				throw new RestException("Communication values is not defined.");
			}

			$entityTypeID = CCrmOwnerType::ResolveID(
				$this->resolveMultiPartParam($arParams, array('entity', 'type'))
			);

			if($entityTypeID === CCrmOwnerType::Deal)
			{
				throw new RestException("Deal is not supported in current context.");
			}

			$criterions = array();
			$dups = array();
			$qty = 0;
			foreach($values as $value)
			{
				if(!is_string($value) || $value === '')
				{
					continue;
				}

				$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion($type, $value);
				$isExists = false;
				foreach($criterions as $curCriterion)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateCriterion $curCriterion */
					if($criterion->equals($curCriterion))
					{
						$isExists = true;
						break;
					}
				}

				if($isExists)
				{
					continue;
				}
				$criterions[] = $criterion;

				$duplicate = $criterion->find($entityTypeID, 20);
				if($duplicate !== null)
				{
					$dups[] = $duplicate;
				}

				$qty++;
				if($qty >= 20)
				{
					break;
				}
			}

			$entityByType = array();
			foreach($dups as $dup)
			{
				/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
				$entities = $dup->getEntities();
				if(!(is_array($entities) && !empty($entities)))
				{
					continue;
				}

				//Each entity type limited by 50 items
				foreach($entities as $entity)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
					$entityTypeID = $entity->getEntityTypeID();
					$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

					$entityID = $entity->getEntityID();

					if(!isset($entityByType[$entityTypeName]))
					{
						$entityByType[$entityTypeName] = array($entityID);
					}
					elseif(!in_array($entityID, $entityByType[$entityTypeName], true))
					{
						$entityByType[$entityTypeName][] = $entityID;
					}
				}
			}
			return $entityByType;
		}
		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmLiveFeedMessageRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		if (!\Bitrix\Crm\Integration\Socialnetwork\Livefeed\AvailabilityHelper::isAvailable())
		{
			throw new RestException('Livefeed is no longer supported');
		}

		global $USER;

		$name = mb_strtoupper($name);
		if($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$arComponentResult = array(
				'USER_ID' => $this->getCurrentUserID()
			);

			$arPOST = array(
				'ENABLE_POST_TITLE' => 'Y',
				'MESSAGE' => $fields['MESSAGE'],
				'SPERM' => $fields['SPERM']
			);

			if (
				isset($fields['POST_TITLE'])
				&& $fields['POST_TITLE'] <> ''
			)
			{
				$arPOST['POST_TITLE'] = $fields['POST_TITLE'];
			}

			$entityTypeID = $fields['ENTITYTYPEID'];
			$entityID = $fields['ENTITYID'];

			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			$userPerms = CCrmPerms::GetCurrentUserPermissions();

			if(
				$entityTypeName !== ''
				&& !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $userPerms)
			)
			{
				throw new RestException('Access denied.');
			}

			if (
				isset($fields["FILES"])
				&& \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
				&& CModule::includeModule('disk')
				&& ($storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($USER->getID()))
				&& ($folder = $storage->getFolderForUploadedFiles())
			)
			{
				$arComponentResult["WEB_DAV_FILE_FIELD_NAME"] = "UF_SONET_LOG_DOC";

				// upload to storage
				$arResultFile = array();

				foreach($fields["FILES"] as $tmp)
				{
					$arFile = CRestUtil::saveFile($tmp);

					if(is_array($arFile))
					{
						$file = $folder->uploadFile(
							$arFile, // file array
							array(
								'NAME' => $arFile["name"],
								'CREATED_BY' => $USER->getID()
							),
							array(),
							true
						);

						if ($file)
						{
							$arResultFile[] = \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$file->getId();
						}
					}
				}

				if (!empty($arResultFile))
				{
					$arPOST['UF_SONET_LOG_DOC'] = $arResultFile;
				}
			}

			$res = CCrmLiveFeedComponent::ProcessLogEventEditPOST($arPOST, $entityTypeID, $entityID, $arComponentResult);

			if(is_array($res))
			{
				throw new RestException(implode(", ", $res));
			}

			return $res;
		}

		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmEntityBindingProxy extends CCrmRestProxyBase
{
	protected $ownerEntityTypeID = CCrmOwnerType::Undefined;
	protected $entityTypeID = CCrmOwnerType::Undefined;
	protected $FIELDS_INFO = null;
	function __construct($ownerEntityTypeID, $entityTypeID)
	{
		$this->setOwnerEntityTypeID($ownerEntityTypeID);
		$this->setEntityTypeID($entityTypeID);
	}
	public function setOwnerEntityTypeID($entityTypeID)
	{
		if(is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new RestException("Parameter 'entityTypeID' is not defined");
		}

		if(
			$entityTypeID !== CCrmOwnerType::Deal
			&& $entityTypeID !== CCrmOwnerType::Lead
			&& $entityTypeID !== CCrmOwnerType::Quote
			&& $entityTypeID !== CCrmOwnerType::Contact
			&& $entityTypeID !== CCrmOwnerType::Company
		)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

			throw new RestException("The owner entity type '{$entityTypeName}' is not supported in current context.");
		}

		$this->ownerEntityTypeID = $entityTypeID;
	}
	public function getOwnerEntityTypeID()
	{
		return $this->ownerEntityTypeID;
	}
	public function setEntityTypeID($entityTypeID)
	{
		if(is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new RestException("Parameter 'entityTypeID' is not defined");
		}

		if($entityTypeID !== CCrmOwnerType::Company && $entityTypeID !== CCrmOwnerType::Contact)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			throw new RestException("The entity type '{$entityTypeName}' is not supported in current context.");
		}

		$this->entityTypeID = $entityTypeID;
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'SORT' => array(
					'TYPE' => 'integer',
					'CAPTION' => Loc::getMessage('CRM_REST_ENTITY_BINDING_FIELD_SORT')
				),
				'IS_PRIMARY' => array(
					'TYPE' => 'char',
					'CAPTION' => Loc::getMessage('CRM_REST_ENTITY_BINDING_FIELD_IS_PRIMARY')
				)
			);
			$entityFieldName = EntityBinding::resolveEntityFieldName($this->entityTypeID);
			if($entityFieldName !== '')
			{
				$this->FIELDS_INFO[$entityFieldName] = array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required),
					'CAPTION' => \CCrmOwnerType::GetDescription($this->entityTypeID)
				);
			}
			else
			{
				$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
				throw new RestException("The entity type '{$entityTypeName}' is not supported in current context.");
			}
		}
		return $this->FIELDS_INFO;
	}

	public function addItem($ownerEntityID, $fields)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException("The parameter 'ownerEntityID' is invalid or not defined.");
		}

		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		$fieldInfos = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldInfos, array());

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if(
			$this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//DEAL -> CONTACT
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied.' : 'Not found.'
				);
			}
			elseif(!CCrmDeal::CheckUpdatePermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			if(!CCrmDeal::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = DealContactTable::getDealBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Contact, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, array($fields));
			if(EntityBinding::isPrimary($fields) || EntityBinding::findPrimaryBinding($effectiveItems) === null)
			{
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Contact, $entityID);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				DealContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Lead
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			// LEAD -> CONTACT
			if(!CCrmLead::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmLead::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = LeadContactTable::getLeadBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Contact, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, [$fields]);
			if(EntityBinding::isPrimary($fields) || EntityBinding::findPrimaryBinding($effectiveItems) === null)
			{
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Contact, $entityID);
			}

			$removedItems = [];
			$addedItems = [];

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				LeadContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//QUOTE -> CONTACT
			if(!CCrmQuote::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmQuote::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = QuoteContactTable::getQuoteBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Contact, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, array($fields));
			if(EntityBinding::isPrimary($fields) || EntityBinding::findPrimaryBinding($effectiveItems) === null)
			{
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Contact, $entityID);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				QuoteContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company
		)
		{
			//CONTACT -> COMPANY
			if(!CCrmContact::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmContact::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Company, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Company, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = ContactCompanyTable::getContactBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Company, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, array($fields));
			if(EntityBinding::isPrimary($fields) || EntityBinding::findPrimaryBinding($effectiveItems) === null)
			{
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Company, $entityID);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Company,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindCompanies($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Company
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//COMPANY -> CONTACT
			if(!CCrmCompany::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmCompany::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = ContactCompanyTable::getCompanyBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Contact, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, array($fields));
			if(EntityBinding::isPrimary($fields) || EntityBinding::findPrimaryBinding($effectiveItems) === null)
			{
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Contact, $entityID);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);

		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}

	public function deleteItem($ownerEntityID, $fields)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException("The parameter 'ownerEntityID' is invalid or not defined.");
		}

		if(!is_array($fields))
		{
			throw new RestException("The parameter 'item' must be array.");
		}

		$fieldInfos = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldInfos, array());

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if(
			$this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//DEAL -> CONTACT
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied.' : 'Not found.'
				);
			}
			elseif(!CCrmDeal::CheckUpdatePermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			if(!CCrmDeal::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = DealContactTable::getDealBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Contact, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				DealContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			if(!empty($removedItems))
			{
				DealContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Lead
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			// LEAD -> CONTACT
			if(!CCrmLead::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmLead::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = LeadContactTable::getLeadBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Contact, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = [];
			$addedItems = [];

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				LeadContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			if(!empty($removedItems))
			{
				LeadContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//QUOTE -> CONTACT
			if(!CCrmQuote::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmQuote::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = QuoteContactTable::getQuoteBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Contact, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				QuoteContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			if(!empty($removedItems))
			{
				QuoteContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company
		)
		{
			//CONTACT -> COMPANY
			if(!CCrmContact::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmContact::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Company, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Company, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = ContactCompanyTable::getContactBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Company, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Company,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindCompanies($ownerEntityID, $addedItems);
			}

			if(!empty($removedItems))
			{
				ContactCompanyTable::unbindCompanies($ownerEntityID, $removedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Company
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//COMPANY -> CONTACT
			if(!CCrmCompany::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmCompany::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = ContactCompanyTable::getCompanyBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Contact, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				ContactCompanyTable::unbindContacts($ownerEntityID, $removedItems);
			}

			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);

		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}

	public function getItems($ownerEntityID)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException('The parameter ownerEntityID is invalid or not defined.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if(
			$this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found'
				);
			}
			elseif(!CCrmDeal::CheckReadPermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			return DealContactTable::getDealBindings($ownerEntityID);
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Lead
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			if(!CCrmLead::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			return LeadContactTable::getLeadBindings($ownerEntityID);
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			if(!CCrmQuote::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			return QuoteContactTable::getQuoteBindings($ownerEntityID);
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company
		)
		{
			if(!CCrmContact::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			return ContactCompanyTable::getContactBindings($ownerEntityID);
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Company
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			if(!CCrmCompany::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			return ContactCompanyTable::getCompanyBindings($ownerEntityID);
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);

		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}

	public function setItems($ownerEntityID, $items)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException('The parameter ownerEntityID is invalid or not defined.');
		}

		if(!is_array($items))
		{
			throw new RestException('The parameter items must be array.');
		}

		$effectiveItems = array();
		$fieldInfos = $this->getFieldsInfo();
		for($i = 0, $l = count($items); $i < $l; $i++)
		{
			$item = $items[$i];
			$this->internalizeFields($item, $fieldInfos, array());
			$effectiveItems[] = $item;
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if(
			$this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//DEAL -> CONTACT
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied.' : 'Not found.'
				);
			}
			elseif(!CCrmDeal::CheckUpdatePermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			if(!CCrmDeal::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Contact, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				DealContactTable::getDealBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				DealContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				DealContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Lead
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			// LEAD -> CONTACT
			if(!CCrmLead::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmLead::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Contact, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = [];
			$addedItems = [];

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				LeadContactTable::getLeadBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				LeadContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				LeadContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//QUOTE -> CONTACT
			if(!CCrmQuote::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmQuote::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Contact, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				QuoteContactTable::getQuoteBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				QuoteContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				QuoteContactTable::bindContacts($ownerEntityID, $addedItems);
			}
			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company
		)
		{
			//CONTACT -> COMPANY
			if(!CCrmContact::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmContact::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Company, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Company,
				ContactCompanyTable::getContactBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				ContactCompanyTable::unbindCompanies($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindCompanies($ownerEntityID, $addedItems);
			}
			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Company
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			//COMPANY -> CONTACT
			if(!CCrmCompany::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmCompany::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Contact, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				ContactCompanyTable::getCompanyBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				ContactCompanyTable::unbindContacts($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindContacts($ownerEntityID, $addedItems);
			}
			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);

		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}

	public function deleteItems($ownerEntityID)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException('The parameter ownerEntityID is invalid or not defined.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if(
			$this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found'
				);
			}
			elseif(!CCrmDeal::CheckReadPermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			DealContactTable::unbindAllContacts($ownerEntityID);

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Lead
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			if(!CCrmLead::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			LeadContactTable::unbindAllContacts($ownerEntityID);

			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			if(!CCrmQuote::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			QuoteContactTable::unbindAllContacts($ownerEntityID);
			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company
		)
		{
			if(!CCrmContact::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			ContactCompanyTable::unbindAllCompanies($ownerEntityID);
			return true;
		}
		elseif(
			$this->ownerEntityTypeID === CCrmOwnerType::Company
			&& $this->entityTypeID === CCrmOwnerType::Contact
		)
		{
			if(!CCrmCompany::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			ContactCompanyTable::unbindAllContacts($ownerEntityID);
			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);

		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'FIELDS')
		{
			return $this->getFields();
		}
		elseif($name === 'ADD')
		{
			return $this->addItem(
				CCrmRestHelper::resolveEntityID($arParams),
				CCrmRestHelper::resolveArrayParam($arParams, 'fields')
			);
		}
		elseif($name === 'DELETE')
		{
			return $this->deleteItem(
				CCrmRestHelper::resolveEntityID($arParams),
				CCrmRestHelper::resolveArrayParam($arParams, 'fields')
			);
		}
		elseif($name === 'ITEMS')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return $this->getItems(CCrmRestHelper::resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				return $this->setItems(
					CCrmRestHelper::resolveEntityID($arParams),
					CCrmRestHelper::resolveArrayParam($arParams, 'items')
				);
			}
			elseif($nameSuffix === 'DELETE')
			{
				return $this->deleteItems(CCrmRestHelper::resolveEntityID($arParams));
			}
		}
		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmUserFieldRestProxy extends UserFieldProxy implements ICrmRestProxy
{
	private $ownerTypeID = CCrmOwnerType::Undefined;
	/** @var CRestServer  */
	private $server = null;

	function __construct($ownerTypeID, \CUser $user = null)
	{
		$this->ownerTypeID = CCrmOwnerType::IsDefined($ownerTypeID) ? $ownerTypeID : CCrmOwnerType::Undefined;
		parent::__construct(CCrmOwnerType::ResolveUserFieldEntityID($this->ownerTypeID), $user);
		$this->setNamePrefix('crm');
	}
	public function getOwnerTypeID()
	{
		return $this->ownerTypeID;
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'FIELDS')
		{
			return self::getFields();
		}
		elseif($name === 'TYPES' && method_exists('\Bitrix\Rest\UserFieldProxy', 'getTypes'))
		{
			return self::getTypes(self::getServer());
		}
		elseif($name === 'SETTINGS')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$type = CCrmRestHelper::resolveParam($arParams, 'type', '');
				if($type === '')
				{
					throw new RestException("Parameter 'type' is not specified or empty.");
				}

				return self::getSettingsFields($type);
			}
		}
		elseif($name === 'ENUMERATION')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return self::getEnumerationElementFields();
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function isAuthorizedUser()
	{
		if($this->isAuthorizedUser === null)
		{
			/**@var \CCrmPerms $userPermissions @**/
			$userPermissions = CCrmPerms::GetUserPermissions($this->user->GetID());
			$this->isAuthorizedUser = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		}
		return $this->isAuthorizedUser;
	}
	protected function checkCreatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkReadPermission()
	{
		//Check only entity read permission to allow non-administrator users to call crm.*.userfield.get, crm.*.userfield.list
		return \Bitrix\Crm\Security\EntityAuthorization::checkReadPermission(
			$this->ownerTypeID,
			0,
			CCrmPerms::GetUserPermissions($this->user->GetID())
		);
	}
	protected function checkUpdatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkDeletePermission()
	{
		return $this->isAuthorizedUser();
	}

	public function get($ID)
	{
		$ufId = (int)$ID;
		if ($ufId > 0 && $this->entityID === CCrmInvoice::GetUserFieldEntityID() && parent::checkReadPermission())
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			$entity = new \CUserTypeEntity();
			$result = $entity->GetByID($ID);
			if (is_array($result) && isset($result['FIELD_NAME'])
				&& isset($invoiceReservedFields[$result['FIELD_NAME']]))
			{
				throw new RestException("The entity with ID '{$ID}' is not found.", RestException::ERROR_NOT_FOUND);
			}
		}

		return parent::get($ID);
	}
	public function getList(array $order, array $filter)
	{
		$result = array();
		$tmpResult = parent::getList($order, $filter);

		if ($this->entityID === CCrmInvoice::GetUserFieldEntityID() && is_array($tmpResult) && !empty($tmpResult))
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			foreach ($tmpResult as $index => $fieldInfo)
			{
				if ($index !== 'total'
					&& isset($fieldInfo['FIELD_NAME'])
					&& !isset($invoiceReservedFields[$fieldInfo['FIELD_NAME']]))
				{
					$result[] = $fieldInfo;
				}
			}

			$result['total'] = count($result);
		}
		else
		{
			$result = $tmpResult;
		}

		return $result;
	}
	public function update($ID, array $fields)
	{
		if ($ID > 0 && $this->entityID === CCrmInvoice::GetUserFieldEntityID() && parent::checkUpdatePermission())
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			$entity = new \CUserTypeEntity();
			$result = $entity->GetByID($ID);
			if (is_array($result) && isset($result['FIELD_NAME'])
				&& isset($invoiceReservedFields[$result['FIELD_NAME']]))
			{
				throw new RestException("The entity with ID '{$ID}' is not found.", RestException::ERROR_NOT_FOUND);
			}
		}

		return parent::update($ID, $fields);
	}
	public function delete($ID)
	{
		if ($ID > 0 && $this->entityID === CCrmInvoice::GetUserFieldEntityID() && parent::checkDeletePermission())
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			$entity = new \CUserTypeEntity();
			$result = $entity->GetByID($ID);
			if (is_array($result) && isset($result['FIELD_NAME'])
				&& isset($invoiceReservedFields[$result['FIELD_NAME']]))
			{
				throw new RestException("The entity with ID '{$ID}' is not found.", RestException::ERROR_NOT_FOUND);
			}
		}

		return parent::delete($ID);
	}
}

class CCrmQuoteRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Quote;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmQuote(true);
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmQuote::GetFieldsInfo();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = CCrmQuote::GetFieldCaption($code);
			}
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmQuote::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$defaultRequisiteLinkParams = Requisite\EntityLink::determineRequisiteLinkBeforeSave(
			CCrmOwnerType::Quote, 0, Requisite\EntityLink::ENTITY_OPERATION_ADD, $fields
		);

		$entity = self::getEntity();
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		if ($result > 0)
		{
			Requisite\EntityLink::register(
				CCrmOwnerType::Quote, (int)$result,
				$defaultRequisiteLinkParams['REQUISITE_ID'],
				$defaultRequisiteLinkParams['BANK_DETAIL_ID'],
				$defaultRequisiteLinkParams['MC_REQUISITE_ID'],
				$defaultRequisiteLinkParams['MC_BANK_DETAIL_ID']
			);

			self::traceEntity(\CCrmOwnerType::Quote, $result, $fields);
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmQuote::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmQuote::GetList(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result = \Bitrix\Crm\Entity\CommentsHelper::prepareFieldsFromCompatibleRestToRead(
			$this->getOwnerTypeID(),
			$ID,
			$result,
		);

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmQuote::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = $ufData['VALUE'] ?? '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmQuote::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STATUS_ID']))
			{
				$order['STATUS_SORT'] = $order['STATUS_ID'];
				unset($order['STATUS_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STATUS_SORT'));
			}
		}

		return CCrmQuote::GetList($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmQuote::Exists($ID))
		{
			$errors[] = 'Quote is not found';
			return false;
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = mb_strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}
		}

		$defaultRequisiteLinkParams = Requisite\EntityLink::determineRequisiteLinkBeforeSave(
			CCrmOwnerType::Quote, $ID, Requisite\EntityLink::ENTITY_OPERATION_UPDATE, $fields
		);

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		if ($result === true)
		{
			Requisite\EntityLink::register(
				CCrmOwnerType::Quote, (int)$ID,
				$defaultRequisiteLinkParams['REQUISITE_ID'],
				$defaultRequisiteLinkParams['BANK_DETAIL_ID'],
				$defaultRequisiteLinkParams['MC_REQUISITE_ID'],
				$defaultRequisiteLinkParams['MC_BANK_DETAIL_ID']
			);

			self::traceEntity(\CCrmOwnerType::Quote, $ID, $fields, true);
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmQuote::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmQuote::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		global $APPLICATION;

		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmQuote::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmQuote::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$rows = array_values($rows);
		$actualRows = array();
		for($i = 0, $qty = count($rows); $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		$result = CCrmQuote::SaveProductRows($ID, $actualRows, true, true, true);
		if(!$result)
		{
			$exp = $APPLICATION->GetException();
			if($exp)
			{
				throw new RestException($exp->GetString());
			}
		}
		return $result;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		elseif($name === 'CONTACT')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Quote, CCrmOwnerType::Contact);
			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmQuoteRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmQuoteAdd'] = self::createEventInfo('crm', 'OnAfterCrmQuoteAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteUpdate'] = self::createEventInfo('crm', 'OnAfterCrmQuoteUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteDelete'] = self::createEventInfo('crm', 'OnAfterCrmQuoteDelete', $callback);

		// user field events
		$bindings[CRestUtil::EVENTS]['onCrmQuoteUserFieldAdd'] = self::createEventInfo('crm', 'OnAfterCrmRestQuoteUserFieldAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteUserFieldUpdate'] = self::createEventInfo('crm', 'OnAfterCrmRestQuoteUserFieldUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteUserFieldDelete'] = self::createEventInfo('crm', 'OnAfterCrmRestQuoteUserFieldDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteUserFieldSetEnumValues'] = self::createEventInfo('crm', 'OnAfterCrmRestQuoteUserFieldSetEnumValues', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Quote, $arParams, $arHandler);
	}
}

class CCrmItemRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);

		if($name === 'DETAILS')
		{
			$entityTypeId = $arParams['entityTypeId'] ?? $arParams['ENTITY_TYPE_ID'] ?? 0;

			$editorRequestDetails = $nameDetails;
			$editorRequestName = array_shift($editorRequestDetails);
			$entityEditorProxy = new CCrmEntityEditorRestProxy($entityTypeId);

			return $entityEditorProxy->processMethodRequest($editorRequestName, $editorRequestDetails, $arParams, $nav, $server);
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmInvoiceRestProxy extends CCrmRestProxyBase
{
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Invoice;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmInvoiceRestProxy', 'processEvent');

		// user field events
		$bindings[CRestUtil::EVENTS]['onCrmInvoiceUserFieldAdd'] = self::createEventInfo('crm', 'OnAfterCrmRestInvoiceUserFieldAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmInvoiceUserFieldUpdate'] = self::createEventInfo('crm', 'OnAfterCrmRestInvoiceUserFieldUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmInvoiceUserFieldDelete'] = self::createEventInfo('crm', 'OnAfterCrmRestInvoiceUserFieldDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmInvoiceUserFieldSetEnumValues'] = self::createEventInfo('crm', 'OnAfterCrmRestInvoiceUserFieldSetEnumValues', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Invoice, $arParams, $arHandler);
	}
}

class CCrmRequisitePresetRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityPreset();
		}

		return self::$ENTITY;
	}

	protected function getCountriesInfo()
	{
		$result = array();

		$countriesInfo = EntityPreset::getCountriesInfo();

		foreach (EntityRequisite::getAllowedRqFieldCountries() as $countryId)
		{
			$countryInfo = is_array($countriesInfo[$countryId]) ? $countriesInfo[$countryId] : array();
			$result[] = array(
					'ID' => $countryId,
					'CODE' => $countryInfo['CODE'] ?? '',
					'TITLE' => $countryInfo['TITLE'] ?? ''
			);
		}

		return $result;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityPreset::getFieldsInfo();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = EntityPreset::getFieldCaption($code);
			}
		}

		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));

		if(!$this->isValidID($entityTypeID) || $entityTypeID !== EntityPreset::Requisite)
		{
			$errors[] = 'ENTITY_TYPE_ID is not defined or invalid.';
			return false;
		}

		if(!EntityPreset::checkCreatePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!$this->checkFields($fields, $sError))
		{
			$errors[] = $sError;
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->add($fields);

		if (is_object($result))
		{
			if($result->isSuccess())
			{
				$result = $result->getId();
			}
			else
			{
				$errors = $result->getErrors();
				$result = false;
			}
		}
		else
		{
			$errors[] = 'Error when adding preset.';
			$result = false;
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$ID}' is not found";
			return false;
		}

		return $r;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$filter['=ENTITY_TYPE_ID'] = EntityPreset::Requisite;

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(!is_array($select))
			$select = array();

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
			array(
				'order' => $order,
				'filter' => $filter,
				'select' => $select,
				'offset' => $offset,
				'count_total' => true
			)
		);

		if (is_object($result))
		{
			$dbResult = new CDBResult($result);
		}
		else
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
		}
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);

		if(!$this->isValidID($entityTypeID) || $entityTypeID !== EntityPreset::Requisite)
		{
			$errors[] = "ENTITY_TYPE_ID is not defined or invalid.";
			return false;
		}

		if(!EntityPreset::checkUpdatePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->update($ID, $fields);

		if($result->isSuccess())
		{
			return true;
		}
		else
		{
			$errors = $result->getErrors();
			return false;
		}

		$errors[] = 'Error when updating preset.';
		return false;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);

		if(!$this->isValidID($entityTypeID) || $entityTypeID !== EntityPreset::Requisite)
		{
			$errors[] = 'ENTITY_TYPE_ID is not defined or invalid.';
			return false;
		}

		if(!EntityPreset::checkDeletePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$result = $entity->delete($ID);

		if (is_object($result))
		{
			if($result->isSuccess())
			{
				$result = true;
			}
			else
			{
				$errors = $result->getErrors();
				$result = false;
			}
		}
		else
		{
			$errors[] = 'Error when deleting preset.';
			$result = false;
		}

		return $result;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if ($name === 'COUNTRIES')
		{
			return $this->getCountriesInfo();
		}
		else
		{
			return parent::processMethodRequest($name, '', $arParams, $nav, $server);
		}
	}

	protected function checkFields($fields, &$errors)
	{
		if (isset($fields['COUNTRY_ID']))
		{
			$countryId = intval($fields['COUNTRY_ID']);
			if (!in_array($countryId, EntityRequisite::getAllowedRqFieldCountries(), true))
			{
				$errors = 'Invalid value of field: COUNTRY_ID.';
				return false;
			}
		}

		return true;
	}
	protected function getById($ID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array('filter'=>array('ID' => $ID)));
		return $result->fetch();
	}
}

class CCrmRequisitePresetFieldRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private static $ENTITY_OWNER = null;
	private $FIELDS_INFO = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityPreset();
		}

		return self::$ENTITY;
	}
	private static function getOwnerEntity()
	{
		if(!self::$ENTITY_OWNER)
		{
			self::$ENTITY_OWNER = new EntityRequisite();
		}

		return self::$ENTITY_OWNER;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityPreset::getSettingsFieldsRestInfo();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = EntityPreset::getFieldCaption($code);
			}
		}

		return $this->FIELDS_INFO;
	}
	protected function innerAddField($presetId, &$fields, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}

		if(!EntityPreset::checkCreatePermissionOwnerEntity($r['ENTITY_TYPE_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$preset = self::getEntity();
		$requisite = self::getOwnerEntity();

		if (!$this->checkFields('ADD', $presetId, $fields, $sError))
		{
			$errors[] = $sError;
			return false;
		}

		$presetData = $preset->getById($presetId);
		$presetCountryId = isset($presetData['COUNTRY_ID']) ? (int)$presetData['COUNTRY_ID'] : 0;

		$fieldsTitles = $requisite->getFieldsTitles($presetCountryId);

		$addFields = $fields;
		if (isset($addFields['FIELD_TITLE']) && isset($addFields['FIELD_NAME']))
		{
			if (isset($fieldsTitles[$addFields['FIELD_NAME']]))
			{
				$title = $fieldsTitles[$addFields['FIELD_NAME']];
				$origFieldTitle = empty($title) ? $addFields['FIELD_NAME'] : $title;
				if ($addFields['FIELD_TITLE'] === $origFieldTitle)
					$addFields['FIELD_TITLE'] = '';
			}
			unset($title);
		}
		if (!is_array($presetData['SETTINGS']))
			$presetData['SETTINGS'] = array();

		$id = $preset->settingsAddField($presetData['SETTINGS'], $addFields);
		if ($id > 0)
		{
			$result = $preset->update($presetId, array('SETTINGS' => $presetData['SETTINGS']));

			if (is_object($result))
			{
				if($result->isSuccess())
				{
					return $id;
				}
				else
				{
					$errors = $result->getErrors();
					return false;
				}
			}
			else
			{
				$errors[] = 'Added preset field. Error when updated Requisite';
				return false;
			}
		}
		else
		{
			$errors[] = 'Error when adding preset field.';
			return false;
		}
	}
	protected function innerGetFields($presetId, $id, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}
		if(!is_array($r['SETTINGS']))
		{
			$r['SETTINGS'] = array();
		}

		$preset = self::getEntity();
		$result = $this->getByFieldId($id, $preset->settingsGetFields($r['SETTINGS']), array(
				'COUNTRY_ID' => $r['COUNTRY_ID']
		));
		if(empty($result))
		{
			$errors[] = "The PresetField with ID '{$id}' is not found";
			return false;
		}

		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $result;
	}
	protected function innerGetListFields($presetId, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}
		if(!is_array($r['SETTINGS']))
		{
			$r['SETTINGS'] = array();
		}

		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		return $entity->settingsGetFields($r['SETTINGS']);
	}
	protected function innerUpdateFields($presetId, $id, $fields, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}

		$preset = self::getEntity();

		$presetField = $this->getByFieldId($id, $preset->settingsGetFields($r['SETTINGS']), array(
				'COUNTRY_ID' => $r['COUNTRY_ID']
		));
		if(empty($presetField))
		{
			$errors[] = "The PresetField with ID '{$id}' is not found";
			return false;
		}

		if(!EntityPreset::checkUpdatePermissionOwnerEntity($r['ENTITY_TYPE_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$fields['ID'] = $id;
		if (!$this->checkFields('UPD', $presetId, $fields, $sError))
		{
			$errors[] = $sError;
			return false;
		}

		$presetSettings = $r['SETTINGS'];
		if($preset->settingsUpdateField($presetSettings, $fields))
		{
			$result = $preset->update($presetId, array('SETTINGS' => $presetSettings));
			if (is_object($result))
			{
				if($result->isSuccess())
				{
					return $id;
				}
				else
				{
					$errors = $result->getErrors();
					return false;
				}
			}
			else
			{
				$errors[] = 'Update preset field. Error when updated Requisite';
				return false;
			}
		}
		else
		{
			$errors[] = 'Error when update preset field.';
			return false;
		}
	}
	protected function innerDeleteField($presetId, $id, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}

		$preset = self::getEntity();

		$presetField = $this->getByFieldId($id, $preset->settingsGetFields($r['SETTINGS']), array(
				'COUNTRY_ID' => $r['COUNTRY_ID']
		));
		if(empty($presetField))
		{
			$errors[] = "The PresetField with ID '{$id}' is not found";
			return false;
		}

		if(!EntityPreset::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$presetSettings = $r['SETTINGS'];
		if($preset->settingsDeleteField($presetSettings, $id))
		{
			$result = $preset->update($presetId, array('SETTINGS' => $presetSettings));

			if (is_object($result))
			{
				if($result->isSuccess())
				{
					return true;
				}
				else
				{
					$errors = $result->getErrors();
					return false;
				}
			}
			else
			{
				$errors[] = 'Deleted preset field. Error when updated Requisite';
				return false;
			}
		}

		$errors[] = 'Error when deleted preset field.';
		return false;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$errors = array();
		$name = mb_strtoupper($name);

		$presetId = 0;
		if($name !== 'FIELDS')
		{
			$preset = $this->resolveArrayParam($arParams, 'preset');
			if(!is_array($preset))
			{
				throw new RestException("Parameter 'PRESET' is not specified or incorrect.");
			}

			$presetId = intval($this->resolveParam($preset, 'ID'));
			if(!$this->checkEntityID($presetId))
			{
				throw new RestException('PRESET[ID] is not defined or invalid.');
			}
		}

		$fieldsInfo = $this->getFieldsInfo();
		if($name === 'AVAILABLETOADD')
		{
			$preset = new EntityPreset();
			$result = $preset->getSettingsFieldsAvailableToAdd(EntityPreset::Requisite, $presetId);
			if (is_object($result))
			{
				if($result->isSuccess())
				{
					$result = $result->getData();
				}
				else
				{
					throw new RestException(implode("\n", $result->getErrors()));
				}
			}
			else
			{
				throw new RestException('Error when getting fields.');
			}

			return $result;
		}
		elseif($name === 'FIELDS')
		{
			return parent::processMethodRequest($name, '', $arParams, $nav, $server);
		}
		elseif($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			$this->internalizeFields($fields, $fieldsInfo, array());

			$errors = array();
			$result = $this->innerAddField($presetId, $fields, $errors);

			if($this->isValidID($result))
			{
				return $result;
			}

			if(empty($errors))
			{
				$errors[] = "Failed to add. General error.";
			}

			throw new RestException(implode("\n", $errors));

		}
		elseif($name === 'GET')
		{
			$id = intval($this->resolveParam($arParams, 'ID'));

			if(!$this->isValidID($id))
			{
				throw new RestException('ID is not defined or invalid.');
			}

			$errors = array();
			$result = $this->innerGetFields($presetId, $id, $errors);

			if(is_array($result))
			{
				$this->externalizeFields($result, $fieldsInfo);
				return $result;
			}

			if(empty($errors))
			{
				$errors[] = "Failed to get. General error.";
			}

			throw new RestException(implode("\n", $errors));
		}
		elseif($name === 'LIST')
		{
			$errors = array();
			$result = $this->innerGetListFields($presetId, $errors);

			if(is_array($result))
			{
				return $result;
			}

			if(empty($errors))
			{
				$errors[] = "Failed to get list. General error.";
			}

			throw new RestException(implode("\n", $errors));
		}
		elseif($name === 'UPDATE')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			$id = intval($this->resolveParam($arParams, 'ID'));

			if(!$this->checkEntityID($id))
			{
				throw new RestException('ID is not defined or invalid.');
			}

			$this->internalizeFields($fields, $fieldsInfo, array());

			$errors = array();
			$result = $this->innerUpdateFields($presetId, $id, $fields, $errors);
			if($this->isValidID($result))
			{
				return true;
			}

			if(empty($errors))
			{
				$errors[] = "Failed to update. General error.";
			}

			throw new RestException(implode("\n", $errors));
		}
		elseif($name === 'DELETE')
		{
			$id = intval($this->resolveParam($arParams, 'ID'));

			if(!$this->isValidID($id))
			{
				throw new RestException('ID is not defined or invalid.');
			}

			$result = $this->innerDeleteField($presetId, $id, $errors);

			if($result)
			{
				return true;
			}

			if(empty($errors))
			{
				$errors[] = "Failed to delete. General error.";
			}

			throw new RestException(implode("\n", $errors));
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	protected function checkFields($action, $presetId, $fields, &$errors)
	{
		if (!isset($fields['FIELD_NAME']))
		{
			$errors = 'FIELD_NAME is not specified.';
			return false;
		}
		elseif($action === 'ADD')
		{
			$entity = self::getEntity();

			$fieldsAvailableToAdd = array();
			$result = $entity->getSettingsFieldsAvailableToAdd(EntityPreset::Requisite, $presetId);
			if ($result->isSuccess())
			{
				$fieldsAvailableToAdd = $result->getData();
				if (!is_array($fieldsAvailableToAdd))
					$fieldsAvailableToAdd = array();
			}

			if (!in_array($fields['FIELD_NAME'], $fieldsAvailableToAdd, true))
			{
				$errors = 'The field '.
						(isset($fields['FIELD_NAME']) ? "'".$fields['FIELD_NAME']."' " : "").
						'can not be added.';
				return false;
			}
		}
		return true;
	}
	protected function exists($ID, $entityTypeID)
	{
		$entity = self::getEntity();

		$res = $entity->getList(array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => array(
						'=ENTITY_TYPE_ID' => $entityTypeID,
						'=ID' => (int)$ID
				),
				'select' => array('ID', 'ENTITY_TYPE_ID', 'SETTINGS', 'COUNTRY_ID'),
				'limit' => 1
		));

		return $res->fetch();
	}
	protected function getByFieldId($id, $fields, $option = array())
	{
		$requisite = self::getOwnerEntity();

		$result = array();
		$presetCountryId = isset($option['COUNTRY_ID']) ? (int)$option['COUNTRY_ID'] : 0;
		$fieldsTitles = $requisite->getFieldsTitles($presetCountryId);
		foreach ($fields as $fieldInfo)
		{
			if (isset($fieldInfo['ID']) && $id === (int)$fieldInfo['ID'])
			{
				$result = $fieldInfo;
				if($result['FIELD_TITLE'] == '')
				{
					if (isset($fieldInfo['FIELD_NAME']) && !empty($fieldInfo['FIELD_NAME']))
					{
						if (isset($fieldsTitles[$fieldInfo['FIELD_NAME']]))
						{
							$title = $fieldsTitles[$fieldInfo['FIELD_NAME']];
							if (!empty($title))
								$result['FIELD_TITLE'] = $title;
						}
					}
				}
				break;
			}
		}
		return $result;
	}
}

class CCrmRequisiteRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Requisite;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityRequisite();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityRequisite::getFieldsInfo();
			$titles = EntityRequisite::getSingleInstance()->getFieldsTitles();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = $titles[$code];
			}
			self::prepareUserFieldsInfo($this->FIELDS_INFO, EntityRequisite::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
		$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));
		$presetID = intval($this->resolveParam($fields, 'PRESET_ID'));

		if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
		{
			$errors[] = 'ENTITY_TYPE_ID is not defined or invalid.';
			return false;
		}
		if(!$this->checkEntityID($entityID))
		{
			$errors[] = 'ENTITY_ID is not defined or invalid.';
			return false;
		}
		if(!$this->checkEntityID($presetID))
		{
			$errors[] = 'PRESET_ID is not defined or invalid.';
			return false;
		}

		if(!EntityRequisite::checkCreatePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!$this->entityExists($entityTypeID, $entityID))
		{
			$errors[] = 'Entity not found.';
			return false;
		}

		$options = [];
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}

		$entity = self::getEntity();

		$result = $entity->add($fields, $options);

		if(!$result->isSuccess())
		{
			$errors = $result->getErrors();
			return false;
		}
		else
		{
			CCrmEntityHelper::NormalizeUserFields($fields, EntityRequisite::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(EntityRequisite::$sUFEntityID, $result->getId(), $fields);

			if(self::isBizProcEnabled())
			{
				CCrmBizProcHelper::AutoStartWorkflows(
						CCrmOwnerType::Requisite,
						$result->getId(),
						CCrmBizProcEventType::Create,
						$errors
				);
			}
		}

		return $result->getId();
	}
	protected function innerGet($ID, &$errors)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Requisite with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityRequisite::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $r;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$permissionOwner = false;

		if(!isset($filter['ENTITY_TYPE_ID']))
		{
			if(EntityRequisite::checkReadPermissionOwnerEntity())
			{
				$permissionOwner = true;
				//Required for prevent selection of the suspended entities (SuspendedContact, SuspendedCompany)
				$filter['@ENTITY_TYPE_ID'] = [CCrmOwnerType::Company, CCrmOwnerType::Contact];
			}
			else
			{
				if(EntityRequisite::checkReadPermissionOwnerEntity(CCrmOwnerType::Company))
				{
					$permissionOwner = true;
					$filter['ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
				}
				elseif(EntityRequisite::checkReadPermissionOwnerEntity(CCrmOwnerType::Contact))
				{
					$permissionOwner = true;
					$filter['ENTITY_TYPE_ID'] = CCrmOwnerType::Contact;
				}
			}
		}
		else
		{
			$permissionOwner = EntityRequisite::checkReadPermissionOwnerEntity($filter['ENTITY_TYPE_ID']);
		}

		if($permissionOwner == false)
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(!is_array($select))
			$select = array();

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
				array(
						'order' => $order,
						'filter' => $filter,
						'select' => $select,
						'offset' => $offset,
						'count_total' => true
				)
		);

		$dbResult = new CDBResult($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Requisite with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);
		$entityID = intval($r['ENTITY_ID']);
		$presetID = intval($r['PRESET_ID']);

		if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
		{
			$errors[] = "ENTITY_TYPE_ID is not defined or invalid.";
			return false;
		}
		if(!$this->checkEntityID($entityID))
		{
			$errors[] = "ENTITY_ID is not defined or invalid.";
			return false;
		}
		if(!$this->checkEntityID($presetID))
		{
			$errors[] = "PRESET_ID is not defined or invalid.";
			return false;
		}

		if(!EntityRequisite::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			if (!is_array($params))
			{
				$params = [];
			}
			$params['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}

		$entity = self::getEntity();

		$result = $entity->update($ID, $fields, $params);
		if(!$result->isSuccess())
		{
			$errors = $result->getErrors();
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmEntityHelper::NormalizeUserFields($fields, EntityRequisite::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(EntityRequisite::$sUFEntityID, $ID, $fields);

			CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Company,
					$result->getId(),
					CCrmBizProcEventType::Edit,
					$errors
			);
		}
		return $result->isSuccess();
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Requisite with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityRequisite::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->delete($ID);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
		}

		return $result->isSuccess();
	}

	protected function getById($ID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array('filter'=>array('ID' => $ID)));
		return $result->fetch();
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);

		if($name === 'BANKDETAIL')
		{
			$name = array_shift($nameDetails);
			$roxy = new CCrmRequisiteBankDetailRestProxy();
			return $roxy->processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}
		elseif($name === 'PRESET')
		{
			$name = array_shift($nameDetails);
			$name = mb_strtoupper($name);
			if($name === 'FIELD')
			{
				$name = array_shift($nameDetails);
				$roxy = new CCrmRequisitePresetFieldRestProxy();
			}
			else
			{
				$roxy = new CCrmRequisitePresetRestProxy();
			}

			return $roxy->processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmRequisiteRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmRequisiteAdd'] = self::createEventInfo('crm', 'OnAfterRequisiteAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmRequisiteUpdate'] = self::createEventInfo('crm', 'OnAfterRequisiteUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmRequisiteDelete'] = self::createEventInfo('crm', 'OnAfterRequisiteDelete', $callback);

		// user field events
		$bindings[CRestUtil::EVENTS]['onCrmRequisiteUserFieldAdd'] = self::createEventInfo('crm', 'OnAfterCrmRestRequisiteUserFieldAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmRequisiteUserFieldUpdate'] = self::createEventInfo('crm', 'OnAfterCrmRestRequisiteUserFieldUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmRequisiteUserFieldDelete'] = self::createEventInfo('crm', 'OnAfterCrmRestRequisiteUserFieldDelete', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmRequisiteUserFieldSetEnumValues'] = self::createEventInfo('crm', 'OnAfterCrmRestRequisiteUserFieldSetEnumValues', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		/* @var Main\Event $event */
		$event = $arParams[0];

		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmrequisiteadd':
			case 'oncrmrequisiteupdate':
				$arParams = array(array("ID" => $event->getParameter('id')));
				break;
			case 'oncrmrequisitedelete':
				$arParams = array($event->getParameter('id'));
				break;
			case 'oncrmrequisiteuserfieldadd':
			case 'oncrmrequisiteuserfieldupdate':
			case 'oncrmrequisiteuserfielddelete':
			case 'oncrmrequisiteuserfieldsetenumvalues':
				$arParams = array($event);
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		return parent::processEntityEvent(CCrmOwnerType::Requisite, $arParams, $arHandler);
	}

	protected function entityExists(int $entityTypeId, int $entityId): bool
	{
		switch ($entityTypeId)
		{
			case CCrmOwnerType::Contact:
				return CCrmContact::Exists($entityId);
			case CCrmOwnerType::Company:
				return CCrmCompany::Exists($entityId);
		}
		return false;
	}
}

class CCrmRequisiteBankDetailRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityBankDetail();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityBankDetail::getFieldsInfo();
			$titles = EntityBankDetail::getSingleInstance()->getFieldsTitles();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = $titles[$code];
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$entityTypeID = $fields['ENTITY_TYPE_ID'] = CCrmOwnerType::Requisite;
		$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));

		if(!$this->checkEntityID($entityID))
		{
			$errors[] = 'ENTITY_ID is not defined or invalid.';
			return false;
		}

		if(!EntityBankDetail::checkCreatePermissionOwnerEntity($entityTypeID, $entityID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$options = [];
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}

		$result = $entity->add($fields, $options);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
			return false;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Requisite,
					$result->getId(),
					CCrmBizProcEventType::Create,
					$errors
			);
		}
		return $result->getId();
	}
	protected function innerGet($ID, &$errors)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The RequisiteBankDetail with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityBankDetail::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $r;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!EntityBankDetail::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(!is_array($select))
			$select = array();

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
				array(
						'order' => $order,
						'filter' => $filter,
						'select' => $select,
						'offset' => $offset,
						'count_total' => true
				)
		);

		$dbResult = new CDBResult($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The RequisiteBankDetail with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);
		$entityID = intval($r['ENTITY_ID']);

		if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
		{
			$errors[] = "ENTITY_TYPE_ID is not defined or invalid.";
			return false;
		}
		if(!$this->checkEntityID($entityID))
		{
			$errors[] = "ENTITY_ID is not defined or invalid.";
			return false;
		}

		if(!EntityBankDetail::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			if (!is_array($params))
			{
				$params = [];
			}
			$params['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}

		$entity = self::getEntity();

		$result = $entity->update($ID, $fields, $params);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Company,
					$result->getId(),
					CCrmBizProcEventType::Edit,
					$errors
			);
		}
		return $result->isSuccess();
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The RequisiteBankDetail with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityBankDetail::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->delete($ID);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
		}

		return $result->isSuccess();
	}

	protected function getById($ID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array('filter'=>array('ID' => $ID)));
		return $result->fetch();
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmRequisiteBankDetailRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmBankDetailAdd'] = self::createEventInfo('crm', 'OnAfterBankDetailAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmBankDetailUpdate'] = self::createEventInfo('crm', 'OnAfterBankDetailUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmBankDetailDelete'] = self::createEventInfo('crm', 'OnAfterBankDetailDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmbankdetailadd':
			case 'oncrmbankdetailupdate':
			case 'oncrmbankdetaildelete':
				/* @var Main\Event $event */
				$event = $arParams[0];
				$ID = $event->getParameter('id');
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID === '')
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
}

class CCrmRequisiteLinkRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = Requisite\EntityLink::getFieldsInfo();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = Requisite\EntityLink::getFieldCaption($code);
			}
		}
		return $this->FIELDS_INFO;
	}

	protected function checkRequisiteLinks($entityTypeId, $entityId,
											$requisiteId, $bankDetailId,
											$mcRequisiteId, $mcBankDetailId, &$errors)
	{
		$params = array(
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId,
			'REQUISITE_ID' => $requisiteId,
			'BANK_DETAIL_ID' => $bankDetailId,
			'MC_REQUISITE_ID' => $mcRequisiteId,
			'MC_BANK_DETAIL_ID' => $mcBankDetailId,
		);

		foreach ($params as $paramName => $value)
		{
			if ($value === '' || intval($value) < 0
				|| (($paramName === 'ENTITY_TYPE_ID' || $paramName === 'ENTITY_ID') && intval($value) === 0))
			{
				$errors[] = $paramName.' is not defined or invalid.';
				return false;
			}
		}

		return true;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$entityTypeId = 0;
		$entityId = 0;

		if (is_array($filter))
		{
			if (isset($filter['=ENTITY_TYPE_ID']))
			{
				$entityTypeId = (int)$filter['=ENTITY_TYPE_ID'];
			}
			else if (isset($filter['ENTITY_TYPE_ID']))
			{
				$entityTypeId = (int)$filter['ENTITY_TYPE_ID'];
			}

			if (isset($filter['=ENTITY_ID']))
			{
				$entityId = (int)$filter['=ENTITY_ID'];
			}
			else if (isset($filter['ENTITY_ID']))
			{
				$entityId = (int)$filter['ENTITY_ID'];
			}
		}

		if (!Requisite\EntityLink::checkReadPermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		unset($entityTypeId, $entityId);

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = Requisite\EntityLink::getList(
			array(
				'order' => $order,
				'filter' => $filter,
				'select' => $select,
				'offset' => $offset,
				'count_total' => true
			)
		);

		$dbResult = new CDBResult($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerRegister(&$fields, &$errors, array $params = null)
	{
		$entityTypeId = $this->resolveParam($fields, 'ENTITY_TYPE_ID');
		$entityId = $this->resolveParam($fields, 'ENTITY_ID');
		$requisiteId = $this->resolveParam($fields, 'REQUISITE_ID');
		$bankDetailId = $this->resolveParam($fields, 'BANK_DETAIL_ID');
		$mcRequisiteId = $this->resolveParam($fields, 'MC_REQUISITE_ID');
		$mcBankDetailId = $this->resolveParam($fields, 'MC_BANK_DETAIL_ID');

		if (!$this->checkRequisiteLinks($entityTypeId, $entityId,
			$requisiteId, $bankDetailId, $mcRequisiteId, $mcBankDetailId, $errors))
		{
			return false;
		}

		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;
		$requisiteId = (int)$requisiteId;
		$bankDetailId = (int)$bankDetailId;
		$mcRequisiteId = (int)$mcRequisiteId;
		$mcBankDetailId = (int)$mcBankDetailId;

		if (!Requisite\EntityLink::checkUpdatePermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			Requisite\EntityLink::checkConsistence(
				$entityTypeId, $entityId,
				$requisiteId, $bankDetailId,
				$mcRequisiteId, $mcBankDetailId
			);

			Requisite\EntityLink::register(
				$entityTypeId, $entityId,
				$requisiteId, $bankDetailId,
				$mcRequisiteId, $mcBankDetailId
			);
		}
		catch (Main\SystemException $e)
		{
			$errors[] = $e->getMessage();
			return false;
		}

		return true;
	}
	protected function innerUnregister($entityTypeId, $entityId, &$errors)
	{
		if(!Requisite\EntityLink::checkUpdatePermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			Requisite\EntityLink::unregister($entityTypeId, $entityId);
		}
		catch (Main\SystemException $e)
		{
			$errors[] = $e->getMessage();
			return false;
		}

		return true;
	}

	protected function getEntityRequisite($entityTypeId, $entityId, &$errors)
	{
		if(!Requisite\EntityLink::checkReadPermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$linkInfo = Requisite\EntityLink::getByEntity($entityTypeId, $entityId);
		if (is_array($linkInfo))
		{
			$result = array_merge(array('ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId), $linkInfo);
		}
		else
		{
			$errors[] = 'Not found';
			return false;
		}

		return $result;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'LINK')
		{
			$fieldsInfo = $this->getFieldsInfo();

			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if ($nameSuffix === 'FIELDS' || $nameSuffix === 'LIST')
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
			else if ($nameSuffix === 'GET')
			{
				$entityTypeId = intval($this->resolveParam($arParams, 'entityTypeId'));
				$entityId = intval($this->resolveParam($arParams, 'entityId'));

				$availableEntityTypeIds = \Bitrix\Crm\Requisite\EntityLink::getAvailableEntityTypeIds();
				if (!isset($availableEntityTypeIds[$entityTypeId]))
				{
					$errors[] = 'entityTypeId is not defined or invalid.';
					return false;
				}
				if(!$this->checkEntityID($entityId))
				{
					$errors[] = 'entityId is not defined or invalid.';
					return false;
				}

				$errors = array();
				$result = $this->getEntityRequisite($entityTypeId, $entityId, $errors);
				if(!is_array($result))
				{
					throw new RestException(implode("\n", $errors));
				}
				$this->externalizeFields($result, $fieldsInfo);
				return $result;
			}
			else if ($nameSuffix === 'REGISTER')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields');
				$methodParams = $this->resolveArrayParam($arParams, 'params');
				$this->internalizeFields($fields, $fieldsInfo, array());
				$errors = array();
				if(!$this->innerRegister($fields, $errors, $methodParams))
				{
					throw new RestException(implode("\n", $errors));
				}

				return true;
			}
			else if ($nameSuffix === 'UNREGISTER')
			{
				$entityTypeId = intval($this->resolveParam($arParams, 'entityTypeId'));
				$entityId = intval($this->resolveParam($arParams, 'entityId'));

				$availableEntityTypeIds = \Bitrix\Crm\Requisite\EntityLink::getAvailableEntityTypeIds();
				if (!isset($availableEntityTypeIds[$entityTypeId]))
				{
					$errors[] = 'entityTypeId is not defined or invalid.';
					return false;
				}
				if(!$this->checkEntityID($entityId))
				{
					throw new RestException('entityId is not defined or invalid.');
				}

				$errors = array();
				if(!$this->innerUnregister($entityTypeId, $entityId, $errors))
				{
					throw new RestException(implode("\n", $errors));
				}

				return true;
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmAddressRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityAddress();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityAddress::getFieldsInfo();
			$labels = EntityAddress::getLabels();
			foreach ($this->FIELDS_INFO as $code => &$field)
			{
				$field['CAPTION'] = $labels[$code];
			}
		}
		return $this->FIELDS_INFO;
	}

	protected function prepareListResult(
		Main\ORM\Query\Result|array $result,
		int|false $page, int|false $limit
	) : CDBResult
	{
		if (is_array($result))
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray($result);
		}
		else
		{
			$dbResult = new CDBResult($result);
		}

		if ($page === false)
		{
			$limit = $result->getSelectedRowsCount();
		}

		$dbResult->NavStart($limit, false, $page);

		return $dbResult;

	}

	private function parseEntityTypeIdElement(array $filter, string $entityTypeIdKey, string $resultName): Main\Result
	{
		$result = new Main\Result();

		if (isset($filter[$entityTypeIdKey]))
		{
			$id = CCrmOwnerType::Undefined;
			$value = $filter[$entityTypeIdKey];
			if (is_int($value))
			{
				$id = $value;
			}
			elseif (is_string($value))
			{
				$id = (int)$value;
			}
			else // array
			{
				$result->addError(new Main\Error('Multiple value', 'MULTIPLE_VALUE'));
			}

			if ($result->isSuccess())
			{
				$validEntityTypeIds = [
					CCrmOwnerType::Lead,
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company,
					CCrmOwnerType::Requisite,
				];

				if (in_array($id, $validEntityTypeIds, true))
				{
					$result->setData([$resultName => $id]);
				}
				else
				{
					$result->addError(new Main\Error('Invalid value', 'INVALID_VALUE'));
				}
			}
		}
		else
		{
			$result->addError(new Main\Error('Value is not set', 'VALUE_IS_NOT_SET'));
		}

		return $result;
	}

	private function parseEntityIdElement(array $filter, string $entityIdKey, string $resultName): Main\Result
	{
		$result = new Main\Result();

		if (isset($filter[$entityIdKey]))
		{
			$id = 0;
			$value = $filter[$entityIdKey];
			if (is_int($value))
			{
				$id = $value;
			}
			elseif (is_string($value))
			{
				$id = (int)$value;
			}
			else // array
			{
				$result->addError(new Main\Error('Multiple value', 'MULTIPLE_VALUE'));
			}

			if ($result->isSuccess())
			{
				if ($id > 0)
				{
					$result->setData([$resultName => $id]);
				}
				else
				{
					$result->addError(new Main\Error('Invalid value', 'INVALID_VALUE'));
				}
			}
		}
		else
		{
			$result->addError(new Main\Error('Value is not set', 'VALUE_IS_NOT_SET'));
		}

		return $result;
	}

	private function parseIdent(array $filter, callable $identMethod, string $identName): Main\Result
	{
		$result = new Main\Result();

		$resultName = lcfirst(StringHelper::snake2camel($identName));
		$idResult1 = $identMethod($filter, '=' . $identName, $resultName);
		$isSetId1 = $idResult1->isSuccess();
		$idResult2 = $identMethod($filter, $identName, $resultName);
		$isSetId2 = $idResult2->isSuccess();
		$id = 0;

		if ($isSetId1 && !$isSetId2)
		{
			$id = $idResult1->getData()[$resultName];
		}
		elseif (!$isSetId1 && $isSetId2)
		{
			$id = $idResult2->getData()[$resultName];
		}
		elseif ($isSetId1 && $isSetId2)
		{
			$id1 = $idResult1->getData()[$resultName];
			$id2 = $idResult2->getData()[$resultName];

			if ($id1 === $id2)
			{
				$id = $id1;
			}
			else
			{
				$result->addError(new Main\Error('Invalid value', 'INVALID_VALUE'));
			}
		}
		else
		{
			$result->addError(new Main\Error('Value is not set', 'VALUE_IS_NOT_SET'));
		}

		if ($result->isSuccess())
		{
			if ($id > 0)
			{
				$result->setData([$resultName => $id]);
			}
			else
			{
				$result->addError(new Main\Error('Invalid value', 'INVALID_VALUE'));
			}
		}

		return $result;
	}

	private function parseEntityTypeId(array $filter): Main\Result
	{
		return $this->parseIdent($filter, [$this, 'parseEntityTypeIdElement'], 'ENTITY_TYPE_ID');
	}

	private function parseEntityId(array $filter): Main\Result
	{
		return $this->parseIdent($filter, [$this, 'parseEntityIdElement'], 'ENTITY_ID');
	}

	private function parseAnchorTypeId(array $filter): Main\Result
	{
		return $this->parseIdent($filter, [$this, 'parseEntityTypeIdElement'], 'ANCHOR_TYPE_ID');
	}

	private function parseAnchorId(array $filter): Main\Result
	{
		return $this->parseIdent($filter, [$this, 'parseEntityIdElement'], 'ANCHOR_ID');
	}

	private function parseIdents(
		array $filter,
		callable $parseTypeId,
		callable $parseId,
		string $typeIdKey,
		string $idKey,
		string $errorMessage,
		string $errorCode
	): Main\Result
	{
		$result = new Main\Result();

		$typeId = 0;
		$typeIdResult = $parseTypeId($filter);
		$isTypeIdParsed = $typeIdResult->isSuccess();
		if ($isTypeIdParsed)
		{
			$typeId = $typeIdResult->getData()[$typeIdKey];
		}

		$id = 0;
		$idResult = $parseId($filter);
		$isIdParsed = $idResult->isSuccess();
		if ($isIdParsed)
		{
			$id = $idResult->getData()[$idKey];
		}

		if ($isTypeIdParsed && $isIdParsed)
		{
			$result->setData(
				[
					$typeIdKey => $typeId,
					$idKey => $id,
				]
			);
		}
		else
		{
			$result->addError(new Main\Error($errorMessage, $errorCode));
		}

		return $result;
	}

	private function parseEntityIdents(array $filter): Main\Result
	{
		return $this->parseIdents(
			$filter,
			[$this, 'parseEntityTypeId'],
			[$this, 'parseEntityId'],
			'entityTypeId',
			'entityId',
			'Entity idents is not parsed',
			'ENTITY_IDENTS_IS_NOT_PARSED',
		);
	}

	private function parseAnchorIdents(array $filter): Main\Result
	{
		return $this->parseIdents(
			$filter,
			[$this, 'parseAnchorTypeId'],
			[$this, 'parseAnchorId'],
			'anchorTypeId',
			'anchorId',
			'Anchor idents is not parsed',
			'ANCHOR_IDENTS_IS_NOT_PARSED',
		);
	}

	private function getIdents(array $filter): Main\Result
	{
		$result = new Main\Result();

		$entityIdentsResult = $this->parseEntityIdents($filter);
		$isEntityIdentsParsed = $entityIdentsResult->isSuccess();
		$isEntityIdentsAbsent = !(
			array_key_exists('=ENTITY_TYPE_ID', $filter)
			|| array_key_exists('ENTITY_TYPE_ID', $filter)
		);
		$anchorIdentsResult = $this->parseAnchorIdents($filter);
		$isAnchorIdentsParsed = $anchorIdentsResult->isSuccess();
		$isAnchorIdentsAbsent = !(
			array_key_exists('=ANCHOR_TYPE_ID', $filter)
			|| array_key_exists('ANCHOR_TYPE_ID', $filter)
		);
		if ($isEntityIdentsParsed && $isAnchorIdentsAbsent && !$isAnchorIdentsParsed)
		{
			$result->setData(
				[
					'entityTypeId' => $entityIdentsResult->getData()['entityTypeId'],
					'entityId' => $entityIdentsResult->getData()['entityId'],
				]
			);
		}
		elseif ($isAnchorIdentsParsed && $isEntityIdentsAbsent && !$isEntityIdentsParsed)
		{
			$result->setData(
				[
					'entityTypeId' => $anchorIdentsResult->getData()['anchorTypeId'],
					'entityId' => $anchorIdentsResult->getData()['anchorId'],
				]
			);
		}
		else
		{
			$result->addError(new Main\Error('Identifiers is not parsed', 'IDENTS_IN_NOT_PARSED'));
		}

		return $result;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$oldRightsCheck = true;
		$identsResult = $this->getIdents($filter);
		if ($identsResult->isSuccess())
		{
			$oldRightsCheck = false;
			$identFields = $identsResult->getData();
			if (
				!EntityAddress::checkReadPermissionOwnerEntity(
					$identFields['entityTypeId'],
					$identFields['entityId']
				)
			)
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		if ($oldRightsCheck)
		{
			if(!EntityAddress::checkReadPermissionOwnerEntity())
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : false;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * ($page - 1);

		if(!is_array($select))
			$select = array();

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		//For backward compatibility only
		if(isset($filter['ENTITY_TYPE_ID']) &&
				($filter['ENTITY_TYPE_ID'] == CCrmOwnerType::Company || $filter['ENTITY_TYPE_ID'] == CCrmOwnerType::Contact))
		{
			$filter['ANCHOR_TYPE_ID'] = $filter['ENTITY_TYPE_ID'];
			unset($filter['ENTITY_TYPE_ID']);

			if(isset($filter['ENTITY_ID']))
			{
				$filter['ANCHOR_ID'] = $filter['ENTITY_ID'];
				unset($filter['ENTITY_ID']);
			}
		}

		$listParams = [
			'order' => $order,
			'filter' => $filter,
			'select' => $select,
			'count_total' => true
		];

		if ($page !== false)
		{
			$listParams['limit'] = $limit;
			$listParams['offset'] = $offset;
		}

		$result = $entity->getList($listParams);

		if (!is_object($result))
		{
			$result = [];
		}

		return $this->prepareListResult($result, $page, $limit);
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);

		$fieldsInfo = $this->getFieldsInfo();

		if($name === 'FIELDS' || $name === 'LIST')
		{
			return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}
		elseif($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$entityID = $entityTypeID = $typeID = 0;

			if(is_array($fields))
			{
				$typeID = intval($this->resolveParam($fields, 'TYPE_ID'));
				$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
				$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));
			}

			if(!$this->isValidID($typeID) || !EntityAddressType::isDefined($typeID))
			{
				throw new RestException('TYPE_ID is not defined or invalid.');
			}
			if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
			{
				throw new RestException('ENTITY_TYPE_ID is not defined or invalid.');
			}
			if(!$this->checkEntityID($entityID))
			{
				throw new RestException('ENTITY_ID is not defined or invalid.');
			}

			$r = $this->exists($typeID, $entityTypeID, $entityID);
			if(is_array($r))
			{
				throw new RestException("TypeAddress exists.");
			}

			if(!EntityAddress::checkCreatePermissionOwnerEntity($entityTypeID, $entityID))
			{
				throw new RestException('Access denied.');
			}

			$this->internalizeFields($fields, $fieldsInfo, array());

			if ($entityTypeID === \CCrmOwnerType::Requisite)
			{
				$anchor = EntityRequisite::getOwnerEntityById($entityID);
				$fields['ANCHOR_TYPE_ID'] = intval($anchor['ENTITY_TYPE_ID']);
				$fields['ANCHOR_ID'] = intval($anchor['ENTITY_ID']);
			}

			EntityAddress::register($entityTypeID, $entityID, $typeID, $fields);

			return true;
		}
		elseif($name === 'UPDATE')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$entityID = $entityTypeID = $typeID = 0;

			if(is_array($fields))
			{
				$typeID = intval($this->resolveParam($fields, 'TYPE_ID'));
				$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
				$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));
			}

			if(!$this->isValidID($typeID) || !EntityAddressType::isDefined($typeID))
			{
				throw new RestException('TYPE_ID is not defined or invalid.');
			}
			if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
			{
				throw new RestException('ENTITY_TYPE_ID is not defined or invalid.');
			}
			if(!$this->checkEntityID($entityID))
			{
				throw new RestException('ENTITY_ID is not defined or invalid.');
			}

			$r = $this->exists($typeID, $entityTypeID, $entityID);
			if(!is_array($r))
			{
				throw new RestException("TypeAddress not found.");
			}

			if(!EntityAddress::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
			{
				throw new RestException('Access denied.');
			}

			$this->internalizeFields($fields, $fieldsInfo, array());

			if (isset($fields['ENTITY_TYPE_ID']) && $fields['ENTITY_TYPE_ID'] == CCrmOwnerType::Requisite &&
				isset($fields['ENTITY_ID']) && $fields['ENTITY_ID'] > 0)
			{
				$requisite = (new EntityRequisite)->getById( $fields['ENTITY_ID']);
				if (is_array($requisite) && isset($requisite['ENTITY_TYPE_ID']) && $requisite['ENTITY_TYPE_ID'] > 0 &&
					isset($requisite['ENTITY_ID']) && $requisite['ENTITY_ID'] > 0)
				{
					$fields['ANCHOR_TYPE_ID'] = $requisite['ENTITY_TYPE_ID'];
					$fields['ANCHOR_ID'] = $requisite['ENTITY_ID'];
				}
			}
			EntityAddress::register($entityTypeID, $entityID, $typeID, $fields, [
				'updateLocationAddress' => !( isset($fields['ADDRESS_LOC_ADDR_ID']) && $fields['ADDRESS_LOC_ADDR_ID'] > 0 )
			]);

			return true;
		}
		elseif($name === 'DELETE')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$entityID = $entityTypeID = $typeID = 0;

			if(is_array($fields))
			{
				$typeID = intval($this->resolveParam($fields, 'TYPE_ID'));
				$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
				$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));
			}

			if(!$this->isValidID($typeID) || !EntityAddressType::isDefined($typeID))
			{
				throw new RestException('TYPE_ID is not defined or invalid.');
			}
			if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
			{
				throw new RestException('ENTITY_TYPE_ID is not defined or invalid.');
			}
			if(!$this->checkEntityID($entityID))
			{
				throw new RestException('ENTITY_ID is not defined or invalid.');
			}

			$r = $this->exists($typeID, $entityTypeID, $entityID);
			if(!is_array($r))
			{
				throw new RestException("TypeAddress not found.");
			}

			if(!EntityAddress::checkDeletePermissionOwnerEntity($entityTypeID, $entityID))
			{
				throw new RestException('Access denied.');
			}

			EntityAddress::unregister($entityTypeID, $entityID, $typeID);

			return true;
		}
		elseif($name === 'GETZONEID')
		{
			return EntityAddress::getZoneId();
		}
		elseif($name === 'SETZONEID')
		{
			$zoneId = $this->resolveParam($arParams, 'ID');

			EntityAddress::setZoneId($zoneId);

			return true;
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	protected function exists($typeID, $entityTypeID, $entityID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array(
				'filter' => array('TYPE_ID' => $typeID,
						'ENTITY_TYPE_ID' => $entityTypeID,
						'ENTITY_ID' => $entityID)
		));

		return $result->fetch();
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmAddressRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmAddressRegister'] = self::createEventInfo('crm', 'OnAfterAddressRegister', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmAddressUnregister'] = self::createEventInfo('crm', 'OnAddressUnregister', $callback);

	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmaddressregister':
			case 'oncrmaddressunregister':

				$fields = isset($arParams[0])? $arParams[0]->getParameter('fields') : null;

				$typeID = is_array($fields) && isset($fields['TYPE_ID'])? (int)$fields['TYPE_ID'] : 0;
				$entityTypeID = is_array($fields) && isset($fields['ENTITY_TYPE_ID'])? (int)$fields['ENTITY_TYPE_ID'] : 0;
				$entityID = is_array($fields) && isset($fields['ENTITY_ID'])? (int)$fields['ENTITY_ID'] : 0;

				$address = new static();

				if(!$address->isValidID($typeID) || !EntityAddressType::isDefined($typeID))
				{
					throw new RestException("Could not find TYPE_ID in fields of event \"{$eventName}\"");
				}
				if(!$address->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
				{
					throw new RestException("Could not find ENTITY_TYPE_ID in fields of event \"{$eventName}\"");
				}
				if(!$address->checkEntityID($entityID))
				{
					throw new RestException("Could not find ENTITY_ID in fields of event \"{$eventName}\"");
				}

				if(mb_strtolower($eventName) == 'oncrmaddressunregister')
				{
					$r = \Bitrix\Crm\AddressTable::getList(array(
						'select' => array(
							'ANCHOR_ID',
							'ANCHOR_TYPE_ID'
						),
						'filter' => array(
							'=ENTITY_ID' => $entityID,
							'=ENTITY_TYPE_ID' => $entityTypeID,
							'=TYPE_ID' => $typeID,
						)
					));

					if($addressFields = $r->fetch())
					{
						$anchorID = (int)$addressFields['ANCHOR_ID'];
						$anchorTypeID = (int)$addressFields['ANCHOR_TYPE_ID'];
					}

				}
				else
				{
					$anchorID = is_array($fields) && isset($fields['ANCHOR_ID'])? (int)$fields['ANCHOR_ID'] : 0;
					$anchorTypeID = is_array($fields) && isset($fields['ANCHOR_TYPE_ID'])? (int)$fields['ANCHOR_TYPE_ID'] : 0;
				}

				if(!$address->isValidID($anchorTypeID) || !CCrmOwnerType::IsDefined($anchorTypeID))
				{
					throw new RestException("Could not find ANCHOR_TYPE_ID in fields of event \"{$eventName}\"");
				}
				if(!$address->checkEntityID($anchorID))
				{
					throw new RestException("Could not find ANCHOR_ID in fields of event \"{$eventName}\"");
				}

				return array('FIELDS' => array('TYPE_ID' => EntityAddressType::resolveName($typeID), 'ENTITY_TYPE_ID' => CCrmOwnerType::resolveName($entityTypeID), 'ENTITY_ID' => $entityID, 'ANCHOR_ID' => $anchorID, 'ANCHOR_TYPE_ID' => CCrmOwnerType::resolveName($anchorTypeID)));
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}

class CCrmAddressTypeRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);

		if ($name === 'GETAVAILABLE')
		{
			$result = [];
			$descriptions = EntityAddressType::getDescriptions(EntityAddressType::getAvailableIds());
			foreach($descriptions as $k => $v)
			{
				$result[] = array('ID' => $k, 'NAME' => $v);
			}
			return $result;
		}
		else if ($name === 'GETZONEMAP')
		{
			$result = [];

			foreach (EntityAddressType::getZoneMap() as $zoneId => $zoneInfo)
			{
				$info = ['ID' => $zoneId];
				foreach ($zoneInfo as $paramName => $paramValue)
				{
					$info[mb_strtoupper($paramName)] = $paramValue;
				}
				$result[] = $info;
			}

			return $result;
		}
		else if ($name === 'GETDEFAULTBYZONE')
		{
			$addressZoneId = (string)$this->resolveParam($arParams, 'ID');

			return EntityAddressType::getDefaultIdByZone($addressZoneId);
		}
		else if ($name === 'GETDEFAULTIDBYENTITYCATEGORY')
		{
			$entityTypeId = (int)$this->resolveParam($arParams, 'ENTITY_TYPE_ID');
			$categoryId = (int)$this->resolveParam($arParams, 'CATEGORY_ID');

			return EntityAddressType::getDefaultIdByEntityCategory($entityTypeId, $categoryId);
		}
		else if ($name === 'GETDEFAULTIDBYENTITYID')
		{
			$entityTypeId = (int)$this->resolveParam($arParams, 'ENTITY_TYPE_ID');
			$entityId = (int)$this->resolveParam($arParams, 'ENTITY_ID');

			return EntityAddressType::getDefaultIdByEntityId($entityTypeId, $entityId);
		}
		else if ($name === 'GETBYZONESORVALUES')
		{
			$zoneIds = $this->resolveArrayParam($arParams, 'ID');
			$values = $this->resolveArrayParam($arParams, 'VALUE');

			if (!is_array($zoneIds))
			{
				$zoneIds = [$zoneIds];
			}

			foreach ($zoneIds as $k => $v)
			{
				$zoneIds[$k] = (string)$zoneIds[$k];
			}

			if (!is_array($values))
			{
				$values = [$values];
			}

			foreach ($values as $k => $v)
			{
				$values[$k] = (int)$values[$k];
			}

			$result = [];
			foreach (EntityAddressType::getDescriptionsByZonesOrValues($zoneIds, $values) as $typeId => $description)
			{
				$result[] = ['ID' => $typeId, 'NAME' => $description];
			}
			return $result;
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmExternalChannelConnectorRestProxy  extends CCrmRestProxyBase
{
	const ERROR_CONNECTOR_CREATE = 'ERROR_CONNECTOR_CREATE';
	const ERROR_CONNECTOR_REGISTRATION = 'ERROR_CONNECTOR_REGISTRATION';

	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new Rest\CCrmExternalChannelConnector();
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = Rest\CCrmExternalChannelConnector::getFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
				array(
						'order' => $order,
						'filter' => $filter,
						'select' => $select,
						'offset' => $offset,
						'count_total' => true
				)
		);

		if (is_object($result))
		{
			$dbResult = new CDBResult($result);
		}
		else
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
		}
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);

		if ($name === 'CONNECTOR')
		{
			$fieldsInfo = $this->getFieldsInfo();
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');

			if($nameSuffix === 'FIELDS' || $nameSuffix === 'LIST')
			{
				return parent::processMethodRequest($nameSuffix, $nameDetails, $arParams, $nav, $server);
			}
			elseif($nameSuffix === 'REGISTER')
			{
				$entity = self::getEntity();
				$entity->setServer($this->getServer());

				$fields = $this->resolveArrayParam($arParams, 'fields');

				$entity->prepareFields($fields);
				$error = [];
				$entity->checkFields($fields, $error);

				$this->internalizeFields($fields, $fieldsInfo, array());

				if (empty($error))
				{
					$channelId = $entity::register($fields['TYPE_ID'], $fields['ORIGINATOR_ID'], $fields);
				}
				else
				{
					throw new RestException(implode('; ', $error), self::ERROR_CONNECTOR_REGISTRATION, CRestServer::STATUS_WRONG_REQUEST);
				}

				if($channelId <> '')
				{
					return array("result"=>$channelId);
				}
				else
				{
					throw new RestException('Connector not created', self::ERROR_CONNECTOR_CREATE, CRestServer::STATUS_INTERNAL);
				}
			}
			elseif($nameSuffix === 'UNREGISTER')
			{
				$entity = self::getEntity();

				$typeId = $originatorId = 0;

				$fields = $this->resolveArrayParam($arParams, 'fields');
				if(is_array($fields))
				{
					$typeId = $this->resolveParam($fields, 'TYPE_ID');
					$originatorId = $this->resolveParam($fields, 'ORIGINATOR_ID');
				}

				if(!$this->isValidCode($typeId) || !Rest\CCrmExternalChannelType::isDefined(Rest\CCrmExternalChannelType::resolveID($typeId)))
				{
					throw new RestException('TYPE_ID is not defined or invalid.');
				}
				if(!$this->isValidCode($originatorId))
				{
					throw new RestException('ORIGINATOR_ID is not defined');
				}

				$r = $this->exists($typeId, $originatorId);
				if(!is_array($r))
				{
					throw new RestException("Connector not found.");
				}

				$entity::unregister($typeId, $originatorId);

				return true;
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	public function isValidCode($code)
	{
		return is_string($code) && $code <> '';
	}

	protected function exists($typeID, $originatorId)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array(
				'filter' => array('TYPE_ID' => $typeID,
						'ORIGINATOR_ID' => $originatorId)
		));

		return $result->fetch();
	}
}

class CCrmExternalChannelRestProxy  extends CCrmRestProxyBase
{
	const ERROR_IMPORT_BATCH = 'ERROR_IMPORT_BATCH';
	const ERROR_CONNECTOR_NOT_FOUND = 'ERROR_CONNECTOR_NOT_FOUND';
	const ERROR_CONNECTOR_INVALID = 'ERROR_CONNECTOR_INVALID';
	const ERROR_PRESET_NOT_FOUND = 'ERROR_PRESET_NOT_FOUND';
	const ERROR_BAD_LICENSE_AGREEMENT  = 'ERROR_BAD_LICENSE_AGREEMENT';
	const ERROR_LICENSE_RESTRICTED  = 'ERROR_LICENSE_RESTRICTED';


	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$connector = new Rest\CCrmExternalChannelConnector();

		$name = mb_strtoupper($name);

		if ($name === 'COMPANY' || $name === 'CONTACT' || $name === 'ACTIVITY')
		{
			$resultImport = array();
			$errorBatch = array();

			$isRegistered = false;
			$methodParams = $this->resolveArrayParam($arParams, 'params');
			if(($channel_id = $methodParams['CHANNEL_ID']) && $channel_id <> '')
			{
				$connector->setChannelId($channel_id);
				$isRegistered = $connector->isRegistered();
				$originator_id = $connector->getOriginatorId();
			}

			if(!$isRegistered)
			{
				throw new RestException('Connector not found!', self::ERROR_CONNECTOR_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
			}
			elseif(empty($originator_id) || $originator_id === '')
			{
				throw new RestException('Connector is invalid!', self::ERROR_CONNECTOR_INVALID, CRestServer::STATUS_FORBIDDEN);
			}
			else
			{
				$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');

				if($name === 'COMPANY' || $nameSuffix === 'COMPANY')
					$entity = new CCrmCompanyRestProxy();
				else
					$entity = new CCrmContactRestProxy();

				$preset = new Rest\CCrmExternalChannelImportPreset();
				$preset->setOwnerEntity($entity);

				if(!$this->isValidID(EntityRequisite::getDefaultPresetId($entity->getOwnerTypeID())))
				{
					throw new RestException("Preset is not defined.", self::ERROR_PRESET_NOT_FOUND, CRestServer::STATUS_FORBIDDEN);
				}

				$import = new Rest\CCrmExternalChannelImport($connector, $preset);

				$batch = $import->resolveParamsBatch($arParams);

				if (!is_array($batch) || count($batch) === 0)
				{
					throw new RestException("Batch is not defined.", self::ERROR_IMPORT_BATCH, CRestServer::STATUS_WRONG_REQUEST);
				}
				elseif(count($batch)>\CRestUtil::BATCH_MAX_LENGTH)
				{
					throw new RestException("Max batch length exceeded ".\CRestUtil::BATCH_MAX_LENGTH, \CRestProvider::ERROR_BATCH_LENGTH_EXCEEDED, CRestServer::STATUS_WRONG_REQUEST);
				}
				else
				{
					$added = 0;
					$updated = 0;
					$skipped = 0;
					$errorList = array();
					$errorBatchDetails = array();

					foreach($batch as $num => $items)
					{
						$errors = array();
						$activity = new Rest\CCrmExternalChannelImportActivity();
						$agent = 	new Rest\CCrmExternalChannelImportAgent();

						$activity->setOwnerEntity($entity);
						$agent->setEntity($entity);

						$activity->import = $import;
						$agent->import = $import;

						$import->setRawData($items);

						$isNewVersionAgent = false;

						$bAgentAdd = false;
						$bAgentUpd = false;
						$bAgentSkip = false;
						$bActivityAdd = false;
						$bActivityUpd = false;

						if(($agentFields = $items[Rest\CCrmExternalChannelImport::AGENT]) && count($agentFields)>0)
						{
							if($import->checkLicense('tariff'))
							{
								if($import->checkLicense('accepted'))
								{
									if((is_set($agentFields, Rest\CCrmExternalChannelImport::FIELDS) && count($agentFields[Rest\CCrmExternalChannelImport::FIELDS])>0) ||
										(is_set($agentFields, Rest\CCrmExternalChannelImport::EXTERNAL_FIELDS) && count($agentFields[Rest\CCrmExternalChannelImport::EXTERNAL_FIELDS])>0))
									{
										$r = $agent->checkFields($agentFields[Rest\CCrmExternalChannelImport::FIELDS]);

										if($r->getErrors())
										{
											$errorList[$num] = $import->formatErrorsPackage(implode('; ', $r->getErrors()), $num);
											$errorBatch[$num] = $r->getErrors();
										}
										else
										{
											$r = $agent->tryGetOwnerInfos($agentFields[Rest\CCrmExternalChannelImport::FIELDS]);
											$resOwnerInfos = $r->getData();
											$ownerInfo = $resOwnerInfos['RESULT'];

											$isNewVersionAgent = ($ownerInfo['ORIGIN_VERSION'] == '' ||
												$ownerInfo['ORIGIN_VERSION'] !== $agentFields[Rest\CCrmExternalChannelImport::FIELDS]['ORIGIN_VERSION']);

											if($isNewVersionAgent)
											{
												if($r->getErrors())
												{
													$errorList[$num] = $import->formatErrorsPackage(implode('; ', $r->getErrors()), $num);
													$errorBatch[$num] = $r->getErrors();
												}
												else
												{
													$resultAgent = array();
													$r = $agent->modify($ownerInfo, $agentFields, $resultAgent);

													$agentId = $resultAgent['id'];

													if($r->getErrors())
													{
														$errorList[$num] = $import->formatErrorsPackage($r->getErrors(), $num);
														$errorBatch[$num] = $r->getErrors();
														$errorBatchDetails[$num] = $r->getData();
													}
													elseif(!$this->isValidID($agentId))
													{
														$errorList[$num] = $import->formatErrorsPackage("Agent is not created", $num);
														$errorBatch[$num][] = new Main\Error("Agent is not created", 1001);
													}
													else
													{
														$bAgentAdd = $resultAgent['process']['add'];
														$bAgentUpd = $resultAgent['process']['upd'];
														$activity->setOwnerEntityId($agentId);
													}
												}
											}
											else
											{
												$activity->setOwnerEntityId($ownerInfo['ID']);
												$bAgentSkip = true;
											}
										}
									}
									else
									{
										$errorList[$num] = $import->formatErrorsPackage("Agent fields or external fields is not defined.", $num);
										$errorBatch[$num][] = new Main\Error("Agent fields or external fields is not defined.", 1002);
									}

									if(empty($errorList[$num]))
									{
										if($name === 'ACTIVITY')
										{
											if(($activityInfo = $items[Rest\CCrmExternalChannelImport::ACTIVITY]) && count($activityInfo)>0)
											{
												$resultActivity = array();

												$typeId = Rest\CCrmExternalChannelImport::resolveTypeIdActivityByFields($activityInfo);

												if($typeId>0)
												{
													$activity->setTypeActivity(Rest\CCrmExternalChannelActivityType::resolveName($typeId));

													$r = $activity->import($activityInfo, $resultActivity, $fileds);

													$activityId = $resultActivity['id'];

													if($r->getErrors())
													{
														$errorList[$num] = $import->formatErrorsPackage($r->getErrors(), $num);
														$errorBatch[$num] = $r->getErrors();
													}
													elseif(!$this->isValidID($activityId))
													{
														$errorList[$num] = $import->formatErrorsPackage("Activity is not imported", $num);
														$errorBatch[$num][] = new Main\Error("Activity is not imported", 1003);
													}
													else
													{
														$bActivityAdd = $resultActivity['process']['add'];
														$bActivityUpd = $resultActivity['process']['upd'];
													}
												}
												else
												{
													$errorList[$num] = $import->formatErrorsPackage("Type activity is not defined.", $num);
													$errorBatch[$num][] = new Main\Error("Type activity is not defined.", 1004);
												}
											}
											else
											{
												$errorList[$num] = $import->formatErrorsPackage("Activity is not defined.", $num);
												$errorBatch[$num][] = new Main\Error("Activity is not defined.", 1005);
											}
										}
										else
										{
											if($isNewVersionAgent)
											{
												$fields = array();

												$activity->setTypeActivity(Rest\CCrmExternalChannelActivityType::ImportAgentName);

												$activity->fillEmptyFields($fields, $agentFields);

												$items[Rest\CCrmExternalChannelImport::ACTIVITY] = array();

												$activity->fillFields($fields);

												$activityId = $activity->getEntity()->innerAdd($fields, $errors);

												if(count($errors)>0)
												{
													$errorList[$num] = $import->formatErrorsPackage(implode('; ', $errors), $num);
													$errorBatch[$num][] = new Main\Error(implode('; ', $errors), 1006);
												}
												elseif(!$this->isValidID($activityId))
												{
													$errorList[$num] = $import->formatErrorsPackage("Activity is not created", $num);
													$errorBatch[$num][] = new Main\Error("Activity is not created", 1007);
												}
												else
												{
													$activity->registerActivityInChannel($activityId, $connector);
												}
											}
										}
									}
								}
								else
								{
									throw new RestException("License agreement not accepted", self::ERROR_BAD_LICENSE_AGREEMENT, CRestServer::STATUS_FORBIDDEN);
								}
							}
							else
							{
								throw new RestException("License restricted", self::ERROR_LICENSE_RESTRICTED, CRestServer::STATUS_FORBIDDEN);
							}
						}
						else
							$errorList[$num] = $import->formatErrorsPackage("Agent is not defined.", $num);

						if($name === 'COMPANY' || $name === 'CONTACT')
						{
							if($bAgentAdd) $added++;
							if($bAgentUpd) $updated++;
							if($bAgentSkip) $skipped++;
						}
						elseif($name === 'ACTIVITY')
						{
							if($bActivityAdd) $added++;
							if($bActivityUpd) $updated++;
						}
					}
				}

				if($added>0)
					$resultImport['added'] = $added;
				if($updated>0)
					$resultImport['updated'] = $updated;
				if($skipped>0)
					$resultImport['skipped'] = $skipped;
				if(count($errorList)>0)
					$resultImport['result_error'] = implode(';', $errorList);

				$detail = array();
				$resultErrors = array();
				if(count($errorBatch)>0)
				{

					foreach($errorBatch as $num=>$errors)
					{
						$detail[$num]['id'] = $num;

						foreach($errors as $error)
						{
							/**@var $error Error */
							$resultErrors[] = array(
								'code' => $error->getCode(),
								'message' => str_replace('\\', '/', $error->getMessage()),
							);
						}

						$detail[$num]['errors'] = $resultErrors;
						$detail[$num]['requisites'] = $this->prepareDetailErrors($errorBatchDetails[$num]['requisites']);
					}

					foreach($detail as $errors)
					{
						$resultImport['batch'][] = $errors;
					}
				}

				return $resultImport;
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	function prepareDetailErrors($errorList)
	{
		$result = array();

		if(isset($errorList['IMPORT_ERROR']) && count($errorList['IMPORT_ERROR'])>0)
		{
			foreach ($errorList['IMPORT_ERROR'] as $error)
			{
				/**@var $error Error */
				$result['errors'][] = array(
					'code' => $error->getCode(),
					'message' => str_replace('\\', '/', $error->getMessage()),
				);
			}
		}

		if(isset($errorList['BATCH_ERROR']) && count($errorList['BATCH_ERROR'])>0)
		{

			foreach ($errorList['BATCH_ERROR'] as $id=>$errors)
			{
				$resultErrors = array();
				if(isset($errors['errors']))
				{
					foreach ($errors['errors'] as $error)
					{
						/**@var $error Error */
						$resultErrors[] = array(
							'code' => $error->getCode(),
							'message' => str_replace('\\', '/', $error->getMessage()),
						);
					}

					$result[] = array('id'=>$id, 'errors'=>$resultErrors);
				}
				elseif ($errors['banks'])
				{
					$resultErrors = $this->prepareDetailErrors($errors['banks']);
					$result[] = array('id'=>$id, 'banks'=>$resultErrors);
				}
			}
		}

		return $result;
	}
}

class CCrmPersonTypeRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
					'CAPTION' => Loc::getMessage('CRM_REST_FIELD_ID')
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly),
					'CAPTION' => Loc::getMessage('CRM_REST_FIELD_NAME')
				)
			);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		global $USER;

		if (!is_array($filter))
		{
			$filter = array();
		}

		if (is_string($select))
		{
			$select = [$select];
		}

		$result = array();

		$crmPerms = new CCrmPerms($USER->GetID());
		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!CModule::IncludeModule('sale'))
		{
			$errors[] = 'Sale module is not installed.';
			return false;
		}

		if (!in_array('*', $select)
			&& !in_array('CODE', $select)
			&& in_array('NAME', $select)
		)
		{
			$select[] = 'CODE';
		}

		foreach ($filter as $field => $value)
		{
			if (mb_strpos($field, 'NAME') !== false)
			{
				$newFieldName = str_replace('NAME', 'CODE', $field);
				$filter[$newFieldName] = $value;
				unset($filter[$field]);
			}
		}

		/** @todo Use SiteTable::getDefaultSiteId() */
		$siteId = '';
		$siteIterator = Bitrix\Main\SiteTable::getList(array(
			'select' => array('LID', 'LANGUAGE_ID'),
			'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y'),
			'cache' => ['ttl' => 86400],
		));
		if ($defaultSite = $siteIterator->fetch())
		{
			$siteId = $defaultSite['LID'];
		}
		unset($defaultSite, $siteIterator);

		$filter['=PERSON_TYPE_SITE.SITE_ID'] = $siteId;

		if (empty($select))
		{
			$select = ['*'];
		}

		$dbRes = \Bitrix\Crm\Invoice\PersonType::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order
		]);
		while($personType = $dbRes->fetch())
		{
			if ($personType['CODE'] === 'CRM_CONTACT' || $personType['CODE'] === 'CRM_COMPANY')
			{
				$personType['NAME'] = $personType['CODE'];

				unset($personType['CODE']);
				unset($personType['ENTITY_REGISTRY_TYPE']);

				$result[] = $personType;
			}
		}

		return $result;
	}
}

class CCrmPaySystemRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTIVE' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'SORT' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DESCRIPTION' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'PERSON_TYPE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTION_FILE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HANDLER' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HANDLER_CODE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HANDLER_NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
			);

			foreach ($this->FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = Loc::getMessage("CRM_REST_PAY_SYSTEM_FIELD_".$code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		global $USER;

		$result = array();

		$crmPerms = new CCrmPerms($USER->GetID());
		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!CModule::IncludeModule('sale'))
		{
			$errors[] = 'Sale module is not installed.';
			return false;
		}

		$personTypeIds = CCrmPaySystem::getPersonTypeIDs();
		$personTypeIds = is_array($personTypeIds) ? array_values($personTypeIds) : [];

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;

		if (!empty($personTypeIds))
		{
			if (empty($select))
				$select = array('ID');

			$skip = array(
				'ACTION_FILE' => false,
				'HANDLER' => false,
				'HANDLER_CODE' => false,
				'HANDLER_NAME' => false
			);
			$selectMap = array_fill_keys($select, true);
			foreach (array_keys($skip) as $fieldName)
			{
				if (!isset($selectMap[$fieldName]))
				{
					if ($fieldName === 'ACTION_FILE')
					{
						$selectMap['ACTION_FILE'] = true;
						$skip[$fieldName] = true;
					}
					else
					{
						$skip[$fieldName] = true;
					}
				}
				else if ($fieldName !== 'ACTION_FILE')
				{
					unset($selectMap[$fieldName]);
				}
			}
			$select = array_keys($selectMap);
			unset($selectMap);

			$res = \Bitrix\Sale\PaySystem\Manager::getList(
				array(
					'order' => $order,
					'filter' => array(
						'LOGIC' => 'AND',
						$filter,
						array('!ID' => \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()),
					),
					'select' => $select
				)
			);
			$handlerMap = null;
			$io = null;
			$actionList = null;
			while ($row = $res->fetch())
			{
				$actionFile = $row['ACTION_FILE'] ?? '';
				/*// only quote or invoice handlers
				if (preg_match('/quote(_\w+)*$/iu', $actionFile)
					|| preg_match('/bill(\w+)*$/iu', $actionFile))
				{*/
				$paySystemPersonTypes = array();
				if (isset($row['ID']) && $row['ID'] > 0)
					$paySystemPersonTypes = \Bitrix\Sale\PaySystem\Manager::getPersonTypeIdList($row['ID']);
				if (empty($paySystemPersonTypes) || array_intersect($paySystemPersonTypes, $personTypeIds))
				{
					if (!$skip['HANDLER'])
						$row['HANDLER'] = $actionFile;
					if ($io === null)
						$io = CBXVirtualIo::GetInstance();
					$handlerCode = $io->ExtractNameFromPath($actionFile);
					if (!$skip['HANDLER_CODE'])
						$row['HANDLER_CODE'] = $handlerCode;
					if ($actionList === null)
						$actionList = CCrmPaySystem::getActionsList();
					if (!$skip['HANDLER_NAME'])
					{
						$row['HANDLER_NAME'] = $actionList[$handlerCode] ?? '';
					}
					$handlerMap = CSalePaySystemAction::getOldToNewHandlersMap();
					$oldHandler = array_search($actionFile, $handlerMap);
					if ($skip['ACTION_FILE'])
					{
						unset($row['ACTION_FILE']);
					}
					else
					{
						if ($oldHandler !== false)
							$row['ACTION_FILE'] = $oldHandler;
					}

					$result[] = $row;
				}
			}
		}

		$dbResult = new CDBResult();
		$dbResult->InitFromArray($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function prepareListParams(&$order, &$filter, &$select)
	{
		parent::prepareListParams($order, $filter, $select); // TODO: Change the autogenerated stub

		$allowedFields = array_fill_keys(array_keys($this->getFieldsInfo()), true);

		if(empty($select) || in_array('*', $select, true))
			$select = array_keys($this->getFieldsInfo());

		foreach ($select as $fieldName)
		{
			if ($fieldName !== '*' && !isset($allowedFields[$fieldName]))
				unset($select[$fieldName]);
		}

		$restrictedFields = array('HANDLER', 'HANDLER_CODE', 'HANDLER_NAME');
		foreach ($restrictedFields as $fieldName)
		{
			if (isset($allowedFields[$fieldName]))
				unset($allowedFields[$fieldName]);
		}

		foreach (array_keys($order) as $orderKey)
		{
			if (!isset($allowedFields[$orderKey]))
				unset($order[$orderKey]);
		}

		$regExp = '/^([!><=%?][><=%]?[<]?|)'.'('.implode('|', array_keys($allowedFields)).')'.'$/';
		foreach (array_keys($filter) as $filterKey)
		{
			$matches = array();
			if (!preg_match($regExp, $filterKey, $matches))
				unset($filter[$filterKey]);
		}
	}
}

class CCrmMeasureRestProxy extends CCrmRestProxyBase
{
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		$fields = Bitrix\Crm\Measure::getFieldsInfo();
		foreach ($fields as $code=>&$field)
		{
			$field['CAPTION'] = Bitrix\Crm\Measure::getFieldCaption($code);
		}
		return $fields;
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$code = isset($fields['CODE']) ? trim($fields['CODE']) : '';
		if($code === '')
		{
			$errors[] = 'Please specify a code for the unit of measurement. The code should be a positive integer number.';
			return false;
		}
		elseif(preg_match('/^[0-9]+$/', $code) !== 1)
		{
			$errors[] = 'The CODE of unit of measurement can include only numbers.';
			return false;
		}
		else
		{
			$code = (int)$code;
		}

		$title = isset($fields['MEASURE_TITLE']) ? trim($fields['MEASURE_TITLE']) : '';
		if($title == '')
		{
			$errors[] = 'Please provide the name for the unit of measurement.';
			return false;
		}

		$result = CCatalogMeasure::getList(array(), array('=CODE' => $code));
		if(is_array($result->Fetch()))
		{
			$errors[] = 'A unit of measurement with the CODE "'.$code.'" already exists.';
			return false;
		}
		else
		{
			$result = CCatalogMeasure::add($fields);
			if($result <= 0)
			{
				if($exception = $APPLICATION->GetException())
					$errors[] = $exception->GetString();
				else
					$errors[] = 'Unknown error when creating unit of measurement.';
				return false;
			}
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationReadPermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$res = CCatalogMeasure::getList(array(), array('=ID' => $ID), false, false, array());
		$result = $res ? $res->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationReadPermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		return CCatalogMeasure::getList($order, $filter, false, $navigation, $select);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$res = CCatalogMeasure::getList(array(), array('=ID' => $ID), false, false, array('ID'));
		$result = $res ? $res->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}
		unset($res);

		$result = CCatalogMeasure::update($ID, $fields);
		if($result !== $ID)
		{
			if($exception = $APPLICATION->GetException())
				$errors[] = $exception->GetString();
			else
				$errors[] = 'Unknown error when updating unit of measurement.';
			return false;
		}

		return true;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$result = CCatalogMeasure::delete($ID);
		if($result !== true)
			$errors[] = 'Error when deleting unit of measurement.';

		return $result;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmMeasureRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmMeasureAdd'] = self::createEventInfo(
			'catalog',
			'MeasureOnAfterAdd',
			$callback
		);
		$bindings[CRestUtil::EVENTS]['onCrmMeasureUpdate'] = self::createEventInfo(
			'catalog',
			'MeasureOnAfterUpdate',
			$callback
		);
		$bindings[CRestUtil::EVENTS]['onCrmMeasureDelete'] = self::createEventInfo(
			'catalog',
			'MeasureOnAfterDelete',
			$callback
		);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmmeasureadd':
				if(!isset($arParams[0]) || !($arParams[0] instanceof Main\Entity\Event))
				{
					throw new RestException("Bad result of event \"{$eventName}\"");
				}
				/** @var Main\Entity\Event $event */
				$event = $arParams[0];
				$id = (int)$event->getParameter('id');
				unset($event);
				if($id <= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}
				else
				{
					return array('FIELDS' => array('ID' => $id));
				}
				break;
			case 'oncrmmeasureupdate':
			case 'oncrmmeasuredelete':
				if(!isset($arParams[0]) || !($arParams[0] instanceof Main\Entity\Event))
				{
					throw new RestException("Bad result of event \"{$eventName}\"");
				}
				/** @var Main\Entity\Event $event */
				$event = $arParams[0];
				$primary = $event->getParameter('id');
				unset($event);
				$id = (!empty($primary['ID'])? (int)$primary['ID'] : 0);
				if($id <= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}
				else
				{
					return array('FIELDS' => array('ID' => $id));
				}
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}

class CCrmTimelineCommentRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'CREATED' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ENTITY_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'ENTITY_TYPE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'AUTHOR_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Immutable
					)
				),
				'COMMENT' => array(
					'TYPE' => 'string'
				),
				'FILES' => array(
					'TYPE' => 'attached_diskfile',
					'ALIAS' => 'CRM_TIMELINE',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple),
				)
			);

			foreach ($this->FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = Loc::getMessage("CRM_REST_TIMELINE_COMMENT_FIELD_".$code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGet($ID, &$errors)
	{
		$entityId =
		$entityTypeId = null;

		$commentRaw = \Bitrix\Crm\Timeline\Entity\TimelineTable::getById($ID);
		if (!($comment = $commentRaw->fetch()))
		{
			$errors[] = 'Not found.';
			return false;
		}

		$bindingsDataRaw = \Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList([
			'filter' => ['OWNER_ID' => $ID]
		]);

		while ($binding = $bindingsDataRaw->fetch())
		{
			if (Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']))
			{
				$entityId = $binding['ENTITY_ID'];
				$entityTypeId = $binding['ENTITY_TYPE_ID'];
				break;
			}
		}

		if (empty($entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$comment['ENTITY_ID'] = $entityId;
		$comment['ENTITY_TYPE'] = mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId));

		return $this->prepareGetResult($comment);
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$entityId = (int)$filter['ENTITY_ID'];
		$entityTypeId = CCrmOwnerType::ResolveID(mb_strtoupper($filter['ENTITY_TYPE']));

		if (!Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$params = [
			'filter' => [
				'TYPE_ID' => \Bitrix\Crm\Timeline\TimelineType::COMMENT,
				'BINDINGS.ENTITY_ID' => $entityId,
				'BINDINGS.ENTITY_TYPE_ID' => $entityTypeId,
			]
		];

		if (is_array($order))
		{
			$sortFields = [];
			foreach ($order as $fieldName => $direction)
			{
				if (in_array($fieldName, ['ID', 'CREATED', 'AUTHOR_ID'], true))
				{
					$sortFields[$fieldName] = ($direction === 'DESC') ? 'DESC' : 'ASC';
				}
			}
			if (!empty($sortFields))
			{
				$params['order'] = $sortFields;
			}
		}

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * ($page - 1);

		$params['limit'] = $limit;
		$params['offset'] = $offset;
		$params['count_total'] = true;

		$dataRaw = \Bitrix\Crm\Timeline\Entity\TimelineTable::getList($params);
		$items = [];
		while($fields = $dataRaw->fetch())
		{
			$fields['ENTITY_ID'] = $entityId;
			$fields['ENTITY_TYPE'] = mb_strtolower(CCrmOwnerType::ResolveName($entityTypeId));
			$items[] = $this->prepareGetResult($fields, $select);
		}

		$dbResult = new CDBResult();
		$dbResult->InitFromArray($items);
		$dbResult->NavStart($limit, false, $page);

		// reassign pager values because original NavStart method does not support array response limited by limit offset.
		$dbResult->NavPageCount = ceil($dataRaw->getCount() / $limit);
		$dbResult->NavRecordCount = $dataRaw->getCount();
		$dbResult->NavPageNomer = $page;

		return $dbResult;
	}
	private function prepareGetResult(array $comment, $select = null)
	{
		$result = [];
		$resultFieldNames = ['ID', 'ENTITY_ID', 'ENTITY_TYPE', 'CREATED', 'COMMENT', 'AUTHOR_ID'];
		if (!empty($select) && is_array($select))
		{
			$resultFieldNames = array_intersect($resultFieldNames, $select);
		}
		foreach ($resultFieldNames as $fieldName)
		{
			$result[$fieldName] = $comment[$fieldName];
		}
		if ($comment['SETTINGS']['HAS_FILES'] === 'Y')
		{
			if (Main\Config\Option::get('disk', 'successfully_converted', false)
				&& Main\ModuleManager::isModuleInstalled('disk')
			)
			{
				$result['FILES'] = [];
				$fileUFField = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(\Bitrix\Crm\Timeline\CommentController::UF_FIELD_NAME, $comment['ID']);
				$files = $fileUFField[\Bitrix\Crm\Timeline\CommentController::UF_COMMENT_FILE_NAME];
				if (!empty($files) && is_array($files['VALUE']))
				{
					$result['FILES'] = $files['VALUE'];
				}
			}
		}

		return $result;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'COMMENT')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			switch ($nameSuffix)
			{
				case 'FIELDS':
				case 'ADD':
				case 'GET':
				case 'LIST':
				case 'UPDATE':
				case 'DELETE':
					return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
		}

		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = ['CCrmTimelineCommentRestProxy', 'processEvent'];

		$bindings[CRestUtil::EVENTS]['onCrmTimelineCommentAdd'] = self::createEventInfo('crm', 'OnAfterCrmTimelineCommentAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmTimelineCommentUpdate'] = self::createEventInfo('crm', 'OnAfterCrmTimelineCommentUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmTimelineCommentDelete'] = self::createEventInfo('crm', 'OnAfterCrmTimelineCommentDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch(mb_strtolower($eventName))
		{
			case 'oncrmtimelinecommentadd':
			case 'oncrmtimelinecommentupdate':
			case 'oncrmtimelinecommentdelete':
				/* @var Main\Event $event */
				$event = $arParams[0];
				$ID = $event->getParameter('ID');
				break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID === '')
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
}

class CCrmTimelineBindingRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'OWNER_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'ENTITY_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'ENTITY_TYPE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				)
			);

			foreach ($this->FIELDS_INFO  as $code=>&$field)
			{
				$field['CAPTION'] = Loc::getMessage("CRM_REST_TIMELINE_BINDINGS_FIELD_".$code);
			}
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$ownerId = (int)$filter['OWNER_ID'];
		if(!$this->checkEntityID($ownerId))
		{
			throw new RestException('OWNER_ID is not defined or invalid.');
		}

		$params = [
			'filter' => ['OWNER_ID' => $ownerId]
		];

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;

		$dataRaw = \Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList($params);
		$items = [];
		while ($fields = $dataRaw->fetch())
		{
			$items[] = [
				'OWNER_ID' => $fields['OWNER_ID'],
				'ENTITY_ID' => $fields['ENTITY_ID'],
				'ENTITY_TYPE' => mb_strtolower(\CCrmOwnerType::ResolveName($fields['ENTITY_TYPE_ID']))
			];
		}
		$dbResult = new CDBResult();
		$dbResult->InitFromArray($items);
		$dbResult->NavStart($limit, false, $page);
		return $dbResult;
	}
	protected function bind(array $fields = [])
	{
		if(!$this->checkEntityID((int)$fields['OWNER_ID']))
		{
			throw new RestException('OWNER_ID is not defined or invalid.');
		}

		if(!$this->checkEntityID((int)$fields['ENTITY_ID']))
		{
			throw new RestException('ENTITY_ID is not defined or invalid.');
		}

		$entityTypeId = null;
		if (isset($fields['ENTITY_TYPE']))
		{
			$entityTypeName = mb_strtoupper($fields['ENTITY_TYPE']);
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		}

		if (!\CCrmOwnerType::IsEntity($entityTypeId))
		{
			throw new RestException('ENTITY_TYPE is not defined or invalid.');
		}

		if (!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($entityTypeId, (int)$fields['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			\Bitrix\Crm\Timeline\Entity\TimelineBindingTable::upsert([
				'OWNER_ID' => (int)$fields['OWNER_ID'],
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => (int)$fields['ENTITY_ID']
			]);
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}

		return true;
	}
	protected function unbind(array $fields = [])
	{
		if(!$this->checkEntityID((int)$fields['OWNER_ID']))
		{
			throw new RestException('OWNER_ID is not defined or invalid.');
		}

		if(!$this->checkEntityID((int)$fields['ENTITY_ID']))
		{
			throw new RestException('ENTITY_ID is not defined or invalid.');
		}

		$entityTypeId = null;
		if (isset($fields['ENTITY_TYPE']))
		{
			$entityTypeName = mb_strtoupper($fields['ENTITY_TYPE']);
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		}

		if (!\CCrmOwnerType::IsEntity($entityTypeId))
		{
			throw new RestException('ENTITY_TYPE is not defined or invalid.');
		}

		if (!Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($entityTypeId, (int)$fields['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$resultBind = Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList([
			'filter' => [
				'OWNER_ID' => (int)$fields['OWNER_ID'],
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => (int)$fields['ENTITY_ID']
			],
		]);

		if (!$resultBind->fetch())
		{
			throw new RestException('Not found.');
		}

		try
		{
			\Bitrix\Crm\Timeline\Entity\TimelineBindingTable::delete(array(
				'OWNER_ID' => (int)$fields['OWNER_ID'],
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => (int)$fields['ENTITY_ID']
			));

		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}

		return true;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name === 'BINDINGS')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if ($nameSuffix === 'LIST' || $nameSuffix === 'FIELDS' )
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
			elseif ($nameSuffix === 'BIND')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields');
				return $this->bind($fields);
			}
			elseif ($nameSuffix === 'UNBIND')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields');
				return $this->unbind($fields);
			}
		}
		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}
class CCrmWebformRestProxy implements ICrmRestProxy
{
	/** @var CRestServer|null  */
	private $server = null;

	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if ($name === 'CONFIGURATION')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return array('URL' => CCrmUrlUtil::ToAbsoluteUrl(Bitrix\Crm\WebForm\Manager::getUrl()));
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmSiteButtonRestProxy implements ICrmRestProxy
{
	/** @var CRestServer|null  */
	private $server = null;

	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if ($name === 'CONFIGURATION')
		{
			$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return array('URL' => CCrmUrlUtil::ToAbsoluteUrl(Bitrix\Crm\SiteButton\Manager::getUrl()));
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmEntityEditorRestProxy implements ICrmRestProxy
{
	protected $entityTypeID = CCrmOwnerType::Undefined;
	function __construct($entityTypeID)
	{
		$this->setEntityTypeID($entityTypeID);
	}

	public function setEntityTypeID($entityTypeID)
	{
		$entityTypeID = (int)$entityTypeID;

		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new RestException("Parameter 'entityTypeID' is not defined");
		}

		$this->entityTypeID = $entityTypeID;
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	/** @var CRestServer|null  */
	private $server = null;

	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = mb_strtoupper($name);
		if($name !== 'CONFIGURATION')
		{
			throw new RestException("Resource '{$name}' is not supported in current context.");
		}

		if(!\Bitrix\Crm\Entity\EntityEditorConfig::isEntityTypeSupported($this->entityTypeID))
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			throw new RestException("The entity type '{$entityTypeName}' is not supported in current context.");
		}

		$userID = (int)\CCrmRestHelper::resolveParam(
			$arParams,
			\CCrmRestHelper::resolveParamName($arParams, array('user', 'id')),
			0
		);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$scope = \CCrmRestHelper::resolveParam($arParams, 'scope', '');
		if ($scope !== \Bitrix\Crm\Entity\EntityEditorConfigScope::COMMON)
		{
			$scope = \Bitrix\Crm\Entity\EntityEditorConfigScope::PERSONAL;
		}

		$extras = \CCrmRestHelper::resolveArrayParam($arParams, 'extras', null);
		if(!is_array($extras))
		{
			$extras = array();
		}

		if(!empty($extras))
		{
			if (\CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeID))
			{
				\CCrmRestHelper::renameParam($extras, ['category', 'id'], 'CATEGORY_ID');
			}
			elseif ($this->entityTypeID === \CCrmOwnerType::Deal)
			{
				\CCrmRestHelper::renameParam($extras, ['deal', 'category', 'id'], 'DEAL_CATEGORY_ID');
			}
			elseif ($this->entityTypeID === \CCrmOwnerType::Lead)
			{
				\CCrmRestHelper::renameParam($extras, ['lead', 'customer', 'type'], 'LEAD_CUSTOMER_TYPE');
			}
		}

		$config = new \Bitrix\Crm\Entity\EntityEditorConfig($this->entityTypeID, $userID, $scope, $extras);

		$nameSuffix = mb_strtoupper(!empty($nameDetails)? implode('_', $nameDetails) : '');
		if($nameSuffix === 'GET')
		{
			if(!$config->canDoOperation(\Bitrix\Crm\Entity\EntityEditorConfigOperation::GET))
			{
				throw new RestException('Access denied.');
			}

			return $config->get();
		}
		elseif($nameSuffix === 'SET')
		{
			if(!$config->canDoOperation(\Bitrix\Crm\Entity\EntityEditorConfigOperation::SET))
			{
				throw new RestException('Access denied.');
			}

			$data = \CCrmRestHelper::resolveArrayParam($arParams, 'data', null);
			if(!is_array($data))
			{
				throw new RestException("Parameter 'data' must be array.");
			}

			$data = $config->normalize($data);

			if(empty($data))
			{
				throw new RestException("There are no data to write.");
			}

			$errors = array();
			if(!$config->check($data, $errors))
			{
				throw new RestException(implode("\n", $errors));
			}

			$data = $config->sanitize($data);
			return !empty($data) && $config->set($data);
		}
		elseif($nameSuffix === 'RESET')
		{
			if(!$config->canDoOperation(\Bitrix\Crm\Entity\EntityEditorConfigOperation::RESET))
			{
				throw new RestException('Access denied.');
			}

			$config->reset();
			return true;
		}
		elseif($nameSuffix === 'FORCECOMMONSCOPEFORALL')
		{
			if(!$config->canDoOperation(\Bitrix\Crm\Entity\EntityEditorConfigOperation::FORCE_COMMON_SCOPE_FOR_ALL))
			{
				throw new RestException('Access denied.');
			}

			$config->forceCommonScopeForAll();
			return true;
		}
		throw new RestException("Operation '{$nameSuffix}' is not supported in current context.");
	}
}
