<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

	// create rule
	$bRunRule = false;
	$bCreateRule = true;
	$rsData = $DB->Query("SELECT ID, ACTIVE FROM b_rating_rule WHERE CONDITION_MODULE = 'intranet'");
	while($arRule = $rsData->Fetch())
	{
		if ($arRule["ACTIVE"] == "N")
			$DB->Query("DELETE FROM b_rating_rule WHERE ID = ".$arRule["ID"], true);
		else
		{
			COption::SetOptionString("intranet", "ratingSubordinateId", $arRule['ID']);	
			CRatingRule::Apply($arRule['ID']);
			$bCreateRule = false;
		}
	}
	
	// after autority
	if ($bCreateRule)
	{
		$dbRes = CLanguage::GetList();
		while ($arRes = $dbRes->Fetch())
		{
			if (file_exists(__DIR__.'/'.$arRes['LID'].'/rating.php'))
				require(__DIR__.'/'.$arRes['LID'].'/rating.php');
		}
		$arFields = Array(
			"ACTIVE" 			=> "Y",
			"NAME" 				=> $MESS['INTR_INSTALL_RATING_RULE'],
			"ENTITY_TYPE_ID"	=> "USER",
			"CONDITION_NAME"	=> "SUBORDINATE",
			"CONDITION_MODULE" 	=> "intranet",
			"CONDITION_CLASS" 	=> "CRatingRulesIntranet",
			"CONDITION_METHOD" 	=> "subordinateCheck",
			"CONDITION_CONFIG" 	=> Array(					
				"SUBORDINATE" => Array(
				),
			),
			"ACTION_NAME" => "empty",
			"ACTION_CONFIG" => Array(),
			"ACTIVATE" 			=> "N",
			"ACTIVATE_CLASS"	=> "empty",
			"ACTIVATE_METHOD" 	=> "empty",
			"DEACTIVATE" 		=> "N",
			"DEACTIVATE_CLASS"  => "empty ",
			"DEACTIVATE_METHOD" => "empty",
			"~CREATED"			=> $DB->GetNowFunction(),
			"~LAST_MODIFIED"	=> $DB->GetNowFunction(),
		);
		$arFields["CONDITION_CONFIG"] = serialize($arFields["CONDITION_CONFIG"]);
		$arFields["ACTION_CONFIG"] = serialize($arFields["ACTION_CONFIG"]);
		$ID = $DB->Add("b_rating_rule", $arFields, array("ACTION_CONFIG", "CONDITION_CONFIG"));
		
		COption::SetOptionString("intranet", "ratingSubordinateId", $ID);	
		CRatingRule::Apply($ID);
	}
	
	// recount ratings
	$rsData = CRatings::GetList(array('ID'=>'ASC'), array());
	while($arRes = $rsData->Fetch())
	{
		CRatings::Calculate($arRes['ID'], true);
	}
?>