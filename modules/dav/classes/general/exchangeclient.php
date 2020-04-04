<?
// http://msdn.microsoft.com/en-us/library/aa580675(v=EXCHG.140).aspx

abstract class CDavExchangeClient
{
	private $scheme = "http";
	private $server = null;
	private $port = '80';
	private $userName = null;
	private $userPassword = null;

	private $proxyScheme = null;
	private $proxyServer = null;
	private $proxyPort = null;
	private $proxyUserName = null;
	private $proxyUserPassword = null;
	private $proxyUsed = false;

	private $fp = null;
	private $socketTimeout = 5;
	private $connected = false;
	private $userAgent = 'Bitrix Exchange client';
	private $path = '/ews/exchange.asmx';

	private $encoding = "windows-1251";

	private $arError = array();
	private $debug = false;

	public function __construct($scheme, $server, $port, $userName, $userPassword)
	{
		$this->scheme = ((strtolower($scheme) == "https") ? "https" : "http");
		$this->server = $server;
		$this->port = $port;
		$this->userName = $userName;
		$this->userPassword = $userPassword;

		$this->connected = false;
		$this->debug = false;

		$this->proxyScheme = null;
		$this->proxyServer = null;
		$this->proxyPort = null;
		$this->proxyUserName = null;
		$this->proxyUserPassword = null;
		$this->proxyUsed = false;
	}

	public function Debug()
	{
		$this->debug = true;
	}

	public function SetCurrentEncoding($siteId = null)
	{
		$this->encoding = CDav::GetCharset($siteId);
		if (is_null($this->encoding) || empty($this->encoding))
			$this->encoding = "utf-8";
	}

	public function SetProxy($proxyScheme, $proxyServer, $proxyPort, $proxyUserName, $proxyUserPassword)
	{
		$this->proxyScheme = ((strtolower($proxyScheme) == "https") ? "https" : "http");
		$this->proxyServer = $proxyServer;
		$this->proxyPort = $proxyPort;
		$this->proxyUserName = $proxyUserName;
		$this->proxyUserPassword = $proxyUserPassword;

		$this->proxyUsed = (strlen($this->proxyServer) > 0 && strlen($this->proxyPort) > 0);
	}

	public function GetPath()
	{
		return $this->path;
	}

	public function Connect()
	{
		if ($this->connected)
			return true;

		$requestScheme = $this->scheme;
		$requestServer = $this->server;
		$requestPort = $this->port;
		if ($this->proxyUsed)
		{
			$requestScheme = $this->proxySchemes;
			$requestServer = $this->proxyServer;
			$requestPort = $this->proxyPort;
		}

		switch ($requestScheme)
		{
			case 'https':
				if (!function_exists("openssl_verify"))
				{
					$this->arError[] = array("EC0", "OpenSSL PHP extention required");
					$this->connected = false;
					return false;
				}

				$requestScheme = 'ssl://';
				$requestPort = ($requestPort === null) ? 443 : $requestPort;
				break;

			case 'http':
				$requestScheme = '';
				$requestPort = ($requestPort === null) ? 80 : $requestPort;
				break;

			default:
				$this->arError[] = array("EC1", "Invalid protocol");
				$this->connected = false;
				return false;
		}

		//$this->fp = @fsockopen($requestScheme.$requestServer, $requestPort, $errno, $errstr, $this->socketTimeout);
		$this->fp = @stream_socket_client(sprintf('%s:%s', $requestScheme.$requestServer, $requestPort), $errno, $errstr, $this->socketTimeout, STREAM_CLIENT_CONNECT,stream_context_create(array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false))));

		if (!$this->fp)
		{
			$this->arError[] = array($errno, $errstr);
			$this->connected = false;
			return false;
		}
		else
		{
			socket_set_blocking($this->fp, 1);
			$this->connected = true;
			return true;
		}
	}

	public function Disconnect()
	{
		if (!$this->connected)
			return;

		fclose($this->fp);
		$this->connected = false;
	}

	public function GetErrors()
	{
		return $this->arError;
	}

	public function AddError($code, $message)
	{
		$this->arError[] = array($code, $message);
	}

	public function ClearErrors()
	{
		$this->arError = array();
	}

	protected function ParseError($response)
	{
		$code = $response->GetStatus('code');
		$isErrorCode = false;

		if ($code == 401)
		{
			$this->arError[] = array(401, $response->GetStatus('phrase'));
			$isErrorCode = true;
		}
		elseif ($code == 500)
		{
			try
			{
				$xmlDoc = $response->GetBodyXml();
			}
			catch (Exception $e)
			{
				$this->arError[] = array($e->getCode(), $e->getMessage());
				return;
			}

			$arFault = $xmlDoc->GetPath("/Envelope/Body/Fault");
			foreach ($arFault as $fault)
			{
				$errorCode = "";
				$arResponseCode = $fault->GetPath("/Fault/detail/ResponseCode");
				if (count($arResponseCode) > 0)
				{
					$errorCode = $arResponseCode[0]->GetContent();
				}
				else
				{
					$arFaultCode = $fault->GetPath("/Fault/faultcode");
					if (count($arFaultCode) > 0)
						$errorCode = $arFaultCode[0]->GetContent();
				}

				$errorMessage = "";
				$arMessage = $fault->GetPath("/Fault/detail/Message");
				if (count($arMessage) > 0)
				{
					$errorMessage = $arMessage[0]->GetContent();
				}
				else
				{
					$arFaultString = $fault->GetPath("/Fault/faultstring");
					if (count($arFaultString) > 0)
						$errorMessage = $arFaultString[0]->GetContent();
				}

				$this->arError[] = array($this->Encode($errorCode), $this->Encode($errorMessage));
			}
			$isErrorCode = true;
		}

		return $isErrorCode;
	}

	protected function FormatStandartFieldsArray($key, $value, &$arFields)
	{
		$bProcessed = false;

		switch ($key)
		{
			case "BodyType":
				$bProcessed = true;
				$arFields[$key] = (strtolower($value) == "html" ? "HTML" : "Text");
				break;

			case "Importance":
				$bProcessed = true;
				$ar = array("low" => "Low", "normal" => "Normal", "high" => "High");

				$value = strtolower($value);
				if (array_key_exists($value, $ar))
					$arFields[$key] = $ar[$value];
				else
					$this->arError[] = array("WrongImportance", "Available values for Importance are Low, Normal, High");
				break;

			case "LegacyFreeBusyType":
				$bProcessed = true;
				$ar = array("free" => "Free", "tentative" => "Tentative", "busy" => "Busy", "oof" => "OOF", "nodata" => "NoData");

				$value = strtolower($value);
				if (array_key_exists($value, $ar))
					$arFields[$key] = $ar[$value];
				else
					$this->arError[] = array("WrongLegacyFreeBusyType", "Available values for LegacyFreeBusyType are Free, Tentative, Busy, OOF, NoData");
				break;

			case "Sensitivity":
				$bProcessed = true;
				$ar = array("normal" => "Normal", "personal" => "Personal", "private" => "Private", "confidential" => "Confidential");

				$value = strtolower($value);
				if (array_key_exists($value, $ar))
					$arFields[$key] = $ar[$value];
				else
					$this->arError[] = array("WrongSensitivity", "Available values for Sensitivity are Normal, Personal, Private, Confidential");
				break;

			case "Status":
				$bProcessed = true;
				$ar = array("notstarted" => "NotStarted", "inprogress" => "InProgress", "completed" => "Completed", "waitingonothers" => "WaitingOnOthers", "deferred" => "Deferred");

				$value = strtolower($value);
				if (array_key_exists($value, $ar))
					$arFields[$key] = $ar[$value];
				else
					$this->arError[] = array("WrongStatus", "Available values for Status are NotStarted, InProgress, Completed, WaitingOnOthers, Deferred");
				break;
		}

		return $bProcessed;
	}

	protected function FormatRecurrenceFieldsArray($key, $value, &$arFields)
	{
		$bProcessed = false;

		switch ($key)
		{
			case "RecurringStartDate":
			case "RecurringEndDate":
				$bProcessed = true;
				$arFields[$key] = date("Y-m-d", MakeTimeStamp($value));
				break;
			case "RecurringType":
				$bProcessed = true;
				$ar = array(
					"none" => "NONE",
					"monthly_absolute" => "MONTHLY_ABSOLUTE",
					"monthly_relative" => "MONTHLY_RELATIVE",
					"yearly_absolute" => "YEARLY_ABSOLUTE",
					"yearly_relative" => "YEARLY_RELATIVE",
					"monthly" => "MONTHLY",
					"yearly" => "YEARLY",
					"weekly" => "WEEKLY",
					"daily" => "DAILY"
				);

				if (empty($value))
					$value = "NONE";
				$value = strtolower($value);

				if (array_key_exists($value, $ar))
				{
					if ($ar[$value] != "NONE")
					{
						$arFields[$key] = $ar[$value];
						$arFields["Recurrence"] = true; // Error ErrorSchemaValidation in Exchange 2013
					}
				}
				else
				{
					$this->arError[] = array("WrongRecurringType", "Available values for RecurringType are NONE, MONTHLY_ABSOLUTE, MONTHLY_RELATIVE, YEARLY_ABSOLUTE, YEARLY_RELATIVE, WEEKLY, DAILY");
				}
				break;

			case "RecurringDayOfWeekIndex":
				$bProcessed = true;
				$ar = array("first" => "First", "second" => "Second", "third" => "Third", "fourth" => "Fourth", "last" => "Last", 1 => "First", 2 => "Second", 3 => "Third", 4 => "Fourth");

				$value = strtolower($value);
				if (array_key_exists($value, $ar))
					$arFields[$key] = $ar[$value];
				else
					$this->arError[] = array("WrongRecurringDayOfWeekIndex", "Available values for RecurringDayOfWeekIndex are First, Second, Third, Fourth, Last");
				break;

			case "RecurringMonth":
				$bProcessed = true;
				$ar = array("january" => "January", "february" => "February", "march" => "March", "april" => "April", "may" => "May", "june" => "June", "july" => "July", "august" => "August", "september" => "September", "october" => "October", "november" => "November", "december" => "December", 1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May", 6 => "June", 7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December");

				$value = strtolower($value);
				if (array_key_exists($value, $ar))
					$arFields[$key] = $ar[$value];
				else
					$this->arError[] = array("WrongRecurringMonth", "Available values for RecurringMonth are January, February, March, April, May, June, July, August, September, October, November, December");
				break;

			case "RecurringDaysOfWeek":
				$bProcessed = true;
				$ar = array("sunday" => "Sunday", "monday" => "Monday", "tuesday" => "Tuesday", "wednesday" => "Wednesday", "thursday" => "Thursday", "friday" => "Friday", "saturday" => "Saturday", "day" => "Day", "weekday" => "Weekday", "weekendday" => "WeekendDay", 0 => "Sunday", 1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday", 7 => "Sunday");

				$value = strtolower($value);
				$arValue = explode(",", $value);
				foreach ($arValue as $value1)
				{
					$arValue1 = explode(" ", $value1);
					foreach ($arValue1 as $value2)
					{
						$value2 = trim($value2);
						if (!empty($value2))
						{
							if (array_key_exists($value2, $ar))
							{
								if (isset($arFields[$key]) && strlen($arFields[$key]) > 0)
									$arFields[$key] .= " ";
								$arFields[$key] .= $ar[$value2];
							}
							else
							{
								$this->arError[] = array("WrongRecurringDaysOfWeek", "Available values for RecurringDaysOfWeek are Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Day, Weekday, WeekendDay");
							}
						}
					}
				}
				break;
		}

		return $bProcessed;
	}

	protected function ConvertRecurrenceToArray($recurrence)
	{
		$arResultItem = array();

		$arAbsoluteMonthlyRecurrence = $recurrence->GetPath("/Recurrence/AbsoluteMonthlyRecurrence");
		if (count($arAbsoluteMonthlyRecurrence) > 0)
		{
			$arResultItem["RECURRING_TYPE"] = "MONTHLY_ABSOLUTE";
			$arAbsoluteMonthlyRecurrenceInterval = $recurrence->GetPath("/Recurrence/AbsoluteMonthlyRecurrence/Interval");
			if (count($arAbsoluteMonthlyRecurrenceInterval) > 0)
				$arResultItem["RECURRING_INTERVAL"] = $arAbsoluteMonthlyRecurrenceInterval[0]->GetContent();
			$arAbsoluteMonthlyRecurrenceDayOfMonth = $recurrence->GetPath("/Recurrence/AbsoluteMonthlyRecurrence/DayOfMonth");
			if (count($arAbsoluteMonthlyRecurrenceDayOfMonth) > 0)
				$arResultItem["RECURRING_DAYOFMONTH"] = $arAbsoluteMonthlyRecurrenceDayOfMonth[0]->GetContent();
		}

		$arRelativeMonthlyRecurrence = $recurrence->GetPath("/Recurrence/RelativeMonthlyRecurrence");
		if (count($arRelativeMonthlyRecurrence) > 0)
		{
			$arResultItem["RECURRING_TYPE"] = "MONTHLY_RELATIVE";
			$arRelativeMonthlyRecurrenceInterval = $recurrence->GetPath("/Recurrence/RelativeMonthlyRecurrence/Interval");
			if (count($arRelativeMonthlyRecurrenceInterval) > 0)
				$arResultItem["RECURRING_INTERVAL"] = $arRelativeMonthlyRecurrenceInterval[0]->GetContent();
			$arRelativeMonthlyRecurrenceDaysOfWeek = $recurrence->GetPath("/Recurrence/RelativeMonthlyRecurrence/DaysOfWeek");
			if (count($arRelativeMonthlyRecurrenceDaysOfWeek) > 0)
				$arResultItem["RECURRING_DAYSOFWEEK"] = $arRelativeMonthlyRecurrenceDaysOfWeek[0]->GetContent();
			$arRelativeMonthlyRecurrenceDayOfWeekIndex = $recurrence->GetPath("/Recurrence/RelativeMonthlyRecurrence/DayOfWeekIndex");
			if (count($arRelativeMonthlyRecurrenceDayOfWeekIndex) > 0)
				$arResultItem["RECURRING_DAYOFWEEKINDEX"] = $arRelativeMonthlyRecurrenceDayOfWeekIndex[0]->GetContent();
		}

		$arAbsoluteYearlyRecurrence = $recurrence->GetPath("/Recurrence/AbsoluteYearlyRecurrence");
		if (count($arAbsoluteYearlyRecurrence) > 0)
		{
			$arResultItem["RECURRING_TYPE"] = "YEARLY_ABSOLUTE";
			$arAbsoluteYearlyRecurrenceDayOfMonth = $recurrence->GetPath("/Recurrence/AbsoluteYearlyRecurrence/DayOfMonth");
			if (count($arAbsoluteYearlyRecurrenceDayOfMonth) > 0)
				$arResultItem["RECURRING_DAYOFMONTH"] = $arAbsoluteYearlyRecurrenceDayOfMonth[0]->GetContent();
			$arAbsoluteYearlyRecurrenceMonth = $recurrence->GetPath("/Recurrence/AbsoluteYearlyRecurrence/Month");
			if (count($arAbsoluteYearlyRecurrenceMonth) > 0)
				$arResultItem["RECURRING_MONTH"] = $arAbsoluteYearlyRecurrenceMonth[0]->GetContent();
		}

		$arRelativeYearlyRecurrence = $recurrence->GetPath("/Recurrence/RelativeYearlyRecurrence");
		if (count($arRelativeYearlyRecurrence) > 0)
		{
			$arResultItem["RECURRING_TYPE"] = "YEARLY_RELATIVE";
			$arRelativeYearlyRecurrenceDaysOfWeek = $recurrence->GetPath("/Recurrence/RelativeYearlyRecurrence/DaysOfWeek");
			if (count($arRelativeYearlyRecurrenceDaysOfWeek) > 0)
				$arResultItem["RECURRING_DAYSOFWEEK"] = $arRelativeYearlyRecurrenceDaysOfWeek[0]->GetContent();
			$arRelativeYearlyRecurrenceDayOfWeekIndex = $recurrence->GetPath("/Recurrence/RelativeYearlyRecurrence/DayOfWeekIndex");
			if (count($arRelativeYearlyRecurrenceDayOfWeekIndex) > 0)
				$arResultItem["RECURRING_DAYOFWEEKINDEX"] = $arRelativeYearlyRecurrenceDayOfWeekIndex[0]->GetContent();
			$arRelativeYearlyRecurrenceMonth = $recurrence->GetPath("/Recurrence/RelativeYearlyRecurrence/Month");
			if (count($arRelativeYearlyRecurrenceMonth) > 0)
				$arResultItem["RECURRING_MONTH"] = $arRelativeYearlyRecurrenceMonth[0]->GetContent();
		}

		$arWeeklyRecurrence = $recurrence->GetPath("/Recurrence/WeeklyRecurrence");
		if (count($arWeeklyRecurrence) > 0)
		{
			$arResultItem["RECURRING_TYPE"] = "WEEKLY";
			$arWeeklyRecurrenceInterval = $recurrence->GetPath("/Recurrence/WeeklyRecurrence/Interval");
			if (count($arWeeklyRecurrenceInterval) > 0)
				$arResultItem["RECURRING_INTERVAL"] = $arWeeklyRecurrenceInterval[0]->GetContent();
			$arWeeklyRecurrenceDaysOfWeek = $recurrence->GetPath("/Recurrence/WeeklyRecurrence/DaysOfWeek");
			if (count($arWeeklyRecurrenceDaysOfWeek) > 0)
				$arResultItem["RECURRING_DAYSOFWEEK"] = $arWeeklyRecurrenceDaysOfWeek[0]->GetContent();
		}

		$arDailyRecurrence = $recurrence->GetPath("/Recurrence/DailyRecurrence");
		if (count($arDailyRecurrence) > 0)
		{
			$arResultItem["RECURRING_TYPE"] = "DAILY";
			$arDailyRecurrenceInterval = $recurrence->GetPath("/Recurrence/DailyRecurrence/Interval");
			if (count($arDailyRecurrenceInterval) > 0)
				$arResultItem["RECURRING_INTERVAL"] = $arDailyRecurrenceInterval[0]->GetContent();
		}

		$arNumberedRecurrence = $recurrence->GetPath("/Recurrence/NumberedRecurrence");
		if (count($arNumberedRecurrence) > 0)
		{
			$arNumberedRecurrenceStartDate = $recurrence->GetPath("/Recurrence/NumberedRecurrence/StartDate");
			if (count($arNumberedRecurrenceStartDate) > 0)
				$arResultItem["RECURRING_STARTDATE"] = CDavICalendarTimeZone::GetFormattedServerDate($arNumberedRecurrenceStartDate[0]->GetContent());
			$arNumberedRecurrenceNumberOfOccurrences = $recurrence->GetPath("/Recurrence/NumberedRecurrence/NumberOfOccurrences");
			if (count($arNumberedRecurrenceNumberOfOccurrences) > 0)
				$arResultItem["RECURRING_NUMBEROFOCCURRENCES"] = $arNumberedRecurrenceNumberOfOccurrences[0]->GetContent();
		}

		$arNoEndRecurrence = $recurrence->GetPath("/Recurrence/NoEndRecurrence");
		if (count($arNoEndRecurrence) > 0)
		{
			$arNoEndRecurrenceStartDate = $recurrence->GetPath("/Recurrence/NoEndRecurrence/StartDate");
			if (count($arNoEndRecurrenceStartDate) > 0)
				$arResultItem["RECURRING_STARTDATE"] = CDavICalendarTimeZone::GetFormattedServerDate($arNoEndRecurrenceStartDate[0]->GetContent());
		}

		$arEndDateRecurrence = $recurrence->GetPath("/Recurrence/EndDateRecurrence");
		if (count($arEndDateRecurrence) > 0)
		{
			$arEndDateRecurrenceStartDate = $recurrence->GetPath("/Recurrence/EndDateRecurrence/StartDate");
			if (count($arEndDateRecurrenceStartDate) > 0)
				$arResultItem["RECURRING_STARTDATE"] = CDavICalendarTimeZone::GetFormattedServerDate($arEndDateRecurrenceStartDate[0]->GetContent());
			$arEndDateRecurrenceEndDate = $recurrence->GetPath("/Recurrence/EndDateRecurrence/EndDate");
			if (count($arEndDateRecurrenceEndDate) > 0)
				$arResultItem["RECURRING_ENDDATE"] = CDavICalendarTimeZone::GetFormattedServerDate($arEndDateRecurrenceEndDate[0]->GetContent());
		}

		return $arResultItem;
	}

	public function Encode($text)
	{
		if (is_null($text) || empty($text))
			return $text;
		if ($this->encoding == "utf-8")
			return $text;

		global $APPLICATION;
		return $APPLICATION->ConvertCharset($text, "utf-8", $this->encoding);
	}

	public function Decode($text)
	{
		if (is_null($text) || empty($text))
			return $text;
		if ($this->encoding == "utf-8")
			return $text;

		global $APPLICATION;
		return $APPLICATION->ConvertCharset($text, $this->encoding, "utf-8");
	}

	public function ExecuteOperation($operationName, $operationBody)
	{
		$request = $this->CreateSOAPRequest("POST", $this->GetPath());
		$request->AddHeader("Content-Type", "text/xml; charset=utf-8");
		$request->AddHeader("SOAPAction", "http://schemas.microsoft.com/exchange/services/2006/messages/".$operationName);
		$request->AddHeader("Connection", "Keep-Alive");
		$request->SetBody($operationBody);

		$this->Connect();
		$response = $this->Send($request);
		$this->Disconnect();

		if (is_null($response))
			return null;

		return $response->GetBody();
	}

	public function Send($request)
	{
		if ($this->debug)
		{
			$f = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++.+++", "a");
			fwrite($f, "\n>>>>>>>>>>>>>>>>>> REQUEST >>>>>>>>>>>>>>>>\n".$request->ToString()."\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
			fclose($f);
		}

		$i = 0;
		while (true)
		{
			$i++;
			if ($i > 3)
				break;

			$this->SendRequest($request);
			$response = $this->GetResponse();

			if (!is_null($response))
			{
				if (($location = $response->GetHeader('Location')) && !is_null($location))
				{
					if ($this->proxyUsed)
						$request->SetPath($this->scheme."://".$this->server.((intval($this->port) > 0) ? ":".$this->port : "").$location);
					else
						$request->SetPath($location);

					continue;
				}
				elseif (($statusCode = $response->GetStatus('code')) && (intval($statusCode) == 401))
				{
					$request = $this->Authenticate($request, $response);
					if (is_null($request))
					{
						$this->AddError("SS1", "Unsupported authentication method (supported: basic, digest)");
						return null;
					}
					continue;
				}
			}

			break;
		}

		if ($this->debug)
		{
			$f = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++.+++", "a");
			fwrite($f, "\n>>>>>>>>>>>>>>>>>> RESPONSE >>>>>>>>>>>>>>>>\n");
			if (is_null($response))
				fwrite($f, "NULL");
			else
				fwrite($f, $response->Dump());
			fwrite($f, "\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n\n");
			fclose($f);
		}

		return $response;
	}

	private function Authenticate($request, $response)
	{
		$authenticate = $response->GetHeader('WWW-Authenticate');
		$authenticateProxy = $response->GetHeader('Proxy-Authenticate');
		if (is_null($authenticate) && is_null($authenticateProxy))
			return null;

		if (!is_null($authenticate) && !is_array($authenticate))
			$authenticate = array($authenticate);
		if (!is_null($authenticateProxy) && !is_array($authenticateProxy))
			$authenticateProxy = array($authenticateProxy);

		if (!is_null($authenticate))
		{
			$arAuth = array();
			foreach ($authenticate as $auth)
			{
				$auth = trim($auth);
				$p = strpos($auth, " ");
				if ($p !== false)
					$arAuth[strtolower(substr($auth, 0, $p))] = trim(substr($auth, $p));
				else
					$arAuth[strtolower($auth)] = "";
			}

			if (array_key_exists("digest", $arAuth))
				$request = $this->AuthenticateDigest(CDavExchangeClientResponce::ExtractArray($arAuth["digest"]), $request, $response, "Authorization");
			elseif (array_key_exists("basic", $arAuth))
				$request = $this->AuthenticateBasic(CDavExchangeClientResponce::ExtractArray($arAuth["basic"]), $request, $response, "Authorization");
			else
				return null;
		}

		if (!is_null($authenticateProxy))
		{
			$arAuthProxy = array();
			foreach ($authenticateProxy as $auth)
			{
				$auth = trim($auth);
				$p = strpos($auth, " ");
				if ($p !== false)
					$arAuthProxy[strtolower(substr($auth, 0, $p))] = trim(substr($auth, $p));
				else
					$arAuthProxy[strtolower($auth)] = "";
			}

			if (array_key_exists("digest", $arAuthProxy))
				$request = $this->AuthenticateDigest(CDavExchangeClientResponce::ExtractArray($arAuthProxy["digest"]), $request, $response, "Proxy-Authorization");
			elseif (array_key_exists("basic", $arAuthProxy))
				$request = $this->AuthenticateBasic(CDavExchangeClientResponce::ExtractArray($arAuthProxy["basic"]), $request, $response, "Proxy-Authorization");
			else
				return null;
		}

		return $request;
	}

	private function AuthenticateDigest($arDigestRequest, $request, $response, $verb = "Authorization")
	{
		// qop="auth",algorithm=MD5-sess,nonce="+Upgraded+v1fdcb1e18d2cc7a72322c81c0d8d2a3c332f7908ef0dfcb01aa9fb63930eadf5722dc8f6ce7b82912353531b18360cd62382a6c2433939d3f",charset=utf-8,realm="Digest"

		$cn = md5(uniqid());

		$a1 = md5($this->userName.':'.$arDigestRequest["realm"].':'.$this->userPassword).":".$arDigestRequest["nonce"].":".$cn;
		$a2 = $request->GetMethod().":".$request->GetPath();
		$hash = md5(md5($a1).":".$arDigestRequest["nonce"].":00000001:".$cn.":".$arDigestRequest["qop"].":".md5($a2));

		$request->SetHeader(
			$verb,
			sprintf(
				"Digest username=\"%s\",realm=\"%s\",nonce=\"%s\",uri=\"%s\",cnonce=\"%s\",nc=00000001,algorithm=%s,response=\"%s\",qop=\"%s\",charset=utf-8",
				$this->userName,
				$arDigestRequest["realm"],
				$arDigestRequest["nonce"],
				$request->GetPath(),
				$cn,
				$arDigestRequest["algorithm"],
				$hash,
				$arDigestRequest["qop"]
			)
		);

		return $request;
	}

	private function AuthenticateBasic($arBasicRequest, $request, $response, $verb = "Authorization")
	{
		// realm="test-exch2007"

		$request->SetHeader(
			$verb,
			sprintf(
				"Basic %s",
				base64_encode($this->userName.":".$this->userPassword)
			)
		);

		return $request;
	}

	private function SendRequest($request)
	{
		if (!$this->connected)
			$this->Connect();

		if (!$this->connected)
			return null;

		fputs($this->fp, $request->ToString());
	}

	private function GetResponse()
	{
		if (!$this->connected)
			return null;

		$arHeaders = array();
		$body = "";

		while ($line = fgets($this->fp, 4096))
		{
			if ($line == "\r\n")
				break;

			$arHeaders[] = trim($line);
		}

		if (count($arHeaders) <= 0)
			return null;

		$bChunked = $bConnectionClosed = false;
		$contentLength = null;
		foreach ($arHeaders as $value)
		{
			if (!$bChunked && preg_match("#Transfer-Encoding:\s*chunked#i", $value))
				$bChunked = true;
			if (!$bConnectionClosed && preg_match('#Connection:\s*close#i', $value))
				$bConnectionClosed = true;
			if (is_null($contentLength))
			{
				if (preg_match('#Content-Length:\s*([0-9]*)#i', $value, $arMatches))
					$contentLength = intval($arMatches[1]);
				if (preg_match('#HTTP/1\.1\s+204#i', $value))
					$contentLength = 0;
			}
		}

		if ($bChunked)
		{
			do
			{
				$line = fgets($this->fp, 4096);
				$line = strtolower($line);

				$chunkSize = "";
				$i = 0;
				while ($i < strlen($line))
				{
					$c = substr($line, $i, 1);
					if (in_array($c, array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f")))
						$chunkSize .= $c;
					else
						break;
					$i++;
				}

				$chunkSize = hexdec($chunkSize);

				if ($chunkSize > 0)
				{
					$lb = $chunkSize;
					$body1 = '';
					while ($lb > 0)
					{
						$d = fread($this->fp, $lb);
						if ($d === false)
							break;

						$body1 .= $d;
						$lb = $chunkSize - ((function_exists('mb_strlen') ? mb_strlen($body1, 'latin1') : strlen($body1)));
					}

					$body .= $body1;
				}

				fgets($this->fp, 4096);
			}
			while ($chunkSize);
		}
		elseif ($contentLength === 0)
		{
		}
		elseif ($contentLength > 0)
		{
			$lb = $contentLength;
			while ($lb > 0)
			{
				$d = fread($this->fp, $lb);
				if ($d === false)
					break;

				$body .= $d;
				$lb = $contentLength - ((function_exists('mb_strlen') ? mb_strlen($body, 'latin1') : strlen($body)));
			}
		}
		else
		{
			socket_set_timeout($this->fp, 0);

			while (!feof($this->fp))
			{
				$d = fread($this->fp, 4096);
				if ($d === false)
					break;

				$body .= $d;
				if (substr($body, -9) == "\r\n\r\n0\r\n\r\n")
				{
					$body = substr($body, 0, -9);
					break;
				}
			}

			socket_set_timeout($this->fp, $this->socketTimeout);
		}

		if ($bConnectionClosed)
			$this->Disconnect();

		$responce = new CDavExchangeClientResponce($arHeaders, $body);

		$httpVersion = $responce->GetStatus('version');
		if (is_null($httpVersion) || ($httpVersion != 'HTTP/1.1' && $httpVersion != 'HTTP/1.0'))
			return null;

		return $responce;
	}

	protected function CreateSOAPRequest($method, $path)
	{
		$request = new CDavExchangeClientRequest($this);

		$request->SetMethod($method);
		if ($this->proxyUsed)
			$request->SetPath($this->scheme."://".$this->server.((intval($this->port) > 0) ? ":".$this->port : "").$path);
		else
			$request->SetPath($path);

		$request->AddHeader('Host', $this->server);
		$request->AddHeader('User-Agent', $this->userAgent);

		return $request;
	}

	public static function NormalizeArray(&$arData, $arMap)
	{
		$arKeys = array_keys($arData);
		foreach ($arKeys as $key)
		{
			$keyLower = strtolower($key);
			if (array_key_exists($keyLower, $arMap))
			{
				$value = $arData[$key];
				unset($arData[$key]);
				$arData[$arMap[$keyLower]] = $value;
			}
		}
	}

	public static function InitUserEntityLoadMessages($key, $defaultMessage = "")
	{
		$arResult = array();

		$dbLang = CLanguage::GetList($b = "", $o = "", array());
		while ($arLang = $dbLang->Fetch())
		{
			$MESS = array();

			$lid = preg_replace("/[^a-z0-9]/i", "", $arLang["LID"]);
			$fn = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/lang/".$lid."/options.php";
			$fnDef = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dav/lang/en/options.php";
			if (file_exists($fn))
				include($fn);
			elseif (file_exists($fnDef))
				include($fnDef);

			if (isset($MESS["DAV_".$key]))
				$arResult[$arLang["LID"]] = $MESS["DAV_".$key];
			else
				$arResult[$arLang["LID"]] = $defaultMessage;
		}

		return $arResult;
	}

	public static function Test($scheme, $server, $port, $userName, $userPassword, $mailbox, $arPFolders = array(), $bDebug = false)
	{
		$arAvailableFolders = array("calendar", "contacts", "tasks");

		if (!is_array($arPFolders))
			$arPFolders = array($arPFolders);

		$arFolders = array();
		foreach ($arPFolders as $value)
		{
			if (in_array($value, $arAvailableFolders))
				$arFolders[] = $value;
		}

		if (count($arFolders) <= 0)
			$arFolders = $arAvailableFolders;

		$arMessages = array();

		if (in_array("calendar", $arFolders))
		{
			$e = new CDavExchangeCalendar($scheme, $server, $port, $userName, $userPassword);
			if ($bDebug)
				$e->Debug();

			$calendarId = null;
			$r = $e->AddCalendar(array("NAME" => "TestCalendarFolderName4", "MAILBOX" => $mailbox));
			$arMessages = array_merge($arMessages, $e->GetErrors());
			foreach ($r as $v)
				$calendarId = $v;

			if (is_null($calendarId))
			{
				$arMessages[] = array("ERROR", "Calendar folder creation error.");
			}
			else
			{
				$arMessages[] = array("SUCCESS", "Calendar folder was successfully created (Id = ".$calendarId["XML_ID"].", ChangeKey = ".$calendarId["MODIFICATION_LABEL"].").");

				$r = $e->UpdateCalendar($calendarId, array("NAME" => "New TestCalendarFolderName4"));
				$arMessages = array_merge($arMessages, $e->GetErrors());
				$calendarId = null;
				foreach ($r as $v)
					$calendarId = $v;

				if (is_null($calendarId))
				{
					$arMessages[] = array("ERROR", "Calendar folder modification error.");
				}
				else
				{
					$arMessages[] = array("SUCCESS", "Calendar folder was successfully updated (Id = ".$calendarId["XML_ID"].", ChangeKey = ".$calendarId["MODIFICATION_LABEL"].").");

					$bFound = false;
					$r = $e->GetCalendarsList(array("MAILBOX" => $mailbox));
					$arMessages = array_merge($arMessages, $e->GetErrors());
					foreach ($r as $v)
					{
						if (($v["NAME"] == "New TestCalendarFolderName4") && ($v["XML_ID"] == $calendarId["XML_ID"]))
							$bFound = true;
					}

					if (!$bFound)
					{
						$arMessages[] = array("ERROR", "Calendar folder is not found by list.");
					}
					else
					{
						$arMessages[] = array("SUCCESS", "Calendar folder was successfully found by list.");

						$bFound = false;
						$r = $e->GetCalendarById($calendarId["XML_ID"]);
						$arMessages = array_merge($arMessages, $e->GetErrors());
						foreach ($r as $v)
						{
							if (($v["NAME"] == "New TestCalendarFolderName4") && ($v["XML_ID"] == $calendarId["XML_ID"]))
								$bFound = true;
						}

						if (!$bFound)
						{
							$arMessages[] = array("ERROR", "Calendar folder is not found by id.");
						}
						else
						{
							$arMessages[] = array("SUCCESS", "Calendar folder was successfully found by id.");

							$itemId = null;
							$arFields = array(
								"MAILBOX" => $mailbox,
								"CALENDAR_ID" => $calendarId["XML_ID"],
								"NAME" => "TestCalendarItem Name",
								"DETAIL_TEXT" => "TestCalendarItem detail text",
								"DETAIL_TEXT_TYPE" => "html",
								"PROPERTY_IMPORTANCE" => "normal",
								"PROPERTY_REMIND_SETTINGS" => "20_min",
								"ACTIVE_FROM" => "16.10.2011 09:00:00",
								"ACTIVE_TO" => "16.10.2011 09:30:00",
								"PROPERTY_LOCATION" => "Some location",
							);
							$r = $e->Add($arFields);
							$arMessages = array_merge($arMessages, $e->GetErrors());
							foreach ($r as $v)
								$itemId = $v;

							if (is_null($itemId))
							{
								$arMessages[] = array("ERROR", "Calendar item creation error.");
							}
							else
							{
								$arMessages[] = array("SUCCESS", "Calendar item was successfully created (Id = ".$itemId["XML_ID"].", ChangeKey = ".$itemId["MODIFICATION_LABEL"].").");

								$arFields = array(
									"NAME" => "New TestCalendarItem Name",
									"DETAIL_TEXT" => "TestCalendarItem detail text",
									"DETAIL_TEXT_TYPE" => "text",
									"ACTIVE_FROM" => "17.10.2011 09:00:00",
									"ACTIVE_TO" => "17.10.2011 09:30:00",
									"PROPERTY_LOCATION" => "Some new location",
								);
								$r = $e->Update($itemId, $arFields);
								$itemId = null;
								$arMessages = array_merge($arMessages, $e->GetErrors());
								foreach ($r as $v)
									$itemId = $v;

								if (is_null($itemId))
								{
									$arMessages[] = array("ERROR", "Calendar item modification error.");
								}
								else
								{
									$arMessages[] = array("SUCCESS", "Calendar item was successfully updated (Id = ".$itemId["XML_ID"].", ChangeKey = ".$itemId["MODIFICATION_LABEL"].").");

									$bFound = false;
									$r = $e->GetList(
										array("Mailbox" => $mailbox, "CalendarId" => $calendarId["XML_ID"]),
										array("ItemShape" => "AllProperties")
									);
									$arMessages = array_merge($arMessages, $e->GetErrors());
									foreach ($r as $v)
									{
										if (($v["NAME"] == "New TestCalendarItem Name") && ($v["XML_ID"] == $itemId["XML_ID"]))
											$bFound = true;
									}

									if (!$bFound)
									{
										$arMessages[] = array("ERROR", "Calendar item is not found by list.");
									}
									else
									{
										$arMessages[] = array("SUCCESS", "Calendar item was successfully found by list.");

										$bFound = false;
										$r = $e->GetById($itemId["XML_ID"]);
										$arMessages = array_merge($arMessages, $e->GetErrors());
										foreach ($r as $v)
										{
											if (($v["NAME"] == "New TestCalendarItem Name") && ($v["XML_ID"] == $itemId["XML_ID"]))
												$bFound = true;
										}

										if (!$bFound)
										{
											$arMessages[] = array("ERROR", "Calendar item is not found by id.");
										}
										else
										{
											$arMessages[] = array("SUCCESS", "Calendar item was successfully found by id.");

											$r = $e->Delete($itemId["XML_ID"]);
											if (!$r)
											{
												$arMessages[] = array("ERROR", "Calendar item deletion error.");
											}
											else
											{
												$arMessages[] = array("SUCCESS", "Calendar item was successfully deleted.");

												$bFound = false;
												$r = $e->GetList(
													array("Mailbox" => $mailbox, "CalendarId" => $calendarId["XML_ID"]),
													array("ItemShape" => "IdOnly")
												);
												$arMessages = array_merge($arMessages, $e->GetErrors());
												foreach ($r as $v)
													$bFound = true;

												if ($bFound)
												{
													$arMessages[] = array("ERROR", "Calendar folder should be empty.");
												}
												else
												{
													$arMessages[] = array("SUCCESS", "Calendar folder is empty.");

													$r = $e->DeleteCalendar($calendarId["XML_ID"]);
													$arMessages = array_merge($arMessages, $e->GetErrors());
													if ($r)
														$arMessages[] = array("SUCCESS", "Calendar folder was successfully deleted.");
													else
														$arMessages[] = array("ERROR", "Calendar folder deletion error.");
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if (in_array("contacts", $arFolders))
		{
			$e = new CDavExchangeContacts($scheme, $server, $port, $userName, $userPassword);
			if ($bDebug)
				$e->Debug();

			$addressbookId = null;
			$r = $e->AddAddressbook(array("NAME" => "TestAddressbookFolderName4", "MAILBOX" => $mailbox));
			$arMessages = array_merge($arMessages, $e->GetErrors());
			foreach ($r as $v)
				$addressbookId = $v;

			if (is_null($addressbookId))
			{
				$arMessages[] = array("ERROR", "Contacts folder creation error.");
			}
			else
			{
				$arMessages[] = array("SUCCESS", "Contacts folder was successfully created (Id = ".$addressbookId["XML_ID"].", ChangeKey = ".$addressbookId["MODIFICATION_LABEL"].").");

				$r = $e->UpdateAddressbook($addressbookId, array("NAME" => "New TestAddressbookFolderName4"));
				$arMessages = array_merge($arMessages, $e->GetErrors());
				$addressbookId = null;
				foreach ($r as $v)
					$addressbookId = $v;

				if (is_null($addressbookId))
				{
					$arMessages[] = array("ERROR", "Contacts folder modification error.");
				}
				else
				{
					$arMessages[] = array("SUCCESS", "Contacts folder was successfully updated (Id = ".$addressbookId["XML_ID"].", ChangeKey = ".$addressbookId["MODIFICATION_LABEL"].").");

					$bFound = false;
					$r = $e->GetAddressbooksList(array("MAILBOX" => $mailbox));
					$arMessages = array_merge($arMessages, $e->GetErrors());
					foreach ($r as $v)
					{
						if (($v["NAME"] == "New TestAddressbookFolderName4") && ($v["XML_ID"] == $addressbookId["XML_ID"]))
							$bFound = true;
					}

					if (!$bFound)
					{
						$arMessages[] = array("ERROR", "Contacts folder is not found by list.");
					}
					else
					{
						$arMessages[] = array("SUCCESS", "Contacts folder was successfully found by list.");

						$bFound = false;
						$r = $e->GetAddressbookById($addressbookId["XML_ID"]);
						$arMessages = array_merge($arMessages, $e->GetErrors());
						foreach ($r as $v)
						{
							if (($v["NAME"] == "New TestAddressbookFolderName4") && ($v["XML_ID"] == $addressbookId["XML_ID"]))
								$bFound = true;
						}

						if (!$bFound)
						{
							$arMessages[] = array("ERROR", "Contacts folder is not found by id.");
						}
						else
						{
							$arMessages[] = array("SUCCESS", "Contacts folder was successfully found by id.");

							$itemId = null;
							$arFields = array(
								"MAILBOX" => $mailbox,
								"ADDRESSBOOK_ID" => $addressbookId["XML_ID"],
								"NAME" => "TestAddressbookItem Name",
								"LAST_NAME" => "MyLastName",
								"SECOND_NAME" => "MySecondName",
								"EMAIL" => "vas2@sfbdsgdf.df",
								"WORK_POSITION" => "Programmer",
								"WORK_ZIP" => "236001",
								"WORK_CITY" => "Kaliningrad",
								"WORK_STREET" => "Kirov str., 261",
								"PERSONAL_PHONE" => "6547646546",
								"PERSONAL_MOBILE" => "55435656",
								"WORK_PHONE" => "876467343",
								"WORK_FAX" => "345737365",
								"WORK_COMPANY" => "Bitrix",
								"WORK_WWW" => "http://www.1c-bitrix.com",
								"PERSONAL_ICQ" => "535435353",
								"WORK_COUNTRY" => 23,
							);
							$r = $e->Add($arFields);
							$arMessages = array_merge($arMessages, $e->GetErrors());
							foreach ($r as $v)
								$itemId = $v;

							if (is_null($itemId))
							{
								$arMessages[] = array("ERROR", "Contacts item creation error.");
							}
							else
							{
								$arMessages[] = array("SUCCESS", "Contacts item was successfully created (Id = ".$itemId["XML_ID"].", ChangeKey = ".$itemId["MODIFICATION_LABEL"].").");

								$arFields = array(
									"NAME" => "New TestAddressbookItem Name",
									"LAST_NAME" => "My new LastName",
									"SECOND_NAME" => "MySecondName",
									"EMAIL" => "vas2@sfbdsgdf.df",
									"WORK_POSITION" => "Programmer",
									"WORK_ZIP" => "236001",
									"WORK_CITY" => "Kaliningrad",
									"WORK_STREET" => "Kirov str., 261",
									"PERSONAL_PHONE" => "6547646546",
									"PERSONAL_MOBILE" => "55435656",
									"WORK_PHONE" => "876467343",
									"WORK_FAX" => "345737365",
									"WORK_COMPANY" => "Bitrix",
									"WORK_WWW" => "http://www.1c-bitrix.com",
									"PERSONAL_ICQ" => "535435353",
									"WORK_COUNTRY" => 23,
								);
								$r = $e->Update($itemId, $arFields);
								$itemId = null;
								$arMessages = array_merge($arMessages, $e->GetErrors());
								foreach ($r as $v)
									$itemId = $v;

								if (is_null($itemId))
								{
									$arMessages[] = array("ERROR", "Contacts item modification error.");
								}
								else
								{
									$arMessages[] = array("SUCCESS", "Contacts item was successfully updated (Id = ".$itemId["XML_ID"].", ChangeKey = ".$itemId["MODIFICATION_LABEL"].").");

									$bFound = false;
									$r = $e->GetList(
										array("Mailbox" => $mailbox, "AddressbookId" => $addressbookId["XML_ID"]),
										array("ItemShape" => "AllProperties")
									);
									$arMessages = array_merge($arMessages, $e->GetErrors());
									foreach ($r as $v)
									{
										if (($v["NAME"] == "New TestAddressbookItem Name") && ($v["XML_ID"] == $itemId["XML_ID"]))
											$bFound = true;
									}

									if (!$bFound)
									{
										$arMessages[] = array("ERROR", "Contacts item is not found by list.");
									}
									else
									{
										$arMessages[] = array("SUCCESS", "Contacts item was successfully found by list.");

										$bFound = false;
										$r = $e->GetById($itemId["XML_ID"]);
										$arMessages = array_merge($arMessages, $e->GetErrors());
										foreach ($r as $v)
										{
											if (($v["NAME"] == "New TestAddressbookItem Name") && ($v["XML_ID"] == $itemId["XML_ID"]))
												$bFound = true;
										}

										if (!$bFound)
										{
											$arMessages[] = array("ERROR", "Contacts item is not found by id.");
										}
										else
										{
											$arMessages[] = array("SUCCESS", "Contacts item was successfully found by id.");

											$r = $e->Delete($itemId["XML_ID"]);
											if (!$r)
											{
												$arMessages[] = array("ERROR", "Contacts item deletion error.");
											}
											else
											{
												$arMessages[] = array("SUCCESS", "Contacts item was successfully deleted.");

												$bFound = false;
												$r = $e->GetList(
													array("Mailbox" => $mailbox, "AddressbookId" => $addressbookId["XML_ID"]),
													array("ItemShape" => "IdOnly")
												);
												$arMessages = array_merge($arMessages, $e->GetErrors());
												foreach ($r as $v)
													$bFound = true;

												if ($bFound)
												{
													$arMessages[] = array("ERROR", "Contacts folder should be empty.");
												}
												else
												{
													$arMessages[] = array("SUCCESS", "Contacts folder is empty.");

													$r = $e->DeleteAddressbook($addressbookId["XML_ID"]);
													$arMessages = array_merge($arMessages, $e->GetErrors());
													if ($r)
														$arMessages[] = array("SUCCESS", "Contacts folder was successfully deleted.");
													else
														$arMessages[] = array("ERROR", "Contacts folder deletion error.");
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if (in_array("tasks", $arFolders))
		{
			$e = new CDavExchangeTasks($scheme, $server, $port, $userName, $userPassword);
			if ($bDebug)
				$e->Debug();

			$folderId = null;
			$r = $e->AddFolder(array("NAME" => "TestFolderFolderName6", "MAILBOX" => $mailbox));
			$arMessages = array_merge($arMessages, $e->GetErrors());
			foreach ($r as $v)
				$folderId = $v;

			if (is_null($folderId))
			{
				$arMessages[] = array("ERROR", "Tasks folder creation error.");
			}
			else
			{
				$arMessages[] = array("SUCCESS", "Tasks folder was successfully created (Id = ".$folderId["XML_ID"].", ChangeKey = ".$folderId["MODIFICATION_LABEL"].").");

				$r = $e->UpdateFolder($folderId, array("NAME" => "New TestFolderFolderName6"));
				$arMessages = array_merge($arMessages, $e->GetErrors());
				$folderId = null;
				foreach ($r as $v)
					$folderId = $v;

				if (is_null($folderId))
				{
					$arMessages[] = array("ERROR", "Tasks folder modification error.");
				}
				else
				{
					$arMessages[] = array("SUCCESS", "Tasks folder was successfully updated (Id = ".$folderId["XML_ID"].", ChangeKey = ".$folderId["MODIFICATION_LABEL"].").");

					$bFound = false;
					$r = $e->GetFoldersList(array("MAILBOX" => $mailbox));
					$arMessages = array_merge($arMessages, $e->GetErrors());
					foreach ($r as $v)
					{
						if (($v["NAME"] == "New TestFolderFolderName6") && ($v["XML_ID"] == $folderId["XML_ID"]))
							$bFound = true;
					}

					if (!$bFound)
					{
						$arMessages[] = array("ERROR", "Tasks folder is not found by list.");
					}
					else
					{
						$arMessages[] = array("SUCCESS", "Tasks folder was successfully found by list.");

						$bFound = false;
						$r = $e->GetFolderById($folderId["XML_ID"]);
						$arMessages = array_merge($arMessages, $e->GetErrors());
						foreach ($r as $v)
						{
							if (($v["NAME"] == "New TestFolderFolderName6") && ($v["XML_ID"] == $folderId["XML_ID"]))
								$bFound = true;
						}

						if (!$bFound)
						{
							$arMessages[] = array("ERROR", "Tasks folder is not found by id.");
						}
						else
						{
							$arMessages[] = array("SUCCESS", "Tasks folder was successfully found by id.");

							$itemId = null;
							$arFields = array(
								"MAILBOX" => $mailbox,
								"FOLDER_ID" => $folderId["XML_ID"],
								"SUBJECT" => "TestFolderItem Name",
								"BODY" => "Should be done!",
								"BODY_TYPE" => "text",
								"IMPORTANCE" => "High",
								"START_DATE" => "20.10.2011",
								"DUE_DATE" => "25.10.2011",
								"PERCENT_COMPLETE" => "0",
								"STATUS" => "NotStarted",
								"TOTAL_WORK" => "123",
								"REMINDER_MINUTES_BEFORE_START" => 365,
							);
							$r = $e->Add($arFields);
							$arMessages = array_merge($arMessages, $e->GetErrors());
							foreach ($r as $v)
								$itemId = $v;

							if (is_null($itemId))
							{
								$arMessages[] = array("ERROR", "Tasks item creation error.");
							}
							else
							{
								$arMessages[] = array("SUCCESS", "Tasks item was successfully created (Id = ".$itemId["XML_ID"].", ChangeKey = ".$itemId["MODIFICATION_LABEL"].").");

								$arFields = array(
									"SUBJECT" => "New TestFolderItem Name",
									"BODY" => "Should be done!!!",
									"BODY_TYPE" => "text",
									"IMPORTANCE" => "Low",
									"START_DATE" => "20.10.2011",
									"DUE_DATE" => "24.10.2011",
									"PERCENT_COMPLETE" => "0",
									"STATUS" => "NotStarted",
									"TOTAL_WORK" => "23",
									"REMINDER_MINUTES_BEFORE_START" => 365,
								);
								$r = $e->Update($itemId, $arFields);
								$itemId = null;
								$arMessages = array_merge($arMessages, $e->GetErrors());
								foreach ($r as $v)
									$itemId = $v;

								if (is_null($itemId))
								{
									$arMessages[] = array("ERROR", "Tasks item modification error.");
								}
								else
								{
									$arMessages[] = array("SUCCESS", "Tasks item was successfully updated (Id = ".$itemId["XML_ID"].", ChangeKey = ".$itemId["MODIFICATION_LABEL"].").");

									$bFound = false;
									$r = $e->GetList(
										array("Mailbox" => $mailbox, "FolderId" => $folderId["XML_ID"]),
										array("ItemShape" => "AllProperties")
									);
									$arMessages = array_merge($arMessages, $e->GetErrors());
									foreach ($r as $v)
									{
										if (($v["SUBJECT"] == "New TestFolderItem Name") && ($v["XML_ID"] == $itemId["XML_ID"]))
											$bFound = true;
									}

									if (!$bFound)
									{
										$arMessages[] = array("ERROR", "Tasks item is not found by list.");
									}
									else
									{
										$arMessages[] = array("SUCCESS", "Tasks item was successfully found by list.");

										$bFound = false;
										$r = $e->GetById($itemId["XML_ID"]);
										$arMessages = array_merge($arMessages, $e->GetErrors());
										foreach ($r as $v)
										{
											if (($v["SUBJECT"] == "New TestFolderItem Name") && ($v["XML_ID"] == $itemId["XML_ID"]))
												$bFound = true;
										}

										if (!$bFound)
										{
											$arMessages[] = array("ERROR", "Tasks item is not found by id.");
										}
										else
										{
											$arMessages[] = array("SUCCESS", "Tasks item was successfully found by id.");
											$r = $e->Delete($itemId["XML_ID"]);
											if (!$r)
											{
												$arMessages[] = array("ERROR", "Tasks item deletion error.");
											}
											else
											{
												$arMessages[] = array("SUCCESS", "Tasks item was successfully deleted.");

												$bFound = false;
												$r = $e->GetList(
													array("Mailbox" => $mailbox, "FolderId" => $folderId["XML_ID"]),
													array("ItemShape" => "IdOnly")
												);
												$arMessages = array_merge($arMessages, $e->GetErrors());
												foreach ($r as $v)
													$bFound = true;

												if ($bFound)
												{
													$arMessages[] = array("ERROR", "Tasks folder should be empty.");
												}
												else
												{
													$arMessages[] = array("SUCCESS", "Tasks folder is empty.");

													$r = $e->DeleteFolder($folderId["XML_ID"]);
													$arMessages = array_merge($arMessages, $e->GetErrors());
													if ($r)
														$arMessages[] = array("SUCCESS", "Tasks folder was successfully deleted.");
													else
														$arMessages[] = array("ERROR", "Tasks folder deletion error.");
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return $arMessages;
	}
}
?>