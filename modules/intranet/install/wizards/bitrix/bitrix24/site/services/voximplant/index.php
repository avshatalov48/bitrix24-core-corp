<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule('voximplant'))
	return;

if(!CModule::IncludeModule('intranet'))
	return;

$checkCursor = \Bitrix\Voximplant\Model\RoleTable::getList(array(
	'limit' => 1
));

if($checkCursor->fetch())
	return;

$defaultRoles = array(
	'admin' => array(
		'NAME' => GetMessage('VOXIMPLANT_ROLE_ADMIN'),
		'PERMISSIONS' => array(
			'CALL_DETAIL' => array(
				'VIEW' => 'X',
			),
			'CALL' => array(
				'PERFORM' => 'X'
			),
			'CALL_RECORD' => array(
				'LISTEN' => 'X'
			),
			'USER' => array(
				'MODIFY' => 'X'
			),
			'SETTINGS' => array(
				'MODIFY' => 'X'
			),
			'LINE' => array(
				'MODIFY' => 'X'
			)
		)
	),
	'chief' => array(
		'NAME' => GetMessage('VOXIMPLANT_ROLE_CHIEF'),
		'PERMISSIONS' => array(
			'CALL_DETAIL' => array(
				'VIEW' => 'X',
			),
			'CALL' => array(
				'PERFORM' => 'X'
			),
			'CALL_RECORD' => array(
				'LISTEN' => 'X'
			),
		)
	),
	'department_head' => array(
		'NAME' => GetMessage('VOXIMPLANT_ROLE_DEPARTMENT_HEAD'),
		'PERMISSIONS' => array(
			'CALL_DETAIL' => array(
				'VIEW' => 'D',
			),
			'CALL' => array(
				'PERFORM' => 'X'
			),
			'CALL_RECORD' => array(
				'LISTEN' => 'D'
			),
		)
	),
	'manager' => array(
		'NAME' => GetMessage('VOXIMPLANT_ROLE_MANAGER'),
		'PERMISSIONS' => array(
			'CALL_DETAIL' => array(
				'VIEW' => 'A',
			),
			'CALL' => array(
				'PERFORM' => 'X'
			),
			'CALL_RECORD' => array(
				'LISTEN' => 'A'
			),
		)
	)
);

$roleIds = array();
foreach ($defaultRoles as $roleCode => $role)
{
	$addResult = \Bitrix\Voximplant\Model\RoleTable::add(array(
		'NAME' => $role['NAME'],
	));

	$roleId = $addResult->getId();
	if($roleId)
	{
		$roleIds[$roleCode] = $roleId;
		\Bitrix\Voximplant\Security\RoleManager::setRolePermissions($roleId, $role['PERMISSIONS']);
	}
}

if(isset($roleIds['admin']))
{
	\Bitrix\Voximplant\Model\RoleAccessTable::add(array(
		'ROLE_ID' => $roleIds['admin'],
		'ACCESS_CODE' => 'G1'
	));
}

if(isset($roleIds['manager']) && \Bitrix\Main\Loader::includeModule('intranet'))
{
	$departmentTree = CIntranetUtils::GetDeparmentsTree();
	$rootDepartment = (int)$departmentTree[0][0];

	if($rootDepartment > 0)
	{
		\Bitrix\Voximplant\Model\RoleAccessTable::add(array(
			'ROLE_ID' => $roleIds['manager'],
			'ACCESS_CODE' => 'DR'.$rootDepartment
		));
	}
}