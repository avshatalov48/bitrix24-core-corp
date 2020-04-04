<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

$component = $this->__component;

$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'TAX_LIST',
		'ACTIVE_ITEM_ID' => '',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

$tbButtons = array(
	array(
		'TEXT' => GetMessage('CRM_CONFIGS_LINK_TEXT'),
		'TITLE' => GetMessage('CRM_CONFIGS_LINK_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_CONFIGS_INDEX'], array()),
		'ICON' => 'go-back'
	)
);
if (!empty($tbButtons))
{
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.toolbar',
		'',
		array(
			'BUTTONS' => $tbButtons
		),
		$component,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);
}

$arAllOptions = array(
		\Bitrix\Crm\Rest\CCrmExternalChannelImportPreset::PRESET_PERSON_TYPE_COMPANY => GetMessage('CRM_TRACKER_SETTINGS_COMPANY'),
		\Bitrix\Crm\Rest\CCrmExternalChannelImportPreset::PRESET_PERSON_TYPE_PERSON => GetMessage('CRM_TRACKER_SETTINGS_CONTACT')
);

$data = $arResult['DATA'];
$list = $arResult['PRESETS_LIST'];

foreach($arAllOptions as $code => $name)
{
	$val = 0;
	if(isset($data[$code]))
		$val = $data[$code];

	$fieldParams = array(
			'id' => $code,
			'name' => $name,
			'type' => 'list',
			'value' => $val,
			'required' => true
	);

	$fieldParams['items'] = $list;

	$arResult['FIELDS']['tab_tracker'][] = $fieldParams;
}

	$arTabs[] = array(
	'id' => 'tab_tracker',
	'name' => GetMessage("CRM_TRACKER_SECTION_NAME"),
	'title' => GetMessage("CRM_TRACKER_SECTION_TITLE"),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_tracker']
	);

	$customButtons = '<input type="submit" name="save" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE_TITLE")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE_TITLE")).'" />';
	$customButtons .= '<input type="button" name="cancel" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL_TITLE")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL_TITLE")).'" onclick="window.location=\''.htmlspecialcharsbx($arResult['BACK_URL']).'\'" />';

	?>

	<div class="crm-config-exch1c">
		<?
		$APPLICATION->IncludeComponent(
				'bitrix:main.interface.form',
				'',
				array(
						'FORM_ID' => $arResult['FORM_ID'],
						'TABS' => $arTabs,
						'BUTTONS' => array(
								'standard_buttons' =>  false,
								'custom_html' => $customButtons
						),
						'SHOW_SETTINGS' => 'N'
				),
				$component, array('HIDE_ICONS' => 'Y')
		);
		?>
	</div>

<?
