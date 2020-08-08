<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('crm'))
{
	return;
}

$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();

if(!function_exists('__CrmShowEndJsonResonse'))
{
	function __CrmShowEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["action"] <> '' && check_bitrix_sessid())
{
	$action = $_POST["action"];

	switch ($action)
	{
		case "delete":
			$entityID = $_POST["itemId"];

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_INVOICE_ID_NOT_DEFINED')));
			}
			if(!CCrmInvoice::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_INVOICE_NOT_FOUND')));
			}
			if(!CCrmInvoice::CheckDeletePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_INVOICE_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$obj = new CCrmInvoice();
				$res = $obj->Delete($entityID);

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => $obj->LAST_ERROR));
			}
			break;

		case "changeStatus":
			$entityID = $_POST["itemId"];
			$statusId = $_POST["statusId"];

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_INVOICE_ID_NOT_DEFINED')));
			}
			if(!CCrmInvoice::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_INVOICE_NOT_FOUND')));
			}
			if(!CCrmInvoice::CheckUpdatePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_INVOICE_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$fields = array("STATUS_ID" => $statusId);

				$obj = new CCrmInvoice();
				$res = $obj->Update($entityID, $fields);

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage("CRM_INVOICE_ERROR_CHANGE_STATUS")));
			}
			break;

		case "getPaySystemItems":
			$arPaySystemsListItems = array();

			if (isset($_POST["clientType"]))
			{
				if (in_array($_POST["clientType"], array("CONTACT", "COMPANY")))
				{
					$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();

					if ($_POST["clientType"] == "COMPANY")
						$personTypeId = $arPersonTypes['COMPANY'];
					elseif ($_POST["clientType"] == "CONTACT")
						$personTypeId = $arPersonTypes['CONTACT'];

					$arPaySystemsListItems = CCrmPaySystem::GetPaySystemsListItems($personTypeId);
				}

				/*$arPaySystemValues = array_keys($arPaySystemsListItems);
				if (!in_array($paySystemValue, $arPaySystemValues))
				{
					if (count($arPaySystemValues) === 0)
						$paySystemValue = 0;
					else
						$paySystemValue = $arPaySystemValues[0];
				}
				$arPaySystemsListData = array();
				foreach ($arPaySystemsListItems as $k => $v)
					$arPaySystemsListData[] = array('value' => $k, 'text' => $v);

				$arResponse['PAY_SYSTEMS_LIST'] = array(
					'items' => $arPaySystemsListData,
					'value' => $paySystemValue
				);
				unset($paySystemValue, $arPaySystemValues, $arPaySystemsListData, $arPaySystemsListItems);*/
			}

			__CrmShowEndJsonResonse(array('PAY_SYSTEMS' => $arPaySystemsListItems));
			break;

		case "savePdf":
			if (!CModule::IncludeModule('sale'))
			{
				__CrmShowEndJsonResonse(array('ERROR'=>'MODULE SALE NOT INCLUDED!'));
			}

			if(isset($_POST['INVOICE_ID']))
			{
				$invoice_id = $_POST['INVOICE_ID'];
			}
			else
			{
				__CrmShowEndJsonResonse(array('ERROR'=>'INVOICE_ID NOT DEFINED!'));
			}

			$CCrmInvoice = new CCrmInvoice();
			if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ') || !CCrmInvoice::CheckReadPermission($invoice_id))
			{
				__CrmShowEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
			}


			$pdfContent = '';

			$dbOrder = Bitrix\Crm\Invoice\Compatible\Invoice::getList(
				array("ID"=>"DESC"),
				array("ID" => $invoice_id),
				false,
				false,
				array('*', 'UF_DEAL_ID', 'UF_QUOTE_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_MYCOMPANY_ID')
			);

			$arOrder = $dbOrder->GetNext();
			$paymentData = is_array($arOrder) ? CCrmInvoice::PrepareSalePaymentData($arOrder) : null;
			if(!$arOrder)
			{
				__CrmShowEndJsonResonse(array('ERROR'=>'COULD NOT FIND ORDER!'));
			}

			if ($arOrder["SUM_PAID"] <> '')
				$arOrder["PRICE"] -= $arOrder["SUM_PAID"];

			$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($arOrder["PAY_SYSTEM_ID"]);
			if ($service !== null)
			{
				CSalePaySystemAction::InitParamArrays(
					$arOrder,
					$ID,
					"",
					array(
						'REQUISITE' => is_array($paymentData['REQUISITE']) ? $paymentData['REQUISITE'] : null,
						'BANK_DETAIL' => is_array($paymentData['BANK_DETAIL']) ? $paymentData['BANK_DETAIL'] : null,
						'CRM_COMPANY' => is_array($paymentData['CRM_COMPANY']) ? $paymentData['CRM_COMPANY'] : null,
						'CRM_CONTACT' => is_array($paymentData['CRM_CONTACT']) ? $paymentData['CRM_CONTACT'] : null,
						'MC_REQUISITE' => is_array($paymentData['MC_REQUISITE']) ? $paymentData['MC_REQUISITE'] : null,
						'MC_BANK_DETAIL' => is_array($paymentData['MC_BANK_DETAIL']) ? $paymentData['MC_BANK_DETAIL'] : null,
						'CRM_MYCOMPANY' => is_array($paymentData['CRM_MYCOMPANY']) ? $paymentData['CRM_MYCOMPANY'] : null
					),
					array(),
					array(),
					REGISTRY_TYPE_CRM_INVOICE
				);

				$order = Bitrix\Crm\Invoice\Invoice::load($invoice_id);
				if ($order)
				{
					$collection = $order->getPaymentCollection();
					if ($collection)
					{
						/** @var \Bitrix\Sale\Payment $payment */
						foreach ($collection as $payment)
						{
							if (!$payment->isInner())
							{
								$initResult = $service->initiatePay($payment, null, \Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);
								if ($initResult->isSuccess())
								{
									$pdfContent = $initResult->getTemplate();
								}
								else
								{
									__CrmShowEndJsonResonse(array('ERROR'=>'PDF MAKER NOT FOUNDED!'));
								}
								break;
							}
						}
					}
				}
			}

			$invNum = isset($_REQUEST['INVOICE_NUM']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_REQUEST['INVOICE_NUM']) : '';
			$fileName = 'invoice_'.($invNum <> '' ? $invNum : strval($invoice_id)).'.pdf';

			$fileData = array(
				'name' => $fileName,
				'type' => 'application/pdf',
				'content' => $pdfContent,
				'MODULE_ID' => 'crm'
			);

			$fileID = CFile::SaveFile($fileData, 'crm');
			if($fileID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => 'COULD NOT SAVE FILE!'));
			}

			$fileArray = CFile::GetFileArray($fileID);
			$storageTypeID = \Bitrix\Crm\Integration\StorageType::getDefaultTypeID();
			if($storageTypeID !== \Bitrix\Crm\Integration\StorageType::File)
			{
				$storageFileID = \Bitrix\Crm\Integration\StorageManager::saveEmailAttachment($fileArray, $storageTypeID);
				$fileInfo = $storageFileID > 0 ? \Bitrix\Crm\Integration\StorageManager::getFileInfo($storageFileID, $storageTypeID) : null;
				if(is_array($fileInfo))
				{
					if($storageTypeID === \Bitrix\Crm\Integration\StorageType::WebDav)
					{
						__CrmShowEndJsonResonse(array('webdavelement' => $fileInfo));
					}
					elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk)
					{
						__CrmShowEndJsonResonse(array('diskfile' => $fileInfo));
					}
				}
				__CrmShowEndJsonResonse(array('ERROR'=>'COULD NOT PREPARE FILE INFO!'));
			}

			__CrmShowEndJsonResonse(
				array('file' =>
					array(
						"fileName" => $fileArray['FILE_NAME'],
						"fileID" => $fileID,
						"fileSize" => CFile::FormatSize($fileArray['FILE_SIZE']),
						"src" => $fileArray['SRC']
					)
				)
			);

			break;
	}
}