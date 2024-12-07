<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.buttons','ui.notification', 'ui.fonts.opensans']);

$hideCoverOption = CUserOptions::GetOption('biconnector', 'biconnector.start');
$hideCover = is_array($hideCoverOption) && isset($hideCoverOption['hide_cover']) && $hideCoverOption['hide_cover'] === 'Y';

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
?>
<div class="biconnector-wrapper">
	<?php if (!$hideCover): ?>
	<div class="biconnector-cover" id="biconnector-cover">
		<div class="biconnector-cover-inner" id="biconnector-start-banner"><?=Loc::getMessage('CT_BBS_BANNER')?></div>
		<div class="biconnector-cover-close-btn" onclick="BX.userOptions.save('biconnector', 'biconnector.start', 'hide_cover', 'Y'); BX('biconnector-cover').style.display='none';">
			<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M7.14168 5.63679L11.0465 9.54165L9.54165 11.0465L5.63679 7.14168L1.73193 11.0465L0.227051 9.54165L4.13191 5.63679L0.227051 1.73193L1.73193 0.227051L5.63679 4.13191L9.54165 0.227051L11.0465 1.73193L7.14168 5.63679Z" fill="white"/>
			</svg>
		</div>
	</div>
	<?php endif;?>

	<div class="biconnector-report-service-container">
		<?php
		if (isset($arResult['GDS_MARKET_LINK']))
		{
			?>
			<div class="biconnector-report-service-block">
				<div class="biconnector-report-service-header --data-studio">
					<div class="biconnector-report-service-header-inner">
						<div class="biconnector-report-service-header-logo ">
							<img src="<?=$templateFolder?>/images/ds-logo.svg" alt="">
						</div>
						<div class="biconnector-report-service-header-desc"><?=Loc::getMessage('CT_BBS_GDS_TITLE')?></div>
					</div>
				</div>
				<div class="biconnector-report-service-inner">
					<h2 class="biconnector-report-service-title"><?=Loc::getMessage('CT_BBS_GDS_STEPS_TITLE')?></h2>
					<div class="biconnector-report-service-connection-step-list">
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_1')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_2')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_3')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_4')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_5')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_6')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_7')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_8')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_GDS_STEP_9')?></div>
						</div>
					</div>
				</div>
				<div class="biconnector-report-service-footer">
					<a class="ui-btn ui-btn-round ui-btn-success ui-btn-lg" href="<?=$arResult['GDS_MARKET_LINK']?>" target="_blank"><?=Loc::getMessage('CT_BBS_GDS_STEPS_BUTTON')?></a>
				</div>
			</div>
			<?php
		}

		if (isset($arResult['PBI_MARKET_LINK']))
		{
			?>
			<div class="biconnector-report-service-block">
				<div class="biconnector-report-service-header --power-bi">
					<div class="biconnector-report-service-header-inner">
						<div class="biconnector-report-service-header-logo">
							<img src="<?=$templateFolder?>/images/pbi-logo.svg" alt="">
						</div>
						<div class="biconnector-report-service-header-desc"><?=Loc::getMessage('CT_BBS_PBI_TITLE')?></div>
					</div>
				</div>
				<div class="biconnector-report-service-inner">
					<h2 class="biconnector-report-service-title"><?=Loc::getMessage('CT_BBS_PBI_STEPS_TITLE')?></h2>
					<div class="biconnector-report-service-connection-step-list">
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_1')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_2')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_3')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_4')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_5')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_6')?></div>
							<div class="biconnector-report-service-connection-step-inner">
								<table>
									<tr>
										<td class="biconnector-report-service-connection-step-inner-label"><?=Loc::getMessage('CT_BBS_PBI_SERVER_NAME')?></td>
										<td class="biconnector-report-service-connection-step-inner-value">
											<span class="biconnector-report-service-connection-step-inner-value-block"><?=$arResult['SERVER_NAME']?></span>
											<button class="biconnector-report-service-connection-step-inner-copy-link" onclick="copyText(this)"><?=Loc::getMessage('CT_BBS_PBI_COPY')?></button></td>
									</tr>
									<tr>
										<td class="biconnector-report-service-connection-step-inner-label"><?=Loc::getMessage('CT_BBS_PBI_ACCESS_KEY')?></td>
										<td class="biconnector-report-service-connection-step-inner-value">
											<span class="biconnector-report-service-connection-step-inner-value-block"><?=$arResult['ACCESS_KEY']?></span>
											<button class="biconnector-report-service-connection-step-inner-copy-link" onclick="copyText(this)"><?=Loc::getMessage('CT_BBS_PBI_COPY')?></button>
										</td>
									</tr>
								</table>
								<script>
									function copyText(btn)
									{
										var range = document.createRange();
										range.selectNode(BX.findPreviousSibling(btn, {'class': 'biconnector-report-service-connection-step-inner-value-block'}));
										window.getSelection().addRange(range);
										try {
											document.execCommand('copy');
											BX.UI.Notification.Center.notify({
												content: "<?=Loc::getMessage('CT_BBS_TEXT_COPIED')?>",
												autoHideDelay: 2000,
											});
											window.getSelection().removeAllRanges();
										} catch(err) {
											BX.UI.Notification.Center.notify({
												content: 'Oops, unable to copy',
												autoHideDelay: 2000,
											});
										}
									}
								</script>
							</div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_7')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_8')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_9')?></div>
						</div>
						<div class="biconnector-report-service-connection-step">
							<div class="biconnector-report-service-connection-step-text"><?=Loc::getMessage('CT_BBS_PBI_STEP_10')?></div>
						</div>
					</div>
				</div>
				<div class="biconnector-report-service-footer">
					<a class="ui-btn ui-btn-round ui-btn-success ui-btn-lg" href="<?=$arResult['PBI_MARKET_LINK']?>" target="_blank"><?=Loc::getMessage('CT_BBS_PBI_STEPS_BUTTON')?></a>
				</div>
			</div>
			<?php
		}
		?>
	</div>
</div>
<?php
if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '');
}
