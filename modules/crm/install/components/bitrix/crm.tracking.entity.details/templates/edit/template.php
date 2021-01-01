<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

$containerId = 'crm-tracking-entity-details-edit';
?>
<div id="<?=htmlspecialcharsbx($containerId)?>">
	<select name="<?=$arParams['SOURCE_INPUT_NAME']?>"
		class="crm-entity-widget-content-select"
	>
		<?foreach ($arResult['SOURCES'] as $source):?>
			<option value="<?=htmlspecialcharsbx($source['ID'])?>"
				<?=($source['ID'] === $arResult['SELECTED_SOURCE_ID'] ? 'selected' : '')?>
			>
				<?=htmlspecialcharsbx($source['NAME'])?>
			</option>
		<?endforeach;?>
	</select>
</div>
<?