<?php

use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load("ui.tooltip");

global $APPLICATION, $USER;

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
\Bitrix\Main\UI\Extension::load("ui.icons.b24");

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'EVENT_LIST',
			'ACTIVE_ITEM_ID' => 'EVENT',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

if(isset($arResult['ERROR']))
{
	echo $arResult['ERROR'];
	return;
}

$gridManagerID = $managerID = $arResult['GRID_ID'].'_MANAGER';
$prefix = $arResult['GRID_ID'];


	$arResult['GRID_DATA'] = array();
	foreach($arResult['EVENT'] as $arEvent)
	{
		$arEvent['FILE_HTML'] = "";
		if(!empty($arEvent['FILES']))
		{
			$arEvent['FILE_HTML'] = '<div class="event-detail-files"><label class="event-detail-files-title">'.GetMessage('CRM_EVENT_TABLE_FILES').':</label><div class="event-detail-files-list">';
				foreach($arEvent['FILES'] as $key=>$value)
					$arEvent['FILE_HTML'] .= '<div class="event-detail-file"><span class="event-detail-file-number">'.$key.'.</span><span class="event-detail-file-info"><a href="'.htmlspecialcharsbx($value['PATH']).'" target="_blank" class="event-detail-file-link">'.htmlspecialcharsbx($value['NAME']).'</a><span class="event-detail-file-size">('.htmlspecialcharsbx($value['SIZE']).')</span></span></div>';
			$arEvent['FILE_HTML'] .= '</div></div>';
		}

		$arActions = array();
		//Disable DELETE event deletion
		if (($arEvent['EVENT_TYPE'] != CCrmEvent::TYPE_DELETE && CCrmPerms::IsAdmin()
			|| ($arEvent['EVENT_TYPE'] == CCrmEvent::TYPE_USER && $arEvent['CREATED_BY_ID'] == CCrmPerms::GetCurrentUserID())))
		{
			$pathToRemove = CUtil::JSEscape($arEvent['PATH_TO_DELETE']);
			$arActions[] =  array(
				'TITLE' => GetMessage('CRM_EVENT_DELETE_TITLE'),
				'TEXT' => GetMessage('CRM_EVENT_DELETE'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}', 
					BX.CrmUIGridMenuCommand.remove, 
					{ pathToRemove: '{$pathToRemove}' }
				)"
			);
		}

		$eventColor = '';
		if ($arEvent['EVENT_TYPE'] == '0')
			$eventColor = 'color: #208c0b';
		elseif ($arEvent['EVENT_TYPE'] == '2')
			$eventColor = 'color: #9c8000';

		$authorHtml = '';
		if($arEvent['CREATED_BY_FULL_NAME'] !== '')
		{
			$photoUrl = isset($arEvent['CREATED_BY_PHOTO_URL']) ? $arEvent['CREATED_BY_PHOTO_URL'] : '';
			$authorHtml = "<div class = \"crm-client-summary-wrapper\">
				<div class=\"crm-client-info-wrapper\">
					<div class=\"crm-client-title-wrapper\">
						<a class='crm-grid-username' href='{$arEvent['CREATED_BY_LINK']}' id=\"balloon_{$arResult['GRID_ID']}_{$arEvent['ID']}\" bx-tooltip-user-id=\"{$arEvent['CREATED_BY_ID']}\">
							<span class='crm-grid-avatar ui-icon ui-icon-common-user'>
								<i ".($photoUrl ? 'style="background-image: url(\'' . Uri::urnEncode($photoUrl) . '\')"' : '') ."></i>
							</span>
							<span class='crm-grid-username-inner'>{$arEvent['CREATED_BY_FULL_NAME']}</span>
						</a>
					</div>
				</div>
			</div>";
		}
		$arColumns = array(
			'CREATED_BY_FULL_NAME' => $authorHtml,
			'EVENT_NAME' => '<span style="'.$eventColor.'">'.$arEvent['EVENT_NAME'].'</span>',
			'EVENT_DESC' => $arEvent['EVENT_DESC'].$arEvent['FILE_HTML'],
			'DATE_CREATE' => FormatDate('x', MakeTimeStamp($arEvent['DATE_CREATE']), (time() + CTimeZone::GetOffset()))
		);
		if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
		{
			$arColumns['ENTITY_TYPE'] = !empty($arEvent['ENTITY_TYPE'])
				? htmlspecialcharsbx(CCrmOwnerType::GetDescription(CCrmOwnerType::ResolveID($arEvent['ENTITY_TYPE']))) : '';

			$arColumns['ENTITY_TITLE'] = !empty($arEvent['ENTITY_TITLE'])?
				'<a href="'.$arEvent['ENTITY_LINK'].'" bx-tooltip-user-id="'.$arEvent['ENTITY_TYPE'].'_'.$arEvent['ENTITY_ID'].'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.'.mb_strtolower($arEvent['ENTITY_TYPE']).'.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon'.($arEvent['ENTITY_TYPE'] == 'LEAD' || $arEvent['ENTITY_TYPE'] == 'DEAL' || $arEvent['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.mb_strtolower($arEvent['ENTITY_TYPE'])).'">'.$arEvent['ENTITY_TITLE'].'</a>'
				: '';
		}
		else
		{
			unset($arEvent['ENTITY_TYPE']);
			unset($arEvent['ENTITY_TITLE']);
		}

		$arResult['GRID_DATA'][] = array(
			'id' => $arEvent['ID'],
			'data' => $arEvent,
			'actions' => $arActions,
			'editable' =>($USER->IsAdmin() || ($arEvent['CREATED_BY_ID'] == $USER->GetId() && $arEvent['EVENT_TYPE'] == 0))? true: false,
			'columns' => $arColumns
		);
	}
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'AJAX_ONLY' => 'Y',
			'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"]
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);

	if (
		$arResult['INTERNAL']
		&& $arResult['ENTITY_TYPE'] !== CCrmOwnerType::OrderPaymentName
		&& $arResult['ENTITY_TYPE'] !== CCrmOwnerType::OrderShipmentName
	)
	{
		// Render toolbar in internal mode
		$toolbarButtons = array();
		if(isset($arResult['ENTITY_TYPE']) && $arResult['ENTITY_TYPE'] !== ''
			&& isset($arResult['ENTITY_ID']) && is_int($arResult['ENTITY_ID']) && $arResult['ENTITY_ID'] > 0)
		{
			$showAddEventButton = in_array($arResult['ENTITY_TYPE'], [CCrmOwnerType::LeadName, CCrmOwnerType::DealName, CCrmOwnerType::ContactName, CCrmOwnerType::CompanyName, CCrmOwnerType::QuoteName, CCrmOwnerType::InvoiceName]);
			if (in_array($arResult['ENTITY_TYPE'], [CCrmOwnerType::ContactName, CCrmOwnerType::CompanyName]))
			{
				$showAddEventButton = !\Bitrix\Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::ResolveID($arResult['ENTITY_TYPE']))
					->getItemCategoryId($arResult['ENTITY_ID'])
				;
			}
			if (\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled() && $showAddEventButton)
			{
				$showAddEventButton = (\Bitrix\Main\Config\Option::get('crm', 'enable_add_event_btn', 'N') === 'Y');
			}

			if ($showAddEventButton)
			{
				$toolbarButtons[] = [
					'TEXT' => GetMessage('CRM_EVENT_VIEW_ADD_SHORT'),
					'TITLE' => GetMessage('CRM_EVENT_VIEW_ADD'),
					'ONCLICK' => "BX.CrmEventListManager.items[\"{$managerID}\"].addItem()",
					'ICON' => 'btn-new'
				];
			}
		}

		if (!empty($toolbarButtons))
		{
			$APPLICATION->IncludeComponent(
				'bitrix:crm.interface.toolbar',
				'',
				[
					'TOOLBAR_ID' => $toolbarID ?? null,
					'BUTTONS' => $toolbarButtons
				],
				$component,
				['HIDE_ICONS' => 'Y']
			);
		}

		$APPLICATION->ShowViewContent('crm-internal-filter');
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.grid',
		'titleflex',
		array(
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
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
			'RENDER_FILTER_INTO_VIEW' => $arResult['INTERNAL'] ? 'crm-internal-filter' : '',
			'DISABLE_SEARCH' => true,
			'ACTION_PANEL' => array(),
			'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
				? $arResult['PAGINATION'] : array(),
			'ENABLE_ROW_COUNT_LOADER' => true,
			'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'] ?? null,
			'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'] ?? null,
			'EXTENSION' => array(
				'ID' => $gridManagerID,
				'CONFIG' => array(
					'ownerTypeName' => 'EVENT',
					'gridId' => $arResult['GRID_ID'],
					'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
					'loaderData' => $arParams['AJAX_LOADER'] ?? null
				),
				'MESSAGES' => array(
					'deletionDialogTitle' => GetMessage('CRM_EVENT_DELETE_TITLE'),
					'deletionDialogMessage' => GetMessage('CRM_EVENT_DELETE_CONFIRM'),
					'deletionDialogButtonTitle' => GetMessage('CRM_EVENT_DELETE')
				)
			),
		),
		$component
	);

if ($arResult['EVENT_HINT_MESSAGE'] == 'Y' && COption::GetOptionString('crm', 'mail', '') != ''):
?>
<div class="crm_notice_message"><?=GetMessage('CRM_IMPORT_EVENT', Array('%EMAIL%' => COption::GetOptionString('crm', 'mail', '')));?></div>
<?endif;?>

<script>
	BX.ready(
		function()
		{
			BX.CrmEventListManager.messages =
			{
				deletionConfirmationDlgTitle: "<?=GetMessageJS('CRM_EVENT_DELETE_TITLE')?>",
				deletionConfirmationDlgContent: "<?=GetMessageJS('CRM_EVENT_DELETE_CONFIRM')?>",
				deletionConfirmationDlgBtn: "<?=GetMessageJS('CRM_EVENT_DELETE')?>"
			};

			BX.CrmEventListManager.create("<?=CUtil::JSEscape($managerID)?>",
				{
					addItemUrl: "/bitrix/components/bitrix/crm.event.add/box.php",
					entityTypeName: "<?=CUtil::JSEscape($arResult['ENTITY_TYPE'])?>",
					entityId: "<?=CUtil::JSEscape($arResult['ENTITY_ID'])?>",
					gridId: "<?=CUtil::JSEscape($arResult['GRID_ID'])?>",
					tabId: "<?=CUtil::JSEscape($arResult['TAB_ID'])?>",
					formId: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>"
				}
			);
		}
	);
</script>
