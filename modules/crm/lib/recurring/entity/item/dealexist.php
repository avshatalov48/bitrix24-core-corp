<?php
namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Main,
	Bitrix\Main\Result,
	Bitrix\Main\Type\Date,
	Bitrix\Crm\DealRecurTable,
	Bitrix\Crm\Automation,
	Bitrix\Crm\Observer\ObserverManager,
	Bitrix\Crm\Binding\DealContactTable,
	Bitrix\Crm\Timeline\DealRecurringController,
	Bitrix\Crm\Recurring;

class DealExist extends DealEntity
{
	/** @var array  */
	private $previousRecurringFields = [];

	/**
	 * @return array
	 */
	protected function getChangeableFields()
	{
		return [
			'PARAMS', 'ACTIVE', 'IS_LIMIT', 'LIMIT_REPEAT', 'LIMIT_DATE', 'CATEGORY_ID', 'START_DATE'
		];
	}

	private function initFields(array $fields = [])
	{
		unset($fields['ID']);
		$this->recurringFields = $fields;
		$this->templateId = $fields['DEAL_ID'];
		$params = is_array($fields['PARAMS']) ? $fields['PARAMS'] : [];
		$this->calculateParameters = $this->formatCalculateParameters($params);
	}

	public static function load($id)
	{
		$id = (int)$id;
		if ($id <= 0)
			return null;

		$fieldsRaw = DealRecurTable::getById($id);
		if ($fields = $fieldsRaw->fetch())
		{
			$dealObject = new self($id);
			$dealObject->initFields($fields);
			return $dealObject;
		}

		return null;
	}

	public static function loadByDealId($dealId)
	{
		if ((int)($dealId) <= 0)
			return null;

		$fieldsRaw = DealRecurTable::getList([
			"filter" => array("=DEAL_ID" => $dealId),
			"limit" => 1
		]);
		if ($fields = $fieldsRaw->fetch())
		{
			$dealObject = new self($fields['ID']);
			$dealObject->initFields($fields);
			return $dealObject;
		}

		return null;
	}

	protected function setFieldNoDemand($name, $value)
	{
		if (!array_key_exists($name, $this->previousRecurringFields))
		{
			$this->previousRecurringFields[$name] = $this->recurringFields[$name];
		}

		parent::setFieldNoDemand($name, $value);
	}

	protected function onFieldChange($name)
	{
		parent::onFieldChange($name);

		if ($name === 'ACTIVE')
		{
			$nextExecution = $this->calculateNextExecutionDate($this->recurringFields['START_DATE']);
			$this->setFieldNoDemand('NEXT_EXECUTION', $nextExecution);
		}
	}

	public function getTemplateField($name)
	{
		return $this->templateFields[$name];
	}

	public function isChanged()
	{
		return !empty($this->previousRecurringFields);
	}

	protected function fillTemplateFields()
	{
		global $USER_FIELD_MANAGER;
		$result = new Main\Result();
		$fields = \CCrmDeal::GetByID($this->templateId, false);
		if (empty($fields))
		{
			$result->addError(new Main\Error('Template entity not found'));
			return $result;
		}

		$this->setTemplateFields($fields);
		$dealUserType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmDeal::GetUserFieldEntityID());
		$userFields = $dealUserType->GetEntityFields($this->templateId);
		foreach ($userFields as $key => $ufField)
		{
			$this->setTemplateField($key, $ufField['VALUE']);
		}

		$dealProducts = \CCrmDeal::LoadProductRows([$this->templateId]);
		$dealProducts = is_array($dealProducts) ? $dealProducts : [];
		$this->setTemplateField('PRODUCT_ROWS', $dealProducts);

		$dealContactIds = [];
		$contactsRawData = DealContactTable::getList([
			'filter' => ['=DEAL_ID' => $this->templateId],
			'select' => ['DEAL_ID', 'CONTACT_ID']
		]);

		while ($contact = $contactsRawData->fetch())
		{
			$dealContactIds[$contact['DEAL_ID']][] = $contact['CONTACT_ID'];
		}
		$this->setTemplateField('CONTACT_IDS', $dealContactIds);

		$observers = ObserverManager::getEntityObserverIDs(\CCrmOwnerType::Deal, $this->templateId);
		if (!empty($observers))
		{
			$this->setTemplateField('OBSERVER_IDS', $observers);
		}

		return $result;
	}

	/**
	 * @param bool $recalculate 	Is need to recalculate activity and next execution date after exposing.
	 *
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 */
	public function expose($recalculate = false)
	{
		$result = new Main\Result();
		if ($this->isChanged())
		{
			$result->addError(new Main\Error('Error exposing. Recurring deal was changed. Need to save changes before exposing.'));
			return $result;
		}

		if (empty($this->templateFields))
		{
			$r = $this->fillTemplateFields();
			if (!$r->isSuccess())
			{
				return $r;
			}
		}

		$fields = $this->prepareDealFieldsBeforeExpose($this->templateFields);
		$addResult = $this->addExposingDeal($fields);
		if ($addResult->isSuccess())
		{
			$newDealId = $addResult->getId();
			$result->setData(['NEW_DEAL_ID' => $newDealId]);

			$this->onAfterDealExpose($newDealId, $fields);

			$this->setFieldNoDemand('COUNTER_REPEAT', (int)$this->recurringFields['COUNTER_REPEAT'] + 1);
			$this->setFieldNoDemand('LAST_EXECUTION', new Date());

			if ($recalculate)
			{
				$this->setFieldNoDemand("NEXT_EXECUTION", $this->calculateNextExecutionDate());
				if (!$this->isActive())
				{
					$this->deactivate();
				}
				else
				{
					$this->setFieldNoDemand("ACTIVE", 'Y');
				}
			}

			$this->save();
		}
		else
		{
			$result->addErrors($addResult->getErrors());
		}

		return $result;
	}

	protected function prepareDealFieldsBeforeExpose($fields)
	{
		$fields['IS_RECURRING'] = 'N';
		$fields['IS_NEW'] = 'Y';
		if (!is_null($this->recurringFields['CATEGORY_ID']))
		{
			$fields['CATEGORY_ID'] = $this->recurringFields['CATEGORY_ID'];
		}
		$fields['PRODUCT_ROWS'] = is_array($fields['PRODUCT_ROWS']) ? $fields['PRODUCT_ROWS'] : [];
		$fields['STAGE_ID'] = \CCrmDeal::GetStartStageID($fields['CATEGORY_ID']);

		$beginDate = $this->calculateBeginDate();
		if ($beginDate <> '')
		{
			$fields['BEGINDATE'] = $beginDate;
		}

		$closeDate = $this->calculateCloseDate();
		if ($closeDate <> '')
		{
			$fields['CLOSEDATE'] = $closeDate;
		}

		$userFields = $this->prepareUserFields($fields['ID']);
		$fields = array_merge($fields, $userFields);
		unset($fields['ID'], $fields['DATE_CREATE']);
		return $fields;
	}

	protected function calculateBeginDate()
	{
		$stringDate = '';
		if ((int)$this->getCalculateParameter('BEGINDATE_TYPE') === Recurring\Entity\Deal::CALCULATED_FIELD_VALUE)
		{
			$beginDate = Recurring\Entity\Deal::getNextDate([
				'MODE' => Recurring\Manager::MULTIPLY_EXECUTION,
				'MULTIPLE_TYPE' => Recurring\Calculator::SALE_TYPE_CUSTOM_OFFSET,
				'MULTIPLE_CUSTOM_TYPE' => (int)$this->getCalculateParameter('OFFSET_BEGINDATE_TYPE'),
				'MULTIPLE_CUSTOM_INTERVAL_VALUE' => (int)$this->getCalculateParameter('OFFSET_BEGINDATE_VALUE'),
			]);
			if ($beginDate instanceof Date)
			{
				$stringDate = $beginDate->toString();
			}
		}

		return $stringDate;
	}

	protected function calculateCloseDate()
	{
		$stringDate = '';
		if ((int)$this->getCalculateParameter('CLOSEDATE_TYPE') === Recurring\Entity\Deal::CALCULATED_FIELD_VALUE)
		{
			$closeDate = Recurring\Entity\Deal::getNextDate([
				'MODE' => Recurring\Manager::MULTIPLY_EXECUTION,
				'MULTIPLE_TYPE' => Recurring\Calculator::SALE_TYPE_CUSTOM_OFFSET,
				'MULTIPLE_CUSTOM_TYPE' => (int)$this->getCalculateParameter('OFFSET_CLOSEDATE_TYPE'),
				'MULTIPLE_CUSTOM_INTERVAL_VALUE' => (int)$this->getCalculateParameter('OFFSET_CLOSEDATE_VALUE'),
			]);
			if ($closeDate instanceof Date)
			{
				$stringDate = $closeDate->toString();
			}
		}

		return $stringDate;
	}

	protected function prepareUserFields($dealId)
	{
		$userFieldValues = [];
		$userFields = $this->getUserFieldInstance()->GetEntityFields($dealId);
		foreach($userFields as $key => $field)
		{
			if ($field['USER_TYPE']['BASE_TYPE'] === 'file' && !empty($field['VALUE']))
			{
				if (is_array($field['VALUE']))
				{
					$userFieldValues[$key] = [];
					foreach ($field['VALUE'] as $value)
					{
						$fileData = \CFile::MakeFileArray($value);
						if (is_array($fileData))
						{
							$userFieldValues[$key][] = $fileData;
						}
					}
				}
				else
				{
					$fileData = \CFile::MakeFileArray($field['VALUE']);
					if (is_array($fileData))
					{
						$userFieldValues[$key] = $fileData;
					}
					else
					{
						$userFieldValues[$key] = $field['VALUE'];
					}
				}
			}
			else
			{
				$userFieldValues[$key] = $field['VALUE'];
			}
		}
		return $userFieldValues;
	}

	/**
	 * @param array $fields
	 *
	 * @return Main\ORM\Data\AddResult
	 */
	protected function addExposingDeal(array $fields = [])
	{
		$result = new Main\ORM\Data\AddResult();
		$dealController = $this->getControllerInstance();

		try
		{
			$newDealId = $dealController->Add($fields, false, [
				'DISABLE_TIMELINE_CREATION' => 'Y',
				'DISABLE_USER_FIELD_CHECK' => true
			]);

			if ($newDealId)
			{
				$result->setId($newDealId);
				if (!empty($this->templateFields['PRODUCT_ROWS']))
				{
					$dealController::SaveProductRows($newDealId, $this->templateFields['PRODUCT_ROWS'], true, true, false);
				}

				$productRowSettings = \CCrmProductRow::LoadSettings('D', $this->templateId);
				if (!empty($productRowSettings))
					\CCrmProductRow::SaveSettings('D', $newDealId, $productRowSettings);
			}
			else
			{
				$result->addError(new Main\Error($dealController->LAST_ERROR));
			}
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	protected function onAfterDealExpose($newId, array $newDealFields)
	{
		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Deal,
			$newId,
			\CCrmBizProcEventType::Create,
			$arErrors
		);

		$starter = new Automation\Starter(\CCrmOwnerType::Deal, $newId);
		$starter->runOnAdd();

		$event = new Main\Event("crm", static::ON_DEAL_RECURRING_EXPOSE_EVENT, [
			'ID' => $this->id,
			'RECURRING_ID' => $this->templateId,
			'DEAL_ID' => $newId,
		]);
		$event->send();

		$newDealFields['RECURRING_ID'] = $this->templateId;
		DealRecurringController::getInstance()->onExpose(
			$newId,
			array(
				'FIELDS' => $newDealFields
			)
		);
	}

	/**
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 */
	public function save()
	{
		$result = new Main\Result();
		if (!$this->isChanged())
		{
			return $result;
		}

		$changedFields = array_keys($this->previousRecurringFields);
		$updateFields = array_intersect_key($this->recurringFields, array_flip($changedFields));
		$r = $this->update($updateFields);
		if (!$r->isSuccess())
		{
			return $r;
		}

		$this->onAfterSave($updateFields);

		$this->previousRecurringFields = [];

		return $result;
	}

	protected function update(array $updateFields)
	{
		return DealRecurTable::update($this->id, $updateFields);
	}

	protected function onAfterSave(array $updateFields)
	{
		if(!empty($this->templateFields['MODIFY_BY_ID']))
		{
			$updateFields['MODIFY_BY_ID'] = $this->templateFields['MODIFY_BY_ID'];
		}

		DealRecurringController::getInstance()->onModify(
			$this->templateId,
			$this->prepareTimelineItem($updateFields, $this->previousRecurringFields)
		);

		$entityModifyFields = [
			'TYPE' => \CCrmOwnerType::DealRecurringName,
			'ID' => $this->id,
			'FIELDS' => $updateFields
		];
		$event = new Main\Event("crm", static::ON_CRM_ENTITY_RECURRING_MODIFY, $entityModifyFields);
		$event->send();

		$updateFields['ID'] = $this->id;
		$updateFields['DEAL_ID'] = $this->templateId;
		$event = new Main\Event("crm", static::ON_DEAL_RECURRING_UPDATE_EVENT, $updateFields);
		$event->send();
	}

	/**
	 * @return Main\Result
	 */
	public function delete()
	{
		$result = new Main\Result();
		try
		{
			$result = DealRecurTable::delete($this->id);
		}
		catch (\Exception $e)
		{
			$result->addError(new Main\Error($e->getMessage()));
		}

		if ($result->isSuccess())
		{
			$event = new Main\Event("crm", static::ON_DEAL_RECURRING_DELETE_EVENT, ['ID' => $this->id]);
			$event->send();
		}
		return $result;
	}

	public function deactivate(): void
	{
		$this->setFieldNoDemand('ACTIVE', 'N');
		$this->setFieldNoDemand('NEXT_EXECUTION', null);
	}
}
