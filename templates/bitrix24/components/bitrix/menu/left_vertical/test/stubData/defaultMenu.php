<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	array (
		'TEXT' => 'CRM analytics',
		'LINK' => '/crm/tracking/',
		'SELECTED' => false,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'menu_item_id' => 'menu_crm_tracking',
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => false,
	),
	array (
		'TEXT' => 'Tasks and projects',
		'LINK' => '/company/personal/user/tasks/',
		'SELECTED' => true,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'real_link' => '/company/personal/user//tasks/',
				'name' => 'tasks',
				'counter_id' => 'tasks_total',
				'menu_item_id' => 'menu_tasks',
				'sub_link' => '/company/personal/user//tasks/task/edit/0/',
				'top_menu_id' => 'tasks_panel_menu',
				'my_tools_section' => true,
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => false,
	),
	array (
		'TEXT' => 'RPA',
		'LINK' => '/rpa/',
		'SELECTED' => false,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'real_link' => '/rpa/',
				'counter_id' => 'rpa_tasks',
				'menu_item_id' => 'menu_rpa',
				'top_menu_id' => 'top_menu_id_rpa',
				'is_beta' => true,
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => false,
	),
	array (
		'TEXT' => 'Contact center (CC)',
		'LINK' => '/contact_center/',
		'SELECTED' => false,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'real_link' => '/contact_center/',
				'menu_item_id' => 'menu_contact_center',
				'top_menu_id' => 'top_menu_id_contact_center',
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => false,
	),
	array (
		'TEXT' => 'Documents',
		'LINK' => '/company/personal/user/disk/documents/',
		'SELECTED' => false,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'menu_item_id' => 'menu_documents',
				'my_tools_section' => true,
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => false,
	),
	array (
		'TEXT' => 'Devops',
		'LINK' => '/devops/',
		'SELECTED' => false,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'real_link' => '/devops/',
				'class' => 'menu-devops',
				'menu_item_id' => 'menu_devops_sect',
				'top_menu_id' => 'top_menu_id_devops',
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => true,
	),
	array (
		'TEXT' => 'Staff management',
		'LINK' => '/company/',
		'SELECTED' => false,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'real_link' => '/company/index.php',
				'class' => 'menu-company',
				'menu_item_id' => 'menu_staff_management',
				'top_menu_id' => 'top_menu_id_company',
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => true,
	),
	array (
		'TEXT' => 'Video something',
		'LINK' => '/conference/',
		'SELECTED' => false,
		'PERMISSION' => 'R',
		'ADDITIONAL_LINKS' =>
			array (
			),
		'ITEM_TYPE' => 'default',
		'PARAMS' =>
			array (
				'class' => 'menu-conference',
				'menu_item_id' => 'menu_conference',
				'top_menu_id' => 'top_menu_id_conference',
			),
		'DEPTH_LEVEL' => 1,
		'IS_PARENT' => false,
	),
];