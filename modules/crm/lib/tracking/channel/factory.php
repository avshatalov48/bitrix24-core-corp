<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Factory
{
	/**
	 * Create channel instance.
	 *
	 * @param string $code Code.
	 * @param string|null $value Value.
	 * @return Base
	 * @throws NotImplementedException
	 */
	public static function create($code, $value = null)
	{
		$class = self::getClass($code);
		if (!$class)
		{
			throw new NotImplementedException("Channel with code `$code` not implemented.");
		}

		return new $class($value);
	}

	/**
	 * Get Return true if channel is known by code.
	 *
	 * @param string $code Code.
	 * @return null|string
	 */
	public static function isKnown($code)
	{
		return !empty(self::getClass($code));
	}

	/**
	 * Get class.
	 *
	 * @param string $code Code.
	 * @return null|string
	 */
	public static function getClass($code)
	{
		$class = null;
		switch ($code)
		{
			case Base::Mail:
				$class = Mail::class;
				break;
			case Base::Call:
				$class = Call::class;
				break;
			case Base::Imol:
				$class = Imol::class;
				break;
			case Base::Site:
				$class = Site::class;
				break;
			case Base::Site24:
				$class = Site24::class;
				break;
			case Base::Shop24:
				$class = Shop24::class;
				break;
			case Base::CrmShop:
				$class = CrmShop::class;
				break;
			case Base::SiteDomain:
				$class = SiteDomain::class;
				break;
			case Base::Button:
				$class = Button::class;
				break;
			case Base::Form:
				$class = Form::class;
				break;
			case Base::Callback:
				$class = Callback::class;
				break;
			case Base::FbLeadAds:
				$class = FbLeadAds::class;
				break;
			case Base::VkLeadAds:
				$class = VkLeadAds::class;
				break;
			case Base::Rest:
				$class = Rest::class;
				break;
			case Base::Order:
				$class = Order::class;
				break;
			case Base::SalesCenter:
				$class = SalesCenter::class;
				break;
		}

		return $class;
	}

	/**
	 * Create site channel instance by host.
	 *
	 * @param string $host Host.
	 * @return Base|Site|Site24|SiteDomain
	 */
	public static function createSiteChannelByHost($host)
	{
		$host = strtolower($host);
		$siteId = Tracking\Internals\SiteTable::getSiteIdByHost($host);
		if ($siteId)
		{
			return new Site($siteId);
		}

		static $sites = null;
		if ($sites === null)
		{
			$sites = Tracking\Provider::getReadyB24Sites();
		}
		if (isset($sites[$host]))
		{
			$site = $sites[$host];
			$code = $site['CODE'] ?? '';
			$code = self::isKnown($code) ? $code : Base::Site24;
			return self::create($code, $site['ID']);
		}

		return new SiteDomain($host);
	}

	/**
	 * Create collection by trace ID.
	 *
	 * @param int $traceId Trace ID.
	 * @return Collection
	 */
	public static function createCollection($traceId)
	{
		$list = Tracking\Internals\TraceChannelTable::getList([
			'filter' => ['=TRACE_ID' => $traceId],
			'order' => ['ID' => 'ASC']
		]);

		$collection = new Collection();
		foreach ($list as $row)
		{
			$collection->addChannel($row['CODE'], $row['VALUE']);
		}

		return $collection;
	}

	/**
	 * Get list of codes.
	 *
	 * @return array
	 */
	public static function getCodes()
	{
		return [
			Base::Mail,
			Base::Call,
			Base::Imol,
			Base::Site,
			Base::Site24,
			Base::Shop24,
			Base::SiteDomain,
			Base::Button,
			Base::Form,
			Base::Callback,
			Base::FbLeadAds,
			Base::VkLeadAds,
			Base::Rest,
			Base::Order,
			Base::SalesCenter,
		];
	}

	/**
	 * Get list of names.
	 *
	 * @return array
	 */
	public static function getNames()
	{
		$list = [];
		foreach (self::getCodes() as $code)
		{
			$list[$code] = Base::getNameByCode($code);
		}

		return $list;
	}
}