<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>
<tr>
	<td align="right" width="40%" valign="top"><?= GetMessage("BPCGHLP_HOLD_TO_CLOSE") ?>:</td>
	<td width="60%" valign="top">
		<select name="HOLD_TO_CLOSE">
			<option value="Y"<?= ("Y" == $arCurrentValues["HOLD_TO_CLOSE"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
			<option value="N"<?= ("N" == $arCurrentValues["HOLD_TO_CLOSE"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
		</select>
	</td>
</tr>
<?php
if ($dialog->getDocumentType()[0] === 'tasks'):
?>
	<tr>
		<td align="right" width="40%" valign="top"><?= GetMessage("TASKS_BP_PD_AS_CHILD_TASK") ?>:</td>
		<td width="60%" valign="top">
			<select name="AS_CHILD_TASK">
				<option value="Y"<?= ("Y" == $arCurrentValues["AS_CHILD_TASK"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
				<option value="N"<?= ("N" == $arCurrentValues["AS_CHILD_TASK"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
			</select>
		</td>
	</tr>
<?php
endif;

foreach ($arDocumentFields as $fieldKey => $fieldValue)
{
	if (
		($fieldValue['UserField']['USER_TYPE']['USER_TYPE_ID'] === 'crm')
		&& ($fieldValue['UserField']['USER_TYPE']['CLASS_NAME'] === 'CUserTypeCrm')
		&& CModule::IncludeModule('crm')
	)
	{
		?>
		<tr>
			<td align="right" width="40%" valign="top"><?= GetMessage("TASKS_BP_AUTO_LINK_TO_CRM_ENTITY") ?>:</td>
			<td width="60%" valign="top">
				<select name="AUTO_LINK_TO_CRM_ENTITY">
					<option value="Y"<?= ("Y" == $arCurrentValues["AUTO_LINK_TO_CRM_ENTITY"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
					<option value="N"<?= ("N" == $arCurrentValues["AUTO_LINK_TO_CRM_ENTITY"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
				</select>
			</td>
		</tr>
		<?php
	}
	?>
	<tr>
		<td align="right" width="40%" valign="top"><?= ($fieldValue["Required"]) ? "<span class=\"adm-required-field\">".htmlspecialcharsbx($fieldValue["Name"])."</span>:" : htmlspecialcharsbx($fieldValue["Name"]) .":" ?></td>
		<td width="60%" id="td_<?= htmlspecialcharsbx($fieldKey) ?>" valign="top">
			<?
			if ($fieldValue["UserField"])
			{
				if ($arCurrentValues[$fieldKey])
				{
					if ($fieldValue["UserField"]["USER_TYPE_ID"] == "boolean")
					{
						$fieldValue["UserField"]["VALUE"] = ($arCurrentValues[$fieldKey] == "Y" ? 1 : 0);
					}
					else
					{
						$fieldValue["UserField"]["VALUE"] = $arCurrentValues[$fieldKey];
					}
					$fieldValue["UserField"]["ENTITY_VALUE_ID"] = 1; //hack to not empty value
				}
				$GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:system.field.edit",
					$fieldValue["UserField"]["USER_TYPE"]["USER_TYPE_ID"],
					array(
						"bVarsFromForm" => false,
						"arUserField" => $fieldValue["UserField"],
						"form_name" => $formName,
						'SITE_ID' => $currentSiteId,
					), null, array("HIDE_ICONS" => "Y")
				);
			}
			else
			{
				$fieldValueTmp = $arCurrentValues[$fieldKey];

				if($fieldKey == 'PRIORITY')
				{
					$fieldValueTmp == CTasks::PRIORITY_HIGH ? CTasks::PRIORITY_HIGH : CTasks::PRIORITY_AVERAGE;
				}

				$fieldValueTextTmp = '';
				if (isset($arCurrentValues[$fieldKey . '_text']))
					$fieldValueTextTmp = $arCurrentValues[$fieldKey . '_text'];

				switch ($fieldValue["Type"])
				{
					case "S:UserID":
						echo CBPDocument::ShowParameterField('user', $fieldKey, $fieldValueTmp, Array('rows' => 1));
						break;
					case "S:DateTime":
						echo CBPDocument::ShowParameterField('datetime', $fieldKey, $fieldValueTmp);
						break;
					case "L":
						?>
						<select id="id_<?= $fieldKey ?>" name="<?= $fieldKey ?>">
							<?
							foreach ($fieldValue["Options"] as $k => $v)
							{
								echo '<option value="'.htmlspecialcharsbx($k).'"'.($k."!" === $fieldValueTmp."!" ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
								if ($k."!" === $fieldValueTmp."!")
									$fieldValueTmp = "";
							}
							?>
						</select>
						<?
						echo CBPDocument::ShowParameterField("string", $fieldKey.'_text', $fieldValueTextTmp, Array('size'=> 30));
						break;
					case "B":
						?>
						<select id="id_<?= $fieldKey ?>" name="<?= $fieldKey ?>">
							<option value="Y"<?= ("Y" == $fieldValueTmp ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
							<option value="N"<?= ("N" == $fieldValueTmp ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
						</select>
						<?
						if (in_array($fieldValueTmp, array("Y", "N")))
						{
							$fieldValueTmp = "";
						}
						echo CBPDocument::ShowParameterField("string", $fieldKey.'_text', $fieldValueTextTmp, Array('size'=> 20));
						break;
					case "T":
						echo CBPDocument::ShowParameterField("text", $fieldKey, $fieldValueTmp, ['rows'=> 7, 'cols' => 40]);
						break;
					default:
						echo CBPDocument::ShowParameterField("string", $fieldKey, $fieldValueTmp, Array('size'=> 40));
						break;
				}
			}

			if ($fieldKey === 'ALLOW_TIME_TRACKING'):?>
				<div style="padding: 10px">
					<div><?=GetMessage('BPTA1A_TIME_TRACKING_H')?>:</div>
					<?=CBPDocument::ShowParameterField("int", 'TIME_ESTIMATE_H', $arCurrentValues['TIME_ESTIMATE_H'], ['size' => 20])?>
					<div><?=GetMessage('BPTA1A_TIME_TRACKING_M')?>:</div>
					<?=CBPDocument::ShowParameterField("int", 'TIME_ESTIMATE_M', $arCurrentValues['TIME_ESTIMATE_M'], ['size' => 20])?>
				</div>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}
?>
<tr>
	<td align="right" width="40%" valign="top"><?= GetMessage('BPSA_CHECK_LIST_ITEMS') ?>:</td>
	<td width="60%" valign="top">
		<?=$dialog->renderFieldControl(['FieldName' => 'CHECK_LIST_ITEMS', 'Type' => 'string', 'Multiple' => true], $arCurrentValues['CHECK_LIST_ITEMS'], true, \Bitrix\Bizproc\FieldType::RENDER_MODE_DESIGNER)?>
	</td>
</tr>
<?php echo $GLOBALS["APPLICATION"]->GetCSS();?>