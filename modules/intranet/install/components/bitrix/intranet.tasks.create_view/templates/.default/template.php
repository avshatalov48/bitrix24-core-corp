<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"])>0)
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	if(strlen($arResult["ErrorMessage"])>0)
	{
		?>
		<span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br />
		<?
	}
	?>

	<?
	function __ShowField($name, $property_fields, $value, $form_name = "form_element", $taskType = "group")
	{
		if ($property_fields["TYPE"] == "user")
			__ShowUserField($name, $value, $form_name);
		elseif ($property_fields["TYPE"] == "datetime")
			__ShowDateTimeField($name, $value, $form_name);
		elseif ($property_fields["TYPE"] == "bool")
			__ShowBoolField($name, $value);
		elseif ($property_fields["TYPE"] == "group")
			__ShowGroupField($name, $property_fields, $value, $taskType);
		else
			__ShowStringField($name, $value);
	}

	function __ShowGroupField($name, $propertyField, $value, $taskType = "group")
	{
		if (!is_array($value))
			$value = array($value);

		$flag = 0;
		$ha = false;

		$res = "";
		$bWas = false;
		$dbSections = CIBlockSection::GetTreeList(Array("IBLOCK_ID" => $propertyField["IBLOCK_ID"]));
		while ($arSections = $dbSections->GetNext())
		{
			if ($taskType == "group")
			{
				if ($flag == 0)
				{
					if ($arSections["EXTERNAL_ID"] != $propertyField["ROOT_ID"])
						continue;

					$flag = $arSections["DEPTH_LEVEL"];

					continue;
				}
				else
				{
					if ($flag == $arSections["DEPTH_LEVEL"])
						break;
				}
			}
			else
			{
				$flag = 1;
				
				if ($arSections["DEPTH_LEVEL"] == 1)
				{
					if ($arSections["XML_ID"] == "users_tasks")
					{
						$ha = true;
					}
					else
					{

						$ha = CSocNetFeaturesPerms::CanPerformOperation(
							$GLOBALS["USER"]->GetID(),
							SONET_ENTITY_GROUP,
							$arSections["XML_ID"],
							"tasks",
							$arResult["arSocNetFeaturesSettings"]["tasks"]["minoperation"][0]
						);
					}
				}

				if (!$ha)
					continue;
			}

			$res .= '<option value="'.$arSections["ID"].'"';
			if (in_array($arSections["ID"], $value))
			{
				$bWas = true;
				$res .= ' selected';
			}
			$res .= '>'.str_repeat(" . ", $arSections["DEPTH_LEVEL"] - $flag).$arSections["NAME"].'</option>';
		}
		echo '<select name="'.$name.'" style="width:98%">';
		echo '<option value=""'.(!$bWas?' selected':'').'>'.GetMessage("INTVT_NOT_SET").'</option>';
		echo $res;
		echo '</select>';
	}

	function __ShowStringField($name, $value)
	{
		echo '<input name="'.$name.'" value="'.htmlspecialcharsex($value).'" style="width:98%" type="text">';
	}

	function __ShowBoolField($name, $value)
	{
		echo '<select name="'.$name.'" style="width:98%">
			<option value=""'.(($value != "Y" && $value != "N") ? " selected" : "").'>'.GetMessage("INTVT_NOT_SET").'</option>
			<option value="Y"'.(($value == "Y") ? " selected" : "").'>'.GetMessage("INTVT_YES").'</option>
			<option value="N"'.(($value == "N") ? " selected" : "").'>'.GetMessage("INTVT_NO").'</option>
			</select>';
	}

	function __ShowDateTimeField($name, $value, $form_name)
	{
		?>
		<input type="radio" name="DATE_TYPE_<?= $name ?>" id="ID_DATE_TYPE_NONE_<?= $name ?>"<?= (StrLen($value) <= 0) ? " checked" : "" ?> value="none"> 
		<label for="ID_DATE_TYPE_NONE_<?= $name ?>"><?= GetMessage("INTVT_NOT_SET") ?></label><br />
		<input type="radio" name="DATE_TYPE_<?= $name ?>" id="ID_DATE_TYPE_CURRENT_<?= $name ?>"<?= ($value == "current") ? " checked" : "" ?> value="current"> 
		<label for="ID_DATE_TYPE_CURRENT_<?= $name ?>"><?= GetMessage("INTVT_CUR_DATE") ?></label><br />
		<input type="radio" name="DATE_TYPE_<?= $name ?>" id="ID_DATE_TYPE_SELECTED_<?= $name ?>"<?= (StrLen($value) > 0 && $value != "current") ? " checked" : "" ?> value="selected"> 
		<label for="ID_DATE_TYPE_SELECTED_<?= $name ?>"><?= GetMessage("INTVT_THIS_DATE") ?></label><br /><br />
		<?
		$GLOBALS["APPLICATION"]->IncludeComponent(
			'bitrix:main.calendar',
			'',
			array(
				'SHOW_INPUT' => 'Y',
				'FORM_NAME' => $form_name,
				'INPUT_NAME' => $name,
				'INPUT_VALUE' => ((StrLen($value) > 0 && $value != "current") ? $value : ""),
				'SHOW_TIME' => 'Y'
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}

	function __ShowUserField($name, $value, $form_name)
	{
		?>
		<input type="radio" name="USER_TYPE_<?= $name ?>" id="ID_USER_TYPE_NONE_<?= $name ?>"<?= (StrLen($value) <= 0) ? " checked" : "" ?> value="none"> 
		<label for="ID_USER_TYPE_NONE_<?= $name ?>"><?= GetMessage("INTVT_NOT_SET") ?></label><br />
		<input type="radio" name="USER_TYPE_<?= $name ?>" id="ID_USER_TYPE_CURRENT_<?= $name ?>"<?= ($value == "current") ? " checked" : "" ?> value="current"> 
		<label for="ID_USER_TYPE_CURRENT_<?= $name ?>"><?= GetMessage("INTVT_CUR_USER") ?></label><br />
		<input type="radio" name="USER_TYPE_<?= $name ?>" id="ID_USER_TYPE_SELECTED_<?= $name ?>"<?= (StrLen($value) > 0 && $value != "current") ? " checked" : "" ?> value="selected"> 
		<label for="ID_USER_TYPE_SELECTED_<?= $name ?>"><?= GetMessage("INTVT_THIS_USER") ?></label><br /><br />
		<?
		$val = "";
		if (StrLen($value) > 0 && $value != "current")
		{
			$dbUser = CUser::GetByID($value);
			$arUser = $dbUser->Fetch();

			$val = CSocNetUser::FormatNameEx(
				$arUser["NAME"],
				$arUser["SECOND_NAME"],
				$arUser["LAST_NAME"],
				$arUser["LOGIN"],
				$arUser["EMAIL"],
				$arUser["ID"]
			);
		}
		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
			$bExtranet = true;
		elseif (CModule::IncludeModule('extranet'))
			$bIntranet = true;
		
		$GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:socialnetwork.user_search_input",
			".default",
			array(
				"TEXT" => "style='width:98%'",
				"NAME" => $name,
				"FUNCTION" => "",
				"EXTRANET" => ($bExtranet ? "E" : ($bIntranet ? "I" : "")),
				"VALUE" => htmlspecialcharsback($val),
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}

	function __ShowPropertyField($name, $property_fields, $value, $form_name = "form_element")
	{
		$type = $property_fields["PROPERTY_TYPE"];
		if ($property_fields["USER_TYPE"] != "")
			__ShowUserPropertyField($name, $property_fields, $value, $form_name);
		elseif ($type == "L")
			__ShowListPropertyField($name, $property_fields, $value);
		elseif ($type == "G")
			__ShowGroupPropertyField($name, $property_fields, $value);
		else
			__ShowStringPropertyField($name, $value);
	}

	function __ShowStringPropertyField($name, $value)
	{
		echo '<input name="'.$name.'" value="'.htmlspecialcharsex($value).'" style="width:98%" type="text">';
	}

	function __ShowGroupPropertyField($name, $propertyField, $value)
	{
		if (!is_array($value))
			$value = array($value);

		$res = "";
		$bWas = false;
		$dbSections = CIBlockSection::GetTreeList(Array("IBLOCK_ID" => $propertyField["LINK_IBLOCK_ID"]));
		while ($arSections = $dbSections->GetNext())
		{
			$res .= '<option value="'.$arSections["ID"].'"';
			if (in_array($arSections["ID"], $value))
			{
				$bWas = true;
				$res .= ' selected';
			}
			$res .= '>'.str_repeat(" . ", $arSections["DEPTH_LEVEL"]).$arSections["NAME"].'</option>';
		}
		echo '<select name="'.$name.'" size="'.$propertyField["MULTIPLE_CNT"].'" '.($propertyField["MULTIPLE"] == "Y" ? "multiple" : "").' style="width:98%">';
		echo '<option value=""'.(!$bWas?' selected':'').'>'.GetMessage("INTVT_NOT_SET").'</option>';
		echo $res;
		echo '</select>';
	}

	function __ShowUserPropertyField($name, $propertyField, $value, $form_name = "form_element")
	{
		$arUserType = CIBlockProperty::GetUserType($propertyField["USER_TYPE"]);

		if (array_key_exists("GetPublicEditHTML", $arUserType))
		{
			echo call_user_func_array($arUserType["GetPublicEditHTML"],
				array(
					$propertyField,
					array("VALUE" => $value, "DESCRIPTION" => ""),
					array(
						"VALUE" => $name,
						"FORM_NAME" => $form_name,
						"MODE" => "FORM_FILL"
					),
				));
		}
		else
		{
			echo '&nbsp;';
		}
	}

	function __ShowListPropertyField($name, $propertyField, $value)
	{
		if (!is_array($value))
			$value = array($value);

		$res = "";
		$bNoValue = true;
		$prop_enums = CIBlockProperty::GetPropertyEnum($propertyField["ID"]);
		while ($ar_enum = $prop_enums->Fetch())
		{
			$sel = in_array($ar_enum["ID"], $value);
			if ($sel)
				$bNoValue = false;
			$res .= '<option value="'.htmlspecialcharsbx($ar_enum["ID"]).'"'.($sel?" selected":"").'>'.htmlspecialcharsex($ar_enum["VALUE"]).'</option>';
		}

		if ($propertyField["MULTIPLE"] == "Y" && IntVal($propertyField["ROW_COUNT"]) < 2)
			$propertyField["ROW_COUNT"] = 5;
		if ($propertyField["MULTIPLE"] == "Y")
			$propertyField["ROW_COUNT"]++;
		$res = '<select name="'.$name.'" size="'.$propertyField["ROW_COUNT"].'" '.($propertyField["MULTIPLE"]=="Y" ? "multiple" : "").' style="width:98%">'.
			'<option value=""'.($bNoValue?' selected':'').'>'.GetMessage("INTVT_NOT_SET").'</option>'.
			$res.
			'</select>';

		echo $res;
	}

	function __ShowStatusPropertyField($name, $propertyField, $value)
	{
		?>
		<input type="radio" name="TASK_PROP_STATUS" id="ID_TASK_PROP_STATUS_NONE"<?= (StrLen($value) <= 0) ? " checked" : "" ?> value="none"> 
		<label for="ID_TASK_PROP_STATUS_NONE"><?= GetMessage("INTVT_NOT_SET") ?></label><br />
		<input type="radio" name="TASK_PROP_STATUS" id="ID_TASK_PROP_STATUS_ACTIVE"<?= ($value == "active") ? " checked" : "" ?> value="active"> 
		<label for="ID_TASK_PROP_STATUS_ACTIVE"><?= GetMessage("INTVT_STATUS_ACTIVE") ?></label><br />
		<input type="radio" name="TASK_PROP_STATUS" id="ID_TASK_PROP_STATUS_SELECTED"<?= (StrLen($value) > 0 && $value != "active") ? " checked" : "" ?> value="selected"> 
		<label for="ID_TASK_PROP_STATUS_SELECTED"><?= GetMessage("INTVT_STATUS_SELECTED") ?></label><br /><br />
		<?
		$res = "";
		$bNoValue = true;
		$prop_enums = CIBlockProperty::GetPropertyEnum($propertyField["ID"]);
		while ($ar_enum = $prop_enums->Fetch())
		{
			$sel = ($ar_enum["ID"] == $value);
			if ($sel)
				$bNoValue = false;
			$res .= '<option value="'.htmlspecialcharsbx($ar_enum["ID"]).'"'.($sel?" selected":"").'>'.htmlspecialcharsex($ar_enum["VALUE"]).'</option>';
		}

		$res = '<select name="'.$name.'" style="width:98%">'.
			'<option value=""'.($bNoValue?' selected':'').'>'.GetMessage("INTVT_NOT_SET").'</option>'.
			$res.
			'</select>';

		echo $res;
	}
	?>

	<?if ($arResult["ShowStep"] == 1):?>
		<table class="intranet-view-form data-table" cellspacing="0" cellpadding="0">
			<tr>
				<th colspan="2"><?= GetMessage("INTVT_SELECT_FORMAT") ?></th>
			</tr>
			<?$i = 0;?>
			<?foreach ($arResult["Templates"] as $template):?>
				<?if ($i == 0):?>
					<tr>
				<?endif;?>
				<td valign="top" width="50%">
					<a href="<?= $template["LINK"] ?>"><?= $template["TITLE"] ?> (<?= $template["NAME"] ?>)</a><br />
					<?= $template["DESCRIPTION"] ?>
				</td>
				<?if ($i == 1):?>
					</tr>
				<?endif;?>
				<?$i = (($i == 0) ? 1 : 0);?>
			<?endforeach;?>
		</table>

		<br /><br />

		<table class="intranet-view-form data-table" cellspacing="0" cellpadding="0">
			<tr>
				<th colspan="2"><?= GetMessage("INTVT_START_EXIST") ?></th>
			</tr>
			<?if (Count($arResult["Settings"]) > 0):?>
				<?$i = 0;?>
				<?foreach ($arResult["Settings"] as $template):?>
					<?if ($i == 0):?>
						<tr>
					<?endif;?>
					<td valign="top" width="50%">
						<a href="<?= $template["LINK"] ?>"><?= $template["TITLE"] ?></a>
					</td>
					<?if ($i == 1):?>
						</tr>
					<?endif;?>
					<?$i = (($i == 0) ? 1 : 0);?>
				<?endforeach;?>
			<?else:?>
				<tr>
					<td colspan="2">
						<?= GetMessage("INTVT_NO_EXIST") ?>
					</td>
				</tr>
			<?endif;?>
		</table>
	<?else:?>
		<form method="post" name="bx_users_filter_simple_form" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
			<table class="intranet-view-form data-table" cellspacing="0" cellpadding="0">
				<tr>
					<th colspan="2"><?= ($arResult["MODE"] == "edit" ? GetMessage("INTVT_EDIT_VIEW") : GetMessage("INTVT_CREATE_VIEW")) ?></th>
				</tr>
				<tr>
					<td valign="top" align="right" width="30%">
						<!--<span class="required-field">*</span>--><?= GetMessage("INTVT_NAME") ?>:
					</td>
					<td valign="top" align="left" width="70%">
						<input type="text" name="TITLE" style="width:98%" value="<?= HtmlSpecialCharsbx($arResult["UserSettings"]["TITLE"]); ?>">
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="30%">
						<?= GetMessage("INTVT_PUBLIC") ?>:
					</td>
					<td valign="top" align="left" width="70%">
						<input type="radio" name="COMMON" id="ID_COMMON_N" value="N"<?if ($arResult["UserSettings"]["COMMON"] != "Y") echo " checked"?>> 
						<label for="ID_COMMON_N"><?= GetMessage("INTVT_PUBLIC_N") ?></label><br />
						<?if ($arResult["Perms"]["CanModifyCommon"]):?>
							<input type="radio" name="COMMON" id="ID_COMMON_Y" value="Y"<?if ($arResult["UserSettings"]["COMMON"] == "Y") echo " checked"?>>
							<label for="ID_COMMON_Y"><?= GetMessage("INTVT_PUBLIC_Y") ?></label>
						<?endif;?>
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="30%">
						<!-- <span class="required-field">*</span> --><?= GetMessage("INTVT_COLUMNS") ?>:
					</td>
					<td valign="top" align="left" width="70%">
						<table width="100%" cellspacing="1" cellpadding="1" style="padding: 1px;">
						<tr>
							<td width="1%" align="center"><b><?= GetMessage("INTVT_COLUMNS_SHOW") ?></b></td>
							<td width="98%" align="left"><b><?= GetMessage("INTVT_COLUMNS_NAME") ?></b></td>
							<td width="1%" align="center"><b><?= GetMessage("INTVT_COLUMNS_ORDER") ?></b></td>
						</tr>
						<?$i = 0;?>
						<?foreach ($arResult["TaskFields"] as $key => $value):?>
							<?if (!$value["SELECTABLE"]) continue;?>
							<? $ia = (Is_Array($arResult["UserSettings"]["COLUMNS"]) && array_key_exists($key, $arResult["UserSettings"]["COLUMNS"])); ?>
							<tr>
								<td width="1%" align="center" style="padding: 1px;">
									<input type="checkbox" name="SHOW_COLUMN[]" value="<?= $key ?>" id="ID_SHOW_COLUMN_<?= $i ?>"<?= (($ia || Count($arResult["UserSettings"]["COLUMNS"]) <= 0) ? " checked" : "") ?>>
								</td>
								<td width="98%" align="left" style="padding: 1px;">
									<label for="ID_SHOW_COLUMN_<?= $i ?>"><?= $value["FULL_NAME"] ?></label>
								</td>
								<td width="1%" align="center" style="padding: 1px;">
									<select name="ORDER_COLUMN[<?= $key ?>]"><?
										if (is_array($arResult["TaskFields"]))
										{
											$tmpCnt = count($arResult["TaskFields"]);
											for ($j = 1; $j <= $tmpCnt; $j++):
												?><option value="<?= $j ?>"<?= (($ia && ($arResult["UserSettings"]["COLUMNS"][$key] == $j) || !$ia && ($j == ($i == $j - 1))) ? " selected" : "") ?>><?= $j ?></option><?
											endfor;
										}
									?></select>
								</td>
							</tr>
							<?$i++;?>
						<?endforeach;?>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="30%">
						<?= GetMessage("INTVT_SORT") ?>:
					</td>
					<td valign="top" align="left" width="70%">
						<?= GetMessage("INTVT_FIRST_SORT") ?><br />
						<select name="ORDER_BY_0">
							<option value=""><?= GetMessage("INTVT_NOT_SORT") ?></option>
							<?foreach ($arResult["TaskFields"] as $key => $value):?>
								<?if (!$value["SELECTABLE"]) continue;?>
								<option value="<?= $key ?>"<?= ($key == $arResult["UserSettings"]["ORDER_BY_0"]) ? " selected" : "" ?>><?= $value["FULL_NAME"] ?></option>
							<?endforeach;?>
						</select><br />
						<input type="radio" name="ORDER_DIR_0" id="ID_ORDER_DIR_0_ASC" value="ASC"<?if ($arResult["UserSettings"]["ORDER_DIR_0"] == "ASC") echo " checked"?>> 
						<label for="ID_ORDER_DIR_0_ASC"><?= GetMessage("INTVT_SORT_ASC") ?></label><br />
						<input type="radio" name="ORDER_DIR_0" id="ID_ORDER_DIR_0_DESC" value="DESC"<?if ($arResult["UserSettings"]["ORDER_DIR_0"] != "ASC") echo " checked"?>> 
						<label for="ID_ORDER_DIR_0_DESC"><?= GetMessage("INTVT_SORT_DESC") ?></label><br /><br />

						<?= GetMessage("INTVT_SECOND_SORT") ?><br />
						<select name="ORDER_BY_1">
							<option value=""><?= GetMessage("INTVT_NOT_SORT") ?></option>
							<?foreach ($arResult["TaskFields"] as $key => $value):?>
								<?if (!$value["SELECTABLE"]) continue;?>
								<option value="<?= $key ?>"<?= ($key == $arResult["UserSettings"]["ORDER_BY_1"]) ? " selected" : "" ?>><?= $value["FULL_NAME"] ?></option>
							<?endforeach;?>
						</select><br />
						<input type="radio" name="ORDER_DIR_1" id="ID_ORDER_DIR_1_ASC" value="ASC"<?if ($arResult["UserSettings"]["ORDER_DIR_1"] == "ASC") echo " checked"?>> 
						<label for="ID_ORDER_DIR_1_ASC"><?= GetMessage("INTVT_SORT_ASC") ?></label><br />
						<input type="radio" name="ORDER_DIR_1" id="ID_ORDER_DIR_1_DESC" value="DESC"<?if ($arResult["UserSettings"]["ORDER_DIR_1"] != "ASC") echo " checked"?>> 
						<label for="ID_ORDER_DIR_1_DESC"><?= GetMessage("INTVT_SORT_DESC") ?></label><br />
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="30%">
						<?= GetMessage("INTVT_FILTER") ?>:
					</td>
					<td valign="top" align="left" width="70%">
						<table width="100%" cellspacing="1" cellpadding="1" style="padding: 3px;">
						<tr>
							<td style="padding: 3px;" align="right"><b><?= GetMessage("INTVT_FILTER_FIELD") ?></b></td>
							<td width="0%">&nbsp;&nbsp;&nbsp;</td>
							<td style="padding: 3px;"><b><?= GetMessage("INTVT_FILTER_QUERY") ?></b></td>
						</tr>
						<?foreach ($arResult["TaskFields"] as $key => $value):?>
							<?if (!$value["FILTERABLE"]) continue;?>
							<tr>
								<td style="padding: 3px;" valign="top" align="right"><?= $value["FULL_NAME"] ?>:</td>
								<td width="0%">&nbsp;&nbsp;&nbsp;</td>
								<td style="padding: 3px;" valign="top"><?
									if ($key == "TASKSTATUS")
										__ShowStatusPropertyField('FILTER['.$key.']', $value, $arResult["UserSettings"]["FILTER"][$key]);
									elseif ($value["IS_FIELD"] || $value["TYPE"] == "user")
										__ShowField('FILTER['.$key.']', $value, $arResult["UserSettings"]["FILTER"][$key], "bx_users_filter_simple_form", $arParams["TASK_TYPE"]);
									else
										__ShowPropertyField('FILTER['.$key.']', $value, $arResult["UserSettings"]["FILTER"][$key], "bx_users_filter_simple_form");
								?></td>
							</tr>
						<?endforeach;?>
						</table>
					</td>
				</tr>
				<?if ($arParams["TASK_TYPE"] == "group"):?>
					<tr>
						<td valign="top" align="right" width="30%">
							<?= GetMessage("INTVT_FOLDERS") ?>:
						</td>
						<td valign="top" align="left" width="70%">
							<input type="radio" name="THROUGH_SAMPLING" id="ID_THROUGH_SAMPLING_Y" value="Y"<?if ($arResult["UserSettings"]["THROUGH_SAMPLING"] == "Y") echo " checked"?>> 
							<label for="ID_THROUGH_SAMPLING_Y"><?= GetMessage("INTVT_FOLDERS_THROW") ?></label><br />
							<input type="radio" name="THROUGH_SAMPLING" id="ID_THROUGH_SAMPLING_N" value="N"<?if ($arResult["UserSettings"]["THROUGH_SAMPLING"] != "Y") echo " checked"?>> 
							<label for="ID_THROUGH_SAMPLING_N"><?= GetMessage("INTVT_FOLDERS_F") ?></label><br /><br />
						</td>
					</tr>
				<?endif;?>
			</table>
			<?=bitrix_sessid_post()?>
			<br />
			<input type="submit" name="save" value="<?= GetMessage("INTVT_SAVE") ?>">
			<input type="reset" name="cancel" value="<?= GetMessage("INTVT_CANCEL") ?>">
		</form>
	<?endif;?>

	<?
}
?>