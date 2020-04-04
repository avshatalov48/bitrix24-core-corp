<?
$className = "CXMPPReceiveIQ";
$classVersion = 2;

if (!class_exists("CXMPPReceiveIQ"))
{
	class CXMPPReceiveIQ
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler
	{
		public function GetIndex()
		{
			return 10000;
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (!$senderClient->IsAuthenticated())
				return false;
			if (strlen($senderJId) <= 0)
				return false;

			if (!array_key_exists("iq", $arMessage))
				return false;

			$to = "";
			if (array_key_exists("to", $arMessage["iq"]["."]))
				$to = $arMessage["iq"]["."]["to"];

			if (strlen($to) > 0 && strpos($to, "@") !== false)
			{
				$arResult = true;

				$arMessage["iq"]["."]["to"] = CXMPPUtility::GetJIdWithResource($arMessage["iq"]["."]["to"], "");

				$server = CXMPPServer::GetServer();
				$server->Send($to, $arMessage, $senderClient->GetClientDomain());
			}
			else
			{
				if ($arMessage["iq"]["."]["type"] == "get" && $arMessage["iq"]["query"]["."]["xmlns"] == "http://jabber.org/protocol/disco#items")
				{
					$arResult = array(
						"iq" => array(
							"." => array(
								"type" => "result",
								"from" => $senderClient->GetClientDomain(),
								"id" => $arMessage['iq']['.']['id'],
							),
							"query" => array(
								"." => array("xmlns" => "http://jabber.org/protocol/disco#items"),
							),
						),
					);
				}
				elseif ($arMessage["iq"]["."]["type"] == "set" && $arMessage["iq"]["session"]["."]["xmlns"] == "urn:ietf:params:xml:ns:xmpp-session")
				{
					$arResult = array(
						"iq" => array(
							"." => array(
								"type" => "result",
								"from" => $senderClient->GetClientDomain(),
								"id" => $arMessage['iq']['.']['id'],
							),
						),
					);
				}
				else
				{
					$arResult = array(
						"iq" => array(
							"." => array(
								"type" => "error",
								"from" => $senderClient->GetClientDomain(),
								"id" => $arMessage['iq']['.']['id'],
							),
							"error" => array(
								"." => array("type" => "cancel"),
								"feature-not-implemented" => array("." => array("xmlns" => "urn:ietf:params:xml:ns:xmpp-stanzas")),
							),
						),
					);
				}
				//$arResult = CXMPPUtility::GetErrorArray($senderJId, "iq", "cancel", "feature-not-implemented", "", $arMessage['iq']['.']['id'], "");
			}

			return $arResult;
		}
	}
}
?>