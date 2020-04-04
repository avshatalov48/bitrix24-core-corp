<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//attention! Necessarily in one line (without line break)!
?><script type="text/javascript">BX.message({disk_revision_api: '<?= (int)\Bitrix\Disk\Configuration::getRevisionApi() ?>',disk_document_service: '<?= (string)\Bitrix\Disk\UserConfiguration::getDocumentServiceCode() ?>',disk_render_uf: true});</script><?