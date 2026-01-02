<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View;

use Bitrix\Rest\RestException;
use Bitrix\Rest\Integration\View\Base;
use Bitrix\Booking\Rest\V1\Controller;

class ViewManager extends \Bitrix\Rest\Integration\ViewManager
{
	/**
	 * @throws RestException
	 */
	public function getView(\Bitrix\Main\Engine\Controller $controller): Base
	{
		$view = self::matchView($controller);
		if (!$view)
		{
			throw new RestException('Unknown object ' . $controller::class);
		}

		return $view;
	}

	private static function matchView(\Bitrix\Main\Engine\Controller $controller): ?Base
	{
		if ($controller instanceof Controller\Resource)
		{
			return new Entity\Resource();
		}

		if ($controller instanceof Controller\Resource\Slots)
		{
			return new Entity\Slot();
		}

		if ($controller instanceof Controller\ResourceType)
		{
			return new Entity\ResourceType();
		}

		if ($controller instanceof Controller\Booking)
		{
			return new Entity\Booking();
		}

		if ($controller instanceof Controller\WaitList)
		{
			return new Entity\WaitList();
		}

		if (
			$controller instanceof Controller\Booking\Client
			|| $controller instanceof Controller\WaitList\Client
		)
		{
			return new Entity\Client();
		}

		if (
			$controller instanceof Controller\Booking\ExternalData
			|| $controller instanceof Controller\WaitList\ExternalData
		)
		{
			return new Entity\ExternalData();
		}

		if ($controller instanceof Controller\ClientType)
		{
			return new Entity\ClientType();
		}

		return null;
	}
}
