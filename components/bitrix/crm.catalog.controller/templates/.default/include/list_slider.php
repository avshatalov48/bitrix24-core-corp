<?php

if (!defined('URL_BUILDER_TYPE'))
{
	define('URL_BUILDER_TYPE', 'CRM');
}
if (!defined('SELF_FOLDER_URL'))
{
	define('SELF_FOLDER_URL', '/shop/settings/');
}
if (!defined('INTERNAL_ADMIN_PAGE'))
{
	define('INTERNAL_ADMIN_PAGE', 'Y');
}
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/admin/iblock_element_admin.php');
