<?
$className = "CXMPPReceivePresence";
$classVersion = 2;

if (!class_exists("CXMPPReceivePresence"))
{
	class CXMPPReceivePresence
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler, IXMPPFactoryServerHandler
	{
		public function GetIndex()
		{
			return 20;
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (!$senderClient->IsAuthenticated())
				return false;
			if (strlen($senderJId) <= 0)
				return false;

			if (!array_key_exists("presence", $arMessage) || !array_key_exists(".", $arMessage["presence"]))
				return false;

			$type = "available";
			if (array_key_exists("type", $arMessage["presence"]["."]))
				$type = $arMessage["presence"]["."]["type"];
			if ($type == "error")
				return false;

			// available (empty) - Signals that the sender is online and available for communication.
			// unavailable - Signals that the sender is no longer available for communication.
			// subscribe - The sender wishes to subscribe to the recipient's presence.
			// subscribed - The sender has allowed the recipient to receive their presence.
			// unsubscribe - The sender is unsubscribing from another entity's presence.
			// unsubscribed - The subscription request has been denied or a previously-granted subscription has been cancelled.
			// probe - A request for an entity's current presence; SHOULD be generated only by a server on behalf of a user.
			// error - An error has occurred regarding processing or delivery of a previously-sent presence stanza.
			if (!in_array($type, array("available", "unavailable", "subscribe", "subscribed", "unsubscribe", "unsubscribed", "probe")))
				return CXMPPUtility::GetErrorArray($senderJId, "presence", "modify", "bad-request", "", "", "", $senderClient->GetClientDomain());

			$to = "";
			if (array_key_exists("to", $arMessage["presence"]["."]))
				$to = $arMessage["presence"]["."]["to"];

			$server = CXMPPServer::GetServer();
			if (strlen($to) <= 0)
				$server->SendAll($arMessage, $senderClient->GetClientDomain());
			else
				$server->Send($to, $arMessage, $senderClient->GetClientDomain());

			if (!in_array($type, array("available")))
				return true;

			$userJId = $senderJId;

			$show = "online";
			if (array_key_exists("show", $arMessage["presence"]))
				$show = $arMessage["presence"]["show"]["#"];

			$status = "";
			if (array_key_exists("status", $arMessage["presence"]))
				$status = $arMessage["presence"]["status"]["#"];

			$priority = 0;
			if (array_key_exists("priority", $arMessage["presence"]))
				$priority = intval($arMessage["presence"]["priority"]["#"]);

			$arUser = CXMPPUtility::GetUserByJId($userJId);
			if (!$arUser)
				return CXMPPUtility::GetErrorArray($senderJId, "presence", "auth", "forbidden", "", "", "", $senderClient->GetClientDomain());

			CUser::SetLastActivityDate($arUser["ID"]);

			CXMPPFactory::SendUnreadMessages($senderJId, $senderClient->GetClientDomain());

			$senderClient->ChangeWorkPresence("Status", $show);

			return true;
		}

		public function GetServerIndex()
		{
			return 20;
		}

		public function ProcessServerMessage(array $arMessage, $clientDomain = "")
		{
			if (!array_key_exists("presence", $arMessage) || !array_key_exists(".", $arMessage["presence"]))
				return false;

			$type = "available";
			if (array_key_exists("type", $arMessage["presence"]["."]))
				$type = $arMessage["presence"]["."]["type"];
			if ($type == "error")
				return false;

			if (!in_array($type, array("available", "unavailable", "subscribe", "subscribed", "unsubscribe", "unsubscribed", "probe")))
				return CXMPPUtility::GetServerErrorArray("bad-request");

			$recipientJId = "";
			if (array_key_exists("to", $arMessage["presence"]["."]))
				$recipientJId = $arMessage["presence"]["."]["to"];

			$server = CXMPPServer::GetServer();
			if (strlen($recipientJId) <= 0)
				$server->SendAll($arMessage, $clientDomain);
			else
				$server->Send($recipientJId, $arMessage, $clientDomain);

			return true;
		}
	}
}
?>