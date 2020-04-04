<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

$imageFileName = '';
if(in_array(LANGUAGE_ID, array('en', 'de', 'ru', 'ua'), true))
{
	$imageFileName = 'livefeed-'.LANGUAGE_ID;
}

?><div class="crm-livefeed-placeholder-content">
	<div class="crm-livefeed-placeholder-text"><?=GetMessage('CRM_ENTITY_LF_PLACEHOLDER_PARAGRAPH_1')?></div>
	<div class="crm-livefeed-placeholder-text"><?=GetMessage('CRM_ENTITY_LF_PLACEHOLDER_PARAGRAPH_2')?></div>
	<?if($imageFileName !== '')
	{
		?><img alt="<?=GetMessage('CRM_ENTITY_LF_PLACEHOLDER_IMAGE_HINT')?>" class="crm-livefeed-placeholder-img" src="/bitrix/js/crm/images/placeholder/<?=$imageFileName?>.png"/><?
	}?>
</div>
