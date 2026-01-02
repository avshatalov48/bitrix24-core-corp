<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title><?= Loc::getMessage('BICONNECTOR_DATASET_IMPORT_ERRORS_FILE_HEADER', ['#DATASET_TITLE#' => htmlspecialcharsbx($arParams['datasetTitle'] ?? '')]) ?></title>
	<style>
		body {
			font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Ubuntu, 'Helvetica Neue', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
		}
		table {
			width: 60%;
			background-color: #f5f7f8;
			border-collapse: collapse;
			border-radius: 10px;
		}
		td, th {
			padding: 14px;
			text-align: left;
			font-weight: 400;
			min-height: 46px;
			color: #525c69;
		}
		th {
			color: #828b95;
		}
		td {
			border-top: 1px solid #dfe0e3;
		}
		td:nth-child(1) {
			width: 5%;
			color: #828b95;
		}
		td:nth-child(2) {
			width: 60%;
		}
		td:nth-child(3) {
			width: 15%;
		}
		td:nth-child(4) {
			width: 20%;
		}
	</style>
</head>
<body>
<table>
	<thead>
	<tr>
		<th><?= Loc::getMessage('BICONNECTOR_DATASET_IMPORT_ERRORS_TABLE_HEADER_NUMBER') ?></th>
		<th><?= Loc::getMessage('BICONNECTOR_DATASET_IMPORT_ERRORS_TABLE_HEADER_ERROR_MESSAGE') ?></th>
		<th><?= Loc::getMessage('BICONNECTOR_DATASET_IMPORT_ERRORS_TABLE_HEADER_ROW_NUMBER') ?></th>
		<th><?= Loc::getMessage('BICONNECTOR_DATASET_IMPORT_ERRORS_TABLE_HEADER_FIELD_TITLE') ?></th>
	</tr>
	</thead>
	<tbody>