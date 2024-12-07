<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if ($this->getComponent()->getErrors()):?>
	<div class="ui-alert ui-alert-danger" style="margin-bottom: 0px;">
		<?php foreach($this->getComponent()->getErrors() as $error):?>
			<span class="ui-alert-message"><?= htmlspecialcharsbx($error->getMessage()) ?></span>
		<?php endforeach;?>
	</div>
	<?php return;
endif;

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
$APPLICATION->SetTitle($arResult['PAGE_TITLE']);
\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.entity.merger',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'GUID' => $arResult['MERGE_GUID'],
			'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
			'ENTITY_IDS' => $arResult['ENTITY_IDS'],
			'PATH_TO_DEDUPE_LIST' => '',
			'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_ENTITY_LIST'],
			'HEADER_TEMPLATE' => $arResult['HEADER_TEMPLATE'],
			'RESULT_LEGEND' => $arResult['RESULT_LEGEND'],
			'RESULT_TITLE' => $arResult['RESULT_TITLE'],
			'IS_RECEIVE_ENTITY_EDITOR_FROM_CONTROLLER' => true,
		],
		'USE_PADDING' => $arResult['USE_PADDING'] ?? false,
		'PAGE_MODE' => $arResult['PAGE_MODE'] ?? false,
		'PAGE_MODE_OFF_BACK_URL' => $arResult['PAGE_MODE_OFF_BACK_URL'],
	]
);
