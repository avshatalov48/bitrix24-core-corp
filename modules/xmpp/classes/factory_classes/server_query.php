<?
$className = "CXMPPServerQuery";
$classVersion = 2;

if (!class_exists("CXMPPServerQuery"))
{
	class CXMPPServerQuery
		extends CXMPPFactoryHandler
		implements IXMPPFactoryServerHandler
	{
		public function GetServerIndex()
		{
			return 100;
		}

		public function ProcessServerMessage(array $arMessage, $clientDomain = "")
		{
			if (!array_key_exists("query", $arMessage) || !array_key_exists(".", $arMessage["query"]))
				return false;

			$arResult = array();

			$type = "get";
			if (array_key_exists("type", $arMessage["query"]["."]))
				$type = $arMessage["query"]["."]["type"];

			$kind = "common";

			if ($type == "get")
			{
				$server = CXMPPServer::GetServer();

				$arResult = array(
					"query" => array(
						"." => array("type" => "result"),
						"common" => array(
							"online" => array("#" => $server->NumberOfOnlineUsers()),
							"connected" => array("#" => $server->NumberOfConnectedUsers() - 1),
						),
					),
				);
			}
			elseif ($type == "set")
			{
				$action = $arMessage["query"]["action"]["#"];
				if ($action == "die")
				{
					$server = CXMPPServer::GetServer();
					$server->Stop();
					die();
				}
				elseif ($action == "clearcache")
				{
					$server = CXMPPServer::GetServer();
					$server->ClearCaches();

					$arResult = array(
						"query" => array(
							"." => array("type" => "result"),
						),
					);
				}
				elseif ($action == "dump")
				{
					CXMPPUtility::MakeDump();

					$arResult = array(
						"query" => array(
							"." => array("type" => "result"),
						),
					);
				}
			}
	
			return $arResult;
		}
	}
}
?>