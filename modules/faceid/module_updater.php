<?php
global $DB, $DBType;
// internal function
function initFaceidUpdater($copyFiles = false)
{
	global $DB, $DBType;

	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_class.php");
	$updater = new CUpdater();
	$updater->Init($curPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/", $DBType, $updaterName = "", $curDir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/faceid/module_updater.php", "faceid", $copyFiles? "ALL": "DB");

	return $updater;
}
function getFaceidUpdaterVersion()
{
	return intval(COption::GetOptionInt("faceid", "~database_schema_version", 0));
}
function setFaceidUpdaterVersion($version)
{
	$version = intval($version);
	COption::SetOptionString("faceid", "~database_schema_version", $version);
}
$currentVersion = getFaceidUpdaterVersion();

if($currentVersion < 1)
{
	$updater = initFaceidUpdater();
	if (!$updater->TableExists("b_faceid_tracking_visitors"))
	{
		$updater->Query(array(
			"MySql" => "CREATE TABLE IF NOT EXISTS `b_faceid_tracking_visitors` (
			  `ID` int(10) unsigned NOT NULL,
			  `FILE_ID` int(10) unsigned NOT NULL,
			  `FACE_ID` int(10) unsigned NOT NULL,
			  `CRM_ID` int(10) unsigned NOT NULL,
			  `VK_ID` varchar(50) NOT NULL,
			  `FIRST_VISIT` datetime NOT NULL,
			  `PRELAST_VISIT` datetime NOT NULL,
			  `LAST_VISIT` datetime NOT NULL,
			  `LAST_VISIT_ID` int(10) unsigned NOT NULL,
			  `VISITS_COUNT` int(10) unsigned NOT NULL
			)"
		));

		$updater->Query(array(
			"MySql" => "ALTER TABLE `b_faceid_tracking_visitors` ADD PRIMARY KEY (`ID`)"
		));

		$updater->Query(array(
			"MySql" => "ALTER TABLE `b_faceid_tracking_visitors` MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT"
		));
	}
	if (!$updater->TableExists("b_faceid_tracking_visits"))
	{
		$updater->Query(array(
			"MySql" => "CREATE TABLE IF NOT EXISTS `b_faceid_tracking_visits` (
			  `ID` int(10) unsigned NOT NULL,
			  `VISITOR_ID` int(10) unsigned NOT NULL,
			  `DATE` datetime NOT NULL
			)"
		));

		$updater->Query(array(
			"MySql" => "ALTER TABLE `b_faceid_tracking_visits` ADD PRIMARY KEY (`ID`), ADD KEY `VISITOR_ID` (`VISITOR_ID`)"
		));

		$updater->Query(array(
			"MySql" => "ALTER TABLE `b_faceid_tracking_visits` MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT"
		));
	}
	setFaceidUpdaterVersion(1);
}
