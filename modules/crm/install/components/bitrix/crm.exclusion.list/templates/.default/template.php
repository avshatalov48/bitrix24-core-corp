<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

\Bitrix\Main\Loader::includeModule('ui');

/** @var CMain $APPLICATION */
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

Toolbar::deleteFavoriteStar();
Toolbar::addFilter([
	"FILTER_ID" => $arParams['FILTER_ID'],
	"GRID_ID" => $arParams['GRID_ID'],
	"FILTER" => $arResult['FILTERS'],
	"DISABLE_SEARCH" => true,
	"ENABLE_LABEL" => true,
]);

if ($arParams['CAN_EDIT'])
{
	$button = new Bitrix\UI\Buttons\Button([
		'text' => Loc::getMessage('CRM_EXCLUSION_LIST_BTN_ADD'),
		'link' => htmlspecialcharsbx($arParams['PATH_TO_IMPORT']),
		'color' => Color::PRIMARY,
		'icon' => Icon::ADD,
	]);
	$button->addAttribute('id', 'CRM_EXCLUSION_BUTTON_ADD');
	Toolbar::addButton($button, ButtonLocation::AFTER_TITLE);
}

Extension::load("ui.buttons");
Extension::load("ui.buttons.icons");


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
	<script>
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
