<?php

namespace Bitrix\Crm\Integration\Sale\Reservation\Config\Entity;

use Bitrix\Crm\Integration\Sale\Reservation\Config\Storage;
use Bitrix\Crm\Integration\Sale\Reservation\Config\TypeDictionary;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Entity
 *
 * Base class for CRM entity reservation config
 * Use factory for instance construction
 * @see \Bitrix\Crm\Integration\Sale\Reservation\Config\EntityFactory::make()
 *
 * ```php
 *	$entity = \Bitrix\Crm\Integration\Sale\Reservation\Config\EntityFactory::make(
 * 		\Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Invoice::CODE
 *	);
 *	$values = $entity->getValues();
 *	$values['name'] = 'new value';
 *
 *	$entity->setValues([
 * 		'scheme item code' => 'value',
 *	]);
 *
 *	$entity->save();
 * ```
 * @package Bitrix\Crm\Integration\Sale\Reservation\Config\Entity
 */
abstract class Entity
{
	public const RESERVATION_MODE_CODE = 'mode';
	public const RESERVE_WITHDRAWAL_PERIOD_CODE = 'period';

	public const RESERVATION_MODE_OPTION_MANUAL = 'manual';
	public const RESERVATION_MODE_OPTION_ON_ADD_TO_DOCUMENT = 'onAddToDocument';
	public const RESERVATION_MODE_OPTION_ON_PAYMENT = 'onPayment';

	protected const DEFAULT_RESERVE_WITHDRAWAL_PERIOD_VALUE = 3;

	/** @var array */
	protected array $values = [];

	public function __construct()
	{
		$this->values = Storage::getEntityValues($this);
	}

	/**
	 * @return string
	 */
	public static function getCode(): string
	{
		return static::CODE;
	}

	/**
	 * @return array
	 */
	public static function getScheme(): array
	{
		return [
			[
				'code' => static::RESERVATION_MODE_CODE,
				'name' => Loc::getMessage('CRM_SALE_RESERVATION_CONFIG_MODE'),
				'type' => TypeDictionary::LIST,
				'default' => static::RESERVATION_MODE_OPTION_ON_ADD_TO_DOCUMENT,
				'sort' => 100,
				'values' => [
					[
						'code' => static::RESERVATION_MODE_OPTION_MANUAL,
						'name' => Loc::getMessage('CRM_SALE_RESERVATION_CONFIG_MODE_OPTION_MANUAL'),
					],
					[
						'code' => static::RESERVATION_MODE_OPTION_ON_ADD_TO_DOCUMENT,
						'name' => Loc::getMessage('CRM_SALE_RESERVATION_CONFIG_MODE_OPTION_ON_ADD_TO_DOCUMENT'),
					],
					[
						'code' => static::RESERVATION_MODE_OPTION_ON_PAYMENT,
						'name' => Loc::getMessage('CRM_SALE_RESERVATION_CONFIG_MODE_OPTION_ON_PAYMENT'),
					],
				],
				'disabled' => false,
			],
			[
				'code' => static::RESERVE_WITHDRAWAL_PERIOD_CODE,
				'name' => Loc::getMessage('CRM_SALE_RESERVATION_CONFIG_PERIOD'),
				'type' => TypeDictionary::INTEGER,
				'default' => static::DEFAULT_RESERVE_WITHDRAWAL_PERIOD_VALUE,
				'sort' => 200,
				'disabled' => false,
			]
		];
	}

	/**
	 * @return string|null
	 */
	public static function getName(): ?string
	{
		return Loc::getMessage(sprintf(
			'CRM_SALE_RESERVATION_ENTITY_%s',
			mb_strtoupper(static::getCode())
		));
	}

	/**
	 * @return array
	 */
	public function getValues(): array
	{
		return $this->values;
	}

	/**
	 * @param array $values
	 * @return Entity
	 */
	public function setValues(array $values): Entity
	{
		$this->values = $values;
		return $this;
	}

	public function save(): void
	{
		Storage::saveEntityValues($this);
	}

	/**
	 * @return mixed|null
	 */
	public function getReservationMode()
	{
		return $this->getValue(self::RESERVATION_MODE_CODE);
	}

	/**
	 * Days count.
	 *
	 * @return mixed|null
	 */
	public function getReserveWithdrawalPeriod()
	{
		return $this->getValue(self::RESERVE_WITHDRAWAL_PERIOD_CODE);
	}

	/**
	 * @param string $code
	 * @return mixed|null
	 */
	public function getValue(string $code)
	{
		return $this->values[$code] ?? null;
	}
}
