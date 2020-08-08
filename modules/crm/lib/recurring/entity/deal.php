<?php
namespace Bitrix\Crm\Recurring\Entity;

use Bitrix\Main,
	Bitrix\Main\Result,
	Bitrix\Main\Type\Date,
	Bitrix\Crm\DealRecurTable,
	Bitrix\Crm\DealTable,
	Bitrix\Crm\Binding\DealContactTable,
	Bitrix\Crm\Observer\Entity\ObserverTable,
	Bitrix\Crm\Restriction\RestrictionManager;

class Deal extends Base
{
	/** @var Deal */
	protected static $instance = null;

	/**
	 * @return Deal
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new Deal();
		}
		return self::$instance;
	}

	public function getList(array $parameters = array())
	{
		return DealRecurTable::getList($parameters);
	}

	public function createEntity(array $dealFields, array $recurringParams)
	{
		$result = new Main\Result();
		try
		{
			$dealItem = Item\DealNew::create();
			$dealItem->initFields($recurringParams);
			$dealItem->setTemplateFields($dealFields);
			$result = $dealItem->save();
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	/**
	 * @param $primary
	 * @param array $data
	 *
	 * @return Result
	 * @throws Main\ArgumentException
	 */
	public function update($primary, array $data)
	{
		$entity = Item\DealExist::load($primary);
		if (!$entity)
		{
			$result = new Main\Result();
			$result->addError(new Main\Error('Recurring deal not found'));
		}

		$entity->setFields($data);
		return $entity->save();
	}

	/**
	 * @param array $filter
	 * @param null $limit
	 * @param bool $recalculate
	 *
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function expose(array $filter, $limit = null, $recalculate = true)
	{
		$result = new Main\Result();

		$idDealsList = array();
		$recurringDealMap = array();
		$newDealIds = array();

		$getParams = [
			'filter' => $filter,
			'select' => ['ID', 'DEAL_ID'],
			'runtime' => $this->getRuntimeTemplateField(),
		];
		if ((int)$limit > 0)
		{
			$getParams['limit'] = (int)$limit;
		}

		$recurring = DealRecurTable::getList($getParams);
		while ($recurData = $recurring->fetch())
		{
			$idDealsList[] = (int)$recurData['DEAL_ID'];
			$recurringDealMap[$recurData['DEAL_ID']] = $recurData['ID'];
		}

		if (empty($idDealsList))
		{
			return $result;
		}

		try
		{
			$idListChunks = array_chunk($idDealsList, 100);

			foreach ($idListChunks as $idList)
			{
				$products = $this->getProducts($idList);
				$dealContactIds = $this->getContactIds($idList);
				$dealObservers = $this->getObservers($idList);
				$dealsData = \CCrmDeal::GetListEx(
					array(),
					array(
						"=ID" => $idList,
						"CHECK_PERMISSIONS" => 'N'
					)
				);

				while ($deal = $dealsData->Fetch())
				{
					$recurringDealId = $deal['ID'];
					$recurringItem = Item\DealExist::load($recurringDealMap[$recurringDealId]);
					if (!$recurringItem)
						continue;
					$deal['PRODUCT_ROWS'] = $products[$recurringDealId];
					$deal['CONTACT_IDS'] = $dealContactIds[$recurringDealId];
					$deal['OBSERVER_IDS'] = $dealObservers[$recurringDealId];
					$recurringItem->setTemplateFields($deal);
					$r = $recurringItem->expose($recalculate);
					if ($r->isSuccess())
					{
						$exposingData = $r->getData();
						$newDealIds[] = $exposingData['NEW_DEAL_ID'];
					}
					else
					{
						$result->addErrors($r->getErrors());
						if ($recalculate)
						{
							$recurringItem->deactivate();
							$recurringItem->save();
						}
					}
					unset($recurringItem);
				}
			}

			unset($idListChunks, $idList);
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		if (!empty($newDealIds))
		{
			$result->setData(array("ID" => $newDealIds));
		}

		return $result;
	}

	/**
	 * @param array $idList
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getContactIds(array $idList)
	{
		$dealContactIds = [];
		$contactsRawData = DealContactTable::getList([
			'filter' => ['DEAL_ID' => $idList],
			'select' => ['DEAL_ID', 'CONTACT_ID']
		]);

		while ($contact = $contactsRawData->fetch())
		{
			$dealContactIds[$contact['DEAL_ID']][] = $contact['CONTACT_ID'];
		}
		return $dealContactIds;
	}

	/**
	 * @param array $idList
	 *
	 * @return array
	 */
	protected function getProducts(array $idList)
	{
		$products = [];
		$productRowData = \CCrmDeal::LoadProductRows($idList);

		foreach ($productRowData as $row)
		{
			$ownerId = $row['OWNER_ID'];
			unset($row['OWNER_ID'],$row['ID']);
			$products[$ownerId][] = $row;
		}

		return $products;
	}

	/**
	 * @param array $idList
	 *
	 * @return array
	 */
	protected function getObservers(array $idList)
	{
		$dealObservers = [];

		$observersRaw = ObserverTable::getList([
			'filter' => ['=ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, '=ENTITY_ID' => $idList],
			'select' => ['USER_ID', 'ENTITY_ID'],
			'order' => ['SORT' => 'ASC']
		]);

		while ($observer = $observersRaw->fetch())
		{
			$dealObservers[$observer['ENTITY_ID']][] = $observer['USER_ID'];
		}

		return $dealObservers;
	}

	/**
	 * @param array $fields
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 */
	public function add(array $fields)
	{
		$dealItem = Item\DealNew::create();
		$dealItem->initFields($fields);
		return $dealItem->save();
	}

	/**
	 * @param $dealId
	 * @param string $reason
	 *
	 * @throws Main\ArgumentException
	 */
	public function cancel($dealId, $reason = "")
	{
		self::deactivate($dealId);
	}

	/**
	 * @param $primary
	 *
	 * @return Result
	 * @throws Main\ArgumentException
	 */
	public function activate($primary)
	{
		return $this->update($primary, ['ACTIVE' => 'Y']);
	}

	/**
	 * @param $primary
	 *
	 * @return Result
	 * @throws Main\ArgumentException
	 */
	public function deactivate($primary)
	{
		return $this->update($primary, ['ACTIVE' => 'N']);
	}

	/**
	 * @param array $params
	 *
	 * @return ParameterMapper\DealMap
	 */
	public static function getParameterMapper(array $params = [])
	{
		return Item\DealEntity::getFormMapper($params);
	}

	/**
	 * Return calculated date by recurring params
	 *
	 * @param array $params
	 * @param null $startDate
	 *
	 * @return Date
	 */
	public static function getNextDate(array $params, $startDate = null)
	{
		$mapper = self::getParameterMapper($params);
		$mapper->fillMap($params);
		return parent::getNextDate($mapper->getPreparedMap(), $startDate);
	}


	/**
	 * @return bool
	 */
	public function isAllowedExpose()
	{
		$restriction = RestrictionManager::getDealRecurringRestriction();
		return $restriction->hasPermission();
	}

	/**
	 * @param mixed $primary
	 *
	 * @return Main\Result
	 * @throws \Exception
	 */
	public function delete($primary)
	{
		$entity = Item\DealExist::load($primary);
		if (!$entity)
		{
			$result = new Main\Result();
			$result->addError(new Main\Error('Recurring deal not found'));
		}

		return $entity->delete();
	}

	public function getRuntimeTemplateField() : array
	{
		return [
			new Main\Entity\ReferenceField(
				'DEAL_ENTITY',
				DealTable::class,
				['=this.DEAL_ID' => 'ref.ID'],
				['join_type' => 'INNER']
			)
		];
	}
}
