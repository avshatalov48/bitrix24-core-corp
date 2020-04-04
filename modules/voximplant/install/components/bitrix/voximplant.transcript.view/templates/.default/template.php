<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @global array $arResult
 */
?>
<div class="vox-transcript">
	<div class="vox-transcript-filter-container">
		<input type="text" class="vox-transcript-filter" placeholder="<?=GetMessage("VOX_TRANSCRIPT_FILTER_PLACEHOLDER")?>" data-role="input-filter">
	</div>
	<? if(is_array($arResult['LINES'])): ?>
		<div class="vox-transcript-dialog" data-role="dialog">
			<? foreach ($arResult['LINES'] as $line): ?>
				<div class="vox-transcript-line-container" data-text="<?= htmlspecialcharsbx($line['MESSAGE'])?>">
					<div class="vox-transcript-avatar <?=($line['SIDE'] == \Bitrix\Voximplant\Transcript::SIDE_CLIENT ? 'vox-transcript-avatar-left' : 'vox-transcript-avatar-right')?>">
						<? if($line['SIDE'] == \Bitrix\Voximplant\Transcript::SIDE_CLIENT && $arResult['CLIENT']['PHOTO'] != ''): ?>
							<div class="vox-transcript-avatar-icon" style="background-image: url(<?=$arResult['CLIENT']['PHOTO']?>)"></div>
						<? elseif($line['SIDE'] == \Bitrix\Voximplant\Transcript::SIDE_USER && $arResult['USER']['PHOTO'] != ''): ?>
							<div class="vox-transcript-avatar-icon" style="background-image: url(<?=$arResult['USER']['PHOTO']?>)"></div>
						<? else: ?>
							<div class="vox-transcript-avatar-icon vox-transcript-avatar-icon-empty"></div>
						<? endif ?>
					</div>
					<div class="vox-transcript-line">
						<div class="vox-transcript-line-message">
							<div class="vox-transcript-line-message-wrap <?=($line['SIDE'] == \Bitrix\Voximplant\Transcript::SIDE_CLIENT ? 'vox-transcript-line-message-incoming' : 'vox-transcript-line-message-outgoing' )?>">
								<div class="vox-transcript-line-message-header">
									<div class="vox-transcript-line-message-name">
										<? if($line['SIDE'] == \Bitrix\Voximplant\Transcript::SIDE_CLIENT): ?>
											<?= htmlspecialcharsbx($arResult['CLIENT']['NAME'])?>
										<? else: ?>
											<?= htmlspecialcharsbx($arResult['USER']['NAME'])?>
										<? endif ?>
									</div>
									<div class="vox-transcript-line-message-time">
										<?= htmlspecialcharsbx($line['START_TIME'])?>
									</div>
								</div>
								<div class="vox-transcript-line-message-text">
									<?= htmlspecialcharsbx($line['MESSAGE'])?>
								</div>
							</div>
						</div>
					</div>
				</div>
			<? endforeach ?>
		</div>
	<? endif ?>
</div>