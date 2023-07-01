<?php
/** @var CMain $APPLICATION */
/** @var CUser $USER */
/** @var array $biconnector_default_option */

$module_id = 'biconnector';
$RIGHT_R = $RIGHT_W = $USER->IsAdmin();
if ($RIGHT_W || $RIGHT_R)
{
	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');
	IncludeModuleLangFile(__FILE__);

	$arAllOptions = [
		['gds_deployment_id', GetMessage('BIC_OPTIONS_GDS_DEPLOYMENT_ID'), ['text', '70']],
	];

	$aTabs = [
		[
			'DIV' => 'edit1',
			'TAB' => GetMessage('MAIN_TAB_SET'),
			'ICON' => 'biconnector_settings',
			'TITLE' => GetMessage('MAIN_TAB_TITLE_SET'),
		],
		[
			'DIV' => 'edit2',
			'TAB' => GetMessage('MAIN_TAB_RIGHTS'),
			'ICON' => 'main_settings',
			'TITLE' => GetMessage('MAIN_TAB_TITLE_RIGHTS'),
		],
	];

	$tabControl = new CAdminTabControl('tabControl', $aTabs);

	CModule::IncludeModule($module_id);

	if (
		$_SERVER['REQUEST_METHOD'] === 'POST'
		&& (
			(isset($_REQUEST['Update']) && $_REQUEST['Update'] !== '')
			|| (isset($_REQUEST['Apply']) && $_REQUEST['Apply'] !== '')
			|| (isset($_REQUEST['RestoreDefaults']) && $_REQUEST['RestoreDefaults'] !== '')
		)
		&& $RIGHT_W
		&& check_bitrix_sessid()
	)
	{
		include __DIR__ . '/default_option.php';

		if (isset($_REQUEST['RestoreDefaults']) && $_REQUEST['RestoreDefaults'] !== '')
		{
			COption::RemoveOption($module_id);
		}
		else
		{
			foreach ($arAllOptions as $arOption)
			{
				$name = $arOption[0];
				$val = trim($_REQUEST[$name], " \t\n\r");
				if ($arOption[2][0] == 'checkbox' && $val !== 'Y')
				{
					$val = 'N';
				}

				if ($val === $biconnector_default_option[$name])
				{
					COption::RemoveOption($module_id, $name);
				}
				else
				{
					COption::SetOptionString($module_id, $name, $val, $arOption[1]);
				}
			}
		}

		ob_start();
		$Update = ($_REQUEST['Update'] ?? '') . ($_REQUEST['Apply'] ?? '');
		require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights2.php';
		ob_end_clean();

		if ($_REQUEST['back_url_settings'] <> '')
		{
			if (
				(isset($_REQUEST['Apply']) && $_REQUEST['Apply'] !== '')
				|| (isset($_REQUEST['RestoreDefaults']) && $_REQUEST['RestoreDefaults'] !== '')
			)
			{
				LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . urlencode(LANGUAGE_ID) . '&back_url_settings=' . urlencode($_REQUEST['back_url_settings']) . '&' . $tabControl->ActiveTabParam());
			}
			else
			{
				LocalRedirect($_REQUEST['back_url_settings']);
			}
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . urlencode(LANGUAGE_ID) . '&' . $tabControl->ActiveTabParam());
		}
	}

	?>
	<form method="post" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?php
	$tabControl->Begin();
	$tabControl->BeginNextTab();

	foreach ($arAllOptions as $arOption)
	{
		$val = COption::GetOptionString($module_id, $arOption[0]);
		$type = $arOption[2];
		?>
		<tr>
			<td width="40%" nowrap <?=($type[0] == 'textarea') ? 'class="adm-detail-valign-top"' : ''?>>
				<label for="<?=htmlspecialcharsbx($arOption[0])?>"><?=$arOption[1]?>:</label>
			<td width="60%">
				<?php if ($type[0] == 'checkbox'):?>
					<input type="checkbox" name="<?=htmlspecialcharsbx($arOption[0])?>" id="<?=htmlspecialcharsbx($arOption[0])?>" value="Y"<?=($val == 'Y') ? ' checked' : ''?>>
				<?php elseif ($type[0] == 'text'):?>
					<input type="text" size="<?=$type[1]?>" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="<?=htmlspecialcharsbx($arOption[0])?>" id="<?=htmlspecialcharsbx($arOption[0])?>">
				<?php elseif ($type[0] == 'textarea'):?>
					<textarea rows="<?=$type[1]?>" cols="<?=$type[2]?>" name="<?=htmlspecialcharsbx($arOption[0])?>" id="<?=htmlspecialcharsbx($arOption[0])?>"><?=htmlspecialcharsbx($val)?></textarea>
				<?php elseif ($type[0] == 'selectbox'):?>
					<select name="<?=htmlspecialcharsbx($arOption[0])?>">
					<?php foreach ($type[1] as $key => $value):?>
						<option value="<?=$key?>"<?=($val == $key) ? ' selected' : ''?>><?=htmlspecialcharsbx($value)?></option>
					<?php endforeach; ?>
					</select>
				<?php endif?>
			</td>
		</tr>
	<?php
	}

	if ($RIGHT_W)
	{
		$tabControl->BeginNextTab();
		require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights2.php';
	}

	?>
	<?php $tabControl->Buttons();?>
		<input <?=(!$RIGHT_W) ? 'disabled' : ''?> type="submit" name="Update" value="<?=GetMessage('MAIN_SAVE')?>" title="<?=GetMessage('MAIN_OPT_SAVE_TITLE')?>" class="adm-btn-save">
		<input <?=(!$RIGHT_W) ? 'disabled' : ''?> type="submit" name="Apply" value="<?=GetMessage('MAIN_OPT_APPLY')?>" title="<?=GetMessage('MAIN_OPT_APPLY_TITLE')?>">
		<?php if ($_REQUEST['back_url_settings'] <> ''):?>
			<input <?=(!$RIGHT_W) ? 'disabled' : ''?> type="button" name="Cancel" value="<?=GetMessage('MAIN_OPT_CANCEL')?>" title="<?=GetMessage('MAIN_OPT_CANCEL_TITLE')?>" onclick="window.location='<?=htmlspecialcharsbx(CUtil::addslashes($_REQUEST['back_url_settings']))?>'">
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST['back_url_settings'])?>">
		<?php endif?>
		<input <?=(!$RIGHT_W) ? 'disabled' : ''?> type="submit" name="RestoreDefaults" title="<?=GetMessage('MAIN_HINT_RESTORE_DEFAULTS')?>" onclick="return confirm('<?=addslashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING'))?>')" value="<?=GetMessage('MAIN_RESTORE_DEFAULTS')?>">
		<?=bitrix_sessid_post();?>
	<?php $tabControl->End();?>
	</form>
	<?php
}
