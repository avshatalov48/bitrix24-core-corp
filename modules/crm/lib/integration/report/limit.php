<?php
namespace Bitrix\Crm\Integration\Report;
use Bitrix\Bitrix24\Feature;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Json;

class Limit
{
	const ONLY_DELETE_SOLUTION_COUNT = 100000;
	const ONLY_DELETE_SOLUTION_HELP_LINK = 'javascript:top.BX.Helper.show(\\\'redirect=detail&code=9673115\\\');';
	const DEAL_TYPE = 'deal';
	const LEAD_TYPE = 'lead';
	const CONTACT_TYPE = 'contact';
	const COMPANY_TYPE = 'company';

	protected static $boardLimits = [];

	public static function getLimitationParams($board)
	{
		if(isset(static::$boardLimits[$board]))
		{
			return static::$boardLimits[$board];
		}

		$result = [];
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return $result;
		}

		$actualLeadCount = self::getLeadCount();
		$maxLeadCount = self::getEntityLimit($board, static::LEAD_TYPE);
		if ($actualLeadCount > $maxLeadCount)
		{
			$result[self::LEAD_TYPE] = [
				'actualCount' => $actualLeadCount,
				'maxCount' => $maxLeadCount
			];
		}

		$actualDealCount = self::getDealCount();
		$maxDealCount = self::getEntityLimit($board, static::DEAL_TYPE);
		if ($actualDealCount > $maxDealCount)
		{
			$result[self::DEAL_TYPE] = [
				'actualCount' => $actualDealCount,
				'maxCount' => $maxDealCount
			];
		}

		$actualContactCount = self::getContactCount();
		$maxContactCount = self::getEntityLimit($board, static::CONTACT_TYPE);
		if ($actualContactCount > $maxContactCount)
		{
			$result[self::CONTACT_TYPE] = [
				'actualCount' => $actualContactCount,
				'maxCount' => $maxContactCount
			];
		}

		$actualCompanyCount = self::getCompanyCount();
		$maxCompanyCount = self::getEntityLimit($board, static::COMPANY_TYPE);
		if ($actualCompanyCount > $maxCompanyCount)
		{
			$result[self::COMPANY_TYPE] = [
				'actualCount' => $actualCompanyCount,
				'maxCount' => $maxCompanyCount
			];
		}

		static::$boardLimits[$board] = $result;
		return $result;
	}

	public static function isAnalyticsLimited($board)
	{
		return !empty(self::getLimitationParams($board));
	}

	public static function resetLimitCache()
	{
		Option::delete('crm', ['name'=> '~crm_lead_count']);
		Option::delete('crm', ['name'=> '~crm_deal_count']);
		Option::delete('crm', ['name'=> '~crm_contact_count']);
		Option::delete('crm', ['name'=> '~crm_company_count']);

		return true;
	}

	/**leads*/
	private static function getLeadCount()
	{
		$leadCachedCount = Option::get('crm', '~crm_lead_count', null);
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		if (is_null($leadCachedCount))
		{
			$leadCount = self::updateLeadCachedData();
			return $leadCount;
		}
		else
		{
			$leadCachedCount = Json::decode($leadCachedCount);

			if ($currentDateTimestamp - $leadCachedCount['date'] > 24*60*60)
			{
				$leadCount = self::updateLeadCachedData();
				return $leadCount;
			}
			else
			{
				return (int)$leadCachedCount['count'];
			}

		}
	}

	private static function updateLeadCachedData()
	{
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		$leadCount = self::getLeadCountByRequest();
		$params['count'] = $leadCount;
		$params['date'] = $currentDateTimestamp;
		Option::set('crm', '~crm_lead_count', Json::encode($params));

		return $leadCount;
	}

	private static function getLeadCountByRequest()
	{
		$query = LeadTable::query();
		$query->addSelect(Query::expr()->count('ID'), 'CNT');
		$result = $query->exec()->fetchRaw();

		return (int)$result['CNT'];
	}

	/**deals*/
	private static function getDealCount()
	{
		$dealCachedCount = Option::get('crm', '~crm_deal_count', null);
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		if (is_null($dealCachedCount))
		{
			$dealCount = self::updateDealCachedData();
			return $dealCount;
		}
		else
		{
			$dealCachedCount = Json::decode($dealCachedCount);

			if ($currentDateTimestamp - $dealCachedCount['date'] > 24*60*60)
			{
				$dealCount = self::updateDealCachedData();
				return $dealCount;
			}
			else
			{
				return (int)$dealCachedCount['count'];
			}

		}
	}

	private static function updateDealCachedData()
	{
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		$dealCount = self::getDealCountByRequest();
		$params['count'] = $dealCount;
		$params['date'] = $currentDateTimestamp;
		Option::set('crm', '~crm_deal_count', Json::encode($params));

		return $dealCount;
	}

	private static function getDealCountByRequest()
	{
		$query = DealTable::query();
		$query->addSelect(Query::expr()->count('ID'), 'CNT');
		$result = $query->exec()->fetchRaw();

		return (int)$result['CNT'];
	}

	/**contacts*/
	private static function getContactCount()
	{
		$contactCachedCount = Option::get('crm', '~crm_contact_count', null);
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		if (is_null($contactCachedCount))
		{
			$contactCount = self::updateContactCachedData();
			return $contactCount;
		}
		else
		{
			$contactCachedCount = Json::decode($contactCachedCount);

			if ($currentDateTimestamp - $contactCachedCount['date'] > 24*60*60)
			{
				$contactCount = self::updateContactCachedData();
				return $contactCount;
			}
			else
			{
				return (int)$contactCachedCount['count'];
			}

		}
	}

	private static function updateContactCachedData()
	{
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		$contactCount = self::getContactCountByRequest();
		$params['count'] = $contactCount;
		$params['date'] = $currentDateTimestamp;
		Option::set('crm', '~crm_contact_count', Json::encode($params));

		return $contactCount;
	}

	private static function getContactCountByRequest()
	{
		$query = ContactTable::query();
		$query->addSelect(Query::expr()->count('ID'), 'CNT');
		$result = $query->exec()->fetchRaw();
		return (int)$result['CNT'];
	}

	/**company*/
	private static function getCompanyCount()
	{
		$companyCachedCount = Option::get('crm', '~crm_company_count', null);
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		if (is_null($companyCachedCount))
		{
			$companyCount = self::updateCompanyCachedData();
			return $companyCount;
		}
		else
		{
			$companyCachedCount = Json::decode($companyCachedCount);

			if ($currentDateTimestamp - $companyCachedCount['date'] > 24*60*60)
			{
				$companyCount = self::updateCompanyCachedData();
				return $companyCount;
			}
			else
			{
				return (int)$companyCachedCount['count'];
			}

		}
	}

	private static function updateCompanyCachedData()
	{
		$currentDate = new Date();
		$currentDateTimestamp = $currentDate->getTimestamp();
		$companyCount = self::getCompanyCountByRequest();
		$params['count'] = $companyCount;
		$params['date'] = $currentDateTimestamp;
		Option::set('crm', '~crm_company_count', Json::encode($params));

		return $companyCount;
	}

	private static function getCompanyCountByRequest()
	{
		$query = CompanyTable::query();
		$query->addSelect(Query::expr()->count('ID'), 'CNT');
		$result = $query->exec()->fetchRaw();
		return (int)$result['CNT'];
	}

	public static function getEntityLimit($boardId, $entityType)
	{

		$entityType = mb_strtolower($entityType);

		$boardLimits = Feature::getVariable("crm_analytics_limits_for_boards");
		if(is_array($boardLimits) && isset($boardLimits[$boardId]))
		{
			return $boardLimits[$boardId][$entityType];
		}

		switch ($entityType)
		{
			case static::LEAD_TYPE:
				return Feature::getVariable('crm_analytics_lead_max_count');
			case static::DEAL_TYPE:
				return Feature::getVariable('crm_analytics_deal_max_count');
			case static::CONTACT_TYPE:
				return Feature::getVariable('crm_analytics_contact_max_count');
			case static::COMPANY_TYPE:
				return Feature::getVariable('crm_analytics_company_max_count');
		}

		return 0;
	}


	public static function getLimitText($board)
	{
		$limitMaskMsg = '';
		$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_MASK_TEXT').'</br>';

		foreach (self::getLimitationParams($board) as $entityType => $limit)
		{
			$limitMaskMsg .= '</br>';
			if ($entityType == Limit::DEAL_TYPE)
			{
				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_DEAL_ACTUAL_COUNT_MASK', [
						'#ACTUAL_COUNT#' => $limit['actualCount']
					]).'</br>';

				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_DEAL_MAX_COUNT_MASK', [
						'#MAX_COUNT#' => $limit['maxCount']
					]).'</br>';

				if ($limit['maxCount'] <= self::ONLY_DELETE_SOLUTION_COUNT)
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_DEAL_LIMIT_SOLUTION_MASK').'</br>';
				}
				else
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_DEAL_LIMIT_SOLUTION_MASK_DELETE', [
							'#MORE_INFO_LINK#' => self::ONLY_DELETE_SOLUTION_HELP_LINK
						]).'</br>';
				}
			}

			if ($entityType == Limit::LEAD_TYPE)
			{
				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_LEAD_ACTUAL_COUNT_MASK', [
						'#ACTUAL_COUNT#' => $limit['actualCount']
					]).'</br>';

				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_LEAD_MAX_COUNT_MASK', [
						'#MAX_COUNT#' => $limit['maxCount']
					]).'</br>';
				if ($limit['maxCount'] <= self::ONLY_DELETE_SOLUTION_COUNT)
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LEAD_LIMIT_SOLUTION_MASK').'</br>';
				}
				else
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LEAD_LIMIT_SOLUTION_MASK_DELETE', [
							'#MORE_INFO_LINK#' => self::ONLY_DELETE_SOLUTION_HELP_LINK
						]).'</br>';
				}
			}

			if ($entityType == Limit::COMPANY_TYPE)
			{
				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_COMPANY_ACTUAL_COUNT_MASK', [
						'#ACTUAL_COUNT#' => $limit['actualCount']
					]).'</br>';

				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_COMPANY_MAX_COUNT_MASK', [
						'#MAX_COUNT#' => $limit['maxCount']
					]).'</br>';
				if ($limit['maxCount'] <= self::ONLY_DELETE_SOLUTION_COUNT)
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_COMPANY_LIMIT_SOLUTION_MASK').'</br>';
				}
				else
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_COMPANY_LIMIT_SOLUTION_MASK_DELETE', [
							'#MORE_INFO_LINK#' => self::ONLY_DELETE_SOLUTION_HELP_LINK
						]).'</br>';
				}
			}

			if ($entityType == Limit::CONTACT_TYPE)
			{
				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_CONTACT_ACTUAL_COUNT_MASK', [
						'#ACTUAL_COUNT#' => $limit['actualCount']
					]).'</br>';

				$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_CONTACT_MAX_COUNT_MASK', [
						'#MAX_COUNT#' => $limit['maxCount']
					]).'</br>';

				if ($limit['maxCount'] <= self::ONLY_DELETE_SOLUTION_COUNT)
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_CONTACT_LIMIT_SOLUTION_MASK').'</br>';
				}
				else
				{
					$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_CONTACT_LIMIT_SOLUTION_MASK_DELETE', [
							'#MORE_INFO_LINK#' => self::ONLY_DELETE_SOLUTION_HELP_LINK
						]).'</br>';
				}
			}



		}
		$limitMaskMsg .= '</br>';

		$limitMaskMsg .= Loc::getMessage('CRM_ANALYTICS_LIMIT_MASK_TEXT_FOR_MORE_INFO');
		return $limitMaskMsg;
	}
}