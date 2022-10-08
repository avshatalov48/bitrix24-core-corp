<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var \Bitrix\Report\VisualConstructor\Entity\Widget $widget */
$widget = $arResult['WIDGET'];
$widgetId = 'widget_id_' . $widget->getGId();

$widgetData = $arResult['WIDGET_DATA'];

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

//TODO rewrite this
$columnsContainerIds = [
	"first-columns-container",
	"second-columns-container",
	"third-columns-container"
];
?>

<div id="<?=$widgetId ?>" class="crm-report-column-funnel-wrapper">
	<table class="crm-report-column-funnel-table">
		<tr class="crm-report-column-funnel-tr crm-report-column-funnel-widget">

			<?foreach ($widgetData as $i => $data):?>

				<td class="crm-report-column-funnel-td <?= !empty($data['singleData'])? 'crm-report-column-funnel-td-last':''?>">
					<?if (!empty($data['singleData'])):?>
						<div class="crm-report-column-funnel-through-funnel-widget-conversion">
							<div class="crm-report-column-funnel-through-funnel-widget-title"><?=$data['topAdditionalTitle']?></div>
							<div class="crm-report-column-funnel-through-funnel-widget-value-box">
								<div class="crm-report-column-funnel-through-funnel-widget-value"><?=$data['topAdditionalValue']?></div>
								<div class="crm-report-column-funnel-through-funnel-widget-percent"><?=$data['topAdditionalValueUnit']?></div>
							</div>
						</div>
					<?endif;?>

					<?if($i === 0):?>
						<div class="crm-report-column-funnel-scale">
							<div class="crm-report-column-funnel-scale-box">
							</div>
						</div>
					<?endif;?>
					<div data-role="<?=$columnsContainerIds[$i]?>" class="crm-report-column-funnel-through-funnel-widget crm-report-column-funnel-through-funnel-widget-columns"></div>
				</td>
			<?endforeach;?>
		</tr>




		<tr class="crm-report-column-funnel-tr">

			<?foreach ($widgetData as $i => $data):?>
				<th class="crm-report-column-funnel-th crm-report-column-funnel-card-title"><?=$data['title']?></th>
			<?endforeach;?>
		</tr>
		<tr class="crm-report-column-funnel-tr">
		</tr>
		<tr class="crm-report-column-funnel-tr">

			<?foreach ($widgetData as $i => $data):?>
				<td class="crm-report-column-funnel-td">
					<div class="crm-report-column-funnel-card-flexed-container">
						<div class="crm-report-column-funnel-card-flex-item">
							<?if(!empty($data['firstAdditionalTitleAmount']) && !empty($data['firstAdditionalValueAmount'])):?>
								<div  class="crm-report-column-funnel-card-info">
									<div class="crm-report-column-funnel-card-info-item">
										<div class="crm-report-column-funnel-card-subtitle"><?=$data['firstAdditionalTitleAmount']?></div>
										<div class="crm-report-column-funnel-card-info-value-box">
											<div data-role="first-additional-card-value-<?=$i?>" class="crm-report-column-funnel-card-info-value crm-report-column-funnel-card-info-value-clickable"><?=$data['firstAdditionalValueAmount']?></div>
										</div>
									</div>
								</div>

							<?endif;?>
							<?if(!empty($data['secondAdditionalTitleAmount']) && !empty($data['secondAdditionalValueAmount'])):?>
								<div class="crm-report-column-funnel-card-info">
									<div class="crm-report-column-funnel-card-info-item">
										<div class="crm-report-column-funnel-card-subtitle"><?=$data['secondAdditionalTitleAmount']?></div>
										<div class="crm-report-column-funnel-card-info-value-box">
											<div class="crm-report-column-funnel-card-info-value"><?=$data['secondAdditionalValueAmount']?></div>
										</div>
									</div>
								</div>
							<?endif;?>
						</div>
						<div class="crm-report-column-funnel-card-flex-item">
							<?if(!empty($data['thirdAdditionalTitleAmount']) && !empty($data['thirdAdditionalValueAmount'])):?>
								<div class="crm-report-column-funnel-card-info">
									<div class="crm-report-column-funnel-card-info-item">
										<div class="crm-report-column-funnel-card-subtitle"><?=$data['thirdAdditionalTitleAmount']?></div>
										<div class="crm-report-column-funnel-card-info-value-box">
											<div class="crm-report-column-funnel-card-info-value"><?=$data['thirdAdditionalValueAmount']?></div>
										</div>
									</div>
								</div>
							<?endif;?>
						</div>
					</div>
				</td>
			<?endforeach;?>

		</tr>
	</table>
</div>

<script>
	BX.Report.Dashboard.Content.Html.ready(function(context) {
		new BX.Crm.Report.ColumnFunnel({
			context: context,
			data: <?=CUtil::PhpToJSObject($arResult['WIDGET_DATA'])?>
		});
	});
</script>


<div data-role="info-popup-content-template" class="crm-report-column-funnel-modal crm-report-column-funnel-modal-hidden" style="border-color: #00B4AC;">
	<div class="crm-report-column-funnel-modal-head">
		<div data-role="info-popup-content-label" class="crm-report-column-funnel-modal-title"></div>
	</div>
	<div class="crm-report-column-funnel-modal-main">
		<div class="crm-report-column-funnel-card-info">
			<div data-role="info-popup-top-card" class="crm-report-column-funnel-card-info-item">
				<div data-role="info-popup-top-title" class="crm-report-column-funnel-card-subtitle"></div>
				<div class="crm-report-column-funnel-card-info-value-box">
					<div data-role="info-popup-top-value" class="crm-report-column-funnel-card-info-value"></div>
				</div>
			</div>
			<div data-role="info-popup-forth-card" class="crm-report-column-funnel-card-info-item">
				<div data-role="info-popup-forth-title" class="crm-report-column-funnel-card-subtitle"></div>
				<div class="crm-report-column-funnel-card-info-value-box">
					<div data-role="info-popup-forth-value" class="crm-report-column-funnel-card-info-value"></div>
				</div>
			</div>
		</div>
		<div class="crm-report-column-funnel-card-info">
			<div data-role="info-popup-second-card" class="crm-report-column-funnel-card-info-item">
				<div data-role="info-popup-second-title" class="crm-report-column-funnel-card-subtitle"></div>
				<div class="crm-report-column-funnel-card-info-value-box">
					<div data-role="info-popup-second-value" class="crm-report-column-funnel-card-info-value"></div>
				</div>
			</div>
			<div data-role="info-popup-third-card" class="crm-report-column-funnel-card-info-item">
				<div data-role="info-popup-third-title" class="crm-report-column-funnel-card-subtitle"></div>
				<div class="crm-report-column-funnel-card-info-value-box">
					<div data-role="info-popup-third-value" class="crm-report-column-funnel-card-info-value"></div>
				</div>
			</div>
		</div>

	</div>
</div>


