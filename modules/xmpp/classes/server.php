<?
class CXMPPServer
{
	private $sockServer;
	private $sockServerSSL;

	private $initialized;

	private $arClients = array();
	private $arClientsIndex = array();
	private $lastClientId;

	private $arSockets = array();
	private $socketsClientsStartIndex = 0;

	private $startPeriodTime;
	private $startClearPeriodTime;
	private $startLongPeriodTime;

	private $arOnlineOnSite = array();

	private $testMode = false;
	private $bitrix24Mode = false;

	public static function GetServer()
	{
		if (!array_key_exists("xmppServerObject", $GLOBALS) || !is_object($GLOBALS["xmppServerObject"]))
		{
			$GLOBALS["xmppServerObject"] = new CXMPPServer();
			$GLOBALS["xmppServerObject"]->Initialize();
		}
		return $GLOBALS["xmppServerObject"];
	}

	public static function IsServerStarted()
	{
		return (array_key_exists("xmppServerObject", $GLOBALS) && is_object($GLOBALS["xmppServerObject"]));
	}

	protected function Initialize()
	{
		if ($this->initialized)
			return;

		ini_set('max_execution_time', 0);
		set_time_limit(0);
		ob_implicit_flush(true);

		$level = ob_get_level();
		for ($i = 0; $i < $level; $i++)
			ob_end_clean();

		$this->testMode = ($GLOBALS["argc"] > 1 ? $GLOBALS["argv"][1] == "test_mode" : false);
		$this->bitrix24Mode = ($GLOBALS["argc"] > 1 ? $GLOBALS["argv"][1] == "bitrix24" : false);

		if ($this->testMode)
		{
			if ($f = @fsockopen(CXMPPServer::GetDomain(), 5222, $errNo, $errStr, 2))
				die(str_replace(array("#host#", "#port#"), array(CXMPPServer::GetDomain(), 5222), "#host#:#port# is already in use."));

			$curPhpVer = phpversion();
			$arCurPhpVer = explode(".", $curPhpVer);
			if (intval($arCurPhpVer[0]) < 5)
				die(str_replace("#ver#", $curPhpVer, "You are using PHP version #ver#, but the xmpp server requires version 5.0.0 or higher."));
		}

		$this->arClients = array();
		$this->arClientsIndex = array();
		$this->lastClientId = 0;

		$this->startPeriodTime = time();
		$this->startPeriodTimeTruncate = time();

		$this->arOnlineOnSite = array();

		$this->logLevel = intval(COption::GetOptionString("xmpp", "log_level", "4"));

		$this->initialized = true;
	}

	public function Run()
	{
		$server = CXMPPServer::GetServer();

		$startSSL = (strtoupper(COption::GetOptionString("xmpp", "start_ssl", "N")) == "Y");

		if ($server->Start() && (!$startSSL || $server->StartSSL()))
		{
			if ($server->testMode)
				$server->Stop();

			$server->Listen();
		}
	}

	protected function Start()
	{
		$listen = COption::GetOptionString("xmpp", "listen_domain", "0.0.0.0");
		$this->sockServer = stream_socket_server("tcp://".$listen.":5222", $errno = 0, $errstr = "");

		if (!$this->sockServer)
		{
			$this->WriteToLog("Create socket error: $errstr ($errno)", 10);
			return false;
		}

		$this->WriteToLog("Server started", 10);
		$this->socketsClientsStartIndex++;
		$this->arSockets[count($this->arSockets)] = $this->sockServer;
		return true;
	}

	protected function StartSSL()
	{
		$context = stream_context_create(
			array(
				'ssl' => array(
					'local_cert' => $_SERVER['DOCUMENT_ROOT'].'/cert/mycert.pem',
					'passphrase' => '',
					'allow_self_signed' => true,
					'verify_peer' => false,
				)
			)
		);

		$listen = COption::GetOptionString("xmpp", "listen_domain", "0.0.0.0");
		$this->sockServerSSL = stream_socket_server("ssl://".$listen.":5223", $errno = 0, $errstr = "", STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

		if (!$this->sockServerSSL)
		{
			$this->WriteToLog("Create socket error: $errstr ($errno)", 10);
			return false;
		}

		$this->WriteToLog("SSL Server started", 10);
		$this->socketsClientsStartIndex++;
		$this->arSockets[count($this->arSockets)] = $this->sockServerSSL;
		return true;
	}

	public function Stop()
	{
		if ($this->logFile)
			fclose($this->logFile);

		if ($this->sockServer)
			@fclose($this->sockServer);
		if ($this->sockServerSSL)
			@fclose($this->sockServerSSL);

		die();
	}

	protected function Listen()
	{
		$this->__ReportKernel();
		$this->startClearPeriodTime = time();

		if (!$this->sockServer && !$this->sockServerSSL)
			return;

		COption::SetOptionInt('xmpp', 'LastActivityDate', time());

		while (true)
		{
			$arReadSockets = $this->arSockets;

			$n = @stream_select($arReadSockets, $w = null, $e = null, 3);
			if ($n > 0)
			{
				while (list($k, $r) = each($arReadSockets))
				{
					if ($this->sockServer && ($r == $this->sockServer))
					{
						if (is_resource($sock = stream_socket_accept($this->sockServer, 0, $ip)))
						{
							$this->lastClientId++;
							$id = $this->lastClientId;

							stream_set_timeout($sock, 5);
							$this->arClients[$id] = new CXMPPClient($id, $sock);
							$this->arSockets[$id + $this->socketsClientsStartIndex] = $sock;

							$this->WriteToLog("Client connected (".$id.")", 5);
						}
					}
					elseif ($this->sockServerSSL && ($r == $this->sockServerSSL))
					{
						if (is_resource($sock = stream_socket_accept($this->sockServerSSL, 0, $ip)))
						{
							$this->lastClientId++;
							$id = $this->lastClientId;

							stream_set_timeout($sock, 5);
							$this->arClients[$id] = new CXMPPClient($id, $sock);
							$this->arSockets[$id + $this->socketsClientsStartIndex] = $sock;

							$this->WriteToLog("Client connected (".$id.")", 5);
						}
					}
					else
					{
						$id = array_search($r, $this->arSockets);
						if ($id !== false && $id > 1)
						{
							if (CXMPPUtility::SelectDatabase($this->arClients[$id - $this->socketsClientsStartIndex]->GetClientDomain()))
							{
								$this->arClients[$id - $this->socketsClientsStartIndex]->Receive();
							}
							else
							{
								$this->arClients[$id - $this->socketsClientsStartIndex]->Disconnect();
								$this->WriteToLog("Client is disconnected because it was not possible to select the database (".$this->arClients[$id - $this->socketsClientsStartIndex]->GetClientDomain().")", 10);
							}
						}
						else
						{
							$sn1 = @stream_socket_get_name($r, true);
							$sn2 = @stream_socket_get_name($r, false);
							$this->WriteToLog("Debug: Socket is not found (".$sn1."-".$sn2.")", 10);
						}
					}
				}
			}

			if (time() - $this->startClearPeriodTime > 30)
			{
				$this->__RefineClientsList();
				$this->WriteToLog("Number of clients connected = ".count($this->arClients), 10);
				$this->startClearPeriodTime = time();
			}

			if (time() - $this->startPeriodTime > 60)
			{
				$this->__ReportKernel();
				$this->startPeriodTime = time();
			}

			if (time() - $this->startLongPeriodTime > 600)
			{
				$this->__PingClients();
				$this->startLongPeriodTime = time();
				COption::SetOptionInt('xmpp', 'LastActivityDate', time());
			}
		}
	}

	private function __PingClients()
	{
		$bRefineClientsList = false;

		$arClientsKeys = array_keys($this->arClients);
		foreach ($arClientsKeys as $id)
		{
			$pingTime = $this->arClients[$id]->GetPingTime();
			if (($pingTime > 0) && (time() - $pingTime > 900))
			{
				$this->WriteToLog("Client disconnected from PING (".$id.",".time()."-".$pingTime.")", 5);
				$this->arClients[$id]->Disconnect();
				$bRefineClientsList = true;
			}
			else
			{
				$this->arClients[$id]->Send(
					array(
						"iq" => array(
							"." => array(
								"type" => "get",
								"from" => $this->arClients[$id]->GetClientDomain(),
								"to" => $this->arClients[$id]->GetJId(),
								"id" => "pg".rand(10, 99),
							),
							"ping" => array(
								"." => array("xmlns" => "urn:xmpp:ping"),
							),
						),
					)
				);
			}
		}

		if ($bRefineClientsList)
		{
			$this->__RefineClientsList();
			$this->WriteToLog("Number of clients connected = ".count($this->arClients), 10);
			$this->startClearPeriodTime = time();
		}
	}

	private function __RefineClientsList()
	{
		$arClientsKeys = array_keys($this->arClients);
		foreach ($arClientsKeys as $id)
		{
			if ($this->arClients[$id]->IsConnected() && !feof($this->arSockets[$id + $this->socketsClientsStartIndex]))
				continue;

			if ($this->arClients[$id]->IsConnected())
			{
				$this->WriteToLog("Client disconnected from REFINE (".$id.")", 5);
				$this->arClients[$id]->Disconnect();
			}

			$clientDomain = $this->arClients[$id]->GetClientDomain();
			$clientJId = $this->arClients[$id]->GetJId();
			$clientJIdWithResource = $this->arClients[$id]->GetJIdWithResource();

			if (is_array($this->arClientsIndex[$clientDomain][$clientJId]))
			{
				if (($i = array_search($id, $this->arClientsIndex[$clientDomain][$clientJId])) !== false)
					unset($this->arClientsIndex[$clientDomain][$clientJId][$i]);
			}

			if ($this->arClients[$id]->IsAuthenticated()
				&& (!array_key_exists($clientJId, $this->arClientsIndex[$clientDomain])
					|| count($this->arClientsIndex[$clientDomain][$clientJId]) <= 0))
			{
				foreach ($this->arClientsIndex[$clientDomain] as $jid1 => $arId1)
				{
					foreach ($arId1 as $id1)
					{
						if (($id1 != $id) && $this->arClients[$id1]->IsAuthenticated())
						{
							$this->Send(
								$jid1,
								array(
									"presence" => array(
										"." => array(
											"type" => "unavailable",
											"from" => $clientJIdWithResource,
											"to" => CXMPPUtility::GetJIdWithResource($jid1, $clientDomain),
										),
									),
								),
								$clientDomain
							);
						}
					}
				}
			}

			unset($this->arClients[$id]);
			unset($this->arSockets[$id + $this->socketsClientsStartIndex]);

			$this->WriteToLog("Client disconnected (".$id.")", 5);
		}
	}

	private function __ReportKernel()
	{
		foreach ($this->arClientsIndex as $clientDomain	=> $arDomainClientsIndex)
		{
			if (!CXMPPUtility::SelectDatabase($clientDomain))
				continue;
			if (count($arDomainClientsIndex) <= 0)
				continue;

			foreach ($arDomainClientsIndex as $jid => $arId)
			{
				foreach ($arId as $id)
				{
					if (array_key_exists($id, $this->arClients))
					{
						if ($this->arClients[$id]->IsConnected() && $this->arClients[$id]->IsAuthenticated())
						{
							$idTmp = $this->arClients[$id]->GetId();
							CUser::SetLastActivityDate($idTmp);
							CXMPPClient::SetLastActivityDate($idTmp);
						}
					}
				}
			}

			$arOnlineOnSiteTmp = array();

			$dbUsers = CUser::GetList(
				$b = "LOGIN",
				$o = "DESC",
				array(
					"ACTIVE" => "Y",
					"LAST_ACTIVITY" => 600,
					"!UF_DEPARTMENT" => false
				),
				array('FIELDS' => array('ID', 'LOGIN'))
			);
			while ($arUser = $dbUsers->Fetch())
				$arOnlineOnSiteTmp[] = CXMPPUtility::GetJId($arUser, $clientDomain);

			if (!is_array($this->arOnlineOnSite[$clientDomain]))
				$this->arOnlineOnSite[$clientDomain] = array();

			$arOffline = array_diff($this->arOnlineOnSite[$clientDomain], $arOnlineOnSiteTmp);

			foreach ($arOffline as $jid)
			{
				if (!array_key_exists($jid, $this->arClientsIndex[$clientDomain])
					|| array_key_exists($jid, $this->arClientsIndex[$clientDomain]) && count($this->arClientsIndex[$clientDomain][$jid]) <= 0)
				{
					$this->SendAll(
						array(
							"presence" => array(
								"." => array(
									"type" => "unavailable",
									"from" => $jid,
								),
							),
						)
					);
				}
			}

			$arOnline = array_diff($arOnlineOnSiteTmp, $this->arOnlineOnSite[$clientDomain]);

			foreach ($arOnline as $jid)
			{
				if (!array_key_exists($jid, $this->arClientsIndex[$clientDomain])
					|| array_key_exists($jid, $this->arClientsIndex[$clientDomain]) && count($this->arClientsIndex[$clientDomain][$jid]) <= 0)
					$this->SendAll(
						array(
							"presence" => array(
								"." => array(
									"from" => $jid,
								),
							),
						)
					);
			}

			$this->arOnlineOnSite[$clientDomain] = $arOnlineOnSiteTmp;
		}
	}

	public function SendPresenceMessages($receiverJId, $clientDomain = "")
	{
		if (empty($clientDomain))
			$clientDomain = CXMPPServer::GetDomain();

		$receiverJIdWithResource = CXMPPUtility::GetJIdWithResource($receiverJId, $clientDomain);

		if (array_key_exists($clientDomain, $this->arOnlineOnSite))
		{
			foreach ($this->arOnlineOnSite[$clientDomain] as $jid)
			{
				$this->Send(
					$receiverJId,
					array(
						"presence" => array(
							"." => array(
								"from" => CXMPPUtility::GetJIdWithResource($jid, $clientDomain),
								"to" => $receiverJIdWithResource,
							),
						),
					),
					$clientDomain
				);
			}
		}

		if (array_key_exists($clientDomain, $this->arClientsIndex))
		{
			foreach ($this->arClientsIndex[$clientDomain] as $jid => $arId)
			{
				if (count($arId) <= 0)
					continue;

				if (!is_array($this->arOnlineOnSite[$clientDomain]) || !in_array($jid, $this->arOnlineOnSite[$clientDomain]))
				{
					$this->Send(
						$receiverJId,
						array(
							"presence" => array(
								"." => array(
									"from" => CXMPPUtility::GetJIdWithResource($jid, $clientDomain),
									"to" => $receiverJIdWithResource,
								),
							),
						),
						$clientDomain
					);
				}
			}
		}
	}

	public function SendPresenceMessages2($receiverJId, $clientDomain = "")
	{
		if (empty($clientDomain))
			$clientDomain = CXMPPServer::GetDomain();

		$receiverJIdWithResource = CXMPPUtility::GetJIdWithResource($receiverJId, $clientDomain);

		if (array_key_exists($clientDomain, $this->arOnlineOnSite))
		{
			foreach ($this->arOnlineOnSite[$clientDomain] as $jid)
			{
				$this->Send(
					$jid,
					array(
						"presence" => array(
							"." => array(
								"from" => $receiverJIdWithResource,
								"to" => CXMPPUtility::GetJIdWithResource($jid, $clientDomain),
							),
						),
					),
					$clientDomain
				);
			}
		}

		if (array_key_exists($clientDomain, $this->arClientsIndex))
		{
			foreach ($this->arClientsIndex[$clientDomain] as $jid => $arId)
			{
				if (count($arId) <= 0)
					continue;

				if (!is_array($this->arOnlineOnSite[$clientDomain]) || !in_array($jid, $this->arOnlineOnSite[$clientDomain]))
				{
					$this->Send(
						$jid,
						array(
							"presence" => array(
								"." => array(
									"from" => $receiverJIdWithResource,
									"to" => CXMPPUtility::GetJIdWithResource($jid, $clientDomain),
								),
							),
						),
						$clientDomain
					);
				}
			}
		}
	}

	public static function GetDomain()
	{
		return COption::GetOptionString("xmpp", "domain_name", BX_XMPP_SERVER_DOMAIN);
	}

	public static function GetLanguage()
	{
		return COption::GetOptionString("xmpp", "domain_lang", "en");
	}

	public function Send($jid, $arMessage, $clientDomain = "")
	{
		$p = strpos($jid, "/");
		if ($p !== false)
			$jid = substr($jid, 0, $p);

		if (empty($clientDomain))
			$clientDomain = CXMPPServer::GetDomain();

		if (array_key_exists($clientDomain, $this->arClientsIndex))
		{
			if (array_key_exists($jid, $this->arClientsIndex[$clientDomain]))
			{
				$r = false;
				foreach ($this->arClientsIndex[$clientDomain][$jid] as $v)
					$r = $this->arClients[$v]->Send($arMessage);
				return $r;
			}
			else
			{
				CXMPPUtility::Show("Error jid=".$jid." - Not connected", 0);
			}
		}
		else
		{
			CXMPPUtility::Show("Error clientDomain=".$clientDomain." - Not connected", 0);
		}

		return false;
	}

	public function SendAll($arMessage, $clientDomain = "")
	{
		if (empty($clientDomain))
			$clientDomain = CXMPPServer::GetDomain();

		if (array_key_exists($clientDomain, $this->arClientsIndex))
		{
			foreach ($this->arClientsIndex[$clientDomain] as $jid => $arId)
			{
				foreach ($arId as $id)
				{
					if (array_key_exists($id, $this->arClients))
					{
						if ($this->arClients[$id]->IsAuthenticated())
							$this->arClients[$id]->Send($arMessage);
					}
				}
			}
		}
	}

	public function _IndexClient($jid, $id, $clientDomain = "")
	{
		$id = intval($id);

		if (empty($clientDomain))
			$clientDomain = CXMPPServer::GetDomain();

		if (!array_key_exists($clientDomain, $this->arClientsIndex))
			$this->arClientsIndex[$clientDomain] = array();

		if (!array_key_exists($jid, $this->arClientsIndex[$clientDomain]))
			$this->arClientsIndex[$clientDomain][$jid] = array();

		$this->arClientsIndex[$clientDomain][$jid][] = $id;
	}

	public function NumberOfOnlineUsers()
	{
		$this->__ReportKernel();

		$n = 0;
		foreach ($this->arOnlineOnSite as $key => $value)
			$n += count($value);

		return $n;
	}

	public function NumberOfConnectedUsers()
	{
		$this->__RefineClientsList();
		return Count($this->arClients);
	}

	public function ClearCaches()
	{
		$factory = CXMPPFactory::GetFactory();
		$factory->ClearCaches();
	}

	public function GetClient($jid, $domain = "")
	{
		if (empty($domain))
			$domain = CXMPPServer::GetDomain();

		if (array_key_exists($domain, $this->arClientsIndex))
		{
			if (array_key_exists($jid, $this->arClientsIndex[$domain]))
			{
				$r = array();
				foreach ($this->arClientsIndex[$domain][$jid] as $v)
					$r[] = $this->arClients[$v];
				if (count($r) > 0)
					return $r;
			}
		}

		return null;
	}

	var $logFile;
	var $logFileName = "/bitrix/modules/xmppd.log";
	var $logLevel = 0;
	var $logMaxSize = 2000000;
	var $startPeriodTimeTruncate;

	public function WriteToLog($txt, $level)
	{
		if ($this->logLevel > $level)
			return;

		if (time() - $this->startPeriodTimeTruncate > 600)
		{
			if ($this->logFile)
				fclose($this->logFile);

			$this->logFile = null;

			if (file_exists($_SERVER["DOCUMENT_ROOT"].$this->logFileName))
			{
				$logSize = @filesize($_SERVER["DOCUMENT_ROOT"].$this->logFileName);
				$logSize = intval($logSize);

				if ($logSize > $this->logMaxSize)
				{
					if (($fp = @fopen($_SERVER["DOCUMENT_ROOT"].$this->logFileName, "rb"))
						&& ($fp1 = @fopen($_SERVER["DOCUMENT_ROOT"].$this->logFileName."_", "wb")))
					{
						$iSeekLen = intval($logSize - $this->logMaxSize / 2.0);
						fseek($fp, $iSeekLen);

						@fwrite($fp1, "Truncated ".Date("Y-m-d H:i:s")."\n---------------------------------\n");
						do
						{
							$data = fread($fp, 8192);
							if (strlen($data) == 0)
								break;

							@fwrite($fp1, $data);
						}
						while (true);

						@fclose($fp);
						@fclose($fp1);

						@copy($_SERVER["DOCUMENT_ROOT"].$this->logFileName."_", $_SERVER["DOCUMENT_ROOT"].$this->logFileName);
						@unlink($_SERVER["DOCUMENT_ROOT"].$this->logFileName."_");
					}
				}
				ClearStatCache();
			}

			$this->startPeriodTimeTruncate = time();
		}

		if (!$this->logFile || $this->logFile == null)
			$this->logFile = fopen($_SERVER["DOCUMENT_ROOT"].$this->logFileName, "a");

		if (!$this->logFile)
		{
			echo "Can't write to log\n---------------------------------\n";
			return;
		}

		fwrite($this->logFile, date("Y-m-d H:i:s")."\n".$txt."\n---------------------------------\n");
		fflush($this->logFile);

		if ($level > 4)
			echo $txt."\n---------------------------------\n";
	}

	public function GetDumpData()
	{
		$result = "";

		$result .= "arClientsIndex:\n".print_r($this->arClientsIndex, true)."\n\n";
		$result .= "--------------------------------------------------------\n\n";

		$result .= "arClients:\n";
		foreach ($this->arClients as $key => $client)
			$result .= "> ".$key.":\n".$client->GetDumpData()."\n\n";
		$result .= "--------------------------------------------------------\n\n";

		$result .= "arOnlineOnSite:\n".print_r($this->arOnlineOnSite, true)."\n\n";
		$result .= "--------------------------------------------------------\n\n";

		return $result;
	}

	public function IsBitrix24Mode()
	{
		return $this->bitrix24Mode;
	}
}
?>