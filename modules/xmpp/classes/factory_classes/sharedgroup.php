<?
$className = "CXMPPSharedGroupIQ";
$classVersion = 2;

if (!class_exists("CXMPPSharedGroupIQ"))
{
	class CXMPPSharedGroupIQ
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler
	{
		public function GetIndex()
		{
			return 50;
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (!$senderClient->IsAuthenticated())
				return false;
			if (strlen($senderJId) <= 0)
				return false;

			if (!array_key_exists("iq", $arMessage))
				return false;

			if (!array_key_exists("sharedgroup", $arMessage["iq"]))
				return false;

			$arResult = array(
				"iq" => array(
					"." => array(
						"type" => "result",
						"from" => CXMPPServer::GetDomain(),
						"id" => $arMessage['iq']['.']['id'],
					),
					"sharedgroup" => array(
						"." => array("xmlns" => "http://www.jivesoftware.org/protocol/sharedgroup"),
					),
				),
			);

			if (is_array($GLOBALS["BX_JHGDHGFJKDFSDG67"][$senderClient->GetClientDomain()]))
				foreach ($GLOBALS["BX_JHGDHGFJKDFSDG67"][$senderClient->GetClientDomain()] as $g)
					$arResult["iq"]["sharedgroup"]["group"][] = array("#" => $g);

			return $arResult;
		}
	}
}
?>