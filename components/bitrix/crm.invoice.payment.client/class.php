<?php
use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Sale,
	Bitrix\DocumentGenerator,
	Bitrix\Crm\WebForm\Form,
	Bitrix\Crm\Integration,
	Bitrix\Crm\Invoice\Invoice,
	Bitrix\Main\Application;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmInvoicePaymentClientComponent extends CBitrixComponent
{
	/** @var  Main\ErrorCollection $errorCollection*/
	private $errorCollection;

	/** @var  int $paySystemShowTemplateId*/
	private $paySystemShowTemplateId;

	/**
	 * Prepare data before execution
	 *
	 * @param Main\HttpRequest $request
	 * @return mixed
	 */
	protected function prepareData($request)
	{
		if (!isset($this->arParams['EXCLUDED_ACTION_LIST']))
		{
			$this->arParams['EXCLUDED_ACTION_LIST'] = array(
				'bill', 'billde', 'billen', 'billla', 'billua', 'billkz', 'billby', 'billbr', 'billfr', 'invoicedocument'
			);
		}

		if ($this->arParams['IS_AJAX_PAY'] === "Y")
		{
			if (isset($this->arParams['PAY_SYSTEM_ID']) && (int)$this->arParams['PAY_SYSTEM_ID'] > 0)
			{
				$this->paySystemShowTemplateId = (int)$this->arParams['PAY_SYSTEM_ID'];
			}
			else
			{
				$this->errorCollection->setError(new Main\Error(Loc::getMessage('CIPC_EMPTY_PAY_SYSTEM')));
				return false;
			}
		}

		if (!isset($this->arParams['ACCOUNT_NUMBER']))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('CIPC_WRONG_ACCOUNT_NUMBER')));
			return false;
		}

		if (!isset($this->arParams['ROWS_BANK_INFO']))
		{
			$this->arParams['ROWS_BANK_INFO'] = array(
				"SELLER_COMPANY_NAME", "SELLER_COMPANY_INN", "SELLER_COMPANY_BANK_ACCOUNT",
				"SELLER_COMPANY_BANK_BIC", "SELLER_COMPANY_BANK_NAME", "SELLER_COMPANY_BANK_ACCOUNT_CORR"
			);
		}

		if (!isset($this->arParams['FORM_ID']))
		{
			$this->arParams['FORM_ID'] = $request['form_id'];
		}
		$this->arParams['FORM_ID'] = (int) $this->arParams['FORM_ID'];

		if (!isset($this->arParams['RETURN_URL']))
		{
			$this->arParams['RETURN_URL'] = (new Sale\PaySystem\Context())->getUrl();
		}

		return true;
	}

	/**
	 * Move all errors to $this->arResult, if there were any
	 *
	 * @return void
	 */
	protected function formatResultErrors()
	{
		if (!$this->errorCollection->isEmpty())
		{
			/** @var Main\Error $error */
			foreach ($this->errorCollection->toArray() as $error)
			{
				$this->arResult['errorMessage'][] = $error->getMessage();
			}
		}
	}

	/**
	 * Check Required Modules
	 *
	 * @return bool
	 */
	protected function checkModules()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('CRM_MODULE_NOT_INSTALLED')));
			return false;
		}
		if (!Loader::includeModule('sale'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE')));
			return false;
		}
		return true;
	}

	/**
	 * Check hash sum in request
	 *
	 * @param $hash
	 * @param Sale\Payment $payment
	 * @return bool
	 */
	protected function checkHash($hash, $payment)
	{
		if ($hash !== $payment->getHash())
		{
			$this->errorCollection->clear();
			$this->errorCollection->setError(new Main\Error(Loc::getMessage("CIPC_WRONG_LINK")));
			return false;
		}
		return true;
	}

	/**
	 * Make URL for buttons 'save' and 'print'
	 *
	 * @return void
	 */
	protected function generateButtonLinks()
	{
		if (is_callable(array('CSalePdf', 'isPdfAvailable')) && CSalePdf::isPdfAvailable())
		{
			$pathToInvoicePayment = "/pub/payment.php?invoice_id=#invoice_id#&hash=".$this->arParams['HASH'];

			$this->arResult['BUTTONS'] = array(
				'SAVE' => CHTTP::urlAddParams(
						CComponentEngine::makePathFromTemplate(
							$pathToInvoicePayment,
							array('invoice_id' => $this->arParams['ACCOUNT_NUMBER'])
						),
						array('pdf' => 1, 'DOWNLOAD' => 'Y', 'ncc' => '1')),
				'PRINT' => "BX.util.popup('".CHTTP::urlAddParams(
						CComponentEngine::makePathFromTemplate(
							$pathToInvoicePayment,
							array('invoice_id' => $this->arParams['ACCOUNT_NUMBER'])
						),
						array('PRINT' => 'Y', 'ncc' => '1'))."', 960, 600)",
				"B24" => CIntranetUtils::getB24Link()
			);
		}
	}

	/**
	 * @return mixed
	 */
	public function executeComponent()
	{
		$this->errorCollection = new Main\ErrorCollection();

		if ($this->checkModules())
		{
			$this->processExecution();
		}

		$this->formatResultErrors();
		$this->includeComponentTemplate();
	}

	/**
	 * Fill list paysystems in $this->arResult
	 *
	 * @param Sale\Payment $payment
	 * @return void
	 */
	protected function fillPaySystemsArray($payment)
	{
		$paySystemList = Sale\PaySystem\Manager::getListWithRestrictions($payment);

		$innerId = Sale\PaySystem\Manager::getInnerPaySystemId();
		unset($paySystemList[$innerId]);

		foreach ($paySystemList as $key => $paySystemElement)
		{
			if (
				!in_array($paySystemElement['ACTION_FILE'], $this->arParams['EXCLUDED_ACTION_LIST']) &&
				!preg_match('/quote(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $paySystemElement['ACTION_FILE'])
			)
			{
				$service = new Sale\PaySystem\Service($paySystemElement);
				if (!$service->isTuned())
					continue;

				if ((int)$paySystemElement["LOGOTIP"] > 0)
				{
					$logoList = CFile::GetFileArray($paySystemElement["LOGOTIP"]);
					$paySystemElement["LOGOTIP"] = $logoList['SRC'];
				}
				else
				{
					$paySystemElement["LOGOTIP"] = '/bitrix/images/sale/sale_payments/'.$paySystemElement['ACTION_FILE'].'.png';
					if (!Bitrix\Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].$paySystemElement["LOGOTIP"]))
						$paySystemElement["LOGOTIP"] = '/bitrix/images/sale/sale_payments/default.png';
				}

				$this->arResult['PAYSYSTEMS_LIST'][] = $paySystemElement;
			}
		}
		return;
	}

	/**
	 * Return template of payment
	 *
	 * @param  Sale\PaySystem\Service $paySystemObject
	 * @param Sale\Payment $payment
	 * @return string $billTemplate
	 */
	protected function getPaySystemTemplate($payment, $paySystemObject)
	{
		if ($paySystemObject->getField('ACTION_FILE') === 'invoicedocument'
			&& $paySystemObject->getField('PS_MODE')
			&& Loader::includeModule('documentgenerator')
		)
		{
			$this->arResult['USE_FRAME'] = 'N';

			// crutch for starting generation
			$paySystemObject->getPdfContent($payment);

			$dbRes = DocumentGenerator\Model\DocumentTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=PROVIDER' => Integration\DocumentGenerator\DataProvider\Invoice::class,
					'=TEMPLATE_ID' => $paySystemObject->getField('PS_MODE'),
					'=VALUE' => $payment->getOrderId()
				],
				'order' => ['ID' => 'DESC'],
				'limit' => 1,
			]);

			if ($data = $dbRes->fetch())
			{
				$document = DocumentGenerator\Document::loadById($data['ID']);
				$document->enablePublicUrl();

				$data = $document->getFile()->getData();

				$this->arResult['FILE_PARAMS'] = array_merge($data, DocumentGenerator\Model\ExternalLinkTable::getPublicUrlsByDocumentId($document->ID));
			}

			return '';
		}

		$this->arResult['USE_FRAME'] = 'Y';

		if ($this->arParams['RETURN_URL'])
		{
			$paySystemObject->getContext()->setUrl($this->arParams['RETURN_URL']);
		}

		$paySystemBufferedOutput = $paySystemObject->initiatePay($payment, null, Sale\PaySystem\BaseServiceHandler::STRING);
		if (!$paySystemBufferedOutput->isSuccess())
		{
			$this->errorCollection->add($paySystemBufferedOutput->getErrors());
			return false;
		}

		return $paySystemBufferedOutput->getTemplate();
	}

	/**
	 * Processing data
	 *
	 * @return void
	 */
	protected function processExecution()
	{
		global $APPLICATION;

		$payBillSystem = null;

		/** @var Main\HttpRequest $request */
		$request = Application::getInstance()->getContext()->getRequest();

		$APPLICATION->SetTitle(Loc::getMessage('CIPC_TITLE_COMPONENT'));

		if (!$this->prepareData($request))
		{
			return;
		}

		if ($this->arParams['ACCOUNT_NUMBER'] == '')
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('CIPC_WRONG_ACCOUNT_NUMBER')));
			return;
		}

		$invoice = Invoice::loadByAccountNumber($this->arParams['ACCOUNT_NUMBER']);
		if (!$invoice)
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('CIPC_WRONG_ACCOUNT_NUMBER')));
			return;
		}

		$paymentCollection = $invoice->getPaymentCollection();

		/** @var Sale\Payment $payment */
		$payment = $paymentCollection->current();

		if (!($payment instanceof Sale\Payment) || !$this->checkHash($this->arParams['HASH'], $payment))
		{
			$this->errorCollection->clear();
			$this->errorCollection->setError(new Main\Error(Loc::getMessage("CIPC_WRONG_LINK")));
			return;
		}

		if ($this->arParams['IS_AJAX_PAY'] !== "Y")
		{
			if (array_key_exists('PAY_SYSTEM_ID', $this->arParams))
			{
				$service = Sale\PaySystem\Manager::getObjectById($this->arParams['PAY_SYSTEM_ID']);
				if ($service !== null)
				{
					if ($this->arParams['RETURN_URL'])
					{
						$service->getContext()->setUrl($this->arParams['RETURN_URL']);
					}

					$result = $service->initiatePay($payment, null, Sale\PaySystem\BaseServiceHandler::STRING);
					$result->getErrors();
					$this->arResult['PAY_SYSTEM_TEMPLATE'] = $result->getTemplate();
					return;
				}
			}

			$this->fillPaySystemsArray($payment);
			$this->paySystemShowTemplateId = $payment->getPaymentSystemId();
		}

		$this->arResult['CUSTOMIZATION'] = array();
		if ($this->arParams['FORM_ID'] > 0)
		{
			$form = new Form;
			$form->loadOnlyForm($this->arParams['FORM_ID']);
			$formData = $form->get();
			if($formData && $formData['BACKGROUND_IMAGE'])
			{
				$this->arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'] = htmlspecialcharsbx(
					CFile::GetPath($formData['BACKGROUND_IMAGE'])
				);
			}
		}

		$paySystemObject = Sale\PaySystem\Manager::getObjectById($this->paySystemShowTemplateId);

		if (!$paySystemObject)
		{
			$this->errorCollection->clear();
			$this->errorCollection->setError(new Main\Error(Loc::getMessage("CIPC_WRONG_LINK")));
			return;
		}

		/** crutch for support crm entities */
		$dbRes = Invoice::getList(
			array(
				'select' => array('*', 'UF_DEAL_ID', 'UF_QUOTE_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_MYCOMPANY_ID'),
				'filter' => array('ID' => $invoice->getId())
			)
		);
		if ($data = $dbRes->fetch())
		{
			$paymentData = is_array($data) ? CCrmInvoice::PrepareSalePaymentData($data, array('PUBLIC_LINK_MODE' => 'Y')) : null;

			$companyId = (int)$data['UF_COMPANY_ID'];
			$contactId = (int)$data['UF_CONTACT_ID'];

			$clientId = $data['USER_ID'];
			if ($companyId > 0)
			{
				$clientId = 'C'.$companyId;
			}
			elseif ($contactId > 0)
			{
				$clientId = 'P'.$contactId;
			}

			\Bitrix\Sale\BusinessValue::redefineProviderField(
				array(
					'PROPERTY' => $paymentData['USER_FIELDS'],
					'ORDER' => array('USER_ID' => $clientId),
				)
			);
			CSalePaySystemAction::InitParamArrays($data, $invoice->getId(), '', $paymentData, array(), array(), REGISTRY_TYPE_CRM_INVOICE);
		}

		if ($this->arParams['IS_AJAX_PAY'] !== "Y")
		{
			$this->collectBankInformation($payment, $paySystemObject);
		}

		if ($invoice->isPaid())
		{
			$this->fillOrderPaidArray($payment);
			return;
		}

		$this->arResult['BILL_TEMPLATE'] = $this->getPaySystemTemplate($payment, $paySystemObject);

		if ($this->arResult['BILL_TEMPLATE'] !== false)
		{
			$this->generateButtonLinks();
		}
		else
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('CIPC_ERROR_PAYMENT_EXECUTION')));
		}

		return;
	}

	/**
	 * Fill paid order values in resulted array
	 *
	 * @param Sale\Payment $payment
	 * @return void
	 */
	protected function fillOrderPaidArray($payment)
	{
		$paymentValues = $payment->getFieldValues();

		$this->arResult['PAY_SYSTEM_PAID_ARRAY'] = array(
			"PAY_SYSTEM_NAME" => $paymentValues['PAY_SYSTEM_NAME'],
			"ACCOUNT_NUMBER" => $this->arParams['ACCOUNT_NUMBER']
		);

		/**@var DateTime $time*/
		$time = null;

		if (!empty($paymentValues['DATE_BILL']))
		{
			$time = $paymentValues['DATE_BILL'];
			$this->arResult['PAY_SYSTEM_PAID_ARRAY']['DATE_BILL'] = $time->format("d.m.Y");
		}

		if (!empty($paymentValues['DATE_PAID']))
		{
			$time = $paymentValues['DATE_PAID'];
			$this->arResult['PAY_SYSTEM_PAID_ARRAY']['DATE_PAID'] = $time->format("d.m.Y");
		}
	}

	/**
	 * Collect bank information from order
	 *
	 * @param Sale\Payment $payment
	 * @param Sale\PaySystem\Service $paySystemObject
	 * @return void
	 */
	protected function collectBankInformation($payment, $paySystemObject)
	{
		$this->arResult['SUM'] = CCurrencyLang::CurrencyFormat($payment->getSum(), $payment->getField('CURRENCY'));
		$bankProps = $paySystemObject->getParamsBusValue($payment);
		$propsHandlerDescription = $paySystemObject->getHandlerDescription();
		$propsHandlerDescription = $propsHandlerDescription['CODES'];

		foreach ($this->arParams['ROWS_BANK_INFO'] as $nameProperty)
		{
			$propsHandlerDescription[$nameProperty]['VALUE'] = $bankProps[$nameProperty];
			if (isset($propsHandlerDescription[$nameProperty]['SHORT_NAME']))
			{
				$propsHandlerDescription[$nameProperty]['NAME'] = $propsHandlerDescription[$nameProperty]['SHORT_NAME'];
			}
			$this->arResult['BANK_PROPERTIES'][$nameProperty] = $propsHandlerDescription[$nameProperty];
		}
	}
}