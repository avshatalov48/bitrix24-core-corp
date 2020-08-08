<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Bitrix\Sale\Delivery;

/**
 * Class HandlersRepository
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
class HandlersRepository
{
	/** @var HandlersCollection */
	static $collection;

	/**
	 * @return HandlersCollection
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getCollection(): HandlersCollection
	{
		if (!is_null(static::$collection))
		{
			return static::$collection;
		}

		static::$collection = new HandlersCollection();

		$handlers = Delivery\Services\Manager::getHandlersList();

		foreach ($handlers as $handlerClassName)
		{
			$handler = Factory::make($handlerClassName);
			if (is_null($handler) || !$handler->isAvailable())
			{
				continue;
			}

			static::$collection->add($handler);
		}

		$restHandlers = Delivery\Services\Manager::getRestHandlerList();
		foreach ($restHandlers as $restHandler)
		{
			$handler = Factory::makeRest($restHandler['CODE']);
			static::$collection->add($handler);
		}

		return static::$collection;
	}

	/**
	 * @param string $code
	 * @return HandlerContract|null
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getByCode(string $code)
	{
		$this->getCollection();

		/** @var HandlerContract $handler */
		foreach (static::$collection as $handler)
		{
			if ($handler->getCode() !== $code)
			{
				continue;
			}

			return $handler;
		}

		return null;
	}
}
