<?php
use \Bitrix\Main\Page\Asset;

define("PUBLIC_AJAX_MODE", true);
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
{
	return false;
}

IncludeModuleLangFile(__FILE__);

\Bitrix\Main\UI\Extension::load("ui.hint");
Asset::getInstance()->addCSS(
	'/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css'
);
$arParams = $_REQUEST['arParams'] ?? [];

$iblockID = isset($_REQUEST['IBLOCK_ID'])
	? intval($_REQUEST['IBLOCK_ID'])
	: (is_array($arParams)
		? intval($arParams['IBLOCK_ID'] ?? 0)
		: 0
	);
if ($iblockID <= 0)
	$iblockID = COption::GetOptionInt("intranet", "iblock_absence");

$bIblockChanged = $iblockID != COption::GetOptionInt('intranet', 'iblock_absence');

function AddAbsence($arFields)
{
	global $DB, $iblockID;

	if (CModule::IncludeModule('iblock'))
	{
		$PROP = array();

		$element = new CIBlockElement;

		if (!empty($arFields['ACTIVE_FROM']) && !empty($arFields['ACTIVE_TO']))
		{
			if ($DB->isDate($arFields['ACTIVE_FROM'], false, LANG, 'FULL') && $DB->isDate($arFields['ACTIVE_TO'], false, LANG, 'FULL'))
			{
				if (makeTimeStamp($arFields['ACTIVE_FROM']) > makeTimeStamp($arFields['ACTIVE_TO']))
					$element->LAST_ERROR .= getMessage('INTR_ABSENCE_FROM_TO_ERR').'<br>';
			}
		}

		if (empty($element->LAST_ERROR))
		{
			$db_absence = CIBlockProperty::GetList(Array(), Array("CODE"=>"ABSENCE_TYPE", "IBLOCK_ID"=>$iblockID));
			if ($ar_absence = $db_absence->Fetch())
			{
				$PROP[$ar_absence['ID']] = array($arFields["ABSENCE_TYPE"]);
			}

			$db_user = CIBlockProperty::GetList(Array(), Array("CODE"=>"USER", "IBLOCK_ID"=>$iblockID));
			if ($ar_user = $db_user->Fetch())
			{
				$PROP[$ar_user['ID']] = array($arFields["USER_ID"]);
			}

			$arNewFields = array(
				"NAME" => $arFields["NAME"],
				"PROPERTY_VALUES"=> $PROP,
				"ACTIVE_FROM" => $arFields["ACTIVE_FROM"],
				"ACTIVE_TO" => $arFields["ACTIVE_TO"],
				"IBLOCK_ID" => $iblockID
			);

			$ID = $element->Add($arNewFields);
		}
	}
	if (empty($ID))
	{
		$arErrors = preg_split("/<br>/", $element->LAST_ERROR);
		return $arErrors;
	}
	else
	{
		return $ID;
	}
}
function EditAbsence($arFields)
{
	global $DB, $iblockID;

	if (CModule::IncludeModule('iblock'))
	{
		$PROP = array();

		$element = new CIBlockElement;

		if (!empty($arFields['ACTIVE_FROM']) && !empty($arFields['ACTIVE_TO']))
		{
			if ($DB->isDate($arFields['ACTIVE_FROM'], false, LANG, 'FULL') && $DB->isDate($arFields['ACTIVE_TO'], false, LANG, 'FULL'))
			{
				if (makeTimeStamp($arFields['ACTIVE_FROM']) > makeTimeStamp($arFields['ACTIVE_TO']))
					$element->LAST_ERROR .= getMessage('INTR_ABSENCE_FROM_TO_ERR').'<br>';
			}
		}

		if (empty($element->LAST_ERROR))
		{
			$db_absence = CIBlockProperty::GetList(Array(), Array("CODE"=>"ABSENCE_TYPE", "IBLOCK_ID"=>$iblockID));
			if ($ar_absence = $db_absence->Fetch())
			{
				$PROP[$ar_absence['ID']] = array($arFields["ABSENCE_TYPE"]);
			}

			$db_user = CIBlockProperty::GetList(Array(), Array("CODE"=>"USER", "IBLOCK_ID"=>$iblockID));
			if ($ar_user = $db_user->Fetch())
			{
				$PROP[$ar_user['ID']] = array($arFields["USER_ID"]);
			}

			$arNewFields = array(
				"NAME" => $arFields["NAME"],
				"PROPERTY_VALUES"=> $PROP,
				"ACTIVE_FROM" => $arFields["ACTIVE_FROM"],
				"ACTIVE_TO" => $arFields["ACTIVE_TO"],
				"IBLOCK_ID" => $iblockID
			);

			$ID = $element->Update(intval($arFields["absence_element_id"]), $arNewFields);
		}
	}
	if (empty($ID))
	{
		$arErrors = preg_split("/<br>/", $element->LAST_ERROR);
		return $arErrors;
	}
	else
	{
		return $ID;
	}
}

function DeleteAbsence($absenceID)
{
	if (CModule::IncludeModule('iblock'))
	{
		CIBlockElement::Delete(intval($absenceID));
	}
}

if(!CModule::IncludeModule('iblock'))
{
	echo GetMessage("INTR_ABSENCE_BITRIX24_MODULE");
}
else
{
	if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] == "delete" && check_bitrix_sessid())
	{
		if(CIBlockElementRights::UserHasRightTo($iblockID, intval($_GET["absenceID"]), "element_delete"))
			DeleteAbsence($_GET["absenceID"]);
		die();
	}

	$ID = 0;
	if($_SERVER["REQUEST_METHOD"] === "POST" && check_bitrix_sessid())
	{
		if (
			\Bitrix\Main\Loader::includeModule('bitrix24')
			&& COption::GetOptionString('bitrix24', 'absence_limits_enabled', '') === 'Y'
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('absence')
		)
		{
			die('error:<li>'.GetMessage('INTR_USER_ERR_NO_RIGHT').'</li>');
		}

		if (isset($_POST['absence_element_id']) && CIBlockElementRights::UserHasRightTo($iblockID, intval($_POST['absence_element_id']), 'element_edit'))
		{
			$ID = EditAbsence($_POST);
		}
		elseif(!isset($_POST['absence_element_id']) && CIBlockSectionRights::UserHasRightTo($iblockID, 0, "section_element_bind"))
		{
			$ID = AddAbsence($_POST);
		}
		else
		{
			die('error:<li>'.GetMessage('INTR_USER_ERR_NO_RIGHT').'</li>');
		}

		if(is_array($ID))
		{
			$arErrors = $ID;
			foreach ($arErrors as $key => $val)
			{
				if ($val == '')
					unset($arErrors[$key]);
			}
			$ID = -1;
			die('error:<li>'.implode('</li><li>', array_map('htmlspecialcharsbx', $arErrors))).'</li>';
		}
		elseif (isset($_POST['absence_element_id']))
			die("close");
	}
?>
<!DOCTYPE html>
<html>
<head>
	<?$APPLICATION->ShowHead(); ?>
</head>
<body>
<div style="width: 450px;"><?

	if ($ID > 0)
	{
	?>

	<p><?=GetMessage("INTR_ABSENCE_SUCCESS")?></p>
	<form method="POST" action="<?=BX_ROOT."/tools/intranet_absence.php".($bIblockChanged?"?IBLOCK_ID=".$iblockID:"")?>" id="ABSENCE_FORM">
		<input type="hidden" name="reload" value="Y">
	</form><?
	}
	else
	{
		$arElement = array();
		if (isset($arParams["ABSENCE_ELEMENT_ID"]) && intval($arParams["ABSENCE_ELEMENT_ID"])>0)
		{
			$rsElement = CIBlockElement::GetList(array(), array("ID" => intval($arParams["ABSENCE_ELEMENT_ID"]), "IBLOCK_ID" => $iblockID), false, false, array("ID", "NAME", "ACTIVE_FROM", "ACTIVE_TO", "IBLOCK_ID", "PROPERTY_ABSENCE_TYPE", "PROPERTY_USER"));
			$arElement = $rsElement->Fetch();
		}

		$controlName = "Single_" . RandString(6);
	?>
	<form method="POST" action="<?echo BX_ROOT."/tools/intranet_absence.php"?>" id="ABSENCE_FORM">
		<?if (isset($_POST['absence_element_id']) || isset($arElement["ID"])):?>
		<input type="hidden" value="<?=(isset($_POST['absence_element_id'])) ? htmlspecialcharsbx($_POST['absence_element_id']) : $arElement['ID']?>" name="absence_element_id"><?
		endif;?>
<?
if ($bIblockChanged):
?>
		<input type="hidden" name="IBLOCK_ID" value="<?=$iblockID?>" />
<?
endif;
?>

		<table width="100%" cellpadding="5">
			<tr valign="bottom">
				<td colspan="2">
					<div style="font-size:14px;font-weight: var(--ui-font-weight-bold);padding-bottom:8px"><label for="USER_ID"><?=GetMessage("INTR_ABSENCE_USER")?></label></div>
					<?
					$UserName = "";
					if (isset($_POST['USER_ID']) || isset($arElement["PROPERTY_USER_VALUE"]))
					{
						$UserID = isset($_POST['USER_ID']) ? $_POST['USER_ID'] : $arElement["PROPERTY_USER_VALUE"];
						$dbUser = CUser::GetList("", "", array("ID" => intval($UserID)));
						if ($arUser = $dbUser->Fetch())
							$UserName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true);
					}
					?>
					<input type="hidden" id="user_id" value="<?if (isset($_POST["USER_ID"])) echo htmlspecialcharsbx($_POST["USER_ID"]); elseif (isset($arElement["PROPERTY_USER_VALUE"])) echo htmlspecialcharsbx($arElement["PROPERTY_USER_VALUE"]);?>" name="USER_ID" style="width:35px;font-size:14px;border:1px #c8c8c8 solid;">
					<span id="uf_user_name"><?=$UserName?></span>

					<?CUtil::InitJSCore(array('popup'));?>
					<a href="javascript:void(0)" onclick="ShowSingleSelector" id="single-user-choice"><?=GetMessage("INTR_USER_CHOOSE")?></a>
					<script>// user_selector:
						var multiPopup, singlePopup;
						function onSingleSelect(arUser)
						{
							BX("user_id").value = arUser.id;
							BX("uf_user_name").innerHTML = BX.util.htmlspecialchars(arUser.name);
							singlePopup.close();
						}

						function ShowSingleSelector(e) {

							if(!e) e = window.event;

							if (!singlePopup)
							{
								singlePopup = new BX.PopupWindow("single-employee-popup", this, {
									offsetTop : 1,
									autoHide : true,
									content : BX("<?=CUtil::JSEscape($controlName)?>_selector_content"),
									zIndex: 3000
								});
							}
							else
							{
								singlePopup.setContent(BX("<?=CUtil::JSEscape($controlName)?>_selector_content"));
								singlePopup.setBindElement(this);
							}

							if (singlePopup.popupContainer.style.display != "block")
							{
								singlePopup.show();
							}

							return BX.PreventDefault(e);
						}

						function Clear()
						{
							O_<?=CUtil::JSEscape($controlName)?>.setSelected();
						}

						BX.ready(function() {
							BX.bind(BX("single-user-choice"), "click", ShowSingleSelector);
							BX.bind(BX("clear-user-choice"), "click", Clear);
						});
					</script>
					<?$name = $APPLICATION->IncludeComponent(
							"bitrix:intranet.user.selector.new", ".default", array(
								'MULTIPLE'  => 'N',
								'NAME'      => $controlName,
								'VALUE'     => 1,
								'POPUP'     => 'Y',
								'ON_SELECT' => 'onSingleSelect',
								'SITE_ID'   => SITE_ID,
								'SHOW_EXTRANET_USERS' => \CModule::includeModule('extranet') && \CExtranet::isExtranetSite() ? 'ALL' : 'NONE',
							), null, array("HIDE_ICONS" => "Y")
						);?>
				</td>
			</tr>
			<tr valign="bottom">
				<td>
					<div style="font-size:14px;font-weight: var(--ui-font-weight-bold);padding-bottom:8px"><label for="ABSENCE_TYPE"><?=GetMessage("INTR_ABSENCE_TYPE")?></label></div>
					<select name="ABSENCE_TYPE" id="absence_type" style="width:100%;font-size:14px;border:1px #c8c8c8 solid;">
						<option value="0"><?=GetMessage("INTR_ABSENCE_NO_TYPE")?></option>
						<?
						$property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$iblockID, "CODE"=>"ABSENCE_TYPE"));
						while($enum_fields = $property_enums->Fetch())
						{
							?><option value="<?=$enum_fields['ID'] ?>"
								<? if (isset($_POST['ABSENCE_TYPE']) && $_POST['ABSENCE_TYPE'] == $enum_fields['ID'] || isset($arElement['PROPERTY_ABSENCE_TYPE_ENUM_ID']) && $arElement['PROPERTY_ABSENCE_TYPE_ENUM_ID'] == $enum_fields['ID']) echo 'selected'; ?>>
								<?=htmlspecialcharsbx(Bitrix\Intranet\UserAbsence::getTypeCaption($enum_fields['XML_ID'], $enum_fields['VALUE'])) ?>
							</option><?
						}
						?>
					</select>
				</td>
			</tr>
			<tr valign="bottom">
				<td>
					<div id="intr-absence-name" style="font-size:14px;font-weight: var(--ui-font-weight-bold);padding-bottom:8px">
						<label for="NAME"><?=GetMessage("INTR_ABSENCE_NAME")?></label>
						<span style="position: relative;top: 2px;" data-hint="<?=GetMessage('INTR_ABSENCE_NAME_HINT')?>"></span>
					</div>
					<input type="text" value="<?if (isset($_POST['NAME'])) echo htmlspecialcharsbx($_POST['NAME']); elseif (isset($arElement["NAME"])) echo htmlspecialcharsbx($arElement["NAME"]);?>" name="NAME" id="NAME" style="width:100%;font-size:14px;border:1px #c8c8c8 solid;">
				</td>
			</tr>
			<tr>
				<td>
					<div style="font-size:14px;font-weight: var(--ui-font-weight-bold);padding-bottom:8px"><?=GetMessage("INTR_ABSENCE_PERIOD")?></div>
				</td>
			</tr>
			<tr valign="bottom" >
				<td>
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td  width="100px">
								<label for="ACTIVE_FROM"><?=GetMessage("INTR_ABSENCE_ACTIVE_FROM")?></label>
							</td>
							<td>
								<?
								$input_value_from = "";
								if (isset($arElement["ACTIVE_FROM"]) || isset($_POST["ACTIVE_FROM"]))
									$input_value_from = (isset($arElement["ACTIVE_TO"])) ? htmlspecialcharsbx(FormatDateFromDB($arElement["ACTIVE_FROM"])) : htmlspecialcharsbx(FormatDateFromDB($_POST["ACTIVE_FROM"]));
								$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
									"SHOW_INPUT" => "Y",
									"FORM_NAME" => "",
									"INPUT_NAME" => "ACTIVE_FROM",
									"INPUT_VALUE" => $input_value_from,
									"SHOW_TIME" => "Y",
									"HIDE_TIMEBAR" => "Y"
									)
								);?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr valign="bottom">
				<td>
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td width="100px">
								<label for="ACTIVE_TO"><?=GetMessage("INTR_ABSENCE_ACTIVE_TO")?></label>
							</td>
							<td>
							<?
							$input_value_to = "";
							if (isset($arElement["ACTIVE_TO"]) || isset($_POST["ACTIVE_TO"]))
								$input_value_to = (isset($arElement["ACTIVE_TO"])) ? htmlspecialcharsbx(FormatDateFromDB($arElement["ACTIVE_TO"])) : htmlspecialcharsbx(FormatDateFromDB($_POST["ACTIVE_TO"]));
							$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
								"SHOW_INPUT" => "Y",
								"FORM_NAME" => "",
								"INPUT_NAME" => "ACTIVE_TO",
								"INPUT_VALUE" => $input_value_to,
								"SHOW_TIME" => "Y",
								"HIDE_TIMEBAR" => "Y"
								)
							);?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?echo bitrix_sessid_post()?>
	</form>
<?
	}
?>
<script>
(function(myBX) {
	if (!myBX || !myBX.AbsenceCalendar)
	{
		return;
	}
	var myPopup = myBX.AbsenceCalendar.popup;
	var myButton = myPopup.buttons[0];
	<?if(isset($arParams["ABSENCE_ELEMENT_ID"]) && intval($arParams["ABSENCE_ELEMENT_ID"])>0 || isset($_POST['absence_element_id'])):?>
	myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_ABSENCE_EDIT')) ?>');
	myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_EDIT_TITLE')) ?>');
	<?elseif ($ID > 0):?>
	myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_ABSENCE_ADD_MORE')) ?>');
	myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_ADD_TITLE')) ?>');
	<?else:?>
	myButton.setName('<?=\CUtil::jsEscape(getMessage('INTR_ABSENCE_ADD')) ?>');
	myPopup.setTitleBar('<?=\CUtil::jsEscape(getMessage('INTR_ADD_TITLE')) ?>');
	<?endif?>

	BX.ready(function() {
		BX.UI.Hint.init(BX('intr-absence-name'));
	});
})(window.BX || window.top.BX || null);
</script>
</div>
</body>
</html><?php
}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
