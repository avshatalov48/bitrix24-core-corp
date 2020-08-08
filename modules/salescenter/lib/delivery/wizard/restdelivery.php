<?php

namespace Bitrix\SalesCenter\Delivery\Wizard;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

/**
 * Class RestDelivery
 * @package Bitrix\SalesCenter\Delivery\Wizard
 */
class RestDelivery extends Base
{
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
						'CONFIG' => $settings['CONFIG'],
					]
				)
			]
		);
	}
}