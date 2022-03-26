<?php
namespace Bitrix\Crm\Restriction;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class Bitrix24SearchLimitRestriction extends Bitrix24QuantityRestriction
{
	private const CACHE_DIR = '/crm/entity_count/';
	private const CACHE_TTL = 60*60; // 1 hour

	public function __construct($name = '', $limit = 0)
	{
		$htmlInfo = null;
		$popupInfo = array(
			'ID' => 'crm_entity_search_limit',
			'TITLE' => Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_TITLE'),
			'CONTENT' => Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_LIMIT_CONTENT')
		);
		parent::__construct($name, $limit, $htmlInfo, $popupInfo);
	}

	public function isExceeded($entityTypeID)
	{
		$limit = $this->getQuantityLimit();
		if($limit <= 0)
		{
			return false;
		}

		$count = $this->getCount($entityTypeID);
		return ($count > $limit);
	}

	public function getCount($entityTypeID)
	{
		$cache = Main\Application::getInstance()->getCache();
		$cacheId = 'crm_search_restriction_count_' . $entityTypeID;
		if ($cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR))
		{
			return (int)$cache->getVars()['count'];
		}
		$cache->startDataCache();

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$count = \CCrmLead::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$count = \CCrmDeal::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$count = \CCrmCompany::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$count = \CCrmContact::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			$count = \CCrmQuote::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			$count = \CCrmInvoice::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			$count = \Bitrix\Crm\Order\Manager::countTotal();
		}
		else
		{
			$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeID);
			if ($factory)
			{
				$count = $factory->getItemsCount();
			}
			elseif (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeID))
			{
				return 0;
			}
			else
			{
				$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
				throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
			}
		}
		$cache->endDataCache(['count' => $count]);

		return $count;
	}

	/**
	 * @param array|null $params
	 * @return array|null
	 */
	public function prepareStubInfo(array $params = null)
	{
		if($params === null)
		{
			$params = array();
		}

		if(!isset($params['REPLACEMENTS']))
		{
			$params['REPLACEMENTS'] = array();
		}
		$params['REPLACEMENTS']['#LIMIT#'] = $this->getQuantityLimit();

		$entityTypeName = isset($params['ENTITY_TYPE_ID']) ? \CCrmOwnerType::ResolveName($params['ENTITY_TYPE_ID']) : '';
		if($entityTypeName !== '')
		{
			$params['TITLE'] = Loc::getMessage("CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_TITLE");
			/*
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_LEAD_CONTENT
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_DEAL_CONTENT
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_CONTACT_CONTENT
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_COMPANY_CONTENT
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_QUOTE_CONTENT
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_INVOICE_CONTENT
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_ORDER_CONTENT
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_DYNAMIC_CONTENT
			 */
			$helpdeskLink = '';
			if (\CCrmOwnerType::isPossibleDynamicTypeId((int)$params['ENTITY_TYPE_ID']))
			{
				$entityTypeName = 'DYNAMIC';
			}
			if (Main\Loader::includeModule('ui'))
			{
				$helpdeskUrl = \Bitrix\UI\Util::getArticleUrlByCode('9745327');
				$helpdeskLink = '<a href="'.$helpdeskUrl.'">' . Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_HELPDESK_LINK').'</a>';
			}
			$content = Loc::getMessage("CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_{$entityTypeName}_CONTENT");
			$content .= '<br><br>';
			$content .= Loc::getMessage("CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_CONTENT2", [
				'#HELPDESK_LINK#' => $helpdeskLink
			]);

			$params['CONTENT'] = $content;

			if (!$params['GLOBAL_SEARCH'])
			{
				$params['ANALYTICS_LABEL'] = 'CRM_' . $entityTypeName . '_FILTER_LIMITS';
			}
		}
		return $this->restrictionInfo->prepareStubInfo($params);
	}

	public function notifyLimitWarning(int $entityTypeId, int $warningCount, int $userId = null): void
	{
		if ($userId === null)
		{
			$userId = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if (!$userId)
		{
			return;
		}

		$this->setUserNotifiedCount($entityTypeId, $warningCount, $userId);

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		if (
			Main\Loader::includeModule("im") &&
			Main\Loader::includeModule("ui") &&
			$entityTypeName !== '')
		{
			$helpdeskUrl = \Bitrix\UI\Util::getArticleUrlByCode('9745327');

			$message =
				Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_'.$entityTypeName.'_WARNING_TEXT1', [
					'#COUNT#' => $warningCount,
					'#LIMIT#' => $this->getQuantityLimit(),
				]).
				"\n\n".
				Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_'.$entityTypeName.'_WARNING_TEXT2', [
					'#HELPDESK_LINK#' => '<a href="'.$helpdeskUrl.'">'.Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_HELPDESK_LINK').'</a>'
				]);

			$messageOut =
				Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_'.$entityTypeName.'_WARNING_TEXT1', [
					'#COUNT#' => $warningCount,
					'#LIMIT#' => $this->getQuantityLimit(),
				]).
				' '.
				Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_'.$entityTypeName.'_WARNING_TEXT2', [
					'#HELPDESK_LINK#' => '('.Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_HELPDESK_LINK').': '.$helpdeskUrl.')'
				]);

			$notificationFields = array(
				"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
				"TO_USER_ID" => $userId,
				"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
				"NOTIFY_MODULE" => "crm",
				"NOTIFY_EVENT" => "other",
				"NOTIFY_TAG" => "CRM|SEARCH_LIMIT_WARNING|".$entityTypeName,
				"NOTIFY_MESSAGE" => $message,
				"NOTIFY_MESSAGE_OUT" => $messageOut
			);
			\CIMNotify::Add($notificationFields);
		}
	}

	public function notifyIfLimitAlmostExceed(int $entityTypeId, int $userId = null)
	{
		$limitWarningValue = $this->getLimitWarningValue($entityTypeId, $userId);
		if ($limitWarningValue > 0)
		{
			$this->notifyLimitWarning($entityTypeId, $limitWarningValue, $userId);
		}
	}

	public function getLimitWarningValue(int $entityTypeId, int $userId = null): int
	{
		if ($userId === null)
		{
			$userId = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if (!$userId)
		{
			return 0;
		}

		$limit = $this->getQuantityLimit();
		if($limit <= 0)
		{
			return 0;
		}

		if (\Bitrix\Crm\Integration\Bitrix24Manager::hasPurchasedLicense())
		{
			return 0;
		}

		return $this->calculateLimitWarningValue(
			$this->getUserNotifiedCount($entityTypeId, $userId),
			$this->getCount($entityTypeId),
			$limit
		);
	}

	protected function calculateLimitWarningValue(int $notifiedCount, int $count, int $limit): int
	{
		if ($count > $limit)
		{
			return 0;
		}
		$thresholds = [50, 100];
		if ($notifiedCount < $count)
		{
			foreach ($thresholds as $threshold)
			{
				$notificationLimit = $limit - $threshold;
				if ($notificationLimit <= 0)
				{
					continue;
				}

				if ($count > $notificationLimit && $notifiedCount < $notificationLimit)
				{
					return $notificationLimit;
				}
			}
		}

		return 0;
	}

	protected function getUserNotifiedCount(int $entityTypeId, int $userId): int
	{
		return (int)\CUserOptions::GetOption('crm', 'crm_entity_search_limit_notification_'.$entityTypeId, 0, $userId);
	}

	protected function setUserNotifiedCount(int $entityTypeId, int $count, int $userId): void
	{
		\CUserOptions::SetOption('crm', 'crm_entity_search_limit_notification_'.$entityTypeId, $count, false, $userId);
	}
}