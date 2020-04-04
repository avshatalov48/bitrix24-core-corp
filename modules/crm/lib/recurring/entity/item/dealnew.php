<?php
namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Main,
	Bitrix\Crm\DealRecurTable,
	Bitrix\Crm\Recurring\Manager,
	Bitrix\Crm\Timeline\DealRecurringController;

class DealNew extends DealEntity
{
	protected $basedId = null;

	/**
	 * @return array
	 */
	protected function getChangeableFields()
	{
		return [
			'DEAL_ID', 'BASED_ID', 'PARAMS', 'IS_LIMIT', 'LIMIT_REPEAT', 'LIMIT_DATE', 'CATEGORY_ID', 'START_DATE'
		];
	}

	public static function create()
	{
		return new self();
	}

	private function isInitializedFields()
	{
		return !empty($this->recurringFields);
	}

	public function initFields(array $fields = [])
	{
		if (!$this->isInitializedFields())
		{
			$this->setFieldsNoDemand($fields);
			if ((int)$fields['DEAL_ID'] > 0)
			{
				$this->templateId = (int)$fields['DEAL_ID'];
			}

			$this->onFieldChange('START_DATE');
		}
	}

	public function setTemplateField($name, $value)
	{
		if ($name === 'ID')
		{
			$value = (int)$value;
			if ($value > 0)
			{
				$this->basedId = $value;
				$this->setFieldNoDemand('BASED_ID', $value);
			}
		}
		else
		{
			parent::setTemplateField($name, $value);
		}
	}

	/**
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 */
	public function save()
	{
		$result = new Main\Result();
		if ((int)($this->templateId) <= 0 && empty($this->templateFields))
		{
			$result->addError(new Main\Error('Error saving. Template deal ID is empty.'));
			return $result;
		}

		if (!empty($this->templateFields))
		{
			$r = $this->saveDealTemplate();
			if (!$r->isSuccess())
			{
				return $r;
			}

			$this->templateId = $r->getId();
			$this->setFieldNoDemand('DEAL_ID', $this->templateId);
		}

		$addResult = $this->add();
		if ($addResult->isSuccess())
		{
			$this->id = $addResult->getId();
			$result->setData([
				'ID' => $this->id,
				'DEAL_ID' => $this->templateId,
			]);

			$this->onAfterSave();
		}
		else
		{
			$result->addErrors($addResult->getErrors());
		}

		return $result;
	}

	private function add()
	{
		return DealRecurTable::add($this->recurringFields);
	}

	/**
	 * @throws Main\ArgumentException
	 */
	private function onAfterSave()
	{
		$eventFields = $this->recurringFields;
		$eventFields['ID'] = $this->id;

		Manager::initCheckAgent(Manager::DEAL);

		DealRecurringController::getInstance()->onCreate(
			$this->templateId,
			array(
				'FIELDS' => $this->templateFields,
				'RECURRING' => $eventFields
			)
		);

		$event = new Main\Event("crm", static::ON_DEAL_RECURRING_ADD_EVENT, $eventFields);
		$event->send();

		$entityModifyFields = [
			'TYPE' => \CCrmOwnerType::DealRecurringName,
			'ID' => $this->id,
			'FIELDS' => $eventFields
		];
		$event = new Main\Event("crm", static::ON_CRM_ENTITY_RECURRING_MODIFY, $entityModifyFields);
		$event->send();
	}

	/**
	 * @return Main\ORM\Data\AddResult
	 */
	private function saveDealTemplate()
	{
		$result = new Main\ORM\Data\AddResult();
		$dealController = $this->getControllerInstance();
		$this->setTemplateField('IS_RECURRING', 'Y');
		$templateId = $dealController->Add($this->templateFields, false, ['DISABLE_TIMELINE_CREATION' => 'Y']);
		if (!$templateId)
		{
			$result->addError(new Main\Error($dealController->LAST_ERROR));
			return $result;
		}
		if (!empty($this->basedId))
		{
			$this->copyDealProductRows($templateId, $this->basedId);
		}

		$result->setId($templateId);
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
}
