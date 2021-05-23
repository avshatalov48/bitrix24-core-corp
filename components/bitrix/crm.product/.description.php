<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_PRODUCT_COMPLEX_NAME'),
	'DESCRIPTION' => GetMessage('CRM_PRODUCT_COMPLEX_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 10,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'product',
			'NAME' => GetMessage('CRM_PRODUCT_NAME')
		)
	),
	'CACHE_PATH' => 'Y'
);