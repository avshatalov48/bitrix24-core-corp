<?php
/** @var \Bitrix\Report\VisualConstructor\Entity\Widget $widget */
$widget = $arResult['WIDGET'];
$widgetId = 'widget_id_' . $widget->getGId();

$widgetData = $arResult['WIDGET_DATA'];

//TODO rewrite this
$columnsContainerIds = [
	"first-columns-container",
	"second-columns-container",
];
?>

<div id="<?=$widgetId ?>" class="crm-report-column-funnel-wrapper">
	<table class="crm-report-column-funnel-table">
		<tr class="crm-report-column-funnel-tr crm-report-column-funnel-widget">

			<?foreach ($widgetData as $i => $data):?>
				<?if (!empty($data['singleData'])):?>
					<td class="crm-report-column-funnel-td crm-report-column-funnel-td-last">
						<div class="crm-report-column-funnel-through-funnel-widget-conversion">
							<div class="crm-report-column-funnel-through-funnel-widget-title"><?=$data['title']?></div>
							<div class="crm-report-column-funnel-through-funnel-widget-value-box">
								<div class="crm-report-column-funnel-through-funnel-widget-value"><?=$data['value']?></div>
								<div class="crm-report-column-funnel-through-funnel-widget-percent"><?=$data['unitOfMeasurement']?></div>
							</div>
						</div>
						<div class="crm-report-column-funnel-through-funnel-widget crm-report-column-funnel-through-funnel-widget-1">

							<div class="crm-report-column-funnel-through-funnel-widget-item"
								 style="height: <?=$data['value']?>%; background-color: <?=$data['color']?>;"></div>
						</div>
					</td>
				<?else:?>
					<td class="crm-report-column-funnel-td">
						<?if($i === 0):?>
							<div class="crm-report-column-funnel-scale">
								<div class="crm-report-column-funnel-scale-box">
								</div>
							</div>
						<?endif;?>
						<div data-role="<?=$columnsContainerIds[$i]?>" class="crm-report-column-funnel-through-funnel-widget crm-report-column-funnel-through-funnel-widget-columns"></div>
					</td>
				<?endif;?>
			<?endforeach;?>



<!--			<td class="crm-report-column-funnel-td">-->
<!--				<div data-role="second-columns-container" class="crm-report-column-funnel-through-funnel-widget crm-report-column-funnel-through-funnel-widget-columns"></div>-->
<!--			</td>-->



		</tr>




		<tr class="crm-report-column-funnel-tr">

			<?foreach ($widgetData as $i => $data):?>
				<?if (!isset($data['singleData']) || $data['singleData'] === false):?>
					<th class="crm-report-column-funnel-th crm-report-column-funnel-card-title"><?=$data['title']?></th>
				<?endif;?>
			<?endforeach;?>
		</tr>
		<tr class="crm-report-column-funnel-tr">
		</tr>
		<tr class="crm-report-column-funnel-tr">

			<?foreach ($widgetData as $i => $data):?>
				<?if (!isset($data['singleData']) || $data['singleData'] === false):?>
					<td class="crm-report-column-funnel-td">
						<?if(!empty($data['firstAdditionalTitleAmount']) && !empty($data['firstAdditionalValueAmount'])):?>
							<div class="crm-report-column-funnel-card-info">
								<div class="crm-report-column-funnel-card-info-item">
									<div class="crm-report-column-funnel-card-subtitle"><?=$data['firstAdditionalTitleAmount']?></div>
									<div class="crm-report-column-funnel-card-info-value-box">
										<div class="crm-report-column-funnel-card-info-value"><?=$data['firstAdditionalValueAmount']?></div>
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
					</td>
				<?elseif (!empty($data['singleData'])):?>
					<td class="crm-report-column-funnel-td">
						<div class="crm-report-column-funnel-card-info">

						</div>
					</td>
				<?endif;?>
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
		<div data-role="info-popup-top-card" class="crm-report-column-funnel-card-info">
			<div class="crm-report-column-funnel-card-info-item">
				<div data-role="info-popup-top-title" class="crm-report-column-funnel-card-subtitle"></div>
				<div class="crm-report-column-funnel-card-info-value-box">
					<div data-role="info-popup-top-value" class="crm-report-column-funnel-card-info-value">245 864 &#x20bd</div>
				</div>
			</div>
		</div>
		<div class="crm-report-column-funnel-card-info">
			<div class="crm-report-column-funnel-card-info-item">
				<div data-role="info-popup-first-title" class="crm-report-column-funnel-card-subtitle"></div>
				<div class="crm-report-column-funnel-card-info-value-box">
					<div data-role="info-popup-first-value" class="crm-report-column-funnel-card-info-value"></div>
				</div>
			</div>
		</div>
	</div>
</div>


