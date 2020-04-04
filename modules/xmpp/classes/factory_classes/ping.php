<?
$className = "CXMPPReceiveIQPing";
$classVersion = 2;

if (!class_exists("CXMPPReceiveIQPing"))
{
	class CXMPPReceiveIQPing
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

			if (!array_key_exists("iq", $arMessage) || !array_key_exists("ping", $arMessage["iq"])
				|| ($arMessage['iq']['ping']['.']['xmlns'] != 'urn:xmpp:ping'))
				return false;

			$to = "";
			if (array_key_exists("to", $arMessage["iq"]["."]))
				$to = $arMessage["iq"]["."]["to"];

			if (!empty($to) && ($to != $senderClient->GetClientDomain()))
				return false;

			$arResult = array(
				"iq" => array(
					"." => array(
						"type" => "result",
						"from" => $senderClient->GetClientDomain(),
						"id" => $arMessage['iq']['.']['id'],
						"to" => $senderClient->GetJIdWithResource(),
					),
				),
			);

			return $arResult;
		}
	}
}
?>