<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Page\Asset;

/** @var array $arParams */
/** @var array $arResult */
/** @var \CBitrixComponent $component */
/** @var \CBitrixComponentTemplate $this */
/** $global CMain $APPLICATION */
/** @global array $_GLOBALS */

global $_GLOBALS;
global $APPLICATION;

CJSCore::Init('fx');
Asset::getInstance()->addCss($this->GetFolder().'/splitter.css');
Asset::getInstance()->addJs($this->GetFolder().'/splitter.js');

$maxImageSize = array(
	'W' => \COption::GetOptionString('iblock', 'list_image_size'),
	'H' => \COption::GetOptionString('iblock', 'list_image_size')
);


function getImageField($property_value_id,$property_value)
{
	$res = CFileInput::Show('NO_FIELDS[' . $property_value_id . ']', $property_value, array(
			'IMAGE' => 'Y',
			'PATH' => false,
			'FILE_SIZE' => false,
			'DIMENSIONS' => false,
			'IMAGE_POPUP' => false,
			'MAX_SIZE' => array('W' => 50, 'H' => 50),
			'MIN_SIZE' => array('W' => 1, 'H' => 1),
		), array(
			'upload' => false,
			'medialib' => false,
			'file_dialog' => false,
			'cloud' => false,
			'del' => false,
			'description' => false,
		)
	);
	$res = preg_replace('!<script[^>]*>.*</script>!isU','', $res);
	$res = preg_replace('!onclick="ImgShw\([^)]+\)[^"]+" !isU','', $res);
	return $res;
}

/*$arSKUProps = $arResult['SKU_PROPS'];*/
$arFilter = $arResult['FILTER'];

$arHeaders = $arResult['HEADERS'];
$arPrices = $arResult['PRICES'];

$tableId = $arResult['TABLE_ID'];
$leftContainerId = $tableId.'_left_container';
$splitterBtnId = $tableId.'_splitter_btn';

$jsEventsManagerId = isset($_GET['JS_EVENTS_MANAGER_ID'])? strval($_GET['JS_EVENTS_MANAGER_ID']) : '';

// START TEMPLATE

// Grid options
$gridOptions = new CGridOptions($arResult['TABLE_ID']);
$arSort = $gridOptions->GetSorting(
	array(
		'sort' => array('name' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);
$arResult['SORT'] = $arSort['sort'];
$arResult['SORT_VARS'] = $arSort['SORT']['vars'] ?? null;
unset($arSort);
$arResult['ROWS_COUNT'] =
	($arResult['DB_RESULT_LIST'] instanceof \CDBResult) ? $arResult['DB_RESULT_LIST']->SelectedRowsCount() : 0;

$arResult['GRID_DATA'] = array();
foreach ($arResult['PRODUCTS'] as $productId => $arItems)
{
	$arActions = array();
	if ($arItems['TYPE'] === 'S')
	{
		$arActions[] = array(
			'ICONCLASS' => 'select',
			'TEXT' => GetMessage('SPS_SELECT'),
			'ONCLICK' => $tableId . '_helper.onSectionChange(' . $arItems['ID'] . ',"' . CUtil::JSEscape($arItems['NAME']) . '");',
			'DEFAULT' => true
		);

	}
	else
	{
		$params = array(
			'id' => $productId,
			'type' => $arItems['TYPE']
		);

		$arActions[] = array(
			'TEXT' => GetMessage('SPS_SELECT'),
			'ONCLICK' => $tableId . '_helper.SelEl('.CUtil::PhpToJSObject($params).', this);',
			'DEFAULT' => true
		);
	}
	$gridDataRecord = array(
		'id' => $arItems['TYPE'].$arItems['ID'],
		'actions' => $arActions,
		'data' => $arItems,
		'editable' => false,
		'columns' => [
			'NAME' => '<a class="crm-gds-item'.($arItems['TYPE'] === 'S' ? ' crm-gds-item-section' : ' crm-gds-item-gds').'">' . htmlspecialcharsbx($arItems['NAME']).'</a>',
			'ACTIVE' => $arItems['ACTIVE'] === 'Y' ? GetMEssage('SPS_PRODUCT_ACTIVE') : GetMEssage('SPS_PRODUCT_NO_ACTIVE'),
			'PREVIEW_PICTURE' => getImageField(
				'NO_FIELDS[' . $arItems['ID'] . '][PREVIEW_PICTURE]',
				$arItems['PREVIEW_PICTURE'] ?? null
			),
			'DETAIL_PICTURE' => getImageField(
				'NO_FIELDS[' . $arItems['ID'] . '][DETAIL_PICTURE]',
				$arItems['DETAIL_PICTURE'] ?? null
			)/*,
			'DETAIL_TEXT' => isset($arItems['DETAIL_TEXT']) ? nl2br(htmlspecialcharsbx($arItems['DETAIL_TEXT'])) : ''*/
		]
	);

	// Product properties
	if (is_array($arResult['PUBLIC_PROPS']) && !empty($arItems['DISPLAY_PROPERTIES']))
	{
		foreach (array_keys($arResult['PUBLIC_PROPS']) as $propId)
		{
			if (!empty($arItems['DISPLAY_PROPERTIES'][$propId]))
				$gridDataRecord['columns']['PROPERTY_'.$propId] = implode('&nbsp;/<br>', $arItems['DISPLAY_PROPERTIES'][$propId]);
		}
	}

	$arResult['GRID_DATA'][] = $gridDataRecord;
}
?>
<!-- START HTML -->
<div id="<?= htmlspecialcharsbx($leftContainerId) ?>" class="crm-catalog-left">
	<div class="crm-catalog-left-inner">
	<?
	// Tree
	$APPLICATION->IncludeComponent(
		'bitrix:crm.product.section.tree',
		'',
		array(
			'CATALOG_ID' => $arResult['IBLOCK_ID'],
			'SECTION_ID' => $arResult['SECTION_ID'],
			'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'] ?? null,
			'JS_EVENTS_MODE' => 'Y',
			'JS_EVENTS_MANAGER_ID' => $jsEventsManagerId
		),
		$component
	);?>
	</div>
</div>
<div class="crm-catalog-right">
	<div class="crm-catalog-right-inner">
		<div class="crm-catalog-search">
			<div class="crm-search-box">
				<table>
					<tr>
						<td class="crm-search-tag-cell"><span class="crm-search-tag"
							id="<?= $tableId ?>_section_label"
							style="<?= $arResult['SECTION_LABEL'] ? '' : 'display:none' ?>"><?= htmlspecialcharsbx($arResult['SECTION_LABEL']) ?>
								<span class="crm-search-tag-del"
									onclick="return <?= $tableId ?>_helper.onSectionChange('0')"></span></span>
						</td>
						<td class="crm-search-input-cell"><input type="text"
							value="<?= htmlspecialcharsbx($arFilter['QUERY'] ?? null) ?>"
							id="<?= $tableId ?>_query"
							onkeyup="<?= $tableId ?>_helper.onSearch(this.value)">
						</td>
					</tr>
				</table>
			</div>
			<span class="crm-catalog-search-icon" onclick="<?= $tableId ?>_helper.search();"></span><span class="crm-catalog-search-clear" id="<?= $tableId ?>_query_clear"
				style="<?= isset($arFilter['QUERY']) && !empty($arFilter['QUERY']) ? '' : 'display:none' ?>"
				onclick="return <?= $tableId ?>_helper.clearQuery()"></span>
		</div>
		<div class="crm-catalog-search-query-settings-container">
			<div class="crm-catalog-search-query-settings">
				<input
					type="checkbox"
					value="Y" <?=(($arFilter['USE_SUBSTRING_QUERY'] ?? null) == 'Y' ? ' checked="checked"' : '');?>
					name="USE_SUBSTRING_QUERY"
					id="<?= $tableId ?>_query_substring"
					onclick="return <?= $tableId ?>_helper.checkSubstring()"
				>&nbsp;<?=GetMessage('CRM_CPS_TPL_MESS_USE_SUBSTRING_QUERY'); ?>
			</div>
		</div>

		<form name="find_form" method="GET" action="<?= $APPLICATION->GetCurPage() ?>?"
			accept-charset="<?= LANG_CHARSET; ?>" id="<?= $tableId ?>_form">
			<input type="hidden" name="mode" value="list">
			<input type="hidden" name="SECTION_ID" value="<?= (int)$arResult['SECTION_ID'] ?>"
				id="<?= $tableId ?>_section_id">
			<input type="hidden" name="QUERY" value="<?= htmlspecialcharsbx($arFilter['QUERY'] ?? null) ?>"
				id="<?= $tableId ?>_query_value">
			<input
				type="hidden"
				name="USE_SUBSTRING_QUERY"
				value="<?=htmlspecialcharsbx($arFilter['USE_SUBSTRING_QUERY'] ?? null) ?>"
				id="<?= $tableId ?>_query_substring_value"
			>
			<input type="hidden" name="func_name" value="<?= htmlspecialcharsbx($arResult['JS_CALLBACK']) ?>">
			<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
			<input type="hidden" id="LID" name="LID" value="<?= $arResult['LID'] ?>">
			<input type="hidden" id="caller" name="caller" value="<?= $arResult['CALLER'] ?>">
			<input type="hidden" name="IBLOCK_ID" value="<?= (int)$arResult['IBLOCK_ID'] ?>"
				id="<?= $tableId ?>_iblock"/>

		</form>
		<?
		$APPLICATION->IncludeComponent(
			'bitrix:crm.product.section.crumbs',
			'',
			array(
				'CATALOG_ID' => $arResult['IBLOCK_ID'],
				'SECTION_ID' => $arResult['SECTION_ID'],
				'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'] ?? null,
				'JS_EVENTS_MODE' => 'Y',
				'JS_EVENTS_MANAGER_ID' => $jsEventsManagerId
			),
			$component
		);
		$_GLOBALS['CRM_PRODUCT_SEARCH_DIALOG_GRID_EPILOG_HANDLER_PARAM_1'] = $arResult['TABLE_ID'];
		$_GLOBALS['CRM_PRODUCT_SEARCH_DIALOG_GRID_EPILOG_HANDLER_PARAM_2'] = $arResult['BREADCRUMBS'];
		function OnCrmProductSearchDialogGridEpilog()
		{
			/** @global array $_GLOBALS */
			global $_GLOBALS;
			?>
			<script type="text/javascript">
				// double click patch
				var rows = BX.findChildren(BX('<?= CUtil::JSEscape($_GLOBALS['CRM_PRODUCT_SEARCH_DIALOG_GRID_EPILOG_HANDLER_PARAM_1']) ?>'), {tag: 'tr'}, true);
				if (rows) {
					var i;
					for (i = 0; i < rows.length; ++i) {
						if (BX.hasClass(rows[i], 'bx-crm-table-body'))
						{
							rows[i].onclick = function ()
							{
								BX.toggleClass(this, 'bx-active');
							};
							BX.bind(rows[i], "contextmenu", rows[i].onclick);
						}
					}
				}
			</script><?

			return true;
		}
		AddEventHandler('main', 'OnEpilog', 'OnCrmProductSearchDialogGridEpilog');
		$APPLICATION->IncludeComponent(
			'bitrix:crm.interface.grid',
			'flat',
			array
			(
				'GRID_ID' => $arResult['TABLE_ID'],
				'HEADERS' => $arResult['HEADERS'],
				'SORT' => isset($arResult['SORT']) ? $arResult['SORT'] : array('name', 'asc'),
				'SORT_VARS' =>
					isset($arResult['SORT_VARS'])
						? $arResult['SORT_VARS'] : array('by' => 'by', 'order' => 'order'),
				'ROWS' => $arResult['GRID_DATA'],
				'FOOTER' =>
					array
					(
						array
						(
							'title' => GetMessage('CRM_ALL'),
							'value' => $arResult['ROWS_COUNT']
						)
					),
				'EDITABLE' => !($arResult['PERMS']['WRITE'] ?? null) || $arResult['INTERNAL'] ? 'N' : 'Y',
				'ACTIONS' =>
					array
					(
						'delete' => $arResult['PERMS']['DELETE'] ?? null,
						'list' => array()
					),
				'ACTION_ALL_ROWS' => true,
				'NAV_OBJECT' => $arResult['DB_RESULT_LIST'],
				'AJAX_MODE' => 'Y',
				'AJAX_OPTION_JUMP' => 'N',
				'AJAX_OPTION_HISTORY' => 'N'
			),
			$component
		);
		?>
	</div>
</div>
<script type="text/javascript">
	<?= CUtil::JSEscape($arResult['TABLE_ID']) ?>_helper = new BX.Crm.ProductSearchDialog({
		tableId: '<?= CUtil::JSEscape($arResult['TABLE_ID']) ?>',
		callback: '<?= $arResult['JS_CALLBACK'] ?>',
		callerName: '<?=CUtil::JSEscape($arResult['CALLER'])?>',
		currentUri: '<?=CUtil::JSEscape($APPLICATION->GetCurPage())?>',
		popup: BX.WindowManager.Get(),
		iblockName: '<?=CUtil::JSEscape(GetMessage('CRM_SECTION_ROOT_NAME'))?>',
		jsEventsManagerId: "<?=CUtil::JSEscape($jsEventsManagerId)?>",
		leftContainerId: "<?=CUtil::JSEscape($leftContainerId)?>",
		splitterBtnId: "<?=CUtil::JSEscape($splitterBtnId)?>"
	});
	BX('<?= CUtil::JSEscape($arResult['TABLE_ID']) ?>_query').focus();
</script>