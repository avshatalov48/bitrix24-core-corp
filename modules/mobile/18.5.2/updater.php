<?
if(IsModuleInstalled('mobile'))
{
	$updater->CopyFiles("install/templates", "templates");
	$updater->CopyFiles("install/components", "components");
}
?>
