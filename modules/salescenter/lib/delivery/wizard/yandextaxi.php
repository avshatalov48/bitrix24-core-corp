<?php

namespace Bitrix\SalesCenter\Delivery\Wizard;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Sale\Handlers\Delivery\Taxi\Yandex\Api\Api;
use Sale\Handlers\Delivery\Taxi\Yandex\Api\RequestEntity\SearchOptions;

Loc::loadMessages(__FILE__);

/**
 * Class YandexTaxi
 * @package Bitrix\SalesCenter\DeliveryServiceInstallator
 */
class YandexTaxi extends Base
{
	/** @var Api */
	private $api;

	/**
	 * YandexTaxi constructor.
	 * @param Api $api
	 */
	public function __construct(Api $api)
	{
		$this->api = $api;
	}

	/**
	 * @inheritdoc
	 */
	protected function buildFieldsFromSettings(array $settings): Result
	{
		$buildResult = parent::buildFieldsFromSettings($settings);
		if (!$buildResult->isSuccess())
		{
			return $buildResult;
		}

		$fields = $buildResult->getData()['FIELDS'];

		return $buildResult->setData(
			[
				'FIELDS' => array_merge(
					$fields,
					[
						'CODE' => $this->handler->getCode(),
						'CONFIG' => [
							'MAIN' => [
								'OAUTH_TOKEN' => $settings['OAUTH_TOKEN']
							]
						]
					]
				)
			]
		);
	}

	/**
	 * @inheritdoc
	 */
	protected function validateSettings(array $settings): Result
	{
		$validationResult = parent::validateSettings($settings);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		if (!isset($settings['OAUTH_TOKEN']) || empty($settings['OAUTH_TOKEN']))
		{
			return $validationResult->addError(
				new Error(
					Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_YANDEX_ERROR_MISSING_TOKEN')
				)
			);
		}
		else
		{
			$this->api->getTransport()->getOauthTokenProvider()->setToken($settings['OAUTH_TOKEN']);

			$searchResult = $this->api->searchActiveClaims(
				(new SearchOptions())->setLimit(1)
			);
			if (!$searchResult->isSuccess())
			{
				return $validationResult->addError(
					new Error(
						Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_YANDEX_ERROR_INVALID_TOKEN')
					)
				);
			}
		}

		return $validationResult;
	}
}
