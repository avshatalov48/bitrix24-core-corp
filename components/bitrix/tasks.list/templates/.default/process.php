<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true){die();};?>

<?if(\Bitrix\Tasks\Util\DisposableAction::needConvertTemplateFiles()):?>
	<?
	$GLOBALS['APPLICATION']->IncludeComponent("bitrix:tasks.util.process",
		'',
		array(
		),
		false,
		array("HIDE_ICONS" => "Y")
	);
	?>
	<?=\Bitrix\Main\Update\Stepper::getHtml("tasks");?>
<?endif?>