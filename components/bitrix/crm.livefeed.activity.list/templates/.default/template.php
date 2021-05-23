<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;

$UID = $prefix = $arResult['UID'];
$items = $arResult['ACTIVITIES'];
$itemData = array();
$wrapperID = "{$prefix}_activities";

?><div id="<?=htmlspecialcharsbx($wrapperID)?>" class="crm-right-block"<?=empty($items) ? ' style="display:none;"' : ''?>>
	<div class="crm-right-block-title"><?=GetMessage('CRM_ENTITY_LF_ACTIVITY_LIST_TITLE')?></div><?
	foreach($items as &$item):
		$itemID = intval($item['~ID']);
		$typeID = isset($item['~TYPE_ID']) ? intval($item['~TYPE_ID']) : CCrmActivityType::Undefined;
		$direction = isset($item['~DIRECTION']) ? intval($item['~DIRECTION']) : CCrmActivityDirection::Undefined;
		$deadline = isset($item['~DEADLINE']) && $item['~DEADLINE'] !== ''
			? CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($item['~DEADLINE'])))
			: '';

		$completed = (isset($item['~COMPLETED']) ? $item['~COMPLETED'] : 'N') === 'Y';
		$expired = !$completed && $deadline !== '' && MakeTimeStamp($deadline) <= ($now = time() + CTimeZone::GetOffset());
		$subject = isset($item['~SUBJECT']) ? htmlspecialcharsbx($item['~SUBJECT']) : $itemID;
		$responsibleName = isset($item['RESPONSIBLE_FULL_NAME']) ? $item['RESPONSIBLE_FULL_NAME'] : '';
		$responsibleUrl = isset($item['PATH_TO_RESPONSIBLE']) ? $item['PATH_TO_RESPONSIBLE'] : '';
		$priority = isset($item['~PRIORITY']) ? intval($item['~PRIORITY']) : CCrmActivityPriority::None;

		$itemData[] = array(
			'ID' => $itemID,
			'typeID' => $typeID,
			'direction' => $direction,
			'subject' => $subject,
			'completed' => $completed,
			'deadline' => $deadline,
			'responsibleName' => $responsibleName
		);

		$containerClassName = '';
		if($typeID === CCrmActivityType::Meeting):
			$containerClassName = 'crm-right-block-meet';
		elseif($typeID === CCrmActivityType::Call):
			if($direction === CCrmActivityDirection::Outgoing):
				$containerClassName = 'crm-right-block-call-to';
			else:
				$containerClassName = 'crm-right-block-call';
			endif;
		elseif($typeID === CCrmActivityType::Email):
			if($direction === CCrmActivityDirection::Outgoing):
				$typeTitle = htmlspecialcharsbx(GetMessage('CRM_ENTITY_LF_ACTIVITY_TYPE_EMAIL_OUTGOING'));
			endif;
		elseif($typeID === CCrmActivityType::Task):
			$containerClassName = 'crm-right-block-task';
		endif;

		if($containerClassName !== '' && $completed)
			$containerClassName .= '-done';

		$containerClassName = $containerClassName !== '' ? "crm-right-block-item  {$containerClassName}" : 'crm-right-block-item';
		if($expired)
			$containerClassName .= ' crm-right-block-deadline';

		$bindingInfoHtml = '';
		if($item['CLIENT_TITLE'] !== '')
			$bindingInfoHtml = GetMessage('CRM_ENTITY_LF_ACTIVITY_CLIENT_INFO', array('#CLIENT#' => $item['CLIENT_TITLE']));
		if($item['REFERENCE_TITLE'] !== '')
		{
			if($bindingInfoHtml !== '')
				$bindingInfoHtml .= ' ';
			$bindingInfoHtml .= GetMessage('CRM_ENTITY_LF_ACTIVITY_REFERENCE_INFO', array('#REFERENCE#' => $item['REFERENCE_TITLE']));
		}
		$bindingInfoHtml = htmlspecialcharsbx($bindingInfoHtml);

		?><div class="<?=$containerClassName?>" data-entity-id="<?=$itemID?>">
			<div class="crm-right-block-item-row">
				<span class="crm-right-block-item-icon"></span>
				<a class="crm-right-block-item-title-text" href="#"><?=$subject?></a><?
				if(!$isReadOnly):
				?><input type="checkbox" class="crm-right-block-checkbox" /><?
				endif;
			?></div>
			<div class="crm-right-block-item-row">
				<span class="crm-right-block-date"><?=$deadline?></span>
				<a class="crm-right-block-name" target="_blank" href="<?=$responsibleUrl?>"><?=$responsibleName?></a>
			</div>
			<div class="crm-right-block-item-row">
				<span class="crm-right-block-item-label"><?=$bindingInfoHtml?></span>
			</div>
		</div><?
	endforeach;
	unset($item);
?></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var uid = "<?=CUtil::JSEscape($UID)?>";
			BX.CrmEntityLiveFeedActivityList.create(
				uid,
				{
					"containerId": "<?=CUtil::JSEscape($wrapperID)?>",
					"activityEditorId": "<?=CUtil::JSEscape($arResult['ACTIVITY_EDITOR_UID'])?>",
					"clientTemplate": "<?=GetMessageJS('CRM_ENTITY_LF_ACTIVITY_CLIENT_INFO')?>",
					"referenceTemplate": "<?=GetMessageJS('CRM_ENTITY_LF_ACTIVITY_REFERENCE_INFO')?>",
					"data": <?=CUtil::PhpToJSObject($itemData)?>,
					"loader": <?=CUtil::PhpToJSObject($arResult['LOADER'])?>
				}
			);
		}
	);
</script>