<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\UI;
use Bitrix\Main\Web\Uri;

UI\Extension::load("ui.tooltip");

$userClassAdditional = "";
if (
	isset($arParams['USER'])
	&& isset($arParams['USER']['TYPE'])
)
{
	switch ($arParams['USER']['TYPE'])
	{
		case 'email':
			$userClassAdditional = " feed-workday-user-name-email";
			break;
		case 'extranet':
			$userClassAdditional = " feed-workday-user-name-extranet";
			break;
		default:
			$userClassAdditional = '';
	}
}
?>
<span class="feed-workday-left-side">
	<div class="ui-icon ui-icon-common-user feed-user-avatar">
		<i
			<? if ($arParams['AVATAR_SRC']):?>
				style="background: url('<?=Uri::urnEncode($arParams['AVATAR_SRC'])?>'); background-size: cover;"
			<? endif ?>
		></i>
	</div>
	<span class="feed-user-name-wrap">
		<a class="feed-workday-user-name<?=$userClassAdditional?>" href="<?=$arParams['USER_URL']?>" bx-tooltip-user-id="<?=$arParams['USER']['ID']?>"><?=CUser::FormatName(
			$arParams['PARAMS']['NAME_TEMPLATE'],
			is_array($arParams['USER']) ? $arParams['USER'] : array()
		); ?></a>
		<span class="feed-workday-user-position"><?=htmlspecialcharsbx($arParams['USER']['WORK_POSITION'])?></span>
	</span>
</span>