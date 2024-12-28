<?php
$module_id = 'sign';

use Bitrix\Main\Localization\Loc;

/** @var \CMain $APPLICATION */

if (!\Bitrix\Main\Loader::includeModule($module_id))
{
	return;
}

// vars
$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
$docRoot = $context->getServer()->getDocumentRoot();
$mid = $request->get('mid');
$backUrl = $request->get('back_url_settings');
$postRight = $APPLICATION->getGroupRight($module_id);

// lang
IncludeModuleLangFile($docRoot . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

if ($postRight >= 'R'):

	$allOptions[] = [
		'public_url',
		Loc::getMessage('SIGN_PROXY_OPT_PUBLIC_URL') . ':',
		['text', 50]
	];

	// $allOptions[] = [
	// 	'use_proxy_local',
	// 	Loc::getMessage('SIGN_PROXY_OPT_USE_PROXY_LOCAL') . ':',
	// 	['checkbox']
	// ];
	//
	// $allOptions[] = [
	// 	'proxy_local',
	// 	Loc::getMessage('SIGN_PROXY_OPT_PROXY_LOCAL') . ':',
	// 	['text', 50]
	// ];

	// $allOptions[] = [
	// 	'max_upload_total_size_kb',
	// 	Loc::getMessage('SIGN_OPT_MAX_UPLOAD_TOTAL_SIZE_KB') . ':',
	// 	['text', 5]
	// ];
	//
	// $allOptions[] = [
	// 	'max_upload_doc_size_kb',
	// 	Loc::getMessage('SIGN_OPT_MAX_UPLOAD_DOC_SIZE_KB') . ':',
	// 	['text', 5]
	// ];
	//
	// $allOptions[] = [
	// 	'max_upload_image_size_kb',
	// 	Loc::getMessage('SIGN_OPT_MAX_UPLOAD_IMAGE_SIZE_KB') . ':',
	// 	['text', 5]
	// ];
	//
	// $allOptions[] = [
	// 	'max_count_pages_img',
	// 	Loc::getMessage('SIGN_OPT_MAX_COUNT_PAGES_IMG') . ':',
	// 	['text', 5]
	// ];

	// tabs
	$tabControl = new CAdmintabControl('tabControl', [
		['DIV' => 'edit1', 'TAB' => Loc::getMessage('MAIN_TAB_SET'), 'ICON' => '']
	]);

	$Update = $Update ?? '';
	$Apply = $Apply ?? '';

	// post save
	if (
		$Update . $Apply <> '' &&
		($postRight === 'W' || $postRight === 'X') &&
		\check_bitrix_sessid()
	)
	{
		foreach ($allOptions as $arOption)
		{
			if ($arOption[0] === 'header')
			{
				continue;
			}

			$name = $arOption[0];

			if ($arOption[2][0] === 'text-list')
			{
				$val = '';
				for ($j = 0; $j < count($$name); $j++)
				{
					if (trim(${$name}[$j]) <> '')
					{
						$val .= ($val <> '' ? ',':'') . trim(${$name}[$j]);
					}
				}
			}
			elseif ($arOption[2][0] === 'doubletext')
			{
				$val = ${$name . '_1'} . 'x' . ${$name . '_2'};
			}
			elseif (
				$arOption[2][0] === 'selectbox' ||
				$arOption[2][0] === 'selectboxtree'
			)
			{
				$val = '';
				if (isset($$name))
				{
					for ($j=0; $j<count($$name); $j++)
					{
						if (trim(${$name}[$j]) <> '')
						{
							$val .= ($val <> '' ? ',':'') . trim(${$name}[$j]);
						}
					}
				}
			}
			else
			{
				$val = $$name;
			}

			if ($arOption[2][0] === 'checkbox' && $val<>'Y')
			{
				$val = 'N';
			}

			$val = trim($val);

			\COption::setOptionString($module_id, $name, $val);
		}

		if ($Update . $Apply <> '' && $backUrl <> '')
		{
			localRedirect($backUrl);
		}
		else
		{
			localRedirect(
				$APPLICATION->GetCurPage() .
				'?mid=' . urlencode($mid) .
				'&lang=' . urlencode(LANGUAGE_ID) .
				'&back_url_settings=' . urlencode($backUrl ?? '') .
				'&' . $tabControl->ActiveTabParam());
		}
	}

	?><form method="post" action="<?php echo $APPLICATION->GetCurPage()?>?mid=<?php echo urlencode($mid)?>&amp;lang=<?php echo LANGUAGE_ID?>"><?php
	$tabControl->Begin();
	$tabControl->BeginNextTab();
foreach($allOptions as $Option):
	if ($Option[0] === 'header')
	{
		?>
		<tr class="heading">
			<td colspan="2">
				<?php echo $Option[1]?>
			</td>
		</tr>
		<?php if (isset($Option[2])):?>
		<tr>
			<td></td>
			<td>
				<?php
				echo BeginNote();
				echo $Option[2];
				echo EndNote();
				?>
			</td>
		</tr>
	<?php endif;

		continue;
	}
	$type = $Option[2];
	$val = \COption::getOptionString(
		$module_id,
		$Option[0],
		$Option[3] ?? null
	);
	?>
	<tr>
	<td style="width: 40%;"><?php
		if ($type[0] === 'checkbox')
		{
			echo '<label for="' . htmlspecialcharsbx($Option[0]) . '">' . $Option[1] . '</label>';
		}
		else
		{
			echo $Option[1];
		}
		?></td>
	<td><?php
		if ($type[0] === 'checkbox'):
			?><input type="checkbox" name="<?php echo htmlspecialcharsbx($Option[0])?>" id="<?php echo htmlspecialcharsbx($Option[0])?>" value="Y"<?php echo ($val === 'Y') ? ' checked="checked"' : ''?> /><?php
		elseif ($type[0] === 'text'):
			?><input type="text" size="<?php echo $type[1]?>" maxlength="255" value="<?php echo htmlspecialcharsbx($val)?>" name="<?php echo htmlspecialcharsbx($Option[0])?>" /><?php
		elseif ($type[0] === 'doubletext'):
			[$val1, $val2] = explode('x', $val);
			?><input type="text" size="<?php echo $type[1]?>" maxlength="255" value="<?php echo htmlspecialcharsbx($val1)?>" name="<?php echo htmlspecialcharsbx(
			$Option[0] . '_1')?>" /><?php
			?><input type="text" size="<?php echo $type[1]?>" maxlength="255" value="<?php echo htmlspecialcharsbx($val2)?>" name="<?php echo htmlspecialcharsbx(
			$Option[0] . '_2')?>" /><?php
		elseif ($type[0] === 'textarea'):
			?><textarea rows="<?php echo $type[1]?>" cols="<?php echo $type[2]?>" name="<?php echo htmlspecialcharsbx(
			$Option[0])?>"><?php echo htmlspecialcharsbx($val)?></textarea><?php
		elseif ($type[0] === 'text-list'):
			$aVal = explode(",", $val);
			for($j=0; $j<count($aVal); $j++):
				?><input type="text" size="<?php echo $type[2]?>" value="<?php echo htmlspecialcharsbx(
				$aVal[$j])?>" name="<?php echo htmlspecialcharsbx($Option[0]) . '[]'?>" /><br /><?php
			endfor;
			for($j=0; $j<$type[1]; $j++):
				?><input type="text" size="<?php echo $type[2]?>" value="" name="<?php echo htmlspecialcharsbx($Option[0]) . '[]'?>" /><br /><?php
			endfor;
		elseif ($type[0] === 'selectbox'):
			$arr = $type[1];
			$arr_keys = array_keys($arr);
			$currValue = explode(',', $val);
			?><select name="<?php echo htmlspecialcharsbx($Option[0])?>[]"<?=$type[2]?>><?php
			for($j = 0; $j < count($arr_keys); $j++):
				?><option value="<?php echo $arr_keys[$j]?>"<?php echo in_array($arr_keys[$j], $currValue) ? ' selected="selected"' : ''?>><?php echo htmlspecialcharsbx(
				$arr[$arr_keys[$j]])?></option><?php
			endfor;
			?></select><?php
		endif;
		echo $Option[4] ?? '';
		?>
	</td>
<?php
endforeach;

	$tabControl->Buttons();
	?>
	<input <?php echo ($postRight < 'W') ? 'disabled="disabled"' : ''?> type="submit" name="Update" value="<?php  echo Loc::getMessage('MAIN_SAVE')?>" title="<?php echo Loc::getMessage('MAIN_OPT_SAVE_TITLE')?>" />
	<input <?php echo ($postRight < 'W') ? 'disabled="disabled"' : ''?> type="submit" name="Apply" value="<?php echo Loc::getMessage('MAIN_OPT_APPLY')?>" title="<?php echo Loc::getMessage('MAIN_OPT_APPLY_TITLE')?>" />
	<?php if ($backUrl <> ''):?>
	<input <?php echo ($postRight < 'W') ? 'disabled="disabled"' : ''?> type="button" name="Cancel" value="<?= Loc::getMessage('MAIN_OPT_CANCEL')?>" title="<?php echo Loc::getMessage('MAIN_OPT_CANCEL_TITLE')?>" onclick="window.location='<?php echo htmlspecialcharsbx(
		CUtil::addslashes($backUrl))?>'" />
	<input type="hidden" name="back_url_settings" value="<?php echo htmlspecialcharsbx($backUrl)?>" />
<?php endif?>
	<?php echo bitrix_sessid_post()?>
	<?php $tabControl->End()?>
	</form>

<?php endif;
