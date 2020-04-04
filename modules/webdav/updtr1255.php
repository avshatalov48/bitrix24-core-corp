<?
global $USER_FIELD_MANAGER;

if (
	$USER_FIELD_MANAGER->GetUserType("webdav_element_history")
	&& CModule::IncludeModule("webdav")
)
{
	$rsData = CUserTypeEntity::GetList(
		array($by=>$order),
		array(
			"ENTITY_ID" => "BLOG_COMMENT",
			"XML_ID" => "UF_BLOG_COMMENT_FH"
		)
	);
	$arRes = $rsData->Fetch();
	if (!$arRes)
	{
		$arFieldProps = Array(
			"USER_TYPE_ID" => "webdav_element_history",
			"SORT" => 100,
			"MULTIPLE" => "N",
			"MANDATORY" => "N",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "N",
			"EDIT_IN_LIST" => "Y",
			"IS_SEARCHABLE" => "N",
			"SETTINGS" => array(),
			"EDIT_FORM_LABEL" => "",
			"LIST_COLUMN_LABEL" => "",
			"LIST_FILTER_LABEL" => "",
			"ERROR_MESSAGE" => "",
			"HELP_MESSAGE" => "",
			"ENTITY_ID" => "BLOG_COMMENT",
			"FIELD_NAME" => "UF_BLOG_COMMENT_FH",
			"XML_ID" => "UF_BLOG_COMMENT_FH"
		);

		$obUserField  = new CUserTypeEntity;
		$propID = $obUserField->Add($arFieldProps, false);
		if($propID)
		{
			return true;
		}
	}
	return true;
}
return false;
