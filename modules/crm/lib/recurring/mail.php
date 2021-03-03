<?php
namespace Bitrix\Crm\Recurring;

use Bitrix\Main\Type\DateTime,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Sale\PaySystem,
	Bitrix\Sale\BusinessValue,
	Bitrix\Main,
	Bitrix\Crm,
	Bitrix\Disk,
	Bitrix\Crm\Integration\StorageType,
	Bitrix\Crm\Integration\StorageManager,
	Bitrix\Crm\Invoice\Invoice,
	Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class Mail
{
	protected $invoice = array();
	protected $dataTo = array();
	protected $dataFrom = array();
	protected $templateData = array();

	/**
	 * Mail constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Set mail data before sending
	 *
	 * @param array $invoiceData
	 * @param array $sendData
	 * @param null $mailTemplateId
	 *
	 * @return Result
	 */
	public function setData($invoiceData = array(), $sendData = array(), $mailTemplateId = null)
	{
		$result = new Main\Result();

		if(count($invoiceData) === 0 || empty($invoiceData['UF_MYCOMPANY_ID']) || (int)($invoiceData['ID']) <= 0)
		{
			$result->addError(new Main\Error('INVOICE DATA ARE NOT FOUND!'));
			return $result;
		}

		if (
			empty($this->dataFrom['ELEMENT_ID'])
			|| $this->dataFrom['ELEMENT_ID'] === $invoiceData['UF_MYCOMPANY_ID']
			|| empty($this->dataFrom['TYPE_ID'])
		)
		{
			$r = $this->prepareDataFrom((int)$invoiceData['UF_MYCOMPANY_ID']);
			if ($r->isSuccess())
			{
				$this->dataFrom = $r->getData();
			}
			else
			{
				return $r;
			}
		}

		$r = $this->prepareDataTo($sendData);
		if ($r->isSuccess())
		{
			$this->dataTo = $r->getData();
		}
		else
		{
			return $r;
		}

		if (empty($this->templateData['ID']) || $this->templateData['ID'] !== (int)$mailTemplateId)
		{
			if (empty($mailTemplateId))
			{
				$this->templateData = array();
			}
			else
			{
				$templateData = \CCrmMailTemplate::GetByID((int)$mailTemplateId);
				if ($templateData)
				{
					$this->templateData = $templateData;
				}
				else
				{
					$this->templateData = array();
				}
			}
		}

		$this->invoice = $invoiceData;

		return $result;
	}

	/**
	 * Prepare owner data
	 *
	 * @param $elementId
	 *
	 * @return Result
	 */
	protected function prepareDataFrom($elementId)
	{
		$result = new Main\Result();

		$data = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'=ELEMENT_ID' => $elementId,
				'=ENTITY_ID' => 'COMPANY',
				'=TYPE_ID' => 'EMAIL'
			)
		);
		$ownerData = $data->Fetch();

		if (!($ownerData) || !check_email($ownerData['VALUE']))
		{
			$result->addError(new Main\Error('Mail sending error. Owner Email is not found'));
			return $result;
		}

		if (isset($ownerData['ENTITY_ID']))
		{
			$ownerTypeName = mb_strtoupper(strval($ownerData['ENTITY_ID']));
		}
		else
		{
			$result->addError(new Main\Error('Mail sending error. Owner type entity is not defined!'));
			return $result;
		}

		$ownerTypeId = \CCrmOwnerType::ResolveID($ownerTypeName);
		if(!\CCrmOwnerType::IsDefined($ownerTypeId))
		{
			$result->addError(new Main\Error('Mail sending error. Owner type is not supported!'));
			return $result;
		}

		$result->setData(
			array(
				'ELEMENT_ID' => $ownerData['ELEMENT_ID'],
				'VALUE' => htmlspecialcharsbx($ownerData['VALUE']),
				'TYPE_ID' => $ownerTypeId
			)
		);

		return $result;
	}

	/**
	 * Prepare receiver data
	 *
	 * @param $data
	 *
	 * @return Result
	 */
	protected function prepareDataTo($data)
	{
		$result = new Main\Result();

		if(!check_email($data['VALUE']))
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_INVALID_EMAIL')));
			return $result;
		}

		$communications = array(
			'ID' => (int)$data['ID'],
			'TYPE' => $data['TYPE_ID'],
			'VALUE' => htmlspecialcharsbx($data['VALUE']),
			'ENTITY_TYPE_ID' => \CCrmOwnerType::ResolveID($data['ENTITY_ID']),
			'ENTITY_ID' => (int)$data['ELEMENT_ID']
		);
		\CCrmActivity::PrepareCommunicationInfo($communications);

		$bindings[$data['COMPLEX_ID']. '_' .$data['ENTITY_ID']] = array(
			'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($data['ENTITY_ID']),
			'OWNER_ID' => (int)$data['ELEMENT_ID']
		);

		$result->setData(
			array(
				'COMMUNICATIONS' => $communications,
				'BINDINGS' => $bindings
			)
		);

		return $result;
	}

	/**
	 * @param $invoice
	 *
	 * @return array
	 */
	protected function fillEmailMessage($invoice)
	{
		return array(
			'SUBJECT' => $this->fillMessageSubject($invoice),
			'BODY' => $this->fillMessageBody($invoice)
		);
	}

	/**
	 * @param $invoice
	 *
	 * @return bool
	 */
	protected function fillMessageBody($invoice)
	{
		$body = isset($this->templateData['BODY']) ? (string)($this->templateData['BODY']) : '';
		if (!empty($body))
		{
			if (\CCrmContentType::BBCode === $this->templateData['BODY_TYPE'])
			{
				$bbCodeParser = new \CTextParser();
				$body = $bbCodeParser->convertText($body);
			}

			$body = \CCrmTemplateManager::prepareTemplate(
				$body,
				\CCrmOwnerType::Invoice, $invoice['ID'],
				\CCrmContentType::Html,
				$invoice['RESPONSIBLE_ID']
			);
		}

		\CCrmActivity::AddEmailSignature($body, \CCrmContentType::BBCode);

		if (empty($body))
		{
			$body = Loc::getMessage('CRM_RECUR_EMPTY_BODY_MESSAGE');
		}

		return $body;
	}

	/**
	 * @param $invoice
	 *
	 * @return mixed|string
	 */
	protected function fillMessageSubject($invoice)
	{
		$subject = isset($this->templateData['SUBJECT']) ? (string)($this->templateData['SUBJECT']) : '';
		if ($subject !== '')
		{
			$subject = \CCrmTemplateManager::PrepareTemplate(
				$subject,
				\CCrmOwnerType::Invoice, $invoice['ID'],
				\CCrmContentType::Html,
				$invoice['RESPONSIBLE_ID']
			);
		}

		if ($subject === '')
		{
			$subject = Loc::getMessage(
				'CRM_RECUR_DEFAULT_EMAIL_SUBJECT',
				array(
					'#ACCOUNT_NUMBER#'=> ($invoice['ACCOUNT_NUMBER'] !== '') ? $invoice['ACCOUNT_NUMBER'] : $invoice['ID'],
				)
			);
			if ($invoice['ORDER_TOPIC'] !== '')
			{
				$subject .= ' - ' .$invoice['ORDER_TOPIC'];
			}
		}

		return $subject;
	}

	/**
	 * Sending invoice
	 *
	 * @return Result
	 */
	public function sendInvoice()
	{
		global $USER_FIELD_MANAGER;

		$result = new Main\Result();
		$now = new DateTime();

		$invoice = $this->invoice;

		if (empty($invoice['ID']) || empty($invoice['ACCOUNT_NUMBER']))
		{
			$result->addError(new Main\Error('Mail sending error. Invoice data are empty!'));
			return $result;
		}

		$message = $this->fillEmailMessage($invoice);

		if(!check_email($this->dataFrom['VALUE']))
		{
			$result->addError(new Main\Error('Mail sending error. Wrong owner email!'));
			return $result;
		}

		try
		{
			$attachmentId = static::getPdfAttachment($invoice['ID']);
			if (is_bool($attachmentId))
			{
				if ($attachmentId === false)
				{
					$result->addError(new Main\Error(Loc::getMessage('CRM_ERROR_SAVING_BILL')));
				}

				return $result;
			}
		}
		catch (Main\SystemException $e)
		{
			$result->addError(new Main\Error($e->getMessage(), $e->getCode()));
			return $result;
		}

		$attachments = array($attachmentId);
		if (!empty($this->templateData['ID']) && $this->templateData['ID'] > 0 && StorageType::getDefaultTypeId() === StorageType::Disk)
		{
			$files = $USER_FIELD_MANAGER->getUserFieldValue('CRM_MAIL_TEMPLATE', 'UF_ATTACHMENT', $this->templateData['ID']);
			if (!empty($files) && is_array($files))
			{
				$diskUfManager = Disk\Driver::getInstance()->getUserFieldManager();
				$diskUfManager->loadBatchAttachedObject($files);
				foreach ($files as $attachedId)
				{
					if ($attachedObject = $diskUfManager->getAttachedObjectById($attachedId))
					{
						$attachments[] = $attachedObject->getObjectId();

						$message['BODY'] = str_replace(
							sprintf('bxacid:%u', $attachedId),
							sprintf('bxacid:n%u', $attachedObject->getObjectId()),
							$message['BODY']
						);
					}
				}
			}
		}

		$fields = array(
			'OWNER_ID' => $this->dataFrom['ID'],
			'OWNER_TYPE_ID' => $this->dataFrom['TYPE_ID'],
			'TYPE_ID' =>  \CCrmActivityType::Email,
			'SUBJECT' => $message['SUBJECT'],
			'START_TIME' => $now,
			'END_TIME' => $now,
			'COMPLETED' => 'Y',
			'RESPONSIBLE_ID' => $invoice['RESPONSIBLE_ID'],
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'DESCRIPTION' => $message['BODY'],
			'DESCRIPTION_TYPE' => \CCrmContentType::Html,
			'DIRECTION' => \CCrmActivityDirection::Outgoing,
			'LOCATION' => '',
			'SETTINGS' => array('MESSAGE_FROM' => $this->dataFrom['VALUE']),
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'STORAGE_TYPE_ID' => StorageType::getDefaultTypeID(),
			'COMMUNICATIONS' => array($this->dataTo['COMMUNICATIONS']),
			'STORAGE_ELEMENT_IDS' => $attachments,
			'BINDINGS' => array_values($this->dataTo['BINDINGS'])
		);

		if(!($id = \CCrmActivity::Add($fields, false, false, array('REGISTER_SONET_EVENT' => true))))
		{
			$result->addError(new Main\Error(\CCrmActivity::GetLastErrorMessage()));
			return $result;
		}

		\CCrmActivity::SaveCommunications($id, $fields['COMMUNICATIONS'], $fields);

		$hostname = \COption::getOptionString('main', 'server_name', 'localhost');
		if (defined('BX24_HOST_NAME') && BX24_HOST_NAME !== '')
		{
			$hostname = BX24_HOST_NAME;
		}
		elseif (defined('SITE_SERVER_NAME') && SITE_SERVER_NAME !== '')
		{
			$hostname = SITE_SERVER_NAME;
		}

		$description = $fields['DESCRIPTION'];
		foreach ($attachments as $item)
		{
			$fileInfo = StorageManager::getFileInfo(
				$item, $fields['STORAGE_TYPE_ID'], false,
				array('OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $id)
			);

			$description = str_replace(
				sprintf('bxacid:n%u', $item),
				htmlspecialcharsbx($fileInfo['VIEW_URL']),
				$description,
				$count
			);

			if ($count > 0)
			{
				$descriptionUpdated = true;
			}

			$fileArray = StorageManager::makeFileArray($item, $fields['STORAGE_TYPE_ID']);

			$contentId = sprintf(
				'bxacid.%s@%s.crm',
				hash('crc32b', $fileArray['external_id'].$fileArray['size'].$fileArray['name']),
				hash('crc32b', $hostname)
			);
			$fields['DESCRIPTION'] = str_replace(
				sprintf('bxacid:n%u', $item),
				sprintf('cid:%s', $contentId),
				$fields['DESCRIPTION']
			);
		}

		if (!empty($descriptionUpdated))
		{
			\CCrmActivity::update($id, array(
				'DESCRIPTION' => $description,
			), false, false, array('REGISTER_SONET_EVENT' => true));
		}

		$sendErrors = array();
		if(!\CCrmActivityEmailSender::TrySendEmail($id, $fields, $sendErrors))
		{
			$errorList = array();
			foreach($sendErrors as $error)
			{
				$code = $error['CODE'];
				if($code === \CCrmActivityEmailSender::ERR_CANT_LOAD_SUBSCRIBE)
				{
					$errors[] = 'Email send error. Failed to load module "subscribe".';
				}
				elseif($code === \CCrmActivityEmailSender::ERR_INVALID_DATA)
				{
					$errors[] = 'Email send error. Invalid data.';
				}
				elseif($code === \CCrmActivityEmailSender::ERR_INVALID_EMAIL)
				{
					$errors[] = 'Email send error. Invalid email is specified.';
				}
				elseif($code === \CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_FROM)
				{
					$errors[] = 'Email send error. "From" is not found.';
				}
				elseif($code === \CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_TO)
				{
					$errors[] = 'Email send error. "To" is not found.';
				}
				elseif($code === \CCrmActivityEmailSender::ERR_CANT_ADD_POSTING)
				{
					$errors[] = 'Email send error. Failed to add posting. Please see details below.';
				}
				elseif($code === \CCrmActivityEmailSender::ERR_CANT_SAVE_POSTING_FILE)
				{
					$errors[] = 'Email send error. Failed to save posting file. Please see details below.';
				}
				elseif($code === \CCrmActivityEmailSender::ERR_CANT_UPDATE_ACTIVITY)
				{
					$errors[] = 'Email send error. Failed to update activity.';
				}
				else
				{
					$errors[] = 'Email send error. General error.';
				}

				$msg = $error['MESSAGE'] ?? '';
				if($msg !== '')
				{
					$errors[] = $msg;
				}
				foreach ($errors as $errorMsg)
				{
					$errorList[] = new Main\Error($errorMsg);
				}
			}
			$result->addErrors($errorList);
			return $result;
		}

		addEventToStatFile(
			'crm',
			'send_email_message',
			sprintf('recurring_%s', $invoice['RECURRING_ID']),
			trim(trim($fields['SETTINGS']['MESSAGE_HEADERS']['Message-Id']), '<>')
		);

		if ($result->isSuccess())
		{
			$result->setData(array('ACTIVITY_ID' => $id));
		}

		return $result;
	}

	/**
	 * Get pdf file of payment bill.
	 *
	 * @param $invoiceId
	 * @return bool|false|int
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\LoaderException
	 * @throws Main\NotSupportedException
	 */
	public static function getPdfAttachment($invoiceId)
	{
		if (!Loader::includeModule('sale'))
		{
			return false;
		}

		$siteId = '';
		$invoice = Invoice::load($invoiceId);
		if (!$invoice)
		{
			return false;
		}
		$paymentCollection = $invoice->getPaymentCollection();
		/** @var \Bitrix\Sale\Payment $payment */
		$payment = $paymentCollection->current();
		$paySystem = $payment->getPaymentSystemId();

		$action = new \CSalePaySystemAction();

		$dbRes = Invoice::getList(
			array(
				'select' => array('*', 'UF_DEAL_ID', 'UF_QUOTE_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_MYCOMPANY_ID'),
				'filter' => array('ID' => $invoiceId)
			)
		);
		if ($data = $dbRes->fetch())
		{
			$paymentData = is_array($data) ? \CCrmInvoice::PrepareSalePaymentData($data, array('PUBLIC_LINK_MODE' => 'Y')) : null;
			$action->InitParamArrays($data, $invoiceId, '', $paymentData, array(), array(), REGISTRY_TYPE_CRM_INVOICE);
			$siteId = $data['LID'];

			if (!empty($paymentData['USER_FIELDS']))
			{
				BusinessValue::redefineProviderField([
					'PROPERTY' => $paymentData['USER_FIELDS']
				]);
			}
		}

		$service = PaySystem\Manager::getObjectById($paySystem);

		if ($service && $service->isAffordPdf())
		{
			$file = $service->getPdf($payment);
			if ($file === null)
			{
				if ($service->isAffordDocumentGeneratePdf()
					&& !$service->isPdfGenerated($payment)
				)
				{
					$service->registerCallbackOnGenerate(
						$payment,
						[
							'CALLBACK_CLASS' => '\Bitrix\Crm\Recurring\Mail',
							'CALLBACK_METHOD' => 'send',
							'MODULE_ID' => 'crm',
						]
					);

					return true;
				}

				return false;
			}

			$storageTypeId = StorageType::getDefaultTypeID();

			return StorageManager::saveEmailAttachment($file, $storageTypeId, $siteId);
		}

		return false;
	}

	/**
	 * @param $invoiceId
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function send($invoiceId)
	{
		$dbRes = Invoice::getList([
			'select' => ['RECURRING_ID'],
			'filter' => [
				'=ID' => $invoiceId
			]
		]);

		if ($data = $dbRes->fetch())
		{
			$dbRes = Crm\InvoiceRecurTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=INVOICE_ID' => $data['RECURRING_ID']
				]
			]);

			if ($data = $dbRes->fetch())
			{
				$recurringInstance = Entity\Item\InvoiceExist::load($data['ID']);
				if ($recurringInstance)
				{
					$preparedEmailData = $recurringInstance->getPreparedEmailData();
					if ($preparedEmailData)
					{
						$invoice = Crm\Recurring\Entity\Invoice::getInstance();
						$emailData[$invoiceId] = array(
							'EMAIL_ID' => (int)$preparedEmailData['EMAIL_ID'],
							'TEMPLATE_ID' => (int)$preparedEmailData['EMAIL_TEMPLATE_ID'] ? (int)$preparedEmailData['EMAIL_TEMPLATE_ID'] : null,
							'INVOICE_ID' => $invoiceId
						);

						$invoice->sendByMail([$preparedEmailData['EMAIL_ID']], $emailData);
					}
				}
			}
		}
	}
}