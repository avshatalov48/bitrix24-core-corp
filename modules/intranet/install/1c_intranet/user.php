<?
IncludeModuleLangFile('/bitrix/modules/intranet/install/1c_intranet.php');

$arUserFields = array(
	'UF_1C' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_1C',
		'USER_TYPE_ID' => 'boolean',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'N',
		'IS_SEARCHABLE' => 'Y',
		'SETTINGS' => array(
			'DISPLAY' => 'CHECKBOX',
		),
	),

	'UF_INN' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_INN',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),

	'UF_PHONE_INNER' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_PHONE_INNER',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'S',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	
	'UF_DISTRICT' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_DISTRICT',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),

	'UF_STATE_FIRST' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_STATE_FIRST',
		'USER_TYPE_ID' => 'datetime',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),

	'UF_STATE_LAST' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_STATE_LAST',
		'USER_TYPE_ID' => 'datetime',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),

	
	'UF_DEPARTMENT' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_DEPARTMENT',
		'USER_TYPE_ID' => 'iblock_section',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'Y',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
		'SETTINGS' => array(
			'DISPLAY' => 'LIST',
			'LIST_HEIGHT' => '8',
			'IBLOCK_ID' => 0,
		)
	),
);

foreach ($arUserFields as $key => $arFld)
{
	$arUserFields[$key]['EDIT_FORM_LABEL'] = $arUserFields[$key]['LIST_COLUMN_LABEL'] = $arUserFields[$key]['LIST_FILTER_LABEL'] = 
		array('ru' => GetMessage('1C_INTRANET_FIELD_'.$key));
}
?>