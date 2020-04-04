<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
//	ClearVars();
if(!CModule::IncludeModule("iblock")) 
	return;
	
if(!CModule::IncludeModule("wiki")) 
	return;		

COption::SetOptionString('wiki','image_max_width','600');
COption::SetOptionString('wiki','image_max_height','600');
COption::SetOptionString('wiki','allow_html','Y');		
		

$APPLICATION->SetGroupRight("wiki", 1, "Y");
$APPLICATION->SetGroupRight("wiki", 2, "R");
$APPLICATION->SetGroupRight("wiki", WIZARD_PORTAL_ADMINISTRATION_GROUP, "Y");
$APPLICATION->SetGroupRight("wiki", WIZARD_DIRECTION_GROUP, "Y");
$APPLICATION->SetGroupRight("wiki", WIZARD_PERSONNEL_DEPARTMENT_GROUP, "Y");
$APPLICATION->SetGroupRight("wiki", WIZARD_EMPLOYEES_GROUP, "W");


$strWarning = "";
$bVarsFromForm = false;


$arIBTLang = array();
$arLang = array();
$l = CLanguage::GetList($lby="sort", $lorder="asc");
while($ar = $l->ExtractFields("l_"))
	$arIBTLang[]=$ar;

for($i=0; $i<count($arIBTLang); $i++)
	$arLang[$arIBTLang[$i]["LID"]] = array("NAME" => GetMessage('WIKI_IBLOCK_TYPE_NAME'));

$arFields = array(
	"ID" => 'wiki',
	"LANG" => $arLang,
	"SECTIONS" => "Y");

$obBlocktype = new CIBlockType;
$IBLOCK_TYPE_ID = $obBlocktype->Add($arFields);
if (strLen($IBLOCK_TYPE_ID) <= 0)
{
	$strWarning .= $obBlocktype->LAST_ERROR;
	$bVarsFromForm = true;
}

if (!$IBLOCK_TYPE_ID)
    $IBLOCK_TYPE_ID = 'wiki';

if ($IBLOCK_TYPE_ID)
{
	$arFields = Array(
		"ACTIVE"=>"Y",
		"NAME"=>GetMessage('WIKI_IBLOCK_NAME'),
		"CODE"=>"wiki",
		"IBLOCK_TYPE_ID"=>$IBLOCK_TYPE_ID,
        "XML_ID" => 'portal_wiki',
		"LID"=>array(),
	    "DETAIL_PAGE_URL" => "#SITE_DIR#/services/wiki/#EXTERNAL_ID#/",
	    "SECTION_PAGE_URL" => "#SITE_DIR#/services/wiki/category:#EXTERNAL_ID#/",
	    "LIST_PAGE_URL" => "#SITE_DIR#/services/wiki/",
	    "GROUP_ID" => Array('1' => 'X', "2" => "R", "3" => "W", WIZARD_PORTAL_ADMINISTRATION_GROUP => "W", WIZARD_DIRECTION_GROUP => "W", WIZARD_PERSONNEL_DEPARTMENT_GROUP => "W", WIZARD_EMPLOYEES_GROUP => "W")
	);
	
	if (IsModuleInstalled('bizproc'))
	{
                $arFields['WORKFLOW'] = 'N';
                $arFields['BIZPROC'] = 'Y';
	}
	
	$ib = new CIBlock;
    $rsIBlock = CIBlock::GetList(array(), array('XML_ID' => 'portal_wiki'));
    $arIBlock = $rsIBlock->Fetch();
         
    if (empty($arIBlock))
    {
		$db_sites = CSite::GetList($lby="sort", $lorder="asc");
		while ($ar_sites = $db_sites->Fetch())
		{
			if ($ar_sites["ACTIVE"] == "Y")
            	$arFields["LID"][] = $ar_sites["LID"];
                $arSites[] = $ar_sites;
            }
                
            if (empty($arFields["LID"]))
            	$arFields["LID"][] = $ar_sites[0]["LID"];
            if (!empty($arUGroupsEx))
            	$arFields["GROUP_ID"] = $arUGroupsEx;
                
            $ID = $ib->Add($arFields);
            if($ID <= 0)
            {
            	$strWarning .= $ib->LAST_ERROR."<br>";
            	$bVarsFromForm = true;
            }
            else
            {
            }
         } 
         else 
         	$ID = $arIBlock['ID'];
	}

	$arReplaceParam = array("IBLOCK_ID" => $ID, 'IBLOCK_TYPE' => $IBLOCK_TYPE_ID);	
		
	if (IsModuleInstalled("forum"))
	{
		CModule::IncludeModule("forum");
		
		$arGroupID = Array(
			"HIDDEN" => 0,
		);
    	$dbExistsGroup = CForumGroup::GetListEx(array(), array("LID" => LANGUAGE_ID));
		while ($arExistsGroup = $dbExistsGroup->Fetch())
		{
			foreach ($arGroupID as $xmlID => $ID)
			{
				if ($arExistsGroup["NAME"] == GetMessage($xmlID."_GROUP_NAME") )
					$arGroupID[$xmlID] = $arExistsGroup["ID"];
			}
		}
			
        $arFields = Array(
			"ACTIVE" => "Y", 
			"NAME" => GetMessage('WIKI_FORUM_NAME'),
			"XML_ID" => "WIKI",
			"FORUM_GROUP_ID" => $arGroupID["HIDDEN"],    
			"GROUP_ID" => array(1 => "Y", 2 => 'E', 3 => 'M', WIZARD_PORTAL_ADMINISTRATION_GROUP => "M", WIZARD_DIRECTION_GROUP => "M", WIZARD_PERSONNEL_DEPARTMENT_GROUP => "M", WIZARD_EMPLOYEES_GROUP => "M"),    
			"SITES" => Array(
		   		WIZARD_SITE_ID => WIZARD_SITE_DIR."community/forum/forum#FORUM_ID#/topic#TOPIC_ID#/"
		   ),
		);
    
        $FORUM_ID = CForumNew::Add($arFields);
        $arReplaceParam['FORUM_ID'] = $FORUM_ID;	        		
        $arReplaceParam['USE_REVIEW'] = 'Y';	        		
	} 
	else 
	{
	    $arReplaceParam['USE_REVIEW'] = 'N';
	    $arReplaceParam['FORUM_ID'] = '0';
	}

	if (IsModuleInstalled("socialnetwork"))
	{
	    
	    if ($IBLOCK_TYPE_ID)
		{
			$arFields = Array(
				"ACTIVE"=>"Y",
				"NAME"=>GetMessage('WIKI_IBLOCK_SOCNET_NAME'),
				"CODE"=>"wiki_group",
				"IBLOCK_TYPE_ID"=>$IBLOCK_TYPE_ID,
            	"XML_ID" => 'portal_socnet_wiki',
				"LID"=>array(),
			    "DETAIL_PAGE_URL" => "",
			    "SECTION_PAGE_URL" => "",
			    "LIST_PAGE_URL" => "",
			    "INDEX_ELEMENT" => 'N',
			    "INDEX_SECTION" => 'N',
			    "GROUP_ID" => Array('1' => 'X', "2"=>"R", "3"=>"W", WIZARD_PORTAL_ADMINISTRATION_GROUP => "W", WIZARD_DIRECTION_GROUP => "W", WIZARD_PERSONNEL_DEPARTMENT_GROUP => "W", WIZARD_EMPLOYEES_GROUP => "W")
			);
			
			if (IsModuleInstalled('bizproc'))
			{
                $arFields['WORKFLOW'] = 'N';
                $arFields['BIZPROC'] = 'Y';
			}
			
		$ib = new CIBlock;
        $rsIBlock = CIBlock::GetList(array(), array('XML_ID' => 'portal_socnet_wiki'));
        $arIBlock = $rsIBlock->Fetch();
			
		if (empty($arIBlock))
         {         
            $db_sites = CSite::GetList($lby="sort", $lorder="asc");
            while ($ar_sites = $db_sites->Fetch())
            {
               if ($ar_sites["ACTIVE"] == "Y")
                  $arFields["LID"][] = $ar_sites["LID"];
               $arSites[] = $ar_sites;
            }
            
            if (empty($arFields["LID"]))
               $arFields["LID"][] = $ar_sites[0]["LID"];
            if (!empty($arUGroupsEx))
               $arFields["GROUP_ID"] = $arUGroupsEx;

            $SOCNET_ID = $ib->Add($arFields);
            if($SOCNET_ID <= 0)
            {
               $strWarning .= $ib->LAST_ERROR."<br>";
               $bVarsFromForm = true;
            }
            else
            {
            }
         } 
         else 
            $SOCNET_ID = $arIBlock['ID'];            
		}	    
	    
        COption::SetOptionString("wiki", "socnet_iblock_type_id", $IBLOCK_TYPE_ID);
        COption::SetOptionString("wiki", "socnet_iblock_id", $SOCNET_ID);
        COption::SetOptionString("wiki", "socnet_enable", 'Y');
        CWikiSocnet::EnableSocnet(true);	    
	    
		if (IsModuleInstalled("forum"))
    	{
    		CModule::IncludeModule("forum");
    		
    		$arGroupID = Array(
				"GENERAL" => 0,
				"COMMENTS" => 0,
				"HIDDEN" => 0,
			);
	    	$dbExistsGroup = CForumGroup::GetListEx(array(), array("LID" => LANGUAGE_ID));
			while ($arExistsGroup = $dbExistsGroup->Fetch())
			{
				foreach ($arGroupID as $xmlID => $ID)
				{
					if ($arExistsGroup["NAME"] == GetMessage($xmlID."_GROUP_NAME") )
						$arGroupID[$xmlID] = $arExistsGroup["ID"];
				}
			}
    		
            $arFields = Array(
               "ACTIVE" => "Y", 
               "NAME" => GetMessage('WIKI_FORUM_SOCNET_NAME'),
		"XML_ID" => 'WIKI_GROUP_COMMENTS',
               "FORUM_GROUP_ID" => $arGroupID["HIDDEN"],   
               "GROUP_ID" => array(1 => "Y", 2 => 'E', 3 => 'M', WIZARD_PORTAL_ADMINISTRATION_GROUP => "M", WIZARD_DIRECTION_GROUP => "M", WIZARD_PERSONNEL_DEPARTMENT_GROUP => "M", WIZARD_EMPLOYEES_GROUP => "M"),    
	           "SITES" => Array(
					WIZARD_SITE_ID => WIZARD_SITE_DIR."community/forum/messages/forum#FORUM_ID#/topic#TOPIC_ID#/message#MESSAGE_ID#/#message#MESSAGE_ID#",
			   ),
            );
            
        
            $SOCNET_FORUM_ID = CForumNew::Add($arFields);
            COption::SetOptionString("wiki", "socnet_forum_id", $SOCNET_FORUM_ID, false, WIZARD_SITE_ID);
            COption::SetOptionString("wiki", "socnet_use_review", "Y", false, WIZARD_SITE_ID);
            COption::SetOptionString("wiki", "socnet_use_captcha", "Y", false, WIZARD_SITE_ID);
            COption::SetOptionString("wiki", "socnet_message_per_page", 10, false, WIZARD_SITE_ID);
            
    	} 
    	else
    	{
    	    COption::SetOptionString("wiki", "socnet_use_review", "N", false, WIZARD_SITE_ID);
    	}	    
	}		
    
?>