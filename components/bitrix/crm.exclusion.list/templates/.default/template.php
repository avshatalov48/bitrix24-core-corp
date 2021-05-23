<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

foreach ($arResult['ERRORS'] as $error)
{
	ShowError($error);
}

foreach ($arResult['ROWS'] as $index => $data)
{
	foreach ($data as $dataKey => $dataValue)
	{
		if (is_string($data[$dataKey]))
		{
			$data[$dataKey] = htmlspecialcharsbx($dataValue);
		}
	}

	if ($data['TYPE_NAME'])
	{
		$data['TYPE_ID'] = $data['TYPE_NAME'];
	}

	$actions = array();
	if ($arParams['CAN_EDIT'])
	{
		$actions[] = array(
			'TITLE' => Loc::getMessage('CRM_EXCLUSION_LIST_BTN_REMOVE_TITLE'),
			'TEXT' => Loc::getMessage('CRM_EXCLUSION_LIST_BTN_REMOVE'),
			'ONCLICK' => "BX.Crm.Exclusion.Grid.removeFromExclusions({$data['ID']});"
		);
	}

	$arResult['ROWS'][$index] = array(
		'id' => $data['ID'],
		'columns' => $data,
		'actions' => $actions
	);
}


$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$bodyClass = ($bodyClass ? $bodyClass . ' ' : '') . ' pagetitle-toolbar-field-view ';
$APPLICATION->SetPageProperty('BodyClass', $bodyClass);
Extension::load("ui.buttons");
Extension::load("ui.buttons.icons");
?>
	<div class="pagetitle-container pagetitle-flexible-space">
		<?
		$APPLICATION->IncludeComponent(
			"bitrix:main.ui.filter",
			"",
			array(
				"FILTER_ID" => $arParams['FILTER_ID'],
				"GRID_ID" => $arParams['GRID_ID'],
				"FILTER" => $arResult['FILTERS'],
				"DISABLE_SEARCH" => true,
				"ENABLE_LABEL" => true,
			)
		);
		?>
	</div>
	<?if ($arParams['CAN_EDIT']):?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<a id="CRM_EXCLUSION_BUTTON_ADD"
			href="<?=htmlspecialcharsbx($arParams['PATH_TO_IMPORT'])?>"
			class="ui-btn ui-btn-primary ui-btn-icon-add"
		>
			<?=Loc::getMessage('CRM_EXCLUSION_LIST_BTN_ADD')?>
		</a>
	</div>
	<?endif;?>
<?
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}


$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));
if ($arParams['CAN_EDIT'])
{
	$button = $snippet->getRemoveButton();
	$button['TEXT'] = Loc::getMessage('CRM_EXCLUSION_LIST_BTN_REMOVE');
	$button['TITLE'] = Loc::getMessage('CRM_EXCLUSION_LIST_BTN_REMOVE_TITLE');
	$controlPanel['GROUPS'][0]['ITEMS'][] = $button;
}


$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arParams['GRID_ID'],
		"COLUMNS" => $arResult['COLUMNS'],
		"ROWS" => $arResult['ROWS'],
		"NAV_OBJECT" => $arResult['NAV_OBJECT'],
		"~NAV_PARAMS" => array('SHOW_ALWAYS' => false),
		'SHOW_ROW_CHECKBOXES' => true,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_PAGINATION' => true,
		'SHOW_SELECTED_COUNTER' => true,
		'SHOW_TOTAL_COUNTER' => true,
		'ACTION_PANEL' => $controlPanel,
		"TOTAL_ROWS_COUNT" => $arResult['TOTAL_ROWS_COUNT'],
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "N",
		"AJAX_OPTION_HISTORY" => "N"
	)
);


?>
	<script type="text/javascript">
		BX.ready(function () {
			BX.Crm.Exclusion.Grid.init(<?=Json::encode([
				'messages' => $arResult['MESSAGES'],
				"gridId" => $arParams['GRID_ID'],
				'signedParameters' => $this->getComponent()->getSignedParameters(),
				'componentName' => $this->getComponent()->getName(),
				'mess' => array()
			])?>);
		});
	</script>
<?