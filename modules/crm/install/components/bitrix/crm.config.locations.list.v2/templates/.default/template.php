<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Location\Admin\LocationHelper as Helper;

Loc::loadMessages(__FILE__);
?>

<?if(!empty($arResult['ERRORS']['FATAL'])):?>

	<?foreach($arResult['ERRORS']['FATAL'] as $error):?>
		<?=ShowError($error)?>
	<?endforeach?>

<?else:?>

	<?foreach($arResult['ERRORS']['NONFATAL'] as $error):?>
		<?=ShowError($error)?>
	<?endforeach?>

	<?
	global $APPLICATION;
	$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

	$arResult['GRID_DATA'] = $arColumns = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arColumns[$arHead['id']] = false;
	}
	foreach($arResult['LOCS'] as $key => &$arLoc)
	{
		$arActions = array();

		if($arResult['CAN_EDIT'])
		{
			$arActions[] =  array(
				'ICONCLASS' => 'view',
				'TITLE' => Loc::getMessage('CRM_CLL2_EDIT_TITLE'),
				'TEXT' => Loc::getMessage('CRM_CLL2_EDIT'),
				'ONCLICK' => "bxCRMLLTInstance.editItem('".CUtil::JSEscape($arLoc['PATH_TO_LOCATIONS_EDIT'])."', '".CUtil::JSEscape($arParams['PATH_TO_LOCATIONS_LIST']).(strlen($arResult['FILTER_VALUES']['PARENT_ID']) ? '?PARENT_ID='.intval($arResult['FILTER_VALUES']['PARENT_ID']) : '')."')",
			);
		}

		if ($arResult['CAN_DELETE'])
		{
			$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'delete',
				'TITLE' => Loc::getMessage('CRM_CLL2_DELETE_TITLE'),
				'TEXT' => Loc::getMessage('CRM_CLL2_DELETE'),
				'ONCLICK' => 'bxCRMLLTInstance.deleteGrid(\''.CUtil::JSEscape(Loc::getMessage('CRM_CLL2_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(Loc::getMessage('CRM_CLL2_DELETE_CONFIRM'), htmlspecialcharsbx($arLoc['NAME_'.ToUpper(LANGUAGE_ID)]))).'\', \''.CUtil::JSEscape(Loc::getMessage('CRM_CLL2_DELETE')).'\', \''.CUtil::JSEscape($arLoc['PATH_TO_LOCATIONS_DELETE']).'\')'
			);
		}

		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => Loc::getMessage('CRM_CLL2_VIEW_SUBTREE_TITLE'),
			'TEXT' => Loc::getMessage('CRM_CLL2_VIEW_SUBTREE'),
			'ONCLICK' => "bxCRMLLTInstance.viewSubtree(".$arLoc['ID'].")",
			'DEFAULT' => true
		);

		$arResult['GRID_DATA'][] = array(
			'id' => $key,
			'actions' => $arActions,
			'data' => $arLoc,
			'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
			/*
			'columns' => array(
				'COUNTRY_NAME' => htmlspecialcharsbx($arLoc['COUNTRY_NAME']),
				'REGION_NAME' => htmlspecialcharsbx($arLoc['REGION_NAME']),
				'CITY_NAME' => htmlspecialcharsbx($arLoc['CITY_NAME']),
				'SORT' => $arLoc['SORT']
			)
			*/
		);
	}
	unset($arLoc);

	// rearrange appearance a bit
	foreach($arResult['FILTER'] as &$fld)
	{
		if($fld['id'] == 'PARENT_ID')
		{
			$fld['type'] = 'custom';
			$fld['settingsHtml'] = '';

			ob_start();
			$APPLICATION->IncludeComponent("bitrix:sale.location.selector.search"/*.Helper::getWidgetAppearance()*/, "", array(
				"ID" => intval($arResult['FILTER_VALUES']['PARENT_ID']) ? intval($arResult['FILTER_VALUES']['PARENT_ID']) : '',
				"INPUT_NAME" => "PARENT_ID",
				"PROVIDE_LINK_BY" => "id",
				"SHOW_ADMIN_CONTROLS" => 'N',
				"SELECT_WHEN_SINGLE" => 'N',
				"FILTER_BY_SITE" => 'N',
				"SHOW_DEFAULT_LOCATIONS" => 'N',
				"SEARCH_BY_PRIMARY" => 'N',

				"ADMIN_MODE" => 'Y',
				"JS_CONTROL_GLOBAL_ID" => 'crm_filter_parent_id'
				),
				false
			);
			$fld['value'] = ob_get_contents();
			ob_end_clean();
		}

		if($fld['id'] == 'TYPE_ID')
		{
			$fld['type'] = 'list';
			$fld['items'][''] = Loc::getMessage('CRM_CLL2_NOT_SELECTED');
			foreach($arResult['TYPES'] as $k => $v)
				$fld['items'][$k] = $v;
		}

		if(in_array($fld['id'], array('ID', 'LATITUDE', 'LONGITUDE', 'SORT')))
		{
			$fld['type'] = 'number';
		}
	}
	?>

	<script>
		bxCRMLLTInstance = new BX.crmLocationListTools();
	</script>

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.grid',
		'',
		array(
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'SORT' => $arResult['SORT'],
			'SORT_VARS' => $arResult['SORT_VARS'],
			'ROWS' => $arResult['GRID_DATA'],
			'FOOTER' =>
			array(
				array(
					'title' => Loc::getMessage('CRM_CLL2_ALL'),
					'value' => $arResult['ROWS_COUNT']
				)
			),
			'EDITABLE' => $arResult['CAN_EDIT'],
			'ACTIONS' =>
				array(
					'delete' => $arResult['CAN_DELETE'],
					'list' => array()
				),
			'ACTION_ALL_ROWS'=>true,
			'NAV_OBJECT'=>$arResult['NAV_RESULT'],
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => $arResult['TAB_ID'],
			'EDITABLE'=>true,
			'AJAX_MODE' => 'N',
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS']
		),
		$component
	);
	?>

<?endif?>
