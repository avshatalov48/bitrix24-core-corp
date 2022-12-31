<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

\Bitrix\Main\UI\Extension::load("main.rating");
\Bitrix\Main\UI\Extension::load("rest.client");

?><script>
	BX.message(<?=\CUtil::phpToJSObject(array(
		"RVR_MOBILE_TITLE" => Bitrix\Main\Localization\Loc::getMessage("RVR_MOBILE_TITLE"),
	))?>);
</script>
<div id="like-result-block" data-user-path="<?=htmlspecialcharsbx($arParams["PATH_TO_USER_PROFILE"])?>" class="bx-ilike-mobile-wrap">
	<div class="bx-ilike-mobile-content">
		<span class="bx-ilike-mobile-wrap-block bx-ilike-mobile-wrap-block-react">
			<span class="bx-ilike-mobile-popup">
				<span class="bx-ilike-mobile-popup-head" id="like-result-head"></span>
				<span class="bx-ilike-mobile-popup-content-container" id="like-result-page">
					<span class="bx-ilike-mobile-popup-content" id="like-result-content"></span>
				</span>
			</span>
		</span>
	</div>
</div>
<script>

</script>