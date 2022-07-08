<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
{
	return [];
}

if (defined('SITE_TEMPLATE_ID') && in_array(SITE_TEMPLATE_ID, ['bitrix24', 'desktop_app', 'login', 'pub']))
{
	return [
		'css' =>[
			'bitrix24-design-tokens.css',
		],
	];
}

return [];