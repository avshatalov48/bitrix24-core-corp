<?php
namespace Bitrix\Crm\Category;
class DealCategoryChangeError
{
	const NONE = 0;
	const GENERAL = 10;
	const NOT_FOUND = 20;
	const CATEGORY_NOT_FOUND = 30;
	const CATEGORY_NOT_CHANGED = 40;
	const RESPONSIBLE_NOT_FOUND = 50;
	const STAGE_NOT_FOUND = 50;
	const HAS_WORKFLOWS = 1000;
}