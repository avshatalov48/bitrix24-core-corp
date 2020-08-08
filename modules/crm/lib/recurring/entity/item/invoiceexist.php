<?php
namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Main,
	Bitrix\Main\Result,
	Bitrix\Main\Type\Date,
	Bitrix\Crm\Recurring\DateType,
	Bitrix\Crm\Recurring\Entity,
	Bitrix\Crm\Requisite\EntityLink,
	Bitrix\Crm\Recurring\Calculator,
	Bitrix\Crm\Invoice\Internals\InvoiceChangeTable,
	Bitrix\Crm\InvoiceRecurTable;

class InvoiceExist extends InvoiceEntity
{
	/** @var array  */
	private $previousRecurringFields = [];

	/**
	 * @return array
	 */
	protected function getChangeableFields()
	{
		return [
			'PARAMS', 'ACTIVE', 'IS_LIMIT', 'LIMIT_REPEAT', 'LIMIT_DATE', 'SEND_BILL', 'EMAIL_ID', 'START_DATE'
		];
	}

	private function initFields(array $fields = [])
	{
		unset($fields['ID']);
		$this->recurringFields = $fields;
		$this->templateId = $fields['INVOICE_ID'];
		$params = is_array($fields['PARAMS']) ? $fields['PARAMS'] : [];
		$this->calculateParameters = $this->formatCalculateParameters($params);
	}

	public static function load($id)
	{
		if ((int)($id) <= 0)
			return null;

		$fieldsRaw = InvoiceRecurTable::getById((int)$id);
		if ($fields = $fieldsRaw->fetch())
		{
			$invoiceObject = new self($fields['ID']);
			$invoiceObject->initFields($fields);
			return $invoiceObject;
		}

		return null;
	}

	public static function loadByInvoiceId($invoiceId)
	{
		if ((int)($invoiceId) <= 0)
			return null;

		$fieldsRaw = InvoiceRecurTable::getList([
			"filter" => array("=INVOICE_ID" => $invoiceId),
			"limit" => 1
		]);
		if ($fields = $fieldsRaw->fetch())
		{
			$invoiceObject = new self($fields['ID']);
			$invoiceObject->initFields($fields);
			return $invoiceObject;
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

	public function isChanged()
	{
		return !empty($this->previousRecurringFields);
	}

	protected function fillTemplateFields()
	{
		$result = new Main\Result();
		$fields = \CCrmInvoice::GetByID($this->templateId);
		if (empty($fields))
		{
			$result->addError(new Main\Error('Template entity not found'));
			return $result;
		}

		$this->setTemplateFields($fields);

		$products = [];
		$productRowData = \CCrmInvoice::GetProductRows($this->templateId);
		foreach ($productRowData as $row)
		{
			if ($row['CUSTOM_PRICE'] === 'Y')
				$row['CUSTOMIZED'] = 'Y';
			$row['ID'] = 0;
			$products[] = $row;
		}
		if (!empty($products))
		{
			$this->setTemplateField('PRODUCT_ROWS', $products);
		}

		$properties = \CCrmInvoice::getPropertiesList([$this->templateId]);
		if(is_array($properties[$this->templateId]))
		{
			$newInvoiceProperties = [];
			foreach($properties[$this->templateId] as $invoiceProperty)
			{
				$value = $invoiceProperty['VALUE'];
				$newInvoiceProperties[$value['ORDER_PROPS_ID']] = $value['VALUE'];
			}
			$this->setTemplateField('INVOICE_PROPERTIES', $newInvoiceProperties);
		}

		$linkData = EntityLink::getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice,
					'=ENTITY_ID' => $this->templateId
				)
			)
		);

		if ($links = $linkData->fetch())
		{
			$this->setTemplateField('REQUISITES', $links);
		}

		return $result;
	}

	/**
	 * @param bool $recalculate 	Is need to recalculate activity and next execution date after exposing.
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectException
	 */
	public function expose($recalculate = false)
	{
		$result = new Main\Result();
		if ($this->isChanged())
		{
			$result->addError(new Main\Error('Error exposing. Recurring invoice was changed. Need to save changes before exposing.'));
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

		$fields = $this->prepareInvoiceFieldsBeforeExpose($this->templateFields);
		$addResult = $this->addExposingInvoice($fields, ['RESET_HISTORY_CREATOR_ID' => $recalculate]);
		if ($addResult->isSuccess())
		{
			$result->setData([
				'NEW_INVOICE_ID' => $addResult->getId()
			]);

			$this->onAfterInvoiceExpose( $addResult->getId(), $fields);

			$this->setFieldNoDemand('LAST_EXECUTION', new Date());
			$this->setFieldNoDemand('COUNTER_REPEAT', (int)$this->recurringFields['COUNTER_REPEAT'] + 1);

			if ($recalculate)
			{
				$today = new Date();
				$nextExecution = $this->calculateNextExecutionDate();
				if ($nextExecution && $today->getTimestamp() === $nextExecution->getTimestamp())
				{
					$nextExecution = $this->calculateNextExecutionDate($today->add('1 day'));
				}
				$this->setFieldNoDemand("NEXT_EXECUTION", $nextExecution);
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

	private function prepareInvoiceFieldsBeforeExpose($fields)
	{
		$fields['RECURRING_ID'] = $this->templateId;
		$fields['IS_RECURRING'] = 'N';
		$fields['DATE_BILL'] = new Date();
		$userFields = $this->prepareUserFields($this->templateId);
		$fields = array_merge($fields, $userFields);
		unset(
			$fields['ID'], $fields['ACCOUNT_NUMBER'], $fields['DATE_STATUS'],
			$fields['DATE_UPDATE'], $fields['DATE_INSERT'], $fields['DATE_PAY_BEFORE'],
			$fields['PAY_VOUCHER_NUM'], $fields['PAY_VOUCHER_DATE'],
			$fields['REASON_MARKED_SUCCESS'], $fields['DATE_MARKED'], $fields['REASON_MARKED']
		);

		$datePayBefore = $this->getDatePayBefore();
		if ($datePayBefore instanceof Date)
		{
			$fields['DATE_PAY_BEFORE'] = $datePayBefore;
		}

		$fields['STATUS_ID'] = \CCrmInvoice::GetDefaultStatusId();
		return $fields;
	}

	protected function prepareUserFields($invoiceId)
	{
		$userFieldValues = [];
		$userFields = $this->getUserFieldInstance()->GetEntityFields($invoiceId);
		foreach ($userFields as $key => $field)
		{
			if ($field["USER_TYPE"]["BASE_TYPE"] === "file" && !empty($field['VALUE']))
			{
				if (is_array($field['VALUE']))
				{
					$fileList = [];
					foreach ($field['VALUE'] as $value)
					{
						$fileData = \CFile::MakeFileArray($value);
						if (is_array($fileData))
						{
							$fileList[] = $fileData;
						}
					}
					$userFieldValues[$key] = $fileList;
				}
				else
				{
					$fileData = \CFile::MakeFileArray($field['VALUE']);
					if (is_array($fileData))
					{
						$userFieldValues[$key] = $fileData;
					}
				}
			}
		}
		return $userFieldValues;
	}

	protected function getDatePayBefore()
	{
		if (
			empty($this->getCalculateParameter('DATE_PAY_BEFORE_TYPE'))
			|| $this->getCalculateParameter('DATE_PAY_BEFORE_TYPE') === Entity\Invoice::UNSET_DATE_PAY_BEFORE
		)
		{
				return null;
		}

		$period = (int)$this->getCalculateParameter('DATE_PAY_BEFORE_PERIOD');
		$count = (int)$this->getCalculateParameter('DATE_PAY_BEFORE_COUNT');
		$result['PERIOD'] = (int)$period;

		if (empty($period))
		{
			return null;
		}

		switch($period)
		{
			case Calculator::SALE_TYPE_DAY_OFFSET:
				{
					$result['DAILY_TYPE'] = DateType\Day::TYPE_A_FEW_DAYS_AFTER;
					$result['INTERVAL'] = $count;
					break;
				}
			case Calculator::SALE_TYPE_WEEK_OFFSET:
				{
					$result['WEEKLY_TYPE'] = DateType\Week::TYPE_A_FEW_WEEKS_AFTER;
					$result['INTERVAL'] = $count;
					break;
				}
			case Calculator::SALE_TYPE_MONTH_OFFSET:
				{
					$result['MONTHLY_TYPE'] = DateType\Month::TYPE_A_FEW_MONTHS_AFTER;
					$result['INTERVAL'] = $count;
					break;
				}
			default:
				return null;
		}

		return Entity\Invoice::getNextDate($result);
	}

	protected function addExposingInvoice($fields, array $options = [])
	{
		$result = new Main\ORM\Data\AddResult();
		$invoiceController = $this->getControllerInstance();
		$reCalculateInvoice = false;
		try
		{
			$newInvoiceId = $invoiceController->Add($fields, $reCalculateInvoice, $this->templateFields['LID']);
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
			return $result;
		}

		if ($newInvoiceId)
		{
			$responsibleId = (int)$fields['RESPONSIBLE_ID'];
			if ($responsibleId > 0 && $options['RESET_HISTORY_CREATOR_ID'])
			{
				$notificationRaw = InvoiceChangeTable::getList([
					'filter' => [
						'ORDER_ID' => $newInvoiceId,
						'TYPE' => 'ORDER_ADDED'
					],
					'select' => ['ID'],
					'limit' => 1
				]);
				if ($notification = $notificationRaw->fetch())
				{
					InvoiceChangeTable::update($notification['ID'], ['USER_ID' => $responsibleId]);
				}
			}

			$result->setId($newInvoiceId);
			if (!empty($this->templateFields['REQUISITES']))
			{
				$requisiteInvoice = $this->templateFields['REQUISITES'];
				EntityLink::register(
					\CCrmOwnerType::Invoice,
					$newInvoiceId,
					$requisiteInvoice['REQUISITE_ID'],
					$requisiteInvoice['BANK_DETAIL_ID'],
					$requisiteInvoice['MC_REQUISITE_ID'],
					$requisiteInvoice['MC_BANK_DETAIL_ID']
				);
			}
		}
		else
		{
			$result->addError(new Main\Error($invoiceController->LAST_ERROR));
		}

		return $result;
	}

	private function onAfterInvoiceExpose($newId, array $newInvoiceFields = [])
	{
		$event = new Main\Event("crm", static::ON_INVOICE_RECURRING_EXPOSE_EVENT, [
			'ID' => $this->id,
			'RECURRING_ID' => $this->templateId,
			'INVOICE_ID' => $newId,
		]);
		$event->send();
	}

	/**
	 * @return Main\ORM\Data\UpdateResult|Result
	 * @throws \Exception
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

	protected function update($updateFields)
	{
		return InvoiceRecurTable::update($this->id, $updateFields);
	}

	protected function onAfterSave(array $updateFields)
	{
		$entityModifyFields = [
			'TYPE' => \CCrmOwnerType::InvoiceRecurringName,
			'ID' => $this->id,
			'FIELDS' => $updateFields
		];
		$event = new Main\Event("crm", static::ON_CRM_ENTITY_RECURRING_MODIFY, $entityModifyFields);
		$event->send();

		$updateFields['ID'] = $this->id;
		$updateFields['INVOICE_ID'] = $this->templateId;
		$event = new Main\Event("crm", static::ON_INVOICE_RECURRING_UPDATE_EVENT, $updateFields);
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
			$result = InvoiceRecurTable::delete($this->id);
		}
		catch (\Exception $e)
		{
			$result->addError(new Main\Error($e->getMessage()));
		}

		if ($result->isSuccess())
		{
			$event = new Main\Event("crm", static::ON_INVOICE_RECURRING_DELETE_EVENT, ['ID' => $this->id]);
			$event->send();
		}
		return $result;
	}

	public function isSendingInvoice()
	{
		return $this->recurringFields['SEND_BILL'] === 'Y' && (int)$this->recurringFields['EMAIL_ID'] > 0;
	}

	public function getPreparedEmailData()
	{
		$result = [];
		if ($this->isSendingInvoice())
		{
			$templateId = (int)$this->getCalculateParameter('EMAIL_TEMPLATE_ID');
			$result = [
				'EMAIL_ID' => (int)$this->recurringFields['EMAIL_ID'],
				'TEMPLATE_ID' => $templateId > 0 ? $templateId : null
			];
		}
		return $result;
	}

	public function deactivate(): void
	{
		$this->setFieldNoDemand('ACTIVE', 'N');
		$this->setFieldNoDemand('NEXT_EXECUTION', null);
	}
}
