<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load("ui.tooltip");

global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-gadget.css");
if (empty($arResult['DEAL']))
	echo GetMessage('CRM_DATA_EMPTY');
else
{
	foreach($arResult['DEAL'] as &$arDeal)
	{
		echo '<div class="crm-gadg-block">';

		echo '<div class="crm-gadg-title">';
		echo '<a href="', $arDeal['PATH_TO_DEAL_SHOW'], '" class="crm-gadg-link" title="', $arDeal['TITLE'], '" bx-tooltip-user-id="DEAL_', $arDeal['~ID'], '" bx-tooltip-loader="', htmlspecialcharsbx('/bitrix/components/bitrix/crm.deal.show/card.ajax.php'), '" bx-tooltip-classname="crm_balloon_no_photo">', $arDeal['TITLE'], '</a>';
		echo '<span class="crm-gadg-title-deadline"> &ndash; </span>';
		echo '<span class="crm-gadg-title-status">', $arDeal['DEAL_TYPE_NAME'], '</span>';
		echo '</div>';

		echo '<div class="crm-gadg-stage">';
		echo '<span class="crm-gadg-stage-left">', htmlspecialcharsbx(GetMessage('CRM_COLUMN_STAGE_ID')), ':<span>';
		echo '<span class="crm-gadg-stage-right">',htmlspecialcharsbx($arDeal['DEAL_STAGE_NAME']), '<span>';
		echo '</div>';

		$comments = isset($arDeal['~COMMENTS']) ? $arDeal['~COMMENTS'] : '';
		if($comments !== '')
		{
			echo '<div class="crm-gadg-text">';
			echo $comments;
			echo '</div>';
		}

		$opportunity = isset($arDeal['~OPPORTUNITY']) &&  floatval($arDeal['~OPPORTUNITY']);
		$contactID = isset($arDeal['~CONTACT_ID']) ? intval($arDeal['~CONTACT_ID']) : 0;
		$companyID = isset($arDeal['~COMPANY_ID']) ? intval($arDeal['~COMPANY_ID']) : 0;

		if($opportunity > 0 || $contactID > 0 || $companyID > 0)
		{
			echo '<div class="crm-gadg-footer">';

			if($opportunity > 0)
			{
				echo '<div class="crm-gadg-footer-row">';
				echo '<span class="crm-gadg-footer-left">',  htmlspecialcharsbx(GetMessage('CRM_DEAL_OPPORTUNITY')),':</span>';
				echo '<span class="crm-gadg-footer-right">', $arDeal['FORMATTED_OPPORTUNITY'],'</span>';
				echo '</div>';
			}

			$contactHtml = '';
			if($contactID > 0)
			{
				$contactHtml = CCrmViewHelper::PrepareClientBaloonHtml(
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
						'ENTITY_ID' => $contactID,
						'PREFIX' => uniqid("{$arResult['GADGET_ID']}_"),
						'NAME' => isset($arDeal['~CONTACT_NAME']) ? $arDeal['~CONTACT_NAME'] : '',
						'LAST_NAME' => isset($arDeal['~CONTACT_LAST_NAME']) ? $arDeal['~CONTACT_LAST_NAME'] : '',
						'SECOND_NAME' => isset($arDeal['~CONTACT_SECOND_NAME']) ? $arDeal['~CONTACT_SECOND_NAME'] : '',
						'CLASS_NAME' => 'crm-gadg-link'
					)
				);
			}
			$companyHtml = '';
			if($companyID > 0)
			{
				$companyTitle = isset($arDeal['~COMPANY_TITLE']) ? $arDeal['~COMPANY_TITLE'] : '';
				if($companyTitle !== '')
				{
					$companyHtml = CCrmViewHelper::PrepareClientBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $companyID,
							'PREFIX' => uniqid("{$arResult['GADGET_ID']}_"),
							'TITLE' => $companyTitle,
							'CLASS_NAME' => 'crm-gadg-link'
						)
					);
				}
			}

			if($contactHtml !== '' || $companyHtml !== '')
			{
				echo '<div class="crm-gadg-footer-row">';
				echo '<span class="crm-gadg-footer-left">', htmlspecialcharsbx(GetMessage('CRM_DEAL_CLIENT')), ':</span>';
				echo '<span class="crm-gadg-footer-right">';

				if($contactHtml !== '')
				{
					echo $contactHtml;
				}

				if($companyHtml !== '')
				{
					if($contactHtml !== '')
					{
						echo ', ';
					}

					echo $companyHtml;
				}
				echo '</span>';
				echo '</div>';
			}

			echo '</div>';
		}
		echo '</div>';
	}
	unset($arDeal);
}
?>