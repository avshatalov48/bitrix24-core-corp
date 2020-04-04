<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Main;

class CCrmContact extends CAllCrmContact
{
	const TABLE_NAME = 'b_crm_contact';
	const COMPANY_TABLE_NAME = 'b_crm_contact_company';
	const DB_TYPE = 'MYSQL';
}
