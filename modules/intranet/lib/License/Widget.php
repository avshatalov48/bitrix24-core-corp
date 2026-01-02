<?php

namespace Bitrix\Intranet\License;

use Bitrix\Intranet\License\Widget\Content\DynamicCollection;
use Bitrix\Intranet\License\Widget\Content\StaticCollection;
use Bitrix\Intranet\License\Widget\Content;
use Bitrix\Main\ArgumentException;

class Widget
{
	/**
	 * @var array<class-string>
	 */
	private const CONTENT_LIST = [
		Content\License::class,
		Content\MainButton::class,
		Content\Baas::class,
		Content\Market::class,
		Content\PurchaseHistory::class,
		Content\Telephony::class,
		Content\Updates::class,
		Content\Partner::class,
	];
	/**
	 * @var array<class-string>
	 */
	private const DYNAMIC_CONTENT_LIST = [
		Content\Baas::class,
		Content\Telephony::class,
	];

	/**
	 * @throws ArgumentException
	 */
	public function getContentCollection(): StaticCollection
	{
		$collection = new StaticCollection();

		foreach (self::CONTENT_LIST as $content)
		{
			$collection->add(new $content);
		}

		return $collection;
	}

	/**
	 * @throws ArgumentException
	 */
	public function getDynamicContentCollection(): DynamicCollection
	{
		$collection = new DynamicCollection();

		foreach (self::DYNAMIC_CONTENT_LIST as $content)
		{
			$collection->add(new $content);
		}

		return $collection;
	}
}
