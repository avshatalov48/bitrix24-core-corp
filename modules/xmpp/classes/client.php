<?
class CXMPPClient
{
	private $xmppId;
	private $jid;
	private $clientDomain = "";

	private $id;
	private $login;
	private $provider;

	private $sock;
	private $resource;

	private $connected = false;
	private $authenticated = false;
	private $authenticationType = "";
	private $authenticationStep = 0;

	private $readBuffer = "";

	private $supportSubGroups = false;

	private $streamId;

	private $presenceDate = null;
	private $presenceType = null;
	private $pingTime;

	public function __construct($xmppId, $sock)
	{
		$this->xmppId = $xmppId;
		$this->sock = $sock;
		$this->connected = true;
		$this->authenticated = false;
		$this->authenticationStep = 0;
	}

	public function IsSubGroupsSupported()
	{
		return $this->supportSubGroups;
	}

	public function SetSubGroupsSupport($val)
	{
		$this->supportSubGroups = ($val ? true : false);
	}

	public function GetClientDomain()
	{
		if (empty($this->clientDomain))
		{
			if (empty($this->jid))
				return "";

			$ar = explode("@", $this->jid);
			$v = $ar[count($ar) - 1];

			$ar = explode("/", $v);
			$this->clientDomain = $ar[0];
		}

		return $this->clientDomain;
	}

	public function SetClientDomain($val)
	{
		$this->clientDomain = $val;
	}

	public function GetXmppId()
	{
		return $this->xmppId;
	}

	public function GetJId()
	{
		return $this->jid;
	}

	public function GetJIdWithResource()
	{
		return $this->jid.(!empty($this->resource) ? "/".$this->resource : "");
	}

	public function GetId()
	{
		return $this->id;
	}

	public function IsConnected()
	{
		return $this->connected;
	}

	public function IsAuthenticated()
	{
		return $this->authenticated;
	}

	public function GetAuthenticationStep()
	{
		return $this->authenticationStep;
	}

	public function SetAuthenticationStep($step)
	{
		$this->authenticationStep = $step;
	}

	public function GetAuthenticationType()
	{
		return $this->authenticationType;
	}

	public function SetAuthenticationType($type)
	{
		$this->authenticationType = $type;
	}

	public function SetResource($resource)
	{
		$this->resource = $resource;
	}

	public function Send($arMessage)
	{
		if (!$this->connected)
			return false;

		if (count($arMessage) <= 0)
			return false;

		$arMessageKeys = array_keys($arMessage);
		if (count($arMessageKeys) <= 0)
			return false;

		$thisJId = $this->jid.(!empty($this->resource) ? "/".$this->resource : "");

		foreach ($arMessageKeys as $key)
		{
			if ($arMessage[$key][0])
			{
				$arMessageKeys1 = array_keys($arMessage[$key]);
				foreach ($arMessageKeys1 as $key1)
				{
					$arMessage[$key][$key1]["."]["to"] = $thisJId;
					if (isset($arMessage[$key][$key1]["."]["from"]) && !isset($arMessage[$key][$key1]["vCard"]))
						$arMessage[$key][$key1]["."]["from"] = CXMPPUtility::GetJIdWithResource($arMessage[$key][$key1]["."]["from"], $this->clientDomain);
				}
			}
			else
			{
				$arMessage[$key]["."]["to"] = $thisJId;
				if (isset($arMessage[$key]["."]["from"]) && !isset($arMessage[$key]["vCard"]))
					$arMessage[$key]["."]["from"] = CXMPPUtility::GetJIdWithResource($arMessage[$key]["."]["from"], $this->clientDomain);
			}
		}

		$message = CXMPPParser::ToXml($arMessage);

		return $this->__Send($message);
	}

	public function __Send($message)
	{
		if (strlen($message) <= 0)
			return false;

		CXMPPUtility::Show(">>> ".$this->jid." (".$this->xmppId.")\n".$message, 0);

		$r = fwrite($this->sock, $message);

		CXMPPUtility::Show(">>> ".$this->jid." (".$this->xmppId."): ".$r, 0);

		return ($r !== false);
	}

	/*
	function HasMessage()
	{
		if (!$this->connected)
			return false;

		if (FEof($this->sock))
		{
			$this->Disconnect();
			return false;
		}

		return (0 !== Stream_Select($r = array($this->sock), $w = null, $e = null, 0));
	}
	*/

	public function Receive()
	{
		$this->readBuffer .= fread($this->sock, 8192);

		return $this->__ParseBuffer();
	}

	public function Disconnect()
	{
		CXMPPUtility::Show("Disconnect ".$this->jid." (".$this->xmppId.")", 5);
		@fclose($this->sock);
		$this->connected = false;
		$this->ChangeWorkPresence("Disconnect", "");

		CXMPPClient::SetLastActivityDate($this->id, 0, true);
	}

	protected function __ParseBuffer()
	{
		$buffer = trim($this->readBuffer);
		if (strlen($buffer) <= 0)
			return false;

		if (strtolower(substr($buffer, 0, 5)) == '<?xml')
			$buffer = trim(substr($buffer, strpos($buffer, ">") + 1));
		if (strtolower(substr($buffer, 0, 14)) == '<stream:stream')
		{
			$buffer .= "</stream:stream>";
			stream_set_timeout($this->sock, 5);
		}

		$arRequest = CXMPPParser::ToArray($buffer);
		if (!$arRequest)
			return false;

		CXMPPUtility::Show("<<< ".$this->jid." (".$this->xmppId.")\n".$buffer, 0);

		$this->readBuffer = "";
		$this->pingTime = time();

		if ($arRequest['server'])
		{
			if (isset($arRequest['server']['.']['domain']))
				$clientDomain = $arRequest['server']['.']['domain'];
			if (empty($clientDomain))
				$clientDomain = CXMPPServer::GetDomain();

			if (!CXMPPUtility::SelectDatabase($clientDomain))
			{
				$this->Disconnect();
				return false;
			}

			if ($arRequest['server']['.']['uniid'] != CXMPPUtility::GetUniid($clientDomain))
			{
				$this->Disconnect();
				return false;
			}

			unset($arRequest['server']);

			foreach ($arRequest as $key => $value)
			{
				if ($value[0])
				{
					foreach ($value as $value0)
						$this->__ProcessServerMessage(array($key => $value0), $clientDomain);
				}
				else
				{
					$this->__ProcessServerMessage(array($key => $value), $clientDomain);
				}
			}

			$this->Disconnect();

			return true;
		}

		foreach ($arRequest as $key => $value)
		{
			if ($value[0])
			{
				foreach ($value as $value0)
					$this->__ProcessMessage(array($key => $value0));
			}
			else
			{
				$this->__ProcessMessage(array($key => $value));
			}
		}

		return true;
	}

	private function __ProcessMessage($arMessage)
	{
		$arMessageKeys = array_keys($arMessage);
		if (count($arMessageKeys) <= 0)
			return false;

		$thisJId = $this->jid.(!empty($this->resource) ? "/".$this->resource : "");

		foreach ($arMessageKeys as $key)
		{
			if (strlen($arMessage[$key]["."]["from"]) <= 0)
				$arMessage[$key]["."]["from"] = $thisJId;
		}

		$factory = CXMPPFactory::GetFactory();

//		$arAuth = false;

		$processResult = $factory->ReceiveMessage($this->jid, $arMessage, $this);

//		if ($arAuth)
//			$this->__Authenticate($arAuth);

		if (is_array($processResult))
			$this->Send($processResult);
	}

	private function __ProcessServerMessage($arMessage, $clientDomain = "")
	{
		if (count($arMessage) <= 0)
			return false;

		if (empty($clientDomain))
			$clientDomain = CXMPPServer::GetDomain();

		$factory = CXMPPFactory::GetFactory();

		$processResult = $factory->ProcessServerMessage($arMessage, $clientDomain);

		if (is_array($processResult))
			$this->Send($processResult);
	}

	public function _Authenticate($id, $login, $jid, $provider = "", $resource = "")
	{
		$id = intval($id);

		$this->authenticated = false;

		if ($id <= 0 || strlen($login) <= 0 || strlen($jid) <= 0)
			return false;

		$this->id = $id;
		$this->login = $login;
		$this->provider = $provider;
		$this->jid = strtolower($jid);
		$this->resource = $resource;

		$server = CXMPPServer::GetServer();
		$server->_IndexClient($this->jid, $this->xmppId, $this->GetClientDomain());

		$this->authenticated = true;

		CXMPPUtility::Show("Authenticate ".$this->jid." (".$this->xmppId.")", 5);
		$this->ChangeWorkPresence("Authenticate", "");
		CXMPPClient::SetLastActivityDate($this->id, time(), true);

		return true;
	}

	private static function GetPresenceIBlockId($clientDomain)
	{
		static $arPresenceIBlockIdCache = array();

		if (!array_key_exists($clientDomain, $arPresenceIBlockIdCache))
		{
			CXMPPUtility::ClearOptionsCache("xmpp");
			$arPresenceIBlockIdCache[$clientDomain] = COption::GetOptionInt('xmpp', 'iblock_presence');
		}

		return $arPresenceIBlockIdCache[$clientDomain];
	}

	public function ChangeWorkPresence($type, $note, $clientDomain = "")
	{
		global $DB;

		$iblockId = self::GetPresenceIBlockId($clientDomain);
		if ($iblockId <= 0)
			return;

		if ($type == "Authenticate")
		{
			$this->presenceDate = date($DB->DateFormatToPHP(FORMAT_DATETIME));
			$this->presenceType = $note;
			return;
		}

		if (strlen($this->presenceDate) <= 0 || $this->presenceDate == date($DB->DateFormatToPHP(FORMAT_DATETIME)))
			return;
		if (intval($this->id) <= 0)
			return;

		$arFields = array(
			"DATE_ACTIVE_FROM" => $this->presenceDate,
			"DATE_ACTIVE_TO" => date($DB->DateFormatToPHP(FORMAT_DATETIME)),
			"NAME" => (strlen($this->presenceType) <= 0) ? "Online" : $this->presenceType,
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $iblockId,
			"IBLOCK_SECTION_ID" => 0,
			"PROPERTY_VALUES" => array(
				"USER" => array($this->id),
				"USER_ACTIVE" => array("Y"),
			),
		);

		$iblockElementObject = new CIBlockElement;
		$idTmp = $iblockElementObject->Add($arFields);

		$this->presenceDate = date($DB->DateFormatToPHP(FORMAT_DATETIME));
		$this->presenceType = $note;
	}

	public function SetStreamId($streamId)
	{
		$this->streamId = $streamId;
	}

	public function GetStreamId()
	{
		return $this->streamId;
	}

	public function GetDumpData()
	{
		$result = "";

		$result .= "\txmppId = ".$this->xmppId."\n";
		$result .= "\tjid = ".$this->jid."\n";
		$result .= "\tclientDomain = ".$this->clientDomain."\n";
		$result .= "\tid = ".$this->id."\n";
		$result .= "\tlogin = ".$this->login."\n";
		$result .= "\tresource = ".$this->resource."\n";
		$result .= "\tconnected = ".$this->connected."\n";
		$result .= "\tauthenticated = ".$this->authenticated;

		return $result;
	}

	public function GetPingTime()
	{
		return $this->pingTime;
	}

	public static function SetLastActivityDate($userId, $lastActivityDate = null, $sendPull = false)
	{
		if (is_null($lastActivityDate))
			$lastActivityDate = time();

		CUserOptions::SetOption('xmpp', 'LastActivityDate', intval($lastActivityDate), false, $userId);

		if ($sendPull)
		{
			if (CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus())
			{
				CPullStack::AddByUser($userId, Array(
					'module_id' => 'xmpp',
					'command'   => 'lastActivityDate',
					'params'    => Array(
						'timestamp' => intval($lastActivityDate)
					),
				));
			}
		}

		return true;
	}
}
?>