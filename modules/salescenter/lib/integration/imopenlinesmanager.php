<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Crm\Tracking\Channel\Imol;
use Bitrix\ImOpenLines\Im,
	Bitrix\ImOpenLines\SalesCenter as ImOlSalesCenter;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Model\Meta;
use Bitrix\SalesCenter\Model\Page;

class ImOpenLinesManager extends Base
{
	const META_PARAM = 'scm';

	protected $sessionId;
	protected $sessionInfo;

	/**
	 * @return bool
	 */
	protected function includeModule()
	{
		if(parent::includeModule())
		{
			return Loader::includeModule('im');
		}

		return false;
	}

	/**
	 * @param $sessionId
	 * @return $this
	 */
	public function setSessionId($sessionId)
	{
		if($this->isEnabled())
		{
			$this->sessionId = (int)$sessionId;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'imopenlines';
	}

	/**
	 * @return array|bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getCrmInfo()
	{
		if($this->isEnabled() && $this->sessionId > 0)
		{
			$activityId = $this->getActivityId();
			if($activityId)
			{
				$result = \Bitrix\ImOpenLines\Crm\Common::getActivityBindings($activityId);
				if($result->isSuccess())
				{
					return $result->getData();
				}
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getClientInfo()
	{
		$clientInfo = [];

		if($this->isEnabled() && $this->sessionId > 0)
		{
			$crmInfo = $this->getCrmInfo();
			if($crmInfo)
			{
				if($crmInfo['DEAL'] > 0)
				{
					$clientInfo['DEAL_ID'] = (int) $crmInfo['DEAL'];
				}

				if ((int)$crmInfo['COMPANY'] > 0)
				{
					$clientInfo['COMPANY_ID'] = (int) $crmInfo['COMPANY'];
				}

				if (!empty($crmInfo['CONTACT']))
				{
					if (is_array($crmInfo['CONTACT']))
					{
						$clientInfo['CONTACT_IDS'] = $crmInfo['CONTACT'];
					}
					else
					{
						$clientInfo['CONTACT_IDS'] = [(int)$crmInfo['CONTACT']];
					}
				}
			}
			$clientInfo['USER_ID'] = $this->getUserId();
		}

		return $clientInfo;
	}

	/**
	 * @return false|int
	 */
	public function getUserId()
	{
		if($this->isEnabled() && $this->sessionId > 0)
		{
			$sessionInfo = $this->getSessionInfo();
			if(is_array($sessionInfo) && $sessionInfo['USER_ID'] > 0)
			{
				return (int) $sessionInfo['USER_ID'];
			}
		}

		return false;
	}

	/**
	 * @return false|string
	 */
	public function getDialogId()
	{
		if($this->isEnabled() && $this->sessionId > 0)
		{
			$sessionInfo = $this->getSessionInfo();
			if(is_array($sessionInfo) && $sessionInfo['CHAT_ID'] > 0)
			{
				return 'chat'.$sessionInfo['CHAT_ID'];
			}
		}

		return false;
	}

	/**
	 * @param $userId
	 * @return array
	 */
	public function getDialogIdsByUserId($userId)
	{
		if (!$this->isEnabled())
		{
			return [];
		}

		$dialogs = [];
		$sessionInfoRaw = SessionTable::getList([
			'select' => ['CHAT_ID'],
			'filter' => [
				'=USER_ID' => $userId,
				'=CLOSED' => 'N'
			]
		]);

		while ($sessionInfo = $sessionInfoRaw->fetch())
		{
			$dialogs[] = 'chat'.$sessionInfo['CHAT_ID'];
		}

		return $dialogs;
	}

	/**
	 * @return array|false
	 */
	public function getSessionInfo()
	{
		if($this->isEnabled() && $this->sessionId > 0)
		{
			if($this->sessionInfo === null)
			{
				$this->sessionInfo = SessionTable::getList([
					'select' => ['ID', 'CRM', 'CRM_ACTIVITY_ID', 'USER_ID', 'CHAT_ID', 'SOURCE', 'CONFIG_ID'],
					'filter' => [
						'=ID' => $this->sessionId,
					]
				])->fetch();
			}

			return $this->sessionInfo;
		}

		return false;
	}

	/**
	 * @return int|false
	 */
	protected function getActivityId()
	{
		$sessionInfo = $this->getSessionInfo();
		if(is_array($sessionInfo) && $sessionInfo['CRM_ACTIVITY_ID'] > 0)
		{
			return $sessionInfo['CRM_ACTIVITY_ID'];
		}

		return false;
	}

	/**
	 * Sending a landing page or CRM form
	 *
	 * @param Page $page
	 * @param $dialogId
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sendPage(Page $page, $dialogId)
	{
		$result = new Result();
		if($this->isEnabled())
		{
			$fieldsMessage = [
				'DIALOG_ID' => $dialogId,
				'AUTHOR_ID' => Driver::getInstance()->getUserId(),
				'FROM_USER_ID' => Driver::getInstance()->getUserId(),
				'PARAMS' => $this->createImParamsByPage($page),
				'MESSAGE' => $this->createImMessageByPage($page),
			];

			if($page->isWebform())
			{
				$imOlMessage = new ImOlSalesCenter\Form(ImOlSalesCenter\Form::normalizeChatId($dialogId));
				$imOlMessage->setFormIds($this->getWebFormIdsByPage($page));
			}
			else
			{
				$imOlMessage = new ImOlSalesCenter\Other(ImOlSalesCenter\Other::normalizeChatId($dialogId));
			}

			$imOlMessage->setMessage($fieldsMessage);

			$resultSendMessage = $imOlMessage->send();

			if(!$resultSendMessage->isSuccess())
			{
				$result->addErrors($resultSendMessage->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param Page $page
	 * @return array
	 */
	protected function getWebFormIdsByPage(Page $page): array
	{
		$result = [];
		if ($page->isWebform())
		{
			$landingPageId = $page->getLandingId();
			if ($landingPageId > 0)
			{
				$forms = LandingManager::getInstance()->getConnectedWebForms();
				foreach ($forms as $form)
				{
					$landingWebForm = (int)$form['formId'];
					if ($landingPageId === (int)$form['landingId'])
					{
						if (!in_array($landingWebForm, $result, true))
						{
							$result[] = $landingWebForm;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param Order $order
	 * @return array|false
	 */
	public function getPublicUrlInfoForOrder(Order $order)
	{
		static $info = [];
		if(!isset($info[$order->getId()]))
		{
			$urlInfo = false;
			if(LandingManager::getInstance()->isOrderPublicUrlAvailable())
			{
				$urlParams = [
					'orderId' => $order->getId(),
					'access' => $order->getHash()
				];

				if ($this->isEnabled() && $this->sessionId > 0)
				{
					$urlParams['sessionIm'] = $this->sessionId;
				}

				$urlInfo = LandingManager::getInstance()->getOrderPublicUrlInfo($urlParams);

				if($urlInfo)
				{
					$orderPreviewData = $this->getOrderPreviewData($order);
					if(!empty($orderPreviewData))
					{
						$urlInfo['url'] = $this->addMetaData($urlInfo['url'], $order->getUserId(), $orderPreviewData);
					}
					$urlInfo['url'] = $this->preparePublicUrl($urlInfo['url']);
				}
			}
			$info[$order->getId()] = $urlInfo;
		}

		return $info[$order->getId()];
	}

	/**
	 * Sends system message to the manager about the order and its current payment status.
	 *
	 * @param Order $order
	 * @param $dialogId
	 * @param bool $isNew
	 * @return Result
	 */
	public function sendOrderNotify(Order $order, $dialogId, bool $isNew = true)
	{
		$result = new Result();
		if($this->isEnabled())
		{
			$urlInfo = $this->getPublicUrlInfoForOrder($order);
			if(!$urlInfo)
			{
				$result->addError(new Error('Page not found'));
				return $result;
			}

			$messageId = Im::addMessage([
				'DIALOG_ID' => $dialogId,
				'AUTHOR_ID' => Driver::getInstance()->getUserId(),
				'FROM_USER_ID' => Driver::getInstance()->getUserId(),
				'SYSTEM' => 'Y',
				'PARAMS' => $this->getCommonImParams(),
				'ATTACH' => $this->createImSystemAttachByOrder($order, $isNew),
				'SKIP_CONNECTOR' => 'Y',
			]);

			if(!$messageId)
			{
				global $APPLICATION;
				$result->addError(new Error($APPLICATION->LAST_ERROR));
			}
		}

		return $result;
	}

	/**
	 * Sends text messages to the client and the manager about the order with public url.
	 *
	 * @param Order $order
	 * @param $dialogId
	 * @param array $paymentData
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sendOrderMessage(Order $order, $dialogId, array $paymentData = []): Result
	{
		$result = new Result();

		if($this->isEnabled())
		{
			$urlInfo = $this->getPublicUrlInfoForOrder($order);
			if(!$urlInfo)
			{
				$result->addError(new Error('Page not found'));
				return $result;
			}
			$fieldsMessage = [
				'DIALOG_ID' => $dialogId,
				'AUTHOR_ID' => Driver::getInstance()->getUserId(),
				'FROM_USER_ID' => Driver::getInstance()->getUserId(),
				'PARAMS' => $this->createImParamsByOrder($order, $urlInfo['url']),
				'MESSAGE' => $this->createImMessageByOrder($order, $urlInfo['url'])
			];

			$imOlMessage = new ImOlSalesCenter\Payment(ImOlSalesCenter\Payment::normalizeChatId($dialogId));
			$imOlMessage->setMessage($fieldsMessage);
			if(!empty($paymentData))
			{
				$imOlMessage->setData($paymentData);
			}

			$resultSendMessage = $imOlMessage->send();

			if(!$resultSendMessage->isSuccess())
			{
				$result->addErrors($resultSendMessage->getErrors());
			}

			$notifyResult = $this->sendOrderCheckWarning($order, $dialogId);
			if(!$notifyResult->isSuccess())
			{
				$result->addErrors($notifyResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Sends system message about the order to the operator and payment status to the client and the manager.
	 *
	 * @param Order $order
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendOrderPayNotify(Order $order)
	{
		$result = new Result();
		$responsibleId = $order->getField('RESPONSIBLE_ID');
		$dialogs = $this->getDialogIdsByUserId($order->getUserId());

		$urlInfo = $this->getPublicUrlInfoForOrder($order);
		if(!$urlInfo)
		{
			$result->addError(new Error('Page not found'));
			return $result;
		}

		foreach ($dialogs as $dialogId)
		{
			$notifyResult = $this->sendOrderNotify($order, $dialogId, false);

			if(!$notifyResult->isSuccess())
			{
				$result->addErrors($notifyResult->getErrors());
			}

			$messageId = Im::addMessage([
				'DIALOG_ID' => $dialogId,
				'AUTHOR_ID' => $responsibleId,
				'FROM_USER_ID' => $responsibleId,
				'PARAMS' => $this->getCommonImParams(),
				'MESSAGE' => $this->createImMessageForPaymentStatus($order),
			]);

			if(!$messageId)
			{
				global $APPLICATION;
				$result->addError(new Error($APPLICATION->LAST_ERROR));
			}

			$messageId = Im::addMessage([
				'DIALOG_ID' => $dialogId,
				'AUTHOR_ID' => $responsibleId,
				'FROM_USER_ID' => $responsibleId,
				'PARAMS' => $this->getCommonImParams(),
				'MESSAGE' => $this->createImMessageForPaymentOrder($urlInfo['url']),
			]);

			if(!$messageId)
			{
				global $APPLICATION;
				$result->addError(new Error($APPLICATION->LAST_ERROR));
			}
		}

		return $result;
	}

	/**
	 * Send message with check public url after printing. In case such url is available.
	 *
	 * @param int $checkId
	 * @param Order $order
	 * @return Result
	 */
	public function sendOrderCheckNotify(int $checkId, Order $order): Result
	{
		$result = new Result();

		if($this->isEnabled())
		{
			$check = \Bitrix\Sale\Cashbox\CheckManager::getObjectById($checkId);
			if(!$check)
			{
				return $result->addError(new Error('Check #'.$checkId.' is not found'));
			}

			$cashbox = \Bitrix\Sale\Cashbox\Manager::getObjectById($check->getField('CASHBOX_ID'));
			if(!$cashbox)
			{
				return $result->addError(new Error('Cashbox #'.$check->getField('CASHBOX_ID').' is not found'));
			}
			$url = (string)$cashbox->getCheckLink($check->getField('LINK_PARAMS'));
			if(empty($url))
			{
				return $result->addError(new Error('No public url for check #'.$checkId));
			}

			$responsibleId = $order->getField('RESPONSIBLE_ID');
			$dialogs = $this->getDialogIdsByUserId($order->getUserId());

			foreach ($dialogs as $dialogId)
			{
				$notifyResult = $this->sendOrderNotify($order, $dialogId, false);

				if(!$notifyResult->isSuccess())
				{
					$result->addErrors($notifyResult->getErrors());
				}

				$messageId = Im::addMessage([
					'DIALOG_ID' => $dialogId,
					'AUTHOR_ID' => $responsibleId,
					'FROM_USER_ID' => $responsibleId,
					'PARAMS' => $this->getCommonImParams(),
					'MESSAGE' => $this->createImMessageForOrderCheck($order, $url),
				]);

				if(!$messageId)
				{
					global $APPLICATION;
					$result->addError(new Error($APPLICATION->LAST_ERROR));
				}
			}

			if ($dialogs)
			{
				$bindings = [
					[
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
						'ENTITY_ID' => $order->getId()
					]
				];

				if ($order->getDealBinding())
				{
					$bindings[] = [
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
						'ENTITY_ID' => $order->getDealBinding()->getDealId()
					];
				}

				Crm\Timeline\OrderCheckController::getInstance()->onSendCheckToIm(
					$checkId,
					[
						'ORDER_FIELDS' => $order->getFieldValues(),
						'SETTINGS' => ['SENDED' => 'Y'],
						'BINDINGS' => $bindings
					]
				);
			}
		}

		return $result;
	}

	/**
	 * Send system notify about check printing error.
	 *
	 * @param int $checkId
	 * @param Order $order
	 * @param string $message Error message.
	 * @return Result
	 */
	public function sendOrderCheckNotifyError(int $checkId, Order $order, string $message): Result
	{
		$result = new Result();

		if($this->isEnabled())
		{
			$dialogs = $this->getDialogIdsByUserId($order->getUserId());

			foreach ($dialogs as $dialogId)
			{
				$messageId = Im::addMessage([
					'DIALOG_ID' => $dialogId,
					'AUTHOR_ID' => Driver::getInstance()->getUserId(),
					'FROM_USER_ID' => Driver::getInstance()->getUserId(),
					'SYSTEM' => 'Y',
					'PARAMS' => $this->getCommonImParams(),
					'ATTACH' => $this->createImSystemAttachByCheck($checkId, $order, $message),
					'SKIP_CONNECTOR' => 'Y',
				]);

				if(!$messageId)
				{
					global $APPLICATION;
					$result->addError(new Error($APPLICATION->LAST_ERROR));
				}
			}
		}

		return $result;
	}

	protected function createImMessageForOrderCheck(Order $order, string $url): string
	{
		$message = Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_CHECK_TEXT_TOP', [
			'#SUM#' => html_entity_decode(SaleManager::getInstance()->getOrderFormattedPrice($order)),
			'#ORDER_DATE#' => SaleManager::getInstance()->getOrderFormattedInsertDate($order),
		]);
		$message .= '[BR]';
		$message .= Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_CHECK_TEXT_BOTTOM');
		$message .= '[BR]';
		$message .= $url;

		return $message;
	}

	protected function createImSystemAttachByCheck(int $checkId, Order $order, string $message): \CIMMessageParamAttach
	{
		$attach = new \CIMMessageParamAttach();
		$attach->AddLink([
			'NAME' => Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_ADD_LINK', [
				'#ORDER_ID#' => $order->getField('ACCOUNT_NUMBER'),
				'#ORDER_DATE#' => SaleManager::getInstance()->getOrderFormattedInsertDate($order),
			]),
			'LINK' => SaleManager::getInstance()->getOrderLink($order->getId()),
		]);
		$attach->AddMessage(Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_CHECK_NOTIFY_ERROR', [
			'#CHECK_ID#' => $checkId,
		]));
		$attach->AddMessage($message);

		return $attach;
	}

	/**
	 * @param Order $order
	 * @param $dialogId
	 * @return Result
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sendOrderCheckWarning(Order $order, $dialogId)
	{
		$result = new Result();

		if(
			Bitrix24Manager::getInstance()->isCurrentZone('ru')
		)
		{
			$warning = SaleManager::getInstance()->getOrderCheckWarning($order);
			if($warning)
			{
				$messageId = Im::addMessage([
					'DIALOG_ID' => $dialogId,
					'AUTHOR_ID' => Driver::getInstance()->getUserId(),
					'FROM_USER_ID' => Driver::getInstance()->getUserId(),
					'SYSTEM' => 'Y',
					'PARAMS' => $this->getCommonImParams(),
					'MESSAGE' => $warning,
					'SKIP_CONNECTOR' => 'Y',
					'URL_PREVIEW' => 'N',
				]);

				if(!$messageId)
				{
					global $APPLICATION;
					$result->addError(new Error($APPLICATION->LAST_ERROR));
				}
			}
		}

		return $result;
	}

	/**
	 * @param Order $order
	 * @return string
	 */
	protected function createImMessageForPaymentStatus(Order $order)
	{
		$message = Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_PAID_TEXT_TOP', [
			'#SUM#' => html_entity_decode(SaleManager::getInstance()->getOrderFormattedPrice($order)),
			'#DATE#' => SaleManager::getInstance()->getOrderFormattedInsertDate($order),
		]);

		return $message;
	}

	/**
	 * @param $publicUrl
	 * @return string
	 */
	protected function createImMessageForPaymentOrder($publicUrl)
	{
		$message = Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_PAID_TEXT_BOTTOM');
		$message .= '[BR]';
		$message .= $publicUrl;

		return $message;
	}

	/**
	 * @param Page $page
	 * @return string
	 */
	protected function createImMessageByPage(Page $page)
	{
		$urlPreviewData = $page->getUrlPreviewData();
		$message = $urlPreviewData['title'];
		if(!$message)
		{
			$message = $page->getName();
		}
		if($message)
		{
			$message .= '[BR]';
		}
		$url = $this->getPageUrlWithParameters($page);
		$message .= $this->preparePublicUrl($url, $page->isFromConnectedSite());

		return $message;
	}

	/**
	 * @param Order $order
	 * @param $publicUrl
	 * @return string
	 */
	protected function createImMessageByOrder(Order $order, $publicUrl)
	{
		$orderPreviewData = $this->getOrderPreviewData($order);
		$message = '[B]'.$orderPreviewData['title'].'[/B]';
		$message .= '[BR]';
		if($orderPreviewData['description'])
		{
			$message .= $orderPreviewData['description'];
			$message .= '[BR]';
		}
		$message .= $publicUrl;

		return $message;
	}

	/**
	 * @param Order $order
	 * @param bool $isNew
	 * @return \CIMMessageParamAttach
	 */
	protected function createImSystemAttachByOrder(Order $order, $isNew = true)
	{
		$attach = new \CIMMessageParamAttach();
		$attach->AddLink([
			'NAME' => Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_ADD_LINK', [
				'#ORDER_ID#' => $order->getField('ACCOUNT_NUMBER'),
				'#ORDER_DATE#' => SaleManager::getInstance()->getOrderFormattedInsertDate($order),
			]),
			'LINK' => SaleManager::getInstance()->getOrderLink($order->getId()),
		]);
		$attach->AddMessage($this->getOrderDescription($order, $isNew));

		return $attach;
	}

	/**
	 * @param Order $order
	 * @param bool $isNew
	 * @return string
	 */
	protected function getOrderDescription(Order $order, $isNew = true)
	{
		if($isNew)
		{
			$description = Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_ADD_TEXT', [
				'#SUM#' => html_entity_decode(SaleManager::getInstance()->getOrderFormattedPrice($order)),
			]);
		}
		else
		{
			$description = Loc::getMessage('SALESCENTER_IMOPMANAGER_SYSTEM_ORDER_TEXT', [
				'#SUM#' => html_entity_decode(SaleManager::getInstance()->getOrderFormattedPrice($order)),
			]);
		}

		$description .= '[BR]';
		$description .= SaleManager::getInstance()->getOrderPayStatus($order);

		return $description;
	}

	/**
	 * @param Page $page
	 * @return array
	 */
	protected function createImParamsByPage(Page $page)
	{
		return array_merge($this->getCommonImParams($this->preparePublicUrl($page->getUrl())), [
			'salescenterPageId' => $page->getId(),
			'richUrlPreview' => $page->getUrlPreviewData(),
		]);
	}

	/**
	 * @param Order $order
	 * @param $publicUrl
	 * @return array
	 */
	protected function createImParamsByOrder(Order $order, $publicUrl)
	{
		return array_merge($this->getCommonImParams($publicUrl), [
			'richUrlPreview' => $this->getOrderPreviewData($order),
		]);
	}

	/**
	 * @param Order $order
	 * @return array
	 */
	public function getOrderPreviewData(Order $order)
	{
		$description = '';
		$discountPrice = SaleManager::getInstance()->getOrderFormattedDiscountPrice($order);
		if($discountPrice)
		{
			$description .= Loc::getMessage('SALESCENTER_IMOPMANAGER_ORDER_ADD_MESSAGE_DISCOUNT', [
				'#DISCOUNT#' => html_entity_decode($discountPrice),
			]);
			$description .= PHP_EOL;
		}
		$description .= Loc::getMessage('SALESCENTER_IMOPMANAGER_ORDER_ADD_MESSAGE_BOTTOM');
		return [
			'title' => Loc::getMessage('SALESCENTER_IMOPMANAGER_ORDER_ADD_MESSAGE_TOP', [
				'#SUM#' => html_entity_decode(SaleManager::getInstance()->getOrderFormattedPrice($order)),
				'#DATE#' => SaleManager::getInstance()->getOrderFormattedInsertDate($order),
			]),
			'description' => $description,
		];
	}

	/**
	 * @param null $url
	 * @return array
	 */
	protected function getCommonImParams($url = null)
	{
		$params = [
			'fromSalescenterApplication' => true,
		];
		if($url)
		{
			$params['url'] = $url;
		}

		return $params;
	}

	/**
	 * @param $url
	 * @param bool $isUseShortUri
	 * @return mixed
	 */
	protected function preparePublicUrl($url, $isUseShortUri = true)
	{
		$url = $this->addUtm($url);
		if($this->isUseShortUri() && $isUseShortUri)
		{
			$url = UrlManager::getInstance()->getHostUrl().\CBXShortUri::GetShortUri($url);
		}
		return $url;
	}

	/**
	 * @param $url
	 * @param $userId
	 * @param array $data
	 * @return string
	 */
	protected function addMetaData($url, $userId, array $data)
	{
		$meta = Meta::getForData($userId, $data);
		if($meta)
		{
			$uri = new Uri($url);
			$uri->addParams([static::META_PARAM => $meta->getHash()]);
			$url = $uri->getLocator();
		}

		return $url;
	}

	/**
	 * @param $url
	 * @return mixed
	 */
	protected function addUtm($url)
	{
		$sessionInfo = $this->getSessionInfo();
		if(is_array($sessionInfo) && $sessionInfo['SOURCE'] && CrmManager::getInstance()->isEnabled())
		{
			$url = Imol::makeUriTrackable(new Uri($url), $sessionInfo['SOURCE'])->getLocator();
		}

		return $url;
	}

	/**
	 * @return bool
	 */
	protected function isUseShortUri()
	{
		return true;
	}

	/**
	 * @param array $activity
	 * @param $sessionId
	 * @return Result
	 */
	public function sendActivityNotify(array $activity, $sessionId)
	{
		$result = new Result();
		if($this->isEnabled)
		{
			$dialogId = $this->setSessionId($sessionId)->getDialogId();

			if(!$dialogId)
			{
				return $result->addError(new Error('Dialog not found'));
			}

			$messageId = Im::addMessage([
				'DIALOG_ID' => $dialogId,
				'AUTHOR_ID' => Driver::getInstance()->getUserId(),
				'FROM_USER_ID' => Driver::getInstance()->getUserId(),
				'SYSTEM' => 'Y',
				'PARAMS' => $this->getCommonImParams(),
				'ATTACH' => $this->createImSystemAttachByActivity($activity),
				'SKIP_CONNECTOR' => 'Y',
			]);

			if(!$messageId)
			{
				global $APPLICATION;
				$result->addError(new Error($APPLICATION->LAST_ERROR));
			}
		}

		return $result;
	}

	/**
	 * @param array $activity
	 * @return \CIMMessageParamAttach
	 */
	protected function createImSystemAttachByActivity(array $activity)
	{
		$attach = new \CIMMessageParamAttach();
		$attach->AddLink([
			'NAME' => $activity['SUBJECT'],
			'LINK' => CrmManager::getInstance()->getActivityViewUrl($activity['ID']),
		]);

		return $attach;
	}

	protected function getPageUrlWithParameters(Page $page): string
	{
		$manager = Driver::getInstance()->getFieldsManager();
		$crmInfo = $this->getCrmInfo();
		if($crmInfo)
		{
			$manager->setIds($crmInfo);
		}

		return $manager->getUrlWithParameters($page);
	}
}