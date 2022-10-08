<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Localization\Loc;

/**
 *  @var array $arParams
 *  @var array $arResult
 *  @var CrmOrderConnectorInstagramView $component
 *  @var CBitrixComponentTemplate $this
 *  @var string $templateName
 *  @var string $templateFolder
 *  @var string $componentPath
 */

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background');

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'currency',
	'date',
	'sidepanel',
	'ui.alerts',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.notification',
	'ui.tilegrid',
	'ui.icons',
	'ui.progressbar',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/crm.order.import.instagram.edit/templates/.default/style.css');

$this->setViewTarget('pagetitle', 10);
?>
<div class="pagetitle-container pagetitle-align-right-container" data-tile-grid="tile-grid-stop-close">
	<button class="ui-btn ui-btn-primary"
			data-entity="tile-grid-import-more"
			style="display: none;">
		<?=Loc::getMessage('CRM_OIIV_IMPORT_MORE')?>
	</button>
	<a class="ui-btn ui-btn-md ui-btn-light-border"
			href="<?=htmlspecialcharsbx($arParams['PATH_TO_CONNECTOR_INSTAGRAM_FEEDBACK'])?>">
		<?=Loc::getMessage('CRM_OIIV_IMPORT_FEEDBACK');?>
	</a>
	<a class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting"
			href="<?=htmlspecialcharsbx($arParams['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL'])?>"></a>
</div>
<?
$this->endViewTarget();

include 'messages.php';

if ($arResult['FORM']['STEP'] == '3')
{
	$logo = isset($arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL'])
		? ' style="background-image: url('.$arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL'].');"'
		: '';
	?>
	<div class="crm-order-instagram-view" data-entity="parent-container">
		<div data-entity="import-selector">
			<div class="crm-order-instagram-edit-block">
				<div class="crm-order-instagram-edit-header">
					<span class="crm-order-instagram-edit-header-logo"<?=$logo?>></span>
					<div class="crm-order-instagram-edit-content-inner">
						<div class="crm-order-instagram-edit-title">
							<span class="crm-order-instagram-edit-name"><?=$arResult['FORM']['PAGE']['INSTAGRAM']['NAME']?></span>
							<?=Loc::getMessage('CRM_OIIV_INSTAGRAM_CONNECTED')?>
						</div>
						<div class="crm-order-instagram-edit-desc">
							<span class="crm-order-instagram-edit-decs-text" data-entity="step-description-text"></span>
						</div>
					</div>
				</div>
			</div>
			<div class="crm-order-instagram-edit-block crm-order-instagram-edit-block-view">
				<div data-tile-grid="tile-grid-stop-close" data-entity="filter">
					<?
					$APPLICATION->IncludeComponent(
						'bitrix:main.ui.filter',
						'',
						[
							'GRID_ID' => $arResult['GRID']['ID'],
							'FILTER_ID' => $arResult['FILTER']['FILTER_ID'],
							'FILTER' => $arResult['FILTER']['FILTER'],
							'FILTER_PRESETS' => $arResult['FILTER']['FILTER_PRESETS'],
							'ENABLE_LIVE_SEARCH' => $arResult['FILTER']['ENABLE_LIVE_SEARCH'],
							'ENABLE_LABEL' => $arResult['FILTER']['ENABLE_LABEL'],
							'RESET_TO_DEFAULT_MODE' => $arResult['FILTER']['RESET_TO_DEFAULT_MODE'],
						],
						$component
					);
					?>
				</div>
				<div class="crm-order-instagram-view-btn" data-tile-grid="tile-grid-stop-close" data-entity="select-controls">
					<button class="ui-btn ui-btn-light-border" data-entity="tile-grid-select-all">
						<?=Loc::getMessage('CRM_OIIV_SELECT_ALL')?>
					</button>
					<button class="ui-btn ui-btn-light-border" data-entity="tile-grid-unselect-all">
						<?=Loc::getMessage('CRM_OIIV_UNSELECT_ALL')?>
					</button>
				</div>
				<?
				if (!empty($arResult['GRID']))
				{
					$generateEmptyBlock = 'function()
						{
							if (BX.Main.tileGridManager && BX.Main.tileGridManager.data["0"] && BX.Main.tileGridManager.data["0"].instance.container)
							{
								BX.Main.tileGridManager.data["0"].instance.container.style = "";
							}
							
							var controls = document.querySelector(\'[data-entity="select-controls"]\');
							if (BX.type.isDomNode(controls))
							{
								BX.hide(controls);
							}
							
							return BX.create("div", {
								props: {
									className: "crm-order-instagram-view-block"
								},
								children: [
									BX.create("div", {
										props: {
											className: "crm-order-instagram-view-block-empty-icon"
										},
									}),
									BX.create("div", {
										props: {
											className: "crm-order-instagram-view-block-subtitle"
										},
										text: "'.Loc::getMessage('CRM_OIIV_NO_MEDIA').'"
									}),
								]
							});
						}'
					;

					$APPLICATION->IncludeComponent(
						'bitrix:main.ui.grid',
						'',
						[
							'TILE_GRID_MODE' => true,
							'TILE_SIZE' => $arResult['GRID']['VIEW_SIZE'],
							'TILE_GRID_ITEMS' => $arResult['GRID']['TILE_ITEMS'],
							'JS_CLASS_TILE_GRID_ITEM' => 'BX.Crm.Instagram.TileGrid.Item',
							'JS_TILE_GRID_GENERATOR_EMPTY_BLOCK' => $generateEmptyBlock,
							//'DATA_FOR_PAGINATION' => $arResult['GRID']['DATA_FOR_PAGINATION'],
							'MODE' => $arResult['GRID']['MODE'],
							'GRID_ID' => $arResult['GRID']['ID'],
							'HEADERS' => $arResult['GRID']['HEADERS'],
							'SORT' => $arResult['GRID']['SORT'],
							'SORT_VARS' => $arResult['GRID']['SORT_VARS'],
							'ROWS' => $arResult['GRID']['ROWS'],

							'AJAX_MODE' => 'Y',
							'AJAX_OPTION_JUMP' => 'N',
							'AJAX_OPTION_STYLE' => 'Y',
							'AJAX_OPTION_HISTORY' => 'N',

							'SHOW_CHECK_ALL_CHECKBOXES' => true,
							'SHOW_ROW_CHECKBOXES' => true,
							'SHOW_ROW_ACTIONS_MENU' => true,
							'SHOW_GRID_SETTINGS_MENU' => true,
							'SHOW_NAVIGATION_PANEL' => true,
							'SHOW_PAGINATION' => true,
							'SHOW_SELECTED_COUNTER' => true,
							'SHOW_TOTAL_COUNTER' => false,
							'SHOW_PAGESIZE' => true,
							'SHOW_ACTION_PANEL' => false,
							'ALLOW_CONTEXT_MENU' => true,

							//'ACTION_PANEL' => $arResult['GRID']['ACTION_PANEL'],
							'NAV_OBJECT' => $arResult['GRID']['NAV_OBJECT'],
							'~NAV_PARAMS' => [
								'SHOW_COUNT' => 'N',
								'SHOW_ALWAYS' => false,
							],

							//'EDITABLE' => !$arResult['GRID']['ONLY_READ_ACTIONS'],
							'ALLOW_EDIT' => true,
						],
						$component
					);
				}
				?>
			</div>
		</div>
	</div>
	<div data-entity="footer" data-tile-grid="tile-grid-stop-close"></div>
	<div data-entity="store-footer" data-tile-grid="tile-grid-stop-close"></div>
	<div data-entity="step-import-footer" data-tile-grid="tile-grid-stop-close"></div>

	<?
	switch (LANGUAGE_ID)
	{
		case 'ru':
			$iconClass = 'crm-order-instagram-view-block-b24-icon'; break;
		default:
			$iconClass = 'crm-order-instagram-view-block-b24-icon crm-order-instagram-view-block-b24-icon-en'; break;
	}
	?>
	<div data-entity="import-step-process" style="display: none;">
		<div class="crm-order-instagram-edit-block">
			<div class="crm-order-instagram-view-block">
				<div class="crm-order-instagram-view-block-title">
					<span data-entity="step-import-title"><?=Loc::getMessage('CRM_OIIV_STEP_PROCESS_TITLE')?></span><span
							class="crm-order-instagram-view-block-title-dots" data-entity="step-import-pointer">&nbsp;&nbsp;&nbsp;</span>
				</div>
				<div class="crm-order-instagram-view-block-process">
					<div class="ui-icon ui-icon-service-instagram-fb crm-order-instagram-view-block-process-icon"><i></i></div>
					<div class="<?=$iconClass?>"></div>
				</div>
				<div data-entity="progress-bar"></div>
			</div>
		</div>
	</div>
	<?
}
?>
<script>
	BX.ready(function()
	{
		if (BX.Currency)
		{
			BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true)?>);
		}

		BX.message(<?=CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__))?>);

		window.top.BX.UI.Notification.Center.setStackDefaults(
			window.top.BX.UI.Notification.Position.TOP_RIGHT,
			{
				offsetX: 79,
				offsetY: 74
			}
		);

		if (window.top.BX.UI.Notification.Center.getBalloonById('new-media-notification'))
		{
			window.top.BX.UI.Notification.Center.getBalloonById('new-media-notification').close();
		}

		<?
		if (!empty($arResult['NOTIFICATIONS']))
		{
			foreach ($arResult['NOTIFICATIONS'] as $notification)
			{
				?>
				window.top.BX.UI.Notification.Center.notify({
					content: '<?=$notification?>',
					category: 'InstagramStore::general',
					width: 'auto'
				});
				<?
			}

			$component::markSessionNotificationsRead();
		}
		?>

		new BX.Crm.Instagram.TileGrid.List({
			params: <?=CUtil::PhpToJSObject($arParams)?>,
			tileItems: <?=CUtil::PhpToJSObject($arResult['GRID']['TILE_ITEMS'])?>,
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName()?>',
			haveItemsToImport: !!'<?=$arResult['HAVE_ITEMS_TO_IMPORT']?>',
			lastViewedTimestamp: '<?=CUtil::JSEscape($arResult['LAST_VIEWED_TIMESTAMP'])?>',
			gridId: '<?=CUtil::JSEscape($arResult['GRID']['ID'])?>',
			filterId: '<?=CUtil::JSEscape($arResult['FILTER']['FILTER_ID'])?>',
		});

		if (window.top === window)
		{
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							'<?=CUtil::JSEscape($arParams['PATH_TO_CONNECTOR_INSTAGRAM_EDIT'])?>'
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 700
						}
					},
					{
						condition: [
							'<?=CUtil::JSEscape($arParams['PATH_TO_CONNECTOR_INSTAGRAM_FEEDBACK'])?>'
						],
						options: {
							cacheable: false,
							allowChangeHistory: false,
							width: 580
						}
					}
				]
			});
		}
	});
</script>