<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"]) > 0)
{
	?>
	<span class='errortext'><?= $arResult["FatalError"] ?></span><br /><br />
	<?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?>
		<span class='errortext'><?= $arResult["ErrorMessage"] ?></span><br /><br />
		<?
	}
	?>

	<?
	global $intaskAutoExecuteFunctionsCache;
	$intaskAutoExecuteFunctionsCache = array();

	function __ShowFieldTmp($name, $arPropertyFields, $value, $form_name = "form_element", $arParams = array(), $arResult = array())
	{
		if ($arPropertyFields["Type"] == "select")
		{
			__ShowSelectFieldTmp($name, $arPropertyFields, $value, $form_name, $arParams);
			return;
		}
		if ($arPropertyFields["Type"] == "user" && $arPropertyFields["Multiple"])
		{
			__ShowUserFieldMultipleTmp($name, $arPropertyFields, $value, $form_name, $arParams);
			return;
		}

		if ($arPropertyFields["Type"] != "text")
			echo '<table cellpadding="0" cellspacing="0" border="0" style="padding:0px;" class="nopadding" width="100%" id="tb'.md5($name).'">';

		if (!is_array($value))
			$value = array("n0" => $value);
		elseif ($arPropertyFields["Multiple"] || count($value) <= 0)
			$value["n0"] = "";

		foreach ($value as $key => $val)
		{
			if ($arPropertyFields["Type"] != "text")
				echo '<tr><td style="padding:0px;">';

			if ($arPropertyFields["Type"] == "user")
				__ShowUserFieldTmp($name, $arPropertyFields["Multiple"], $key, $val, $form_name, $arParams);
			elseif ($arPropertyFields["Type"] == "datetime")
				__ShowDateTimeFieldTmp($name, $arPropertyFields["Multiple"], $key, $val, $form_name);
			elseif ($arPropertyFields["Type"] == "file")
				__ShowFileFieldTmp($name, $arPropertyFields, $key, $val, $form_name, $arParams);
			elseif ($arPropertyFields["Type"] == "text")
				__ShowTextFieldTmp($name, $arPropertyFields["Multiple"], $key, $val, $arResult["IsInSecurity"]);
			else
				__ShowStringFieldTmp($name, $arPropertyFields["Multiple"], $key, $val);

			if ($arPropertyFields["Type"] != "text")
				echo '</td></tr>';

			if (!$arPropertyFields["Multiple"])
				break;
		}

		if ($arPropertyFields["Multiple"] && $arPropertyFields["Type"] != "text")
			echo '<tr><td style="padding:0px;"><input type="button" value="'.GetMessage("INTET_ADD").'" onClick="addNewRow(\'tb'.md5($name).'\')"></td></tr>';

		if ($arPropertyFields["Type"] != "text")
			echo '</table>';
	}

	function __ShowStringFieldTmp($name, $multiple, $key, $value)
	{
		echo '<input name="'.$name.($multiple ? '['.$key.']' : '').'" value="'.$value.'" size="40" type="text">';
	}

	function __ShowTextFieldTmp($name, $multiple, $key, $value, $isInSecurity)
	{
		if (!$isInSecurity)
		{
			echo '<textarea name="'.$name.($multiple ? '['.$key.']' : '').'" style="width:98%" rows="10">'.$value.'</textarea>';
		}
		else
		{
			CModule::IncludeModule("fileman");
			
			if ($name == "DETAIL_TEXT")
			{
				?>
				<script>
				window.LHE_TC = {
					butTitle: '<?= GetMessage("INTET_VE_INS_SUBTASK") ?>',
					dialogTitle: '<?= GetMessage("INTET_VE_INS_SUBTASK1") ?>',
					subTaskLabel: '<?= GetMessage("INTET_VE_INS_SUBTASK2") ?>'
				};

				window.subTaskStyles = "\n" +
				"ul.bx-subtasklist {list-style-image: url(/bitrix/images/fileman/light_htmledit/check_off.gif);}" + 
				"ul.bx-subtasklist li{padding-left: 5px;}" +
				"ul.bx-subtasklist li.checked{ list-style-image: url(/bitrix/images/fileman/light_htmledit/check_on.gif);}" +
				"\n";
				</script>
				<?
				AddEventHandler("fileman", "OnBeforeLightEditorScriptsGet", "lhe_add_js");
				function lhe_add_js($LheId)
				{
					if ($LheId == 'LHETaskId')
						return array("JS" => array('/bitrix/js/fileman/light_editor/task_checkbox.js'));
				}
				$ar = array(
					'id' => 'LHETaskId',
					'height' => '200px',
					'inputName' => $name.($multiple ? '['.$key.']' : ''),
					'inputId' => $name.'_id',
					'content' => $value,
					'bUseFileDialogs' => false,
					'toolbarConfig' => array(
						'Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat',
						'CreateLink', 'DeleteLink', 'Image', 'Video',
						'ForeColor',
						'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull',
						'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent',
						'StyleList', 'HeaderList',
						'FontList', 'FontSizeList', 'TaskCheckbox'
					),
					'jsObjName' => 'oLHE',
					'bResizable' => true
				);
			}
			else
			{
				$ar = array(
					'height' => '200px',
					'inputName' => $name.($multiple ? '['.$key.']' : ''),
					'inputId' => $name.'_id',
					'content' => $value,
					'bUseFileDialogs' => false,
					'toolbarConfig' => array(
						'Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat',
						'CreateLink', 'DeleteLink', 'Image', 'Video',
						'ForeColor',
						'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull',
						'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent',
						'StyleList', 'HeaderList',
						'FontList', 'FontSizeList'
					),
					'bResizable' => true
				);
			}

			$LHE = new CLightHTMLEditor;
			$LHE->Show($ar);
		}
	}

	function __ShowDateTimeFieldTmp($name, $multiple, $key, $value, $form_name)
	{
		$GLOBALS["APPLICATION"]->IncludeComponent(
			'bitrix:main.calendar',
			'',
			array(
				'SHOW_INPUT' => 'Y',
				'FORM_NAME' => $form_name,
				'INPUT_NAME' => $name.($multiple ? '['.$key.']' : ''),
				'INPUT_VALUE' => ((StrLen($value) > 0 && $value != "current") ? $value : ""),
				'SHOW_TIME' => 'Y',
				'INPUT_ADDITIONAL_ATTR' => "",
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}

	function __ShowUserFieldTmp($name, $multiple, $key, $value, $form_name, $arParams)
	{
		if (strlen($value) > 0 && $value != "current")
		{
			$arParams["NAME_TEMPLATE_VALUE"] = str_replace(
				array("#NOBR#", "#/NOBR#"), 
				array("", ""), 
				$arParams["NAME_TEMPLATE"]
			);

			$arParams['NAME_TEMPLATE_VALUE'] .= (IsModuleInstalled('intranet') ? ' <#EMAIL#>' : '');
			$arParams['NAME_TEMPLATE_VALUE'] .= " [#ID#]";
			$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

			$dbUser = CUser::GetByID($value);
			$arUser = $dbUser->Fetch();

			$value = CUser::FormatName($arParams['NAME_TEMPLATE_VALUE'], $arUser, $bUseLogin);					
		}

		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
			$bExtranet = true;
		elseif (CModule::IncludeModule('extranet'))
			$bIntranet = true;

		$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:intranet.user.selector', '', array(
			'INPUT_NAME' => $name,
			'INPUT_NAME_STRING' => $name."_string",
			'INPUT_NAME_SUSPICIOUS' => $name."_suspicious",
			'INPUT_VALUE_STRING' => htmlspecialcharsback($value),
			'EXTERNAL' => 'A',
			'MULTIPLE' => 'N',
			'SOCNET_GROUP_ID' => ($arParams["TASK_TYPE"] == "group" ? $arParams["OWNER_ID"] : ""),
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			)
		);
	}

	function __ShowUserFieldMultipleTmp($name, $arPropertyFields, $value, $form_name, $arParams)
	{
		if (!is_array($value))
			$value = array($value);

		$arUsers = array();

		$arParams["NAME_TEMPLATE_VALUE"] = str_replace(
			array("#NOBR#", "#/NOBR#"), 
			array("", ""), 
			$arParams["NAME_TEMPLATE"]
		);

		$arParams['NAME_TEMPLATE_VALUE'] .= (IsModuleInstalled('intranet') ? ' <#EMAIL#>' : '');
		$arParams['NAME_TEMPLATE_VALUE'] .= " [#ID#]";
		$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

		foreach ($value as $val)
		{
			if (strlen($val) > 0 && $val != "current")
			{
				$dbUser = CUser::GetByID($val);
				$arUser = $dbUser->Fetch();

				$arUsers[$val] = htmlspecialcharsback(CUser::FormatName($arParams['NAME_TEMPLATE_VALUE'], $arUser, $bUseLogin));
			}
		}

		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
			$bExtranet = true;
		elseif (CModule::IncludeModule('extranet'))
			$bIntranet = true;

		$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:intranet.user.selector', '', array(
			'INPUT_NAME' => $name,
			'INPUT_NAME_STRING' => $name."_string",
			'INPUT_NAME_SUSPICIOUS' => $name."_suspicious",
			'TEXTAREA_MIN_HEIGHT' => 30,
			'TEXTAREA_MAX_HEIGHT' => 60,
			'INPUT_VALUE_STRING' => implode("\n", $arUsers),
			'EXTERNAL' => 'A',
			'SOCNET_GROUP_ID' => ($arParams["TASK_TYPE"] == "group" ? $arParams["OWNER_ID"] : ""),
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			)
		);
	}

	function __ShowSelectFieldTmp($name, $arPropertyFields, $value, $form_name, $arParams)
	{
		if (!is_array($value))
			$value = array($value => "");

		echo '<select name="'.$name.($arPropertyFields["MULTIPLE"] == "Y" ? '[]" multiple size="5"' : '"').'>';
		echo '<option value="">'.GetMessage("INTET_NOT_SET").'</option>';
		foreach ($arPropertyFields["Options"] as $optionKey => $optionValue)
			echo '<option value="'.htmlspecialcharsbx($optionKey).'"'.(array_key_exists($optionKey, $value) ? " selected" : "").'>'.htmlspecialcharsex($optionValue).'</option>';
		echo '</select>';
	}

	function __ShowFileFieldTmp($name, $multiple, $key, $value, $form_name, $arParams)
	{
		echo CFile::InputFile($name.($multiple ? '['.$key.']' : ''), 40, $value, false, 0, "");
		if (IntVal($value) > 0)
		{
			echo "<br>";
			echo CFile::ShowFile($value, 5000000, 300, 300, true)."<br>";
		}
	}
	?>

	<script language="JavaScript">
	<!--
	function addNewRow(tableID)
	{
		var tbl = document.getElementById(tableID);
		var cnt = tbl.rows.length;
		var oRow = tbl.insertRow(cnt - 1);
		var oCell = oRow.insertCell(0);
		oCell.style.padding = "0 0 0 0";
		var sHTML = tbl.rows[cnt - 2].cells[0].innerHTML;

		var p = 0;
		while (true)
		{
			var s = sHTML.indexOf('[n', p);
			if (s < 0)
				break;
			var e = sHTML.indexOf(']', s);
			if (e < 0)
				break;
			var n = parseInt(sHTML.substr(s + 2, e - s));

			sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);

			p = s + 1;
		}

		var p = 0;
		while (true)
		{
			var s = sHTML.indexOf('__n', p);
			if (s < 0)
				break;
			var e = sHTML.indexOf('__', s + 2);
			if (e < 0)
				break;
			var n = parseInt(sHTML.substr(s + 3, e - s));
			sHTML = sHTML.substr(0, s) + '__n' + (++n) + '__' + sHTML.substr(e + 2);
			p = e + 2;
		}
		var p = 0;
		while (true)
		{
			var s = sHTML.indexOf('__N', p);
			if (s < 0)
				break;
			var e = sHTML.indexOf('__', s + 2);
			if (e < 0)
				break;
			var n = parseInt(sHTML.substr(s + 3, e - s));
			sHTML = sHTML.substr(0, s) + '__N' + (++n) + '__' + sHTML.substr(e + 2);
			p = e + 2;
		}
		var p = 0;
		while (true)
		{
			var s = sHTML.indexOf('xxn', p);
			if (s < 0)
				break;
			var e = sHTML.indexOf('xx', s + 2);
			if (e < 0)
				break;
			var n = parseInt(sHTML.substr(s + 3, e - s));
			sHTML = sHTML.substr(0, s) + 'xxn' + (++n) + 'xx' + sHTML.substr(e + 2);
			p = e + 2;
		}

		oCell.innerHTML = sHTML;

		var patt = new RegExp ("<" + "script" + ">([^\000]*?)<" + "\/" + "script" + ">", "gi");
		var code = sHTML.match(patt);
		if (code)
		{
			for (var i = 0; i < code.length; i++)
				if (code[i] != '')
					jsUtils.EvalGlobal(code[i].substr(8, code[i].length - 17));
		}
	}
	//-->
	</script>

	<form method="post" name="bx_users_filter_simple_form" action="<?= POST_FORM_ACTION_URI ?>" enctype="multipart/form-data" onsubmit="verifyTaskForm();">
		<table class="intranet-view-form data-table" cellspacing="0" cellpadding="0">
			<tr>
				<th colspan="2"><?= (($arParams["ACTION"] == "create") ? GetMessage("INTET_CREATE_TITLE") : (($arParams["ACTION"] == "edit") ? str_replace("#ID#", $arResult["Task"]["ID"], GetMessage("INTET_EDIT_TITLE")) : str_replace("#ID#", $arResult["Task"]["ID"], GetMessage("INTET_VIEW_TITLE")))) ?></th>
			</tr>

			<tr>
				<td valign="top" align="right" width="30%">
					<?= GetMessage("INTET_CURRENT_STATUS") ?>:
				</td>
				<td valign="top" align="left" width="70%">
					<?if ($USER->IsAdmin() && strlen($arResult["DocumentState"]["ID"]) > 0):?><a href="/bitrix/admin/bizproc_log.php?ID=<?= $arResult["DocumentState"]["ID"] ?>"><?endif;?><?= strlen($arResult["DocumentState"]["STATE_TITLE"]) > 0 ? $arResult["DocumentState"]["STATE_TITLE"] : $arResult["DocumentState"]["STATE_NAME"] ?><?if ($USER->IsAdmin() && strlen($arResult["DocumentState"]["ID"]) > 0):?></a><?endif;?>
					<script language="JavaScript">
					function OnChangeBPCommand(cmd)
					{
						<?if ($arParams["ACTION"] == "create"):?>
							var ar = {
								"-" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal'),
								"CloseEvent" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal')
							};
						<?else:?>
							<?if ($arResult["DocumentState"]["STATE_NAME"] == "NotAccepted"):?>
								var ar = {
									"-" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal'),
									"SetResponsibleEvent" : new Array(),
									"ApproveEvent" : new Array('PROPERTY_TaskReport', 'PROPERTY_TaskSize'),
									"InProgressEvent" : new Array('PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize'),
									"CompleteEvent" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal'),
									"CloseEvent" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal')
								};
							<?elseif ($arResult["DocumentState"]["STATE_NAME"] == "NotStarted"):?>
								var ar = {
									"-" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSizeReal'),
									"SetResponsibleEvent" : new Array(),
									"ApproveEvent" : new Array('PROPERTY_TaskReport', 'PROPERTY_TaskSize'),
									"InProgressEvent" : new Array('PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize'),
									"CompleteEvent" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal'),
									"CloseEvent" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskComplete', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal'),
									"WaitingEvent" : new Array(),
									"DeferredEvent" : new Array()
								};
							<?elseif ($arResult["DocumentState"]["STATE_NAME"] == "InProgress"):?>
								var ar = {
									"-" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskSizeReal'),
									"SetResponsibleEvent" : new Array(),
									"ApproveEvent" : new Array('PROPERTY_TaskReport', 'PROPERTY_TaskSize'),
									"CompleteEvent" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal'),
									"CloseEvent" : new Array('PROPERTY_TaskFinish', 'PROPERTY_TaskReport', 'PROPERTY_TaskSize', 'PROPERTY_TaskSizeReal'),
									"WaitingEvent" : new Array(),
									"DeferredEvent" : new Array()
								};
							<?elseif ($arResult["DocumentState"]["STATE_NAME"] == "Completed"):?>
								var ar = {
									"-" : new Array(),
									"CloseEvent" : new Array()
								};
							<?elseif ($arResult["DocumentState"]["STATE_NAME"] == "Closed"):?>
								var ar = {
									"-" : new Array()
								};
							<?elseif ($arResult["DocumentState"]["STATE_NAME"] == "Waiting"
								|| $arResult["DocumentState"]["STATE_NAME"] == "Deferred"):?>
								var ar = {
									"-" : new Array(),
									"SetResponsibleEvent" : new Array(),
									"NotStartedEvent" : new Array(),
									"ApproveEvent" : new Array(),
									"InProgressEvent" : new Array(),
									"CompleteEvent" : new Array(),
									"CloseEvent" : new Array(),
									"WaitingEvent" : new Array(),
									"DeferredEvent" : new Array()
								};
							<?endif;?>
						<?endif;?>

						for (var i = 0; i < ar["-"].length; i++)
						{
							var o1 = document.getElementById("task_field_" + ar["-"][i]);
							if (o1) o1.style.display = "none";
						}

						<?if ($arParams["ACTION"] != "create"):?>
							var o1 = document.getElementById("task_field_PROPERTY_TaskAssignedTo");
							if (o1) o1.style.display = "none";
							var o1 = document.getElementById("task_field_PROPERTY_TaskAssignedTo_1");
							if (o1) o1.style.display = "";
						<?endif;?>

						if (cmd.length <= 0)
							return;

						var arCmd = cmd.split("_");
						if (arCmd.length != 3)
							return;

						if (arCmd[2] == "SetResponsibleEvent")
						{
							var o1 = document.getElementById("task_field_PROPERTY_TaskAssignedTo");
							if (o1) o1.style.display = "";
							var o1 = document.getElementById("task_field_PROPERTY_TaskAssignedTo_1");
							if (o1) o1.style.display = "none";
						}
						else
						{
							if (ar[arCmd[2]])
							{
								for (var i = 0; i < ar[arCmd[2]].length; i++)
								{
									var o1 = document.getElementById("task_field_" + ar[arCmd[2]][i]);
									if (o1) o1.style.display = "";
								}
							}
						}
					}
					</script>
				</td>
			</tr>
			<?
			if (count($arResult["DocumentState"]["AllowableEvents"]) > 0)
			{
				?>
				<tr>
					<td valign="top" align="right" width="30%">
						<?= GetMessage("INTET_SEND_COMMAND") ?>:
					</td>
					<td valign="top" align="left" width="70%">
						<select name="bizproc_event" onchange="OnChangeBPCommand(this.options[this.selectedIndex].value)">
							<option value="">&nbsp;</option>
							<?
							foreach ($arResult["DocumentState"]["AllowableEvents"] as $e)
							{
								?><option value="<?= htmlspecialcharsbx($e["NAME"]) ?>"<?= ($_REQUEST["bizproc_event_".$bizProcIndex] == $e["NAME"]) ? " selected" : ""?>><?= htmlspecialcharsbx($e["TITLE"]) ?></option><?
							}
							?>
						</select>
					</td>
				</tr>
				<?
			}

			$bEditFields = ($arParams["ACTION"] == "edit" || $arParams["ACTION"] == "create");
			?>

			<tr id="task_field_PROPERTY_TaskAssignedTo">
				<td valign="top" align="right" width="30%">
					<span class="required-field">*</span>
					<?= GetMessage("INTET_RESPONSIBLE") ?>:
				</td>
				<td valign="top" align="left" width="70%">
					<?
					__ShowFieldTmp("PROPERTY_TaskAssignedTo", $arResult["TaskFields"]["PROPERTY_TaskAssignedTo"], $arResult["Task"]["PROPERTY_TaskAssignedTo"], "bx_users_filter_simple_form", $arParams);
					?>
				</td>
			</tr>

			<tr id="task_field_PROPERTY_TaskAssignedTo_1" style="display:none">
				<td valign="top" align="right" width="30%">
					<?= GetMessage("INTET_RESPONSIBLE") ?>:
				</td>
				<td valign="top" align="left" width="70%">
					<?= $arResult["Task"]["PROPERTY_TaskAssignedTo_PRINTABLE"] ?>
				</td>
			</tr>

			<?foreach ($arResult["TaskFieldsOrder"] as $vvv):?>
				<?
				$field = $vvv[0];
				$arField = $arResult["TaskFields"][$field];
				?>
				<?if ($field == "ID" || $field == "PROPERTY_TaskAssignedTo") continue;?>
				<?if ($arParams["ACTION"] == "create" && !$arField["EDITABLE"]) continue;?>
				<?if ($arParams["TASK_TYPE"] == "user" && $field == "IBLOCK_SECTION_ID") continue;?>
				<?
				if (!$bEditFields || !$arField["EDITABLE"])
				{
					$bHasValue = false;
					if (is_array($arResult["Task"][$field."_PRINTABLE"]))
					{
						foreach ($arResult["Task"][$field."_PRINTABLE"] as $val)
							$bHasValue = (is_array($val) && count($val) > 0 || !is_array($val) && strlen($val) > 0);
					}
					else
					{
						$bHasValue = (strlen($arResult["Task"][$field."_PRINTABLE"]) > 0);
					}
					if (!$bHasValue)
						continue;
				}
				?>

				<tr id="task_field_<?= $field ?>">
					<td valign="top" align="right" width="30%">
						<?if ($arField["IS_REQUIRED"] == "Y" && $bEditFields):?>
							<span class="required-field">*</span>
						<?endif;?>
						<?= $arField["FULL_NAME"] ?>:
					</td>
					<td valign="top" align="left" width="70%">
						<?if ($bEditFields && $arField["EDITABLE"]):?>
							<?
							__ShowFieldTmp($field, $arField, $arResult["Task"][$field], "bx_users_filter_simple_form", $arParams, $arResult);
							?>
						<?else:?>
							<?if (is_array($arResult["Task"][$field."_PRINTABLE"])):?>
								<?foreach ($arResult["Task"][$field."_PRINTABLE"] as $val):?>
									<?
									if ($arField["Type"] == "file")
									{
										echo CFile::ShowFile($val, 0, 300, 300, true)."<br>";
									}
									else
									{
										if (is_array($val))
										{
											$bFirst = true;
											foreach ($val as $v)
											{
												if (!$bFirst)
													echo " &gt; ";
												echo $v;
												$bFirst = false;
											}
										}
										else
										{
											echo $val;
										}
									}
									?><br />
								<?endforeach?>
							<?else:?>
								<?= $arResult["Task"][$field."_PRINTABLE"] ?>
							<?endif;?>
						<?endif;?>
					</td>
				</tr>
			<?endforeach;?>
		</table>
		<?=bitrix_sessid_post()?>
		<br />
		<input type="hidden" name="back_url" value="<?= $arResult["back_url"] ?>">
		<?if ($bEditFields || count($arResult["DocumentState"]["AllowableEvents"]) > 0):?>
			<input type="submit" name="save" value="<?= GetMessage("INTET_SAVE") ?>">
			<input type="submit" name="apply" value="<?= GetMessage("INTET_APPLY") ?>">
			<input type="reset" name="cancel" value="<?= GetMessage("INTET_CANCEL") ?>" onclick="window.location='<?= $arResult["back_url"] ?>'">
		<?endif;?>
	</form>
	<script language="JavaScript">
	<!--
	OnChangeBPCommand("");

	function verifyTaskForm()
	{
		<?
		global $intaskAutoExecuteFunctionsCache;
		if (is_array($intaskAutoExecuteFunctionsCache))
		{
			foreach($intaskAutoExecuteFunctionsCache as $f)
			{
				?><?= $f ?>; 
				<?
			}
		}
		?>
		return true;
	}
	//-->
	</script>
	<?
}
?>