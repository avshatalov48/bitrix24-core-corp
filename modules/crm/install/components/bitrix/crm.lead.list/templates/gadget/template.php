<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load("ui.tooltip");

global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-gadget.css");

if (empty($arResult['LEAD']))
	echo GetMessage('CRM_DATA_EMPTY');
else
{
	foreach($arResult['LEAD'] as &$arLead)
	{
		echo '<div class="crm-gadg-block">';
		echo '<div class="crm-gadg-title">';

		echo '<a href="', $arLead['PATH_TO_LEAD_SHOW'], '" class="crm-gadg-link" title="', $arLead['TITLE'], '" bx-tooltip-user-id="LEAD_', $arLead['~ID'], '" bx-tooltip-loader="', htmlspecialcharsbx('/bitrix/components/bitrix/crm.lead.show/card.ajax.php'), '" bx-tooltip-classname="crm_balloon_no_photo">', $arLead['TITLE'], '</a>';
		echo '</div>';

		echo '<div class="crm-gadg-stage">';
		echo '<span class="crm-gadg-stage-left">', htmlspecialcharsbx(GetMessage('CRM_COLUMN_STATUS_MSGVER_1')), ':<span>';
		echo '<span class="crm-gadg-stage-right">', htmlspecialcharsbx($arLead['LEAD_STATUS_NAME']), '<span>';
		echo '</div>';

		$name = isset($arLead['LEAD_FORMATTED_NAME']) ? $arLead['LEAD_FORMATTED_NAME'] : '';
		$post = isset($arLead['POST']) ? $arLead['POST'] : '';
		$companyTitle = isset($arLead['COMPANY_TITLE']) ? $arLead['COMPANY_TITLE'] : '';

		if($name !== '' || $post !== '' || $companyTitle !== '')
		{
			echo '<div class="crm-gadg-description">';
			if($name !== '')
			{
				echo $name;
			}

			if($post !== '')
			{
				echo '<span class="crm-gadg-description-grey">';
				if($name !== '')
				{
					echo ' (', $post, ')';
				}
				else
				{
					echo $post;
				}
				echo '</span>';
			}

			if($companyTitle !== '')
			{
				if($name !== '' || $post !== '')
				{
					echo ', ';
				}

				echo $companyTitle;
			}

			echo '</div>';
		}

		echo '</div>';
	}
	unset($arLead);
}
?>