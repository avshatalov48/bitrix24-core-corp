<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Main\Config\Option;
use Bitrix\Crm\Entity;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Binding\OrderContactCompanyTable;

/**
 * Class Order
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Order extends Base implements Features\EntityDetectable
{
	protected $code = self::Order;
	protected $isOrderEntitySearched = false;

	/**
	 * Return true if it is configured.
	 *
	 * @return bool
	 */
	public static function isConfigured()
	{
		return self::isConfiguredLocal() || self::isConfiguredRemote();
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
		if ($this->isOrderEntitySearched)
		{
			return $collection;
		}

		//$this->isOrderEntitySearched = true;
		if (!$this->getValue()/* || !static::isConfigured()*/)
		{
			return $collection;
		}

		$this->appendOrderEntities($collection);
		$this->appendDealEntities($collection);

		return $collection;
	}

	/**
	 * Get channels.
	 *
	 * @return Collection
	 */
	public function getChannels()
	{
		$collection = parent::getChannels();
		$orderId = $this->getEntities()->getIdByTypeId(\CCrmOwnerType::Order);
		if (!$orderId)
		{
			return $collection;
		}


		$collection->setChannel(new SalesCenter());

		return $collection;
	}

	/**
	 * Return true if it is configured.
	 *
	 * @return bool
	 */
	public static function isConfiguredLocal()
	{
		return true;
	}

	/**
	 * Return true if it is configured remote order receiving.
	 *
	 * @return bool
	 */
	public static function isConfiguredRemote()
	{
		return !empty(static::getDealField());
	}

	private function appendDealEntities(Entity\Identificator\ComplexCollection $collection)
	{
		$fieldCode = static::getDealField();
		if (!$fieldCode)
		{
			return;
		}

		$row = DealTable::getRow([
			'select' => ['ID', 'CONTACT_ID', 'COMPANY_ID'],
			'filter' => ["=$fieldCode" => $this->getValue()],
			'order' => ['ID' => 'DESC']
		]);
		if (!$row)
		{
			return;
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
	}

	private function appendOrderEntities(Entity\Identificator\ComplexCollection $collection)
	{
		$orderId = $this->getValue();
		$rows = OrderContactCompanyTable::getList([
			'select' => ['ORDER_ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'],
			'filter' => [
				"=ORDER_ID" => $orderId,
				'=IS_PRIMARY' => 'Y',
			],
		])->fetchAll();
		if (empty($rows))
		{
			return;
		}

		$collection->addIdentificator(\CCrmOwnerType::Order, $orderId, true);
		foreach ($rows as $row)
		{
			if (in_array($row['ENTITY_TYPE_ID'], [\CCrmOwnerType::Company, \CCrmOwnerType::Contact]))
			{
				$collection->addIdentificator($row['ENTITY_TYPE_ID'], $row['ENTITY_ID'], true);
			}
		}
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