<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Order\Manager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\UI\Util;
use CCrmOwnerType;
use CIMNotify;
use CUserOptions;

Loc::loadMessages(__FILE__);

class Bitrix24SearchLimitRestriction extends Bitrix24QuantityRestriction
{
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

	public function isExceeded($entityTypeId): bool
	{
		$limit = $this->getQuantityLimit();
		if ($limit <= 0)
		{
			return false;
		}

		$count = $this->getCount($entityTypeId);

		return $count > $limit;
	}

	public function getCount($entityTypeId): int
	{
		$cacheId = 'crm_search_restriction_count_' . $entityTypeId;

		if ($this->cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR))
		{
			return (int)$this->cache->getVars()['count'];
		}

		$this->cache->startDataCache();

		if ($entityTypeId === CCrmOwnerType::Lead)
		{
			$count = \CCrmLead::GetTotalCount();
		}
		elseif ($entityTypeId === CCrmOwnerType::Deal)
		{
			$count = \CCrmDeal::GetTotalCount();
		}
		elseif ($entityTypeId === CCrmOwnerType::Company)
		{
			$count = \CCrmCompany::GetTotalCount();
		}
		elseif ($entityTypeId === CCrmOwnerType::Contact)
		{
			$count = \CCrmContact::GetTotalCount();
		}
		elseif ($entityTypeId === CCrmOwnerType::Quote)
		{
			$count = \CCrmQuote::GetTotalCount();
		}
		elseif ($entityTypeId === CCrmOwnerType::Invoice)
		{
			$count = \CCrmInvoice::GetTotalCount();
		}
		elseif ($entityTypeId === CCrmOwnerType::Order)
		{
			$count = Manager::countTotal();
		}
		else
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$count = $factory->getItemsCount();
			}
			elseif (CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
			{
				return 0;
			}
			else
			{
				$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);

				throw new NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
			}
		}

		$this->cache->endDataCache(['count' => $count]);

		return $count;
	}

	public function prepareStubInfo(array $params = null)
	{
		if ($params === null)
		{
			$params = [];
		}

		if (!isset($params['REPLACEMENTS']))
		{
			$params['REPLACEMENTS'] = [];
		}
		$params['REPLACEMENTS']['#LIMIT#'] = $this->getQuantityLimit();

		$entityTypeName = isset($params['ENTITY_TYPE_ID'])
			? CCrmOwnerType::ResolveName($params['ENTITY_TYPE_ID'])
			: '';

		if ($entityTypeName !== '')
		{
			$params['TITLE'] = Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_TITLE');
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
			if (CCrmOwnerType::isPossibleDynamicTypeId((int)$params['ENTITY_TYPE_ID']))
			{
				$entityTypeName = 'DYNAMIC';
			}

			if (Loader::includeModule('ui'))
			{
				$helpdeskUrl = Util::getArticleUrlByCode('9745327');
				$helpdeskLink = '<a href="'.$helpdeskUrl.'">' . Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_HELPDESK_LINK').'</a>';
			}
			$content = Loc::getMessage("CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_{$entityTypeName}_CONTENT");
			$content .= '<br><br>';
			$content .= Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_FILTER_CONTENT', [
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
			$userId = Container::getInstance()->getContext()->getUserId();
		}

		if (!$userId)
		{
			return;
		}

		$this->setUserNotifiedCount($entityTypeId, $warningCount, $userId);

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		if (
			Loader::includeModule('im')
			&& Loader::includeModule('ui')
			&& $entityTypeName !== ''
		)
		{
			$helpdeskUrl = Util::getArticleUrlByCode('9745327');

			$notifyMessageCallback = function (?string $languageId = null) use (
				$entityTypeName,
				$warningCount,
				$helpdeskUrl,
			)
			{
				$firstWarningText = Loc::getMessage(
					"CRM_B24_SEARCH_LIMIT_RESTRICTION_{$entityTypeName}_WARNING_TEXT1",
					[
						'#COUNT#' => $warningCount,
						'#LIMIT#' => $this->getQuantityLimit(),
					],
					$languageId,
				);

				$helpdeskTitle = Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_HELPDESK_LINK', null, $languageId);
				$secondWarningText = Loc::getMessage(
					"CRM_B24_SEARCH_LIMIT_RESTRICTION_{$entityTypeName}_WARNING_TEXT2",
					[ '#HELPDESK_LINK#' => '<a href="'.$helpdeskUrl.'">'. $helpdeskTitle .'</a>' ],
					$languageId,
				);

				return "{$firstWarningText}\n\n{$secondWarningText}";
			};

			$notifyMessageOutCallback = function (?string $languageId = null) use (
				$entityTypeName,
				$warningCount,
				$helpdeskUrl,
			){
				$firstWarningText = Loc::getMessage(
					"CRM_B24_SEARCH_LIMIT_RESTRICTION_{$entityTypeName}_WARNING_TEXT1",
					[
						'#COUNT#' => $warningCount,
						'#LIMIT#' => $this->getQuantityLimit(),
					],
					$languageId,
				);

				$helpdeskTitle = Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_HELPDESK_LINK', null, $languageId);
				$secondWarningText = Loc::getMessage(
					"CRM_B24_SEARCH_LIMIT_RESTRICTION_{$entityTypeName}_WARNING_TEXT2",
					[
						'#HELPDESK_LINK#' => "({$helpdeskTitle}: {$helpdeskUrl})",
					],
					$languageId,
				);

				return "{$firstWarningText} {$secondWarningText}";
			};

			CIMNotify::Add([
				'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
				'TO_USER_ID' => $userId,
				'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
				'NOTIFY_MODULE' => 'crm',
				'NOTIFY_EVENT' => 'other',
				'NOTIFY_TAG' => 'CRM|SEARCH_LIMIT_WARNING|' . $entityTypeName,
				'NOTIFY_MESSAGE' => $notifyMessageCallback,
				'NOTIFY_MESSAGE_OUT' => $notifyMessageOutCallback,
			]);
		}
	}

	public function notifyIfLimitAlmostExceed(int $entityTypeId, int $userId = null): void
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
			$userId = Container::getInstance()->getContext()->getUserId();
		}

		if (!$userId)
		{
			return 0;
		}

		$limit = $this->getQuantityLimit();
		if ($limit <= 0)
		{
			return 0;
		}

		if (Bitrix24Manager::hasPurchasedLicense())
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
		return (int)CUserOptions::GetOption(
			'crm',
			'crm_entity_search_limit_notification_' . $entityTypeId,
			0,
			$userId
		);
	}

	protected function setUserNotifiedCount(int $entityTypeId, int $count, int $userId): void
	{
		CUserOptions::SetOption(
			'crm',
			'crm_entity_search_limit_notification_' . $entityTypeId,
			$count,
			false,
			$userId
		);
	}
}
