<?
IncludeModuleLangFile(__FILE__);

define("ADMIN_MODULE_NAME", "controller");
define("ADMIN_MODULE_ICON", "<img src=\"/bitrix/images/controller/big.gif\" width=\"48\" height=\"48\" border=\"0\" alt=\"".GetMessage("CTRLR_MODULE_ICON_ALT")."\" title=\"".GetMessage("CTRLR_MODULE_ICON_ALT")."\">");

function adminListAddUserLink(CAdminListRow $row, $name, $user_id, $label)
{
	$user_id = intval($user_id);
	$htmlLink = 'user_edit.php?lang='.LANGUAGE_ID.'&ID='.$user_id;
	$row->AddViewField($name, '[<a href="'.htmlspecialcharsbx($htmlLink).'">'.$user_id.'</a>] '.htmlspecialcharsEx($label));
}
?>
