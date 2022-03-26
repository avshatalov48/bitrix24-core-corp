<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true){die();};?>

<?if(\Bitrix\Tasks\Util\DisposableAction::needConvertTemplateFiles()):?>
	<?=\Bitrix\Main\Update\Stepper::getHtml("tasks");?>
<?endif?>