<?php

use Bitrix\Intranet\UserField\Types\EmployeeType;

IncludeModuleLangFile(__FILE__);

class CIEmployeeProperty
{
	static $cache = array();

	public static function _GetUserArray($user_id)
	{
		$user_id = intval($user_id);
		if (!array_key_exists($user_id, self::$cache))
		{
			$rsUsers = CUser::GetList("", "", array("ID_EQUAL_EXACT" => $user_id, '!UF_DEPARTMENT' => false));
			self::$cache[$user_id] = $rsUsers->Fetch();
		}
		return self::$cache[$user_id];
	}

	public static function GetEditForm($value, $strHTMLControlName)
	{
		global $USER, $APPLICATION;

		$name_x = preg_replace("/([^a-z0-9])/is", "_", $strHTMLControlName["VALUE"]);
		if (trim($strHTMLControlName["FORM_NAME"]) == '')
			$strHTMLControlName["FORM_NAME"] = "form_element";

		global $adminSidePanelHelper;
		if (!is_object($adminSidePanelHelper))
		{
			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
			$adminSidePanelHelper = new CAdminSidePanelHelper();
		}

		if ($adminSidePanelHelper->isPublicSidePanel())
		{
			$titleUserId = $USER->GetID();
		}
		else
		{
			$titleUserId = '<a title="'.CUtil::JSEscape(GetMessage("MAIN_EDIT_USER_PROFILE")).'" class="tablebodylink" href="/bitrix/admin/user_edit.php?ID='.$USER->GetID().'&lang='.LANGUAGE_ID.'">'.$USER->GetID().'</a>';
		}

		$selfFolderUrl = (defined("SELF_FOLDER_URL") ? SELF_FOLDER_URL : "/bitrix/admin/");

		ob_start();
		?>
<input type="text" name="<?echo htmlspecialcharsbx($strHTMLControlName["VALUE"])?>" id="<?echo $name_x?>" value="<?echo intval($value['VALUE']) > 0 ? intval($value['VALUE']) : ''?>" size="3" class="typeinput" />&nbsp;&nbsp;<?
		$APPLICATION->IncludeComponent('bitrix:intranet.user.search', '', array(
			'INPUT_NAME' => $name_x,
			'MULTIPLE' => 'N',
			'SHOW_BUTTON' => 'Y',
		), null, array('HIDE_ICONS' => 'Y'))?><IFRAME style="width:0; height:0; border: 0; display: none;" src="javascript:void(0)" name="hiddenframe<?echo htmlspecialcharsbx($strHTMLControlName["VALUE"])?>" id="hiddenframe<?=$name_x?>"></IFRAME><span id="div_<?=$name_x?>"></span>
<script>
var value_<?=$name_x?> = '';
function Ch<?=$name_x?>()
{
	var DV_<?=$name_x?> = document.getElementById("div_<?=$name_x?>");
	if (document.getElementById('<?echo $name_x?>'))
	{
		var old_value = value_<?=$name_x?>;
		value_<?=$name_x?>=parseInt(document.getElementById('<?echo $name_x?>').value);
		if (value_<?=$name_x?> > 0)
		{
			if (old_value != value_<?=$name_x?>)
			{
				DV_<?=$name_x?>.innerHTML = '<i><? echo CUtil::JSEscape(GetMessage("MAIN_WAIT"))?></i>';
				if (value_<?=$name_x?> != <?echo intval($USER->GetID())?>)
				{
					document.getElementById("hiddenframe<?=$name_x?>").src='<?=$selfFolderUrl; ?>get_user.php?ID=' + value_<?=$name_x?>+'&strName=<?=$name_x?>&lang=<? echo LANGUAGE_ID.(defined("ADMIN_SECTION") && ADMIN_SECTION===true?"&admin_section=Y":"")?>';
				}
				else
				{
					DV_<?=$name_x?>.innerHTML = '[<?=$titleUserId?>] (<?echo CUtil::JSEscape(htmlspecialcharsbx($USER->GetLogin()))?>) <? echo CUtil::JSEscape(htmlspecialcharsbx($USER->GetFirstName().' '.$USER->GetLastName()))?>';
				}
			}

		}
		else
		{
			DV_<?=$name_x?>.innerHTML = '';
		}
	}
	setTimeout(function(){Ch<?=$name_x?>()},1000);
}
Ch<?=$name_x?>();
//-->
</script>
<?
			$return = ob_get_contents();
			ob_end_clean();
		return  $return;


	}

	public static function GetAdminListViewHTML($value)
	{
		$arUser = CIEmployeeProperty::_GetUserArray($value["VALUE"]);
		if($arUser)
		{
			if (defined("PUBLIC_MODE") && PUBLIC_MODE == 1)
			{
				$titleUserId = $arUser["ID"];
			}
			else
			{
				$titleUserId = "<a title='".GetMessage("MAIN_EDIT_USER_PROFILE")."' href='user_edit.php?ID=".$arUser["ID"]."&lang=".LANGUAGE_ID."'>".$arUser["ID"]."</a>";
			}

			return "[".$titleUserId."] (".htmlspecialcharsbx($arUser["LOGIN"]).") ".htmlspecialcharsbx($arUser["NAME"])." ".htmlspecialcharsbx($arUser["LAST_NAME"]);
		}
		else
		{
			return "&nbsp;";
		}
	}

	public static function GetPublicViewHTML($value)
	{
		$arUser = CIEmployeeProperty::_GetUserArray($value["VALUE"]);
		if($arUser)
		{
			return "(".htmlspecialcharsbx($arUser["LOGIN"]).") ".htmlspecialcharsbx($arUser["NAME"])." ".htmlspecialcharsbx($arUser["LAST_NAME"]);
		}
		else
		{
			return "&nbsp;";
		}
	}
}

class CUserTypeEmployee extends CIEmployeeProperty
{
	public static function getUserTypeDescription()
	{
		return EmployeeType::getUserTypeDescription();
	}

	public static function getDbColumnType()
	{
		return EmployeeType::getDbColumnType();
	}

	public static function getPublicText($userField)
	{
		return EmployeeType::renderText($userField);
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		return EmployeeType::renderEditForm($arUserField, $arHtmlControl);
	}

	public static function GetAdminListViewHTML($value)
	{
		$additionalParameters = (func_num_args() > 1 ? func_get_arg(1) : null);
		return EmployeeType::renderAdminListView([], $additionalParameters);
	}

	public static function checkFields($userField, $value)
	{
		return EmployeeType::checkFields($userField, $value);
	}

	public static function onSearchIndex($userField)
	{
		return EmployeeType::onSearchIndex($userField);
	}

	public static function onBeforeSave($userField, $value)
	{
		return EmployeeType::onBeforeSave($userField, $value);
	}
}

class CUserTypeEmployeeDisplay extends \Bitrix\Main\UserField\TypeBase
{
	const USER_TYPE_ID = EmployeeType::USER_TYPE_ID;
	const SELECTOR_CONTEXT = EmployeeUfComponent::SELECTOR_CONTEXT;

	public static function getPublicEdit($userField, $additionalParameters = array())
	{
		return EmployeeType::renderEdit($userField, $additionalParameters);
	}

	public static function getPublicView($userField, $additionalParameters = array())
	{
		return EmployeeType::renderView($userField, $additionalParameters);
	}

	public static function getPublicText($arUserField)
	{
		return EmployeeType::getPublicText($arUserField);
	}
}

class CIBlockPropertyEmployee extends CIEmployeeProperty
{
	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" =>"employee",
			"DESCRIPTION" => GetMessage('INTR_PROP_EMP_TITLE'),
			"GetPropertyFieldHtml" => array("CIBlockPropertyEmployee","GetPropertyFieldHtml"),
			"GetAdminListViewHTML" => array("CIBlockPropertyEmployee","GetAdminListViewHTML"),
			"GetPublicViewHTML" => array("CIBlockPropertyEmployee","GetPublicViewHTML"),
			"GetPublicEditHTML" => array("CIBlockPropertyEmployee","GetPublicEditHTML"),
			"GetPublicEditHTMLMulty" => array("CIBlockPropertyEmployee", "GetPublicEditHTMLMulty"),
			"GetPublicFilterHTML" => array("CIBlockPropertyEmployee","GetPublicFilterHTML"),
			"GetUIFilterProperty" => array(__CLASS__, 'GetUIFilterProperty'),
			"ConvertToDB" => array("CIBlockPropertyEmployee","ConvertFromToDB"),
			"CheckFields" => array("CIBlockPropertyEmployee","CheckFields"),
			"GetLength" => array("CIBlockPropertyEmployee","GetLength"),
			"GetUIEntityEditorProperty" => array("CIBlockPropertyEmployee","GetUIEntityEditorProperty"),
			"GetUIEntityEditorPropertyEditHtml" => array("CIBlockPropertyEmployee","GetUIEntityEditorPropertyEditHtml"),
			"GetUIEntityEditorPropertyViewHtml" => array("CIBlockPropertyEmployee","GetUIEntityEditorPropertyViewHtml"),
		);
	}

	public static function CheckFields($arProperty, $value)
	{
		$error = array();

		$value = trim($value["VALUE"], "\n\r\t ");
		if(!empty($value))
		{
			$value = (int)$value;
			if(empty($value))
			{
				$error[] = GetMessage("INTR_PROP_EMP_VALIDATE_ERROR");
			}
		}

		return $error;
	}

	public static function GetLength($arProperty, $value)
	{
		return mb_strlen(trim($value["VALUE"], "\n\r\t "));
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		return parent::GetEditForm($value, $strHTMLControlName);
	}

	public static function GetAdminListViewHTML($value)
	{
		$value = func_num_args() > 1 ? func_get_arg(1) : null;

		return parent::GetAdminListViewHTML($value);
	}

	public static function GetPublicViewHTML($value)
	{
		$value = func_num_args() > 1 ? func_get_arg(1) : null;

		return parent::GetPublicViewHTML($value);
	}

	public static function GetPublicFilterHTML($arProperty, $strHTMLControlName)
	{
		global $APPLICATION;
		ob_start();

		if(isset($_REQUEST[$strHTMLControlName["VALUE"]]))
			$arUser = parent::_GetUserArray($_REQUEST[$strHTMLControlName["VALUE"]]);
		else
			$arUser = false;

		if ($arUser)
			$UF_HeadName = $arUser["NAME"] == "" && $arUser["LAST_NAME"] == "" ? $arUser["LOGIN"] : $arUser["NAME"]." ".$arUser["LAST_NAME"];
		else
			$UF_HeadName = "";

		$controlID = "Single_" . RandString(6);
		$controlName = $strHTMLControlName['VALUE'];
		?>
		<input type="text" id="<?echo $controlID?>" value="<?if($arUser) echo htmlspecialcharsbx($arUser['ID']);?>" name="<?echo $controlName?>" style="width:35px;font-size:14px;border:1px #c8c8c8 solid;">
		<a href="javascript:void(0)" id="single-user-choice<?echo $controlID?>"><?=GetMessage("INTR_PROP_EMP_SU")?></a>
		<span id="<?echo $controlID?>_name" style="margin-left:15px"><?=htmlspecialcharsex($UF_HeadName)?></span>
		<span id="structure-department-head<?echo $controlID?>" class="structure-department-head" <?if ($UF_HeadName != ""):?>style="visibility:visible"<?endif;?> onclick='BX("<?echo $controlID?>").value = ""; BX("<?echo $controlID?>_name").innerHTML = ""; BX("structure-department-head<?echo $controlID?>").style.visibility="hidden";'></span><br>
		<?CUtil::InitJSCore(array('popup'));?>
		<script type="text/javascript" src="/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js"></script>
		<script type="text/javascript">BX.loadCSS('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css');</script>
		<script>// user_selector:
		var multiPopup<?echo $controlID?>;
		var singlePopup<?echo $controlID?>;
		var taskIFramePopup<?echo $controlID?>;

		function onSingleSelect<?echo $controlID?>(arUser)
		{
			BX("<?echo $controlID?>").value = arUser.id;
			BX("<?echo $controlID?>_name").innerHTML = BX.util.htmlspecialchars(arUser.name);
			BX("structure-department-head<?echo $controlID?>").style.visibility="visible";
		}

		function ShowSingleSelector<?echo $controlID?>(e)
		{
			if(!e) e = window.event;

			if (!singlePopup<?echo $controlID?>)
			{
				singlePopup<?echo $controlID?> = new BX.PopupWindow("single-employee-popup-<?echo $controlID?>", this, {
					offsetTop : 1,
					autoHide : true,
					content : BX("<?=CUtil::JSEscape($controlID)?>_selector_content"),
					zIndex: 3000
				});
			}
			else
			{
				singlePopup<?echo $controlID?>.setBindElement(this);
			}

			if (singlePopup<?echo $controlID?>.popupContainer.style.display != "block")
				singlePopup<?echo $controlID?>.show();

			return BX.PreventDefault(e);
		}

		function Clear<?echo $controlID?>()
		{
			O_<?=CUtil::JSEscape($controlID)?>.setSelected();
		}

		BX.ready(function() {
			BX.bind(BX("single-user-choice<?echo $controlID?>"), "click", ShowSingleSelector<?echo $controlID?>);
			BX.bind(BX("clear-user-choice"), "click", Clear<?echo $controlID?>);
		});
		</script>
		<?$name = $APPLICATION->IncludeComponent(
			"bitrix:intranet.user.selector.new", ".default", array(
				"MULTIPLE" => "N",
				"NAME" => $controlID,
				"VALUE" => $arUser["ID"],
				"POPUP" => "Y",
				"ON_SELECT" => "onSingleSelect".$controlID,
				"SITE_ID" => SITE_ID,
				"SHOW_EXTRANET_USERS" => "NONE",
			), null, array("HIDE_ICONS" => "Y")
		);

		$strResult = ob_get_contents();
		ob_end_clean();
		return $strResult;
	}

	public static function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
	{
		global $APPLICATION;
			ob_start();

		$arUser = parent::_GetUserArray($value["VALUE"]);
		if ($arUser)
			$UF_HeadName = $arUser["NAME"] == "" && $arUser["LAST_NAME"] == "" ? $arUser["LOGIN"] : $arUser["NAME"]." ".$arUser["LAST_NAME"];
		else
			$UF_HeadName = "";

		$controlID = "Single_" . RandString(6);
		$controlName = $strHTMLControlName['VALUE'];
		?>
		<input type="text" id="<?echo $controlID?>" value="<?if($arUser) echo htmlspecialcharsbx($arUser['ID']);?>" name="<?echo $controlName?>" style="width:35px;font-size:14px;border:1px #c8c8c8 solid;">
		<a href="javascript:void(0)" id="single-user-choice<?echo $controlID?>"><?=GetMessage("INTR_PROP_EMP_SU")?></a>
		<span id="<?echo $controlID?>_name" style="margin-left:15px"><?=htmlspecialcharsex($UF_HeadName)?></span>
		<span id="structure-department-head<?echo $controlID?>" class="structure-department-head" <?if ($UF_HeadName != ""):?>style="visibility:visible"<?endif;?> onclick='BX("<?echo $controlID?>").value = ""; BX("<?echo $controlID?>_name").innerHTML = ""; BX("structure-department-head<?echo $controlID?>").style.visibility="hidden";'></span><br>
		<?CUtil::InitJSCore(array('popup'));?>
		<script type="text/javascript" src="/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js"></script>
		<script type="text/javascript">BX.loadCSS('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css');</script>
		<script>// user_selector:
		var multiPopup<?echo $controlID?>;
		var singlePopup<?echo $controlID?>;
		var taskIFramePopup<?echo $controlID?>;

		function onSingleSelect<?echo $controlID?>(arUser)
		{
			BX("<?echo $controlID?>").value = arUser.id;
			BX("<?echo $controlID?>_name").innerHTML = BX.util.htmlspecialchars(arUser.name);
			BX("structure-department-head<?echo $controlID?>").style.visibility="visible";
		}

		function ShowSingleSelector<?echo $controlID?>(e)
		{
			if(!e) e = window.event;

			if (!singlePopup<?echo $controlID?>)
			{
				singlePopup<?echo $controlID?> = new BX.PopupWindow("single-employee-popup-<?echo $controlID?>", this, {
					offsetTop : 1,
					autoHide : true,
					content : BX("<?=CUtil::JSEscape($controlID)?>_selector_content"),
					zIndex: 3000
				});
			}
			else
			{
				singlePopup<?echo $controlID?>.setBindElement(this);
			}

			if (singlePopup<?echo $controlID?>.popupContainer.style.display != "block")
				singlePopup<?echo $controlID?>.show();

			return BX.PreventDefault(e);
		}

		function Clear<?echo $controlID?>()
		{
			O_<?=CUtil::JSEscape($controlID)?>.setSelected();
		}

		BX.ready(function() {
			BX.bind(BX("single-user-choice<?echo $controlID?>"), "click", ShowSingleSelector<?echo $controlID?>);
			BX.bind(BX("clear-user-choice"), "click", Clear<?echo $controlID?>);
		});
		</script>
		<?$name = $APPLICATION->IncludeComponent(
			"bitrix:intranet.user.selector.new", ".default", array(
				"MULTIPLE" => "N",
				"NAME" => $controlID,
				"VALUE" => $arUser["ID"],
				"POPUP" => "Y",
				"ON_SELECT" => "onSingleSelect".$controlID,
				"SITE_ID" => SITE_ID,
				"SHOW_EXTRANET_USERS" => "NONE",
			), null, array("HIDE_ICONS" => "Y")
		);

		$strResult = ob_get_contents();
		ob_end_clean();
		return $strResult;
	}

	public static function GetPublicEditHTMLMulty($arProperty, $value, $strHTMLControlName)
	{
		global $APPLICATION;
			ob_start();

		$arValues = array();
		$UF_HeadName = "";
		foreach($value as $arValue)
		{
			if (is_array($arValue))
				$arUser = parent::_GetUserArray($arValue["VALUE"]);
			else
				$arUser = parent::_GetUserArray($arValue);

			if ($arUser)
			{
				$UF_HeadName .= $arUser["NAME"] == "" && $arUser["LAST_NAME"] == "" ? $arUser["LOGIN"] : $arUser["NAME"]." ".$arUser["LAST_NAME"];
				$arValues[] = $arUser["ID"];
			}
		}

		$controlID = "Multiple_" . RandString(6);
		$controlName = $strHTMLControlName['VALUE'];
		?>
		<span id="<?echo $controlID?>_hids"><input type="hidden" name="<?echo $controlName?>[]"></span>
		<div id="<?echo $controlID?>_res"></div>
		<a href="javascript:void(0)" id="single-user-choice<?echo $controlID?>"><?=GetMessage("INTR_PROP_EMP_SU")?></a><br>
		<?CUtil::InitJSCore(array('popup'));?>
		<script type="text/javascript" src="/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js"></script>
		<script type="text/javascript">BX.loadCSS('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css');</script>
		<script>// user_selector:
		var multiPopup<?echo $controlID?>;
		var singlePopup<?echo $controlID?>;
		var taskIFramePopup<?echo $controlID?>;

		function onMultipleSelect<?echo $controlID?>(arUsers)
		{
			var hiddens = BX.findChildren(BX('<?echo $controlID?>_hids'), {tagName : 'input'}, true);
			for(var i = 0; i < hiddens.length; i++)
				hiddens[i].value = '';

			var text = '';
			for(var i = 0; i < arUsers.length; i++)
			{
				var arUser = arUsers[i];
				if(arUser)
				{
					if(!hiddens[i])
					{
						hiddens[i] = BX.clone(hiddens[0], true);
						hiddens[0].parentNode.insertBefore(hiddens[i], hiddens[0]);
					}
					hiddens[i].value = arUser.id;
					text += '['+arUser.id+'] ' + BX.util.htmlspecialchars(arUser.name)+'<br>';
				}
			}
			BX("<?echo $controlID?>_res").innerHTML = text;
		}

		function ShowSingleSelector<?echo $controlID?>(e)
		{
			if(!e) e = window.event;

			if (!singlePopup<?echo $controlID?>)
			{
				singlePopup<?echo $controlID?> = new BX.PopupWindow("single-employee-popup-<?echo $controlID?>", this, {
					offsetTop : 1,
					autoHide : true,
					content : BX("<?=CUtil::JSEscape($controlID)?>_selector_content"),
					zIndex: 3000
				});
			}
			else
			{
				singlePopup<?echo $controlID?>.setBindElement(this);
			}

			if (singlePopup<?echo $controlID?>.popupContainer.style.display != "block")
				singlePopup<?echo $controlID?>.show();

			return BX.PreventDefault(e);
		}

		function Clear<?echo $controlID?>()
		{
			O_<?=CUtil::JSEscape($controlID)?>.setSelected();
		}

		BX.ready(function() {
			BX.bind(BX("single-user-choice<?echo $controlID?>"), "click", ShowSingleSelector<?echo $controlID?>);
			BX.bind(BX("clear-user-choice"), "click", Clear<?echo $controlID?>);
		});
		</script>
		<?$name = $APPLICATION->IncludeComponent(
			"bitrix:intranet.user.selector.new", ".default", array(
				"MULTIPLE" => "Y",
				"NAME" => $controlID,
				"VALUE" => $arValues,
				"POPUP" => "Y",
				"ON_CHANGE" => "onMultipleSelect".$controlID,
				"SITE_ID" => SITE_ID,
				"SHOW_EXTRANET_USERS" => "NONE",
			), null, array("HIDE_ICONS" => "Y")
		);

		$strResult = ob_get_contents();
		ob_end_clean();
		return $strResult;
	}

	public static function ConvertFromToDB($arProperty, $value)
	{
		$value['VALUE'] = intval($value['VALUE']);

		if($value['VALUE']>0)
		{
			$dbRes = CUser::GetList('id', 'asc', array('ID' => $value['VALUE'], '!UF_DEPARTMENT' => false), array('SELECT' => array('ID')));
			if (!$dbRes->Fetch())
			{
				$value['VALUE'] = false;
			}
		}
		else
		{
			$value['VALUE'] = false;
		}

		return $value;
	}

	/**
	 * @param array $property
	 * @param array $strHTMLControlName
	 * @param array &$field
	 * @return void
	 */
	public static function GetUIFilterProperty($property, $strHTMLControlName, &$field)
	{
		$field['type'] = 'custom_entity';
		$field['filterable'] = '';
		$field['selector'] = ['type' => 'user'];
	}

	public static function GetUIEntityEditorProperty($settings, $value)
	{
		return [
			'type' => 'custom'
		];
	}

	public static function GetUIEntityEditorPropertyViewHtml(array $params = [])
	{
		if (empty($params['VALUE']))
		{
			return '';
		}

		if (!is_array($params['VALUE']))
		{
			$params['VALUE'] = [$params['VALUE']];
		}

		foreach ($params['VALUE'] as $value)
		{
			$result[] = parent::GetPublicViewHTML(['VALUE' => $value]);
		}
		return implode(', ', $result);
	}

	public static function GetUIEntityEditorPropertyEditHtml(array $params = [])
	{
		\Bitrix\Main\UI\Extension::load(['ui.entity-selector', 'ui.buttons', 'ui.forms']);

		$isMultipleValue = $params['SETTINGS']['MULTIPLE'] === 'Y';

		$isMultipleValue = CUtil::PhpToJSObject($isMultipleValue);

		$preselectedItems = [];

		if (!is_array($params['VALUE']))
		{
			$params['VALUE'] = (!empty($params['VALUE'])) ? [$params['VALUE']] : [];
		}

		foreach ($params['VALUE'] as $value)
		{
			$preselectedItems[] = ['user', $value];
		}

		$selectedItems = \Bitrix\UI\EntitySelector\Dialog::getSelectedItems($preselectedItems)->toJsObject();

		$container_id = $params['FIELD_NAME'] . '_container';
		$container_hidden_id = $params['FIELD_NAME'] . '_container_hidden';

		return <<<HTML
			<input type="hidden" name="{$params['FIELD_NAME']}[]" value="0" />
			<div id="{$container_id}" name="{$container_id}"></div>
			<div id ="{$container_hidden_id}" name="{$container_hidden_id}"></div>
			<script>
				(function() {
					var selector = new BX.UI.EntitySelector.TagSelector({
						id: '{$container_id}',
						multiple: {$isMultipleValue},
						
						dialogOptions: {
							height: 300,
							id: '{$container_id}',
							multiple: {$isMultipleValue},
							context: 'CATALOG_PRODUCT_CARD_EMPLOYEES',
							selectedItems: {$selectedItems},
	
							events: {
								'Item:onSelect': setSelectedInputs.bind(this, 'Item:onSelect'),
								'Item:onDeselect': setSelectedInputs.bind(this, 'Item:onDeselect'),
							},
	
							entities: [
								{
									id: 'user'
								},
								{
									id: 'department'
								}
							]
						}
					})
				
					function setSelectedInputs(eventName, event)
					{
						var dialog = event.getData().item.getDialog();
						dialog.hide();
						var selectedItems = dialog.getSelectedItems();
						if (Array.isArray(selectedItems))
						{
							var selectedItemsId = [];
							var htmlInputs = '';
							selectedItems.forEach(function(item, index, array)
							{
								htmlInputs += '<input type="hidden" name="{$params['FIELD_NAME']}[]" value="' + item['id'] + '" />';
								selectedItemsId.push(item['id']);
							});
							document.getElementById('{$container_hidden_id}').innerHTML = htmlInputs;
							BX.Event.EventEmitter.emit('onChangeEmployee');
						}
					}
				
					selector.renderTo(document.getElementById('{$container_id}'));
				})();
			</script>
HTML;
	}
}
