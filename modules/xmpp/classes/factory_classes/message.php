<?
$className = "CXMPPReceiveMessage";
$classVersion = 2;

if (!class_exists("CXMPPReceiveMessage"))
{
	class CXMPPReceiveMessage
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler, IXMPPFactoryServerHandler
	{
		public function GetIndex()
		{
			return 10;
		}

		private function htmlspecialcharsback($str)
		{
			if (strlen($str) > 0)
			{
				$str = str_replace("&lt;", "<", $str);
				$str = str_replace("&gt;", ">", $str);
				$str = str_replace("&quot;", "\"", $str);
				$str = str_replace("&amp;", "&", $str);
				$str = str_replace("&apos;", "'", $str);
			}
			return $str;
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (!$senderClient->IsAuthenticated())
				return false;
			if (strlen($senderJId) <= 0)
				return false;

			if (!array_key_exists("message", $arMessage) || !array_key_exists(".", $arMessage["message"]))
				return false;

			$type = "normal";
			if (array_key_exists("type", $arMessage["message"]["."]))
				$type = $arMessage["message"]["."]["type"];
			if ($type == "error")
				return false;

			$mid = "";
			if (array_key_exists("id", $arMessage["message"]["."]))
				$mid = $arMessage["message"]["."]["id"];

			if (!in_array($type, array("chat", "groupchat", "headline", "normal")))
				return CXMPPUtility::GetErrorArray($senderJId, "message", "modify", "bad-request", "", $mid, "", $senderClient->GetClientDomain());

			$recipientJId = "";
			if (array_key_exists("to", $arMessage["message"]["."]))
				$recipientJId = $arMessage["message"]["."]["to"];
			if (strlen($recipientJId) <= 0)
				return CXMPPUtility::GetErrorArray($senderJId, "message", "modify", "bad-request", "", $mid, "", $senderClient->GetClientDomain());

			$body = "";
			if (array_key_exists("body", $arMessage["message"]))
				$body = $arMessage["message"]["body"]["#"];
			if (strlen($body) <= 0)
				return true;
			//$body = CXMPPReceiveMessage::htmlspecialcharsback($body);
			//$body = html_entity_decode($body);
			$body = html_entity_decode($body, ENT_COMPAT, SITE_CHARSET);
			$body = str_replace("&apos;", "'", $body);

			$arSender = CXMPPUtility::GetUserByJId($senderJId);
			if (!$arSender)
				return CXMPPUtility::GetErrorArray($senderJId, "message", "auth", "forbidden", $recipientJId, $mid, "", $senderClient->GetClientDomain());

			$arRecipient = CXMPPUtility::GetUserByJId($recipientJId);
			if (!$arRecipient)
				return CXMPPUtility::GetErrorArray($senderJId, "message", "cancel", "item-not-found", $recipientJId, $mid, "", $senderClient->GetClientDomain());

			if (!CSocNetUserPerms::CanPerformOperation($arSender["ID"], $arRecipient["ID"], "message", false))
				return CXMPPUtility::GetErrorArray($senderJId, "message", "auth", "forbidden", $recipientJId, $mid, "", $senderClient->GetClientDomain());
			
			if (IsModuleInstalled("im") && CModule::IncludeModule("im"))
			{
				$arMessageFields = array(
					"FROM_USER_ID" => $arSender["ID"],
					"TO_USER_ID" => $arRecipient["ID"],
					"MESSAGE" => $body
				);
				CIMMessage::Add($arMessageFields);
			}
			else
			{
				$arMessageFields = array(
					"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"MESSAGE_TYPE" => SONET_MESSAGE_PRIVATE,
					"FROM_USER_ID" => $arSender["ID"],
					"TO_USER_ID" => $arRecipient["ID"],
					"MESSAGE" => $body,
				);
				CSocNetMessages::Add($arMessageFields);
			}

			return true;
		}

		public function GetServerIndex()
		{
			return 10;
		}

		public function ProcessServerMessage(array $arMessage, $clientDomain = "")
		{
			if (!array_key_exists("message", $arMessage) || !array_key_exists(".", $arMessage["message"]))
				return false;

			$type = "normal";
			if (array_key_exists("type", $arMessage["message"]["."]))
				$type = $arMessage["message"]["."]["type"];
			if ($type == "error")
				return false;

			if (!in_array($type, array("chat", "groupchat", "headline", "normal")))
				return CXMPPUtility::GetServerErrorArray("bad-request");

			$recipientJId = "";
			if (array_key_exists("to", $arMessage["message"]["."]))
				$recipientJId = $arMessage["message"]["."]["to"];
			if (strlen($recipientJId) <= 0)
				return CXMPPUtility::GetServerErrorArray("bad-request");

			$server = CXMPPServer::GetServer();
			$res = $server->Send($recipientJId, $arMessage, $clientDomain);

			return array(
				'result' => array(
					"." => array(
						"type" => ($res ? "success" : "skip"),
					),
				),
			);
		}
	}
}
?>