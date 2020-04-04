<?php
namespace Bitrix\Crm\Recurring\Entity;

use Bitrix\Main,
	Bitrix\Main\Type\Date,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Result,
	Bitrix\Main\Localization\Loc,
	Bitrix\Crm\Requisite\EntityLink,
	Bitrix\Crm\InvoiceRecurTable,
	Bitrix\Crm\Recurring\DateType,
	Bitrix\Crm\Recurring\Calculator,
	Bitrix\Crm\Recurring\Manager,
	\Bitrix\Crm\EntityRequisite,
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
		$newInvoice = new \CCrmInvoice();
		unset($invoiceFields['ID'], $invoiceFields['ACCOUNT_NUMBER']);

		try
		{
			$invoiceFields['DATE_BILL'] = new Date();
			$invoiceFields['RECURRING_ID'] = null;
			$invoiceFields['IS_RECURRING'] = 'Y';
			$recalculate = false;

			$idRecurringInvoice = $newInvoice->Add($invoiceFields, $recalculate, SITE_ID, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));

			if (!$idRecurringInvoice)
			{
				$result->addError(new Main\Error(Loc::getMessage("CRM_RECUR_INVOICE_ERROR_ADDITION")));
				return $result;
			}

			$this->linkInvoiceRequisite($invoiceFields, $idRecurringInvoice);
			$recurParams['INVOICE_ID'] = $idRecurringInvoice;
			$r = $this->add($recurParams);

			if ($r->isSuccess())
			{
				Manager::initCheckAgent(Manager::INVOICE);

				$result->setData(
					array(
						"INVOICE_ID" => $idRecurringInvoice,
						"ID" => $r->getId()
					)
				);

				$event = new Main\Event("crm", "OnCrmRecurringEntityModify", $recurParams);
				$event->send();
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
	 * @param array $fields
	 *
	 * @return Main\ORM\Data\AddResult
	 */
	public function add(array $fields)
	{
		$fields = $this->prepareDates($fields);
		$fields = $this->prepareActivity($fields);

		$fields['EMAIL_ID'] = ((int)$fields['EMAIL_ID'] > 0) ? (int)$fields['EMAIL_ID'] : null;
		if (is_null((int)$fields['EMAIL_ID']))
		{
			$fields['SEND_BILL'] = 'N';
		}

		return InvoiceRecurTable::add($fields);
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
		$result = new Main\Result();
		
		$primary = (int)$primary;
		if ($primary <= 0)
		{
			$result->addError(new Main\Error("Wrong primary ID"));
			return $result;
		}

		$data['NEXT_EXECUTION'] = null;

		$recur = InvoiceRecurTable::getById($primary);
		$recurData = $recur->fetch();

		if (!$recurData)
		{
			$result->addError(new Main\Error("Wrong primary ID"));
			return $result;
		}

		$data = array_merge($recurData, $data);

		$recurringParams = $data['PARAMS'];

		if (is_array($recurringParams))
		{
			$today = new Date();

			if ($data['START_DATE'] instanceof Date)
			{
				$startDay = $today->getTimestamp() > $data['START_DATE']->getTimestamp() ? $today : $data['START_DATE'];
			}
			else
			{
				$startDay = $today;
			}

			if ($data['LAST_EXECUTION'] instanceof Date)
			{
				if ($data['LAST_EXECUTION']->getTimestamp() >= $startDay->getTimestamp())
				{
					$startDay->add('+1 day');
				}
			}

			$data['NEXT_EXECUTION'] = self::getNextDate($recurringParams, $startDay);
		}

		$data = $this->prepareActivity($data);

		unset($data['ID'], $data['INVOICE_ID'], $data['COUNTER_REPEAT']);
		$resultUpdating = InvoiceRecurTable::update($primary, $data);
		if ($resultUpdating->isSuccess())
		{
			$event = new Main\Event("crm", "OnCrmRecurringEntityModify", $data);
			$event->send();
		}

		return $resultUpdating;
	}

	/**
	 * Create new invoices from recurring invoices. Invoices's selection is from InvoiceRecurTable.
	 *
	 * @param $filter
	 * @param $limit
	 * @param bool $recalculate
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 */
	public function expose(array $filter, $limit = null, $recalculate = true)
	{
		global $USER_FIELD_MANAGER;

		$result = new Main\Result();

		$idInvoicesList = array();
		$recurParamsList = array();
		$linkEntityList = array();
		$newInvoiceIds = array();
		$emailList = array();
		$emailData = array();

		$getParams['filter'] = $filter;
		if ((int)$limit > 0)
		{
			$getParams['limit'] = (int)$limit;
		}

		$recurring = InvoiceRecurTable::getList($getParams);

		while ($recurData = $recurring->fetch())
		{
			$recurData['INVOICE_ID'] = (int)$recurData['INVOICE_ID'];
			$idInvoicesList[] = $recurData['INVOICE_ID'];
			$recurParamsList[$recurData['INVOICE_ID']] = $recurData;
		}

		if (empty($idInvoicesList))
		{
			return $result;
		}

		try
		{
			$newInvoice = new \CCrmInvoice(false);
			$today = new Date();
			$tomorrow = Date::createFromTimestamp(strtotime('tomorrow'));
			$userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmInvoice::GetUserFieldEntityID());

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
			
			$idListChunks = array_chunk($idInvoicesList, 999);

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
					$properties[$invoiceId] = $row;
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
					$recurData = $recurParamsList[$invoice['ID']];
					$invoice['RECURRING_ID'] = $invoice['ID'];
					$invoice['IS_RECURRING'] = 'N';
					$invoice['PRODUCT_ROWS'] = $products[$invoice['ID']];
					$newInvoiceProperties = array();

					if(is_array($properties[$invoice['ID']]))
					{
						foreach($properties[$invoice['ID']] as $invoiceProperty)
						{
							$value = $invoiceProperty['VALUE'];
							$newInvoiceProperties[$value['ORDER_PROPS_ID']] = $value['VALUE'];
						}
						$invoice['INVOICE_PROPERTIES'] = $newInvoiceProperties;
					}

					$recurParam = $recurData['PARAMS'];

					$invoice['DATE_BILL'] = $today;
					$invoiceTemplateId = $invoice['ID'];
					$userFields = $userType->GetEntityFields($invoice['ID']);
					foreach ($userFields as $key => $field)
					{
						if ($field["USER_TYPE"]["BASE_TYPE"] == "file" && !empty($invoice[$key]))
						{
							if (is_array($invoice[$key]))
							{
								$fileList = array();
								foreach ($invoice[$key] as $value)
								{
									$fileData = \CFile::MakeFileArray($value);
									if (is_array($fileData))
									{
										$fileList[] = $fileData;
									}
								}
								$invoice[$key] = $fileList;
							}
							else
							{
								$fileData = \CFile::MakeFileArray($invoice[$key]);
								if (is_array($fileData))
								{
									$invoice[$key] = $fileData;
								}
								else
								{
									unset($invoice[$key]);
								}
							}
						}
					}
					unset(
						$invoice['ID'], $invoice['ACCOUNT_NUMBER'], $invoice['DATE_STATUS'],
						$invoice['DATE_UPDATE'], $invoice['DATE_INSERT'], $invoice['DATE_PAY_BEFORE'],
						$invoice['PAY_VOUCHER_NUM'], $invoice['PAY_VOUCHER_DATE'],
						$invoice['REASON_MARKED_SUCCESS'], $invoice['DATE_MARKED'], $invoice['REASON_MARKED']
					);

					if (!empty($recurParam['DATE_PAY_BEFORE_TYPE']) && $recurParam['DATE_PAY_BEFORE_TYPE'] !== self::UNSET_DATE_PAY_BEFORE)
					{
						$datePayBefore = $this->getDatePayBefore($recurParam);
						if ($datePayBefore instanceof Date)
							$invoice['DATE_PAY_BEFORE'] = $datePayBefore;
					}

					$reCalculateInvoice = false;
					$resultInvoiceId = $newInvoice->Add($invoice, $reCalculateInvoice, $invoice['LID']);

					if ($resultInvoiceId)
					{
						$requisiteInvoice = $linkEntityList[$invoiceTemplateId];

						EntityLink::register(
							\CCrmOwnerType::Invoice,
							$resultInvoiceId,
							$requisiteInvoice['REQUISITE_ID'],
							$requisiteInvoice['BANK_DETAIL_ID'],
							$requisiteInvoice['MC_REQUISITE_ID'],
							$requisiteInvoice['MC_BANK_DETAIL_ID']
						);
						
						$newInvoiceIds[] = $resultInvoiceId;

						$recurData["LAST_EXECUTION"] = $today;
						$recurData["COUNTER_REPEAT"] = (int)$recurData['COUNTER_REPEAT'] + 1;
						$updateData = array(
							"LAST_EXECUTION" => $recurData["LAST_EXECUTION"],
							"COUNTER_REPEAT" => $recurData["COUNTER_REPEAT"]
						);

						if ($recalculate)
						{
							$recurData["NEXT_EXECUTION"] = self::getNextDate($recurParam, clone($tomorrow));
							$updateData["NEXT_EXECUTION"] = $recurData["NEXT_EXECUTION"];
							$recurData = $this->prepareActivity($recurData);
							$updateData["ACTIVE"] = $recurData["ACTIVE"];
						}

						if ($recurData['SEND_BILL'] === 'Y' && (int)$recurData['EMAIL_ID'] > 0)
						{
							$emailList[] = (int)$recurData['EMAIL_ID'];
							$emailData[$resultInvoiceId] = array(
								'EMAIL_ID' => (int)$recurData['EMAIL_ID'],
								'TEMPLATE_ID' => (int)$recurParam['EMAIL_TEMPLATE_ID'] ? (int)$recurParam['EMAIL_TEMPLATE_ID'] : null,
								'INVOICE_ID' => $resultInvoiceId
							);
						}

						InvoiceRecurTable::update($recurData['ID'], $updateData);
						$event = new Main\Event("crm", "OnCrmRecurringEntityModify", $updateData);
						$event->send();
					}
					else
					{
						$result->addError(new Main\Error(Loc::getMessage("CRM_RECUR_INVOICE_ERROR_GET_EMPTY")));
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
	 * @param $invoiceId
	 *
	 * @return Main\ORM\Data\UpdateResult
	 * @throws Main\ArgumentException
	 */
	public function deactivate($invoiceId)
	{
		if ((int)$invoiceId > 0)
		{
			$invoiceId = (int)$invoiceId;
		}
		else
		{
			throw new Main\ArgumentException('Wrong invoice id.');
		}

		return InvoiceRecurTable::update(
			$invoiceId,
			array(
				"ACTIVE" => 'N',
				"NEXT_EXECUTION" => null
			)
		);
	}

	/**
	 * Activate recurring invoices
	 *
	 * @param $invoiceId
	 *
	 * @return Main\Entity\UpdateResult|Result
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public function activate($invoiceId)
	{
		$result = new Result();

		if ((int)$invoiceId > 0)
		{
			$invoiceId = (int)$invoiceId;
		}
		else
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_WRONG_ID')));
			return $result;
		}

		$invoiceData = InvoiceRecurTable::getList(
			array(
				"filter" => array("INVOICE_ID" => $invoiceId)
			)
		);
		if ($invoice = $invoiceData->fetch())
		{
			$recurringParams = $invoice['PARAMS'];
			$invoice['NEXT_EXECUTION'] = self::getNextDate($recurringParams);
			$invoice["COUNTER_REPEAT"] = (int)$invoice["COUNTER_REPEAT"] + 1;
			$isActive = $this->isActive($invoice);
			if ($isActive)
			{
				$result = InvoiceRecurTable::update(
					$invoiceId,
					array(
						"ACTIVE" => 'Y',
						"NEXT_EXECUTION" => $invoice['NEXT_EXECUTION'],
						"COUNTER_REPEAT" => $invoice['COUNTER_REPEAT']
					)
				);

				$event = new Main\Event("crm", "OnCrmRecurringEntityModify", $invoice);
				$event->send();
			}
			else
			{
				if ((int)$invoice['COUNTER_REPEAT'] > (int)$invoice['LIMIT_REPEAT'])
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
	 * @param $invoiceId
	 * @param string $reason
	 *
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public function cancel($invoiceId, $reason = "")
	{
		$invoiceId = (int)$invoiceId;
		if ($invoiceId <= 0)
		{
			throw new Main\ArgumentException('Wrong invoice id.');
		}

		$invoice =  new \CCrmInvoice();
		$invoice->Update(
			$invoiceId,
			array(
				"CANCELED" => "Y",
				"DATE_CANCELED" => new DateTime(),
				"REASON_CANCELED" => $reason
			)
		);

		$recurringData = InvoiceRecurTable::getList(
			array(
				"filter" => array("=INVOICE_ID" => $invoiceId)
			)
		);

		while ($recurring = $recurringData->fetch())
		{
			$this->update(
				$recurring['ID'],
				array(
					"ACTIVE" => "N",
					"NEXT_EXECUTION" => null
				)
			);
		}
	}

	/**
	 * @param array $invoiceFields
	 * @param $idRecurringInvoice
	 *
	 * @return void
	 * @throws Main\ArgumentException
	 */
	private function linkInvoiceRequisite(array $invoiceFields, $idRecurringInvoice)
	{
		$requisite = new EntityRequisite();

		$requisiteEntityList = array();
		$mcRequisiteEntityList = array();

		$requisiteIdLinked = 0;
		$bankDetailIdLinked = 0;
		$mcRequisiteIdLinked = 0;
		$mcBankDetailIdLinked = 0;

		if (isset($invoiceFields['UF_COMPANY_ID']) && $invoiceFields['UF_COMPANY_ID'] > 0)
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $invoiceFields['UF_COMPANY_ID']);
		if (isset($invoiceFields['UF_CONTACT_ID']) && $invoiceFields['UF_CONTACT_ID'] > 0)
			$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $invoiceFields['UF_CONTACT_ID']);
		if (isset($invoiceFields['UF_MYCOMPANY_ID']) && $invoiceFields['UF_MYCOMPANY_ID'] > 0)
			$mcRequisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $invoiceFields['UF_MYCOMPANY_ID']);
		$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);

		if (is_array($requisiteInfoLinked))
		{
			if (isset($requisiteInfoLinked['REQUISITE_ID']))
				$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
			if (isset($requisiteInfoLinked['BANK_DETAIL_ID']))
				$bankDetailIdLinked = (int)$requisiteInfoLinked['BANK_DETAIL_ID'];
		}
		$mcRequisiteInfoLinked = $requisite->getDefaultMyCompanyRequisiteInfoLinked($mcRequisiteEntityList);

		if (is_array($mcRequisiteInfoLinked))
		{
			if (isset($mcRequisiteInfoLinked['MC_REQUISITE_ID']))
				$mcRequisiteIdLinked = (int)$mcRequisiteInfoLinked['MC_REQUISITE_ID'];
			if (isset($mcRequisiteInfoLinked['MC_BANK_DETAIL_ID']))
				$mcBankDetailIdLinked = (int)$mcRequisiteInfoLinked['MC_BANK_DETAIL_ID'];
		}
		unset($requisite, $requisiteEntityList, $mcRequisiteEntityList, $requisiteInfoLinked, $mcRequisiteInfoLinked);

		if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
		{
			EntityLink::register(
				\CCrmOwnerType::Invoice, $idRecurringInvoice,
				$requisiteIdLinked, $bankDetailIdLinked,
				$mcRequisiteIdLinked, $mcBankDetailIdLinked
			);
		}
	}

	/**
	 * @param $emailList
	 * @param $emailData
	 *
	 * @return Result
	 */
	private function sendByMail($emailList, $emailData)
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
		if (Main\Loader::includeModule('bitrix24'))
			return !in_array(\CBitrix24::getLicenseType(), array('project', 'tf', 'retail'));

		return true;
	}

	/**
	 * Calculate payment date of a new invoice.
	 *
	 * @param array $params
	 *
	 * @return Date|null
	 */
	protected function getDatePayBefore(array $params)
	{
		$result['PERIOD'] = (int)$params['DATE_PAY_BEFORE_PERIOD'];

		if (empty($result['PERIOD']))
			return null;

		switch($result['PERIOD'])
		{
			case Calculator::SALE_TYPE_DAY_OFFSET:
			{
				$result['TYPE'] = DateType\Day::TYPE_A_FEW_DAYS_AFTER;
				$result['INTERVAL_DAY'] = (int)$params['DATE_PAY_BEFORE_COUNT'];
				break;
			}
			case Calculator::SALE_TYPE_WEEK_OFFSET:
			{
				$result['TYPE'] = DateType\Week::TYPE_A_FEW_WEEKS_AFTER;
				$result['INTERVAL_WEEK'] = (int)$params['DATE_PAY_BEFORE_COUNT'];
				break;
			}
			case Calculator::SALE_TYPE_MONTH_OFFSET:
			{
				$result['TYPE'] = DateType\Month::TYPE_A_FEW_MONTHS_AFTER;
				$result['INTERVAL_MONTH'] = (int)$params['DATE_PAY_BEFORE_COUNT'];
				break;
			}
			default:
				return null;
		}

		return parent::getNextDate($result);
	}

	/**
	 * @param array $params
	 *
	 * @return ParameterMapper\Map
	 */
	public static function getParameterMapper(array $params = [])
	{
		return ParameterMapper\FirstFormInvoice::getInstance();
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
}
