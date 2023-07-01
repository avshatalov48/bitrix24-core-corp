<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;
use Bitrix\Seo;

Loc::loadMessages(__FILE__);

/**
 * Class Ad
 *
 * @package Bitrix\Crm\Tracking\Analytics
 */
class Ad
{
	const CacheTtl = 900;

	protected $account;
	protected $authAdapter;
	protected $seoCode;
	protected $code;
	protected $clientId;
	protected $accountId;

	/**
	 * Return true if supported.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function isSupported($code)
	{
		return !empty(self::getSeoCodeByCode($code));
	}

	/**
	 * Update account ID compatible.
	 *
	 * @return string
	 * @internal
	 */
	public static function updateAccountIdCompatible()
	{
		$list = Main\Config\Option::get('crm', 'tracking_ad_acc', '');
		if ($list)
		{
			 try
			 {
				 $list = Main\Web\Json::decode($list);
				 $list = is_array($list) ? $list : [];
			 }
			 catch (\Exception $exception)
			 {
				 $list = [];
			 }
		}
		else
		{
			$list = [];
		}

		if (count($list) === 0)
		{
			return '';
		}


		if (!Main\Loader::includeModule('seo'))
		{
			return '';
		}

		$providers = \Bitrix\Seo\Analytics\Service::getProviders();
		foreach ($list as $code => $accountId)
		{
			$seoCode = self::getSeoCodeByCode($code);
			if (!$accountId || !$seoCode || !isset($providers[$seoCode]))
			{
				continue;
			}

			$provider = $providers[$seoCode];
			if (!$provider || !$provider['HAS_AUTH'] || !$provider['PROFILE'])
			{
				continue;
			}

			$clientId = $provider['PROFILE']['CLIENT_ID'];
			if (!$clientId)
			{
				continue;
			}

			$row = Tracking\Internals\SourceTable::getRow([
				'select' => ['ID'],
				'filter' => ['=CODE' => $code],
				'limit' => 1,
				'order' => ['ID' => 'ASC']
			]);

			Tracking\Internals\SourceTable::update($row['ID'], [
				'AD_ACCOUNT_ID' => $accountId,
				'AD_CLIENT_ID' => $clientId,
			]);
		}

		Main\Config\Option::delete('crm', ['name' => 'tracking_ad_acc']);

		return '';
	}

	/**
	 * Get SEO-code by code.
	 *
	 * @param string $code Code.
	 * @return string|null
	 */
	public static function getSeoCodeByCode($code)
	{
		if (!Main\Loader::includeModule('seo') || !$code)
		{
			return null;
		}

		$map = [
			Tracking\Source\Base::Ga => Seo\Analytics\Service::TYPE_GOOGLE,
			Tracking\Source\Base::Vk => Seo\Analytics\Service::TYPE_VKONTAKTE,
			Tracking\Source\Base::Vkads => Seo\Analytics\Service::TYPE_VKADS,
			Tracking\Source\Base::Ya => Seo\Analytics\Service::TYPE_YANDEX,
			Tracking\Source\Base::Fb => Seo\Analytics\Service::TYPE_FACEBOOK,
			Tracking\Source\Base::Ig => Seo\Analytics\Service::TYPE_INSTAGRAM,
		];

		return ($code && isset($map[$code])) ? $map[$code] : null;
	}

	/**
	 * Ad constructor.
	 *
	 * @param array $source Source.
	 */
	public function __construct(array $source)
	{
		$this->code = $source['CODE'];
		$this->clientId = $source['AD_CLIENT_ID'];
		$this->accountId = $source['AD_ACCOUNT_ID'];
		$this->seoCode = self::isSupported($this->code)
			? self::getSeoCodeByCode($this->code)
			: null;

		if ($this->seoCode)
		{
			$service = Seo\Analytics\Service::getInstance()->setClientId($this->clientId);
			$this->authAdapter = $service->getAuthAdapter($this->seoCode);
			$this->account = $service->getAccount($this->seoCode);
		}
	}

	/**
	 * Return true if it is connected.
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return (
			$this->account && $this->authAdapter && $this->authAdapter->hasAuth()
		);
	}

	/**
	 * Get auth url.
	 *
	 * @return null|string
	 */
	public function getAuthUrl()
	{
		if (!$this->authAdapter)
		{
			return null;
		}

		return $this->authAdapter->getAuthUrl();
	}

	/**
	 * Disconnect.
	 *
	 * @return $this
	 */
	public function disconnect()
	{
		if ($this->isConnected())
		{
			$this->authAdapter->removeAuth();
		}

		return $this;
	}

	/**
	 * Return true if it support pages.
	 *
	 * @return bool
	 */
	public function isSupportPages()
	{
		if ($this->isConnected())
		{
			return $this->account->hasPublicPages();
		}

		return false;
	}

	/**
	 * Get pages.
	 *
	 * @param string $accountId Account ID.
	 * @return array|Main\Result
	 */
	public function getPages($accountId)
	{
		if ($this->isConnected())
		{
			return $this->account->getPublicPages($accountId);
		}

		return [];
	}

	/**
	 * Get accounts.
	 *
	 * @return array|Seo\Retargeting\Response
	 */
	public function getAccounts()
	{
		if ($this->isConnected())
		{
			return $this->account->getList();
		}

		return [];
	}

	/**
	 * Get expenses.
	 *
	 * @param Main\Type\Date|null $dateFrom Date from.
	 * @param Main\Type\Date|null $dateTo Date to.
	 * @return array
	 */
	public function getExpenses(Main\Type\Date $dateFrom = null, Main\Type\Date $dateTo = null)
	{
		self::updateAccountIdCompatible();

		$defaultResult = [
			'impressions' => 0,
			'actions' => 0,
			'spend' => 0,
			'currency' => '',
		];

		if (!$this->isConnected())
		{
			return $defaultResult;
		}

		if ($this->account->hasAccounts())
		{
			if (!$this->accountId)
			{
				return $defaultResult;
			}
		}

		$cacheDir = '/crm/tracking/ad/expenses/';
		$cacheTtl = (int) Main\Config\Option::get('crm', 'crm_tracking_expenses_cache_ttl') ?: self::CacheTtl;
		$cacheId = $this->code
			. '|' . $this->clientId . '|' . $this->accountId
			. '|' . $dateFrom->getTimestamp() . '|' . $dateTo->getTimestamp();
		$cache = Main\Data\Cache::createInstance();
		if ($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			return $cache->getVars()['expenses'] + $defaultResult;
		}

		$expenses = $defaultResult;
		$result = $this->account->getExpenses($this->accountId, $dateFrom, $dateTo)->fetch();
		if (!empty($result['EXPENSES']))
		{
			$result = $result['EXPENSES'];
			/** @var $result Seo\Analytics\Internals\Expenses  */

			$currencyId = $result->getCurrency();
			if ($currencyId === 'BYN' && \CCrmCurrency::getAccountCurrencyID() === 'BYR')
			{
				$currencyId = 'BYR';
			}
			$expenses = [
				'impressions' => $result->getImpressions(),
				'actions' => $result->getActions(),
				'spend' => ($result->getSpend() && $result->getCurrency())
					? \CCrmCurrency::convertMoney(
						$result->getSpend(),
						$currencyId,
						\CCrmCurrency::GetAccountCurrencyID()
					)
					: $result->getSpend(),
				'currency' => \CCrmCurrency::GetAccountCurrencyID(),
			];
		}

		if (!empty($expenses['spend']))
		{
			$cache->startDataCache($cacheTtl, $cacheId, $cacheDir);
			$cache->endDataCache(['expenses' => $expenses]);
		}

		return $expenses;
	}

	/**
	 * Return true if it support expenses report.
	 *
	 * @return bool
	 */
	public function isSupportExpensesReport()
	{
		if ($this->isConnected())
		{
			return $this->account->hasExpensesReport();
		}

		return false;
	}

	/**
	 * Get expenses report.
	 *
	 * @param Main\Type\Date|null $dateFrom Date from.
	 * @param Main\Type\Date|null $dateTo Date to.
	 * @return Main\Result
	 */
	public function getExpensesReport(Main\Type\Date $dateFrom = null, Main\Type\Date $dateTo = null)
	{
		$checkResult = $this->checkDetalizationSupporting();
		if ($checkResult)
		{
			return $checkResult;
		}

		return $this->account->getExpensesReport($this->accountId, $dateFrom, $dateTo);
	}

	public function manageKeyword($groupId, $id, $active = true)
	{
		$checkResult = $this->checkDetalizationSupporting();
		if ($checkResult)
		{
			return $checkResult;
		}

		return $this->account->manageAdKeyword($this->accountId, $groupId, $id, $active);
	}

	public function manageGroup($id, $active = true)
	{
		$checkResult = $this->checkDetalizationSupporting();
		if ($checkResult)
		{
			return $checkResult;
		}

		return $this->account->manageAdGroup($this->accountId, $id, $active);
	}

	public function manageCampaign($id, $active = true)
	{
		$checkResult = $this->checkDetalizationSupporting();
		if ($checkResult)
		{
			return $checkResult;
		}

		return $this->account->manageAdCampaign($this->accountId, $id, $active);
	}

	protected function checkDetalizationSupporting()
	{
		if (!$this->isConnected())
		{
			return (new Main\Result())->addError(new Main\Error('Ads account not connected.'));
		}

		if (!$this->isSupportExpensesReport())
		{
			return (new Main\Result())->addError(new Main\Error('Detalization not supported.'));
		}

		if ($this->account->hasAccounts())
		{
			if (!$this->accountId)
			{
				return (new Main\Result())->addError(new Main\Error('Ads account not selected.'));
			}
		}

		return null;
	}
}
