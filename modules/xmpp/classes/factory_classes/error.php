<?
$className = "CXMPPReceiveError";
$classVersion = 2;

if (!class_exists("CXMPPReceiveError"))
{
	class CXMPPReceiveError
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler
	{
		public function GetIndex()
		{
			return 100;
		}

		function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (strlen($senderJId) <= 0)
				return false;

			if (array_key_exists("message", $arMessage))
			{
				if (!array_key_exists(".", $arMessage["message"]))
					return false;

				$type = "normal";
				if (array_key_exists("type", $arMessage["message"]["."]))
					$type = $arMessage["message"]["."]["type"];
				if ($type != "error")
					return false;

				return true;
			}
			elseif (array_key_exists("iq", $arMessage))
			{
				if (!array_key_exists(".", $arMessage["iq"]))
					return false;

				$type = "";
				if (array_key_exists("type", $arMessage["iq"]["."]))
					$type = $arMessage["iq"]["."]["type"];
				if ($type != "error")
					return false;

				$to = "";
				if (array_key_exists("to", $arMessage["iq"]["."]))
					$to = $arMessage["iq"]["."]["to"];
				if (!empty($to) && ($to != $senderClient->GetClientDomain()))
					return false;

				return true;
			}

			return false;
		}
	}
}
?>