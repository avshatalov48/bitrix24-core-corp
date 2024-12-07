<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Uri;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if (SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\UI\Extension::load(
	[
		'crm_activity_planner',
		'crm_common',
		'ui.fonts.opensans',
		'crm.autorun',
		'crm.entity-list.panel',
		'crm.badge',
		'ui.design-tokens',
	]
);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

?><div id="crm-quote-list-progress-bar-container"></div><div id="rebuildMessageWrapper"><?

if (!empty($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'])):
	?><div id="rebuildQuoteSearchWrapper"></div><?
endif;

if (!empty($arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'])):
	?><div id="rebuildQuoteAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_QUOTE_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildQuoteAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;

if (!empty($arResult['NEED_FOR_TRANSFER_PS_REQUISITES'])):
	?><div id="transferPSRequisitesMsg" class="crm-view-message">
	<?=Bitrix\Crm\Requisite\Conversion\PSRequisiteConverter::getIntroMessage(
		array(
			'EXEC_ID' => 'transferPSRequisitesLink', 'EXEC_URL' => '#',
			'SKIP_ID' => 'skipTransferPSRequisitesLink', 'SKIP_URL' => '#'
		)
	)?>
	</div><?
endif;
?></div><?

$isInternal = $arResult['INTERNAL'];
$allowWrite = $arResult['PERMS']['WRITE'];
$allowDelete = $arResult['PERMS']['DELETE'];
$currentUserID = $arResult['CURRENT_USER_ID'];

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'QUOTE',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'filterFields' => []
);
echo CCrmViewHelper::RenderQuoteStatusSettings();
$prefix = $arResult['GRID_ID'];
$prefixLC = mb_strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = [];
$arColumns = [];
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();

$fieldContentTypeMap = \Bitrix\Crm\Model\FieldContentTypeTable::loadForMultipleItems(
	\CCrmOwnerType::Quote,
	array_keys($arResult['QUOTE']),
);

/** @var \Bitrix\Crm\Conversion\EntityConversionConfig $conversionConfig */
$conversionConfig = $arResult['CONVERSION_CONFIG'] ?? null;

foreach($arResult['QUOTE'] as $sKey =>  $arQuote)
{
	$jsTitle = isset($arQuote['~TITLE']) ? CUtil::JSEscape($arQuote['~TITLE']) : '';
	$jsShowUrl = isset($arQuote['PATH_TO_QUOTE_SHOW']) ? CUtil::JSEscape($arQuote['PATH_TO_QUOTE_SHOW']) : '';

	$arActions = [];

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_QUOTE_SHOW_TITLE_MSGVER_1'),
		'TEXT' => GetMessage('CRM_QUOTE_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arQuote['PATH_TO_QUOTE_SHOW'])."')",
		'DEFAULT' => true
	);

	if ($arQuote['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_QUOTE_EDIT_TITLE_MSGVER_1'),
			'TEXT' => GetMessage('CRM_QUOTE_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arQuote['PATH_TO_QUOTE_EDIT'])."')",
		);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_QUOTE_COPY_TITLE'),
			'TEXT' => GetMessage('CRM_QUOTE_COPY'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arQuote['PATH_TO_QUOTE_COPY'])."')"
		);
	}

	if ($arQuote['DELETE'] && !$arResult['INTERNAL'])
	{
		$pathToRemove = CUtil::JSEscape($arQuote['PATH_TO_QUOTE_DELETE']);
		$arActions[] =  array(
			'TITLE' => GetMessage('CRM_QUOTE_DELETE_TITLE_MSGVER_1'),
			'TEXT' => GetMessage('CRM_QUOTE_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}', 
				BX.CrmUIGridMenuCommand.remove, 
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	if (!$isInternal && $arResult['CAN_CONVERT'])
	{
		if ($arResult['CONVERSION_PERMITTED'] && $conversionConfig)
		{
			$arSchemeList = [];

			$toolsManager = Container::getInstance()->getIntranetToolsManager();
			$availabilityManager = AvailabilityManager::getInstance();

			foreach($conversionConfig->getScheme()->getItems() as $item)
			{
				$entityTypeId = current($item->getEntityTypeIds());
				if ($toolsManager->checkEntityTypeAvailability($entityTypeId))
				{
					$onClick = sprintf(
						"BX.Crm.Conversion.Manager.Instance.getConverter('%s').convertBySchemeItemId('%s', %d);",
						\CUtil::JSEscape($arResult['CONVERTER_ID']),
						\CUtil::JSEscape($item->getId()),
						(int)$arQuote['ID']
					);
				}
				else
				{
					$onClick = $availabilityManager->getEntityTypeAvailabilityLock($entityTypeId);
				}

				$arSchemeList[] = [
					'TITLE' => $item->getPhrase(),
					'TEXT' => $item->getPhrase(),
					'ONCLICK' => $onClick,
				];
			}

			if (!empty($arSchemeList))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_QUOTE_CREATE_ON_BASIS_TITLE_MSGVER_1'),
					'TEXT' => GetMessage('CRM_QUOTE_CREATE_ON_BASIS'),
					'MENU' => $arSchemeList
				);
			}
		}
		else
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_QUOTE_CREATE_ON_BASIS_TITLE_MSGVER_1'),
				'TEXT' => GetMessage('CRM_QUOTE_CREATE_ON_BASIS'),
				'ONCLICK' => isset($arResult['CONVERSION_LOCK_SCRIPT']) ? $arResult['CONVERSION_LOCK_SCRIPT'] : ''
			);
		}
	}

	$eventParam = array(
		'ID' => $arQuote['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);
	foreach(GetModuleEvents('crm', 'onCrmQuoteListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_QUOTE_LIST_MENU', $eventParam, &$arActions));
	}

	$contactID = (int)($arQuote['~CONTACT_ID'] ?? 0);
	$companyID = (int)($arQuote['~COMPANY_ID'] ?? 0);
	$leadID = (int)($arQuote['~LEAD_ID'] ?? 0);
	$dealID = (int)($arQuote['~DEAL_ID'] ?? 0);
	$myCompanyID = (int)($arQuote['~MYCOMPANY_ID'] ?? 0);


	$webformId = null;
	if (
		isset($arQuote['WEBFORM_ID'])
		&& isset($arResult['WEBFORM_LIST'][$arQuote['WEBFORM_ID']])
	)
	{
		$webformId = $arResult['WEBFORM_LIST'][$arQuote['WEBFORM_ID']];
	}

	$resultItem = array(
		'id' => $arQuote['ID'],
		'actions' => $arActions,
		'data' => $arQuote,
		'editable' => !$arQuote['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'QUOTE_NUMBER' => '<a target="_top" href="'.$arQuote['PATH_TO_QUOTE_SHOW'].'">'.$arQuote['QUOTE_NUMBER'].'</a>',
			'QUOTE_SUMMARY' => CCrmViewHelper::RenderInfo1(
				$arQuote['PATH_TO_QUOTE_SHOW'],
				$arQuote['ID'],
				Tracking\UI\Grid::enrichSourceName(
					\CCrmOwnerType::Quote,
					$arQuote['ID'],
					$arQuote['TITLE'] ? $arQuote['TITLE'] : \Bitrix\Crm\Item\Quote::getTitlePlaceholderFromData($arQuote)
				),
				'_top'
			),
			'QUOTE_CLIENT' => $contactID > 0
				? CCrmViewHelper::PrepareClientInfo(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'ENTITY_ID' => $contactID,
						'TITLE' => isset($arQuote['~CONTACT_FORMATTED_NAME']) ? $arQuote['~CONTACT_FORMATTED_NAME'] : ('['.$contactID.']'),
						'PREFIX' => "QUOTE_{$arQuote['~ID']}",
						'DESCRIPTION' => isset($arQuote['~COMPANY_TITLE']) ? $arQuote['~COMPANY_TITLE'] : ''
					)
				) : ($companyID > 0
					? CCrmViewHelper::PrepareClientInfo(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $companyID,
							'TITLE' => isset($arQuote['~COMPANY_TITLE']) ? $arQuote['~COMPANY_TITLE'] : ('['.$companyID.']'),
							'PREFIX' => "QUOTE_{$arQuote['~ID']}"
						)
					) : ''),
			'COMPANY_ID' => isset($arQuote['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arQuote['COMPANY_INFO']) : '',
			'LEAD_ID' => isset($arQuote['LEAD_INFO']) ? CCrmViewHelper::PrepareClientInfo($arQuote['LEAD_INFO']) : '',
			'DEAL_ID' => isset($arQuote['DEAL_INFO']) ? CCrmViewHelper::PrepareClientInfo($arQuote['DEAL_INFO']) : '',
			'CONTACT_ID' => isset($arQuote['CONTACT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arQuote['CONTACT_INFO']) : '',
			'MYCOMPANY_ID' => isset($arQuote['MY_COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arQuote['MY_COMPANY_INFO']) : '',
			'WEBFORM_ID' => $webformId,
			'TITLE' => '<a target="_self" href="'.$arQuote['PATH_TO_QUOTE_SHOW'].'">'.$arQuote['TITLE'].'</a>',
			'CLOSED' => ($arQuote['CLOSED'] ?? null) == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'ASSIGNED_BY' => $arQuote['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "QUOTE_{$arQuote['~ID']}_RESPONSIBLE",
						'USER_ID' => $arQuote['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arQuote['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arQuote['PATH_TO_USER_PROFILE']
					)
				) : '',
			'COMMENTS' => htmlspecialcharsback($arQuote['COMMENTS'] ?? null),
			'SUM' => '<nobr>'.$arQuote['FORMATTED_OPPORTUNITY'].'</nobr>',
			'OPPORTUNITY' => '<nobr>'.$arQuote['OPPORTUNITY'].'</nobr>',
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arQuote['DATE_CREATE']), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arQuote['DATE_MODIFY'] ?? null), $now),
			'CURRENCY_ID' => CCrmCurrency::GetEncodedCurrencyName($arQuote['CURRENCY_ID']),
			'PRODUCT_ID' => isset($arQuote['PRODUCT_ROWS']) ? htmlspecialcharsbx(CCrmProductRow::RowsToString($arQuote['PRODUCT_ROWS'])) : '',
			'STATUS_ID' => CCrmViewHelper::RenderQuoteStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arQuote['~ID'],
					'CURRENT_ID' => $arQuote['~STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.quote.list/list.ajax.php'
				)
			),
			'CREATED_BY' => ($arQuote['~CREATED_BY'] ?? null) > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					[
						'PREFIX' => "QUOTE_{$arQuote['~ID']}_CREATOR",
						'USER_ID' => $arQuote['~CREATED_BY'],
						'USER_NAME'=> $arQuote['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arQuote['PATH_TO_USER_CREATOR']
					]
				) : '',
			'MODIFY_BY' => ($arQuote['~MODIFY_BY'] ?? null) > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "QUOTE_{$arQuote['~ID']}_MODIFIER",
						'USER_ID' => $arQuote['~MODIFY_BY'],
						'USER_NAME'=> $arQuote['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arQuote['PATH_TO_USER_MODIFIER']
					)
				) : '',
			'ENTITIES_LINKS' => $arQuote['FORMATTED_ENTITIES_LINKS'],
			'CLOSEDATE' => empty($arQuote['CLOSEDATE']) ? '' : '<nobr>'.$arQuote['CLOSEDATE'].'</nobr>',
			'ACTUAL_DATE' => empty($arQuote['ACTUAL_DATE']) ? '' : '<nobr>'.$arQuote['ACTUAL_DATE'].'</nobr>',
		) + $arResult['QUOTE_UF'][$sKey]
	);

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Quote,
		$arQuote['ID'],
		$resultItem['columns']
	);

	$resultItem['columns'] = \Bitrix\Crm\Entity\CommentsHelper::enrichGridRow(
		\CCrmOwnerType::Quote,
		$fieldContentTypeMap[$arQuote['ID']] ?? [],
		$arQuote,
		$resultItem['columns'],
	);

	if ($arQuote['IN_COUNTER_FLAG'] === true)
	{
		if ($resultItem['columnClasses']['CLOSEDATE'] != '')
			$resultItem['columnClasses']['CLOSEDATE'] .= ' ';
		else
			$resultItem['columnClasses']['CLOSEDATE'] = '';
		$resultItem['columnClasses']['CLOSEDATE'] .= 'crm-list-quote-today';
	}
	if ($arQuote['EXPIRED_FLAG'] === true)
	{
		if ($resultItem['columnClasses']['CLOSEDATE'] != '')
			$resultItem['columnClasses']['CLOSEDATE'] .= ' ';
		else
			$resultItem['columnClasses']['CLOSEDATE'] = '';
		$resultItem['columnClasses']['CLOSEDATE'] .= 'crm-list-quote-time-expired';
	}

	if (isset($arQuote['badges']) && is_array($arQuote['badges']))
	{
		$resultItem['columns']['QUOTE_SUMMARY'] .= Bitrix\Crm\Component\EntityList\BadgeBuilder::render($arQuote['badges']);
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'AJAX_ONLY' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

if ($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_QUOTE_LIST_ADD_SHORT_MSGVER_1'),
		'TITLE' => GetMessage('CRM_QUOTE_LIST_ADD_MSGVER_1'),
		'LINK' => $arResult['PATH_TO_QUOTE_ADD'],
		'ICON' => 'btn-new'
	);

	if ($arResult['ADD_EVENT_NAME'] !== '')
	{
		$analyticsBuilder = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault(CCrmOwnerType::Quote)
			->setSection(
				!empty($arParams['~ANALYTICS']['c_section']) && is_string($arParams['~ANALYTICS']['c_section'])
					? $arParams['~ANALYTICS']['c_section']
					: null
			)
			->setSubSection(
				!empty($arParams['~ANALYTICS']['c_sub_section']) && is_string($arParams['~ANALYTICS']['c_sub_section'])
					? $arParams['~ANALYTICS']['c_sub_section']
					: null
			)
			->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CREATE_LINKED_ENTITY_BUTTON);
		$data = [
			'urlParams' => $analyticsBuilder->buildData(),
		];
		foreach ($data['urlParams'] as $key => $value)
		{
			$data['urlParams']['st[' . $key . ']'] = $value;
			unset($data['urlParams'][$key]);
		}
		$addButton['ONCLICK'] = "BX.onCustomEvent(window, '{$arResult['ADD_EVENT_NAME']}', " . json_encode($data) . ")";
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']) . '_toolbar',
			'BUTTONS' => [$addButton]
		),
		$component,
		['HIDE_ICONS' => 'Y']
	);
}

//region Navigation
$navigationHtml = '';
if (isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION']))
{
	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.pagenavigation',
		'',
		$arResult['PAGINATION'] ?? [],
		$component,
		['HIDE_ICONS' => 'Y']
	);
	$navigationHtml = ob_get_contents();
	ob_end_clean();
}
//endregion

$filterLazyLoadUrl = '/bitrix/components/bitrix/crm.quote.list/filter.ajax.php?' . bitrix_sessid_get();
$filterLazyLoadParams = [
	'filter_id' => urlencode($arResult['GRID_ID']),
	'category_id' => $arResult['CATEGORY_ID'] ?? null,
	'siteID' => SITE_ID,
];
$uri = new Uri($filterLazyLoadUrl);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
		'ENABLE_FIELDS_SEARCH' => 'Y',
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => array(
			'LAZY_LOAD' => array(
				'GET_LIST' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'list']))->getUri(),
				'GET_FIELD' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'field']))->getUri(),
				'GET_FIELDS' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'fields']))->getUri(),
			),
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
			'CONFIG' => [
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
		),
		'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
			$arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? ModuleManager::isModuleInstalled('ui')
		),
		'LIVE_SEARCH_LIMIT_INFO' => $arResult['LIVE_SEARCH_LIMIT_INFO'] ?? null,
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => [
			'GROUPS' => [
				[
					'ITEMS' => $isInternal ? [] : $arResult['PANEL']?->getControls(),
				],
			],
		],
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : [],
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Quote))
			->setItems([
				NavigationBarPanel::ID_AUTOMATION,
				NavigationBarPanel::ID_KANBAN,
				NavigationBarPanel::ID_LIST,
				NavigationBarPanel::ID_DEADLINES,
			], NavigationBarPanel::ID_LIST)
			->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
			->get(),
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::QuoteName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => '',
				'activityServiceUrl' => '',
				'taskCreateUrl'=> '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.quote.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => $arParams['AJAX_LOADER'] ?? null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_QUOTE_DELETE_TITLE_MSGVER_1'),
				'deletionDialogMessage' => GetMessage('CRM_QUOTE_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_QUOTE_DELETE')
			)
		)
	),
	$component
);
?>
<script>
	BX.ready(
		function()
		{
			BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
		}
	);
</script>
<script>
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
				{
					startButton: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_BTN_START')?>",
					stopButton: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_BTN_STOP')?>",
					closeButton: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_BTN_CLOSE')?>",
					requestError: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_REQUEST_ERR')?>"
				};

			BX.Crm.EntityList.Panel.init(<?= \CUtil::PhpToJSObject([
				'gridId' => $arResult['GRID_ID'],
				'progressBarContainerId' => 'crm-quote-list-progress-bar-container',
			]) ?>);
		}
	);
</script>
<?php

if (
	!$isInternal
	&& \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME') !== 'Y'
)
{
	$settingsButtonExtenderParams = \Bitrix\Crm\UI\SettingsButtonExtender\SettingsButtonExtenderParams::createDefaultForGrid(
		\CCrmOwnerType::Quote,
		$arResult['GRID_ID'],
	);

	echo <<<HTML
<script>
	BX.ready(() => {
		{$settingsButtonExtenderParams->buildJsInitCode()}
	});
</script>
HTML;
}

if ($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && $conversionConfig):
	Extension::load('crm.conversion');
	?><script>
		BX.ready(
			function()
			{
				BX.Crm.Conversion.Manager.Instance.initializeConverter(
					BX.CrmEntityType.enumeration.quote,
					{
						configItems: <?= CUtil::PhpToJSObject($conversionConfig->toJson()) ?>,
						scheme: <?= CUtil::PhpToJSObject($conversionConfig->getScheme()->toJson(true)) ?>,
						params: {
							id: '<?= \CUtil::JSEscape($arResult['CONVERTER_ID']) ?>',
							serviceUrl: "<?='/bitrix/components/bitrix/crm.quote.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
							originUrl: '<?= CUtil::JSEscape($APPLICATION->GetCurPage()) ?>',
							messages: {
								accessDenied: "<?=GetMessageJS("CRM_QUOTE_CONV_ACCESS_DENIED")?>",
								generalError: "<?=GetMessageJS("CRM_QUOTE_CONV_GENERAL_ERROR")?>",
								dialogTitle: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_TITLE_MSGVER_1")?>",
								syncEditorLegend: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_SYNC_LEGEND_MSGVER_1")?>",
								syncEditorFieldListTitle: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
								syncEditorEntityListTitle: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
								continueButton: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_CONTINUE_BTN")?>",
								cancelButton: "<?=GetMessageJS("CRM_QUOTE_CONV_DIALOG_CANCEL_BTN")?>"
							},
							analytics: {
								c_section: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_QUOTE ?>',
								c_sub_section: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_LIST ?>',
								c_element: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU ?>',
							},
						}
					},
				);
			}
		);
	</script>
<?endif;?>
<?if ($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
	<script>
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("rebuildQuoteSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_QUOTE_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildQuoteSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.quote.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildQuoteSearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if ($arResult['NEED_FOR_REBUILD_QUOTE_ATTRS']):?>
<script>
	BX.ready(
		function()
		{
			var link = BX("rebuildQuoteAttrsLink");
			if (link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildQuoteAttrsMsg");
						if (msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;?>
<?if ($arResult['NEED_FOR_TRANSFER_PS_REQUISITES']):?>
	<script>
		BX.ready(
			function()
			{
				BX.CrmPSRequisiteConverter.messages =
				{
					processDialogTitle: "<?=GetMessageJS('CRM_PS_RQ_TX_PROC_DLG_TITLE')?>",
					processDialogSummary: "<?=GetMessageJS('CRM_PS_RQ_TX_PROC_DLG_DLG_SUMMARY1')?>"
				};

				var converter = BX.CrmPSRequisiteConverter.create(
					"psRqConverter",
					{
						serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.config.ps.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
					}
				);

				BX.addCustomEvent(
					converter,
					'ON_PS_REQUISITE_TRANFER_COMPLETE',
					function()
					{
						var msg = BX("transferPSRequisitesMsg");
						if (msg)
						{
							msg.style.display = "none";
						}
					}
				);

				var transferLink = BX("transferPSRequisitesLink");
				if (transferLink)
				{
					BX.bind(
						transferLink,
						"click",
						function(e)
						{
							converter.convert();
							return BX.PreventDefault(e);
						}
					);
				}

				var skipTransferLink = BX("skipTransferPSRequisitesLink");
				if (skipTransferLink)
				{
					BX.bind(
						skipTransferLink,
						"click",
						function(e)
						{
							converter.skip();

							var msg = BX("transferPSRequisitesMsg");
							if (msg)
							{
								msg.style.display = "none";
							}

							return BX.PreventDefault(e);
						}
					);
				}
			}
		);
	</script>
<?endif;

if (!empty($arResult['RESTRICTED_FIELDS_ENGINE']))
{
	Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['RESTRICTED_FIELDS_ENGINE'];
}
