<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

?>
<table cellpadding="0" cellspacing="0" border="0" class="field-types-table">
<?foreach($arResult['ROWS'] as $arRow):?>
<tr>
	<td>
		<div class="field-types-name"><a href="<?=$arRow['LINK_LIST']?>"><?=$arRow['NAME']?></a></div>
		<div class="field-types-desc"><?=$arRow['DESC']?></div>
		<div class="field-types-add"><a href="<?=$arRow['LINK_ADD']?>" title="<?=GetMessage('CRM_FIELDS_ADD_FIELD_TITLE')?>"><?=GetMessage('CRM_FIELDS_ADD_FIELD')?></a></div>
		<div class="field-types-list"><a href="<?=$arRow['LINK_LIST']?>" title="<?=GetMessage('CRM_FIELDS_LIST_FIELD_TITLE')?>"><?=GetMessage('CRM_FIELDS_LIST_FIELD')?></a></div>
	</td>
</tr>
<?endforeach;?>
</table>