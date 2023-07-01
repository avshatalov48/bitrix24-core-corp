<?
IncludeModuleLangFile(__FILE__);

class CCrmExternalSaleImport
{
	private $externalSaleId = 0;
	private $arExternalSale = null;
	private $catalogId = 0;

	/** @var CCrmExternalSaleProxy*/
	private $proxy = null;

	private $path = "/bitrix/admin/1c_exchange.php";

	/** @var CCrmExternalSaleImportResult*/
	private $arImportResult = null;
	private $arError = array();
	private $serverVersion = null;
	private $serverSessionID = null;

	/** @var array|null  */
	private $params = array();

	const SyncStatusFinished = 0;
	const SyncStatusContinue = 1;
	const SyncStatusError = 2;

	/**
	 * Creates an importer
	 *
	 * @param $saleId ID of the external shop (CCrmExternalSale)
	 */
	public function __construct($saleId)
	{
		$this->externalSaleId = intval($saleId);
		$this->proxy = new CCrmExternalSaleProxy($this->externalSaleId);
		if (!$this->proxy->IsInitialized())
		{
			$this->AddError("PA1", sprintf("External site '%d' is not found", $this->externalSaleId));
			$this->proxy = null;
		}
	}

	/**
	 * Add parameter
	 * @param string $key Parameter key.
	 * @param mixed $value Parameter value.
	 */
	public function AddParam($key, $value)
	{
		$this->params[$key] = $value;
	}

	/**
	 * Remove parameter
	 * @param string $key Parameter key.
	 */
	public function RemoveParam($key)
	{
		unset($this->params[$key]);
	}

	/**
	 * The method loads the specified order.
	 *
	 * @param $orderId ID of the loaded order
	 * @param $skipBP Skip starting BPs
	 * @return int Possible values are CCrmExternalSaleImport::SyncStatusFinished - order was loaded,
	 *      CCrmExternalSaleImport::SyncStatusError - there was an error.
	 */
	public function GetOrderData($orderId, $skipBP = true)
	{
		$this->ClearErrors();
		$this->arImportResult = new CCrmExternalSaleImportResult();

		$orderId = intval($orderId);
		if ($orderId <= 0)
		{
			$this->AddError("GO1", "Order ID is not specified");
			return self::SyncStatusError;
		}

		$filter = array(
			"ORDER_ID" => $orderId,
			"GZ_COMPRESSION_SUPPORTED" => function_exists("gzcompress") ? 1 : 0,
			"type" => "crm",
			"mode" => "query"
		);

		$queryOptions = array();
		$sessid = $this->GetServerSessionID();
		$serverVersion = $this->GetServerVersion();
		if($sessid !== "" && $serverVersion >= 2.09)
		{
			$queryOptions["REQUEST_METHOD"] = "GET";
			$filter["sessid"] = $sessid;
		}
		else
		{
			$queryOptions["REQUEST_METHOD"] = "POST";
			if($sessid !== "")
			{
				$filter["sessid"] = $sessid;
			}
		}

		$orderData = $this->QueryOrderData($filter, $queryOptions);
		if ($orderData == null)
		{
			$this->AddError("SD2", "Communication error");
			return self::SyncStatusError;
		}

		$arErrors = array();
		$arOrders = $this->ParseOrderData($orderData, $modificationLabel, $arErrors);
		if (is_array($arOrders))
		{
			foreach ($arOrders as $order)
			{
				$this->SaveOrderData($order, $skipBP);
			}

			return self::SyncStatusFinished;
		}

		foreach ($arErrors as $error)
		{
			$this->AddError($error[0], $error[1]);
		}
		return self::SyncStatusError;
	}

	protected function GetServerVersion()
	{
		if($this->serverVersion !== null)
		{
			return $this->serverVersion;
		}

		$this->serverVersion = 0;

		$sessid = $this->GetServerSessionID();
		if($sessid !== "")
		{
			$request = array(
				"METHOD" => "GET",
				"PATH" =>  CHTTP::urlAddParams(
					$this->path,
					array(
						"type" => "crm",
						"mode" => "init",
						"version" => "2.09",
						"sessid" => $sessid
					)
				)
			);

			$response = $this->proxy->Send($request);
			if($response !== null)
			{
				$body = isset($response["BODY"]) ? $response["BODY"] : '';
				if(preg_match('/\bversion=([0-9\.]+)/', $body, $m) === 1)
				{
					$this->serverVersion = floatval($m[1]);
				}
			}
		}

		return $this->serverVersion;
	}
	protected function GetServerSessionID()
	{
		if($this->serverSessionID !== null)
		{
			return $this->serverSessionID;
		}

		$this->serverSessionID = "";
		$request = array(
			"METHOD" => "GET",
			"PATH" =>  CHTTP::urlAddParams($this->path, array("type" => "crm", "mode" => "checkauth"))
		);

		$response = $this->proxy->Send($request);
		if($response !== null)
		{
			$bodyLines = isset($response["BODY"]) ? explode("\n", $response["BODY"]) : array();
			if(count($bodyLines) > 3)
			{
				$ary = explode("=", $bodyLines[3]);
				if(count($ary) > 1)
				{
					$this->serverSessionID = trim($ary[1]);
				}
			}
		}
		return $this->serverSessionID;
	}
	/**
	 * The method loads the specified number of orders which was updated from the last load.
	 * 
	 * @return int Possible values are CCrmExternalSaleImport::SyncStatusFinished - all updated orders were loaded,
	 *      CCrmExternalSaleImport::SyncStatusContinue - the specified number of updated orders were loaded but may remain other updated orders and this method is necessary to start again,
	 *      CCrmExternalSaleImport::SyncStatusError - there was an error.
	 */
	public function SyncOrderData($bSkipBP = false, $bSkipNotify = false)
	{
		$this->ClearErrors();
		$this->arImportResult = new CCrmExternalSaleImportResult();

		self::AddTrace("START: {$this->externalSaleId}");

		@set_time_limit(0);
		@ini_set("track_errors", "1");
		@ignore_user_abort(true);

		if ($this->arExternalSale == null)
			$this->arExternalSale = CCrmExternalSale::GetDefaultSettings($this->externalSaleId);

		$importSize = intval($this->arExternalSale["SIZE"]);
		if ($importSize <= 0)
			$importSize = 100;
		$importPeriod = intval($this->arExternalSale["PERIOD"]);
		$modificationLabel = intval($this->arExternalSale["LABEL"]);

		$sessid = $this->GetServerSessionID();
		$serverVersion = $this->GetServerVersion();

		self::AddTrace("VERSION: {$serverVersion}");

		if($sessid !== "" && $serverVersion >= 2.09)
		{
			//Stepwise
			$data = $this->arExternalSale["SYNC_DATA"];

			if(!is_array($data) || empty($data))
			{
				$data = array(
					"DEAL_CREATED" => 0,
					"DEAL_UPDATED" => 0,
					"CONTACT_CREATED" => 0,
					"CONTACT_UPDATED" => 0,
					"COMPANY_CREATED" => 0,
					"COMPANY_UPDATED" => 0,
					"TOTAL"=> 0
				);
			}

			$modificationLabelTmp = $modificationLabel;
			if ($modificationLabelTmp <= 0)
			{
				$modificationLabelTmp = time() - $importPeriod * 86400;
			}

			$request = array(
				"LID" => LANGUAGE_ID,
				"MODIFICATION_LABEL" => $modificationLabelTmp,
				"ZZZ" => date("Z"),
				"IMPORT_SIZE" => $importSize,
				"GZ_COMPRESSION_SUPPORTED" => function_exists("gzcompress") ? 1 : 0,
				"type" => "crm",
				"mode" => "query",
				"sessid" => $sessid
			);

			self::AddTrace(array('QUERY_ORDER_DATA' => $request));

			$orderData = $this->QueryOrderData($request, array("REQUEST_METHOD" => "GET"));
			if ($orderData == null)
			{
				self::AddTrace('FINISH: NO SOURCE DATA');

				$this->AddError("SD2", "Communication error");
				CCrmExternalSale::Update($this->externalSaleId, array("SYNC_DATA" => array()));
				return self::SyncStatusError;
			}

			$arErrors = array();
			$arOrders = $this->ParseOrderData($orderData, $modificationLabelTmp, $arErrors);

			if(is_array($arOrders))
			{
				self::AddTrace('DATA PARSING SUCCESSFUL');
			}
			else
			{
				self::AddTrace('DATA PARSING FAILED');
			}

			foreach ($arErrors as $error)
			{
				$this->AddError($error[0], $error[1]);
			}

			if (is_array($arOrders))
			{
				if (count($arOrders) <= 0)
				{
					CCrmExternalSale::Update(
						$this->externalSaleId,
						array(
							"MODIFICATION_LABEL" => $modificationLabelTmp,
							"LAST_STATUS" => $data["TOTAL"] > 0
								? sprintf("Success: %d item(s)", $data["TOTAL"]) : "Success: 0 items",
							"~LAST_STATUS_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
							"SYNC_DATA" => array()
						)
					);

					if (!$bSkipNotify)
					{
						$this->arImportResult->numberOfCreatedDeals = $data["DEAL_CREATED"];
						$this->arImportResult->numberOfUpdatedDeals = $data["DEAL_UPDATED"];

						$this->arImportResult->numberOfCreatedContacts = $data["CONTACT_CREATED"];
						$this->arImportResult->numberOfUpdatedContacts = $data["CONTACT_UPDATED"];

						$this->arImportResult->numberOfCreatedCompanies = $data["COMPANY_CREATED"];
						$this->arImportResult->numberOfUpdatedCompanies = $data["COMPANY_UPDATED"];

						if($this->arImportResult->numberOfCreatedDeals > 0
							|| $this->arImportResult->numberOfUpdatedDeals > 0
							|| $this->arImportResult->numberOfCreatedContacts > 0
							|| $this->arImportResult->numberOfUpdatedContacts > 0
							|| $this->arImportResult->numberOfCreatedCompanies > 0
							|| $this->arImportResult->numberOfUpdatedCompanies)
						{
							$this->Notify();
						}

						// Reset totals for keep actual iteration totals
						$this->arImportResult->numberOfCreatedDeals = 0;
						$this->arImportResult->numberOfUpdatedDeals = 0;

						$this->arImportResult->numberOfCreatedContacts = 0;
						$this->arImportResult->numberOfUpdatedContacts = 0;

						$this->arImportResult->numberOfCreatedCompanies = 0;
						$this->arImportResult->numberOfUpdatedCompanies = 0;
					}

					self::AddTrace('FINISH: NO DATA TO SAVE');

					return self::SyncStatusFinished;
				}

				foreach ($arOrders as $order)
				{
					$this->SaveOrderData($order, $bSkipBP);
				}

				if(empty($this->arError))
				{
					$data["DEAL_CREATED"] += $this->arImportResult->numberOfCreatedDeals;
					$data["DEAL_UPDATED"] += $this->arImportResult->numberOfUpdatedDeals;

					$data["CONTACT_CREATED"] += $this->arImportResult->numberOfCreatedContacts;
					$data["CONTACT_UPDATED"] += $this->arImportResult->numberOfUpdatedContacts;

					$data["COMPANY_CREATED"] += $this->arImportResult->numberOfCreatedCompanies;
					$data["COMPANY_UPDATED"] += $this->arImportResult->numberOfUpdatedCompanies;

					$data["TOTAL"] += count($arOrders);

					$arFieldsTmp = array(
						"MODIFICATION_LABEL" => $modificationLabelTmp,
						"~LAST_STATUS_DATE" => $GLOBALS["DB"]->CurrentTimeFunction()
					);
					if (count($arOrders) > 0)
					{
						$arFieldsTmp["LAST_STATUS"] = sprintf("Success: %d item(s)", count($arOrders));
					}

					$arFieldsTmp["SYNC_DATA"] = $data;
					CCrmExternalSale::Update($this->externalSaleId, $arFieldsTmp);
					self::AddTrace('FINISH: CONTINUE');

					if (!$bSkipNotify)
					{
						$this->Notify();
					}

					return self::SyncStatusContinue;
				}
			}

			$ar = array();
			foreach ($this->arError as $err)
				$ar[] = sprintf("[%s] %s", $err[0], $err[1]);
			$this->arExternalSale["ERRORS"] = $this->arExternalSale["ERRORS"] + 1;

			CCrmExternalSale::Update(
				$this->externalSaleId,
				array(
					"LAST_STATUS" => implode(" ", $ar),
					"IMPORT_ERRORS" => $this->arExternalSale["ERRORS"],
					"~LAST_STATUS_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"SYNC_DATA" => array()
				)
			);

			self::AddTrace('FINISH: ERROR');
			return self::SyncStatusError;
		}
		else
		{
			$modificationLabelTmp = $modificationLabel;
			if ($modificationLabelTmp <= 0 && $importPeriod > 0)
			{
				$modificationLabelTmp = time() - $importPeriod * 86400;
			}
			//Simple

			$request = array(
				"LID" => LANGUAGE_ID,
				"MODIFICATION_LABEL" => $modificationLabelTmp,
				"ZZZ" => date("Z"),
				"IMPORT_SIZE" => $importSize,
				"GZ_COMPRESSION_SUPPORTED" => function_exists("gzcompress") ? 1 : 0,
				"type" => "crm",
				"mode" => "query"
			);

			if($sessid !== "")
			{
				$request["sessid"] = $sessid;
			}

			self::AddTrace(array('QUERY_ORDER_DATA' => $request));

			$orderData = $this->QueryOrderData($request, array("REQUEST_METHOD" => "POST"));
			if ($orderData == null)
			{
				self::AddTrace('FINISH: NO SOURCE DATA');

				$this->AddError("SD2", "Communication error");
				return self::SyncStatusError;
			}

			$arErrors = array();
			$arOrders = $this->ParseOrderData($orderData, $modificationLabel, $arErrors);

			if(is_array($arOrders))
			{
				self::AddTrace('DATA PARSING SUCCESSFUL');
			}
			else
			{
				self::AddTrace('DATA PARSING FAILED');
			}

			foreach ($arErrors as $error)
				$this->AddError($error[0], $error[1]);

			if (is_array($arOrders))
			{
				if (count($arOrders) <= 0)
				{
					$arFieldsTmp = array(
						"~LAST_STATUS_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
						"LAST_STATUS" => "Success: 0 items",
						"SYNC_DATA" => array()
					);
					if (empty($modificationLabel))
						$arFieldsTmp["MODIFICATION_LABEL"] = time();
					CCrmExternalSale::Update($this->externalSaleId, $arFieldsTmp);

					self::AddTrace('FINISH: NO DATA TO SAVE');

					return self::SyncStatusFinished;
				}

				foreach ($arOrders as $order)
					$this->SaveOrderData($order, $bSkipBP);

				if(empty($this->arError))
				{
					$arFieldsTmp = array("MODIFICATION_LABEL" => $modificationLabel, "~LAST_STATUS_DATE" => $GLOBALS["DB"]->CurrentTimeFunction());
					if (count($arOrders) > 0)
						$arFieldsTmp["LAST_STATUS"] = sprintf("Success: %d item(s)", count($arOrders));
					CCrmExternalSale::Update($this->externalSaleId, $arFieldsTmp);

					if (!$bSkipNotify)
					{
						$this->Notify();
					}

					self::AddTrace('FINISH: CONTINUE');

					return self::SyncStatusContinue;
				}
			}

			$ar = array();
			foreach ($this->arError as $err)
				$ar[] = sprintf("[%s] %s", $err[0], $err[1]);
			$this->arExternalSale["ERRORS"] = $this->arExternalSale["ERRORS"] + 1;
			CCrmExternalSale::Update($this->externalSaleId, array("LAST_STATUS" => implode(" ", $ar), "IMPORT_ERRORS" => $this->arExternalSale["ERRORS"], "~LAST_STATUS_DATE" => $GLOBALS["DB"]->CurrentTimeFunction()));

			self::AddTrace('FINISH: ERROR');

			return self::SyncStatusError;
		}
	}

	private function SaveOrderDataContact($arOrder)
	{
		if (!isset($arOrder["CONTRACTOR"]) || !is_array($arOrder["CONTRACTOR"]))
			return false;

		self::AddTrace('SAVE_ORDER_CONTACT_DATA:START');

		$contactId = 0;

		$contactXmlId = $arOrder["CONTRACTOR"]["ID"];
		if (isset($arOrder["CONTRACTOR"]["FIRST_NAME"]) && $arOrder["CONTRACTOR"]["FIRST_NAME"] != "")
			$contactXmlId .= "|".$arOrder["CONTRACTOR"]["FIRST_NAME"];
		if (isset($arOrder["CONTRACTOR"]["LAST_NAME"]) && $arOrder["CONTRACTOR"]["LAST_NAME"] != "")
			$contactXmlId .= "|".$arOrder["CONTRACTOR"]["LAST_NAME"];

		$dbContact = CCrmContact::GetListEx(
			array(),
			array("ORIGINATOR_ID" => $this->externalSaleId, "ORIGIN_ID" => $contactXmlId, "CHECK_PERMISSIONS" => "N")
		);

		if ($arContact = $dbContact->Fetch())
			$contactId = $arContact["ID"];

		$arFields = array(
			'ORIGINATOR_ID' => $this->externalSaleId,
			'ORIGIN_ID' => $contactXmlId,
			'TYPE_ID' => 'CLIENT',
			'OPENED' => 'Y'
		);

		$sources = CCrmStatus::GetStatusList('SOURCE');
		if(isset($sources['WEB']))
		{
			$arFields['SOURCE_ID'] = 'WEB';
		}

		if (isset($arOrder["CONTRACTOR"]["FIRST_NAME"]) && $arOrder["CONTRACTOR"]["FIRST_NAME"] != "")
			$arFields['NAME'] = $arOrder["CONTRACTOR"]["FIRST_NAME"];
		if (isset($arOrder["CONTRACTOR"]["LAST_NAME"]) && $arOrder["CONTRACTOR"]["LAST_NAME"] != "")
			$arFields['LAST_NAME'] = $arOrder["CONTRACTOR"]["LAST_NAME"];
		if (isset($arOrder["CONTRACTOR"]["SECOND_NAME"]) && $arOrder["CONTRACTOR"]["SECOND_NAME"] != "")
			$arFields['SECOND_NAME'] = $arOrder["CONTRACTOR"]["SECOND_NAME"];
		if (isset($arOrder["CONTRACTOR"]["BIRTHDAY"]) && $arOrder["CONTRACTOR"]["BIRTHDAY"] != "")
			$arFields['BIRTHDATE'] = $arOrder["CONTRACTOR"]["BIRTHDAY"];

		if (isset($arOrder["CONTRACTOR"]["FULL_NAME"]) && $arOrder["CONTRACTOR"]["FULL_NAME"] != "")
			$arFields['FULL_NAME'] = $arOrder["CONTRACTOR"]["FULL_NAME"];
		elseif (isset($arOrder["CONTRACTOR"]["NAME"]) && $arOrder["CONTRACTOR"]["NAME"] != "")
			$arFields['FULL_NAME'] = $arOrder["CONTRACTOR"]["NAME"];

		if (is_array($arOrder["CONTRACTOR"]["ADDRESS"]))
		{
			if (isset($arOrder["CONTRACTOR"]["ADDRESS"]["VIEW"]) && $arOrder["CONTRACTOR"]["ADDRESS"]["VIEW"] != '')
			{
				if (!empty($arFields["ADDRESS"]))
					$arFields["ADDRESS"] .= "\n";
				$arFields["ADDRESS"] .= $arOrder["CONTRACTOR"]["ADDRESS"]["VIEW"];
			}
			else
			{
				foreach ($arOrder["CONTRACTOR"]["ADDRESS"] as $key => $val)
				{
					if ($key == "VIEW")
					{
						continue;
					}
					if (!empty($arFields["ADDRESS"]))
					{
						$arFields["ADDRESS"] .= ", ";
					}
					$arFields["ADDRESS"] .= $val;
				}
			}
		}
		if (is_array($arOrder["CONTRACTOR"]["CONTACTS"]))
		{
			$arFields["FM"] = array();
			if ($contactId > 0)
			{
				$dbCrmFieldMulti = CCrmFieldMulti::GetList(array(), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactId, "CHECK_PERMISSIONS" => "N"));
				while ($arCrmFieldMulti = $dbCrmFieldMulti->Fetch())
					$arFields["FM"][$arCrmFieldMulti["TYPE_ID"]][$arCrmFieldMulti["ID"]] = array("VALUE_TYPE" => $arCrmFieldMulti["VALUE_TYPE"], "VALUE" => $arCrmFieldMulti["VALUE"]);
			}

			$arMapTmp = array(
				"MAIL" => "EMAIL", "E-MAIL" => "EMAIL", "WORKPHONE" => "PHONE"
			);
			$arInc = array();
			foreach ($arOrder["CONTRACTOR"]["CONTACTS"] as $val)
			{
				$t = mb_strtoupper(preg_replace("/\s/", "", $val["TYPE"]));
				if (!isset($arMapTmp[$t]))
				{
					continue;
				}

				$bFound = false;
				$tNew = $arMapTmp[$t];
				if (isset($arFields["FM"][$tNew]) && is_array($arFields["FM"][$tNew]))
				{
					if(count($arFields["FM"][$tNew]) >= 50)
					{
						//Disable adding new communication after threshold is exceeded
						$bFound = true;
					}
					else
					{
						foreach ($arFields["FM"][$tNew] as $k => $v)
						{
							if ($v["VALUE"] == $val["VALUE"])
							{
								$bFound = true;
								break;
							}
						}
					}
				}
				if (!$bFound)
				{
					$arInc[$tNew]++;
					$arFields["FM"][$tNew]["n".$arInc[$tNew]] = array("VALUE_TYPE" => "WORK", "VALUE" => $val["VALUE"]);
				}
			}
		}

		$newContact = ($contactId == 0);

		if ($this->arExternalSale == null)
			$this->arExternalSale = CCrmExternalSale::GetDefaultSettings($this->externalSaleId);

		$obj = new CCrmContact(false);
		if ($contactId == 0)
		{
			if (
				(!isset($arFields['NAME']) || ($arFields['NAME'] == ''))
				&& (!isset($arFields['LAST_NAME']) || ($arFields['LAST_NAME'] == ''))
			)
				$arFields['LAST_NAME'] = $contactXmlId;

			$assignedById = $this->arExternalSale["RESPONSIBLE"];
			if ($assignedById > 0)
				$arFields["ASSIGNED_BY_ID"] = $assignedById;

			self::AddTrace(array('ADD CONTACT' => $arFields));

			$res = $obj->Add($arFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
			if($res > 0)
			{
				$contactId = (int)$res;
				$this->arImportResult->numberOfCreatedContacts++;
			}
		}
		else
		{
			self::AddTrace(array("UPDATE CONTACT: {$contactId}" => $arFields));

			$res = $obj->Update($contactId, $arFields, true, true, array('DISABLE_USER_FIELD_CHECK' => true));
			if($res)
			{
				$this->arImportResult->numberOfUpdatedContacts++;
			}
		}

		if (!$res)
		{
			if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
				$this->AddError($ex->GetID(), $ex->GetString());
			else
				$this->AddError("CCA", "Contact creation error");

			if (!empty($obj->LAST_ERROR))
				$this->AddError("CCA", $obj->LAST_ERROR);

			self::AddTrace('SAVE_ORDER_CONTACT_DATA:FAILED');
			return false;
		}

		self::AddTrace("SAVE_ORDER_CONTACT_DATA:FINISHED:{$contactId}");
		return array($contactId, $newContact);
	}

	private function SaveOrderDataCompany($arOrder)
	{
		if (!isset($arOrder["CONTRACTOR"]) || !is_array($arOrder["CONTRACTOR"]))
			return false;

		self::AddTrace('SAVE_ORDER_COMPANY_DATA:START');

		$companyId = 0;

		$companyXmlId = $arOrder["CONTRACTOR"]["ID"];
		if (isset($arOrder["CONTRACTOR"]["INN"]) && $arOrder["CONTRACTOR"]["INN"] != "")
			$companyXmlId .= "|".$arOrder["CONTRACTOR"]["INN"];

		$dbCompany = CCrmCompany::GetList(array(), array("ORIGINATOR_ID" => $this->externalSaleId, "ORIGIN_ID" => $companyXmlId, "CHECK_PERMISSIONS" => "N"));
		if ($arCompany = $dbCompany->Fetch())
			$companyId = $arCompany["ID"];

		$arFields = array(
			'ORIGINATOR_ID' => $this->externalSaleId,
			'ORIGIN_ID' => $companyXmlId,
			'COMPANY_TYPE' => 'CUSTOMER',
		);

		if (isset($arOrder["CONTRACTOR"]["OFFICIAL_NAME"]) && $arOrder["CONTRACTOR"]["OFFICIAL_NAME"] != "")
			$arFields['TITLE'] = $arOrder["CONTRACTOR"]["OFFICIAL_NAME"];
		elseif (isset($arOrder["CONTRACTOR"]["NAME"]) && $arOrder["CONTRACTOR"]["NAME"] != "")
			$arFields['TITLE'] = $arOrder["CONTRACTOR"]["NAME"];

		if (is_array($arOrder["CONTRACTOR"]["ADDRESS"]))
		{
			foreach ($arOrder["CONTRACTOR"]["ADDRESS"] as $key => $val)
			{
				if ($key == "VIEW")
					continue;
				if (!empty($arFields["ADDRESS"]))
					$arFields["ADDRESS"] .= ", ";
				$arFields["ADDRESS"] .= $val;
			}
			if (isset($arOrder["CONTRACTOR"]["ADDRESS"]["VIEW"]))
			{
				if (!empty($arFields["ADDRESS"]))
					$arFields["ADDRESS"] .= "\n";
				$arFields["ADDRESS"] .= $arOrder["CONTRACTOR"]["ADDRESS"]["VIEW"];
			}
		}
		if (is_array($arOrder["CONTRACTOR"]["LEGAL_ADDRESS"]))
		{
			foreach ($arOrder["CONTRACTOR"]["LEGAL_ADDRESS"] as $key => $val)
			{
				if ($key == "VIEW")
					continue;
				if (!empty($arFields["ADDRESS_LEGAL"]))
					$arFields["ADDRESS_LEGAL"] .= ", ";
				$arFields["ADDRESS_LEGAL"] .= $val;
			}
			if (isset($arOrder["CONTRACTOR"]["LEGAL_ADDRESS"]["VIEW"]))
			{
				if (!empty($arFields["ADDRESS_LEGAL"]))
					$arFields["ADDRESS_LEGAL"] .= "\n";
				$arFields["ADDRESS_LEGAL"] .= $arOrder["CONTRACTOR"]["LEGAL_ADDRESS"]["VIEW"];
			}
		}
		if (is_array($arOrder["CONTRACTOR"]["CONTACTS"]))
		{
			$arFields["FM"] = array();
			if ($companyId > 0)
			{
				$dbCrmFieldMulti = CCrmFieldMulti::GetList(array(), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyId, "CHECK_PERMISSIONS" => "N"));
				while ($arCrmFieldMulti = $dbCrmFieldMulti->Fetch())
					$arFields["FM"][$arCrmFieldMulti["TYPE_ID"]][$arCrmFieldMulti["ID"]] = array("VALUE_TYPE" => $arCrmFieldMulti["VALUE_TYPE"], "VALUE" => $arCrmFieldMulti["VALUE"]);
			}

			$arMapTmp = array(
				"MAIL" => "EMAIL", "E-MAIL" => "EMAIL", "WORKPHONE" => "PHONE"
			);
			$arInc = array();
			foreach ($arOrder["CONTRACTOR"]["CONTACTS"] as $val)
			{
				$t = mb_strtoupper(preg_replace("/\s/", "", $val["TYPE"]));
				if (!isset($arMapTmp[$t]))
				{
					continue;
				}

				$bFound = false;
				$tNew = $arMapTmp[$t];
				if (isset($arFields["FM"][$tNew]) && is_array($arFields["FM"][$tNew]))
				{
					if(count($arFields["FM"][$tNew]) >= 50)
					{
						//Disable adding new communication after threshold is exceeded
						$bFound = true;
					}
					else
					{
						foreach ($arFields["FM"][$tNew] as $k => $v)
						{
							if ($v["VALUE"] == $val["VALUE"])
							{
								$bFound = true;
								break;
							}
						}
					}
				}
				if (!$bFound)
				{
					$arInc[$tNew]++;
					$arFields["FM"][$tNew]["n".$arInc[$tNew]] = array("VALUE_TYPE" => "WORK", "VALUE" => $val["VALUE"]);
				}
			}
		}

		$arMapTmp = array("INN", "KPP", "EGRPO", "OKVED", "OKDP", "OKOPF", "OKFC", "OKPO");
		foreach ($arMapTmp as $m)
		{
			if (isset($arOrder["CONTRACTOR"][$m]))
				$arFields["BANKING_DETAILS"] .= $m.": ".$arOrder["CONTRACTOR"][$m]."\n";
		}
		if (is_array($arOrder["CONTRACTOR"]["BANK_ADDRESS"]))
		{
			foreach ($arOrder["CONTRACTOR"]["BANK_ADDRESS"] as $key => $val)
			{
				if (!empty($arFields["BANKING_DETAILS"]))
					$arFields["BANKING_DETAILS"] .= ", ";
				$arFields["BANKING_DETAILS"] .= $val;
			}
		}

		$newCompany = ($companyId == 0);

		if ($this->arExternalSale == null)
			$this->arExternalSale = CCrmExternalSale::GetDefaultSettings($this->externalSaleId);

		$obj = new CCrmCompany(false);
		if ($companyId == 0)
		{
			if (!isset($arFields['TITLE']) || ($arFields['TITLE'] == ''))
				$arFields['TITLE'] = $companyXmlId;

			$assignedById = $this->arExternalSale["RESPONSIBLE"];
			if ($assignedById > 0)
				$arFields["ASSIGNED_BY_ID"] = $assignedById;

			self::AddTrace(array('ADD COMPANY' => $arFields));

			$res = $obj->Add($arFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
			if($res > 0)
			{
				$companyId = (int)$res;
				$this->arImportResult->numberOfCreatedCompanies++;
			}
		}
		else
		{
			self::AddTrace(array("UPDATE COMPANY: {$companyId}" => $arFields));

			$res = $obj->Update($companyId, $arFields, true, true, array('DISABLE_USER_FIELD_CHECK' => true));
			if($res)
			{
				$this->arImportResult->numberOfUpdatedCompanies++;
			}
		}

		if (!$res)
		{
			if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
				$this->AddError($ex->GetID(), $ex->GetString());
			else
				$this->AddError("CCA", "Company creation error");

			if (!empty($obj->LAST_ERROR))
				$this->AddError("CCA", $obj->LAST_ERROR);

			self::AddTrace('SAVE_ORDER_COMPANY_DATA:FAILED');
			return false;
		}

		self::AddTrace("SAVE_ORDER_COMPANY_DATA:FINISHED:{$companyId}");
		return array($companyId, $newCompany);
	}

	private function SaveOrderDataDeal($arOrder, $contactId = null, $companyId = null)
	{
		self::AddTrace('SAVE_ORDER_DEAL_DATA:START');

		$dealId = 0;
		$dealTitle = "";
		$dealCategoryId = 0;

		$dbDeal = CCrmDeal::GetListEx(
			array(),
			array(
				"ORIGINATOR_ID" => $this->externalSaleId,
				"ORIGIN_ID" => $arOrder["ID"],
				"CHECK_PERMISSIONS" => "N"
			)
		);
		if ($arDeal = $dbDeal->Fetch())
		{
			$dealId = (int)$arDeal["ID"];
			if(isset($arDeal["TITLE"]))
			{
				$dealTitle = $arDeal["TITLE"];
			}
			if(isset($arDeal["CATEGORY_ID"]))
			{
				$dealCategoryId = (int)$arDeal["CATEGORY_ID"];
			}
		}

		$newDeal = ($dealId == 0);

		if ($this->arExternalSale == null)
			$this->arExternalSale = CCrmExternalSale::GetDefaultSettings($this->externalSaleId);

		$arFields = array(
			'ORIGINATOR_ID' => $this->externalSaleId,
			'ORIGIN_ID' => $arOrder["ID"],
			'BEGINDATE' => $arOrder["DATE_INSERT"],
			'CURRENCY_ID' => $arOrder["CURRENCY"],
			'EXCH_RATE' => $arOrder["CURRENCY_RATE"],
			'OPPORTUNITY' => $arOrder["PRICE"]
		);

		// Prevent reset comment if order comment is empty
		if(isset($arOrder["COMMENT"]) && $arOrder["COMMENT"] !== "")
		{
			$arFields["COMMENTS"] = $arOrder["COMMENT"];
		}

		if ($contactId != null && intval($contactId) > 0)
			$arFields["CONTACT_ID"] = $contactId;
		if ($companyId != null && intval($companyId) > 0)
			$arFields["COMPANY_ID"] = $companyId;

		// Prevent reset stage for existed deals
		if ($newDeal)
		{
			$arStageList = Bitrix\Crm\Category\DealCategory::getStageList($dealCategoryId);
			if(!empty($arStageList))
			{
				$arFields["STAGE_ID"] = current(array_keys($arStageList));
			}
		}

		$arAdditionalInfo = array();
		if ($contactId != null && intval($contactId) > 0)
		{
			if (isset($arOrder["CONTRACTOR"]["FULL_NAME"]) && $arOrder["CONTRACTOR"]["FULL_NAME"] != "")
				$arAdditionalInfo['CONTACT_FULL_NAME'] = $arOrder["CONTRACTOR"]["FULL_NAME"];
			elseif (isset($arOrder["CONTRACTOR"]["NAME"]) && $arOrder["CONTRACTOR"]["NAME"] != "")
				$arAdditionalInfo['CONTACT_FULL_NAME'] = $arOrder["CONTRACTOR"]["NAME"];
		}
		if ($companyId != null && intval($companyId) > 0)
		{
			if (isset($arOrder["CONTRACTOR"]["OFFICIAL_NAME"]) && $arOrder["CONTRACTOR"]["OFFICIAL_NAME"] != "")
				$arAdditionalInfo['COMPANY_FULL_NAME'] = $arOrder["CONTRACTOR"]["OFFICIAL_NAME"];
			elseif (isset($arOrder["CONTRACTOR"]["NAME"]) && $arOrder["CONTRACTOR"]["NAME"] != "")
				$arAdditionalInfo['COMPANY_FULL_NAME'] = $arOrder["CONTRACTOR"]["NAME"];
		}


		if (is_array($arOrder["PROPERTIES"]))
		{
			foreach ($arOrder["PROPERTIES"] as $arProp)
			{
				if (!empty($arProp["VALUE"]))
				{
					$arAdditionalInfo[mb_strtoupper($arProp["NAME"])] = $arProp["VALUE"];
					if ($arAdditionalInfo[mb_strtoupper($arProp["NAME"])] == "true")
						$arAdditionalInfo[mb_strtoupper($arProp["NAME"])] = true;
					elseif ($arAdditionalInfo[mb_strtoupper($arProp["NAME"])] == "false")
						$arAdditionalInfo[mb_strtoupper($arProp["NAME"])] = false;
				}

				switch(mb_strtoupper($arProp["NAME"]))
				{
					case 'CANCELED':
						if($arProp["VALUE"] == 'true')
						{
							$arFields["STAGE_ID"] = \Bitrix\Crm\Category\DealCategory::prepareStageID(
								$dealCategoryId,
								"LOSE"
							);
							$arFields["PROBABILITY"] = 0;
						}
						break;
					case 'ORDERPAID':
						if($arProp["VALUE"] == 'true')
						{
							$arFields["STAGE_ID"] = \Bitrix\Crm\Category\DealCategory::prepareStageID(
								$dealCategoryId,
								"WON"
							);
							$arFields["PROBABILITY"] = 100;
						}
						break;
				}
			}
		}

		$arFields["ADDITIONAL_INFO"] = serialize($arAdditionalInfo);

		$accountNumber = isset($arOrder["ACCOUNT_NUMBER"]) && $arOrder["ACCOUNT_NUMBER"] !== ''
			? $arOrder["ACCOUNT_NUMBER"] : $arOrder["ID"];

		$assignedById = isset($this->arExternalSale["RESPONSIBLE"]) ? (int)$this->arExternalSale["RESPONSIBLE"] : 0;
		if ($assignedById <= 0)
		{
			$assignedById = 1;
		}

		$obj = new CCrmDeal(false);
		if ($dealId == 0)
		{
			$arFields['TITLE'] = sprintf("%s #%s", $this->arExternalSale["PREFIX"], $accountNumber);
			$arFields['OPENED'] = $this->arExternalSale["PUBLIC"];
			$arFields["TYPE_ID"] = 'SALE';
			$arFields["CLOSEDATE"] = ConvertTimeStamp(time() + CTimeZone::GetOffset() + 86400, "FULL");
			if (!isset($arFields["PROBABILITY"]))
			{
				$arFields["PROBABILITY"] = $this->arExternalSale["PROBABILITY"];
			}
			$arFields["ASSIGNED_BY_ID"] = $assignedById;

			self::AddTrace(array('ADD DEAL' => $arFields));

			$res = $obj->Add(
				$arFields,
				true,
				array(
					'DISABLE_USER_FIELD_CHECK' => true,
					'CURRENT_USER' => $assignedById
				)
			);
			if($res > 0)
			{
				$dealId = (int)$res;
				$this->arImportResult->numberOfCreatedDeals++;
			}
		}
		else
		{
			$defaultDealTitle = isset($this->params['DEFAULT_DEAL_TITLE'])
				? $this->params['DEFAULT_DEAL_TITLE'] : 'Deal';

			if ($dealTitle === '' || $dealTitle === $defaultDealTitle)
			{
				$arFields['TITLE'] = sprintf("%s #%s", $this->arExternalSale["PREFIX"], $accountNumber);
			}

			self::AddTrace(array("UPDATE DEAL: {$dealId}" => $arFields));

			$res = $obj->Update(
				$dealId,
				$arFields,
				true,
				true,
				array(
					'DISABLE_USER_FIELD_CHECK' => true,
					'CURRENT_USER' => $assignedById
				)
			);
			if($res)
			{
				$this->arImportResult->numberOfUpdatedDeals++;
			}
		}

		if (!$res)
		{
			if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
				$this->AddError($ex->GetID(), $ex->GetString());
			else
				$this->AddError("CDA", "Deal creation error");

			if (!empty($obj->LAST_ERROR))
				$this->AddError("CDA", $obj->LAST_ERROR);

			self::AddTrace('SAVE_ORDER_DEAL_DATA:FAILED');
			return false;
		}

		self::AddTrace("SAVE_ORDER_DEAL_DATA:FINISHED:{$dealId}");
		return array($dealId, $newDeal);
	}

	private function SaveOrderDataProducts($arOrder, $dealId)
	{
		if (!isset($arOrder["ITEMS"]) || !is_array($arOrder["ITEMS"]))
			return false;

		self::AddTrace('SAVE_ORDER_PRODUCT_DATA:START');

		if (!$this->catalogId)
		{
			if ($this->arExternalSale == null)
				$this->arExternalSale = CCrmExternalSale::GetDefaultSettings($this->externalSaleId);

			$this->catalogId = CCrmCatalog::GetCatalogId($this->arExternalSale["NAME"], $this->externalSaleId, SITE_ID);
			if (!$this->catalogId)
			{
				if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
					$this->AddError($ex->GetID(), $ex->GetString());
				else
					$this->AddError("CCA", "Catalog creation error");

				self::AddTrace('SAVE_ORDER_PRODUCT_DATA:FAILED');
				return false;
			}
		}

		$arProductRows = array();

		foreach ($arOrder["ITEMS"] as $arItem)
		{
			$productId = 0;
			$dbProduct = CCrmProduct::GetList(array(), array("CATALOG_ID" => $this->catalogId, "ORIGINATOR_ID" => $this->externalSaleId, "ORIGIN_ID" => $arItem["ID"], "CHECK_PERMISSIONS" => "N"), array('ID'), array('nTopCount' => 1));
			if ($arProduct = $dbProduct->Fetch())
				$productId = $arProduct["ID"];

			$arFields = array(
				'NAME' => $arItem["NAME"],
				'ACTIVE' => "Y",
				'CATALOG_ID' => $this->catalogId,
				'PRICE' => $arItem["PRICE"],
				'CURRENCY_ID' => $arOrder["CURRENCY"],
				'ORIGINATOR_ID' => $this->externalSaleId,
				'ORIGIN_ID' => $arItem["ID"],
			);

			if ($productId == 0)
			{
				$res = CCrmProduct::Add($arFields);
				if($res > 0)
				{
					$productId = (int)$res;
				}
			}
			else
			{
				$res = CCrmProduct::Update($productId, $arFields);
			}

			if (!$res)
			{
				if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
					$this->AddError($ex->GetID(), $ex->GetString());
				else
					$this->AddError("CDA", "Product creation error");

				continue;
			}

			$arProductRows[] = array(
				'PRODUCT_ID' => $productId,
				'PRICE' => $arItem["PRICE"],
				'QUANTITY' => $arItem["QUANTITY"],
			);
		}

		if (is_array($arOrder["TAXES"]))
		{
			foreach ($arOrder["TAXES"] as $arItem)
			{
				if (intval($arItem["IN_PRICE"]) > 0)
					continue;

				$productId = 0;
				$dbProduct = CCrmProduct::GetList(array(), array("CATALOG_ID" => $this->catalogId, "ORIGINATOR_ID" => $this->externalSaleId, "ORIGIN_ID" => "tax_".$arItem["NAME"], "CHECK_PERMISSIONS" => "N"), array('ID'), array('nTopCount' => 1));
				if ($arProduct = $dbProduct->Fetch())
					$productId = $arProduct["ID"];

				$arFields = array(
					'NAME' => $arItem["NAME"],
					'ACTIVE' => "Y",
					'CATALOG_ID' => $this->catalogId,
					'PRICE' => $arItem["PRICE"],
					'CURRENCY_ID' => $arOrder["CURRENCY"],
					'ORIGINATOR_ID' => $this->externalSaleId,
					'ORIGIN_ID' => "tax_".$arItem["NAME"],
				);

				if ($productId == 0)
				{
					$res = CCrmProduct::Add($arFields);
					$productId = intval($res);
				}
				else
				{
					$res = CCrmProduct::Update($productId, $arFields);
				}

				if (!$res)
				{
					if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
						$this->AddError($ex->GetID(), $ex->GetString());
					else
						$this->AddError("CDA", "Product creation error");

					continue;
				}

				$arProductRows[] = array(
					'PRODUCT_ID' => $productId,
					'PRICE' => $arItem["PRICE"],
					'QUANTITY' => 1,
				);
			}
		}

		if (is_array($arOrder["DISCOUNTS"]))
		{
			foreach ($arOrder["DISCOUNTS"] as $arItem)
			{
				if (intval($arItem["IN_PRICE"]) > 0)
					continue;

				$productId = 0;
				$dbProduct = CCrmProduct::GetList(array(), array("CATALOG_ID" => $this->catalogId, "ORIGINATOR_ID" => $this->externalSaleId, "ORIGIN_ID" => "discount_".$arItem["NAME"], "CHECK_PERMISSIONS" => "N"), array('ID'), array('nTopCount' => 1));
				if ($arProduct = $dbProduct->Fetch())
					$productId = $arProduct["ID"];

				$arFields = array(
					'NAME' => $arItem["NAME"],
					'ACTIVE' => "Y",
					'CATALOG_ID' => $this->catalogId,
					'PRICE' => $arItem["PRICE"],
					'CURRENCY_ID' => $arOrder["CURRENCY"],
					'ORIGINATOR_ID' => $this->externalSaleId,
					'ORIGIN_ID' => "discount_".$arItem["NAME"],
				);

				if ($productId == 0)
				{
					$res = CCrmProduct::Add($arFields);
					$productId = intval($res);
				}
				else
				{
					$res = CCrmProduct::Update($productId, $arFields);
				}

				if (!$res)
				{
					if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
						$this->AddError($ex->GetID(), $ex->GetString());
					else
						$this->AddError("CDA", "Product creation error");

					continue;
				}

				$arProductRows[] = array(
					'PRODUCT_ID' => $productId,
					'PRICE' => -$arItem["PRICE"],
					'QUANTITY' => 1,
				);
			}
		}

		self::AddTrace(array("SAVE DEAL PRODUCTS: {$dealId}" => $arProductRows));
		CCrmProductRow::SaveRows("D", $dealId, $arProductRows, null, false, false);
		self::AddTrace("SAVE_ORDER_PRODUCT_DATA:FINISHED");
		return true;
	}

	private function SaveOrderDataDealBP($dealId, $isNewDeal, $arParameters = array())
	{
		$dealId = intval($dealId);
		if ($dealId <= 0)
			return;

		self::AddTrace('SAVE_ORDER_DEAL_BP:START');

		static $isBPIncluded = null;
		if ($isBPIncluded === null)
			$isBPIncluded = CModule::IncludeModule("bizproc") && CBPRuntime::isFeatureEnabled();
		if (!$isBPIncluded)
			return;

		static $arBPTemplates = [];

		$autoExecType = $isNewDeal ? CBPDocumentEventType::Create : CBPDocumentEventType::Edit;
		if (!array_key_exists($autoExecType, $arBPTemplates))
		{
			$arBPTemplates[$autoExecType] = CBPWorkflowTemplateLoader::SearchTemplatesByDocumentType(
				array('crm', 'CCrmDocumentDeal', 'DEAL'),
				$autoExecType
			);
		}

		if (is_array($arBPTemplates[$autoExecType]))
		{
			if (!is_array($arParameters))
			{
				$arParameters = array($arParameters);
			}
			if (!array_key_exists("TargetUser", $arParameters))
			{
				$assignedById = intval(COption::GetOptionString("crm", "sale_deal_assigned_by_id", "0"));
				if ($assignedById > 0)
				{
					$arParameters["TargetUser"] = "user_" . $assignedById;
				}
			}

			$runtime = CBPRuntime::GetRuntime();

			foreach ($arBPTemplates[$autoExecType] as $wt)
			{
				try
				{
					$wi = $runtime->CreateWorkflow(
						$wt["ID"],
						array('crm', 'CCrmDocumentDeal', 'DEAL_' . $dealId),
						$arParameters
					);
					$wi->Start();
				}
				catch (Exception $e)
				{
					$this->AddError($e->getCode(), $e->getMessage());
				}
			}
		}
		if($isNewDeal)
		{
			$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $dealId);
			$assignedById = intval(COption::GetOptionString("crm", "sale_deal_assigned_by_id", "0"));
			if ($assignedById > 0)
			{
				$starter->setUserId($assignedById);
			}

			$starter->setContextToImport()->runOnAdd();
		}

		self::AddTrace('SAVE_ORDER_DEAL_BP:FINISED');
	}

	private function SaveOrderDataContactBP($contactId, $isNewContact, $arParameters = array())
	{
		$contactId = intval($contactId);
		if ($contactId <= 0)
			return;

		self::AddTrace('SAVE_ORDER_CONTACT_BP:START');

		static $isBPIncluded = null;
		if ($isBPIncluded === null)
			$isBPIncluded = CModule::IncludeModule("bizproc") && CBPRuntime::isFeatureEnabled();
		if (!$isBPIncluded)
			return;

		static $arBPTemplates = [];

		$autoExecType = $isNewContact ? CBPDocumentEventType::Create : CBPDocumentEventType::Edit;
		if (!array_key_exists($autoExecType, $arBPTemplates))
		{
			$arBPTemplates[$autoExecType] = CBPWorkflowTemplateLoader::SearchTemplatesByDocumentType(
				array('crm', 'CCrmDocumentContact', 'CONTACT'),
				$autoExecType
			);
		}

		if (is_array($arBPTemplates[$autoExecType]))
		{
			if (!is_array($arParameters))
			{
				$arParameters = array($arParameters);
			}
			if (!array_key_exists("TargetUser", $arParameters))
			{
				$assignedById = intval(COption::GetOptionString("crm", "sale_deal_assigned_by_id", "0"));
				if ($assignedById > 0)
				{
					$arParameters["TargetUser"] = "user_" . $assignedById;
				}
			}

			$runtime = CBPRuntime::GetRuntime();

			foreach ($arBPTemplates[$autoExecType] as $wt)
			{
				try
				{
					$wi = $runtime->CreateWorkflow(
						$wt["ID"],
						array('crm', 'CCrmDocumentContact', 'CONTACT_' . $contactId),
						$arParameters
					);
					$wi->Start();
				}
				catch (Exception $e)
				{
					$this->AddError($e->getCode(), $e->getMessage());
				}
			}
		}

		self::AddTrace('SAVE_ORDER_CONTACT_BP:FINISED');
	}

	private function SaveOrderDataCompanyBP($companyId, $isNewCompany, $arParameters = array())
	{
		$companyId = intval($companyId);
		if ($companyId <= 0)
			return;

		self::AddTrace('SAVE_ORDER_COMPANY_BP:START');

		static $isBPIncluded = null;
		if ($isBPIncluded === null)
			$isBPIncluded = CModule::IncludeModule("bizproc") && CBPRuntime::isFeatureEnabled();
		if (!$isBPIncluded)
			return;

		static $arBPTemplates = [];
		$autoExecType = $isNewCompany ? CBPDocumentEventType::Create : CBPDocumentEventType::Edit;
		if (!array_key_exists($autoExecType, $arBPTemplates))
		{
			$arBPTemplates[$autoExecType] = CBPWorkflowTemplateLoader::SearchTemplatesByDocumentType(
				array('crm', 'CCrmDocumentCompany', 'COMPANY'),
				$autoExecType
			);
		}

		if (is_array($arBPTemplates[$autoExecType]))
		{
			if (!is_array($arParameters))
			{
				$arParameters = array($arParameters);
			}
			if (!array_key_exists("TargetUser", $arParameters))
			{
				$assignedById = intval(COption::GetOptionString("crm", "sale_deal_assigned_by_id", "0"));
				if ($assignedById > 0)
				{
					$arParameters["TargetUser"] = "user_" . $assignedById;
				}
			}

			$runtime = CBPRuntime::GetRuntime();

			foreach ($arBPTemplates[$autoExecType] as $wt)
			{
				try
				{
					$wi = $runtime->CreateWorkflow(
						$wt["ID"],
						array('crm', 'CCrmDocumentCompany', 'COMPANY_' . $companyId),
						$arParameters
					);
					$wi->Start();
				}
				catch (Exception $e)
				{
					$this->AddError($e->getCode(), $e->getMessage());
				}
			}
		}

		self::AddTrace('SAVE_ORDER_COMPANY_BP:FINISHED');
	}

	private function SaveOrderData($arOrder, $skipBP = false)
	{
		self::AddTrace(array('SAVE_ORDER_DATA:START' => $arOrder));

		$companyId = 0;
		$contactId = 0;
		if (isset($arOrder["CONTRACTOR"]["OFFICIAL_NAME"]))
		{
			$result = $this->SaveOrderDataCompany($arOrder);
			if (!$result)
				return false;

			[$companyId, $isNewCompany] = $result;
			if (!$skipBP)
				$this->SaveOrderDataCompanyBP($companyId, $isNewCompany);
		}
		else
		{
			$result = $this->SaveOrderDataContact($arOrder);
			if (!$result)
				return false;

			[$contactId, $isNewContact] = $result;
			if (!$skipBP)
				$this->SaveOrderDataContactBP($contactId, $isNewContact);
		}

		$result = $this->SaveOrderDataDeal($arOrder, $contactId, $companyId);
		if (!$result)
			return false;

		[$dealId, $isNewDeal] = $result;

		$this->SaveOrderDataProducts($arOrder, $dealId);

		if (!$skipBP)
			$this->SaveOrderDataDealBP($dealId, $isNewDeal);

		self::AddTrace('SAVE_ORDER_DATA:FINISED');
		return true;
	}

	private function Notify()
	{
		if ($this->arExternalSale == null)
			$this->arExternalSale = CCrmExternalSale::GetDefaultSettings($this->externalSaleId);

		if (intval($this->arExternalSale["GROUP_ID"]) <= 0)
			return true;

		static $isSNIncluded = null;
		if ($isSNIncluded === null)
			$isSNIncluded = CModule::IncludeModule("socialnetwork");
		if (!$isSNIncluded)
			return;

		$ar = array("#NAME#" => $this->arExternalSale["NAME"]);
		foreach ($this->arImportResult->ToArray() as $k => $v)
			$ar["#".mb_strtoupper($k)."#"] = $v;

		$message = str_replace(
			array("#DEAL_URL#", "#CONTACT_URL#", "#COMPANY_URL#"),
			array(
				"/crm/deal/list/?ORIGINATOR_ID=".$this->externalSaleId."&filter=%CD%E0%E9%F2%E8&clear_filter=&by=date_modify&order=desc",
				"/crm/contact/list/?ORIGINATOR_ID=".$this->externalSaleId."&filter=%CD%E0%E9%F2%E8&clear_filter=&by=date_modify&order=desc",
				"/crm/company/list/?ORIGINATOR_ID=".$this->externalSaleId."&filter=%CD%E0%E9%F2%E8&clear_filter=&by=date_modify&order=desc",
			),
			GetMessage("CRM_GCES_NOTIFY_MESSAGE", $ar)
		);

		$arFields = Array(
			"EVENT_ID" => "crm_new_deals",
			"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"TITLE_TEMPLATE" => "SYSTEM MESSAGE",
			"TITLE" => GetMessage("CRM_GCES_NOTIFY_TITLE", array("#NAME#" => $this->arExternalSale["NAME"])),
			"MESSAGE" => $message,
			"TEXT_MESSAGE" => HTMLToTxt($message),
			"MODULE_ID" => "crm_shared",
			"CALLBACK_FUNC" => false,
			"SOURCE_ID" => false,
			"ENABLE_COMMENTS" => "Y",
			"ENTITY_TYPE" => SONET_ENTITY_GROUP,
			"ENTITY_ID" => $this->arExternalSale["GROUP_ID"],
			"URL" => "",
		);

		$logId = CSocNetLog::Add($arFields, false);

		if (intval($logId) > 0)
		{
			$arPerms = array(
				"SG".$this->arExternalSale["GROUP_ID"],
				"SG".$this->arExternalSale["GROUP_ID"]."_A",
				"SG".$this->arExternalSale["GROUP_ID"]."_E",
				"SG".$this->arExternalSale["GROUP_ID"]."_K"
			);

			CSocNetLog::Update($logId, array("TMP_ID" => $logId));
			CSocNetLogRights::Add($logId, $arPerms);
			if (COption::GetOptionString('crm', 'enable_livefeed_merge', 'N') === 'Y')
			{
				CSocNetLog::SendEvent($logId, 'SONET_NEW_EVENT', $logId);
			}

			return $logId;
		}

		if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
			$this->AddError($ex->GetID(), $ex->GetString());
		else
			$this->AddError("CDA", "Notify error");

		return false;
	}

	private function NotifyError()
	{
		if ($this->arExternalSale == null)
			$this->arExternalSale = CCrmExternalSale::GetDefaultSettings($this->externalSaleId);

		if (intval($this->arExternalSale["GROUP_ID"]) <= 0)
			return true;

		static $isSNIncluded = null;
		if ($isSNIncluded === null)
			$isSNIncluded = CModule::IncludeModule("socialnetwork");
		if (!$isSNIncluded)
			return;

		$ar = array(
			"#NAME#" => $this->arExternalSale["NAME"],
			"#URL#" => "/crm/configs/external_sale/",
			"#DATE#" => date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), time()),
		);
		$message = GetMessage("CRM_GCES_NOTIFY_ERROR_MESSAGE", $ar);

		$arFields = array(
			"EVENT_ID" => "crm_10_errors",
			"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"TITLE_TEMPLATE" => "SYSTEM MESSAGE",
			"TITLE" => GetMessage("CRM_GCES_NOTIFY_ERROR_TITLE", array("#NAME#" => $this->arExternalSale["NAME"])),
			"MESSAGE" => $message,
			"TEXT_MESSAGE" => HTMLToTxt($message),
			"MODULE_ID" => "crm_shared",
			"CALLBACK_FUNC" => false,
			"SOURCE_ID" => false,
			"ENABLE_COMMENTS" => "Y",
			"ENTITY_TYPE" => SONET_ENTITY_GROUP,
			"ENTITY_ID" => $this->arExternalSale["GROUP_ID"],
			"URL" => "",
		);

		$logId = CSocNetLog::Add($arFields, false);

		if (intval($logId) > 0)
		{
			$arPerms = array(
				"SG".$this->arExternalSale["GROUP_ID"],
				"SG".$this->arExternalSale["GROUP_ID"]."_A",
				"SG".$this->arExternalSale["GROUP_ID"]."_E",
				"SG".$this->arExternalSale["GROUP_ID"]."_K"
			);

			CSocNetLog::Update($logId, array("TMP_ID" => $logId));
			CSocNetLogRights::Add($logId, $arPerms);
			CSocNetLog::SendEvent($logId, "SONET_NEW_EVENT", $logId);

			return $logId;
		}

		if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
			$this->AddError($ex->GetID(), $ex->GetString());
		else
			$this->AddError("CDA", "Notify error");

		return false;
	}

	public static function SocNetFormatEvent($arFields, $arParams, $bMail = false)
	{
		$arResult = array(
			'EVENT' => $arFields,
			'EVENT_FORMATTED' => array(
				'TITLE' => $arFields["TITLE"],
				'TITLE_24' => $arFields["TITLE"],
				"MESSAGE" => $arFields["~MESSAGE"],
				"SHORT_MESSAGE" => $arFields["~MESSAGE"],
				'IS_IMPORTANT' => false,//($arFields["EVENT_ID"] == "crm_10_errors") ? true : false,
				'STYLE' => 'new-employee'
			),
		);

		$arResult['CREATED_BY']['FORMATTED'] = "CRM";
		$arResult['ENTITY']['FORMATTED']["NAME"] = GetMessage("CRM_EXT_SALE_IM_GROUP")." <a href='".str_replace("#group_id#", $arFields["ENTITY_ID"], $arParams["PATH_TO_GROUP"])."'>".$arFields["GROUP_NAME"]."</a>";
		$arResult['ENTITY']['FORMATTED']["URL"] = "";

		if (
			$arParams["MOBILE"] != "Y" 
			&& $arParams["NEW_TEMPLATE"] != "Y"
		)
			$arResult['EVENT_FORMATTED']['IS_MESSAGE_SHORT'] = CSocNetLog::FormatEvent_IsMessageShort($arFields['MESSAGE']);

		return $arResult;
	}

	public static function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents["crm_new_deals"] = array(
			"ENTITIES" => array(
				SONET_SUBSCRIBE_ENTITY_GROUP => array(
					'TITLE' =>GetMessage('CRM_EXT_SALE_TITLE_SETTINGS'),
				),
			),
			"CLASS_FORMAT" => "CCrmExternalSaleImport",
			"METHOD_FORMAT" => "SocNetFormatEvent",
		);
		$arSocNetLogEvents["crm_10_errors"] = array(
			"ENTITIES" => array(
				SONET_SUBSCRIBE_ENTITY_GROUP => array(
					'TITLE' =>GetMessage('CRM_EXT_SALE_TITLE_ERROR_SETTINGS'),
				),
			),
			"CLASS_FORMAT" => "CCrmExternalSaleImport",
			"METHOD_FORMAT" => "SocNetFormatEvent",
		);
	}

	private function ParseOrderData($orderData, &$modificationLabel, &$arErrors)
	{
		if (empty($orderData))
		{
			$arErrors[] = array("PD1", GetMessage("CRM_EXT_SALE_IMPORT_EMPTY_ANSW"));
			return null;
		}

		if (mb_substr(ltrim($orderData), 0, mb_strlen('<?xml')) != '<?xml')
		{
			$orderDataTmp = @gzuncompress($orderData);
			if (mb_substr(ltrim($orderDataTmp), 0, mb_strlen('<?xml')) != '<?xml')
			{
				if (mb_strpos($orderDataTmp, "You haven't rights for exchange") !== false)
					$arErrors[] = array("PD2", GetMessage("CRM_EXT_SALE_IMPORT_UNKNOWN_ANSW_PERMS"));
				elseif (mb_strpos($orderDataTmp, "failure") !== false)
				{
					$arErrors[] = array("PD2", GetMessage("CRM_EXT_SALE_IMPORT_UNKNOWN_ANSW_F"));
					$arErrors[] = array("PD2", preg_replace("/\s*failure\n/", "", $orderDataTmp));
				}
				elseif (mb_strpos($orderData, "Authorization") !== false || mb_strpos($orderData, "Access denied") !== false)
					$arErrors[] = array("PD2", GetMessage("CRM_EXT_SALE_IMPORT_UNKNOWN_ANSW_PERMS1"));
				else
					$arErrors[] = array("PD2", GetMessage("CRM_EXT_SALE_IMPORT_UNKNOWN_ANSW").mb_substr($orderData, 0, 100));
				return null;
			}
			$orderData = $orderDataTmp;
			unset($orderDataTmp);
		}

		$charset = "";
		if (preg_match("/^<"."\?xml[^>]+?encoding=[\"']([^>\"']+)[\"'][^>]*\?".">/i", $orderData, $matches))
			$charset = trim($matches[1]);
		if (!empty($charset) && (mb_strtoupper($charset) != mb_strtoupper(SITE_CHARSET)))
			$orderData = CharsetConverter::ConvertCharset($orderData, $charset, SITE_CHARSET);

		$objXML = new CDataXML();
		if ($objXML->LoadString($orderData))
		{
			$arOrderData = $objXML->GetArray();
		}
		else
		{
			$arErrors[] = array("XL1", GetMessage("CRM_EXT_SALE_IMPORT_ERROR_XML"));
			return null;
		}

		$arSettings = array();
		foreach ($arOrderData["CommerceInformation"]["@"] as $key => $value)
		{
			$arSettings[$key] = array();

			$ar1 = explode(";", $value);
			foreach ($ar1 as $v1)
			{
				$ar2 = explode("=", $v1);
				if (count($ar2) == 2)
					$arSettings[$key][trim($ar2[0])] = $ar2[1];
			}

			if (count($arSettings[$key]) <= 0)
				$arSettings[$key] = $value;
		}
		if (!isset($arSettings["SumFormat"]["CRD"]))
			$arSettings["SumFormat"]["CRD"] = '.';
		if (!isset($arSettings["QuantityFormat"]["CRD"]))
			$arSettings["QuantityFormat"]["CRD"] = '.';
		if (!isset($arSettings["DateFormat"]["DF"]))
			$arSettings["DateFormat"]["DF"] = 'yyyy-MM-dd';
		$arSettings["DateFormat"]["DF"] = mb_strtoupper($arSettings["DateFormat"]["DF"]);
		if (!isset($arSettings["TimeFormat"]["DF"]))
			$arSettings["TimeFormat"]["DF"] = 'HH:MM:SS';
		$arSettings["TimeFormat"]["DF"] = str_replace("MM", "MI", $arSettings["TimeFormat"]["DF"]);

		$arOrders = array();

		if (is_array($arOrderData["CommerceInformation"]["#"]) && is_array($arOrderData["CommerceInformation"]["#"]["Document"]))
		{
			foreach ($arOrderData["CommerceInformation"]["#"]["Document"] as $arDocument)
			{
				if ($arDocument["#"]["BusinessTransaction"][0]["#"] == "ItemOrder")
				{
					$v = $this->ParseOrderDataOrder($arDocument, $arSettings);
					if (is_array($v))
					{
						$arOrders[] = $v;
						if (isset($v["DATE_UPDATE"]))
						{
							$modificationLabelTmp = MakeTimeStamp($v["DATE_UPDATE"]);
							if ($modificationLabelTmp > $modificationLabel)
								$modificationLabel = $modificationLabelTmp;
						}
					}
				}
			}
		}

		return $arOrders;
	}

	private function QueryOrderData($arFilter, $arOptions = null)
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$requestMethod = isset($arOptions["REQUEST_METHOD"]) && is_string($arOptions["REQUEST_METHOD"])
			? mb_strtoupper($arOptions["REQUEST_METHOD"]) : "";
		if($requestMethod === "")
		{
			$requestMethod = "GET";
		}

		$siteUrl = !empty($_SERVER["HTTP_HOST"])
			? (($GLOBALS["APPLICATION"]->IsHTTPS() ? "https" : "http")."://".$_SERVER["HTTP_HOST"])
			: "";

		if($requestMethod === "GET")
		{
			if ($siteUrl !== "")
			{
				$arFilter["CRM_SITE_URL"] = $siteUrl;
			}

			$request = array(
				"METHOD" => "GET",
				"PATH" => CHTTP::urlAddParams($this->path, $arFilter),
				"HEADERS" => array()
			);
		}
		else
		{
			$request = array(
				"METHOD" => "POST",
				"PATH" => $this->path,
				"HEADERS" => array(),
				"BODY" => array()
			);

			foreach ($arFilter as $key => $val)
			{
				$request["BODY"][$key] = $val;
			}

			if ($siteUrl !== "")
			{
				$request["BODY"]["CRM_SITE_URL"] = $siteUrl;
			}
		}

		$response = $this->proxy->Send($request);
		if (is_array($response) && isset($response["BODY"]))
		{
			return $response["BODY"];
		}

		$errors = array();
		foreach ($this->proxy->GetErrors() as $error)
		{
			$errors[] = sprintf("[%s] %s", $error[0], $error[1]);
		}
		$status = implode(" ", $errors);

		$this->AddError("GD1", $status);
		CCrmExternalSale::Update($this->externalSaleId, array("LAST_STATUS" => $status));
		return null;
	}

	/**
	 * Verifies if the importer is ready to load orders
	 *
	 * @return bool True if the importer is ready to load orders, false otherwise
	 */
	public function IsInitialized()
	{
		return $this->proxy != null;
	}

	/**
	 * Returns array of errors occurred during import
	 *
	 * @return array Array of errors like the following: array(array("Here is error code", "Here is error message"), ...)
	 */
	public function GetErrors()
	{
		return $this->arError;
	}

	/**
	 * @return CCrmExternalSaleImportResult|null
	 */
	public function GetImportResult()
	{
		return $this->arImportResult;
	}

	private function AddError($code, $message)
	{
		$this->arError[] = array($code, $message);
		AddMessage2Log($message, "CRM_EXTERNAL_SALE_IMPORT ERROR: {$code}");
		$this->AddMessage2Log(sprintf("[%s] %s", $code, $message));
	}

	public static function IsTraceEnabled()
	{
		return \Bitrix\Main\Config\Option::get('crm', 'enable_sale_import_trace', 'N') === 'Y';
	}

	public static function EnableTrace($enable)
	{
		\Bitrix\Main\Config\Option::set('crm', 'enable_sale_import_trace', $enable ? 'Y' : 'N');
	}

	protected static function AddTrace($message)
	{
		if(self::IsTraceEnabled())
		{
			AddMessage2Log($message, "CRM_EXTERNAL_SALE_IMPORT", 1);
		}
	}

	private function ClearErrors()
	{
		$this->arError = array();
	}

	private function ParseOrderDataOrder($arDocument, $arSettings)
	{
		$arOrder = array();

		foreach ($arDocument["#"] as $key => $value)
		{
			$value = $value[0]["#"];
			switch ($key)
			{
				case 'Id':
					$arOrder["ID"] = intval($value);
					break;
				case 'Number':
					$arOrder["ACCOUNT_NUMBER"] = $value;
					break;
				case 'Amount':
					$arOrder["PRICE"] = $arDocument["#"]["Amount"][0]["#"];
					$arOrder["PRICE"] = str_replace($arSettings["SumFormat"]["CRD"], ".", $arOrder["PRICE"]);
					break;
				case 'Comment':
					$arOrder["COMMENT"] = $value;
					break;
				case 'DateUpdate':
					$arOrder["DATE_UPDATE"] = ConvertTimeStamp(MakeTimeStamp($value, "YYYY-MM-DD HH:MI:SS"), "FULL");
					break;
				case 'Date':
				case 'Time':
					if (!isset($arOrder["DATE_INSERT"]))
					{
						$str = "";
						$fmt = "";
						if (isset($arDocument["#"]["Date"][0]["#"]))
						{
							$str .= $arDocument["#"]["Date"][0]["#"];
							$fmt .= $arSettings["DateFormat"]["DF"];
						}
						if ($str != "" && isset($arDocument["#"]["Time"][0]["#"]))
						{
							$str .= " ";
							$fmt .= " ";
						}
						if (isset($arDocument["#"]["Time"][0]["#"]))
						{
							$str .= $arDocument["#"]["Time"][0]["#"];
							$fmt .= $arSettings["TimeFormat"]["DF"];
						}
						$arOrder["DATE_INSERT"] = ConvertTimeStamp(MakeTimeStamp($str, $fmt), "FULL");
					}
					break;
				case 'Currency':
					$arOrder["CURRENCY"] = $value;
					break;
				case 'CurrencyRate':
					$arOrder["CURRENCY_RATE"] = $value;
					break;
				case 'Contractors':
					$this->ParseOrderDataOrderContractors($value, $arSettings, $arOrder);
					break;
				case 'Items':
					$this->ParseOrderDataOrderItems($value, $arSettings, $arOrder);
					break;
				case 'PropertiesValues':
					$this->ParseOrderDataOrderPropertiesValues($value, $arSettings, $arOrder);
					break;
				case 'Taxes':
					$this->ParseOrderDataOrderTaxes($value, $arSettings, $arOrder);
					break;
				case 'Discounts':
					$this->ParseOrderDataOrderDiscounts($value, $arSettings, $arOrder);
					break;
				default:
					$arOrder[$key] = $value;
					break;
			}
		}

		return $arOrder;
	}

	private function ParseOrderDataOrderContractors($document, $arSettings, &$arOrder)
	{
		if (!is_array($document["Contractor"]))
			return;

		$arOrder["CONTRACTOR"] = array();
		foreach ($document["Contractor"] as $arContractor)
		{
			$arContractor = $arContractor["#"];

			foreach ($arContractor as $key => $value)
			{
				$value = $value[0]["#"];
				switch ($key)
				{
					case 'Id':
						$arOrder["CONTRACTOR"]["ID"] = $value;
						break;
					case 'ItemName':
						$arOrder["CONTRACTOR"]["NAME"] = $value;
						break;
					case 'FullName':
						$arOrder["CONTRACTOR"]["FULL_NAME"] = $value;
						break;
					case 'LastName':
						$arOrder["CONTRACTOR"]["LAST_NAME"] = $value;
						break;
					case 'FirstName':
						$arOrder["CONTRACTOR"]["FIRST_NAME"] = $value;
						break;
					case 'SecondName':
						$arOrder["CONTRACTOR"]["SECOND_NAME"] = $value;
						break;
					case 'DateOfBirth':
						$arOrder["CONTRACTOR"]["BIRTHDAY"] = $value;
						break;
					case 'Sex':
						$arOrder["CONTRACTOR"]["SEX"] = $value;
						break;
					case 'INN':
						$arOrder["CONTRACTOR"]["INN"] = $value;
						break;
					case 'KPP':
						$arOrder["CONTRACTOR"]["KPP"] = $value;
						break;
					case 'RegistrationAddress':
						$arOrder["CONTRACTOR"]["ADDRESS"] = $this->ParseOrderDataOrderContractorsAddress($value);
						break;
					case 'OfficialName':
						$arOrder["CONTRACTOR"]["OFFICIAL_NAME"] = $value;
						break;
					case 'LegalAddress':
						$arOrder["CONTRACTOR"]["LEGAL_ADDRESS"] = $this->ParseOrderDataOrderContractorsAddress($value);
						break;
					case 'EGRPO':
						$arOrder["CONTRACTOR"]["EGRPO"] = $value;
						break;
					case 'OKVED':
						$arOrder["CONTRACTOR"]["OKVED"] = $value;
						break;
					case 'OKDP':
						$arOrder["CONTRACTOR"]["OKDP"] = $value;
						break;
					case 'OKOPF':
						$arOrder["CONTRACTOR"]["OKOPF"] = $value;
						break;
					case 'OKFC':
						$arOrder["CONTRACTOR"]["OKFC"] = $value;
						break;
					case 'OKPO':
						$arOrder["CONTRACTOR"]["OKPO"] = $value;
						break;
					case 'Accounts':
						$arOrder["CONTRACTOR"]["ACCOUNT"] = $this->ParseOrderDataOrderContractorsAccounts($value);
						break;
					case 'BankAddress':
						$arOrder["CONTRACTOR"]["BANK_ADDRESS"] = $this->ParseOrderDataOrderContractorsAddress($value);
						break;
					case 'Contacts':
						$arOrder["CONTRACTOR"]["CONTACTS"] = $this->ParseOrderDataOrderContractorsContacts($value);
						break;
					case 'Representatives':
						$arOrder["CONTRACTOR"]["REPRESENTATIVES"] = $this->ParseOrderDataOrderContractorsRepresentatives($value);
						break;
					default:
						$arOrder["CONTRACTOR"][$key] = $value;
						break;
				}
			}
		}
	}

	private function ParseOrderDataOrderContractorsRepresentatives($document)
	{
		if (!is_array($document["Representative"]))
			return null;

		$arResult = array();
		foreach ($document["Representative"] as $arRepresentative)
		{
			$arRepresentative = $arRepresentative["#"];
			if (is_array($arRepresentative))
			{
				foreach ($arRepresentative as $arContractor)
				{
					$arResultTmp = array();

					$arContractor = $arContractor["#"];
					if (is_array($arContractor))
					{
						foreach ($arContractor as $key => $value)
						{
							$value = $value[0]["#"];
							switch ($key)
							{
								case 'Relation':
									$arResultTmp["RELATION"] = $value;
									break;
								case 'Id':
									$arResultTmp["ID"] = $value;
									break;
								case 'ItemName':
									$arResultTmp["NAME"] = $value;
									break;
								default:
									$arResultTmp[$key] = $value;
									break;
							}
						}
					}

					$arResult[] = $arResultTmp;
				}
			}
		}

		return $arResult;
	}

	private function ParseOrderDataOrderContractorsContacts($document)
	{
		if (!is_array($document["Contact"]))
			return null;

		$arResult = array();
		foreach ($document["Contact"] as $arContact)
		{
			$arResultTmp = array();

			$arContact = $arContact["#"];
			foreach ($arContact as $key => $value)
			{
				$value = $value[0]["#"];
				switch ($key)
				{
					case 'Type':
						$arResultTmp["TYPE"] = $value;
						break;
					case 'Value':
						$arResultTmp["VALUE"] = $value;
						break;
					default:
						$arResultTmp[$key] = $value;
						break;
				}
			}

			$arResult[] = $arResultTmp;
		}

		return $arResult;
	}

	private function ParseOrderDataOrderContractorsAddress($document)
	{
		$arResult = array();

		if (isset($document["View"]))
			$arResult["VIEW"] = $document["View"][0]["#"];

		if (is_array($document["AddressField"]))
		{
			foreach ($document["AddressField"] as $arAddressField)
			{
				$fieldType = null;
				$fieldValue = null;

				$arAddressField = $arAddressField["#"];
				foreach ($arAddressField as $key => $value)
				{
					$value = $value[0]["#"];
					switch ($key)
					{
						case 'Type':
							$fieldType = $value;
							break;
						case 'Value':
							$fieldValue = $value;
							break;
					}
				}

				if ($fieldType != null)
					$arResult[$fieldType] = $fieldValue;
			}
		}

		return $arResult;
	}

	private function ParseOrderDataOrderContractorsAccounts($document)
	{
		return array();
	}

	private function ParseOrderDataOrderPropertiesValues($document, $arSettings, &$arOrder)
	{
		if (!is_array($document["PropertyValue"]))
			return;

		$arOrder["PROPERTIES"] = array();
		foreach ($document["PropertyValue"] as $arPropertyValue)
		{
			$arPropertyValue = $arPropertyValue["#"];
			$arResultTmp = array();

			foreach ($arPropertyValue as $key => $value)
			{
				$value = $value[0]["#"];
				switch ($key)
				{
					case 'ItemName':
						$arResultTmp["NAME"] = $value;
						break;
					case 'Value':
						$arResultTmp["VALUE"] = $value;
						break;
					default:
						$arResultTmp[$key] = $value;
						break;
				}
			}

			$arOrder["PROPERTIES"][] = $arResultTmp;
		}
	}

	private function ParseOrderDataOrderDiscounts($document, $arSettings, &$arOrder)
	{
		if (!is_array($document["Discount"]))
			return;

		$arOrder["DISCOUNTS"] = array();
		foreach ($document["Discount"] as $arDiscount)
		{
			$arDiscount = $arDiscount["#"];
			$arResultTmp = array();

			foreach ($arDiscount as $key => $value)
			{
				$value = $value[0]["#"];
				switch ($key)
				{
					case 'ItemName':
						$arResultTmp["NAME"] = $value;
						break;
					case 'InPrice':
						$arResultTmp["IN_PRICE"] = (mb_strtolower($value) == 'true') ? true : false;
						break;
					case 'Amount':
						$arResultTmp["PRICE"] = str_replace($arSettings["SumFormat"]["CRD"], ".", $value);
						break;
					default:
						$arResultTmp[$key] = $value;
						break;
				}
			}

			$arOrder["DISCOUNTS"][] = $arResultTmp;
		}
	}

	private function ParseOrderDataOrderItems($document, $arSettings, &$arOrder)
	{
		if(!(is_array($document) && is_array($document["Item"])))
		{
			return;
		}

		$arOrder["ITEMS"] = array();
		foreach ($document["Item"] as $arItem)
		{
			$arItem = $arItem["#"];
			$arResultTmp = array();

			foreach ($arItem as $key => $value)
			{
				$value = $value[0]["#"];
				switch ($key)
				{
					case 'Id':
						$arResultTmp["ID"] = $value;
						break;
					case 'ItemName':
						$arResultTmp["NAME"] = $value;
						break;
					case 'Amount':
					case 'ItemPrice':
						if (!isset($arResultTmp["PRICE"]))
						{
							$priceTotal = (float)str_replace($arSettings["SumFormat"]["CRD"], ".", $arItem["Amount"][0]["#"]);
							$priceUnit = (float)str_replace($arSettings["SumFormat"]["CRD"], ".", $arItem["ItemPrice"][0]["#"]);
							$quantity = (float)str_replace($arSettings["QuantityFormat"]["CRD"], ".", $arItem["Quantity"][0]["#"]);

							$price = $priceTotal;
							if ($quantity > 0)
							{
								$price /= $quantity;
							}

							$discountPrice = 0;
							if ($priceUnit != $price)
							{
								$discountPrice = $priceUnit - $price;
							}

							$arResultTmp["PRICE"] = $price;
							$arResultTmp["DISCOUNT_PRICE"] = $discountPrice;
						}
						break;
					case 'Quantity':
						$arResultTmp["QUANTITY"] = str_replace($arSettings["QuantityFormat"]["CRD"], ".", $value);
						break;
					case 'PropertiesValues':
						if (is_array($value["ItemProperty"]))
						{
							foreach ($value["ItemProperty"] as $v)
								$arResultTmp["PROPERTIES"][$v["#"]["ItemName"][0]["#"]] = $v["#"]["Value"][0]["#"];
						}
						break;
					case 'Taxes':
						$taxValueTmp = $value["Tax"][0]["#"]["TaxValue"][0]["#"];
						$arResultTmp["VAT_RATE"] = $taxValueTmp / 100;
						$arResultTmp["VAT_NAME"] = $value["Tax"][0]["#"]["Name"][0]["#"];
						break;
					default:
						$arResultTmp[$key] = $value;
						break;
				}
			}

			$arOrder["ITEMS"][] = $arResultTmp;
		}
	}

	private function ParseOrderDataOrderTaxes($document, $arSettings, &$arOrder)
	{
		if (!is_array($document["Tax"]))
			return;

		$arOrder["TAXES"] = array();
		foreach ($document["Tax"] as $arTax)
		{
			$arTax = $arTax["#"];
			$arResultTmp = array();

			foreach ($arTax as $key => $value)
			{
				$value = $value[0]["#"];
				switch ($key)
				{
					case 'ItemName':
						$arResultTmp["NAME"] = $value;
						break;
					case 'InPrice':
						$arResultTmp["IN_PRICE"] = (mb_strtolower($value) == 'true') ? true : false;
						break;
					case 'Amount':
						$arResultTmp["PRICE"] = str_replace($arSettings["SumFormat"]["CRD"], ".", $value);
						break;
					default:
						$arResultTmp[$key] = $value;
						break;
				}
			}

			$arOrder["TAXES"][] = $arResultTmp;
		}
	}

	function AddMessage2Log($text)
	{
		if (!defined("CRM_ERROR_LOG") || !CRM_ERROR_LOG)
			return;

		$text = trim($text);
		if (empty($text))
			return;

		$maxLogSize = 10000;
		$readSize = 2048;
		$logFile = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm_import.log";
		$logFileTmp = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm_import1.log";

		$oldAbortStatus = ignore_user_abort(true);

		if (file_exists($logFile))
		{
			$logSize = @filesize($logFile);
			$logSize = intval($logSize);

			if ($logSize > $maxLogSize)
			{
				if (!($fp = @fopen($logFile, "rb")))
				{
					ignore_user_abort($oldAbortStatus);
					return;
				}

				if (!($fp1 = @fopen($logFileTmp, "wb")))
				{
					ignore_user_abort($oldAbortStatus);
					return;
				}

				$iSeekLen = intval($logSize - $maxLogSize / 2.0);
				fseek($fp, $iSeekLen);

				do
				{
					$data = fread($fp, $readSize);
					if ($data == '')
						break;

					@fwrite($fp1, $data);
				}
				while(true);

				@fclose($fp);
				@fclose($fp1);

				@copy($logFileTmp, $logFile);
				@unlink($logFileTmp);
			}
			clearstatcache();
		}

		if ($fp = @fopen($logFile, "ab+"))
		{
			if (flock($fp, LOCK_EX))
			{
				@fwrite($fp, date("Y-m-d H:i:s").": ".$text."\n");
				@fflush($fp);
				@flock($fp, LOCK_UN);
				@fclose($fp);
			}
		}
		ignore_user_abort($oldAbortStatus);
	}

	public static function DataSync($id)
	{
		global $USER;
		if(!(isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser))))
		{
			$USER = new CUser();
		}

		if (\Bitrix\Crm\Restriction\RestrictionManager::getIntegrationShopRestriction()->isExceeded())
		{
			return '';
		}

		$id = intval($id);

		$i = new CCrmExternalSaleImport($id);
		if ($i->IsInitialized())
		{
			if ($i->arExternalSale == null)
				$i->arExternalSale = CCrmExternalSale::GetDefaultSettings($id);

			if ($i->arExternalSale["LABEL"] != "")
				$i->SyncOrderData(false, false);

			if ($i->arExternalSale["ERRORS"] > 10)
			{
				$i->NotifyError();
				return;
			}

			return "CCrmExternalSaleImport::DataSync(".$id.");";
		}
	}
}

class CCrmExternalSaleImportResult
{
	public $numberOfCreatedDeals = 0;
	public $numberOfUpdatedDeals = 0;
	public $numberOfCreatedContacts = 0;
	public $numberOfUpdatedContacts = 0;
	public $numberOfCreatedCompanies = 0;
	public $numberOfUpdatedCompanies = 0;

	public function ToArray()
	{
		return array(
			"CreatedDeals" => $this->numberOfCreatedDeals,
			"UpdatedDeals" => $this->numberOfUpdatedDeals,
			"TotalDeals" => $this->numberOfCreatedDeals + $this->numberOfUpdatedDeals,
			"CreatedContacts" => $this->numberOfCreatedContacts,
			"UpdatedContacts" => $this->numberOfUpdatedContacts,
			"TotalContacts" => $this->numberOfCreatedContacts + $this->numberOfUpdatedContacts,
			"CreatedCompanies" => $this->numberOfCreatedCompanies,
			"UpdatedCompanies" => $this->numberOfUpdatedCompanies,
			"TotalCompanies" => $this->numberOfCreatedCompanies + $this->numberOfUpdatedCompanies,
		);
	}
}
