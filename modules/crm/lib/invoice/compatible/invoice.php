<?php
namespace Bitrix\Crm\Invoice\Compatible;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class Invoice extends Sale\Compatible\OrderCompatibility
{
	const ENTITY_ORDER_TABLE = 'b_crm_invoice';
	const ENTITY_PAYMENT_TABLE = 'b_crm_invoice_payment';

	/**
	 * @return string
	 */
	protected static function getBasketCompatibilityClassName()
	{
		return Basket::class;
	}


	/**
	 * @return string
	 */
	protected static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @return Main\Entity\Base
	 *
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected static function getEntity()
	{
		return Crm\Invoice\Internals\InvoiceTable::getEntity();
	}

	public function setFilter(array $filter = array())
	{
		if(isset($filter['SEARCH_CONTENT']) && $filter['SEARCH_CONTENT'] !== '')
		{
			$searchFilter = Crm\Search\SearchEnvironment::prepareEntityFilter(
				\CCrmOwnerType::Invoice,
				array(
					'SEARCH_CONTENT' => Crm\Search\SearchEnvironment::prepareSearchContent($filter['SEARCH_CONTENT'])
				)
			);
			unset($filter['SEARCH_CONTENT']);
			$filter = array_merge($filter, $searchFilter);
			unset($searchFilter);
		}

		parent::setFilter($filter);
	}

	/**
	 * @param int $index
	 *
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected function addPropertyRuntime($index)
	{
		if ($this->getPropertyRuntimeName($index))
			return;

		$this->query->registerRuntimeField(
			'PROPERTY_'.$index,
			array(
				'data_type' => '\Bitrix\Crm\Invoice\Internals\InvoicePropsValueTable',
				'reference' => array(
					'ref.ORDER_ID' => 'this.ID',
				),
				'join_type' => 'inner'
			)
		);

		$this->runtimeFields[] = 'PROPERTY_'.$index;
		$this->propertyRuntimeList[$index] = 'PROPERTY_'.$index;
	}

	protected function addBasketRuntime($key)
	{
		return null;
	}

	protected static function getDefaultFuserId()
	{
		return Sale\Fuser::getIdByUserId((int)\CSaleUser::GetAnonymousUserID());
	}
}
