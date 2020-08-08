<?php

namespace Bitrix\Crm\Invoice\Compatible;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class Helper
{

	public static function getList($arOrder = array("ID"=>"DESC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		if (!is_array($arOrder))
			$arOrder = array('ID' => 'DESC');
		if (!is_array($arFilter))
			$arFilter = array();
		if (!is_array($arSelectFields))
			$arSelectFields = array();

		if (array_key_exists("DATE_FROM", $arFilter))
		{
			$val = $arFilter["DATE_FROM"];
			unset($arFilter["DATE_FROM"]);
			$arFilter[">=DATE_INSERT"] = $val;
		}
		if (array_key_exists("DATE_TO", $arFilter))
		{
			$val = $arFilter["DATE_TO"];
			unset($arFilter["DATE_TO"]);
			$arFilter["<=DATE_INSERT"] = $val;
		}
		if (array_key_exists("DATE_INSERT_FROM", $arFilter))
		{
			$val = $arFilter["DATE_INSERT_FROM"];
			unset($arFilter["DATE_INSERT_FROM"]);
			$arFilter[">=DATE_INSERT"] = $val;
		}
		if (array_key_exists("DATE_INSERT_TO", $arFilter))
		{
			$val = $arFilter["DATE_INSERT_TO"];
			unset($arFilter["DATE_INSERT_TO"]);
			$arFilter["<=DATE_INSERT"] = $val;
		}
		if (array_key_exists("DATE_UPDATE_FROM", $arFilter))
		{
			$val = $arFilter["DATE_UPDATE_FROM"];
			unset($arFilter["DATE_UPDATE_FROM"]);
			$arFilter[">=DATE_UPDATE"] = $val;
		}
		if (array_key_exists("DATE_UPDATE_TO", $arFilter))
		{
			$val = $arFilter["DATE_UPDATE_TO"];
			unset($arFilter["DATE_UPDATE_TO"]);
			$arFilter["<=DATE_UPDATE"] = $val;
		}

		if (array_key_exists("DATE_STATUS_FROM", $arFilter))
		{
			$val = $arFilter["DATE_STATUS_FROM"];
			unset($arFilter["DATE_STATUS_FROM"]);
			$arFilter[">=DATE_STATUS"] = $val;
		}
		if (array_key_exists("DATE_STATUS_TO", $arFilter))
		{
			$val = $arFilter["DATE_STATUS_TO"];
			unset($arFilter["DATE_STATUS_TO"]);
			$arFilter["<=DATE_STATUS"] = $val;
		}
		if (array_key_exists("DATE_PAYED_FROM", $arFilter))
		{
			$val = $arFilter["DATE_PAYED_FROM"];
			unset($arFilter["DATE_PAYED_FROM"]);
			$arFilter[">=DATE_PAYED"] = $val;
		}
		if (array_key_exists("DATE_PAYED_TO", $arFilter))
		{
			$val = $arFilter["DATE_PAYED_TO"];
			unset($arFilter["DATE_PAYED_TO"]);
			$arFilter["<=DATE_PAYED"] = $val;
		}
		if (array_key_exists("DATE_ALLOW_DELIVERY_FROM", $arFilter))
		{
			$val = $arFilter["DATE_ALLOW_DELIVERY_FROM"];
			unset($arFilter["DATE_ALLOW_DELIVERY_FROM"]);
			$arFilter[">=DATE_ALLOW_DELIVERY"] = $val;
		}
		if (array_key_exists("DATE_ALLOW_DELIVERY_TO", $arFilter))
		{
			$val = $arFilter["DATE_ALLOW_DELIVERY_TO"];
			unset($arFilter["DATE_ALLOW_DELIVERY_TO"]);
			$arFilter["<=DATE_ALLOW_DELIVERY"] = $val;
		}
		if (array_key_exists("DATE_CANCELED_FROM", $arFilter))
		{
			$val = $arFilter["DATE_CANCELED_FROM"];
			unset($arFilter["DATE_CANCELED_FROM"]);
			$arFilter[">=DATE_CANCELED"] = $val;
		}
		if (array_key_exists("DATE_CANCELED_TO", $arFilter))
		{
			$val = $arFilter["DATE_CANCELED_TO"];
			unset($arFilter["DATE_CANCELED_TO"]);
			$arFilter["<=DATE_CANCELED"] = $val;
		}
		if (array_key_exists("DATE_DEDUCTED_FROM", $arFilter))
		{
			$val = $arFilter["DATE_DEDUCTED_FROM"];
			unset($arFilter["DATE_DEDUCTED_FROM"]);
			$arFilter[">=DATE_DEDUCTED"] = $val;
		}
		if (array_key_exists("DATE_DEDUCTED_TO", $arFilter))
		{
			$val = $arFilter["DATE_DEDUCTED_TO"];
			unset($arFilter["DATE_DEDUCTED_TO"]);
			$arFilter["<=DATE_DEDUCTED"] = $val;
		}
		if (array_key_exists("DATE_MARKED_FROM", $arFilter))
		{
			$val = $arFilter["DATE_MARKED_FROM"];
			unset($arFilter["DATE_MARKED_FROM"]);
			$arFilter[">=DATE_MARKED"] = $val;
		}
		if (array_key_exists("DATE_MARKED_TO", $arFilter))
		{
			$val = $arFilter["DATE_MARKED_TO"];
			unset($arFilter["DATE_MARKED_TO"]);
			$arFilter["<=DATE_MARKED"] = $val;
		}
		if (array_key_exists("DATE_PAY_BEFORE_FROM", $arFilter))
		{
			$val = $arFilter["DATE_PAY_BEFORE_FROM"];
			unset($arFilter["DATE_PAY_BEFORE_FROM"]);
			$arFilter[">=DATE_PAY_BEFORE"] = $val;
		}
		if (array_key_exists("DATE_PAY_BEFORE_TO", $arFilter))
		{
			$val = $arFilter["DATE_PAY_BEFORE_TO"];
			unset($arFilter["DATE_PAY_BEFORE_TO"]);
			$arFilter["<=DATE_PAY_BEFORE"] = $val;
		}
		if (array_key_exists("DELIVERY_REQUEST_SENT", $arFilter))
		{
			if($arFilter["DELIVERY_REQUEST_SENT"] == "Y")
				$arFilter["!DELIVERY_DATE_REQUEST"] = "";
			else
				$arFilter["+DELIVERY_DATE_REQUEST"] = "";

			unset($arFilter["DELIVERY_REQUEST_SENT"]);
		}

		$callback = false;
		if (array_key_exists("CUSTOM_SUBQUERY", $arFilter))
		{
			$callback = $arFilter["CUSTOM_SUBQUERY"];
			unset($arFilter["CUSTOM_SUBQUERY"]);
		}

		$result = Invoice::getList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $callback);
		if ($result instanceof Sale\Compatible\CDBResult)
			$result->addFetchAdapter(new Sale\Compatible\OrderFetchAdapter());

		return $result;
	}

	public static function update($id, $fields, $changeDateUpdate = true)
	{
		global $APPLICATION;

		$id = (int)$id;

		$fields1 = array();
		foreach ($fields as $key => $value)
		{
			if (mb_substr($key, 0, 1) == "=")
			{
				$fields1[mb_substr($key, 1)] = $value;
				unset($fields[$key]);
			}
		}

		if (!self::checkFields("UPDATE", $fields, $id))
			return false;

		if (!empty($fields1))
		{
			$fields1 = Invoice::backRawField(Invoice::ENTITY_ORDER, $fields1);
		}

		$result = Invoice::update($id, array_merge($fields, $fields1), $changeDateUpdate);
		if (!$result->isSuccess())
		{
			foreach($result->getErrorMessages() as $error)
			{
				$APPLICATION->ThrowException($error);
			}

			return false;
		}

		// TODO: ... [ORDER_CHANGE_001] - delete or replace
		/*$arOrderOldFields = array();

		$resultFields = $result->getData();
		if (!empty($resultFields['OLD_FIELDS']) && is_array($resultFields['OLD_FIELDS']))
		{
			$arOrderOldFields = $resultFields['OLD_FIELDS'];
		}

		\CSaleOrderChange::AddRecordsByFields($id, $arOrderOldFields, $fields);*/

		return $id;
	}

	public static function delete($id)
	{
		global $APPLICATION;
		
		$id = (int)$id;
		if ($id <= 0)
			return false;

		$arOrder = static::getById($id);
		if ($arOrder)
		{
			/** @var Sale\Result $r */
			$r = Invoice::delete($id);

			$orderDeleted = (bool)$r->isSuccess();

			if (!$r->isSuccess())
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_DELETE_ERROR",
						["#MESSAGE#" => implode(' ', $r->getErrorMessages())]
					)
				);
				return false;
			}

			return $orderDeleted;
		}

		return false;
	}

	private static function checkFields($action, &$fields, $id = 0)
	{
		/** @global \CDatabase $DB */
		/** @global \CMain $APPLICATION */
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $DB, $APPLICATION, $USER_FIELD_MANAGER;

		if (is_set($fields, "SITE_ID") && $fields["SITE_ID"] <> '')
			$fields["LID"] = $fields["SITE_ID"];

		if ((is_set($fields, "LID") || $action=="ADD") && $fields["LID"] == '')
		{
			$APPLICATION->ThrowException(Loc::getMessage("CRM_INVOICE_COMPAT_HELPER_EMPTY_SITE"));
			return false;
		}
		if ((is_set($fields, "PERSON_TYPE_ID") || $action=="ADD") && intval($fields["PERSON_TYPE_ID"])<=0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("CRM_INVOICE_COMPAT_HELPER_EMPTY_PERS_TYPE"));
			return false;
		}
		if ((is_set($fields, "USER_ID") || $action=="ADD") && intval($fields["USER_ID"])<=0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("CRM_INVOICE_COMPAT_HELPER_EMPTY_USER_ID"));
			return false;
		}

		if (is_set($fields, "PAYED") && $fields["PAYED"] != "Y")
			$fields["PAYED"] = "N";
		if (is_set($fields, "CANCELED") && $fields["CANCELED"] != "Y")
			$fields["CANCELED"] = "N";
		if (is_set($fields, "STATUS_ID") && $fields["STATUS_ID"] == '')
			$fields["STATUS_ID"] = "N";
		if (is_set($fields, "ALLOW_DELIVERY") && $fields["ALLOW_DELIVERY"] != "Y")
			$fields["ALLOW_DELIVERY"] = "N";
		if (is_set($fields, "EXTERNAL_ORDER") && $fields["EXTERNAL_ORDER"] != "Y")
			$fields["EXTERNAL_ORDER"] = "N";

		if (is_set($fields, "PRICE") || $action=="ADD")
		{
			$fields["PRICE"] = str_replace(",", ".", $fields["PRICE"]);
			$fields["PRICE"] = doubleval($fields["PRICE"]);
		}
		if (is_set($fields, "PRICE_DELIVERY") || $action=="ADD")
		{
			$fields["PRICE_DELIVERY"] = str_replace(",", ".", $fields["PRICE_DELIVERY"]);
			$fields["PRICE_DELIVERY"] = doubleval($fields["PRICE_DELIVERY"]);
		}
		if (is_set($fields, "SUM_PAID") || $action=="ADD")
		{
			$fields["SUM_PAID"] = str_replace(",", ".", $fields["SUM_PAID"]);
			$fields["SUM_PAID"] = doubleval($fields["SUM_PAID"]);
		}
		if (is_set($fields, "DISCOUNT_VALUE") || $action=="ADD")
		{
			$fields["DISCOUNT_VALUE"] = str_replace(",", ".", $fields["DISCOUNT_VALUE"]);
			$fields["DISCOUNT_VALUE"] = doubleval($fields["DISCOUNT_VALUE"]);
		}
		if (is_set($fields, "TAX_VALUE") || $action=="ADD")
		{
			$fields["TAX_VALUE"] = str_replace(",", ".", $fields["TAX_VALUE"]);
			$fields["TAX_VALUE"] = doubleval($fields["TAX_VALUE"]);
		}

		if(!is_set($fields, "LOCKED_BY") && (!is_set($fields, "UPDATED_1C") || (is_set($fields, "UPDATED_1C") && $fields["UPDATED_1C"] != "Y")))
		{
			$fields["UPDATED_1C"] = "N";
			$fields["~VERSION"] = "VERSION+0+1";
		}

		if ((is_set($fields, "CURRENCY") || $action=="ADD") && $fields["CURRENCY"] == '')
		{
			$APPLICATION->ThrowException(Loc::getMessage("CRM_INVOICE_COMPAT_HELPER_EMPTY_CURRENCY"));
			return false;
		}

		if (is_set($fields, "CURRENCY"))
		{
			if (!($arCurrency = \CCurrency::GetByID($fields["CURRENCY"])))
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_WRONG_CURRENCY",
						["#ID#" => $fields["CURRENCY"]]
					)
				);
				return false;
			}
		}

		if (is_set($fields, "LID"))
		{
			$dbSite = \CSite::GetByID($fields["LID"]);
			if (!$dbSite->Fetch())
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_WRONG_SITE",
						["#ID#" => $fields["LID"]]
					)
				);
				return false;
			}
		}

		if (is_set($fields, "USER_ID"))
		{
			$dbUser = \CUser::GetByID($fields["USER_ID"]);
			if (!$dbUser->Fetch())
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_WRONG_USER",
						["#ID#" => $fields["USER_ID"]]
					)
				);
				return false;
			}
		}

		if (is_set($fields, "PERSON_TYPE_ID"))
		{
			$dbRes = Crm\Invoice\PersonType::getList([
				'filter' => [
					'=ID' => $fields["PERSON_TYPE_ID"]
				]
			]);
			if (!$dbRes->fetch())
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_WRONG_PERSON_TYPE",
						["#ID#" => $fields["PERSON_TYPE_ID"]]
					)
				);
				return false;
			}
		}

		if (is_set($fields, "PAY_SYSTEM_ID") && intval($fields["PAY_SYSTEM_ID"]) > 0)
		{
			$paysystem = new \CSalePaySystem();
			if (!($arPaySystem = $paysystem->GetByID(intval($fields["PAY_SYSTEM_ID"]))))
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_WRONG_PS",
						["#ID#" => $fields["PAY_SYSTEM_ID"]]
					)
				);
				return false;
			}
			unset($paysystem);
		}

		if (is_set($fields, "DELIVERY_ID") && intval($fields["DELIVERY_ID"]) > 0)
		{
			if (!($delivery = Sale\Delivery\Services\Table::getById($fields["DELIVERY_ID"])))
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_WRONG_DELIVERY",
						["#ID#" => $fields["DELIVERY_ID"]]
					)
				);
				return false;
			}
		}

		if (is_set($fields, "STATUS_ID"))
		{
			$res = Crm\Invoice\InvoiceStatus::getList(['filter' => ['STATUS_ID' => $fields['STATUS_ID']]]);
			$row = $res->fetch();
			if (!is_array($row))
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						"CRM_INVOICE_COMPAT_HELPER_WRONG_STATUS",
						["#ID#" => $fields["STATUS_ID"]]
					)
				);
				return false;
			}
			unset($res, $row);
		}

		if (is_set($fields, "ACCOUNT_NUMBER") && $action=="UPDATE")
		{
			if ($fields["ACCOUNT_NUMBER"] == '')
			{
				$APPLICATION->ThrowException(Loc::getMessage("CRM_INVOICE_COMPAT_HELPER_EMPTY_ACCOUNT_NUMBER"));
				return false;
			}
			else
			{
				$accountNumber = $DB->ForSql($fields["ACCOUNT_NUMBER"]);
				$dbres = $DB->Query(
					"SELECT ID, ACCOUNT_NUMBER FROM b_crm_invoice WHERE ACCOUNT_NUMBER = '{$accountNumber}'", true
				);
				unset($accountNumber);
				if ($arRes = $dbres->GetNext())
				{
					if (is_array($arRes) && $arRes["ID"] != $id)
					{
						$APPLICATION->ThrowException(
							Loc::getMessage("CRM_INVOICE_COMPAT_HELPER_EXISTING_ACCOUNT_NUMBER")
						);
						return false;
					}
				}
			}
		}

		if($action == "ADD")
			$fields["VERSION"] = 1;

		if (!$USER_FIELD_MANAGER->CheckFields(Crm\Invoice\Internals\InvoiceTable::getUfId(), $id, $fields))
		{
			return false;
		}

		return True;
	}

	public static function payOrder($id, $paid)
	{
		/** @global \CUser $USER */
		/** @global \CMain $APPLICATION */
		global $USER, $APPLICATION;

		$id = (int)$id;
		$paid = (($paid != 'Y') ? 'N' : 'Y');

		if ($id <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_NO_INVOICE_ID'));
			return false;
		}

		$fields = self::getById($id);
		if (!$fields)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_NO_INVOICE', ['#ID#', $id]));
			return false;
		}

		if ($fields['PAYED'] == $paid)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_DUB_PAY', ['#ID#', $id]));
			return false;
		}

		$arFields = array(
			'PAYED' => $paid,
			'=DATE_PAYED' => Main\Application::getConnection()->getSqlHelper()->getCurrentDateTimeFunction(),
			'EMP_PAYED_ID' => (intval($USER->GetID()) > 0 ? intval($USER->GetID()) : false),
			'SUM_PAID' => 0
		);

		/** @var Sale\Result $r */
		$r = Invoice::pay($id, $arFields);

		if (!$r->isSuccess())
		{
			$APPLICATION->ThrowException(
				Loc::getMessage(
					"CRM_INVOICE_COMPAT_HELPER_PAY_ERROR",
					["#MESSAGE#" => implode(' ', $r->getErrorMessages())]
				)
			);
			return false;
		}

		return true;
	}

	public static function cancelOrder($id, $cancel, $description = '')
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$id = (int)$id;
		$cancel = (($cancel != 'Y') ? 'N' : 'Y');

		if ($id <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_NO_INVOICE_ID'));
			return false;
		}

		$fields = self::getById($id);
		if (!$fields)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_NO_INVOICE', ['#ID#', $id]));
			return false;
		}

		if ($fields['CANCELED'] == $cancel)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_DUB_CANCEL', ['#ID#', $id]));
			return false;
		}

		/** @var Sale\Result $r */
		$r = Invoice::cancel($id, $cancel, $description);
		if (!$r->isSuccess())
		{
			$APPLICATION->ThrowException(
				Loc::getMessage(
					"CRM_INVOICE_COMPAT_HELPER_CANCEL_ERROR",
					["#MESSAGE#" => implode(' ', $r->getErrorMessages())]
				)
			);
			return false;
		}

		return true;
	}

	public static function getById($id)
	{
		$id = (int)$id;
		if ($id <= 0)
			return false;

		$res = Invoice::getById($id);

		if ($row = $res->Fetch())
		{
			$dataKeys = array_keys($row);
			foreach ($dataKeys as $key)
			{

				if (!empty($row[$key])
					&& ($row[$key] instanceof \DateTime
						|| ($row[$key] instanceof Main\Type\DateTime)
						|| ($row[$key] instanceof Main\Type\Date)))
				{
					/** @var \Bitrix\Main\Type\Date|\Bitrix\Main\Type\DateTime $dateObject */
					$dateObject = $row[$key];

					if ($key == "DATE_INSERT")
					{
						$row['DATE_INSERT_FORMAT'] =
							Invoice::convertDateFieldToFormat($dateObject, FORMAT_DATE);
					}
					elseif ($key == "DATE_STATUS")
					{
						$row['DATE_STATUS_FORMAT'] =
							Invoice::convertDateFieldToFormat($dateObject, FORMAT_DATETIME);
					}
					elseif ($key == "DATE_UPDATE")
					{
						$row['DATE_UPDATE_FORMAT'] =
							Invoice::convertDateFieldToFormat($dateObject, FORMAT_DATETIME);
					}
					elseif ($key == "DATE_LOCK")
					{
						$row['DATE_LOCK_FORMAT'] =
							Invoice::convertDateFieldToFormat($dateObject, FORMAT_DATETIME);
					}

					$row[$key] = $dateObject->format('Y-m-d H:i:s');
				}
			}
			$row = Sale\Compatible\OrderFetchAdapter::convertRowData($row);

			return $row;
		}

		return false;
	}

	public static function doSaveOrder(&$fields, $additionalFields, $id, &$errors)
	{
		global $APPLICATION;

		$id = (int)$id;

		$defFields = array(
			"ID" => $fields["ID"],
			"LID" => $fields["SITE_ID"],
			"PERSON_TYPE_ID" => $fields["PERSON_TYPE_ID"],
			"PRICE" => $fields["PRICE"],
			"CURRENCY" => $fields["CURRENCY"],
			"USER_ID" => $fields["USER_ID"],
			"PAY_SYSTEM_ID" => $fields["PAY_SYSTEM_ID"],
			"PRICE_DELIVERY" => $fields["DELIVERY_PRICE"],
			"DELIVERY_ID" => ($fields["DELIVERY_ID"] <> '' ? $fields["DELIVERY_ID"] : false),
			"DISCOUNT_VALUE" => $fields["DISCOUNT_PRICE"],
			"TAX_VALUE" => $fields["TAX_VALUE"],
			"TRACKING_NUMBER" => $fields["TRACKING_NUMBER"]
		);

		if ($fields["DELIVERY_PRICE"] == $fields["PRICE_DELIVERY"]
			&& isset($fields['PRICE_DELIVERY_DIFF']) && floatval($fields['PRICE_DELIVERY_DIFF']) > 0)
		{
			$defFields["DELIVERY_PRICE"] = $fields['PRICE_DELIVERY_DIFF'] + $fields["PRICE_DELIVERY"];
		}

		if ($id <= 0)
		{
			$defFields["PAYED"] = "N";
			$defFields["CANCELED"] = "N";
			$defFields["STATUS_ID"] = "N";
		}
		$defFields = array_merge($defFields, $additionalFields);

		// It comes from places like crm_invoice`s Add() and tells us if we need
		// to convert location props from ID to CODE.
		if(!$fields['LOCATION_IN_CODES'])
		{
			\CSaleOrder::TranslateLocationPropertyValues($fields["PERSON_TYPE_ID"], $fields["ORDER_PROP"]);
		}
		unset($fields['LOCATION_IN_CODES']);


		$orderFields = array_merge($fields, $defFields, $additionalFields);
		if (isset($orderFields['CUSTOM_DISCOUNT_PRICE']) && $orderFields['CUSTOM_DISCOUNT_PRICE'] === true)
			Sale\Compatible\DiscountCompatibility::reInit(Sale\Compatible\DiscountCompatibility::MODE_DISABLED);

		if ($id > 0)
			$orderFields['ID'] = $id;

		if (isset($orderFields['ACCOUNT_NUMBER']))
		{
			$accountNumber = $orderFields['ACCOUNT_NUMBER'];
			if (is_string($accountNumber) && $accountNumber <> '' || is_numeric($accountNumber))
			{
				$res = static::getList(
					['ID' => 'ASC'],
					[
						'ACCOUNT_NUMBER' => $accountNumber,
						'!ID' => $id,
					],
					false,
					['nTopCount' => 1],
					['ID']
				);
				if ($res->fetch())
				{
					$errors[] = Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_EXISTING_ACCOUNT_NUMBER');
					return false;
				}
			}
		}

		/** @var Sale\Result $r */
		$r = Invoice::modifyOrder(Invoice::ORDER_COMPAT_ACTION_SAVE, $orderFields);
		if ($r->isSuccess())
		{
			$id = $r->getId();
		}
		else
		{
			foreach ($r->getErrorMessages() as $error)
			{
				$errors[] = $error;
				$APPLICATION->ThrowException($error);
			}

			return false;
		}

		return $id;
	}

	public static function setMark($id, $comment = "", $userId = 0)
	{
		/** @var Main\DB\Connection $connection */
		$connection = Main\Application::getInstance()->getConnectionPool()->getConnection();
		$helper = $connection->getSqlHelper();

		$id = (int)$id;
		if ($id < 0)
			return false;

		$userId = (int)$userId;

		$fields = array(
			"MARKED" => "Y",
			"REASON_MARKED" => $comment,
			"EMP_MARKED_ID" => $userId,
			"=DATE_MARKED" => $helper->getCurrentDateTimeFunction()
		);

		return static::update($id, $fields);
	}

	public static function statusOrder($id, $statusId)
	{
		global $USER, $APPLICATION;

		$id = (int)$id;
		$statusId = trim($statusId);

		if ($id <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_NO_INVOICE_ID'));
			return false;
		}

		$arOrder = static::getByID($id);
		if (!$arOrder)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_NO_INVOICE', ['#ID#', $id]));
			return false;
		}

		if ($arOrder['STATUS_ID'] == $statusId)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CRM_INVOICE_COMPAT_HELPER_DUB_STATUS', ['#ID#', $id]));
			return false;
		}

		/** @var Main\DB\Connection $connection */
		$connection = Main\Application::getInstance()->getConnectionPool()->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$userId = (isset($USER) && $USER instanceof \CUser) ? (int)$USER->GetID() : 0;
		$arFields = array(
			'STATUS_ID' => $statusId,
			'=DATE_STATUS' => $sqlHelper->getCurrentDateTimeFunction(),
			'EMP_STATUS_ID' => $userId > 0 ? $userId : false
		);

		return static::update($id, $arFields);
	}
}
