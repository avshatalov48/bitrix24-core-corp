<?php

namespace Bitrix\Sign\Controllers\V1;

use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Api\Client\DomainRequest;
use Bitrix\Sign\Main\Application;
use Bitrix\Sign\Restriction;
use Bitrix\Sign\Service\Container;

class Portal extends \Bitrix\Sign\Engine\Controller
{
	/**
	 * @return array
	 */
	public function hasRestrictionsAction(): array
	{
		return [
			'isAvailable' => Storage::instance()->isAvailable(),
			'smsAllowed' => Restriction::isSmsAllowed(),
			'newDocAllowed' => Restriction::isNewDocAllowed(),
			'availableOnTariff' => Restriction::isSignAvailable(),
		];
	}

	public function changeDomainAction(): ?array
	{
		$currentDomain = Application::getServer()->getHttpHost();
		$response = Container::instance()->getApiClientDomainService()->change(
			(new DomainRequest($currentDomain))
		);

		if (!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
			 return null;
		}

		Storage::instance()->setCurrentDomain($currentDomain);

		return [];
	}
}
