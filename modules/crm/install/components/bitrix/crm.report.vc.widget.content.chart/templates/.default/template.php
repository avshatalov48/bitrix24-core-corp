<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllUser $USER */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.info-helper',
	'ui.icons',
	'ui.hint',
]);

$this->addExternalJs($this->GetFolder() . '/js/helper.js');
$this->addExternalJs($this->GetFolder() . '/js/popup.js');
$this->addExternalJs($this->GetFolder() . '/js/tooltip.js');
$this->addExternalJs($this->GetFolder() . '/js/column.js');
$this->addExternalJs($this->GetFolder() . '/js/item.js');
$this->addExternalJs($this->GetFolder() . '/js/layer.js');
$this->addExternalJs($this->GetFolder() . '/js/polygon.js');
$this->addExternalJs($this->GetFolder() . '/js/source.js');
$this->addExternalJs($this->GetFolder() . '/js/stage.js');


if ($arResult['FEATURE_CODE'])
{
	?>
	<script>BX.UI.InfoHelper.show('<?=$arResult['FEATURE_CODE']?>');</script>
	<div class="crm-report-chart-not-available">
		<div><?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_NOT_AVAILABLE')?></div>
	</div>
	<?php
	return;
}

$containerId = 'crm-analytics-report-view-chart';
$stages = $arResult['DATA']['dict']['stages'];
$firstStageCode = current($stages);
$lastStageCode = end($stages);
?>


<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-report-chart-wrapper">

<table class="crm-report-chart-table <?=(!$arParams['IS_COSTABLE'] ? 'crm-report-chart-temporary-active' : '')?>">
	<tr class="crm-report-chart-tr crm-report-chart-widget">
		<td colspan="<?=count($arResult['DATA']['stages'])?>">
			<div class="crm-report-chart-through-funnel-widget-conversion crm-report-chart-funnel-through-funnel-widget-conversion-inline">

				<?
				if($arParams['IS_COSTABLE'] && !$arParams['IS_TRAFFIC'])
				{
					$colorCode = 'roi';
					$colorCaption = Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_ROI');
				}
				else
				{
					$colorCode = 'conversion';
					$colorCaption = Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_CONV_FULL');
				}

				?>
				<div class="crm-report-chart-through-funnel-widget-title"><?=$colorCaption?></div>
					<div class="crm-report-chart-through-funnel-widget-value-box">
					<?

					$val = $arResult['DATA'][$colorCode];
					if (is_array($val))
					{
						$conversionColor = htmlspecialcharsbx($val['color']);
						$conversionText = htmlspecialcharsbx($val['text']);
						$conversionValue = htmlspecialcharsbx($val['value']);
						$conversionValue = str_replace('%', '', $conversionValue);
						echo '<div class="crm-report-chart-grid-rating">'
							. '<div class="crm-report-chart-through-funnel-widget-value">' . $conversionValue . '</div>'
							. '<div class="crm-report-chart-through-funnel-widget-percent">% &nbsp;</div>'
							. '<div class="crm-report-chart-grid-rating-icon" style="background: ' . $conversionColor . '; margin-top: 6px;"></div>'
							. '<div class="crm-report-chart-grid-rating-text" style="color: ' . $conversionColor . '; margin-top: 6px;">' . $conversionText . '</div>'
							. '</div>';
					}
					else
					{
						?>
						<div class="crm-report-chart-through-funnel-widget-value">
							<?=htmlspecialcharsbx($val)?>
						</div>
						<div class="crm-report-chart-through-funnel-widget-percent">%</div>
						<?
					}
					?>
				</div>
			</div>
		</td>
	</tr>
	<tr data-role="graph" class="crm-report-chart-tr crm-report-chart-widget">
		<?foreach ($arResult['DATA']['stages'] as $stage):?>
			<td class="crm-report-chart-td">
				<?if ($stage['code'] === $firstStageCode):?>
					<div class="crm-report-chart-scale">
						<div class="crm-report-chart-scale-box">
							<?foreach ($stage['scales'] as $scale):?>
								<div class="crm-report-chart-scale-item">
									<?=htmlspecialcharsbx($scale)?>
								</div>
							<?endforeach;?>
						</div>
					</div>

					<?if (!$arParams['IS_COSTABLE']):?>
						<div class="crm-report-chart-temporary-box">
							<div class="crm-report-chart-temporary-content">
								<?if ($arParams['IS_AD_ACCESSIBLE']):?>
									<div class="crm-report-chart-temporary-text">
										<?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_SETUP')?>
									</div>
									<div class="crm-report-chart-temporary-btn">
										<a target="_top" href="/crm/tracking/"
											class="ui-btn ui-btn-sm ui-btn-primary"
										>
											<?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_SETUP_BTN')?>
										</a>
									</div>
								<?else:?>
									<div class="crm-report-chart-temporary-text">
										<?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_COMING_SOON')?>
									</div>
								<?endif;?>
							</div>
							<div class="crm-report-chart-temporary-bg"></div>
							<div class="crm-report-chart-temporary-info">
								<div class="crm-report-chart-th crm-report-chart-card-title"><?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_ACTIONS')?></div>
								<div class="crm-report-chart-th crm-report-chart-card-subtitle"><?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_COST')?></div>
								<div class="crm-report-chart-td crm-report-chart-temporary-flex">
									<div class="crm-report-chart-card-info">
										<div class="crm-report-chart-card-info-box crm-report-chart-card-info-price">
											<div class="crm-report-chart-card-info-value">
												<?=($arResult['DATA']['demoAction'])?>
											</div>
											<div class="crm-report-chart-card-info-symbol">
												<?=($arResult['DATA']['currencyText'])?>
											</div>
										</div>
									</div>
									<div style="margin-left: 10px;" class="crm-report-chart-card-info-box crm-report-chart-card-info-percent">
										<div class="crm-report-chart-card-info-value">+ 7</div>
										<div class="crm-report-chart-card-info-symbol">%</div>
									</div>
								</div>
							</div>
						</div>
					<?endif;?>
				<?endif;?>
				<div class="crm-report-chart-flex-box">
					<div class="crm-report-chart-through-funnel-widget crm-report-chart-through-funnel-widget-1">
						<?foreach ($arResult['DATA']['sources'] as $source):
							?>
							<div data-role="items/<?=htmlspecialcharsbx($source['code'])?>/<?=htmlspecialcharsbx($stage['code'])?>"
								class="crm-report-chart-through-funnel-widget-item"
								style="background: <?=htmlspecialcharsbx($source['color'])?>;"
							></div>
						<?endforeach;?>
					</div>

					<?if ($stage['code'] !== $lastStageCode):?>
						<div class="crm-report-chart-through-funnel-widget crm-report-chart-through-funnel-widget-1 crm-report-chart-through-funnel-widget-mirror">
							<svg class="crm-report-chart-through-funnel-widget-svg" >
								<?foreach ($arResult['DATA']['sources'] as $source):
									if (empty($source['color']))
									{
										continue;
									}
									?>
									<polygon
											data-role="polygons/<?=htmlspecialcharsbx($source['code'])?>/<?=htmlspecialcharsbx($stage['code'])?>"
											points="0 0,0 0,0 0,0 0"
											style="fill: white; stroke: white;"
											preserveAspectRatio="none"
									></polygon>
								<?endforeach;?>
							</svg>
						</div>
					<?else:?>

					<?endif;?>
				</div>

				<div data-role="tooltips/<?=htmlspecialcharsbx($stage['code'])?>"
					class="crm-report-chart-tooltip"
					style="display: none;"
				>
					<div class="crm-report-chart-tooltip-triangle"></div>
					<div class="crm-report-chart-tooltip-value-box">
						<div data-role="tooltip-value" class="crm-report-chart-tooltip-value"></div>
						<div class="crm-report-chart-tooltip-percent">%</div>
					</div>
				</div>
			</td>
		<?endforeach;?>
	</tr>
	<tr class="crm-report-chart-tr">
		<?foreach ($arResult['DATA']['stages'] as $stage):?>
			<th class="crm-report-chart-th crm-report-chart-card-title">
				<?=htmlspecialcharsbx($stage['caption'])?>
			</th>
		<?endforeach;?>
	</tr>
	<tr class="crm-report-chart-tr">
		<?foreach ($arResult['DATA']['stages'] as $stage):?>
			<th class="crm-report-chart-th crm-report-chart-card-subtitle">
				<?if ($arParams['IS_COSTABLE']):?>
					<?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_AVG_COST')?>
				<?else:?>
					<?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_QUANTITY_SMALL')?>
				<?endif;?>
			</th>
		<?endforeach;?>
	</tr>
	<tr class="crm-report-chart-tr">
		<?foreach ($arResult['DATA']['stages'] as $stage):?>
			<td class="crm-report-chart-td">
				<div class="crm-report-chart-card-info">
					<div class="crm-report-chart-card-info-box crm-report-chart-card-info-price">
						<?if ($arParams['IS_COSTABLE']):?>
							<div class="crm-report-chart-card-info-value">
								<?=($stage['costPrint'])?>
							</div>
							<div class="crm-report-chart-card-info-symbol">
								<?=($arResult['DATA']['currencyText'])?>
							</div>
						<?else:?>
							<div class="crm-report-chart-card-info-value">
								<?=htmlspecialcharsbx($stage['quantityPrint'])?>
							</div>
						<?endif;?>
					</div>

					<div class="crm-report-chart-card-info-box crm-report-chart-card-info-percent">
						<?
						$valueChanging = $arParams['IS_COSTABLE'] ? $stage['costChanging'] : $stage['quantityChanging'];
						$valueChangingGreen = ($arParams['IS_COSTABLE'] && $valueChanging <= 0) || (!$arParams['IS_COSTABLE'] && $valueChanging >= 0);
						$valueHint = Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_HINT_' . ($arParams['IS_COSTABLE'] ? 'COST' : 'QUANTITY'));
						?>

						<div class="crm-report-chart-card-info-value" style="<?=($valueChangingGreen ? 'color: green;' : '')?>">
							<?=($valueChanging >= 0 ? '+' : '')?>
							<?=htmlspecialcharsbx($valueChanging)?>
						</div>
						<div class="crm-report-chart-card-info-symbol">%</div>
						<span data-hint="<?=htmlspecialcharsbx($valueHint)?>" class="ui-hint"></span>
					</div>
				</div>
			</td>
		<?endforeach;?>
	</tr>
</table>

	<div style="display: none;">
		<div data-role="popup" class="crm-report-chart-modal" style="border-color: #F7CC00;">
			<div class="crm-report-chart-modal-head">
				<div class="crm-report-chart-modal-title">
					<div data-role="popup-icon" class="crm-report-chart-modal-title-icon">
						<i data-role="popup-icon-color"></i>
					</div>
					<span data-role="popup-caption" class="crm-report-chart-modal-title-text"></span>
				</div>
			</div>
			<div class="crm-report-chart-modal-main">
				<div class="crm-report-chart-modal-subtitle"
					data-role="popup-quantity-caption"
					data-text="<?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_MOVE_TO_STAGE')?>"
					data-text-single="<?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_QUANTITY_SMALL')?>"
				></div>
				<div class="crm-report-chart-modal-content">
					<div data-role="popup-quantity-prev" class="crm-report-chart-modal-value crm-report-chart-modal-value-grey"></div>
					<div data-role="popup-quantity-arrow" class="crm-report-chart-modal-arrow-right">&#8594;</div>
					<div data-role="popup-quantity" class="crm-report-chart-modal-value crm-report-chart-modal-value-black"></div>
				</div>
				<div <?if (!$arParams['IS_COSTABLE']):?>style="display: none;"<?endif;?>>
					<div class="crm-report-chart-modal-subtitle"><?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_STAGE_COST')?></div>
					<div class="crm-report-chart-card-info">
						<div class="crm-report-chart-card-info-box crm-report-chart-card-info-price">
							<div data-role="popup-cost" class="crm-report-chart-card-info-value"></div>
							<div data-role="popup-cost-currency" class="crm-report-chart-card-info-symbol">
								<?=($arResult['DATA']['currencyText'])?>
							</div>
						</div>
						<div data-role="popup-cost-changing" class="crm-report-chart-card-info-box crm-report-chart-card-info-percent">
							<div data-role="popup-cost-changing-value" class="crm-report-chart-card-info-value"></div>
							<div data-role="" class="crm-report-chart-card-info-symbol">%</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

<script>
	BX.Report.Dashboard.Content.Html.ready(function () {
		new BX.Crm.Analytics.Report.ViewChart.Manager(<?=Json::encode([
			'containerId' => $containerId,
			'data' => $arResult['DATA'],
		])?>);
	});
</script>