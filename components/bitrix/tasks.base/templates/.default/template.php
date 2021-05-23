<?
/**
 * This file contains a recommended default template structure.
 * 
 * $arResult format:
 *  ACTION_RESULT -		results of on-hit action processing
 * 	DATA - 				main compnent data
 *  CAN - 				data permissions
 *  AUX_DATA - 			different auxiliary data that is not a part of main data structure
 *  ERROR - 			all arrors occured during component execution
 *  MODE - 				current corportal plan modes
 *  TEMPLATE_DATA - 	some auxiliary data taken from result_modifier.php
 */

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<?if(is_array($arResult['ERROR']['FATAL']) && !empty($arResult['ERROR']['FATAL'])):?>
	<?foreach($arResult['ERROR']['FATAL'] as $error):?>
		<?=ShowError($error)?>
	<?endforeach?>
<?else:?>

	<?if(is_array($arResult['ERROR']['WARNING'])):?>
		<?foreach($arResult['ERROR']['WARNING'] as $error):?>
			<?=ShowError($error)?>
		<?endforeach?>
	<?endif?>

	<div id="sls-<?=$arResult['RANDOM_TAG']?>">
		<pre><?print_r($arResult);?></pre>
	</div>

	<?
	if((string) $arResult['TEMPLATE_DATA']['EXTENSION_ID'] != '')
	{
		CJSCore::Init($arResult['TEMPLATE_DATA']['EXTENSION_ID']);
	}
	?>

	<script>

	</script>

<?endif?>