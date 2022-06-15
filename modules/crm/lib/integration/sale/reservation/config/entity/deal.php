<?php

namespace Bitrix\Crm\Integration\Sale\Reservation\Config\Entity;

use Bitrix\Crm\Integration\Sale\Reservation\Config\TypeDictionary;
use Bitrix\Main\Localization\Loc;

/**
 * Class Deal
 *
 * @package Bitrix\Crm\Integration\Sale\Reservation\Config\Entity
 */
class Deal extends Entity
{
	public const CODE = 'deal';

	public const AUTO_WRITE_OFF_ON_FINALIZE_CODE = 'autoWriteOffOnFinalize';

	/**
	 * @inheritDoc
	 */
	public static function getScheme(): array
	{
		$result = parent::getScheme();

		$result[] = [
			'code' => static::AUTO_WRITE_OFF_ON_FINALIZE_CODE,
			'name' => Loc::getMessage('CRM_SALE_RESERVATION_CONFIG_DEAL_AUTO_WRITE_OFF_ON_FINALIZE_CODE'),
			'type' => TypeDictionary::OPTION,
			'default' => true,
			'sort' => 300,
			'disabled' => true,
			'description' => Loc::getMessage('CRM_SALE_RESERVATION_CONFIG_DEAL_AUTO_WRITE_OFF_ON_FINALIZE_DESCRIPTION'),
		];

		return $result;
	}

	/**
	 * @return mixed|null
	 */
	public function getAutoWriteOffOnFinalize()
	{
		return $this->getValue(static::AUTO_WRITE_OFF_ON_FINALIZE_CODE);
	}
}
