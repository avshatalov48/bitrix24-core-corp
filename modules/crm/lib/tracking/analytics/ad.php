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

	/**
	 * Return true if supported.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function isSupported($code)
	{
		if (!Main\Loader::includeModule('seo') || !$code)
		{
			return false;
		}

		return !empty(self::getSeoCodeByCode($code));
	}

	/**
	 * Get account ID by code.
	 *
	 * @param string $code Code.
	 * @return string|null
	 */
	public static function getAccountIdByCode($code)
	{
		$list = self::getAccountIds();
		return isset($list[$code]) ? $list[$code] : null;
	}

	/**
	 * Get account IDs.
	 *
	 * @return array|mixed|string
	 */
	protected static function getAccountIds()
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

		return $list;
	}

	/**
	 * Set account ID by code.
	 *
	 * @param string $code Code.
	 * @param string $accountId Account ID.
	 * @return void
	 */
	public static function setAccountIdByCode($code, $accountId)
	{
		$list = self::getAccountIds();
		$list[$code] = $accountId;
		Main\Config\Option::set('crm', 'tracking_ad_acc', Main\Web\Json::encode($list));
	}

	/**
	 * Get SEO-code by code.
	 *
	 * @param string $code Code.
	 * @return string|null
	 */
	public static function getSeoCodeByCode($code)
	{
		$map = [
			Tracking\Source\Base::Ga => Seo\Analytics\Service::TYPE_GOOGLE,
			Tracking\Source\Base::Vk => Seo\Analytics\Service::TYPE_VKONTAKTE,
			Tracking\Source\Base::Ya => Seo\Analytics\Service::TYPE_YANDEX,
		];

		$isAdEnabled = Main\Config\Option::get('crm', '~tracking_ad_enabled', 'N') === 'Y';
		if ($isAdEnabled)
		{
			$map[Tracking\Source\Base::Fb] = Seo\Analytics\Service::TYPE_FACEBOOK;
			$map[Tracking\Source\Base::Ig] = Seo\Analytics\Service::TYPE_INSTAGRAM;
		}

		return ($code && isset($map[$code])) ? $map[$code] : null;
	}

	/**
	 * Ad constructor.
	 *
	 * @param string $code Code.
	 */
	public function __construct($code)
	{
		$this->code = $code;
		$this->seoCode = self::isSupported($code) ? self::getSeoCodeByCode($code) : null;
		if ($this->seoCode)
		{
			$this->authAdapter = Seo\Analytics\Service::getAuthAdapter($this->seoCode);
			$this->account = Seo\Analytics\Service::getAccount($this->seoCode);
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
	 * Get user profile.
	 *
	 * @return Seo\Retargeting\Response|null
	 */
	public function getUserProfile()
	{
		if (!$this->isConnected())
		{
			return null;
		}

		return $this->account->getProfileCached();
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

		$accountId = null;
		$account = Seo\Analytics\Service::getAccount($this->seoCode);
		if ($account->hasAccounts())
		{
			$accountId = self::getAccountIdByCode($this->code);
			if (!$accountId)
			{
				return $defaultResult;
			}
		}

		$cacheDir = '/crm/tracking/ad/expenses/';
		$cacheTtl = (int) Main\Config\Option::get('crm', 'crm_tracking_expenses_cache_ttl') ?: self::CacheTtl;
		$cacheId = $this->code . '|' . $dateFrom->getTimestamp() . '|' . $dateTo->getTimestamp();
		$cache = Main\Data\Cache::createInstance();
		if ($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			return $cache->getVars()['expenses'] + $defaultResult;
		}

		$expenses = $defaultResult;
		$result = $account->getExpenses($accountId, $dateFrom, $dateTo)->fetch();
		if (!empty($result['EXPENSES']))
		{
			$result = $result['EXPENSES'];
			/** @var $result Seo\Analytics\Internals\Expenses  */

			$expenses = [
				'impressions' => $result->getImpressions(),
				'actions' => $result->getActions(),
				'spend' => ($result->getSpend() && $result->getCurrency()) ?
					\CCrmCurrency::convertMoney(
						$result->getSpend(),
						$result->getCurrency(),
						\CCrmCurrency::GetAccountCurrencyID()
					)
					:
					$result->getSpend(),
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
}