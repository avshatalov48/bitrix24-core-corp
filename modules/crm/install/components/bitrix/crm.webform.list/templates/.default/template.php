<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\EntityList\Settings\PermissionItem;
use Bitrix\Crm\Security\Role\Manage\Manager\WebFormSelection;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\UI;

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 */

Main\UI\Extension::load([
	"ui.buttons",
	"ui.icons",
	"ui.switcher",
	"sidepanel",
	'crm.form.embed',
	"crm.form.qr",
	'date',
]);
Main\Loader::includeModule("ui");
if(Main\Loader::includeModule("bitrix24"))
{
	CBitrix24::initLicenseInfoPopupJS();
	Main\UI\Extension::load([
		'bitrix24.phoneverify',
	]);
}

echo \Bitrix\Crm\Tour\Permissions\WebForm::getInstance()->build();

$region = Main\Application::getInstance()->getLicense()->getRegion();
$privacyDesc = $region
	? Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_TEXT_' . mb_strtoupper($region))
	: null
;
$privacyDesc = $privacyDesc ?: Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_TEXT');
$notifyUserConsentHelperId = $region !== 'by' ? 5791365 : 16215312;

$descImagePath = $this->GetFolder() . "/images/demo_main_";
$descImagePath .= (in_array(LANGUAGE_ID, array('ru', 'ua', 'kz', 'by')) ? 'ru' : (LANGUAGE_ID === 'de' ? 'de' : 'en'));
$descImagePath .= ".png";

foreach ($arResult["ITEMS"] as $index => $data)
{
	foreach ($data as $dataKey => $dataValue)
	{
		if (is_string($dataValue) && $dataKey !== 'ENTITY_COUNTERS')
		{
			$data[$dataKey] = htmlspecialcharsbx($dataValue);
		}
	}
	$data['ID'] = (int)$data['ID'];


	$entityCounters = '';
	foreach ($data['ENTITY_COUNTERS']['counters'] as $counterIndex => $entityCounter)
	{
		$counterValue = (int)$entityCounter['VALUE'];
		$entityLink = htmlspecialcharsbx($entityCounter['LINK'] ?: '');
		$entityCaption = htmlspecialcharsbx($entityCounter['ENTITY_CAPTION']);

		$entityLink = !$entityLink
			? '<span class="crm-webform-list-entity-link" title="' . $entityCaption . '">' . $entityCaption . '</span>'
			: '<a
					href="' . $entityLink . '" 
					onclick="event.stopPropagation(); BX.SidePanel.Instance.open(\'' . $entityLink . '\'); return false;"
					class="crm-webform-list-entity-link"
					title="' . $entityCaption . '"
				>' . $entityCaption . '</a>'
		;

		$entityCounters .= ''
			. '<div class="crm-webform-list-entity">'
			. $entityLink
			. '<span class="crm-webform-list-entity-counter">' . $counterValue . '</span>'
			. '</div>'
		;
	}
	$entityCounters = htmlspecialcharsbx($data['ENTITY_COUNTERS']['caption']);
	$entityCounters = '<a 
			class="crm-webform-list-entity-link" 
			title="' . $entityCounters . '"
			data-crm-form-entities="' . htmlspecialcharsbx(Json::encode([
				'id' => $data['ID'],
				'counters' => $data['ENTITY_COUNTERS']['counters']
			])) . '"	
		>' . $entityCounters . '</a>'
	;
	$data['ENTITY_COUNTERS'] = $entityCounters;


	$data['NAME'] = '<a target="_top" href="'.$data['PATH_TO_WEB_FORM_EDIT'].'" onclick="event.stopPropagation();">'
		. $data['NAME']
		.'</a>'
	;
	$data['EMBEDDING'] = '<button 
			type="button" 
			class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round ui-btn-no-caps crm-webform-item-btn"  
 			onclick="event.stopPropagation(); BX.Crm.WebFormList.showSiteCode(' . $data["ID"] . ', {activeMenuItemId: \'inline\'}, '.($data['PHONE_VERIFIED'] ? 'false' : 'true').'); return false;"
 		>' . Loc::getMessage('CRM_WEBFORM_LIST_ITEM_BTN_GET_SCRIPT'). '
 		</button>'
	;

	/*
	$data['SUMMARY_CONVERSION'] = '<div class="crm-webform-grid-conversion">'
		. '<div class="crm-webform-grid-conversion-icon" style="background: ' . $data['SUMMARY_CONVERSION']['color'] . '"></div>'
		. ($data['SUMMARY_CONVERSION']['value'] ? '<div class="crm-webform-grid-conversion-value">' . $data['SUMMARY_CONVERSION']['value'] . '</div>' : '')
		. '<div class="crm-webform-grid-conversion-text" style="color: ' . $data['SUMMARY_CONVERSION']['color'] . '">' . $data['SUMMARY_CONVERSION']['text'] . '</div>'
		. '</div>';
	*/

	$convVal = $data['SUMMARY_CONVERSION']['value'];
	$convText = $data['SUMMARY_CONVERSION']['text'];
	$convColor = $data['SUMMARY_CONVERSION']['color'];
	$convColor = $convColor ? '--' . $convColor : '';
	$data['SUMMARY_CONVERSION'] = '
		<div class="crm-webform-grid-conversion ' . $convColor . '">
			' . ($convVal !== null ? '<div class="crm-webform-grid-conversion-value">' . $convVal .'</div>' : '') . '
			' . ($convVal !== null ? '<div class="crm-webform-grid-conversion-text">' . $convText .'</div>' : '') . '
		</div>'
	;

	$submitsVal = $data['SUMMARY_SUBMITS']['value'];
	$submitsText = $data['SUMMARY_SUBMITS']['text'];
	$submitsTitle = $data['SUMMARY_SUBMITS']['hint'];
	$submitsColor = $data['SUMMARY_SUBMITS']['color'];
	$submitsColor = $submitsColor ? '--' . $submitsColor : '';
	$titleAttr = !empty($submitsTitle) ? ('title="' . $submitsTitle . '"') : '';
	$data['SUMMARY_SUBMITS'] = '
		<div class="crm-webform-grid-conversion ' . $submitsColor . '">
			' . (!empty($submitsText) ? '<div class="crm-webform-grid-conversion-text" '.$titleAttr.'>'.$submitsText.'</div>' : '') . '
			<div 
				class="crm-webform-grid-conversion-icon-status"
				' . $titleAttr . '
			></div>
		</div>'
	;

	$userIconPath = empty($data['ACTIVE_CHANGE_BY']['ICON']) ? '' : $data['ACTIVE_CHANGE_BY']['ICON'];
	$userClass = empty($data['ACTIVE_CHANGE_BY']['ICON']) ? 'ui-icon ui-icon-common-user' : '';

	$culture = Main\Context::getCurrent()->getCulture();
	$activeDateDisplay = $data["ACTIVE_CHANGE_DATE"]
		? FormatDate($culture->getLongDateFormat() . ', ' . $culture->getShortTimeFormat(), MakeTimeStamp($data["ACTIVE_CHANGE_DATE"]))
		: ''
	;
	$isActive = $data['ACTIVE'] === 'Y';
	$data['ACTIVE'] = '
		<div 
			class="crm-webform-list-active"
			data-crm-form-switcher="'
			. htmlspecialcharsbx(Json::encode([
				'id' => $data["ID"],
				'active' => $isActive,
				'dateActiveShort' => $isActive
					? FormatDate("SHORT", MakeTimeStamp($data["ACTIVE_CHANGE_DATE"]))
					: Loc::getMessage('CRM_WEBFORM_LIST_NOT_ACTIVE')
				,
				'dateActiveFull' => $activeDateDisplay,
				'dateToday' => FormatDate("SHORT"),
				'activatedBy' => [
					'iconClass' => $userClass,
					'iconPath' => $userIconPath,
					'path' => $data['ACTIVE_CHANGE_BY']['LINK'],
					'name' => $data['ACTIVE_CHANGE_BY']['NAME'],
					'text' => $isActive
						? Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_ACTIVATED')
						: Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_DEACTIVATED')
					,
				],
			]))
			. '" 
			onclick="event.stopPropagation();"
		></div>
	';

	$data['ACTIVE_CHANGE_BY'] ='
		<a 
			class="crm-webform-list-user"
				target="_top" 
				href="'.$data['ACTIVE_CHANGE_BY']['LINK']. '"
		>
			<span 
				class="crm-webform-list-user-icon ' . htmlspecialcharsbx($userClass) . '" 
				style="background-image: url(' . Uri::urnEncode(htmlspecialcharsbx($userIconPath)) . ');"
			>
				<i></i>
			</span>
			<span 
				class="crm-webform-list-user-name"> 
			' . htmlspecialcharsbx($data['ACTIVE_CHANGE_BY']['NAME']) . '
			</span>
		</a>
	';

	$data['PATH_TO_WEB_FORM_FILL'] = '
		<div 
			data-crm-form-qr="'
				. htmlspecialcharsbx(Json::encode([
					'id' => $data["ID"],
					'path' => $data['PATH_TO_WEB_FORM_FILL'],
					'needVerify' => !$data['PHONE_VERIFIED'],
				]))
				. '" 
			onclick="event.stopPropagation();"
		></div>'
	;

	/** @var DateTime */
	if($data["DATE_CREATE"])
	{
		$data['DATE_CREATE'] = FormatDate("X", MakeTimeStamp($data["DATE_CREATE"]));
	}
	/** @var DateTime */
	if($data["ACTIVE_CHANGE_DATE"])
	{
		$data['ACTIVE_CHANGE_DATE'] = FormatDate("X", MakeTimeStamp($data['ACTIVE_CHANGE_DATE']));
	}

	$data['NAME'] .= '<div class="crm-webform-list-socials">';
	foreach ($data['ADS_FORM'] as $adsFormService)
	{
		$top = empty($top) ? 20 : $top + 20;
		$typeUpped = mb_strtoupper($adsFormService['TYPE']);
		if ($adsFormService['HAS_LINKS'])
		{
			$data['NAME'] .= '
				<div 
					class="crm-webform-list-social-icon--wrapper"
					data-bx-crm-webform-ads-btn="'.htmlspecialcharsbx($adsFormService['TYPE']).'"
				>
					<span class="ui-icon ui-icon-service-'.htmlspecialcharsbx($adsFormService['ICON']).' ui-icon-xs">
						<i></i>
					</span>
				<span class="crm-webform-list-social-icon-text">'.htmlspecialcharsbx($adsFormService['NAME']).'</span>
			';
		}
	}

	$data['PATH_TO_WEB_FORM_EDIT'] = CUtil::JSEscape($data['PATH_TO_WEB_FORM_EDIT']);
	$data['NAME'] .= '</div>';
	$actions = [];

	if ($arResult['PERM_CAN_EDIT'])
	{
		$actions[] = [
			"TITLE" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_EDIT'),
			"TEXT" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_EDIT'),
			'ONCLICK' => "top.location='{$data['PATH_TO_WEB_FORM_EDIT']}';",
			'DEFAULT' => true,
		];

		$actions[] = [
			"TITLE" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_COPY1'),
			"TEXT" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_COPY1'),
			"ONCLICK" => "BX.Crm.WebFormList.copy({$data["ID"]});"
		];
		$actions[] = [
			"TITLE" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_RESET_COUNTERS'),
			"TEXT" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_RESET_COUNTERS'),
			"ONCLICK" => "BX.Crm.WebFormList.resetCounters({$data["ID"]});"
		];

		if ($data['IS_SYSTEM'] <> 'Y')
		{
			$actions[] = [
				"TITLE" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_REMOVE'),
				"TEXT" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_REMOVE'),
				"ONCLICK" => "BX.Crm.WebFormList.remove({$data["ID"]});"
			];
		}
	}
	else
	{
		$actions[] = [
			"TITLE" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_VIEW'),
			"TEXT" => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_VIEW'),
			'ONCLICK' => "top.location='{$data['PATH_TO_WEB_FORM_EDIT']}';",
			'DEFAULT' => true,
		];
	}

	$data["GET_PAYMENT"] =
		$data["IS_PAY"] === "Y"
			? Loc::getMessage("CRM_WEBFORM_LIST_ITEM_GET_PAYMENT_ACTIVATED")
			: Loc::getMessage("CRM_WEBFORM_LIST_ITEM_GET_PAYMENT_DEACTIVATED")
	;

	$arResult["ITEMS"][$index] = [
		"id" => $data["ID"],
		"columns" => $data,
		"actions" => $actions,
	];
}


// TOOLBAR

$interfaceToolbarButtons = [];

$permissionButton = new PermissionItem(new WebFormSelection());
if (isset($arParams['ANALYTICS']) && is_array($arParams['ANALYTICS']))
{
	$permissionButton->setAnalytics($arParams['ANALYTICS']);
}

if ($permissionButton->canShow())
{
	$interfaceToolbarButtons[] = $permissionButton->toInterfaceToolbarButton();
}

if ($arResult['PERM_CAN_EDIT'])
{
	$attributes = [
		'target' => '_top',
	];
	if (!$arResult['AVAILABLE'])
	{
		$attributes['onclick'] = "BX.UI.InfoHelper.show('limit_crm_webform_edit')";
	}

	$interfaceToolbarButtons[] = [
		'LINK' => $arResult['AVAILABLE'] ? CUtil::JSEscape($arResult['PATH_TO_WEB_FORM_NEW']) : '#',
		'ATTRIBUTES' => $attributes,
		'LOCATION' => UI\Toolbar\ButtonLocation::AFTER_TITLE,
	];
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.toolbar',
	SITE_TEMPLATE_ID === 'bitrix24' ? 'title' : '',
	[
		'TOOLBAR_ID' => 'crm_webform_toolbar',
		'BUTTONS' => $interfaceToolbarButtons,
		'TOOLBAR_PARAMS' => [],
	],
	$component,
	[
		'HIDE_ICONS' => 'Y',
	],
);

UI\Toolbar\Facade\Toolbar::addFilter([
	"GRID_ID" => $arResult["GRID_ID"],
	"FILTER_ID" => $arResult["FILTER_ID"],
	"FILTER" => $arResult["FILTER"],
	"FILTER_PRESETS" => $arResult['FILTER_PRESETS'],
	"DISABLE_SEARCH" => false,
	"ENABLE_LIVE_SEARCH" => true,
	"ENABLE_LABEL" => true,
	'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
]);


// INFORMATION
if(!$arResult['HIDE_DESC'] || !$arResult['HIDE_DESC_FZ152']):?>
	<div id="CRM_LIST_DESC_CONT" class="crm-webform-list-info">
		<div class="crm-webform-list-info-container">
		<?php if(!$arResult['HIDE_DESC_FZ152']):?>
			<h2 class="crm-webform-list-info-title">
				<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_TITLE')?>
			</h2>

			<div class="crm-webform-list-info-inner">

				<div class="crm-webform-list-info-list-container">
					<?=$privacyDesc?>
					<br>
					<br>
				</div>

				<div class="crm-webform-list-info-list-container">
					<ul class="crm-webform-list-info-list">
						<li class="">
							<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_ITEMS_1', array('%req_path%' => '/crm/configs/mycompany/'))?>
							<br>
							<br>
						</li>
						<li class="">
							<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_ITEMS_2', array('%email%' => htmlspecialcharsbx($arResult['USER_CONSENT_EMAIL'])))?>
							<br>
							<br>
						</li>
						<li class="">
							<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_ITEMS_3')?>
							<br>
							<br>
						</li>
					</ul>
				</div>

				<div class="crm-webform-list-info-list-container">
					<span id="CRM_LIST_WEBFORM_NOTIFY_BTN_HIDE" class="webform-small-button webform-small-button-blue">
						<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_BTN_OK')?>
					</span>
					<a onclick="BX.Helper.show('redirect=detail&code=<?=$notifyUserConsentHelperId?>'); return false;"
					   href="javascript: void();" target="_blank">
						<?=Loc::getMessage('CRM_WEBFORM_LIST_NOTIFY_USER_CONSENT_BTN_DETAIL')?>
					</a>
				</div>
			</div>
		<?php else:?>
			<?php if(!$arResult['HIDE_DESC']):?>
				<h2 class="crm-webform-list-info-title"><?=Loc::getMessage('CRM_WEBFORM_LIST_INFO_TITLE')?></h2>
				<div class="crm-webform-list-info-visual">
					<span class="crm-webform-list-info-visual-item" style="height: 225px;">
						<img src="<?=$descImagePath?>" alt="">
					</span>
				</div>
				<div class="crm-webform-list-info-inner">
					<div class="crm-webform-list-info-list-container">
						<ul class="crm-webform-list-info-list">
							<li class="crm-webform-list-info-list-item">
								<?=Loc::getMessage('CRM_WEBFORM_LIST_DESC1')?>
							</li>
							<li class="crm-webform-list-info-list-item">
								<?=Loc::getMessage('CRM_WEBFORM_LIST_DESC2')?>
							</li>
						</ul>
					</div>
				</div>
				<span
					id="CRM_LIST_DESC_BTN_HIDE"
					class="crm-webform-list-info-btn-hide"
					title="<?=Loc::getMessage('CRM_WEBFORM_LIST_HIDE_DESC')?>"
				></span>
			<?php endif;?>
		<?php endif;?>
		</div>
	</div>
<?php endif;

$snippet = new Main\Grid\Panel\Snippet();
$controlPanel = ['GROUPS' => [['ITEMS' => []]]];

if ($arResult['PERM_CAN_EDIT'])
{
	$gridId = $arResult["GRID_ID"];
	$controlPanel['GROUPS'][0]['ITEMS'][] = [
		'ID' => "action_button_{$gridId}",
		'NAME' => "action_button_{$gridId}",
		'TYPE' => Main\Grid\Panel\Types::DROPDOWN,
		'ITEMS' => [
			[
				'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_BTN_CHOOSE_ACTION'),
				'VALUE' => 'none',
				'ONCHANGE' => [
					[
						'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							['JS' => "BX.Crm.WebFormList.setGroupAction(null)"],
						],
					],
				],
			],
			[
				'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_BTN_ACTIVATE'),
				'VALUE' => 'activate',
				'ONCHANGE' => [
					['ACTION' => Main\Grid\Panel\Actions::RESET_CONTROLS],
					[
						'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							[
								'JS' => "BX.Crm.WebFormList.setGroupAction('activate')"
							],
						],
					],
				],
			],
			[
				'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_BTN_DEACTIVATE'),
				'VALUE' => 'deactivate',
				'ONCHANGE' => [
					['ACTION' => Main\Grid\Panel\Actions::RESET_CONTROLS],
					[
						'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							[
								'JS' => "BX.Crm.WebFormList.setGroupAction('deactivate')"
							],
						],
					],
				],
			],
			[
				'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_BTN_REMOVE'),
				'VALUE' => 'delete',
				'ONCHANGE' => [
					['ACTION' => Main\Grid\Panel\Actions::RESET_CONTROLS],
					[
						'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => [
							[
								'JS' => "BX.Crm.WebFormList.setGroupAction('delete')"
							],
						],
					],
				],
			],
		],
	];
	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getApplyButton([
		'ONCHANGE' => [
			[
				'ACTION' => Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => [
					[
						'JS' => "BX.Crm.WebFormList.runGroupAction()"
					],
				],
			],
		],
	]);
}

?>
<div class="crm-webform-list-wrapper">
	<?php

	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		"bitrix:main.ui.grid",
		"",
		array(
			"GRID_ID" => $arResult["GRID_ID"],
			"COLUMNS" => $arResult['COLUMNS'],
			"ROWS" => $arResult['ITEMS'],
			"NAV_OBJECT" => $arResult['NAVIGATION_OBJECT'],
			"~NAV_PARAMS" => array('SHOW_ALWAYS' => false),
			'SHOW_ROW_CHECKBOXES' => true,
			'SHOW_GRID_SETTINGS_MENU' => true,
			'SHOW_PAGINATION' => true,
			'SHOW_SELECTED_COUNTER' => true,
			'SHOW_TOTAL_COUNTER' => true,
			'STUB' => $arResult['TOTAL_ROWS_COUNT'] < 1 ? $arResult['STUB'] : null,
			'ACTION_PANEL' => $controlPanel,
			'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
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
		<?php if (!$arResult['PERM_CAN_EDIT']) :?>
				BX.SidePanel.Instance.bindAnchors({
					rules:
						[
							{
								condition: [
									"/crm/webform/edit/\\d+/",
								]
							},
						]
				});
		<?php endif;?>
			BX.message({
				CRM_WEBFORM_LIST_DELETE_CONFIRM: "<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_LIST_DELETE_CONFIRM")) ?>",
				CRM_WEBFORM_LIST_DELETE_ERROR: "<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_LIST_DELETE_ERROR")) ?>",
				CRM_WEBFORM_LIST_ITEM_WRITE_ACCESS_DENIED: "<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_LIST_ITEM_WRITE_ACCESS_DENIED")) ?>",
				CRM_WEBFORM_LIST_DELETE_CONFIRM_TITLE:"<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_LIST_DELETE_CONFIRM_TITLE")) ?>",
				CRM_WEBFORM_LIST_BTN_DETAILS:"<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_LIST_BTN_DETAILS")) ?>",
				CRM_WEBFORM_LIST_NOT_ACTIVE:"<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_LIST_NOT_ACTIVE")) ?>",
				CRM_WEBFORM_LIST_ITEM_PHONE_NOT_VERIFIED:"<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_LIST_ITEM_PHONE_NOT_VERIFIED")) ?>",
				CRM_WEBFORM_PHONE_VERIFY_CUSTOM_SLIDER_TITLE:"<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_PHONE_VERIFY_CUSTOM_SLIDER_TITLE")) ?>",
				CRM_WEBFORM_PHONE_VERIFY_CUSTOM_TITLE:"<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_PHONE_VERIFY_CUSTOM_TITLE")) ?>",
				CRM_WEBFORM_PHONE_VERIFY_CUSTOM_DESCRIPTION_V1:"<?=CUtil::jsEscape(getMessage("CRM_WEBFORM_PHONE_VERIFY_CUSTOM_DESCRIPTION_V1")) ?>",
			});

			window.webformList = BX.Crm.WebFormList.init(<?=Json::encode(array(
			   "gridId" => "CRM_WEBFORM_GRID",
		   ))?>);

			<?php if ($arResult['SHOW_PERMISSION_ERROR']):?>
				window.webformList.showNotification(BX.message.CRM_WEBFORM_LIST_ITEM_WRITE_ACCESS_DENIED);
			<?php endif; ?>
		});
	</script>
</div>
