<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

$APPLICATION->SetTitle(Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_RESULT_PAGETITLE'));

\Bitrix\Main\UI\Extension::load("ui.common");
\Bitrix\Main\UI\Extension::load("ui.alerts");

?>
<div class="intranet-stresslevel-instruction-wrapper">
	<div class="intranet-stresslevel-instruction-img intranet-stresslevel-instruction-img-<?=htmlspecialcharsbx($arResult['LAST_DATA']['type'])?>">
		<div class="intranet-stresslevel-instruction-img-arrow" style="transform: rotate(<?=intval($arResult['LAST_DATA']['value']/100*180)?>deg)"></div>
		<div class="intranet-stresslevel-instruction-img-title"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_RESULT_IMAGE_CAPTION')?></div>
		<div class="intranet-stresslevel-instruction-img-value"><?=$arResult['LAST_DATA']['value']?></div>
		<div class="intranet-stresslevel-instruction-img-text"><?=$arResult['LAST_DATA_TYPE_DESCRIPTION']?></div>
	</div>
	<div class="ui-title-2"><?=$arResult['LAST_DATA_TYPE_TEXT_TITLE']?></div>
	<div class="ui-text-2"><?=$arResult['LAST_DATA_TYPE_TEXT']?></div>
	<div class="ui-alert ui-alert-primary ui-alert-icon-warning">
		<span class="ui-alert-message"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_RESULT_PAGETITLE_LINK', [
				'#A_BEGIN#' => '<a href="'.Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_RESULT_PAGETITLE_LINK_HREF').'" target="_blank">',
				'#A_END#' => '</a>'
		])?></span>
	</div>
</div>
<?
if (!empty($arResult['HISTORIC_DATA']))
{
	?>
	<div class="intranet-stresslevel-instruction-wrapper">
		<div class="ui-title-2"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_RESULT_DYN_TITLE')?></div>
		<div class="intranet-stresslevel-instruction-graph">
			<div class="intranet-stresslevel-instruction-graph-col">
				<div class="intranet-stresslevel-instruction-graph-col-param">100</div>
				<div class="intranet-stresslevel-instruction-graph-col-param">50</div>
				<div class="intranet-stresslevel-instruction-graph-col-param">0</div>
			</div>
			<?
			foreach($arResult['HISTORIC_DATA'] as $measure)
			{
				?>
				<div class="intranet-stresslevel-instruction-graph-col">
					<div class="intranet-stresslevel-instruction-graph-col-graph intranet-stresslevel-instruction-graph-col-graph-<?=htmlspecialcharsbx($measure['type'])?>" style="height: <?=intval($measure['value'])?>%;"></div>
					<div class="intranet-stresslevel-instruction-graph-col-info">
						<div class="intranet-stresslevel-instruction-graph-col-date"><?=$measure['date']->format('d.m')?></div>
						<div class="intranet-stresslevel-instruction-graph-col-value"><?=intval($measure['value'])?></div>
					</div>
				</div>
				<?
			}
			?>
		</div>
	</div>
	<?
}
?>
