<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$containerId = 'crm-tracking-entity-details-view';
?>
<div id="<?=htmlspecialcharsbx($containerId)?>">
	<?if (empty($arResult['TRACES'])):?>
		<div data-role="trace/def">
			<span style="opacity: 0.5;">
				<?=(
					!empty($arResult['SOURCES'][0]['NAME'])
						? $arResult['SOURCES'][0]['NAME']
						: Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_ORGANIC')
				)?>
			</span>
		</div>
	<?else:?>
	<div id="crm-tracking-entity-details" class="crm-tracking-entity-details">
		<?
		$traceCounter = 0;
		foreach ($arResult['TRACES'] as $trace):
			$traceCounter++;
			?>
		<div class="crm-tracking-entity-details-path-block-inner <?=($traceCounter > 1 ? 'crm-tracking-entity-details-hidden' : '')?>"
			data-role="trace"
		>
			<div data-role="trace/header" class="crm-tracking-entity-details-head">
				<?if (false && $trace['SOURCE']):?>
					<div class="crm-tracking-entity-details-path-info">
						<span class="crm-tracking-entity-details-path-icon <?=htmlspecialcharsbx($arResult['DATA']['SOURCE']['ICON_CLASS'])?>">
							<i></i>
						</span>
						<span class="crm-tracking-entity-details-path-name">
							<?=htmlspecialcharsbx($trace['SOURCE']['NAME'])?>
						</span>
					</div>
				<?endif;?>

				<?
				$APPLICATION->includeComponent(
					'bitrix:crm.tracking.entity.path',
					'',
					[
						'ENTITY_TYPE_ID' => $arParams['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $arParams['ENTITY_ID'],
						'TRACE_ID' => $trace['ROW']['ID'],
						'SKIP_SOURCE' => false,
					]
				);
				?>

				<div class="crm-tracking-entity-details-toggler">
					<div class="crm-tracking-entity-details-overlay"></div>
					<?if($traceCounter <= 1):?>
						<div data-role="trace/edit" class="crm-tracking-entity-details-edit"></div>
					<?endif;?>
					<div data-role="trace/view" class="crm-tracking-entity-details-show"
						data-caption-show="<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_SHOW')?>"
						data-caption-hide="<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_HIDE')?>"
					></div>
				</div>
			</div>

			<?if (isset($trace['SITE'], $trace['PAGES']) && $trace['SITE'] && $trace['PAGES']):?>
				<div data-role="trace/details" class="crm-tracking-entity-details-body">
					<div class="crm-tracking-entity-details-path-header"></div>
					<div class="crm-tracking-entity-details-path-site">
						<span class="crm-tracking-entity-details-path-name">
							<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_PAGES')?>:
						</span>
						<a target="_blank"
							href="http://<?=htmlspecialcharsbx($trace['SITE']['DOMAIN'])?>"
							class="crm-tracking-entity-details-path-site-link"
						>
							<?=htmlspecialcharsbx($trace['SITE']['DOMAIN'])?>
						</a>
						<!--
						<span id="crm-tracking-entity-details-toggler"
							class="crm-tracking-entity-details-path-tip"
						></span>
						-->
					</div>

					<div title="<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_DEVICE_' . ($trace['IS_MOBILE'] ? 'MOBILE' : 'DESKTOP'))?>"
						class="crm-tracking-entity-details-device crm-tracking-entity-details-device-<?=($trace['IS_MOBILE'] ? 'mobile' : 'desktop')?>"
					></div>

					<div class="crm-tracking-entity-details-popup-list">
						<br>
						<?foreach ($trace['PAGES'] as $pageIndex => $page):?>
							<div class="crm-tracking-entity-details-popup-item">
								<span class="crm-tracking-entity-details-popup-time">
									<?=((!$pageIndex) && !empty($page['IS_REF'])
										? Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_REF_DOMAIN')
										: htmlspecialcharsbx($page['DATE_INSERT'])
									)?>
								</span>
								<a target="_blank" href="<?=\CUtil::JSEscape(htmlspecialcharsbx($page['URL']))?>"
									class="crm-tracking-entity-details-popup-decs"
								>
									<?=htmlspecialcharsbx($page['TITLE'] ?: Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_NO_TITLE'))?>
								</a>
							</div>
						<?endforeach;?>
					</div>
				</div>
			<?endif;?>

		</div>
		<?endforeach;?>
	</div>
	<?endif;?>
	<script>
		BX.ready(function () {
			let stopPropagation = true;
			let context = BX('<?=$containerId?>');
			if (context)
			{
				let blocks = context.querySelectorAll('[data-role="trace"]');
				Array.prototype.slice.call(blocks).forEach(function(block)
				{
					let edit = block.querySelector('[data-role="trace/edit"]');
					let view = block.querySelector('[data-role="trace/view"]');

					let className = 'crm-tracking-entity-details-hidden';
					let adjustText = function()
					{
						view.textContent = BX.hasClass(block, className)
							? view.getAttribute('data-caption-show')
							: view.getAttribute('data-caption-hide')
					};
					adjustText();
					BX.bind(view, 'click', function()
					{
						BX.hasClass(block, className)
							? BX.removeClass(block, className)
							: BX.addClass(block, className);

						adjustText();
					});
					BX.bind(edit, 'mouseup', function()
					{
						stopPropagation = false;
					});
				}, this);

				let defView = context.querySelector('[data-role="trace/def"]');
				if (defView)
				{
					BX.bind(defView, 'mouseup', function()
					{
						stopPropagation = false;
					});
				}

				let handler = function(e)
				{
					if (!stopPropagation)
					{
						stopPropagation = true;
						return;
					}
					e.stopPropagation();
					e.stopImmediatePropagation();
				};
				BX.bind(context, 'mouseup', handler);
			}
		})
	</script>
</div>
<?
