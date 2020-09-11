<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Engine\ActionFilter;

class CVoxImplantInvoiceListComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	use \Bitrix\Main\ErrorableImplementation;
	protected $gridId = 'voximplant_invoice_list';
	protected $filterId = 'voximplant_invoice_list_filter';

	public function __construct($component = null)
	{
		parent::__construct($component);
		Loader::includeModule("voximplant");
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	public function executeComponent()
	{
		$this->init();
		if(!$this->checkAccess())
			return false;

		$this->prepareData();
		if($this->arResult['ERROR'])
		{
			ShowError($this->arResult['ERROR']);
			return false;
		}

		$this->includeComponentTemplate();

		return $this->arResult;
	}

	public function init()
	{

	}

	public function checkAccess()
	{
		return true;
	}

	public function prepareData()
	{
		$apiClient = new CVoxImplantHttp();

		$gridOptions = new \Bitrix\Main\Grid\Options($this->gridId);
		$filter = $this->prepareFilter();

		$result = $apiClient->listInvoices($filter);

		if($result['error'])
		{
			$this->arResult['ERROR'] = $result['error']['msg'];
			return;
		}

		$this->arResult['GRID_ID'] = $this->gridId;
		$this->arResult['FILTER_ID'] = $this->filterId;
		$this->arResult['FILTER'] = $this->getFilterDefinition();
		$this->arResult['HEADERS'] = $this->prepareHeaders();
		$this->arResult['ROWS'] = $this->prepareRows($result);
		$this->arResult['ROWS_COUNT'] = $result['total_count'];
		$this->arResult['DOWNLOAD_URL'] = UrlManager::getInstance()->createByBitrixComponent($this, 'downloadInvoice', ['invoiceNumber' => 'INVOICE_NUMBER' ]);
	}

	public function prepareFilter()
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filterId);
		$filter = $filterOptions->getFilter($this->getFilterDefinition());

		$result = [];

		if ($filter["INVOICE_DATE_from"] <> '')
		{
			try
			{
				$result["from_invoice_date"] = \Bitrix\Main\Type\DateTime::createFromUserTime($filter["INVOICE_DATE_from"])->format('Y-m-d');
			} catch (Exception $e)
			{
			}
		}
		if ($filter["INVOICE_DATE_to"] <> '')
		{
			try
			{
				$result["to_invoice_date"] = \Bitrix\Main\Type\DateTime::createFromUserTime($filter["INVOICE_DATE_to"])->format('Y-m-d');
			} catch (Exception $e)
			{
			}
		}

		return $result;
	}

	public function prepareRows($apiResponse)
	{
		$result = [];

		if(!is_array($apiResponse['result']))
		{
			return $result;
		}
		$account = new CVoxImplantAccount();
		$currency = $account->GetAccountCurrency();
		$i = 0;
		foreach ($apiResponse['result'] as $resultRow)
		{
			$invoiceDate = new \Bitrix\Main\Type\Date($resultRow["invoice_date"], "Y-m-d");
			$fromDate = new \Bitrix\Main\Type\Date($resultRow["from_date"], "Y-m-d");
			$toDate = new \Bitrix\Main\Type\Date($resultRow["to_date"], "Y-m-d");
			if(Loader::includeModule('currency'))
			{
				$totalAmount = CCurrencyLang::CurrencyFormat($resultRow["total_amount"], $currency, true);
			}
			else
			{
				$totalAmount = htmlspecialcharsbx($resultRow["total_amount"]);
			}

			$result[] = [
				"id" => "invoice_row_" . $i++,
				"data" => [
					"INVOICE_DATE" => $invoiceDate,
					"INVOICE_NUMBER" => htmlspecialcharsbx($resultRow["invoice_number"]),
					"INVOICE_TYPE" => htmlspecialcharsbx($resultRow["invoice_type"]),
					"DATE_RANGE" => $fromDate . " - " . $toDate,
					"TOTAL_AMOUNT" => $totalAmount,
				],
				"editable" => false,
				"actions" => [
					[
						"TITLE" => Loc::getMessage("VOX_INVOICES_DOWNLOAD"),
						"TEXT" => Loc::getMessage("VOX_INVOICES_DOWNLOAD"),
						"ONCLICK" => "BX.Voximplant.Invoices.downloadInvoice('". CUtil::JSEscape($resultRow["invoice_number"]) ."')",
						"DEFAULT" => false
					]
				],
			];
		}
		return $result;
	}

	public function prepareHeaders()
	{
		return array(
			array("id" => "INVOICE_DATE", "name" => Loc::getMessage("VOX_INVOICES_DOCUMENT_DATE"), "default" => true, "editable" => false),
			array("id" => "INVOICE_NUMBER", "name" => Loc::getMessage("VOX_INVOICES_DOCUMENT_NUMBER"), "default" => true, "editable" => false),
			array("id" => "INVOICE_TYPE", "name" => Loc::getMessage("VOX_INVOICES_DOCUMENT_TYPE"), "default" => true, "editable" => false),
			array("id" => "DATE_RANGE", "name" => Loc::getMessage("VOX_INVOICES_DATE_RANGE"), "default" => true, "editable" => false),
			array("id" => "TOTAL_AMOUNT", "name" => Loc::getMessage("VOX_INVOICES_TOTAL_AMOUNT"), "default" => true, "editable" => false),
		);
	}

	public function getFilterDefinition()
	{
		return [
			"INVOICE_DATE" => [
				"id" => "INVOICE_DATE",
				"name" => Loc::getMessage("VOX_INVOICES_DOCUMENT_DATE"),
				"type" => "date",
				"default" => true
			]
		];
	}


	public function downloadInvoiceAction($invoiceNumber)
	{
		$apiClient = new CVoxImplantHttp();
		$fileContent = $apiClient->generateInvoice($invoiceNumber);

		if(!$fileContent)
		{
			if($apiClient->GetError()->msg)
			{
				$this->errorCollection[] = new \Bitrix\Main\Error($apiClient->GetError()->msg);
			}
			else
			{
				$this->errorCollection[] = new \Bitrix\Main\Error("Unknown error", "EMPTY_RESPONSE");
			}
			return null;
		}

		$localPath = \CTempFile::GetDirectoryName(12, "voximplant");
		if(!\Bitrix\Main\IO\Directory::isDirectoryExists($localPath))
		{
			\Bitrix\Main\IO\Directory::createDirectory($localPath);
		}
		$fileName = md5($invoiceNumber) . ".pdf";

		$path = $localPath . "/" . $fileName;

		\Bitrix\Main\IO\File::putFileContents($localPath . "/" . $fileName, $fileContent);

		return new \Bitrix\Main\Engine\Response\File($path, $fileName);
	}

	public function configureActions()
	{
		return [
			"downloadInvoice" => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
				],
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				]
			]
		];
	}
}