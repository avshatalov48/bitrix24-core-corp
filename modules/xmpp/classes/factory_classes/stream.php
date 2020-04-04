<?
$className = "CXMPPStream";
$classVersion = 2;

if (!class_exists("CXMPPStream"))
{
	class CXMPPStream
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler
	{
		public function GetIndex()
		{
			return 60;
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if ($senderClient->IsAuthenticated() && !array_key_exists("stream:stream", $arMessage))
				return false;

			$server = CXMPPServer::GetServer();
			$arResult = true;

			if (array_key_exists("stream:stream", $arMessage))
			{
				if ($senderClient->IsAuthenticated())
				{
					$senderClient->SetStreamId("bx".rand(1000, 9999));
					$message = sprintf(
						'<'.'?xml version="1.0" encoding="UTF-8"?'.'><stream:stream xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams" id="%s" from="%s" version="1.0"><stream:features><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"/><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/><ping xmlns="urn:xmpp:ping"/></stream:features>',
						$senderClient->GetStreamId(),
						htmlspecialcharsbx($senderClient->GetClientDomain())
					);
					$senderClient->__Send($message);
				}
				else
				{
					$version = "";
					if (is_array($arMessage["stream:stream"]["."]) && array_key_exists("version", $arMessage["stream:stream"]["."]))
						$version = $arMessage["stream:stream"]["."]["version"];

					$to = "";
					if (is_array($arMessage["stream:stream"]["."]) && array_key_exists("to", $arMessage["stream:stream"]["."]))
						$to = $arMessage["stream:stream"]["."]["to"];

					if (CXMPPUtility::IsBitrix24Mode())
						$senderClient->SetClientDomain($to);
					else
						$senderClient->SetClientDomain(CXMPPServer::GetDomain());

					if (CXMPPUtility::SelectDatabase($senderClient->GetClientDomain()))
					{
						$bAllowSasl = false;
						if (strlen($version) > 0)
						{
							$ar = explode(".", $version);
							$majorV = intval($ar[0]);
							if ($majorV >= 1)
								$bAllowSasl = true;
						}

						if ($bAllowSasl)
							$senderClient->SetAuthenticationType("SASL");
						else
							$senderClient->SetAuthenticationType("NON-SASL");

						if ($bAllowSasl)
						{
							$message = sprintf(
								'<?xml version="1.0"?><stream:stream xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams" id="%s" from="%s" version="1.0"><stream:features><mechanisms xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><mechanism>PLAIN</mechanism></mechanisms><auth xmlns="http://jabber.org/features/iq-auth"/></stream:features>',
								rand(1000, 9999),
								htmlspecialcharsbx($senderClient->GetClientDomain())
							);
						}
						else
						{
							$message = sprintf(
								'<?xml version="1.0"?><stream:stream xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams" id="%s" from="%s">',
								rand(1000, 9999),
								htmlspecialcharsbx($senderClient->GetClientDomain())
							);
						}

						$senderClient->__Send($message);
					}
					else
					{
						$senderClient->Disconnect();
					}
				}
			}
			elseif (array_key_exists("auth", $arMessage) && $senderClient->GetAuthenticationType() == "SASL")
			{
				$mechanism = "PLAIN";
				if (array_key_exists("mechanism", $arMessage["auth"]["."]))
					$mechanism = strtoupper($arMessage["auth"]["."]["mechanism"]);

				$message = '';

				if ($mechanism == "PLAIN")
				{
					$r = base64_decode($arMessage["auth"]["#"]);

					if (strlen($r) > 0)
					{
						if (substr($r, 0, 1) == "\x00")
							$r = "z".$r;

						$arResp = explode("\x00", $r);

						$jid = $arResp[0];
						$login = $arResp[1];
						$pwd = $arResp[2];

						if (strlen($login) > 0)
						{
							$authResult = $GLOBALS["USER"]->Login($login, $pwd, "N");
							CXMPPUtility::Show("!S ".$login.": ".(is_array($authResult) ? Print_R($authResult, true) : $authResult), 0);

							if ($authResult === true)
							{
								$message = '<success xmlns="urn:ietf:params:xml:ns:xmpp-sasl"/>';

								$senderClient->_Authenticate(
									$GLOBALS["USER"]->GetID(),
									$login,
									CXMPPUtility::GetJId(array("LOGIN" => $login), $senderClient->GetClientDomain()),
									false,
									$arMessage['iq']['query']['resource']['#']
								);
							}
							else
							{
								$message = '<failure xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><not-authorized/></failure></stream:stream>';
							}
						}
						else
						{
							$message = '<failure xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><temporary-auth-failure/></failure></stream:stream>';
						}
					}
					else
					{
						$message = '<failure xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><temporary-auth-failure/></failure></stream:stream>';
					}
				}
				else
				{
					$message = '<failure xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><invalid-mechanism/></failure></stream:stream>';
				}

				$senderClient->__Send($message);
			}
			elseif (array_key_exists("iq", $arMessage) && $senderClient->GetAuthenticationType() == "NON-SASL")
			{
				$message = '';

				if ($arMessage['iq']['query']['.']['xmlns'] == 'jabber:iq:auth')
				{
					$type = $arMessage['iq']['.']['type'];
					if (!in_array($type, array("get", "set")))
						return;

					if ($type == 'get')
					{
						$message = sprintf(
							'<iq type="result" id="%s" from="%s"><query xmlns="%s"><username>%s</username><password/><resource/></query></iq>',
							htmlspecialcharsbx($arMessage['iq']['.']['id']),
							htmlspecialcharsbx($senderClient->GetClientDomain()),
							htmlspecialcharsbx($arMessage['iq']['query']['.']['xmlns']),
							htmlspecialcharsbx($arMessage['iq']['query']['username']['#'])
						);
					}
					elseif ($type == 'set')
					{
						$username = $arMessage['iq']['query']['username']['#'];
						$password = $arMessage['iq']['query']['password']['#'];

						$authResult = $GLOBALS["USER"]->Login($username, $password, "N");
						CXMPPUtility::Show("!N ".$username.": ".(is_array($authResult) ? print_r($authResult, true) : $authResult), 0);

						if ($authResult === true)
						{
							$message = sprintf(
								'<iq type="result" id="%s" from="%s"/>',
								htmlspecialcharsbx($arMessage['iq']['.']['id']),
								htmlspecialcharsbx($senderClient->GetClientDomain())
							);

							$senderClient->_Authenticate($GLOBALS["USER"]->GetID(), $username,
								CXMPPUtility::GetJId(array("LOGIN" => $username), $senderClient->GetClientDomain()),
								false, $arMessage['iq']['query']['resource']['#']
							);
						}
						else
						{
							$message = sprintf(
								'<iq type="error" id="%s" from="%s"><error code="401" type="auth"><not-authorized xmlns="urn:ietf:params:xml:ns:xmpp-stanzas"/></error></iq>',
								htmlspecialcharsbx($arMessage['iq']['.']['id']),
								htmlspecialcharsbx($senderClient->GetClientDomain())
							);
						}
					}
				}
				else
				{
					$message = '<failure xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><invalid-mechanism/></failure></stream:stream>';
				}

				$senderClient->__Send($message);
			}

			return $arResult;
		}
	}
}
?>