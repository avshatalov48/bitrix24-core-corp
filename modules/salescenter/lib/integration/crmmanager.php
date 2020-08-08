<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Crm\Activity\Provider\OpenLine;
use Bitrix\Crm\Activity\Provider\WebForm;
use Bitrix\Crm\Activity\Provider\Sms;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\SalesCenter;
use Bitrix\Main;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Automation;
use Bitrix\Crm\Order;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\Timeline;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Requisite\EntityLink;

Main\Localization\Loc::loadMessages(__FILE__);

class CrmManager extends Base
{
	protected $dealsLink;
	protected $contactsLink;
	protected $forms;

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'crm';
	}

	/**
	 * @return array
	 */
	public function getWebForms()
	{
		if($this->forms === null)
		{
			$this->forms = [];
			if($this->isEnabled)
			{
				$formList = FormTable::getList([
					'select' => ['ID', 'NAME', 'SECURITY_CODE'],
					'filter' => [
						'=ACTIVE' => 'Y',
					],
					'order' => [
						'IS_CALLBACK_FORM' => 'ASC',
						'ID' => 'DESC',
					],
				]);
				while($form = $formList->fetch())
				{
					$this->forms[$form['ID']] = $form;
				}
			}
		}

		return $this->forms;
	}

	/**
	 * @param bool $fromSettings
	 * @return false|string
	 */
	public function getDealsLink($fromSettings = false)
	{
		if($this->dealsLink === null)
		{
			$this->dealsLink = false;
			if($this->isEnabled)
			{
				$viewNameToId = [
					'list' => DealSettings::VIEW_LIST,
					'kanban' => DealSettings::VIEW_KANBAN,
					'calendar' => DealSettings::VIEW_CALENDAR,
				];

				$defaultView = $this->getDefaultDealListLinks();

				$this->dealsLink = $defaultView[DealSettings::VIEW_LIST];
				if($fromSettings)
				{
					$settingsDefaultView = DealSettings::getCurrent()->getDefaultListViewID();
					if(isset($defaultView[$settingsDefaultView]))
					{
						$this->dealsLink = $defaultView[$settingsDefaultView];
					}

					$navigationIndex = \CUserOptions::GetOption('crm.navigation', 'index');
					if(is_array($navigationIndex))
					{
						foreach($navigationIndex as $code => $value)
						{
							if(mb_strtoupper($code) === 'DEAL')
							{
								$parts = explode(':', $value);
								if(is_array($parts) && count($parts) >= 2)
								{
									$page = $parts[0];
								}
								else
								{
									$page = $value;
								}
								$this->dealsLink = $defaultView[$viewNameToId[$page]];
							}
						}
					}
				}

				$this->dealsLink = \CComponentEngine::makePathFromTemplate($this->dealsLink);
			}
		}

		return $this->dealsLink;
	}

	/**
	 * @return array
	 */
	protected function getDefaultDealListLinks()
	{
		$defaultView = [
			DealSettings::VIEW_LIST => CrmCheckPath('PATH_TO_DEAL_LIST', '', '#SITE_DIR#crm/deal/list/'),
			DealSettings::VIEW_KANBAN => '#SITE_DIR#crm/deal/kanban/',
			DealSettings::VIEW_CALENDAR => '#SITE_DIR#crm/deal/calendar/',
		];

		$currentCategoryID = \CUserOptions::GetOption('crm', 'current_deal_category', -1);
		if($currentCategoryID >= 0)
		{
			$defaultView[DealSettings::VIEW_LIST] = \CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#crm/deal/category/#category_id#/',
				['category_id' => $currentCategoryID]
			);
			$defaultView[DealSettings::VIEW_KANBAN] = \CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#crm/deal/kanban/category/#category_id#/',
				['category_id' => $currentCategoryID]
			);
			$defaultView[DealSettings::VIEW_CALENDAR] = \CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#crm/deal/calendar/category/#category_id#/',
				['category_id' => $currentCategoryID]
			);
		}

		return $defaultView;
	}

	/**
	 * @param bool $fromSettings
	 * @return false|string
	 */
	public function getContactsLink($fromSettings = false)
	{
		if($this->contactsLink === null)
		{
			$this->contactsLink = false;
			if($this->isEnabled)
			{
				$viewNameToId = [
					'list' => ContactSettings::VIEW_LIST,
				];

				$defaultView = [
					ContactSettings::VIEW_LIST => CrmCheckPath('PATH_TO_CONTACT_LIST', '', '#SITE_DIR#crm/contact/list/'),
				];

				$this->contactsLink = $defaultView[ContactSettings::VIEW_LIST];

				if($fromSettings)
				{
					$settingsDefaultView = ContactSettings::getCurrent()->getDefaultListViewID();
					if(isset($defaultView[$settingsDefaultView]))
					{
						$this->contactsLink = $defaultView[$settingsDefaultView];
					}

					$navigationIndex = \CUserOptions::GetOption('crm.navigation', 'index');
					if(is_array($navigationIndex))
					{
						foreach($navigationIndex as $code => $value)
						{
							if(mb_strtoupper($code) === 'CONTACT')
							{
								$parts = explode(':', $value);
								if(is_array($parts) && count($parts) >= 2)
								{
									$page = $parts[0];
								}
								else
								{
									$page = $value;
								}
								$this->contactsLink = $defaultView[$viewNameToId[$page]];
							}
						}
					}
				}

				$this->contactsLink = \CComponentEngine::makePathFromTemplate($this->contactsLink);
			}
		}

		return $this->contactsLink;
	}

	/**
	 * @return array
	 */
	public function getSaleAdminPages()
	{
		$result = [];

		if($this->isEnabled)
		{
			\CBitrixComponent::includeComponentClass("bitrix:crm.admin.page.controller");
			$crmAdminPageController = new \CCrmAdminPageController();
			$crmAdminPageController->prepareComponentParams([
				"SEF_FOLDER" => "/shop/settings/",
			]);

			$shopUrls = $crmAdminPageController->getShopUrls();

			$catalogUrlCode = $this->getCatalogUrlCode();
			if($catalogUrlCode && isset($shopUrls[$catalogUrlCode]))
			{
				$result['catalog'] = $shopUrls[$catalogUrlCode];
			}

			$result['sale_cashbox_check'] = $shopUrls['sale_cashbox_check'];
			$result['cat_vat_admin'] = $shopUrls['cat_vat_admin'];
			$result['sale_tax'] = $shopUrls['sale_tax'];
			$result['sale_tax_rate'] = $shopUrls['sale_tax_rate'];
			$result['sale_tax_exempt'] = $shopUrls['sale_tax_exempt'];
			$result['cat_group_admin'] = $shopUrls['cat_group_admin'];
			$result['cat_round_list'] = $shopUrls['cat_round_list'];
			$result['cat_extra'] = $shopUrls['cat_extra'];
			$result['cat_measure_list'] = $shopUrls['cat_measure_list'];
		}

		return $result;
	}

	/**
	 * @return false|string
	 */
	protected function getCatalogUrlCode()
	{
		$catalogId = \CCrmCatalog::GetDefaultID();
		if($catalogId > 0)
		{
			return 'menu_catalog_'.$catalogId;
		}

		return false;
	}

	/**
	 * @param $activityId
	 * @param array $fields
	 */
	public static function onActivityAdd($activityId, array $fields)
	{
		if($fields['PROVIDER_ID'] === WebForm::PROVIDER_ID)
		{
			$bindings = [];
			if(isset($fields['BINDINGS']) && is_array($fields['BINDINGS']))
			{
				foreach($fields['BINDINGS'] as $binding)
				{
					$bindings[$binding['OWNER_TYPE_ID']][$binding['OWNER_ID']] = $binding['OWNER_ID'];
				}
			}
			if(empty($bindings) || empty($fields['SUBJECT']) || empty($fields['ID']) || !ImOpenLinesManager::getInstance()->isEnabled())
			{
				return;
			}

			if(isset($bindings[\CCrmOwnerType::Lead]))
			{
				$list = LeadTable::getList(['select' => ['COMPANY_ID'], 'filter' => [
					'=ID' => $bindings[\CCrmOwnerType::Lead],
					'!COMPANY_ID' => 0,
				]]);
				while($lead = $list->fetch())
				{
					$bindings[\CCrmOwnerType::Company][$lead['COMPANY_ID']] = $lead['COMPANY_ID'];
				}
				$list = LeadContactTable::getList(['select' => ['CONTACT_ID'], 'filter' => [
					'=LEAD_ID' => $bindings[\CCrmOwnerType::Lead],
					'!CONTACT_ID' => 0,
				]]);
				while($leadContact = $list->fetch())
				{
					$bindings[\CCrmOwnerType::Contact][$leadContact['CONTACT_ID']] = $leadContact['CONTACT_ID'];
				}
			}
			if(isset($bindings[\CCrmOwnerType::Deal]))
			{
				$list = DealTable::getList(['select' => ['COMPANY_ID'], 'filter' => [
					'=ID' => $bindings[\CCrmOwnerType::Deal],
					'!COMPANY_ID' => 0,
				]]);
				while($deal = $list->fetch())
				{
					$bindings[\CCrmOwnerType::Company][$deal['COMPANY_ID']] = $deal['COMPANY_ID'];
				}
				$list = DealContactTable::getList(['select' => ['CONTACT_ID'], 'filter' => [
					'=DEAL_ID' => $bindings[\CCrmOwnerType::Deal],
					'!CONTACT_ID' => 0,
				]]);
				while($dealContact = $list->fetch())
				{
					$bindings[\CCrmOwnerType::Contact][$dealContact['CONTACT_ID']] = $dealContact['CONTACT_ID'];
				}
			}

			$isOwnersFilterSet = false;
			$ownersFilter = Query::filter()->logic('or');
			foreach($bindings as $type => $ids)
			{
				$ownersFilter->addCondition(
					Query::filter()
						->logic('and')
						->where('OWNER_TYPE_ID', '=', $type)
						->whereIn('OWNER_ID', array_values($ids))
				);
				$isOwnersFilterSet = true;
			}
			if(!$isOwnersFilterSet)
			{
				return;
			}

			$activityFilter = Query::filter()
				->logic('and')
				->addCondition(
					Query::filter()
						->where('PROVIDER_ID', '=', OpenLine::ACTIVITY_PROVIDER_ID)
						->where('COMPLETED', '=', 'N')
						->where('ASSOCIATED_ENTITY_ID', '>', 0)
					)
				->addCondition($ownersFilter);

			$sessionIds = [];
			try
			{
				$list = ActivityTable::getList(['select' => ['ID', 'ASSOCIATED_ENTITY_ID'], 'filter' => $activityFilter]);
				while($activity = $list->fetch())
				{
					$sessionIds[$activity['ASSOCIATED_ENTITY_ID']] = $activity['ASSOCIATED_ENTITY_ID'];
				}
			}
			finally
			{

			}
			foreach($sessionIds as $sessionId)
			{
				ImOpenLinesManager::getInstance()->sendActivityNotify($fields, $sessionId);
			}
		}
	}

	/**
	 * @param int $activityId
	 * @return bool|string
	 */
	public function getActivityViewUrl($activityId)
	{
		$activityPath = \CComponentEngine::makeComponentPath(
			'bitrix:crm.activity.planner'
		);
		$activityPath = getLocalPath('components' . $activityPath . '/slider.php');
		if($activityPath)
		{
			$uriView = new \Bitrix\Main\Web\Uri('/bitrix/components/bitrix/crm.activity.planner/slider.php');
			$uriView->addParams(array(
				'site_id' => SITE_ID,
				'sessid' => bitrix_sessid_get(),
				'ajax_action' => 'ACTIVITY_VIEW',
				'activity_id' => $activityId,
			));

			return $uriView->getLocator();
		}

		return false;
	}

	/**
	 * @param $ownerTypeId
	 * @param $ownerId
	 * @return array
	 */
	public function getClientInfo($ownerTypeId, $ownerId)
	{
		$clientInfo = [];

		if($this->isEnabled)
		{
			if($ownerTypeId == \CCrmOwnerType::Lead)
			{
				$lead = LeadTable::getById($ownerId)->fetch();
				if($lead)
				{
					$clientInfo['COMPANY_ID'] = (int)$lead['COMPANY_ID'];
					$clientInfo['CONTACT_IDS'] = LeadContactTable::getContactLeadIDs($ownerId);
				}
			}
			elseif($ownerTypeId == \CCrmOwnerType::Deal)
			{
				$deal = DealTable::getById($ownerId)->fetch();
				if($deal)
				{
					$clientInfo['CONTACT_IDS'] = DealContactTable::getDealContactIDs($ownerId);
					$clientInfo['COMPANY_ID'] = (int)$deal['COMPANY_ID'];
					$clientInfo['DEAL_ID'] = $ownerId;
				}
			}
			elseif($ownerTypeId == \CCrmOwnerType::Contact)
			{
				$clientInfo['CONTACT_IDS'] = [(int)$ownerId];
			}
			elseif($ownerTypeId == \CCrmOwnerType::Company)
			{
				$clientInfo['COMPANY_ID'] = (int)$ownerId;
			}
			elseif($ownerTypeId == \CCrmOwnerType::Order)
			{
				$order = Order\Order::load($ownerId);
				if($order)
				{
					$collection = $order->getContactCompanyCollection();
					$company = $collection->getPrimaryCompany();
					if($company)
					{
						$clientInfo['COMPANY_ID'] = (int)$company->getField('ENTITY_ID');
					}
					$contacts = $collection->getContacts();
					foreach($contacts as $contact)
					{
						$clientInfo['CONTACT_IDS'][] = (int)$contact->getField('ENTITY_ID');
					}
				}
			}
		}

		return $clientInfo;
	}

	/**
	 * @return bool
	 */
	public function isShowSmsTile()
	{
		return (
			$this->isEnabled && class_exists('\Bitrix\Crm\Integration\SalesCenterManager')
		);
	}

	public function saveTriggerOnOrderPaid($dealId, $stageId)
	{
		$target = $this->getDealAutomationTarget();

		if ($target === null)
		{
			return 0;
		}

		$dealCategoryId = \CCrmDeal::GetCategoryID($dealId);
		$stages = \CCrmDeal::GetStages($dealCategoryId);

		$triggers = $target->getTriggers(array_keys($stages));
		$trigger = $this->findTriggerOnOrderPaid($triggers);
		if ($trigger)
		{
			if ($trigger['DOCUMENT_STATUS'] === $stageId)
			{
				return $trigger['ID'];
			}

			$trigger['DELETED'] = 'Y';
			$target->setTriggers([$trigger]);
		}

		if ($stageId)
		{
			$result = $target->setTriggers([
				[
					'DOCUMENT_STATUS' => $stageId,
					'CODE' => Automation\Trigger\OrderPaidTrigger::getCode(),
					'NAME' => Automation\Trigger\OrderPaidTrigger::getName()
				]
			]);

			if ($result)
			{
				return $result[0]['ID'];
			}
		}

		return 0;
	}

	protected function getDealAutomationTarget()
	{
		if (!Loader::includeModule('bizproc'))
		{
			return null;
		}

		$runtime = \CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$documentService = $runtime->GetService('DocumentService');

		return $documentService->createAutomationTarget([
			'crm',
			\CCrmDocumentDeal::class,
			'DEAL',
		]);
	}

	protected function findTriggerOnOrderPaid(array $triggers)
	{
		foreach ($triggers as $trigger)
		{
			if ($trigger['CODE'] === Automation\Trigger\OrderPaidTrigger::getCode())
			{
				return $trigger;
			}
		}

		return [];
	}

	public function getStageWithOrderPaidTrigger($dealId)
	{
		$target = $this->getDealAutomationTarget();

		$dealCategoryId = \CCrmDeal::GetCategoryID($dealId);
		$stages = \CCrmDeal::GetStages($dealCategoryId);

		$triggers = $target->getTriggers(array_keys($stages));
		$trigger = $this->findTriggerOnOrderPaid($triggers);
		if ($trigger)
		{
			return $trigger['DOCUMENT_STATUS'];
		}

		return '';
	}

	public function sendOrderBySms(Order\Order $order, array $sendingInfo)
	{
		$entityCommunication = $this->getEntityCommunication($order);
		if ($entityCommunication === null)
		{
			return false;
		}

		if (mb_strpos($sendingInfo['provider'], '|') === false)
		{
			$senderId = $sendingInfo['provider'];
			$messageFrom = $this->getMessageFromValue($senderId);
		}
		else
		{
			$senderId = 'rest';
			$messageFrom = $sendingInfo['provider'];
		}

		$messageTo = $this->getEntityCommunicationPhone($entityCommunication);
		$messageBody = str_replace(
			'#LINK#',
			LandingManager::getInstance()->getUrlInfoByOrder($order)['shortUrl'],
			$sendingInfo['text']
		);
		$orderId = $order->getId();
		$dealId = $order->getDealBinding()->getDealId();

		if (
			!$senderId
			|| $messageTo === ''
		)
		{
			return false;
		}

		$bindings = [
			[
				'OWNER_TYPE_ID' => \CCrmOwnerType::Order,
				'OWNER_ID' => $orderId,
			],
			[
				'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
				'OWNER_ID' => $dealId,
			],
			[
				'OWNER_TYPE_ID' => $entityCommunication::getEntityType(),
				'OWNER_ID' => $entityCommunication->getField('ENTITY_ID')
			]
		];

		$result =  Integration\SmsManager::sendMessage([
			'SENDER_ID' => $senderId,
			'AUTHOR_ID' => $order->getField('RESPONSIBLE_ID'),
			'MESSAGE_FROM' => $messageFrom,
			'MESSAGE_TO' => $messageTo,
			'MESSAGE_BODY' => $messageBody,
			'MESSAGE_HEADERS' => [
				'module_id' => 'crm',
				'bindings' => $bindings
			]
		]);

		if($result->isSuccess())
		{
			$this->addTimelineEntryOnSend($order);

			Sms::addActivity([
				'AUTHOR_ID' => $order->getField('RESPONSIBLE_ID'),
				'DESCRIPTION' => $messageBody,
				'ASSOCIATED_ENTITY_ID' => $result->getId(),
				'BINDINGS' => $bindings,
				'COMMUNICATIONS' => [
					[
						'ENTITY_TYPE' => $entityCommunication::getEntityTypeName(),
						'ENTITY_TYPE_ID' => $entityCommunication::getEntityType(),
						'ENTITY_ID' => $entityCommunication->getField('ENTITY_ID'),
						'TYPE' => \CCrmFieldMulti::PHONE,
						'VALUE' => $messageTo
					]
				]
			]);
		}

		return $result->isSuccess();
	}

	/**
	 * @param $senderId
	 * @return mixed|string
	 */
	protected function getMessageFromValue($senderId)
	{
		$fromList = Integration\SmsManager::getSenderFromList($senderId);
		foreach ($fromList as $item)
		{
			if (
				isset($item['id'])
				&& $item['id']
			)
			{
				return $item['id'];
			}
		}

		return '';
	}

	/**
	 * @param Order\Order $order
	 * @return Order\Company|Order\Contact|null
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	protected function getEntityCommunication(Order\Order $order)
	{
		/** @var Order\Contact $contact */
		$contact = $order->getContactCompanyCollection()->getPrimaryContact();
		if ($contact)
		{
			return $contact;
		}

		/** @var Order\Company $company */
		$company = $order->getContactCompanyCollection()->getPrimaryCompany();
		if ($company)
		{
			return $company;
		}

		return null;
	}

	protected function getEntityCommunicationPhone(Order\ContactCompanyEntity $entity)
	{
		$phoneList = \CCrmFieldMulti::GetEntityFields($entity::getEntityTypeName(), $entity->getField('ENTITY_ID'), 'PHONE', true, false);
		foreach ($phoneList as $phone)
		{
			$parser = Main\PhoneNumber\Parser::getInstance();

			return $parser->parse($phone['VALUE'])->format();
		}

		return '';
	}

	public function saveSmsTemplate($template)
	{
		Main\Config\Option::set('salescenter', 'salescenter_sms_template', $template);
	}

	public function getSmsTemplate()
	{
		return Main\Config\Option::get(
			'salescenter',
			'salescenter_sms_template',
			Main\Localization\Loc::getMessage('SALESCENTER_CRMMANAGER_SMS_TEMPLATE_2')
		);
	}

	private function addTimelineEntryOnSend(Order\Order $order): void
	{
		$orderId = $order->getId();
		$dealId = $order->getDealBinding()->getDealId();
		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $orderId,
			]
		];

		if ($order->getDealBinding())
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealId,
			];
		}

		$params = [
			'ORDER_FIELDS' => $order->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderName,
				'FIELDS' => [
					'ORDER_ID' => $orderId,
					'DEAL_ID' => $dealId,
					'SENT' => 'Y',
					'DESTINATION' => 'SMS',
				]
			],
			'BINDINGS' => $bindings
		];

		Timeline\OrderController::getInstance()->onSend($orderId, $params);
	}

	public static function getDefaultMyCompanyPhoneId(): int
	{
		return Main\Config\Option::get(
			'salescenter',
			'salescenter_default_my_company_phone_id',
			0
		);
	}

	public static function setDefaultMyCompanyPhoneId($id): void
	{
		Main\Config\Option::set(
			'salescenter',
			'salescenter_default_my_company_phone_id',
			intval($id)
		);
	}

	public static function getPublishedCompanyName(): string
	{
		$company = \CCrmCompany::GetByID(EntityLink::getDefaultMyCompanyId());

		return (string)$company['TITLE'] ?? '';
	}

	public static function getPublishedCompanyPhone(): array
	{
		$result = [
			'ID' => 0,
			'VALUE'=>''
		];

		$list = [];
		$companyId = EntityLink::getDefaultMyCompanyId();
		if($companyId>0)
		{
			$dbRes = \CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				[
					'ENTITY_ID' => \CCrmOwnerType::CompanyName,
					'ELEMENT_ID' => $companyId,
					'TYPE_ID' => \CCrmFieldMulti::PHONE,
				]
			);
			while ($crmFieldMultiData = $dbRes->Fetch())
			{
				$phoneNumberId = $crmFieldMultiData['ID'];
				if ($phoneNumberId)
				{
					$list[] = [
						'ID' => $crmFieldMultiData['ID'],
						'VALUE'=>$crmFieldMultiData['VALUE']
					];

					if(static::getDefaultMyCompanyPhoneId() == $phoneNumberId)
					{
						$result = [
							'ID' => $crmFieldMultiData['ID'],
							'VALUE'=>$crmFieldMultiData['VALUE']
						];
					}
				}
			}
		}

		if($result['ID'] == 0)
		{
			if(static::getDefaultMyCompanyPhoneId() !== 0)
			{
				static::setDefaultMyCompanyPhoneId(0);
			}

			if(count($list)>0 && isset($list[0]))
			{
				$result = $list[0];
			}
		}


		return $result;
	}

	public static function getPublishedCompanyEmail(): array
	{
		$result = [];
		$companyId = EntityLink::getDefaultMyCompanyId();
		if($companyId>0)
		{
			$dbRes = \CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				[
					'ENTITY_ID' => \CCrmOwnerType::CompanyName,
					'ELEMENT_ID' => $companyId,
					'TYPE_ID' => \CCrmFieldMulti::EMAIL,
				]
			);
			if(($crmFieldMultiData = $dbRes->Fetch()) && $emailId = $crmFieldMultiData['ID'])
			{
				$result = [
					'ID' => $crmFieldMultiData['ID'],
					'VALUE'=>$crmFieldMultiData['VALUE']
				];
			}
		}

		return $result;
	}
}