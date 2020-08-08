<?php
namespace Bitrix\Crm\Recurring\Entity;

use Bitrix\Main,
	Bitrix\Main\Type\Date,
	Bitrix\Main\Result,
	Bitrix\Crm\Requisite\EntityLink,
	Bitrix\Crm\InvoiceRecurTable,
	Bitrix\Crm\InvoiceTable,
	Bitrix\Crm\Restriction\RestrictionManager,
	Bitrix\Crm\Recurring\Mail;

class Invoice extends Base
{
	const UNSET_DATE_PAY_BEFORE = 0;
	const SET_DATE_PAY_BEFORE = 1;

	/** @var Invoice */
	protected static $instance = null;

	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new Invoice();
		}
		return self::$instance;
	}

	public function getList(array $parameters = array())
	{
		return InvoiceRecurTable::getList($parameters);
	}

	/**
	 * Create recurring invoice
	 *
	 * @param array $invoiceFields
	 * @param array $recurParams
	 *
	 * @return Main\Result
	 * @throws \Exception
	 */
	public function createEntity(array $invoiceFields, array $recurParams)
	{
		$result = new Main\Result();
		try
		{
			$invoiceItem = Item\InvoiceNew::create();
			$invoiceItem->initFields($recurParams);
			$invoiceItem->setTemplateFields($invoiceFields);
			$result = $invoiceItem->save();
		}
		catch (\Exception $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	/**
	 * @param array $fields
	 *
	 * @return Result
	 * @throws Main\ObjectException
	 */
	public function add(array $fields)
	{
		$invoiceItem = Item\InvoiceNew::create();
		$invoiceItem->initFields($fields);
		return $invoiceItem->save();
	}

	/**
	 * Update recurring invoice
	 *
	 * @param int $primary
	 * @param array $data
	 *
	 * @return Main\Result
	 * @throws \Exception
	 */
	public function update($primary, array $data)
	{
		$entity = Item\InvoiceExist::load($primary);
		if (!$entity)
		{
			$result = new Main\Result();
			$result->addError(new Main\Error('Recurring invoice not found'));
		}

		$entity->setFields($data);
		return $entity->save();
	}

	/**
	 * Create new invoices from recurring invoices. Invoices's selection is from InvoiceRecurTable.
	 *
	 * @param array $filter
	 * @param null $limit
	 * @param bool $recalculate
	 *
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function expose(array $filter, $limit = null, $recalculate = true)
	{
		$result = new Main\Result();

		$idInvoicesList = array();
		$recurringInvoiceMap = array();
		$linkEntityList = array();
		$newInvoiceIds = array();
		$emailList = array();
		$emailData = array();

		$getParams = [
			'filter' => $filter,
			'select' => ['ID', 'INVOICE_ID'],
			'runtime' => $this->getRuntimeTemplateField()
		];
		if ((int)$limit > 0)
		{
			$getParams['limit'] = (int)$limit;
		}

		$recurring = InvoiceRecurTable::getList($getParams);

		while ($recurData = $recurring->fetch())
		{
			$recurData['INVOICE_ID'] = (int)$recurData['INVOICE_ID'];
			$idInvoicesList[] = $recurData['INVOICE_ID'];
			$recurringInvoiceMap[$recurData['INVOICE_ID']] = $recurData['ID'];
		}

		if (empty($idInvoicesList))
		{
			return $result;
		}

		try
		{
			$linkData = EntityLink::getList(
				array(
					'filter' => array(
						'=ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice,
						'=ENTITY_ID' => $idInvoicesList
					)
				)
			);

			while ($link = $linkData->fetch())
			{
				$linkEntityList[$link['ENTITY_ID']] = $link;
			}
			
			$idListChunks = array_chunk($idInvoicesList, 100);

			foreach ($idListChunks as $idList)
			{
				$products = array();
				$properties = array();

				$productRowData = \CCrmInvoice::GetProductRows($idList);

				foreach ($productRowData as $row)
				{
					if ($row['CUSTOM_PRICE'] === 'Y')
						$row['CUSTOMIZED'] = 'Y';

					$row['ID'] = 0;

					$products[$row['ORDER_ID']][] = $row;
				}

				$propertiesRowData = \CCrmInvoice::getPropertiesList($idList);

				foreach ($propertiesRowData as $invoiceId => $row)
				{
					$newInvoiceProperties = [];
					if(is_array($row))
					{
						foreach($row as $invoiceProperty)
						{
							$value = $invoiceProperty['VALUE'];
							$newInvoiceProperties[$value['ORDER_PROPS_ID']] = $value['VALUE'];
						}
						$properties[$invoiceId] = $newInvoiceProperties;
					}
				}

				unset($row);

				$invoicesData = \CCrmInvoice::GetList(
					array(),
					array(
						"=ID" => $idList,
						"CHECK_PERMISSIONS" => 'N'
					)
				);

				while ($invoice = $invoicesData->Fetch())
				{
					$invoice['PRODUCT_ROWS'] = $products[$invoice['ID']];
					$invoice['REQUISITES'] = $linkEntityList[$invoice['ID']];
					$invoice['INVOICE_PROPERTIES'] = $properties[$invoice['ID']];
					$recurringItem = Item\InvoiceExist::load($recurringInvoiceMap[$invoice['ID']]);
					if (!$recurringItem)
						continue;
					$recurringItem->setTemplateFields($invoice);
					$r = $recurringItem->expose($recalculate);
					if ($r->isSuccess())
					{
						$exposingData = $r->getData();
						$resultInvoiceId = $exposingData['NEW_INVOICE_ID'];
						$newInvoiceIds[] = $resultInvoiceId;
						if ($recurringItem->isSendingInvoice())
						{
							$preparedEmailData = $recurringItem->getPreparedEmailData();
							$emailList[] = $preparedEmailData['EMAIL_ID'];
							$emailData[$resultInvoiceId] = array(
								'EMAIL_ID' => (int)$preparedEmailData['EMAIL_ID'],
								'TEMPLATE_ID' => (int)$preparedEmailData['TEMPLATE_ID'] ? (int)$preparedEmailData['TEMPLATE_ID'] : null,
								'INVOICE_ID' => $resultInvoiceId
							);
						}
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
				}
			}

			unset($idListChunks, $idList);

			if (!empty($emailList))
			{
				$result = $this->sendByMail($emailList, $emailData);
			}
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		if (!empty($newInvoiceIds))
		{
			$result->setData(array("ID" => $newInvoiceIds));
		}

		return $result;
	}

	/**
	 * @param $primary
	 *
	 * @return Result
	 * @throws \Exception
	 */
	public function deactivate($primary)
	{
		return $this->update($primary, ['ACTIVE' => 'N']);
	}

	/**
	 * Activate recurring invoices
	 *
	 * @param $primary
	 *
	 * @return Main\Entity\UpdateResult|Result
	 * @throws \Exception
	 */
	public function activate($primary)
	{
		return $this->update($primary, ['ACTIVE' => 'Y']);
	}

	/**
	 * @param $primary
	 * @param string $reason
	 *
	 * @throws \Exception
	 */
	public function cancel($primary, $reason = "")
	{
		self::deactivate($primary);
	}

	/**
	 * @param $emailList
	 * @param $emailData
	 *
	 * @return Result
	 */
	public function sendByMail($emailList, $emailData)
	{
		$result = new Result();
		$emails = array();

		$emailFieldsData = \CCrmFieldMulti::GetListEx(
			array('ID' => 'asc'),
			array(
				'=ID' => $emailList,
				'=TYPE_ID' => 'EMAIL'
			)
		);
		while ($email = $emailFieldsData->Fetch())
		{
			$emails[$email['ID']] = $email;
		}

		if (!empty($emails)) 
		{
			$idListChunks = array_chunk(array_keys($emailData), 999);
			$mail = new Mail();
			foreach ($idListChunks as $idList) 
			{
				$newInvoiceData = \CCrmInvoice::GetList(
					array(),
					array(
						"=ID" => $idList,
						"CHECK_PERMISSIONS" => 'N'
					)
				);

				while ($invoice = $newInvoiceData->Fetch())
				{
					$emailId = $emailData[$invoice['ID']]['EMAIL_ID'];
					$templateId = $emailData[$invoice['ID']]['TEMPLATE_ID'];
					$r = $mail->setData($invoice, $emails[$emailId], $templateId);

					if ($r->isSuccess()) 
					{
						$mailResult = $mail->sendInvoice();
						if (!($mailResult->isSuccess())) 
						{
							$result->addErrors($mailResult->getErrors());
						}
					} 
					else 
						{
						$result->addErrors($r->getErrors());
					}
				}
			}
		}
		
		return $result;
	}

	/**
	 * @return bool
	 */
	public function isAllowedExpose()
	{
		$restriction = RestrictionManager::getInvoiceRecurringRestriction();
		return $restriction->hasPermission();
	}

	/**
	 * @param array $params
	 *
	 * @return ParameterMapper\Map
	 */
	public static function getParameterMapper(array $params = [])
	{
		return Item\InvoiceEntity::getFormMapper($params);
	}

	/**
	 * Calculate next exposing date.
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
	 * @param mixed $primary
	 *
	 * @return Main\Result
	 * @throws \Exception
	 */
	public function delete($primary)
	{
		$entity = Item\InvoiceExist::load($primary);
		if (!$entity)
		{
			$result = new Main\Result();
			$result->addError(new Main\Error('Recurring invoice not found'));
		}

		return $entity->delete();
	}

	public function getRuntimeTemplateField() : array
	{
		return [
			new Main\Entity\ReferenceField(
				'INVOICE_ENTITY',
				InvoiceTable::class,
				['=this.INVOICE_ID' => 'ref.ID'],
				['join_type' => 'INNER']
			)
		];
	}
}
