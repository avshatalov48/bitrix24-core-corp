<?
IncludeModuleLangFile('/bitrix/modules/intranet/install/1c_intranet.php');

$enum_index = 0;

$arIBlockFields = array(
	'ABSENCE' => array(
		'USER' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_USER'),
			'ACTIVE' => 'Y',
			'CODE' => 'USER',
			'PROPERTY_TYPE' => 'S',
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
			'USER_TYPE' => 'employee',
			'IS_REQUIRED' => 'Y',

		),
		'STATE' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_STATE'),
			'ACTIVE' => 'Y',
			'CODE' => 'STATE',
			'PROPERTY_TYPE' => 'S',
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
		),
		'FINISH_STATE' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_FINISH_STATE'),
			'ACTIVE' => 'Y',
			'CODE' => 'FINISH_STATE',
			'PROPERTY_TYPE' => 'S',
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
		),
		'ABSENCE_TYPE' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE'),
			'ACTIVE' => 'Y',
			'CODE' => 'ABSENCE_TYPE',
			'PROPERTY_TYPE' => 'L',
			'ROW_COUNT' => 1,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
			'VALUES' => array(
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_VACATION'),
					'DEF' => 'Y',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'VACATION',
				),
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_ASSIGNMENT'),
					'DEF' => 'N',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'ASSIGNMENT',
				),
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_LEAVESICK'),
					'DEF' => 'N',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'LEAVESICK',
				),
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_LEAVEMATERINITY'),
					'DEF' => 'N',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'LEAVEMATERINITY',
				),
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_LEAVEUNPAYED'),
					'DEF' => 'N',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'LEAVEUNPAYED',
				),
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_UNKNOWN'),
					'DEF' => 'N',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'UNKNOWN',
				),
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_PERSONAL'),
					'DEF' => 'N',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'PERSONAL',
				),
				array(
					'VALUE' => GetMessage('1C_INTRANET_FIELD_ABSENCE_TYPE_OTHER'),
					'DEF' => 'N',
					'SORT' => (++$enum_index) * 100,
					'XML_ID' => 'OTHER',
				),
			),
		),
	),
	
	'STATE_HISTORY' => array(
		'USER' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_USER'),
			'ACTIVE' => 'Y',
			'CODE' => 'USER',
			'PROPERTY_TYPE' => 'S',
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
			'USER_TYPE' => 'employee',
		
		),
		'USER_ACTIVE' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_USER_ACTIVE'),
			'ACTIVE' => 'Y',
			'CODE' => 'USER_ACTIVE',
			'PROPERTY_TYPE' => 'S',
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',

		),
		'DEPARTMENT' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_DEPARTMENT'),
			'ACTIVE' => 'Y',
			'CODE' => 'DEPARTMENT',
			'PROPERTY_TYPE' => 'G',
			'LINK_IBLOCK_ID' => 0,
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
		),
		'POST' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_POST'),
			'ACTIVE' => 'Y',
			'CODE' => 'POST',
			'PROPERTY_TYPE' => 'S',
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
		),
		'STATE' => array(
			'IBLOCK_ID' => 0,
			'NAME' => GetMessage('1C_INTRANET_FIELD_STATE'),
			'ACTIVE' => 'Y',
			'CODE' => 'STATE',
			'PROPERTY_TYPE' => 'L',
			'ROW_COUNT' => 1,
			'COL_COUNT' => 30,
			'LIST_TYPE' => 'L',
			'MULTIPLE' => 'N',
			'VALUES' => array(),
		),
	)
);

$arStateEnum = array('ACCEPTED', 'MOVED', 'FIRED');
foreach ($arStateEnum as $key => $XML_ID)
{
	$arIBlockFields['STATE_HISTORY']['STATE']['VALUES'][] = array(
		'XML_ID' => $XML_ID,
		'VALUE' => GetMessage('1C_INTRANET_FIELD_STATE_'.$XML_ID),
		'SORT' => ($key + 2) * 100,
		'DEF' => 'N',
	);
}
?>