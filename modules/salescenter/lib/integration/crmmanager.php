<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Crm\Activity\Provider\BaseMessage;
use Bitrix\Crm\AddressTable;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\BindingsMaker\ActivityBindingsMaker;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Restriction\OrderRestriction;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm\Automation;
use Bitrix\Crm\Order;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\Timeline;
use Bitrix\Main\Loader;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Sale\Payment;
use Bitrix\Salescenter\Analytics;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Crm;
use Bitrix\Crm\ClientInfo;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Crm\Workflow\PaymentStage;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Item\Deal;
use Bitrix\SalesCenter\Component\PaymentSlip;
use Bitrix\Salescenter\PaymentSlip\PaymentSlipManager;
use CCrmOwnerType;

Main\Localization\Loc::loadMessages(__FILE__);

class CrmManager extends Base
{
	public const SMS_MODE_PAYMENT = 'payment';
	public const SMS_MODE_COMPILATION = 'compilation';

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
				$formList = FormTable::getDefaultTypeList([
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
			$result['sale_cashbox_correction'] = $shopUrls['sale_cashbox_correction'];
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
			$clientInfo =
				ClientInfo::createFromOwner(
					(int)$ownerTypeId,
					(int)$ownerId
				)
				->toArray()
			;
		}

		return $clientInfo;
	}

	public function isOwnerEntityExists(int $ownerId, int $ownerTypeId): bool
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($ownerTypeId);
		if (!$factory)
		{
			return false;
		}

		$item = $factory->getItem($ownerId, ['ID']);
		if (!$item)
		{
			return false;
		}

		return true;
	}


	/**
	 * @param int $ownerId
	 * @param int $ownerTypeId
	 * @return bool
	 */
	public function isOwnerEntityInFinalStage(int $ownerId, int $ownerTypeId): bool
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($ownerTypeId);
		if ($factory)
		{
			$item = $factory->getItem($ownerId);
			if ($item)
			{
				$stage = $factory->getStage($item->getStageId());
				return $stage && Crm\PhaseSemantics::isFinal($stage->getSemantics());
			}
		}

		return false;
	}

	/**
	 * Get active (not finished) deal that has been converted from the lead.
	 *
	 * @param int $leadId
	 * @return Deal|null
	 */
	public function getLeadActiveDeal(int $leadId): ?Deal
	{
		$factory = Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::Deal);
		if ($factory)
		{
			$items = $factory->getItems([
				'filter' => [
					'=LEAD_ID' => $leadId,
				],
				'order' => [
					'ID' => 'desc',
				],
			]);
			foreach ($items as $item)
			{
				$stage = $factory->getStage($item->getStageId());
				if (!$stage || !Crm\PhaseSemantics::isFinal($stage->getSemantics()))
				{
					return $item;
				}
			}
		}

		return null;
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

	/**
	 * @param $triggerCode
	 * @param $entityId
	 * @param $entityTypeId
	 * @param $stageId
	 * @return int|mixed
	 */
	private function saveTriggerByCode($triggerCode, $entityId, $entityTypeId, $stageId)
	{
		$target = $this->getAutomationTarget($entityTypeId);

		if ($target === null)
		{
			return 0;
		}

		$stages = $this->getStageList($entityId, $entityTypeId);

		$triggers = $target->getTriggers(array_keys($stages));
		$trigger = $this->findTriggerByCode($triggerCode, $triggers);
		if ($trigger)
		{
			if ($trigger['DOCUMENT_STATUS'] === $stageId)
			{
				return $trigger['ID'];
			}

			$trigger['DELETED'] = 'Y';
			$target->setTriggers([$trigger]);
		}

		/** @var Automation\Trigger\BaseTrigger $triggerClassName */
		$triggerClassName = \Bitrix\Crm\Automation\Factory::getTriggerByCode($triggerCode);

		if ($stageId)
		{
			$result = $target->setTriggers([
				[
					'DOCUMENT_STATUS' => $stageId,
					'CODE' => $triggerClassName::getCode(),
					'NAME' => $triggerClassName::getName()
				]
			]);

			if ($result)
			{
				return $result[0]['ID'];
			}
		}

		return 0;
	}

	/**
	 * @param $entityId
	 * @param $entityTypeId
	 * @param $stageId
	 * @return int|mixed
	 */
	public function saveTriggerOnOrderPaid($entityId, $entityTypeId, $stageId)
	{
		if (!Automation\Trigger\OrderPaidTrigger::isSupported($entityTypeId))
		{
			return 0;
		}

		return $this->saveTriggerByCode(
			Automation\Trigger\OrderPaidTrigger::getCode(),
			$entityId,
			$entityTypeId,
			$stageId
		);
	}

	/**
	 * @param $entityId
	 * @param $entityTypeId
	 * @param $stageId
	 * @return int|mixed
	 */
	public function saveTriggerOnDeliveryFinished($entityId, $entityTypeId, $stageId)
	{
		if (!Automation\Trigger\DeliveryFinishedTrigger::isSupported($entityTypeId))
		{
			return 0;
		}

		return $this->saveTriggerByCode(
			Automation\Trigger\DeliveryFinishedTrigger::getCode(),
			$entityId,
			$entityTypeId,
			$stageId
		);
	}

	protected function getAutomationTarget($entityTypeId)
	{
		if (!Loader::includeModule('bizproc'))
		{
			return null;
		}

		$runtime = \CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$documentService = $runtime->GetService('DocumentService');

		return $documentService->createAutomationTarget(
			\CCrmBizProcHelper::ResolveDocumentType($entityTypeId)
		);
	}

	/**
	 * @param string $triggerCode
	 * @param array $triggers
	 * @return array|mixed
	 */
	private function findTriggerByCode(string $triggerCode, array $triggers)
	{
		foreach ($triggers as $trigger)
		{
			if ($trigger['CODE'] === $triggerCode)
			{
				return $trigger;
			}
		}

		return [];
	}

	/**
	 * @param $entityId
	 * @param $entityTypeId
	 * @return mixed|string
	 */
	public function getStageWithOrderPaidTrigger($entityId, $entityTypeId)
	{
		if (!Automation\Trigger\OrderPaidTrigger::isSupported($entityTypeId))
		{
			return '';
		}

		return $this->getStageWithTrigger(
			Automation\Trigger\OrderPaidTrigger::getCode(),
			$entityId,
			$entityTypeId
		);
	}

	/**
	 * @param $entityId
	 * @param $entityTypeId
	 * @return mixed|string
	 */
	public function getStageWithDeliveryFinishedTrigger($entityId, $entityTypeId)
	{
		if (!Automation\Trigger\DeliveryFinishedTrigger::isSupported($entityTypeId))
		{
			return '';
		}

		return $this->getStageWithTrigger(
			Automation\Trigger\DeliveryFinishedTrigger::getCode(),
			$entityId,
			$entityTypeId
		);
	}

	/**
	 * @param $triggerCode
	 * @param $entityId
	 * @param $entityTypeId
	 * @return mixed|string
	 */
	private function getStageWithTrigger($triggerCode, $entityId, $entityTypeId)
	{
		$target = $this->getAutomationTarget($entityTypeId);
		if ($target === null)
		{
			return '';
		}

		$stages = $this->getStageList($entityId, $entityTypeId);

		$triggers = $target->getTriggers(array_keys($stages));
		$trigger = $this->findTriggerByCode($triggerCode, $triggers);
		if ($trigger)
		{
			return $trigger['DOCUMENT_STATUS'];
		}

		return '';
	}

	/**
	 * @param int $compilationId
	 * @param int $dealId
	 * @param array $compilationLink
	 * @param array $sendingInfo
	 * @return bool
	 */
	public function sendCompilationBySms(int $compilationId, int $dealId, array $compilationLink, array $sendingInfo): bool
	{
		$linkForMessage = $compilationLink['link'];

		$messageBody = str_replace(
			'#LINK#',
			$linkForMessage,
			$sendingInfo['text']
		);

		$senderId = (mb_strpos($sendingInfo['provider'], '|') === false) ? $sendingInfo['provider'] : 'rest';

		$messageTo = $this->getDealContactPhone($dealId);
		$responsibleId = \CCrmOwnerType::GetResponsibleID(\CCrmOwnerType::Deal, $dealId);

		$result = Crm\MessageSender\MessageSender::send(
			[
				Crm\Integration\SmsManager::getSenderCode() => [
					'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
					'MESSAGE_BODY' => $messageBody,
					'SENDER_ID' => $senderId,
					'MESSAGE_FROM' => $senderId === 'rest' ? $sendingInfo['provider'] : null,
				]
			],
			[
				'COMMON_OPTIONS' => [
					'PHONE_NUMBER' => $messageTo,
					'USER_ID' => $responsibleId,
					'ADDITIONAL_FIELDS' => [
						'ENTITY_TYPE' => \CCrmOwnerType::ContactName,
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
						'ENTITY_ID' => $this->getPrimaryContact($dealId)['CONTACT_ID'],
						'ENTITIES' => [
							'DEAL' => \CCrmDeal::GetByID($dealId),
						],
						'BINDINGS' => [
							[
								'OWNER_TYPE_ID' => \CCrmOwnerType::Contact,
								'OWNER_ID' => $this->getPrimaryContact($dealId)['CONTACT_ID']
							],
							[
								'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
								'OWNER_ID' => $dealId,
							]
						],
						'ACTIVITY_AUTHOR_ID' => $responsibleId,
						'ACTIVITY_DESCRIPTION' => $messageBody,
						'PRODUCT_IDS' => $compilationLink['productIds'],
						'COMPILATION_ID' => $compilationId,
					]
				]
			]
		);

		return $result->isSuccess();
	}

	public static function onSendCompilation(Main\Event $event): void
	{
		$additionalFields = $event->getParameter('ADDITIONAL_FIELDS');

		if (!$additionalFields)
		{
			return;
		}

		$dealId = (int)($additionalFields['ENTITIES']['DEAL']['ID'] ?? 0);
		$productIds = $additionalFields['PRODUCT_IDS'] ?? [];
		$compilationId = (int)($additionalFields['COMPILATION_ID'] ?? 0);

		if (!$dealId || !$productIds || !$compilationId)
		{
			return;
		}

		$compilationProducts = CatalogManager::getInstance()->getProductVariations($productIds);
		if (empty($compilationProducts))
		{
			return;
		}

		$timelineParams = [
			'SETTINGS' => [
				'DEAL_ID' => $dealId,
				'SENT_PRODUCTS' => $compilationProducts,
				'COMPILATION_ID' => $compilationId,
			]
		];

		Timeline\ProductCompilationController::getInstance()->onCompilationSent($dealId, $timelineParams);
	}

	public function sendPaymentBySms(Order\Payment $payment, array $sendingInfo, Order\Shipment $shipment = null)
	{
		/** @var Order\Order $order */
		$order = $payment->getOrder();

		$entityCommunication = $order->getContactCompanyCollection()->getEntityCommunication();
		if (!$entityCommunication)
		{
			return false;
		}

		$messageTo = $order->getContactCompanyCollection()->getEntityCommunicationPhone();
		if (!$messageTo)
		{
			return false;
		}

		$urlInfoByOrder = LandingManager::getInstance()->getUrlInfoByOrder(
			$order,
			['paymentId' => $payment->getId()]
		);

		if (!isset($urlInfoByOrder['shortUrl']))
		{
			return false;
		}

		$paymentLink = $urlInfoByOrder['shortUrl'];

		$messageBody = str_replace(
			'#LINK#',
			$paymentLink,
			$sendingInfo['text']
		);

		$senderId = (mb_strpos($sendingInfo['provider'], '|') === false) ? $sendingInfo['provider'] : 'rest';

		$result = Crm\MessageSender\MessageSender::send(
			[
				Crm\Integration\NotificationsManager::getSenderCode() => [
					'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
					'TEMPLATE_CODE' => 'ORDER_LINK',
					'PLACEHOLDERS' => [
						'NAME' => $entityCommunication->getCustomerName(),
						'URL' => $paymentLink,
					],
				],
				Crm\Integration\SmsManager::getSenderCode() => [
					'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
					'MESSAGE_BODY' => $messageBody,
					'SENDER_ID' => $senderId,
					'MESSAGE_FROM' => $senderId === 'rest' ? $sendingInfo['provider'] : null,
				]
			],
			[
				'COMMON_OPTIONS' => [
					'PHONE_NUMBER' => $messageTo,
					'USER_ID' => $order->getField('RESPONSIBLE_ID'),
					'ADDITIONAL_FIELDS' => [
						'ENTITY_TYPE' => $entityCommunication::getEntityTypeName(),
						'ENTITY_TYPE_ID' => $entityCommunication::getEntityType(),
						'ENTITY_ID' => $entityCommunication->getField('ENTITY_ID'),
						'ENTITIES' => [
							'ORDER' => $order,
							'PAYMENT' => $payment,
							'SHIPMENT' => $shipment,
						],
						'CREATE_VIEWED_TIMELINE_ITEM' => true,
						'BINDINGS' => ActivityBindingsMaker::makeByPayment(
							$payment,
							[
								'extraBindings' => [
									[
										'TYPE_ID' => $entityCommunication::getEntityType(),
										'ID' => $entityCommunication->getField('ENTITY_ID')
									]
								]
							]
						),
						'ACTIVITY_AUTHOR_ID' => $order->getField('RESPONSIBLE_ID'),
						'ACTIVITY_DESCRIPTION' => $messageBody,
						'HIGHLIGHT_URL' => $paymentLink,
					]
				]
			]
		);

		return $result->isSuccess();
	}

	/**
	 * Send link to payment check (slip) after success payment
	 *
	 * @return bool
	 */
	public function sendPaymentSlipBySms(Payment $payment): bool
	{
		$paymentSlipManager = PaymentSlipManager::getManager();
		if (!$paymentSlipManager)
		{
			return false;
		}

		$paymentSlipConfig = $paymentSlipManager->getConfig();
		if (!$paymentSlipConfig->isSendingEnabled())
		{
			return false;
		}

		$messageBody = str_replace(
			'#CHECK_URL#',
			PaymentSlip::getFullPathToSlip($payment->getId()),
			Loc::getMessage('SALESCENTER_CRMMANAGER_TERMINAL_PAYMENT_PAID_SMS_TEMPLATE')
		);

		$senders = [];
		if ($paymentSlipConfig->isNotificationsEnabled())
		{
			$senders = [
				Crm\Integration\NotificationsManager::getSenderCode() => [
					'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_SALESCENTER_TERMINAL_PAYMENT_PAID,
					'TEMPLATE_CODE' => 'ORDER_PAYMENT_SLIP',
					'PLACEHOLDERS' => [
						'ORDER_PAYMENT' => PaymentSlip::getFullPathToSlip($payment->getId()),
					],
				],
			];
		}
		else if ($senderId = $paymentSlipConfig->getSelectedSmsServiceId())
		{
			$senders = [
				Crm\Integration\SmsManager::getSenderCode() => [
					'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_SALESCENTER_TERMINAL_PAYMENT_PAID,
					'MESSAGE_BODY' => $messageBody,
					'SENDER_ID' => $senderId,
				],
			];
		}
		else
		{
			return false;
		}

		$order = $payment->getOrder();

		$entityCommunication = $order->getContactCompanyCollection()->getEntityCommunication();
		if (!$entityCommunication)
		{
			return false;
		}

		$messageTo = Crm\Terminal\OrderProperty::getTerminalPhoneValue($order);
		if (!$messageTo)
		{
			return false;
		}

		$result = Crm\MessageSender\MessageSender::send(
			$senders,
			[
				'COMMON_OPTIONS' => [
					'PHONE_NUMBER' => $messageTo,
					'USER_ID' => $order->getField('RESPONSIBLE_ID'),
					'ADDITIONAL_FIELDS' => [
						'ENTITY_TYPE' => $entityCommunication::getEntityTypeName(),
						'ENTITY_TYPE_ID' => $entityCommunication::getEntityType(),
						'ENTITY_ID' => $entityCommunication->getField('ENTITY_ID'),
						'ENTITIES' => [
							'ORDER' => $order,
							'PAYMENT' => $payment,
						],
						'BINDINGS' => [
							[
								'OWNER_TYPE_ID' => $entityCommunication::getEntityType(),
								'OWNER_ID' => $entityCommunication->getField('ENTITY_ID'),
							],
						],
						'ACTIVITY_AUTHOR_ID' => $order->getField('RESPONSIBLE_ID'),
						'ACTIVITY_DESCRIPTION' => $messageBody,
					],
				],
			]
		);

		return $result->isSuccess();
	}

	public function sendApplicationLinkBySms(string $phone, string $senderId, int $responsibleId, ItemIdentifier $entity): bool
	{
		$messageBody = Loc::getMessage(
			'SALESCENTER_CRMMANAGER_TERMINAL_PAYMENT_APP_LINK_TEMPLATE',
			[
				'#MANAGER_NAME#' => Main\Engine\CurrentUser::get()->getFormattedName(),
				'#APP_URL#' => MobileManager::MOBILE_APP_LINK,
			],
		);

		$senders = [
			Crm\Integration\SmsManager::getSenderCode() => [
				'ACTIVITY_PROVIDER_TYPE_ID' => BaseMessage::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
				'MESSAGE_BODY' => $messageBody,
				'SENDER_ID' => $senderId,
			],
		];

		$result = Crm\MessageSender\MessageSender::send(
			$senders,
			[
				'COMMON_OPTIONS' => [
					'PHONE_NUMBER' => $phone,
					'USER_ID' => $responsibleId,
					'ADDITIONAL_FIELDS' => [
						'BINDINGS' => [
							[
								'OWNER_TYPE_ID' => $entity->getEntityTypeId(),
								'OWNER_ID' => $entity->getEntityId(),
							],
						],
						'ACTIVITY_AUTHOR_ID' => Main\Engine\CurrentUser::get()->getId(),
						'ACTIVITY_DESCRIPTION' => $messageBody,
					],
				],
			]
		);

		return $result->isSuccess();
	}

	/**
	 * @param string $destination
	 * @param Main\Event $event
	 */
	private static function onSendPayment(string $destination, Main\Event $event)
	{
		/** @var array $messageFields */
		$additionalFields = $event->getParameter('ADDITIONAL_FIELDS');

		if (!$additionalFields)
		{
			return;
		}

		$createViewedTimelineItem = $additionalFields['CREATE_VIEWED_TIMELINE_ITEM'] ?? false;

		/** @var Order\Payment $payment */
		$payment = $additionalFields['ENTITIES']['PAYMENT'] ?? null;
		if ($createViewedTimelineItem && $payment)
		{
			$shipment = $additionalFields['ENTITIES']['SHIPMENT'] ?? null;

			if (static::needAddTimelineEntryOnPaymentSendSms($payment, $destination))
			{
				static::addTimelineEntryOnPaymentSend(
					$payment,
					[
						'DESTINATION' => $destination,
						'PAYMENT_ID' => $payment->getId(),
						'SHIPMENT_ID' => $shipment ? $shipment->getId() : 0,
					]
				);
			}

			PaymentWorkflow::createFrom($payment)->setStage(PaymentStage::SENT_NO_VIEWED);

			$order = $additionalFields['ENTITIES']['ORDER'] ?? null;
			$provider = $additionalFields['SENDER_ID'] ?? null;
			if ($order && $provider)
			{
				$channel = new Order\SendingChannels\Sms($provider);
				$manager = new Order\SendingChannels\Manager();
				$manager->bindChannelToOrder($order, $channel);

				static::addAnalyticsOnSendOrderBySms($order);
				static::addAnalyticsOnSendPaymentBySms($payment);
			}
		}
	}

	/**
	 * @param Main\Event $event
	 */
	public static function onSendPaymentByControlCenter(Main\Event $event)
	{
		static::onSendPayment('BITRIX24', $event);
	}

	/**
	 * @param Main\Event $event
	 */
	public static function onSendPaymentBySms(Main\Event $event): void
	{
		static::onSendPayment('SMS', $event);
	}

	protected static function addAnalyticsOnSendOrderBySms(Order\Order $order)
	{
		$constructor = new Analytics\LabelConstructor();

		// legacy analytics, to be removed later
		AddEventToStatFile('salescenter', 'orderSend', $order->getId(), $constructor->getContextLabel($order), 'context');
		AddEventToStatFile('salescenter', 'orderSend', $order->getId(), $constructor->getChannelLabel($order), 'channel');
		AddEventToStatFile('salescenter', 'orderSend', $order->getId(), $constructor->getChannelNameLabel($order), 'channel_name');
	}

	protected static function addAnalyticsOnSendPaymentBySms(Order\Payment $payment)
	{
		$constructor = new Analytics\LabelConstructor();

		$event = $constructor->getAnalyticsEventForPayment('payment_link_sent', $payment);
		$channelName = $constructor->getChannelNameLabel($payment->getOrder());
		$event->setP1('provider_' . $channelName);

		$event->send();
	}

	public function saveSmsTemplate($template, $mode = self::SMS_MODE_PAYMENT)
	{
		$smsOptions = $this->getSmsTemplateOptionsMap()[$mode];
		if (!$smsOptions)
   		{
   			$smsOptions = $this->getSmsTemplateOptionsMap()[self::SMS_MODE_PAYMENT];
   		}

		Main\Config\Option::set('salescenter', $smsOptions['option_name'], $template);
	}

	public function getSmsTemplate($mode = self::SMS_MODE_PAYMENT)
	{
		$smsOptions = $this->getSmsTemplateOptionsMap()[$mode];
		if (!$smsOptions)
		{
   			$smsOptions = $this->getSmsTemplateOptionsMap()[self::SMS_MODE_PAYMENT];
   		}

		return Main\Config\Option::get(
			'salescenter',
			$smsOptions['option_name'],
			$smsOptions['default_text']
		);
	}

	public function getDefaultWrappedSmsTemplate($mode = self::SMS_MODE_PAYMENT)
	{
		$smsOptions = $this->getSmsTemplateOptionsMap()[$mode];
		if (!$smsOptions)
		{
			$smsOptions = $this->getSmsTemplateOptionsMap()[self::SMS_MODE_PAYMENT];
		}

		return $smsOptions['default_text_wrapped'] ?? null;
	}

	public function getDefaultSmsTemplate($mode = self::SMS_MODE_PAYMENT)
	{
		$smsOptions = $this->getSmsTemplateOptionsMap()[$mode];
		if (!$smsOptions)
		{
			$smsOptions = $this->getSmsTemplateOptionsMap()[self::SMS_MODE_PAYMENT];
		}

		return $smsOptions['default_text'] ?? null;
	}

	/**
	 * @return array
	 */
	public function getAllSmsTemplates(): array
	{
		return [
			self::SMS_MODE_PAYMENT => $this->getSmsTemplate(self::SMS_MODE_PAYMENT),
			self::SMS_MODE_COMPILATION => $this->getSmsTemplate(self::SMS_MODE_COMPILATION),
		];
	}

	/**
	 * @return array
	 */
	private function getSmsTemplateOptionsMap(): array
	{
		return [
			self::SMS_MODE_PAYMENT => [
				'option_name' => 'salescenter_sms_template',
				'default_text' => Loc::getMessage('SALESCENTER_CRMMANAGER_SMS_TEMPLATE_3'),
				'default_text_wrapped' => Loc::getMessage('SALESCENTER_CRMMANAGER_SMS_TEMPLATE_WRAPPED'),
			],
			self::SMS_MODE_COMPILATION => [
				'option_name' => 'salescenter_compilation_sms_template',
				'default_text' => Loc::getMessage('SALESCENTER_CRMMANAGER_COMPILATION_SMS_TEMPLATE'),
			]
		];
	}

	/**
	 * @param Order\Order $order
	 * @param array $options
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\SystemException
	 */
	public function addTimelineEntryOnOrderSend(Order\Order $order, array $options): void
	{
		$binding = $order->getEntityBinding();
		if (!$binding)
		{
			return;
		}

		$orderId = $order->getId();

		$params = [
			'ORDER_FIELDS' => $order->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderName,
				'FIELDS' => [
					'ORDER_ID' => $orderId,
					'OWNER_ID' => $binding->getOwnerId(),
					'OWNER_TYPE_ID' => $binding->getOwnerTypeId(),
					'SENT' => 'Y',
					'DESTINATION' => $options['DESTINATION'] ?? '',
					'PAYMENT_ID' => $options['PAYMENT_ID'] ?? '',
					'SHIPMENT_ID' => $options['SHIPMENT_ID'] ?? '',
				]
			],
			'BINDINGS' => Order\BindingsMaker\TimelineBindingsMaker::makeByOrder($order)
		];

		Timeline\OrderController::getInstance()->onSend($orderId, $params);
	}

	/**
	 * @param Order\Payment $payment
	 * @param array $options
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\SystemException
	 */
	public static function addTimelineEntryOnPaymentSend(Order\Payment $payment, array $options): void
	{
		$order = $payment->getOrder();

		$binding = $order->getEntityBinding();
		if (!$binding)
		{
			return;
		}

		$params = [
			'SETTINGS' => [
				'FIELDS' => [
					'ORDER_ID' => $payment->getOrderId(),
					'OWNER_ID' => $binding->getOwnerId(),
					'OWNER_TYPE_ID' => $binding->getOwnerTypeId(),
					Timeline\OrderPaymentController::VIEWED_WAY_CUSTOMER_PAYMENT_PAY => 'N',
					'DESTINATION' => $options['DESTINATION'] ?? '',
					'PAYMENT_ID' => $options['PAYMENT_ID'] ?? '',
					'SHIPMENT_ID' => $options['SHIPMENT_ID'] ?? '',
				]
			],
			'BINDINGS' => Order\BindingsMaker\TimelineBindingsMaker::makeByPayment($payment),
			'FIELDS' => $payment->getFieldValues(),
		];

		Timeline\OrderPaymentController::getInstance()->onSend($payment->getId(), $params);
	}

	protected static function needAddTimelineEntryOnPaymentSendSms(Order\Payment $payment, string $destination): bool
	{
		$dbRes = Timeline\Entity\TimelineTable::getList([
			'order' => ['ID' => 'ASC'],
			'filter' => [
				'TYPE_ID' => Timeline\TimelineType::ORDER,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'ASSOCIATED_ENTITY_ID' => $payment->getId(),
			],
		]);

		while ($item = $dbRes->fetch())
		{
			if (
				isset($item['SETTINGS']['FIELDS']['DESTINATION'])
				&& $item['SETTINGS']['FIELDS']['DESTINATION'] === $destination
			)
			{
				return false;
			}
		}

		return true;
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
		$company = \CCrmCompany::GetByID(EntityLink::getDefaultMyCompanyId(), false);

		return (string)($company['TITLE'] ?? '');
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

	/**
	 * @return int[]
	 */
	public function getMyCompanyAddressList(): array
	{
		$requisite = EntityRequisite::getSingleInstance()->getList(
			[
				'select' => ['ID'],
				'filter' => [
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
					'=ENTITY_ID' => (int)EntityLink::getDefaultMyCompanyId()
				],
			]
		)->fetch();

		if (!$requisite)
		{
			return [];
		}

		$result = [];

		$addresses = AddressTable::getList(
			[
				'filter' => [
					'ENTITY_ID' => (int)$requisite['ID'],
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
					'>LOC_ADDR_ID' => 0,
				],
			]
		)->fetchAll();

		$sortingMap = [
			EntityAddressType::Primary => 10,
			EntityAddressType::Delivery => 20,
		];

		foreach ($addresses as $address)
		{
			$result[$address['TYPE_ID']] = [
				'VALUE' => (int)$address['LOC_ADDR_ID'],
				'SORT' => isset($sortingMap[$address['TYPE_ID']]) ? $sortingMap[$address['TYPE_ID']] : 100,
			];
		}

		uasort($result, function ($a, $b) {
			return $a['SORT'] < $b['SORT'] ? -1 : 1;
		});

		return array_column($result, 'VALUE');
	}

	/**
	 * @param int $contactId
	 * @return int[]
	 */
	public function getClientAddressList(int $contactId)
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$entityTypeId = CCrmOwnerType::Contact;
		$requisite = EntityRequisite::getSingleInstance()->getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $contactId,
			],
		])->fetch();

		if (!$requisite)
		{
			return [];
		}

		$result = [];

		$addresses = AddressTable::getList([
			'filter' => [
				'ENTITY_ID' => (int)$requisite['ID'],
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
				'>LOC_ADDR_ID' => 0,
			],
		])->fetchAll();

		$defaultAddressTypeByCategory =
			method_exists(EntityAddressType::class, 'getDefaultIdByEntityId')
			? EntityAddressType::getDefaultIdByEntityId($entityTypeId, $contactId)
			: EntityAddressType::Undefined
		;
		$defaultAddressTypeId =
			EntityAddressType::isDefined($defaultAddressTypeByCategory)
				? $defaultAddressTypeByCategory
				: EntityAddressType::getDefaultIdByZone(EntityAddress::getZoneId())
		;

		$sortingMap = [
			EntityAddressType::Delivery => 10,
			$defaultAddressTypeId => 20,
		];

		foreach ($addresses as $address)
		{
			$result[$address['TYPE_ID']] = [
				'VALUE' => (int)$address['LOC_ADDR_ID'],
				'SORT' => $sortingMap[$address['TYPE_ID']] ?? 100,
			];
		}

		uasort($result, function ($a, $b) {
			return $a['SORT'] < $b['SORT'] ? -1 : 1;
		});

		return array_column($result, 'VALUE');
	}

	private function getDealContactPhone(int $dealId)
	{
		$contact = $this->getPrimaryContact($dealId);
		if (!$contact)
		{
			return '';
		}

		return self::getContactPhoneFormat($contact['CONTACT_ID']);
	}

	public function getItemContactFields(Crm\Item $item)
	{
		$contactId = $item->getContactId();
		if (!$contactId)
		{
			return '';
		}

		return \CCrmContact::GetByID($contactId);
	}

	/**
	 * @param Crm\Item $item
	 * @return mixed|string
	 */
	public function getItemContactPhoneFormatted(Crm\Item $item)
	{

		$contactId = $item->getContactId();
		if (!$contactId)
		{
			return '';
		}

		return self::getContactPhoneFormat($contactId);
	}
	/**
	 * @param Crm\Item $item
	 * @return mixed|string
	 */
	public function getItemContactEditorUrl(Crm\Item $item)
	{
		$contactId = $item->getContactId();
		if (!$contactId)
		{
			return '';
		}
		else
		{
			return \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Contact, $contactId, false).'?init_mode=edit';
		}
	}

	/**
	 * Does not actually format the phone; the function's name is kept for backwards compatibility
	 * @see CrmManager::getFormattedContactPhone() if you need a formatted phone
	 */
	public static function getContactPhoneFormat(int $contactId): string
	{
		$phones = \CCrmFieldMulti::GetEntityFields(
			'CONTACT',
			$contactId,
			'PHONE',
			true,
			false
		);
		$phone = current($phones);
		if (!is_array($phone))
		{
			return '';
		}

		return isset($phone['VALUE']) ? (string)$phone['VALUE'] : '';
	}

	public static function getFormattedContactPhone(int $contactId): string
	{
		$phone = self::getContactPhoneFormat($contactId);

		return empty($phone) ? '' : Parser::getInstance()->parse($phone)->format();
	}

	/**
	 * @param int $dealId
	 * @return mixed|string
	 */
	public function getDealContactPhoneFormat(int $dealId)
	{
		$phone = $this->getDealContactPhone($dealId);
		if ($phone === '')
		{
			return '';
		}

		return Parser::getInstance()->parse($phone)->format();
	}

	public function getOrderLimitSliderId()
	{
		return OrderRestriction::LIMIT_SLIDER_ID;
	}

	/**
	 * @return mixed
	 */
	public function isOrderLimitReached()
	{
		return OrderRestriction::isOrderLimitReached();
	}

	/**
	 * @param int $dealId
	 * @return array|null
	 */
	private function getPrimaryContact(int $dealId): ?array
	{
		$contacts = DealContactTable::getDealBindings($dealId);
		foreach ($contacts as $contact)
		{
			if ($contact['IS_PRIMARY'] !== 'Y')
			{
				continue;
			}

			return $contact;
		}

		return null;
	}

	public function getStageList(int $entityId, int $entityTypeId) : array
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isStagesSupported())
		{
			return [];
		}

		$result = [];

		$collection = $factory->getStages($factory->getItemCategoryId($entityId));
		foreach ($collection as $item)
		{
			$result[$item->getStatusId()] = $item->collectValues();
		}

		return Crm\Color\PhaseColorScheme::fillDefaultColors($result);
	}

	public function isTerminalAvailable(): bool
	{
		return Crm\Terminal\AvailabilityManager::getInstance()->isAvailable();
	}

	public function isPaymentFromTerminal(\Bitrix\Sale\Payment $payment): bool
	{
		return (
			$this->isEnabled()
			&& Container::getInstance()->getTerminalPaymentService()->isTerminalPayment($payment->getId())
		);
	}

	/**
	 * @return array
	 */
	public function getUsableSmsSendersList(): array
	{
		$result = [];
		$restSender = null;

		$senderList = Crm\Integration\SmsManager::getSenderInfoList(true);
		foreach ($senderList as $sender)
		{
			if ($sender['canUse'])
			{
				if ($sender['id'] === 'rest')
				{
					$restSender = $sender;

					continue;
				}

				$result[] = $sender;
			}
		}

		if ($restSender !== null)
		{
			foreach ($restSender['fromList'] as $sender)
			{
				$result[] = $sender;
			}
		}

		return $result;
	}

	public static function getOrderIdByEntity(Item $entity): ?int
	{
		$relation = Container::getInstance()->getRelationManager()
			->getRelation(
				new RelationIdentifier(
					$entity->getEntityTypeId(),
					\CCrmOwnerType::Order
				)
			)
		;
		if (!$relation)
		{
			return null;
		}

		$result = null;

		$orderIdentifiers = $relation->getChildElements(
			new ItemIdentifier(
				$entity->getEntityTypeId(),
				$entity->getId()
			)
		);
		foreach ($orderIdentifiers as $orderIdentifier)
		{
			$result = $orderIdentifier->getEntityId();
		}

		return $result;
	}

	public function getTerminalPaymentRuntimeReferenceField(): ?Main\Entity\ReferenceField
	{
		return
			$this->isEnabled
				? Container::getInstance()->getTerminalPaymentService()->getRuntimeReferenceField()
				: null
		;
	}
}
