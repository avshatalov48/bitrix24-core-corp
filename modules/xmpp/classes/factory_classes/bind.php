<?
$className = "CXMPPReceiveIQBind";
$classVersion = 2;

if (!class_exists("CXMPPReceiveIQBind"))
{
	class CXMPPReceiveIQBind
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler
	{
		public function GetIndex()
		{
			return 30;
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (strlen($senderJId) <= 0)
				return false;

			if (!array_key_exists("iq", $arMessage) || !array_key_exists("bind", $arMessage["iq"])
				|| ($arMessage['iq']['bind']['.']['xmlns'] != 'urn:ietf:params:xml:ns:xmpp-bind'))
				return false;

			$type = "";
			if (array_key_exists("type", $arMessage["iq"]["."]))
				$type = $arMessage["iq"]["."]["type"];

			if ($type == "set")
			{
				$resource = $arMessage["iq"]["bind"]["resource"]["#"];
				if (strlen($resource) <= 0)
					$resource = "bx";

				$senderClient->SetResource($resource);

				$arMessageTmp = array(
					"iq" => array(
						"." => array(
							"type" => "result",
							"to" => htmlspecialcharsbx($senderClient->GetClientDomain())."/".$senderClient->GetStreamId(),
							"id" => $arMessage['iq']['.']['id'],
						),
						"bind" => array(
							"." => array("xmlns" => "urn:ietf:params:xml:ns:xmpp-bind"),
							"jid" => array("#" => $senderJId."/".$resource),
						),
					),
				);

				$messageTmp = CXMPPParser::ToXml($arMessageTmp);
				$senderClient->__Send($messageTmp);
			}
			else
			{
				$arMessageTmp = array(
					"iq" => array(
						"." => array(
							"type" => "result",
							"from" => $senderClient->GetClientDomain(),
							"id" => $arMessage['iq']['.']['id'],
						),
					),
				);

				$messageTmp = CXMPPParser::ToXml($arMessageTmp);
				$senderClient->__Send($messageTmp);
			}

			return true;
		}
	}
}
?>