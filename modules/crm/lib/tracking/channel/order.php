<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\Entity;
use Bitrix\Main\Config\Option;

/**
 * Class Order
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Order extends Base implements Features\EntityDetectable
{
	protected $code = self::Order;

	/**
	 * Return true if it is configured.
	 *
	 * @return bool
	 */
	public static function isConfigured()
	{
		return !empty(static::getDealField());
	}

	/**
	 * Order constructor.
	 *
	 * @param string $orderId Order ID.
	 */
	public function __construct($orderId)
	{
		$this->value = $orderId;
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return '# ' . $this->getValue();
	}

	/**
	 * Get entities.
	 *
	 * @return Entity\Identificator\ComplexCollection
	 */
	public function getEntities()
	{
		$collection = parent::getEntities();
		$fieldCode = static::getDealField();
		if (!$this->getValue() || !$fieldCode || !static::isConfigured())
		{
			return $collection;
		}

		$row = DealTable::getRow([
			'select' => ['ID', 'CONTACT_ID', 'COMPANY_ID'],
			'filter' => [$fieldCode => $this->getValue()],
			'order' => ['ID' => 'DESC']
		]);
		if (!$row)
		{
			return $collection;
		}

		$collection->addIdentificator(\CCrmOwnerType::Deal, $row['ID'], true);
		if ($row['CONTACT_ID'])
		{
			$collection->addIdentificator(\CCrmOwnerType::Contact, $row['CONTACT_ID'], true);
		}
		if ($row['COMPANY_ID'])
		{
			$collection->addIdentificator(\CCrmOwnerType::Company, $row['COMPANY_ID'], true);
		}

		return $collection;
	}

	/**
	 * Set deal field.
	 *
	 * @param string $code Field code.
	 * @return void
	 */
	public static function setDealField($code)
	{
		Option::set('crm', '~tracking_order_field', $code);
	}

	/**
	 * Get deal field.
	 *
	 * @return string
	 */
	public static function getDealField()
	{
		return Option::get('crm', '~tracking_order_field') ?: null;
	}
}