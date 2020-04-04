<?php
namespace Bitrix\Crm\Recurring\Entity;

use Bitrix\Main,
	Bitrix\Main\Result,
	Bitrix\Main\Type\Date,
	Bitrix\Main\Localization\Loc,
	Bitrix\Crm\DealRecurTable,
	Bitrix\Crm\Automation,
	Bitrix\Crm\Binding\DealContactTable,
	Bitrix\Crm\Timeline\DealRecurringController,
	Bitrix\Crm\Recurring\Calculator,
	Bitrix\Crm\Recurring\Manager;

class Deal extends Base
{
	/** @var Deal */
	protected static $instance = null;

	/** @var \CCrmDeal */
	protected static $dealInstance = null;

	/** @var \CCrmUserType */
	protected static $ufInstance = null;

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

	/**
	 * @return \CCrmDeal
	 */
	protected function getDealInstance()
	{
		if(self::$dealInstance === null)
		{
			self::$dealInstance = new \CCrmDeal(false);
		}
		return self::$dealInstance;
	}

	/**
	 * @return \CCrmUserType
	 */
	protected function getUserFieldInstance()
	{
		if(self::$ufInstance === null)
		{
			global $USER_FIELD_MANAGER;
			self::$ufInstance = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmDeal::GetUserFieldEntityID());
		}
		return self::$ufInstance;
	}

	public function getList(array $parameters = array())
	{
		return DealRecurTable::getList($parameters);
	}

	public function createEntity(array $dealFields, array $recurringParams)
	{
		$result = new Main\Result();
		$newDeal = $this->getDealInstance();
		if ((int)$dealFields['ID'] > 0)
		{
			$recurringParams['BASED_ID'] = $dealFields['ID'];
		}
		$parentDealId = $dealFields['ID'];
		unset($dealFields['ID']);
		try
		{
			$dealFields['DATE_BILL'] = new Date();
			$dealFields['IS_RECURRING'] = 'Y';
			$reCalculate = false;
			$idRecurringDeal = $newDeal->Add($dealFields, $reCalculate, array('DISABLE_TIMELINE_CREATION' => 'Y'));
			if (!$idRecurringDeal)
			{
				$result->addError(new Main\Error($newDeal->LAST_ERROR));
				return $result;
			}

			if ((int)$parentDealId > 0)
			{
				$this->copyDealProductRows($idRecurringDeal, $parentDealId);
			}
			$recurringParams['DEAL_ID'] = $idRecurringDeal;
			$r = $this->add($recurringParams);

			if ($r->isSuccess())
			{
				$this->onAfterCreateEntity($idRecurringDeal, $dealFields, $recurringParams);
				$result->setData(
					array(
						"DEAL_ID" => $idRecurringDeal,
						"ID" => $r->getId()
					)
				);
			}
			else
			{
				$result->addErrors($r->getErrors());
			}
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	/**
	 * @param $dealId
	 * @param $parentDealId
	 *
	 * @return bool
	 */
	protected function copyDealProductRows($dealId, $parentDealId)
	{
		$result = true;
		$productRows = \CCrmDeal::LoadProductRows($parentDealId);
		if (is_array($productRows) && !empty($productRows))
		{
			foreach ($productRows as &$product)
			{
				unset($product['ID'], $product['OWNER_ID']);
			}
			$result = \CCrmDeal::SaveProductRows($dealId, $productRows, true, true, false);
		}
		return $result;
	}

	/**
	 * @param $dealId
	 * @param $dealFields
	 * @param $recurringFields
	 */
	protected function onAfterCreateEntity($dealId, array $dealFields, array $recurringFields)
	{
		Manager::initCheckAgent(Manager::DEAL);

		if ($dealId > 0)
		{
			DealRecurringController::getInstance()->onCreate(
				$dealId,
				array(
					'FIELDS' => $dealFields,
					'RECURRING' => $recurringFields
				)
			);
			$recurringFields['MODIFY_BY_ID'] = $dealFields['CREATED_BY_ID'];
			DealRecurringController::getInstance()->onModify(
				$dealId,
				$this->prepareTimelineModify($recurringFields)
			);
		}

		$event = new Main\Event("crm", "OnCrmRecurringEntityModify", $recurringFields);
		$event->send();
	}

	/**
	 * @param $primary
	 * @param array $data
	 *
	 * @return Main\Result
	 */
	public function update($primary, array $data)
	{
		$result = new Main\Result();

		$primary = (int)$primary;
		if ($primary <= 0)
		{
			$result->addError(new Main\Error("Wrong primary ID"));
			return $result;
		}

		$recur = DealRecurTable::getById($primary);
		$recurData = $recur->fetch();
		if (!$recurData)
		{
			$result->addError(new Main\Error("Entity isn't recurring"));
			return $result;
		}
		$previousData = [
			'ACTIVE' => $recurData['ACTIVE'],
			'NEXT_EXECUTION' => $recurData['NEXT_EXECUTION']
		];
		unset($recurData['ACTIVE'], $recurData['NEXT_EXECUTION']);

		$data = $this->prepareUpdateFields(
			array_merge($recurData, $data)
		);

		$resultUpdate = DealRecurTable::update($primary, $data);

		if ($resultUpdate->isSuccess())
		{
			$recurData = array_merge($recurData, $previousData);
			$this->onAfterUpdate($data, $recurData);
		}

		return $resultUpdate;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	protected function prepareUpdateFields(array $data)
	{
		$data['NEXT_EXECUTION'] = null;
		$recurringParams = $data['PARAMS'];

		if (is_array($recurringParams) && $data['ACTIVE'] !== 'N')
		{
			$today = new Date();
			$nextDate = null;

			if ($data['START_DATE'] instanceof Date)
			{
				$nextDate = self::getNextDate($recurringParams, clone($data['START_DATE']));
				if ($nextDate instanceof Date)
				{
					if (
						$nextDate->getTimestamp() > $data['START_DATE']->getTimestamp()
						&& $data['START_DATE']->getTimestamp() > $today->getTimestamp()
					)
					{
						$nextDate = $data['START_DATE'];
					}
				}
			}

			if (!($nextDate instanceof Date) || ($today->getTimestamp() > $nextDate->getTimestamp()))
			{
				$nextDate = self::getNextDate($recurringParams);
			}

			$data['NEXT_EXECUTION'] = $nextDate;
		}

		if (!isset($data['ACTIVE']))
		{
			$data = $this->prepareActivity($data);
		}

		return $data;
	}

	/**
	 * @param array $previousFields
	 * @param array $currentFields
	 */
	protected function onAfterUpdate(array $currentFields, array $previousFields)
	{
		$previousTimestamp = ($previousFields['NEXT_EXECUTION'] instanceof Date) ? $previousFields['NEXT_EXECUTION']->getTimestamp() : 0;
		$currentTimestamp = ($currentFields['NEXT_EXECUTION'] instanceof Date) ? $currentFields['NEXT_EXECUTION']->getTimestamp() : 0;
		if ($previousFields['ACTIVE'] !== $currentFields['ACTIVE'] || $previousTimestamp !== $currentTimestamp)
		{
			$currentFields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
			DealRecurringController::getInstance()->onModify(
				$previousFields['DEAL_ID'],
				$this->prepareTimelineModify($currentFields, $previousFields)
			);

			$event = new Main\Event("crm", "OnCrmRecurringEntityModify", $currentFields);
			$event->send();
		}
	}

	/**
	 * @param array $filter
	 * @param null $limit
	 * @param bool $recalculate
	 *
	 * @return Result
	 */
	public function expose(array $filter, $limit = null, $recalculate = true)
	{
		$result = new Main\Result();

		$idDealsList = array();
		$recurParamsList = array();
		$newDealIds = array();

		$getParams['filter'] = $filter;
		if ((int)$limit > 0)
		{
			$getParams['limit'] = (int)$limit;
		}

		$recurring = DealRecurTable::getList($getParams);

		while ($recurData = $recurring->fetch())
		{
			$recurData['DEAL_ID'] = (int)$recurData['DEAL_ID'];
			$idDealsList[] = $recurData['DEAL_ID'];
			$recurParamsList[$recurData['DEAL_ID']] = $recurData;
		}

		if (empty($idDealsList))
		{
			return $result;
		}

		try
		{
			$newDeal = $this->getDealInstance();
			$idListChunks = array_chunk($idDealsList, 999);

			foreach ($idListChunks as $idList)
			{
				$products = $this->getProducts($idList);
				$dealContactIds = $this->getContactIds($idList);
				$dealsData = \CCrmDeal::GetList(
					array(),
					array(
						"=ID" => $idList,
						"CHECK_PERMISSIONS" => 'N'
					)
				);

				while ($deal = $dealsData->Fetch())
				{
					$recurringDealId = $deal['ID'];
					$recurData = $recurParamsList[$recurringDealId];
					$exposeParams = [
						'PRODUCT_ROWS' => $products[$recurringDealId],
						'RECURRING_FIELDS' => $recurData,
						'CONTACT_IDS' => $dealContactIds[$recurringDealId]
					];
					$deal = $this->fillFieldsBeforeExpose($deal, $exposeParams);
					$reCalculateDeal = false;
					$resultId = $newDeal->Add($deal, $reCalculateDeal, array(
						'DISABLE_TIMELINE_CREATION' => 'Y',
						'DISABLE_USER_FIELD_CHECK' => true
					));

					if ($resultId)
					{
						if (!empty($products[$recurringDealId]))
						{
							$newDeal::SaveProductRows($resultId, $products[$recurringDealId], true, true, false);
						}

						$productRowSettings = \CCrmProductRow::LoadSettings('D', $recurringDealId);
						if (!empty($productRowSettings))
							\CCrmProductRow::SaveSettings('D', $resultId, $productRowSettings);

						$newDealIds[] = $resultId;

						$options['RECALCULATE_NEXT_EXECUTION'] = $recalculate;
						$this->onAfterDealExpose($resultId, $recurData, $options);
					}
					else
					{
						$result->addError(new Main\Error($newDeal->LAST_ERROR));
					}
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
	 * @param array $fields
	 * @param array $params
	 *
	 * @return array
	 */
	protected function fillFieldsBeforeExpose(array $fields, array $params)
	{
		$recurData = $params['RECURRING_FIELDS'];
		$fields['IS_RECURRING'] = 'N';
		$fields['IS_NEW'] = 'Y';
		if (isset($recurData['CATEGORY_ID']))
		{
			$fields['CATEGORY_ID'] = $recurData['CATEGORY_ID'];
		}
		$fields['PRODUCT_ROWS'] = is_array($params['PRODUCT_ROWS']) ? $params['PRODUCT_ROWS'] : [];
		$fields['STAGE_ID'] = \CCrmDeal::GetStartStageID($fields['CATEGORY_ID']);

		if (!empty($params['CONTACT_IDS']))
		{
			$fields['CONTACT_IDS'] = $params['CONTACT_IDS'];
		}

		$recurParam = $recurData['PARAMS'];
		if ((int)$recurParam['BEGINDATE_TYPE'] === self::CALCULATED_FIELD_VALUE)
		{
			$beginDate = self::getNextDate([
				'MODE' => Manager::MULTIPLY_EXECUTION,
				'MULTIPLE_TYPE' => Calculator::SALE_TYPE_CUSTOM_OFFSET,
				'MULTIPLE_CUSTOM_TYPE' => (int)$recurParam['OFFSET_BEGINDATE_TYPE'],
				'MULTIPLE_CUSTOM_INTERVAL_VALUE' => (int)$recurParam['OFFSET_BEGINDATE_VALUE'],
			]);
			if ($beginDate instanceof Date)
			{
				$fields['BEGINDATE'] = $beginDate->toString();
			}
		}

		if ((int)$recurParam['CLOSEDATE_TYPE'] === self::CALCULATED_FIELD_VALUE)
		{
			$closeDate = self::getNextDate([
				'MODE' => Manager::MULTIPLY_EXECUTION,
				'MULTIPLE_TYPE' => Calculator::SALE_TYPE_CUSTOM_OFFSET,
				'MULTIPLE_CUSTOM_TYPE' => (int)$recurParam['OFFSET_CLOSEDATE_TYPE'],
				'MULTIPLE_CUSTOM_INTERVAL_VALUE' => (int)$recurParam['OFFSET_CLOSEDATE_VALUE'],
			]);
			if ($closeDate instanceof Date)
			{
				$fields['CLOSEDATE'] = $closeDate->toString();
			}
		}

		$userFields = $this->getUserFieldInstance()->GetEntityFields($fields['ID']);
		foreach($userFields as $key => $field)
		{
			if ($field["USER_TYPE"]["BASE_TYPE"] == "file" && !empty($field['VALUE']))
			{
				if (is_array($field['VALUE']))
				{
					$fields[$key] = array();
					foreach ($field['VALUE'] as $value)
					{
						$fileData = \CFile::MakeFileArray($value);
						if (is_array($fileData))
						{
							$fields[$key][] = $fileData;
						}
					}
				}
				else
				{
					$fileData = \CFile::MakeFileArray($field['VALUE']);
					if (is_array($fileData))
					{
						$fields[$key] = $fileData;
					}
					else
					{
						$fields[$key] = $field['VALUE'];
					}
				}
			}
			else
			{
				$fields[$key] = $field['VALUE'];
			}
		}

		unset($fields['ID'], $fields['DATE_CREATE']);
		return $fields;
	}

	/**
	 * @param $newId
	 * @param array $recurringFields
	 * @param array $options
	 */
	protected function onAfterDealExpose($newId, array $recurringFields, array $options)
	{
		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Deal,
			$newId,
			\CCrmBizProcEventType::Create,
			$arErrors
		);

		Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $newId);

		$deal['RECURRING_ID'] = $recurringFields['DEAL_ID'];
		DealRecurringController::getInstance()->onExpose(
			$newId,
			array(
				'FIELDS' => $deal
			)
		);
		$previousRecurData = $recurringFields;

		$recurringFields["LAST_EXECUTION"] = new Date();
		$recurringFields["COUNTER_REPEAT"] = (int)$recurringFields['COUNTER_REPEAT'] + 1;
		$updateData = array(
			"LAST_EXECUTION" => $recurringFields["LAST_EXECUTION"],
			"COUNTER_REPEAT" => $recurringFields["COUNTER_REPEAT"]
		);

		if ($options['RECALCULATE_NEXT_EXECUTION'])
		{
			$recurringFields["NEXT_EXECUTION"] = self::getNextDate($recurringFields['PARAMS']);
			$updateData["NEXT_EXECUTION"] = $recurringFields["NEXT_EXECUTION"];
			$recurringFields = $this->prepareActivity($recurringFields);
			$updateData["ACTIVE"] = $recurringFields["ACTIVE"];
		}

		DealRecurTable::update($recurringFields['ID'], $updateData);

		$updateData['MODIFY_BY_ID'] = $deal['MODIFY_BY_ID'];
		DealRecurringController::getInstance()->onModify(
			$recurringFields['DEAL_ID'],
			$this->prepareTimelineModify($updateData, $previousRecurData)
		);

		$event = new Main\Event("crm", "OnCrmRecurringEntityModify", $updateData);
		$event->send();
	}

	/**
	 * @param array $fields
	 *
	 * @return Main\ORM\Data\AddResult
	 */
	public function add(array $fields)
	{
		$recurParams = $this->prepareDates($fields);
		$recurringParams = $this->prepareActivity($recurParams);
		return DealRecurTable::add($recurringParams);
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
	 * @param $dealId
	 *
	 * @return Main\Result
	 */
	public function activate($dealId)
	{
		$result = new Result();

		if ((int)$dealId > 0)
		{
			$dealId = (int)$dealId;
		}
		else
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_WRONG_ID')));
			return $result;
		}

		$dealData = DealRecurTable::getList(
			array(
				"filter" => array("DEAL_ID" => $dealId)
			)
		);
		if ($deal = $dealData->fetch())
		{
			$recurringParams = $deal['PARAMS'];
			$deal['NEXT_EXECUTION'] = self::getNextDate($recurringParams);
			$deal["COUNTER_REPEAT"] = (int)$deal["COUNTER_REPEAT"] + 1;
			$isActive = $this->isActive($deal);
			if ($isActive)
			{
				$result = DealRecurTable::update(
					$dealId,
					array(
						"ACTIVE" => 'Y',
						"NEXT_EXECUTION" => $deal['NEXT_EXECUTION'],
						"COUNTER_REPEAT" => $deal['COUNTER_REPEAT']
					)
				);
			}
			else
			{
				if ((int)$deal['COUNTER_REPEAT'] > (int)$deal['LIMIT_REPEAT'])
				{
					$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_ACTIVATE_LIMIT_REPEAT')));
				}
				else
				{
					$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_ACTIVATE_LIMIT_DATA')));
				}
			}
		}
		else
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_WRONG_ID')));
		}
		return $result;
	}

	/**
	 * @param $dealId
	 *
	 * @throws Main\ArgumentException
	 */
	public function deactivate($dealId)
	{
		$dealId = (int)$dealId;
		if ($dealId <= 0)
		{
			throw new Main\ArgumentException('Wrong deal id.');
		}

		$recurringData = DealRecurTable::getById($dealId);
		if ( $recurringData->fetch())
		{
			$inactiveFields = [
				"ACTIVE" => "N",
				"NEXT_EXECUTION" => null
			];
			DealRecurTable::update($dealId,	$inactiveFields);
		}
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	protected function prepareActivity($data)
	{
		$today = new Date();
		$todayTimestamp = $today->getTimestamp();

		if ($data['NEXT_EXECUTION'] instanceof Date
			&& $todayTimestamp > $data['NEXT_EXECUTION']->getTimestamp()
		)
		{
			$data['NEXT_EXECUTION'] = null;
			$data['ACTIVE'] = "N";
			return $data;
		}
		else if ($data['NEXT_EXECUTION'] instanceof Date
			&& $data['LAST_EXECUTION'] instanceof Date
			&& $data['LAST_EXECUTION'] >= $data['NEXT_EXECUTION']->getTimestamp()
			&& $todayTimestamp >= $data['LAST_EXECUTION']->getTimestamp()
		)
		{
			$data['NEXT_EXECUTION'] = null;
			$data['ACTIVE'] = "N";
			return $data;
		}

		return parent::prepareActivity($data);
	}


	/**
	 * @param $currentFields
	 * @param $previousFields
	 *
	 * @return array
	 */
	private function prepareTimelineModify(array $currentFields, array $previousFields = array())
	{
		$preparedCurrent = array();

		if (!empty($currentFields['MODIFY_BY_ID']))
			$preparedCurrent['MODIFY_BY_ID'] = $currentFields['MODIFY_BY_ID'];

		if (!empty($currentFields['CREATED_BY_ID']))
			$preparedCurrent['CREATED_BY_ID'] = $currentFields['CREATED_BY_ID'];

		if ($currentFields["ACTIVE"] == 'Y' && $currentFields["NEXT_EXECUTION"] instanceof Main\Type\Date)
		{
			$preparedCurrent['VALUE'] = $currentFields["NEXT_EXECUTION"]->toString();

			$controllerFields = array(
				'FIELD_NAME' => "NEXT_EXECUTION",
				'CURRENT_FIELDS' => $preparedCurrent
			);

			if ($previousFields['NEXT_EXECUTION'] instanceof Main\Type\Date)
				$controllerFields['PREVIOUS_FIELDS']["VALUE"] = $previousFields['NEXT_EXECUTION']->toString();
		}
		else
		{
			$preparedCurrent['VALUE'] = $currentFields["ACTIVE"];
			$controllerFields = array(
				'FIELD_NAME' => "ACTIVE",
				'CURRENT_FIELDS' => $preparedCurrent,
				'PREVIOUS_FIELDS' => array('VALUE' => $previousFields["ACTIVE"])
			);
		}

		return $controllerFields;
	}

	/**
	 * @param array $params
	 *
	 * @return ParameterMapper\Map
	 */
	public static function getParameterMapper(array $params = [])
	{
		if (!empty($params['PERIOD_DEAL']))
		{
			return ParameterMapper\FirstFormDeal::getInstance();
		}

		return ParameterMapper\SecondFormDeal::getInstance();
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
		if (Main\Loader::includeModule('bitrix24'))
			return !in_array(\CBitrix24::getLicenseType(), array('project', 'tf', 'retail')) || Main\Config\Option::get('crm', 'recurring_deal_enabled', 'N') === 'Y';

		return true;
	}
}
