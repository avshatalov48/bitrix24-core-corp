<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

// tmp for compatibility with activity editor
$prefix = mb_strtolower('kanban_activity');
$activityEditorID = "{$prefix}_editor";
$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	array(
		'CONTAINER_ID' => '',
		'EDITOR_ID' => $activityEditorID,
		'PREFIX' => $prefix,
		'ENABLE_UI' => false,
		'ENABLE_TOOLBAR' => false,
		'ENABLE_EMAIL_ADD' => true,
		'ENABLE_TASK_ADD' => false,
		'MARK_AS_COMPLETED_ON_VIEW' => false,
		'SKIP_VISUAL_COMPONENTS' => 'Y'
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

// tmp for compatibility with user selector
if (\Bitrix\Main\Loader::includeModule('socialnetwork'))
{
	\CJSCore::init(array('socnetlogdest'));

	$destSort = \CSocNetLogDestination::GetDestinationSort(
		array(
			'DEST_CONTEXT' => \Bitrix\Crm\Entity\EntityEditor::getUserSelectorContext()
		)
	);
	$last = array();
	\CSocNetLogDestination::fillLastDestination(
		$destSort,
		$last
	);

	$destUserIDs = array();
	if(isset($last['USERS']))
	{
		foreach($last['USERS'] as $code)
		{
			$destUserIDs[] = str_replace('U', '', $code);
		}
	}

	$dstUsers =\CSocNetLogDestination::getUsers(array(
		'id' => $destUserIDs
	));
	$structure = \CSocNetLogDestination::getStucture(array(
		'LAZY_LOAD' => true
	));

	$department = $structure['department'];
	$departmentRelation = $structure['department_relation'];
	$departmentRelationHead = $structure['department_relation_head'];
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.Crm.EntityEditorUserSelector.users =  <?= \CUtil::phpToJSObject($dstUsers);?>;
				BX.Crm.EntityEditorUserSelector.department = <?= \CUtil::phpToJSObject($department);?>;
				BX.Crm.EntityEditorUserSelector.departmentRelation = <?= \CUtil::phpToJSObject($departmentRelation);?>;
				BX.Crm.EntityEditorUserSelector.last = <?= \CUtil::phpToJSObject(array_change_key_case($last, CASE_LOWER));?>;
			}
		);
	</script>
	<?
}