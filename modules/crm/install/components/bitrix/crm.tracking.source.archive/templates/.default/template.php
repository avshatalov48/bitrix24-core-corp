<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

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

	if ($data['NAME'])
	{
		$detailUrl = \CUtil::JSEscape(htmlspecialcharsbx($data['URLS']['EDIT']));
		$data['NAME'] = "<a href=\"$detailUrl\">{$data['NAME']}</a>";;
	}

	$actions = array();
	if ($arParams['CAN_EDIT'])
	{
		$actions[] = array(
			'TITLE' => Loc::getMessage('CRM_TRACKING_ARCHIVE_BTN_REMOVE_TITLE'),
			'TEXT' => Loc::getMessage('CRM_TRACKING_ARCHIVE_BTN_REMOVE'),
			'ONCLICK' => "BX.Crm.Tracking.Source.Archive.unarchive({$data['ID']});"
		);
	}

	$arResult['ROWS'][$index] = array(
		'id' => $data['ID'],
		'columns' => $data,
		'actions' => $actions
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.feedback.form',
	'',
	\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
);

$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));
if ($arParams['CAN_EDIT'])
{
	/*
	$button = $snippet->getRemoveButton();
	$button['TEXT'] = Loc::getMessage('CRM_TRACKING_ARCHIVE_BTN_REMOVE');
	$button['TITLE'] = Loc::getMessage('CRM_TRACKING_ARCHIVE_BTN_REMOVE_TITLE');
	$controlPanel['GROUPS'][0]['ITEMS'][] = $button;
	*/
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
		'SHOW_ROW_CHECKBOXES' => false,
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
			BX.Crm.Tracking.Source.Archive.init(<?=Json::encode([
				'messages' => $arResult['MESSAGES'],
				"gridId" => $arParams['GRID_ID'],
				'signedParameters' => $this->getComponent()->getSignedParameters(),
				'componentName' => $this->getComponent()->getName(),
				'mess' => []
			])?>);
		});
	</script>
<?