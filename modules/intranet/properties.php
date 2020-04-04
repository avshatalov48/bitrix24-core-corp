<?
IncludeModuleLangFile(__FILE__);

class CIEmployeeProperty
{
	static $cache = array();

	function _GetUserArray($user_id)
	{
		$user_id = intval($user_id);
		if (!array_key_exists($user_id, self::$cache))
		{
			$rsUsers = CUser::GetList($by="", $order="", array("ID_EQUAL_EXACT" => $user_id, '!UF_DEPARTMENT' => false));
			self::$cache[$user_id] = $rsUsers->Fetch();
		}
		return self::$cache[$user_id];
	}

	function GetEditForm($value, $strHTMLControlName)
	{
		global $USER, $APPLICATION;

		$name_x = preg_replace("/([^a-z0-9])/is", "_", $strHTMLControlName["VALUE"]);
		if (strlen(trim($strHTMLControlName["FORM_NAME"])) <= 0)
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

	function GetAdminListViewHTML($value)
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

	function GetPublicViewHTML($value)
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
	function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => \CUserTypeEmployeeDisplay::USER_TYPE_ID,
			"CLASS_NAME" => "CUserTypeEmployee",
			"DESCRIPTION" => GetMessage('INTR_PROP_EMP_TITLE'),
			"BASE_TYPE" => \CUserTypeManager::BASE_TYPE_ENUM,
			"EDIT_CALLBACK" => array('\CUserTypeEmployeeDisplay', 'getPublicEdit'),
			"VIEW_CALLBACK" => array('\CUserTypeEmployeeDisplay', 'getPublicView'),
		);
	}

	function GetDBColumnType()
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "int(18)";
			case "oracle":
				return "number(18)";
			case "mssql":
				return "int";
		}
	}

	public static function getPublicText($userField)
	{
		return CUserTypeEmployeeDisplay::getPublicText($userField);
	}


	// function PrepareSettings($arUserField)
	// {
		// return $arUserField['SETTINGS'];
	// }

	// function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	// {
		// return 'Settings!';
	// }

	function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		return parent::GetEditForm(array('VALUE' => $arHtmlControl['VALUE']), array('VALUE' => $arHtmlControl['NAME']));
	}

	// function GetFilterHTML($arUserField, $arHtmlControl)
	// {
		// return 'Filter!';
	// }

	function GetAdminListViewHTML($value)
	{
		$arHtmlControl = func_num_args() > 1 ? func_get_arg(1) : null;

		return parent::GetAdminListViewHTML($arHtmlControl);
	}

	// function GetAdminListEditHTML($arUserField, $arHtmlControl)
	// {
		// return 'AdminListEdit';
	// }

	function CheckFields($arUserField, $value)
	{
		return array();
	}

	function OnSearchIndex($arUserField)
	{
		$res = '';

		if(is_array($arUserField["VALUE"]))
		{
			$val = $arUserField["VALUE"];
		}
		else
		{
			$val = array($arUserField["VALUE"]);
		}
		$isSearchModuleIncluded = \Bitrix\Main\Loader::includeModule('search');

		$val = array_filter($val, "intval");
		if(count($val))
		{
			foreach($val as $v)
			{
				$rs = CUser::GetList($by="", $order="", array( "ID" => $v));
				while($ar = $rs->Fetch())
				{
					if($isSearchModuleIncluded)
					{
						$res .= CSearch::KillTags(CUser::FormatName(CSite::GetNameFormat(), $ar))."\r\n";
					}
					else
					{
						$res .= strip_tags(CUser::FormatName(CSite::GetNameFormat(), $ar))."\r\n";
					}
				}
			}
		}

		return $res;
	}

	function OnBeforeSave($arUserField, $value)
	{
		return $value;
	}
}

class CUserTypeEmployeeDisplay extends \Bitrix\Main\UserField\TypeBase
{
	const USER_TYPE_ID = 'employee';
	const SELECTOR_CONTEXT = 'USERFIELD_TYPE_EMPLOYEE';

	public static function getPublicEdit($arUserField, $arAdditionalParameters = array())
	{
		global $APPLICATION;

		static::initDisplay(array('intranet_userfield_employee'));

		ob_start();

		$selectorName = $arUserField['FIELD_NAME'].\Bitrix\Main\Security\Random::getString(5);
		$fieldName = static::getFieldName($arUserField, $arAdditionalParameters);
		$fieldValue = static::getFieldValue($arUserField, $arAdditionalParameters);
?>
		<div id="cont_<?=$selectorName?>">
			<div id="field_<?=$selectorName?>" class="main-ui-control-entity main-ui-control userfieldemployee-control" data-multiple="<?=$arUserField['MULTIPLE'] === 'Y' ? 'true' : 'false'?>">
				<a href="#" class="feed-add-destination-link" id="add_user_<?=$selectorName?>" onclick="BX.Intranet.UserFieldEmployee.instance('<?=CUtil::JSEscape($selectorName)?>').open(this.parentNode); return false;"><?=GetMessage('INTR_PROP_EMP_SU')?></a>
			</div>
			<div id="value_<?=$selectorName?>" style="display: none;"><input type="hidden" name="<?=\Bitrix\Main\Text\HtmlFilter::encode($fieldName)?>" value=""></div>

		</div>

<script>
(function(){
	'use strict';

	var selectControl = new BX.Intranet.UserFieldEmployee('<?=$selectorName?>', {
		multiple: <?=$arUserField['MULTIPLE'] === 'Y' ? 'true' : 'false'?>
	});

	var entity = new BX.Intranet.UserFieldEmployeeEntity({
		field: 'field_<?=$selectorName?>',
		multiple: <?=$arUserField['MULTIPLE'] === 'Y' ? 'true' : 'false'?>
	});

	var fieldLayout = {
		multiple: <?=$arUserField['MULTIPLE'] === 'Y' ? 'true' : 'false'?>,
		updateHandler: function(value, userStack)
		{
			if(fieldLayout.multiple)
			{
				var result = [];

				for(var i = 0; i < value.length; i++)
				{
					result.push({name: userStack[value[i]].name, value: value[i]});
				}

				fieldLayout.setData(result);
			}
			else
			{
				if(value === null)
				{
					fieldLayout.setData(null);
				}
				else
				{
					fieldLayout.setData({name: userStack[value].name, value: value});
				}
			}
		},

		removeHandler: function(value)
		{
			var result = fieldLayout.multiple ? [] : null,
				selectControlValue = fieldLayout.multiple ? [] : null;

			for(var i = 0; i < value.length; i++)
			{
				var item = {
					name: value[i].label,
					value: value[i].value
				};

				if(!fieldLayout.multiple)
				{
					selectControlValue = item.value;
					result = item;
					break;
				}
				else
				{
					selectControlValue.push(item.value);
					result.push(item);
				}
			}

			selectControl.setValue(selectControlValue);
			fieldLayout.setData(result);
		},

		setData: function(value)
		{
			var valueContainer = BX('value_<?=$selectorName?>');
			var html = '';

			if(fieldLayout.multiple)
			{
				if(value.length > 0)
				{
					var entityValue = [];
					for(var i = 0; i < value.length; i++)
					{
						entityValue.push({
							value: value[i].value,
							label: value[i].name
						});

						html += '<input type="hidden" name="<?=\CUtil::JSEscape($fieldName)?>" value="' + BX.util.htmlspecialchars(value[i].value) + '" />';
					}

					entity.setData(entityValue);
				}
				else
				{
					entity.removeSquares();
				}
			}
			else
			{
				if(value !== null)
				{
					entity.setData(value.name, value.value);
					html += '<input type="hidden" name="<?=\CUtil::JSEscape($fieldName)?>" value="' + BX.util.htmlspecialchars(value.value) + '" />';
				}
				else
				{
					entity.removeSquares();
				}
			}

			if(html.length <= 0)
			{
				html = '<input type="hidden" name="<?=\CUtil::JSEscape($fieldName)?>" value="" />'
			}

			valueContainer.innerHTML = html;

			BX.defer(function(){
				BX.fireEvent(valueContainer.firstChild, 'change');
			})();
		}
	};

	BX.addCustomEvent(selectControl, 'onUpdateValue', fieldLayout.updateHandler);
	BX.addCustomEvent(entity, 'BX.Intranet.UserFieldEmployeeEntity:remove', fieldLayout.removeHandler);
})();
</script>

<?
		$jsObject = 'BX.Intranet.UserFieldEmployee.instance(\''.\CUtil::JSEscape($selectorName).'\')';
		$componentValue = array();
		foreach($fieldValue as $userId)
		{
			if (intval($userId) > 0)
			{
				$componentValue['U'.$userId] = 'users';
			}
		}

		$APPLICATION->IncludeComponent(
			"bitrix:main.ui.selector",
			".default",
			array(
				'API_VERSION' => 3,
				'ID' => $selectorName,
				'BIND_ID' => 'field_'.$selectorName,
				'TAG_ID' => 'add_user_'.$selectorName,
				'ITEMS_SELECTED' => $componentValue,
				'CALLBACK' => array(
					'select' => $jsObject.'.callback.select',
					'unSelect' => $jsObject.'.callback.unSelect',
				),
				'OPTIONS' => array(
					'useContainer' => 'Y',
					'multiple' => ($arUserField["MULTIPLE"] === 'Y' ? 'Y' : 'N'),
					'extranetContext' => false,
					'eventInit' => 'BX.UFEMP:'.$selectorName.':init',
					'eventOpen' => 'BX.UFEMP:'.$selectorName.':open',
					'context' => static::SELECTOR_CONTEXT,
					'contextCode' => 'U',
					'useSearch' => 'Y',
					'userNameTemplate' => \CSite::GetNameFormat(),
					'useClientDatabase' => 'Y',
					'allowEmailInvitation' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'Y',
					'enableSonetgroups' => 'N',
					'departmentSelectDisable' => 'Y',
					'allowAddUser' => 'N',
					'allowAddCrmContact' => 'N',
					'allowAddSocNetGroup' => 'N',
					'allowSearchEmailUsers' => 'N',
					'allowSearchCrmEmailUsers' => 'N',
					'allowSearchNetworkUsers' => 'N'
				)
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		return ob_get_clean();
	}

	public static function getPublicView($arUserField, $arAdditionalParameters = array())
	{
		$html = '';

		$value = static::normalizeFieldValue($arUserField["VALUE"]);

		if(count($value) > 0 && $value[0] !== null)
		{
			$dbRes = \Bitrix\Main\UserTable::getList(array(
				'filter' => array(
					'@ID' => $value
				),
			));

			$resultList = array();
			$imageList = array();

			while($res = $dbRes->fetch())
			{
				$resultList[] = array(
					'ID' => $res['ID'],
					'NAME' => \CUser::FormatName(\CSite::GetNameFormat(), $res, true, false),
					'PERSONAL_PHOTO' => $res['PERSONAL_PHOTO'],
					'WORK_POSITION' => $res['WORK_POSITION']
				);

				if($res['PERSONAL_PHOTO'] > 0)
				{
					$imageList[$res['PERSONAL_PHOTO']] = false;
				}
			}

			if(count($imageList) > 0)
			{
				foreach($imageList as $imageId => $f)
				{
					$imageFile = \CFile::GetFileArray($imageId);
					if($imageFile !== false)
					{
						$tmpFile = \CFile::ResizeImageGet(
							$imageFile,
							array("width" => 60, "height" => 60),
							BX_RESIZE_IMAGE_EXACT
						);
						$imageList[$imageId] = $tmpFile['src'];
					}
				}
			}

			$pathToUser = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", false, SITE_ID);
			$pathToUser = ($pathToUser ? $pathToUser : SITE_DIR."company/personal/user/#user_id#/");

			$first = true;
			foreach($resultList as $res)
			{
				if(!$first)
				{
					$html .= static::getHelper()->getMultipleValuesSeparator();
				}
				$first = false;

				$userRow = '
<a class="uf-employee-wrap" href="'.\Bitrix\Main\Text\HtmlFilter::encode(str_replace('#user_id#', $res['ID'], $pathToUser)).'" target="_blank">
	<span class="uf-employee-image" '.($res['PERSONAL_PHOTO'] > 0 && $imageList[$res['PERSONAL_PHOTO']] !== false ? 'style="background-image:url('.$imageList[$res['PERSONAL_PHOTO']].'); background-size: 30px;"' : '').'></span>
	<span class="uf-employee-data">
		<span class="uf-employee-name">'.\Bitrix\Main\Text\HtmlFilter::encode($res['NAME']).'</span>
		<span class="uf-employee-position">'.\Bitrix\Main\Text\HtmlFilter::encode($res['WORK_POSITION']).'</span>
	</span>
</a>
';

				$html .= static::getHelper()->wrapSingleField($userRow);
			}
		}

		static::initDisplay(array('intranet_userfield_employee'));

		return static::getHelper()->wrapDisplayResult($html);
	}

	public static function getPublicText($arUserField)
	{
		$values = array_filter(
			static::normalizeFieldValue($arUserField['VALUE']),
			function($value){ return $value > 0; }
		);

		if(empty($values))
		{
			return '';
		}

		static $userNames = array();

		$results = array();
		foreach($values as $k => $v)
		{
			if(!isset($userNames[$v]))
			{
				$results[$v] = null;
				continue;
			}

			$results[$v] = $userNames[$v];
			unset($values[$k]);
		}

		if(!empty($values))
		{
			$dbResult = \Bitrix\Main\UserTable::getList(array('filter' => array('@ID' => array_values($values))));
			while($fields = $dbResult->fetch())
			{
				$userName = \CUser::FormatName(\CSite::GetNameFormat(), $fields, true, false);
				$results[$fields['ID']] = $userNames[$fields['ID']] = $userName;
			}
		}
		return implode(', ', array_values($results));
	}
}

class CIBlockPropertyEmployee extends CIEmployeeProperty
{
	function GetUserTypeDescription()
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
			"GetLength" => array("CIBlockPropertyEmployee","GetLength")
		);
	}

	function CheckFields($arProperty, $value)
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

	function GetLength($arProperty, $value)
	{
		return strlen(trim($value["VALUE"], "\n\r\t "));
	}

	function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		return parent::GetEditForm($value, $strHTMLControlName);
	}

	function GetAdminListViewHTML($value)
	{
		$value = func_num_args() > 1 ? func_get_arg(1) : null;

		return parent::GetAdminListViewHTML($value);
	}

	function GetPublicViewHTML($value)
	{
		$value = func_num_args() > 1 ? func_get_arg(1) : null;

		return parent::GetPublicViewHTML($value);
	}

	function GetPublicFilterHTML($arProperty, $strHTMLControlName)
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

	function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
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

	function GetPublicEditHTMLMulty($arProperty, $value, $strHTMLControlName)
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

	function ConvertFromToDB($arProperty, $value)
	{
		$value['VALUE'] = intval($value['VALUE']);

		if($value['VALUE']>0)
		{
			$dbRes = CUser::GetList($by = 'id', $order = 'asc', array('ID' => $value['VALUE'], '!UF_DEPARTMENT' => false), array('SELECT' => array('ID')));
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
}
