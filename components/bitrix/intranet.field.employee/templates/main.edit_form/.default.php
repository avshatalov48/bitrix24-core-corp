<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

?>

<table id='table_<?= $arResult['userField']['FIELD_NAME'] ?>'>
	<?php
	foreach($arResult['value'] as $key => $value)
	{
		$nameX = $arResult['userField']['FIELD_NAME'] . '_' . $key . '_';
		$valueNameX = 'value_' . $nameX;
		?>
		<tr>
			<td>
				<input
					type="text"
					name="<?= $value['name'] ?>"
					id="<?= $nameX ?>"
					value="<?= ($value['value'] > 0 ? $value['value'] : '') ?>"
					size="3"
					class="typeinput"
					<?= (empty($value['disabled']) ? '' : ' disabled="disabled"') ?>
				>
				&nbsp;&nbsp;

				<?php
				if(empty($value['disabled']))
				{
					$APPLICATION->IncludeComponent(
						'bitrix:intranet.user.search',
						'',
						[
							'INPUT_NAME' => $nameX,
							'MULTIPLE' => 'N',
							'SHOW_BUTTON' => 'Y',
						],
						null,
						['HIDE_ICONS' => 'Y']
					);
				}
				?>

				<IFRAME
					style="width:0; height:0; border: 0; display: none;"
					src="javascript:void(0)"
					name="hiddenframe<?= $value['name'] ?>"
					id="hiddenframe<?= $nameX ?>"
				>
				</IFRAME>

				<span
					id="div_<?= $nameX ?>"
				>
				</span>

				<script>
					var <?= $valueNameX ?> = '';

					function Ch<?= $nameX ?>()
					{
						var DV_<?= $nameX ?> = document.getElementById("div_<?= $nameX ?>");
						if (document.getElementById('<?= $nameX ?>'))
						{
							var old_value = <?= $valueNameX ?>;
							<?= $valueNameX ?> = parseInt(document.getElementById('<?= $nameX ?>').value, 10);
							if (<?= $valueNameX ?> > 0)
							{
								if (old_value != <?= $valueNameX ?>)
								{
									DV_<?= $nameX ?>.innerHTML = '<i><?= CUtil::JSEscape(Loc::getMessage('MAIN_WAIT'))?></i>';
									if (<?= $valueNameX ?> != <?= (int)$USER->GetID()?>)
									{
										document.getElementById("hiddenframe<?= $nameX ?>").src = '<?=$arResult['selfFolderUrl']; ?>get_user.php?ID=' + <?= $valueNameX ?>+ '&strName=<?= $nameX ?>&lang=<?= LANGUAGE_ID . (defined('ADMIN_SECTION') && ADMIN_SECTION === true ? '&admin_section=Y' : '')?>';
									}
									else
									{
										DV_<?= $nameX ?>.innerHTML = '[<?= $arResult['titleUserId'] ?>] (<?= CUtil::JSEscape(HtmlFilter::encode($USER->GetLogin()))?>) <?= CUtil::JSEscape(HtmlFilter::encode($USER->GetFirstName() . ' ' . $USER->GetLastName()))?>';
									}
								}

							}
							else
							{
								DV_<?= $nameX ?>.innerHTML = '';
							}
						}
						setTimeout(function ()
						{
							Ch<?= $nameX ?>()
						}, 1000);
					}

					Ch<?= $nameX ?>();
				</script>
			</td>
		</tr>
		<?php
	}

	if(
		$arResult['userField']['MULTIPLE'] === 'Y'
		&&
		$arResult['userField']['EDIT_IN_LIST'] !== 'N'
	)
	{
		$rowClass = '';
		$fieldNameX = str_replace('_', 'x', $arResult['userField']['FIELD_NAME']);
		?>
		<tr>
			<td style='padding-top: 6px;'>
				<input
					type="button"
					value="<?= Loc::getMessage('USER_TYPE_PROP_ADD') ?>"
					onClick="
						addNewRow(
						'table_<?= $arResult['userField']['FIELD_NAME'] ?>',
						'<?= $fieldNameX ?>|<?= $arResult['userField']['FIELD_NAME'] ?>|<?= $arResult['userField']['FIELD_NAME'] ?>_old_id'
						)"
				>
			</td>
		</tr>
		<?php
	}
	?>
</table>