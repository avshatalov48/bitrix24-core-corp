<?php
use \Bitrix\Main;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var array $arParams
 * @var array $arResult
 * @var array $entity
 * @var CBitrixComponentTemplate $this
 */
CModule::IncludeModule('ui');
Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Main\UI\Extension::load([
	'main.loader',
	'ui.userfield',
	'ui.buttons',
	'ui.icons',
	'ui.vue.components.audioplayer',
	'mobile.diskfile',
	'mobile.utils',
	'date',
	'main.core',
	'main.core.events',
	'mobile_uploader',
	'fx'
]);
$currentUser = ['AUTHOR_ID' => CCrmSecurityHelper::GetCurrentUserID()];
Bitrix\Crm\Timeline\Controller::prepareAuthorInfo($currentUser);
$item = reset($arResult['ITEMS']);
?>
<div class="crm-phonetracker-detail-comments-container">
	<div id="phonetracker-items-<?=$arResult['ENTITY']['ID']?>">
	<?php
if (!empty($arResult['ACTIVITIES']))
{
	?><div class="crm-phonetracker-detail-comments" id="phonetracker-activities-<?=$arResult['ENTITY']['ID']?>"></div><?php
}
?><div class="crm-phonetracker-detail-comments-empty"><?=Main\Localization\Loc::getMessage('MPT_ADD_FIRST_COMMENT')?></div>
	</div>
</div>
<script>
	BX.message(<?=CUtil::phpToJsObject(Main\Localization\Loc::loadLanguageFile(__FILE__))?>);
	BX.ready(function() {
		BX.Mobile.Crm.Calltracker.Configuration.set(<?=CUtil::PhpToJSObject([
			'componentName' => $this->getComponent()->getName(),
			'signedParameters' => $this->getComponent()->getSignedParameters(),
			'currentAuthor' => $currentUser
		])?>);
		(new BX.Mobile.Crm.Calltracker.Timeline(
			{
				entity: <?=CUtil::PhpToJSObject(['ID' => $arResult['ENTITY']['ID']])?>,
				containerScheduleItems: BX('phonetracker-activities-<?=$arResult['ENTITY']['ID']?>'),
				scheduleItems: <?=CUtil::PhpToJSObject($arResult['ACTIVITIES'])?>,
				containerHistoryItems: BX('phonetracker-items-<?=$arResult['ENTITY']['ID']?>'),
				historyItems: <?=CUtil::PhpToJSObject($arResult['ITEMS'])?>,
			}
		))<?=($arResult['PAGINATION_HAS_MORE'] ? '.initPagination('.($item ? $item['ID'] : 0).')' : '')?>;
	});
</script>