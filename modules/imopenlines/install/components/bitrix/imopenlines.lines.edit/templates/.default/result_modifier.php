<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!function_exists('getImolDestInputElement'))
{
	function getImolDestInputElement($canEdit = true)
	{
		ob_start();
		?>
		<span data-id="%data_id%" class="%dest_input_container_class%">
		<span class="bx-destination-text">%user_name_default%</span>
	</span>
		<?
		return ob_get_clean();
	}
}

if (!function_exists('getImolUserDataInputElement'))
{
	function getImolUserDataInputElement($canEdit = true)
	{
		ob_start();
		?>
		<div class="%user_data_input_container_class%" data-id="%data_id%">
			<div class="imopenlines-form-settings-user-info">
				<span class="imopenlines-form-settings-user-img" style="background-image: url(%user_avatar_default%)"></span>
				<span class="imopenlines-form-settings-user-name">%user_name_default%</span>
			</div>
			<div class="imopenlines-form-settings-user-add">
			<span class="imopenlines-form-settings-user-img imopenlines-form-settings-user-img-add"
				  id="button-avatar-user-%data_id%"
				  style="background-image: url(%user_avatar_show%)"></span>
				<div class="imopenlines-form-settings-user-input-block">
					<input class="imopenlines-form-settings-user-input"
						   type="text"
						   placeholder="<?=Loc::getMessage("IMOL_CONFIG_QUEUE_USER_NAME_PLACEHOLDER")?>"
						   value="%user_name%"
						   name="USER_NAME"
						   <?if (!$canEdit) { ?>disabled="disabled"<? } ?>>
					<input class="imopenlines-form-settings-user-input"
						   type="text"
						   placeholder="<?=Loc::getMessage("IMOL_CONFIG_QUEUE_USER_WORK_POSITION_PLACEHOLDER")?>"
						   name="USER_WORK_POSITION"
						   value="%user_work_position%"
						   <?if (!$canEdit) { ?>disabled="disabled"<? } ?>>
					<input class="imopenlines-form-settings-user-input"
						   type="hidden"
						   name="USER_AVATAR"
						   id="input-avatar-user-%data_id%"
						   value="%user_avatar%"
						   <?if (!$canEdit) { ?>disabled="disabled"<? } ?>>
					<input class="imopenlines-form-settings-user-input"
						   type="hidden"
						   name="USER_AVATAR_ID"
						   id="input-avatar-file-id-user-%data_id%"
						   value="%user_avatar_file_id%"
						   <?if (!$canEdit) { ?>disabled="disabled"<? } ?>>
				</div>
			</div>
		</div>
		<?
		return ob_get_clean();
	}
}

if (!function_exists('getImolDefaultUserDataInputElement'))
{
	function getImolDefaultUserDataInputElement($canEdit = true)
	{
		ob_start();
		?>
		<div class="imopenlines-form-settings-user-add">
			<span class="imopenlines-form-settings-user-img imopenlines-form-settings-user-img-add"
				  id="button-avatar-user-default-user"
				  style="background-image: url(%user_avatar_show%)"></span>
			<div class="imopenlines-form-settings-user-input-block">
				<input class="imopenlines-form-settings-user-input"
					   style="margin-top: 25px;"
					   type="text"
					   placeholder="<?=Loc::getMessage("IMOL_CONFIG_QUEUE_USER_NAME_PLACEHOLDER")?>"
					   value="%user_name%"
					   name="CONFIG[DEFAULT_OPERATOR_DATA][NAME]"
					   <?if (!$canEdit) { ?>disabled="disabled"<? } ?>>
				<input class="imopenlines-form-settings-user-input"
					   type="hidden"
					   name="CONFIG[DEFAULT_OPERATOR_DATA][AVATAR]"
					   id="input-avatar-user-default-user"
					   value="%user_avatar%"
					   <?if (!$canEdit) { ?>disabled="disabled"<? } ?>>
				<input class="imopenlines-form-settings-user-input"
					   type="hidden"
					   name="CONFIG[DEFAULT_OPERATOR_DATA][AVATAR_ID]"
					   id="input-avatar-file-id-user-default-user"
					   value="%user_avatar_file_id%"
					   <?if (!$canEdit) { ?>disabled="disabled"<? } ?>>
			</div>
		</div>
		<?
		return ob_get_clean();
	}
}

if (!function_exists('getImolUserDataAvatarTemplate'))
{
	function getImolUserDataAvatarTemplate()
	{
		ob_start();
		?>
		<span data-imopenlines-user-photo-edit-avatar-item="" data-file-id="%file_id%" data-path="%path%" class="imopenlines-user-photo-upload-item-added-completed-item">
			<span data-remove="" class="imopenlines-user-photo-upload-item-remove"></span>
			<span data-view="" style="background-image: url(%path%)" class="imopenlines-user-photo-upload-item"></span>
			<span class="imopenlines-user-photo-upload-item-selected"></span>
		</span>
		<?
		return ob_get_clean();
	}
}

$arResult["HTML"]["DEST_INPUT_ELEMENTS"] = "";
$arResult["HTML"]["USER_DATA_INPUT_ELEMENTS"] = "";
$arResult["HTML"]["DEFAULT_OPERATOR_DATA"] = "";
if (!$arResult["CAN_EDIT"])
{
	foreach ($arResult["QUEUE_DESTINATION"]["SELECTED"]["USERS"] as $userId)
	{
		$dataId = "U" . $userId;
		$defaultUser = $arResult["QUEUE_DESTINATION"]["USERS"][$dataId];
		$queueUser = $arResult["QUEUE_DESTINATION"]["QUEUE_USERS_FIELDS"][$dataId];
		$searchReplace = array(
			"%data_id%" => $dataId,
			"%user_name_default%" => $defaultUser["name"],
			"%user_avatar_default%" => str_replace(' ', '%20', $defaultUser["avatar"]),
			"%user_name%" => !empty($queueUser["USER_NAME"]) ? $queueUser["USER_NAME"] : $defaultUser["name"],
			"%user_avatar%" => !empty($queueUser["USER_AVATAR"]) ? $queueUser["USER_AVATAR"] : $defaultUser["avatar"],
			"%user_avatar_file_id%" => intval($queueUser["USER_AVATAR_ID"]) > 0 ? $queueUser["USER_AVATAR_ID"] : "",
			"%user_work_position%" => !empty($queueUser["USER_WORK_POSITION"]) ? $queueUser["USER_WORK_POSITION"] : "",
			"%dest_input_container_class%" => "bx-destination bx-destination-users",
			"%user_data_input_container_class%" => "imopenlines-form-settings-user",
		);
		$searchReplace["%user_avatar_show%"] = str_replace(' ', '%20', $searchReplace["%user_avatar%"]);
		$arResult["HTML"]["DEST_INPUT_ELEMENTS"] .= str_replace(array_keys($searchReplace), array_values($searchReplace), getImolDestInputElement(false));
		$arResult["HTML"]["USER_DATA_INPUT_ELEMENTS"] .= str_replace(array_keys($searchReplace), array_values($searchReplace), getImolUserDataInputElement(false));
	}

	$searchReplace = array(
		"%user_name%" => !empty($arResult["CONFIG"]["DEFAULT_OPERATOR_DATA"]["NAME"]) ? $arResult["CONFIG"]["DEFAULT_OPERATOR_DATA"]["NAME"] : "",
		"%user_avatar%" => !empty($arResult["CONFIG"]["DEFAULT_OPERATOR_DATA"]["AVATAR"]) ? $arResult["CONFIG"]["DEFAULT_OPERATOR_DATA"]["AVATAR"] : "",
		"%user_avatar_file_id%" => intval($arResult["CONFIG"]["DEFAULT_OPERATOR_DATA"]["AVATAR_ID"]) > 0 ? $arResult["CONFIG"]["DEFAULT_OPERATOR_DATA"]["AVATAR_ID"] : "",
	);
	$searchReplace["%user_avatar_show%"] = str_replace(' ', '%20', $searchReplace["%user_avatar%"]);
	$arResult["HTML"]["DEFAULT_OPERATOR_DATA"] = str_replace(array_keys($searchReplace), array_values($searchReplace), getImolDefaultUserDataInputElement($arResult["CAN_EDIT"]));
}

$arResult["PANEL_BUTTONS"] = array();
/*if (!$arResult["IFRAME"])
{
	$arResult["PANEL_BUTTONS"][] = array (
		"TYPE" => "save",
		"ONCLICK" => "BX.submit(BX('imol_config_edit_form'))"
	);
}*/
$arResult["PANEL_BUTTONS"][] = array(
	"TYPE" => "save"
);
$arResult["PANEL_BUTTONS"][] = array(
	"TYPE" => "cancel",
	"LINK" =>  $arResult["PATH_TO_LIST"]
);

$arResult["CONFIG_MENU"][$arResult["PAGE"]]["ACTIVE"] = true;
$arResult["PAGE_TITLE"] = $arResult["CONFIG_MENU"][$arResult["PAGE"]]["NAME"];

$serverAddress = \Bitrix\ImOpenLines\Common::getServerAddress() . $this->GetFolder() . "/images/";
$arResult["HELLO"]["ICONS"] = array(
	array("PATH" => $serverAddress . "upload-girl-mini-1.png"),
	array("PATH" => $serverAddress . "upload-girl-mini-2.png"),
	array("PATH" => $serverAddress . "upload-girl-mini-3.png"),
	array("PATH" => $serverAddress . "upload-girl-mini-4.png"),
	array("PATH" => $serverAddress . "upload-man-mini-1.png"),
	array("PATH" => $serverAddress . "upload-man-mini-2.png"),
	array("PATH" => $serverAddress . "upload-man-mini-3.png"),
	array("PATH" => $serverAddress . "upload-man-mini-4.png"),
);