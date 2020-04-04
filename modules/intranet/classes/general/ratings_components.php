<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/intranet/classes/general/ratings_components.php");

class CAllRatingsComponentsIntranet
{	
	// return configs of component-rating
	function OnGetRatingConfigs()
	{
	    $arConfigs = array(
	    	'MODULE_ID' => 'INTRANET',
	    	'MODULE_NAME' => GetMessage('INTRANET_RATING_NAME'),
	    );
		$arConfigs["COMPONENT"]["USER"]["RATING"][] = array(
		   "ID"	=> 'SUBORDINATE',
			"REFRESH_TIME"	=> '3600',
			"CLASS"	=> 'CRatingsComponentsIntranet',
			"CALC_METHOD"	=> 'CalcSubordinateBonus',						
			"NAME" => GetMessage('INTRANET_RATING_USER_SUBORDINATE_NAME'),
			"DESC" => GetMessage('INTRANET_RATING_USER_SUBORDINATE_DESC'),
			"FORMULA" => "(SubordinateValue + StartValue) * K",
			"FORMULA_DESC" => GetMessage('INTRANET_RATING_USER_SUBORDINATE_FORMULA_DESC'),
		    "FIELDS" => array(
				array(
					"ID" => 'COEFFICIENT',
					"DEFAULT" => '1',
				),
			)
		);		
		return $arConfigs;
	}

	// check input values, if value does not validate, set the default value
	function __CheckFields($entityId, $arConfigs)
	{
		$arDefaultConfig = CRatingsComponentsIntranet::__AssembleConfigDefault($entityId);
		
		if ($entityId == "USER") {
			if (isset($arConfigs['RATING']['SUBORDINATE']))
			{
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['SUBORDINATE']['COEFFICIENT']))
					$arConfigs['RATING']['SUBORDINATE']['COEFFICIENT'] = $arDefaultConfig['RATING']['SUBORDINATE']['COEFFICIENT']['DEFAULT'];
			}
		}	
		return $arConfigs;
	}
		
	// return support object
	function OnGetRatingObject()
	{
		$arRatingConfigs = CRatingsComponentsIntranet::OnGetRatingConfigs();
		foreach ($arRatingConfigs["COMPONENT"] as $SupportType => $value)
			$arSupportType[] = $SupportType;
			
		return $arSupportType;
	}
	
	// check the value of the component-rating which relate to the module
	function OnAfterAddRating($ID, $arFields)
	{
		$arFields['CONFIGS']['INTRANET'] = CRatingsComponentsIntranet::__CheckFields($arFields['ENTITY_ID'], $arFields['CONFIGS']['INTRANET']);
		
		return $arFields;
	}
	
	// check the value of the component-rating which relate to the module
	function OnAfterUpdateRating($ID, $arFields)
	{
		$arFields['CONFIGS']['INTRANET'] = CRatingsComponentsIntranet::__CheckFields($arFields['ENTITY_ID'], $arFields['CONFIGS']['INTRANET']);
		
		return $arFields;
	}
	
	// Utilities
		
	// collect the default and regular expressions for the fields component-rating
	function __AssembleConfigDefault($objectType = null) 
	{
		$arConfigs = array();
		$arRatingConfigs = CRatingsComponentsIntranet::OnGetRatingConfigs();
		if (is_null($objectType)) 
		{
			foreach ($arRatingConfigs["COMPONENT"] as $OBJ_TYPE => $TYPE_VALUE)
				foreach ($TYPE_VALUE as $RAT_TYPE => $RAT_VALUE)
					foreach ($RAT_VALUE as $VALUE_CONFIG)
				   		foreach ($VALUE_CONFIG['FIELDS'] as $VALUE_FIELDS) 
				   		   $arConfigs[$OBJ_TYPE][$RAT_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT'] = $VALUE_FIELDS['DEFAULT'];
		}
		else 
		{
			foreach ($arRatingConfigs["COMPONENT"][$objectType] as $RAT_TYPE => $RAT_VALUE)
				foreach ($RAT_VALUE as $VALUE_CONFIG)
					foreach ($VALUE_CONFIG['FIELDS'] as $VALUE_FIELDS) 
				   		$arConfigs[$RAT_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT'] = $VALUE_FIELDS['DEFAULT'];

		}
		return $arConfigs;
	}	
}
?>