<?
$className = "CXMPPReceiveIQRoster";
$classVersion = 2;

if (!class_exists("CXMPPReceiveIQRoster"))
{
	class CXMPPReceiveIQRoster
		extends CXMPPFactoryHandler
		implements IXMPPFactoryHandler, IXMPPFactoryCleanableHandler
	{
		private $rosterCache = array();
		private $rosterCacheAlt = array();

		private $intranet;

		public function GetIndex()
		{
			return 50;
		}

		public function Initialize()
		{
			parent::Initialize();
			
			$this->intranet = IsModuleInstalled('intranet');

			if (!$this->intranet)
				CModule::IncludeModule("socialnetwork");
		}

		public function ReceiveMessage($senderJId, array $arMessage, CXMPPClient $senderClient)
		{
			if (!$senderClient->IsAuthenticated())
				return false;
			if (strlen($senderJId) <= 0)
				return false;

			if (!array_key_exists("iq", $arMessage))
				return false;

			if ($arMessage['iq']['query']['.']['xmlns'] != 'jabber:iq:roster'
				&& ($arMessage['iq']['query']['.']['xmlns'] != 'jabber:iq:private' || $arMessage['iq']['query']['roster']['.']['xmlns'] != 'roster:delimiter'))
				return false;

			if ($arMessage['iq']['query']['.']['xmlns'] == 'jabber:iq:roster')
			{
				$arResult = array(
					"iq" => array(
						"." => array(
							"type" => "result",
							"to" => $senderJId,
							"id" => $arMessage['iq']['.']['id'],
						),
						"query" => array(
							"." => array(
								"xmlns" => $arMessage['iq']['query']['.']['xmlns'],
							),
						),
					),
				);

				if ($this->intranet)
				{
					$clientDomain = $senderClient->GetClientDomain();
					if (!array_key_exists($clientDomain, $this->rosterCache))
						$this->rosterCache[$clientDomain] = array();
					if (!array_key_exists($clientDomain, $this->rosterCacheAlt))
						$this->rosterCacheAlt[$clientDomain] = array();

					if ($senderClient->IsSubGroupsSupported() && count($this->rosterCache[$clientDomain]) <= 0
						|| !$senderClient->IsSubGroupsSupported() && count($this->rosterCacheAlt[$clientDomain]) <= 0)
					{
						$arDepCache = array();
						$arDepCacheValue = array();

						$dbUsers = CUser::GetList($b = "LAST_NAME", $o = "asc", array("ACTIVE" => "Y", "!UF_DEPARTMENT"=>false), array('SELECT' => array('UF_*')));
						while ($arUser = $dbUsers->Fetch())
						{
							$arT = array(
								"." => array(
									"subscription" => "both",
									"name" => CUser::FormatName($this->nameTemplate, $arUser),
									"jid" => CXMPPUtility::GetJId($arUser, $clientDomain),
								),
							);

							if (is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) > 0)
							{
								$arNewDep = array_diff($arUser['UF_DEPARTMENT'], $arDepCache);

								if (count($arNewDep) > 0)
								{
									$dbRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('ID' => $arNewDep));
									while ($arSect = $dbRes->Fetch())
									{
										$arDepCache[] = $arSect['ID'];

										$dbRes1 = CIBlockSection::GetNavChain($arSect['IBLOCK_ID'], $arSect['ID']);
										while ($arSect1 = $dbRes1->Fetch())
										{
											if ($senderClient->IsSubGroupsSupported())
											{
												if (strlen($arDepCacheValue[$arSect['ID']]) > 0)
													$arDepCacheValue[$arSect['ID']] .= "/";
												$arDepCacheValue[$arSect['ID']] .= htmlspecialcharsbx($arSect1['NAME']);
											}
											else
											{
												if (strlen($arDepCacheValue[$arSect['ID']]) > 0)
													$arDepCacheValue[$arSect['ID']] = "/".$arDepCacheValue[$arSect['ID']];
												$arDepCacheValue[$arSect['ID']] = htmlspecialcharsbx($arSect1['NAME']).$arDepCacheValue[$arSect['ID']];
											}
										}
									}
								}

								foreach ($arUser['UF_DEPARTMENT'] as $key => $sect)
								{
									if (strlen($arDepCacheValue[$sect]) > 0)
									{
										$arT["group"][] = array("#" => $arDepCacheValue[$sect]);
										if (!is_array($GLOBALS["BX_JHGDHGFJKDFSDG67"]))
											$GLOBALS["BX_JHGDHGFJKDFSDG67"] = array();
										if (!is_array($GLOBALS["BX_JHGDHGFJKDFSDG67"][$clientDomain]))
											$GLOBALS["BX_JHGDHGFJKDFSDG67"][$clientDomain] = array();
										if (!in_array($arDepCacheValue[$sect], $GLOBALS["BX_JHGDHGFJKDFSDG67"][$clientDomain]))
											$GLOBALS["BX_JHGDHGFJKDFSDG67"][$clientDomain][] = $arDepCacheValue[$sect];
									}
								}
							}

							if ($senderClient->IsSubGroupsSupported())
								$this->rosterCache[$clientDomain][] = $arT;
							else
								$this->rosterCacheAlt[$clientDomain][] = $arT;
						}
					}

					if ($senderClient->IsSubGroupsSupported())
					{
						foreach ($this->rosterCache[$clientDomain] as $ar)
						{
							if ($senderClient->GetJId() != $ar["."]["jid"])
								$arResult["iq"]["query"]["item"][] = $ar;
						}
					}
					else
					{
						foreach ($this->rosterCacheAlt[$clientDomain] as $ar)
						{
							if ($senderClient->GetJId() != $ar["."]["jid"])
								$arResult["iq"]["query"]["item"][] = $ar;
						}
					}
				}
				else
				{
					$ar = array();

					$dbFriends = CSocNetUserRelations::GetRelatedUsers($senderClient->GetId(), SONET_RELATIONS_FRIEND);
					while ($arFriends = $dbFriends->GetNext())
					{
						$pref = (($senderClient->GetId() == $arFriends["FIRST_USER_ID"]) ? "SECOND" : "FIRST");

						$name = CUser::FormatName($this->nameTemplate, 
							array("NAME" 		=> $arFriends[$pref."_USER_NAME"],
								"LAST_NAME" 	=> $arFriends[$pref."_USER_LAST_NAME"],
								"SECOND_NAME" 	=> $arFriends[$pref."_USER_SECOND_NAME"],
								"LOGIN" 		=> $arFriends[$pref."_USER_LOGIN"],
								), true);

						$arT = array(
							"." => array(
								"subscription" => "both",
								"name" => $name,
								"jid" => CXMPPUtility::GetJId(array("LOGIN" => $arFriends[$pref."_USER_LOGIN"]), $senderClient->GetClientDomain()),
							),
						);

						$ar[] = $arT;
					}

					$arResult["iq"]["query"]["item"] = $ar;
				}

				$senderClient->Send($arResult);

				$server = CXMPPServer::GetServer();
				$server->SendPresenceMessages($senderJId, $senderClient->GetClientDomain());
				$server->SendPresenceMessages2($senderJId, $senderClient->GetClientDomain());
			}
			elseif ($arMessage['iq']['query']['.']['xmlns'] == 'jabber:iq:private' && $arMessage['iq']['query']['roster']['.']['xmlns'] == 'roster:delimiter')
			{
				$arResult = array(
					"iq" => array(
						"." => array(
							"type" => "result",
							"to" => $senderJId,
							"id" => $arMessage['iq']['.']['id'],
						),
						"query" => array(
							"." => array("xmlns" => 'jabber:iq:private',),
							"roster" => array(
								"." => array("xmlns" => 'roster:delimiter',),
								"#" => "/",
							),
						),
					),
				);

				$senderClient->SetSubGroupsSupport(true);

				$senderClient->Send($arResult);
			}

			return true;
		}

		public function ClearCaches()
		{
			$this->rosterCache = array();
			$this->rosterCacheAlt = array();
		}
	}
}
?>
