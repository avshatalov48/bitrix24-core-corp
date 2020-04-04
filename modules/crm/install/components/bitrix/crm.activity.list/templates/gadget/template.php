<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-gadget.css");
if(!function_exists('__CrmActivityListRenderGadgetItem'))
{
	function __CrmActivityListRenderGadgetItem(&$item, $displayClient, $editorID, $prefix)
	{
		static $ownerDataCache = array();

		$typeID = isset($item['~TYPE_ID']) ? intval($item['~TYPE_ID']) : CCrmActivityType::Undefined;
		$direction = isset($item['~DIRECTION']) ? intval($item['~DIRECTION']) : CCrmActivityDirection::Undefined;
		$deadline = isset($item['~DEADLINE']) ? $item['~DEADLINE'] : '';
		$completed = (isset($item['~COMPLETED']) ? $item['~COMPLETED'] : 'N') === 'Y';
		$expired = !$completed && $deadline !== '' && MakeTimeStamp($deadline) <= ($now = time() + CTimeZone::GetOffset());
		$subject = isset($item['~SUBJECT']) ? htmlspecialcharsbx($item['~SUBJECT']) : $item['~ID'];
		$descr = isset($item['~DESCRIPTION']) ? htmlspecialcharsbx($item['~DESCRIPTION']) : '';
		$priority = isset($item['~PRIORITY']) ? intval($item['~PRIORITY']) : CCrmActivityPriority::None;
		$js = "BX.CrmActivityEditor.viewActivity('{$editorID}', {$item['~ID']}, { 'enableInstantEdit':false, 'enableEditButton':false });";

		$containerClassName = 'crm-gadg-block crm-gadg-to-do';
		$typeTitle = '';
		if($typeID === CCrmActivityType::Meeting):
			$containerClassName .= ' crm-gadg-meeting';
			$typeTitle = htmlspecialcharsbx(GetMessage('CRM_ACTION_TYPE_MEETING'));
		elseif($typeID === CCrmActivityType::Call):
			if($direction === CCrmActivityDirection::Outgoing):
				$containerClassName .= ' crm-gadg-call-outgoing';
				$typeTitle = htmlspecialcharsbx(GetMessage('CRM_ACTION_TYPE_CALL_OUTGOING'));
			else:
				$containerClassName .= ' crm-gadg-call-incoming';
				$typeTitle = htmlspecialcharsbx(GetMessage('CRM_ACTION_TYPE_CALL_INCOMING'));
			endif;
		elseif($typeID === CCrmActivityType::Email):
			if($direction === CCrmActivityDirection::Outgoing):
				$containerClassName .= ' crm-gadg-email-outgoing';
				$typeTitle = htmlspecialcharsbx(GetMessage('CRM_ACTION_TYPE_EMAIL_OUTGOING'));
			else:
				$containerClassName .= ' crm-gadg-email-incoming';
				$typeTitle = htmlspecialcharsbx(GetMessage('CRM_ACTION_TYPE_EMAIL_INCOMING'));
			endif;
		elseif($typeID === CCrmActivityType::Task):
			$containerClassName .= ' crm-gadg-task';
			$typeTitle = htmlspecialcharsbx(GetMessage('CRM_ACTION_TYPE_TASK'));
		endif;

		if(!$expired && $priority === CCrmActivityPriority::High)
		{
			$containerClassName .= ' crm-gadg-to-do-important';
		}

		if($expired)
		{
			$containerClassName .= ' crm-gadg-red';
		}

		echo '<div class="', $containerClassName, '">';

		echo '<a href="#" onclick="', $js, ' return false;" class="crm-to-do-type" title="', $typeTitle, '"></a>';

		echo '<div class="crm-gadg-title">';
		echo '<a href="#" class="crm-gadg-link" title="', $subject,'" onclick="', $js, ' return false;" >', $subject, '</a>',
			'<span class="crm-gadg-title-deadline"> ',
			$deadline !== '' ? CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($deadline))) : '';

		if($priority === CCrmActivityPriority::High)
		{
			echo '<span class="crm-gadg-to-do-important-icon" title="', htmlspecialcharsbx(GetMessage('CRM_ACTION_IMPORTANT')),'"></span>';
		}

		echo '</span>';


		if($expired)
		{
			echo '<span class="crm-gadg-title-status"> ',  htmlspecialcharsbx(GetMessage('CRM_ACTION_EXPIRED')), '</span>';
		}
		elseif($completed)
		{
			echo '<span class="crm-gadg-title-status"> ',  htmlspecialcharsbx(GetMessage('CRM_ACTION_COMPLETED')), '</span>';
		}

		echo '</div>';

		if($descr !== '')
		{
			echo '<div class="crm-gadg-text">', $descr, '</div>';
		}

		echo '<div class="crm-gadg-footer">';

		$ownerTypeID = isset($item['OWNER_TYPE_ID']) ? intval($item['OWNER_TYPE_ID']) : 0;
		$ownerID = isset($item['OWNER_ID']) ? intval($item['OWNER_ID']) : 0;

		$referenceCaption = '';
		$referenceHtml = '';

		if($ownerTypeID > 0 && $ownerID > 0)
		{
			if($ownerTypeID === CCrmOwnerType::Lead)
			{
				$referenceCaption = htmlspecialcharsbx(GetMessage('CRM_ACTION_REFERENCE_LEAD'));
				$key = "{$ownerTypeID}_{$ownerID}";
				if(!(isset($ownerDataCache[$key]) && isset($ownerDataCache[$key]['TITLE'])))
				{
					$dbRes = CCrmLead::GetListEx(array(), array('ID'=> $ownerID), false, false, array('TITLE'));
					if($arRes = $dbRes->Fetch())
					{
						$ownerDataCache[$key] = array('TITLE' => $arRes['TITLE']);
					}
				}

				$referenceHtml = CCrmViewHelper::PrepareEntityBaloonHtml(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
						'ENTITY_ID' => $ownerID,
						'PREFIX' => uniqid("{$prefix}_"),
						'TITLE' => $ownerDataCache[$key]['TITLE'],
						'CLASS_NAME' => 'crm-gadg-link'
					)
				);
			}
			elseif($ownerTypeID === CCrmOwnerType::Deal)
			{
				$referenceCaption = htmlspecialcharsbx(GetMessage('CRM_ACTION_REFERENCE_DEAL'));
				$key = "{$ownerTypeID}_{$ownerID}";
				if(!(isset($ownerDataCache[$key]) && isset($ownerDataCache[$key]['TITLE'])))
				{
					$dbRes = CCrmDeal::GetListEx(array(), array('ID'=> $ownerID), false, false, array('TITLE'));
					if($arRes = $dbRes->Fetch())
					{
						$ownerDataCache[$key] = array('TITLE' => $arRes['TITLE']);
					}
				}

				$referenceHtml = CCrmViewHelper::PrepareEntityBaloonHtml(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
						'ENTITY_ID' => $ownerID,
						'PREFIX' => uniqid("{$prefix}_"),
						'TITLE' => $ownerDataCache[$key]['TITLE'],
						'CLASS_NAME' => 'crm-gadg-link'
					)
				);
			}
		}

		if($referenceHtml !== '')
		{
			echo '<div class="crm-gadg-footer-row">';
			echo '<span class="crm-gadg-footer-left">', $referenceCaption, ':</span>';
			echo '<span class="crm-gadg-footer-right">', $referenceHtml, '</span>';
			echo '</div>';
		}

		if($displayClient)
		{
			//Looking for first contact or company
			$clientHtml = '';
			$commLoaded = isset($item['COMMUNICATIONS_LOADED']) ? $item['COMMUNICATIONS_LOADED'] : true;
			if($commLoaded && is_array($item['COMMUNICATIONS']))
			{
				$comms = $item['COMMUNICATIONS'];
			}
			else
			{
				//Communications are disabled. Try to load first 3 communications to resolve client
				$comms = CCrmActivity::GetCommunications($item['~ID'], 3);
			}

			foreach($comms as &$comm)
			{
				$commOwnerTypeID = isset($comm['ENTITY_TYPE_ID']) ? intval($comm['ENTITY_TYPE_ID']) : 0;
				$commOwnerID = isset($comm['ENTITY_ID']) ? intval($comm['ENTITY_ID']) : 0;

				if($commOwnerTypeID <= 0 || $commOwnerID <= 0)
				{
					continue;
				}

				$settings = isset($comm['ENTITY_SETTINGS']) ? $comm['ENTITY_SETTINGS'] : array();
				if($commOwnerTypeID === CCrmOwnerType::Company)
				{
					$clientHtml = CCrmViewHelper::PrepareClientBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $commOwnerID,
							'PREFIX' => uniqid("{$prefix}_"),
							'TITLE' => isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '',
							'CLASS_NAME' => 'crm-gadg-link'
						)
					);
					break;
				}
				elseif($commOwnerTypeID === CCrmOwnerType::Contact)
				{
					$clientHtml = CCrmViewHelper::PrepareClientBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
							'ENTITY_ID' => $commOwnerID,
							'PREFIX' => uniqid("{$prefix}_"),
							'NAME' => isset($settings['NAME']) ? $settings['NAME'] : '',
							'LAST_NAME' => isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '',
							'SECOND_NAME' => isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : '',
							'CLASS_NAME' => 'crm-gadg-link'
						)
					);
					break;
				}
			}
			unset($comm);

			if($clientHtml !== '')
			{
				echo '<div class="crm-gadg-footer-row">';
				echo '<span class="crm-gadg-footer-left">', htmlspecialcharsbx(GetMessage('CRM_ACTION_CUSTOMER')), ':</span>';
				echo '<span class="crm-gadg-footer-right">', $clientHtml, '</span>';
				echo '</div>';
			}
		}
		echo '</div>';

		echo '</div>';
	}
}

$lcPrefix = strtolower($arResult['PREFIX']);
$editorID = $lcPrefix !== '' ? "{$lcPrefix}_crm_gadget_activity_editor" : 'crm_gadget_activity_editor';
$displayClient = $arResult['DISPLAY_CLIENT']
	&& (empty($arResult['SELECTED_FIELDS']) || in_array('CLIENT', $arResult['SELECTED_FIELDS'], true));

foreach($arResult['ITEMS'] as &$item):
	__CrmActivityListRenderGadgetItem($item, $displayClient, $editorID, $arResult['PREFIX']);
endforeach;
unset($item);

if(!empty($arResult['PATH_TO_FULL_VIEW'])):
?><div class="crm-gadget-activity-full-view">
	<a href="<?= $arResult['PATH_TO_FULL_VIEW']?>"><?=htmlspecialcharsbx(GetMessage('CRM_ACTION_GO_TO_FULL_VIEW'))?></a>
</div><?
endif;

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	array(
		'CONTAINER_ID' => '',
		'EDITOR_ID' => $editorID,
		'EDITOR_TYPE' => 'MIXED',
		'PREFIX' => $arResult['PREFIX'],
		'OWNER_TYPE' => $arResult['OWNER_TYPE'],
		'OWNER_ID' => $arResult['OWNER_ID'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'ENABLE_UI' => false,
		'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK_ADD'],
		'ENABLE_CALENDAR_EVENT_ADD' => $arResult['ENABLE_CALENDAR_EVENT_ADD'],
		'ENABLE_EMAIL_ADD' => $arResult['ENABLE_EMAIL_ADD'],
		'ENABLE_TOOLBAR' => false,
		'EDITOR_ITEMS' => array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
?><script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmActivityGadget.create('<?=$lcPrefix !== '' ? "{$lcPrefix}_crm_activity_gadget" : 'crm_activity_gadget'?>', {'editorID':'<?=$editorID?>'});
			}
	);
</script>