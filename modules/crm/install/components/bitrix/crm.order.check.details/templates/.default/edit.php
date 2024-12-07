<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Localization\Loc;

CJSCore::Init(array('popup', 'sidepanel'));

Loc::loadMessages(__FILE__);
//\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_form.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/crm.entity.details/templates/.default/script.js');
//\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/crm.entity.editor/templates/.default/script.js');


\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.buttons.icons', 'ui.design-tokens', 'ui.fonts.opensans']);
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/slider.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/entity-editor/css/style.css');

?>

<form method="POST" id="check_add" name="check_add">
	<?= bitrix_sessid_post();?>
	<div id="form_wrapper">
		<div id="check_form_error_block">
		</div>
		<table id="add_form" name="adding_form" class="crm-check-add-table">
			<tr>
				<td>
					<div class="crm-entity-card-container" style="width: 100%">
						<div class="crm-entity-card-container-content">
							<div class="crm-entity-card-widget">
								<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-select" data-cid="MAIN_ENTITY">
									<input name="MAIN_ENTITY" type="hidden" value="NEW">
									<div class="crm-entity-widget-content-block-title">
										<?=Loc::getMessage('CRM_ORDER_CHECK_MAIN_ENTITY')?>
									</div>
									<div id="crm-order-check-add-main-entity" class="crm-entity-widget-content-block-inner crm-entity-widget-content-block-select">
										<div class="crm-entity-widget-content-select"><?=$arResult['DEFAULT_MAIN_ENTITY']['NAME']?></div>
									</div>
								</div>
								<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-select" data-cid="TYPE">
									<input name="TYPE" type="hidden" value="NEW">
									<div class="crm-entity-widget-content-block-title">
										<?=Loc::getMessage('CRM_ORDER_CHECK_TYPE')?>
									</div>
									<div id="crm-order-check-add-type" class="crm-entity-widget-content-block-inner crm-entity-widget-content-block-select">
										<div class="crm-entity-widget-content-select"><?=$arResult['CHECK_TYPES'][0]['NAME']?></div>
									</div>
								</div>
								<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-custom-checkbox" data-cid="">
									<div id="crm-order-check-add-addition-title" class="crm-entity-widget-content-block-title">
										<?=Loc::getMessage('CRM_ORDER_CHECK_ADDITION_ENTITY')?>
									</div>
									<div id="crm-order-check-add-addition-entity" class="crm-entity-widget-content-block-inner">
									</div>
								</div>
							</div>
						</div>
					</div>
				</td>
			</tr>
		</table>
	</div>
	<div class="crm-footer-container">
		<div class="crm-entity-section-control">
			<button class="ui-btn ui-btn-success" id="add_check_button" name="save" type="button">
				<span class="webform-small-button-text"><?= Loc::getMessage('CRM_BUTTONS_SAVE')?></span>
			</button>
			<a class="ui-btn ui-btn-link" id="cancel_check_button" name="cancel"><?= Loc::getMessage('CRM_BUTTONS_CANCEL')?></a>
		</div>
	</div>
</form>
<?
$jsData = array(
	'AJAX_URL' => '/bitrix/components/bitrix/crm.order.check.details/ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'SHOW_URL' => $arResult['PATH_TO_ORDER_SHOW'],
	'MAIN_LIST' => $arResult['MAIN_LIST'],
	'ADDITION_LIST' => $arResult['ADDITION_LIST'],
	'TYPE_LIST' => $arResult['CHECK_TYPES'],
	'IS_MULTIPLE' => $arResult['IS_MULTIPLE'],
	'DATA' => array(
		'MAIN' => $arResult['DEFAULT_MAIN_ENTITY'],
		'TYPE' => $arResult['CURRENT_TYPE'],
		'ORDER_ID' => $arResult['ORDER_ID']
	)
);
?>
<script>
	BX.CrmOrderCheckDetails.Edit.messages =
		{

		};

	BX.CrmOrderCheckDetails.Edit.create(<?=CUtil::PhpToJSObject($jsData)?>);
</script>