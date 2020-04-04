<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<script type="text/javascript">
var phpVars;
if (typeof(phpVars) != "object")
	var phpVars = {};

phpVars.LANGUAGE_ID = '<?=CUtil::JSEscape(LANGUAGE_ID)?>';
<?
$userOptions = CUserOptions::GetOption('webdav', 'navigator', array('platform'=>'Win'));
?>
phpVars.platform = '<?=CUtil::JSEscape($userOptions['platform'])?>';
</script>
