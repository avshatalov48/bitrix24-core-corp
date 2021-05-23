<?php
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

$toolbarId = $arParams['TOOLBAR_ID'];
?>
<div class="bx-disk-interface-toolbar <?= $arParams['CLASS_NAME'] ?>" id="<?=htmlspecialcharsbx($toolbarId)?>">
	<table cellpadding="0" cellspacing="0" border="0" class="bx-disk-interface-toolbar" style="width: 100%;">
		<tbody>

		<tr>
			<td class="bx-content">
				<table cellpadding="0" cellspacing="0" border="0">
					<tbody>
					<tr><?php
					foreach($arParams['BUTTONS'] as $item)
					{
						$id = isset($item['ID']) ? $item['ID'] : '';
						$text = isset($item['TEXT']) ? $item['TEXT'] : '';
						$title = isset($item['TITLE']) ? $item['TITLE'] : '';
						$link = isset($item['LINK']) ? $item['LINK'] : '#';
						$iconClassName = 'bx-disk-context-button-icon';
						if(isset($item['ICON']))
						{
							$iconClassName .= ' '.$item['ICON'];
						}
						?>
						<td>
							<a id="<?=htmlspecialcharsbx($id)?>" href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" hidefocus="true" class="bx-disk-context-button">
								<span class="<?= htmlspecialcharsbx($iconClassName); ?>"></span>
								<span class="bx-disk-context-button-text"><?=htmlspecialcharsbx($text)?></span>
							</a>
						</td><?php
					}
						?>
					</tr>
					</tbody>
				</table>
			</td>
			<? if(!empty($arParams['DROPDOWN_FILTER'])){ ?>
			<td class="bx-disk-content " style="text-align: right;">
				<?= Loc::getMessage('DISK_INTERFACE_TOOLBAR_LABEL_FOR_DROPDOWN_FILTERS') ?>:
											<span id="<?= $toolbarId.'_dropdown_filter' ?>" class="popup-control">
												<span class="popup-current">
													<span class="popup-current-text">
														<?= $arResult['DROPDOWN_FILTER_CURRENT_LABEL'] ?>
														<span class="icon-arrow"></span>
													</span>
												</span>
											</span>
			</td>
			<? } ?>
		</tr>

		</tbody>
	</table>
</div>
<script>
(function() {
	BX.ready(function () {
		<? if(!empty($arResult['DROPDOWN_FILTER_JS'])){ ?>
		var element = BX("<?=CUtil::JSEscape($toolbarId.'_dropdown_filter')?>");
		BX.bind(
			element,
			'click',
			function(){
				BX.PopupMenu.show(
					<?=CUtil::JSEscape($toolbarId.'_dropdown_filter')?> + '_pm',
					element,
					<?= $arResult['DROPDOWN_FILTER_JS'] ?>,
					{
						autoHide : true,
						offsetTop: 0,
						offsetLeft:25,
						angle: { offset: 45 },
						events:
						{
							onPopupClose : function(){}
						}
					}
				);
			}
		);
		<? } ?>
	});
})();
</script>