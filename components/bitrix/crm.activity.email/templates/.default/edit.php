<?php

use Bitrix\Main\Loader;
use Bitrix\Mail\Helper;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

$formId = 'crm_act_email_create_form';

$activity = $arParams['ACTIVITY'];

$ownerUid = sprintf('CRM%s%u', \CCrmOwnerType::resolveName($activity['OWNER_TYPE_ID']), $activity['OWNER_ID']);

$socNetLogDestTypes = array(
	\CCrmOwnerType::LeadName    => 'leads',
	\CCrmOwnerType::DealName    => 'deals',
	\CCrmOwnerType::ContactName => 'contacts',
	\CCrmOwnerType::CompanyName => 'companies',
);

$rcptList = array(
	'users' => array(),
	'emails' => array(),
	'companies' => array(),
	'contacts' => array(),
	'deals' => array(),
	'leads' => array(),
);
$rcptLast = array(
	'users' => array(),
	'emails' => array(),
	'crm' => array(),
	'companies' => array(),
	'contacts' => array(),
	'deals' => array(),
	'leads' => array(),
);

$communications = array_merge(
	(array) $activity['__communications'],
	$activity['COMMUNICATIONS'],
	$activity['REPLY_TO'],
	$activity['REPLY_ALL'],
	$activity['REPLY_CC']
);

foreach ($communications as $k => $item)
{
	if (\CCrmOwnerType::isDefined($item['ENTITY_TYPE_ID']))
	{
		$item['ENTITY_TYPE'] = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
		$id = 'CRM'.$item['ENTITY_TYPE'].$item['ENTITY_ID'].':'.hash('crc32b', $item['TYPE'].':'.$item['VALUE']);
		$type = $socNetLogDestTypes[$item['ENTITY_TYPE']];

		$rcptList[$type][$id] = array(
			'id'         => $id,
			'entityId'   => $item['ENTITY_ID'],
			'entityType' => $type,
			'name'       => htmlspecialcharsbx($item['TITLE']),
			'desc'       => htmlspecialcharsbx($item['VALUE']),
			'email'      => htmlspecialcharsbx($item['VALUE']),
			'avatar'     => $item['IMAGE_URL'],
		);
		$rcptLast['crm'][$id] = $id;
		$rcptLast[$type][$id] = $id;
	}
	else
	{
		$id   = 'U'.md5($item['VALUE']);
		$type = 'users';

		$rcptList['emails'][$id] = $rcptList[$type][$id] = array(
			'id'         => $id,
			'entityId'   => $k,
			'name'       => htmlspecialcharsbx($item['VALUE']),
			'desc'       => htmlspecialcharsbx($item['VALUE']),
			'email'      => htmlspecialcharsbx($item['VALUE']),
			'isEmail'    => 'Y',
		);
		$rcptLast['emails'][$id] = $rcptLast[$type][$id] = $id;
	}
}

$rcptSelected = array();
$rcptCcSelected = array();

foreach (($activity['PARENT_ID'] ?? null) > 0 ? ['REPLY_ALL', 'REPLY_CC'] : ['COMMUNICATIONS'] as $field)
{
	foreach ($activity[$field] as $k => $item)
	{
		if (\CCrmOwnerType::isDefined($item['ENTITY_TYPE_ID']))
		{
			$item['ENTITY_TYPE'] = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
			$id = 'CRM'.$item['ENTITY_TYPE'].$item['ENTITY_ID'];
			$id = \Bitrix\Crm\Integration\Main\UISelector\CrmEntity::getMultiKey($id, $item['VALUE']);
			$type = $socNetLogDestTypes[$item['ENTITY_TYPE']].'_MULTI';
		}
		else
		{
			$id = 'U'.$item['ENTITY_ID'];
			$type = 'users';
		}

		switch ($field)
		{
			case 'COMMUNICATIONS':
			case 'REPLY_ALL':
				$rcptSelected[$id] = $type;
				break;
			case 'REPLY_CC':
				$rcptCcSelected[$id] = $type;
				break;
		}
	}
}

$docsList = array(
	'companies' => array(),
	'contacts' => array(),
	'deals' => array(),
	'leads' => array(),
);
$docsLast = array(
	'crm' => array(),
	'companies' => array(),
	'contacts' => array(),
	'deals' => array(),
	'leads' => array(),
);
$docsSelected = array();
foreach ($arParams['DOCS_BINDINGS'] as $item)
{
	$item['OWNER_TYPE'] = \CCrmOwnerType::resolveName($item['OWNER_TYPE_ID']);
	$id = 'CRM'.$item['OWNER_TYPE'].$item['OWNER_ID'];
	$type = $socNetLogDestTypes[$item['OWNER_TYPE']];

	$docsList[$type][$id] = array(
		'id'         => $id,
		'entityId'   => $item['OWNER_ID'],
		'entityType' => $type,
		'name'       => htmlspecialcharsbx($item['TITLE']),
		'desc'       => htmlspecialcharsbx($item['DESCRIPTION']),
	);
	$docsLast['crm'][$id] = $id;
	$docsLast[$type][$id] = $id;
	$docsSelected[$id] = $type;
}

if (!empty($arParams['TEMPLATES']))
{
	$this->setViewTarget('planner_slider_header');

	?>
	<div class="crm-activity-planner-slider-header-control-item crm-activity-planner-slider-header-control-select crm-activity-email-create-template">
		<div class="crm-activity-planner-slider-header-control-description"><?=getMessage('CRM_ACT_EMAIL_CREATE_TEMPLATE') ?>:</div>
		<div class="crm-activity-planner-slider-header-control-text"><?=getMessage('CRM_ACT_EMAIL_CREATE_NOTEMPLATE') ?></div>
		<div class="crm-activity-planner-slider-header-control-triangle"></div>
	</div>
	<?

	$this->endViewTarget();
}

?>

<form id="<?=htmlspecialcharsbx($formId) ?>" method="POST"
	action="/bitrix/components/bitrix/crm.activity.editor/ajax.php?action=save_email&context=<?=rawurlencode($_REQUEST['context']) ?>">
	<span id="crm_act_email_create_hidden" style="display: none; "></span>
	<?=bitrix_sessid_post() ?>
	<input type="hidden" name="ACTION" value="SAVE_EMAIL">
	<input type="hidden" name="DATA[ownerType]" value="<?=\CCrmOwnerType::resolveName($activity['OWNER_TYPE_ID']) ?>">
	<input type="hidden" name="DATA[ownerID]" value="<?=$activity['OWNER_ID'] ?>">
	<? if (preg_grep(sprintf('/^%s:/i', preg_quote($ownerUid, '/')), array_keys($rcptSelected + $rcptCcSelected))): ?>
		<input type="hidden" name="DATA[ownerRcpt]" value="Y">
	<? endif ?>
	<input type="hidden" name="DATA[storageTypeID]" value="<?=\CCrmActivityStorageType::Disk ?>">
	<? if (($activity['FORWARDED_ID'] ?? null) > 0): ?>
		<input name="DATA[FORWARDED_ID]" type="hidden" value="<?=$activity['FORWARDED_ID'] ?>">
	<? elseif (($activity['REPLIED_ID'] ?? null) > 0): ?>
		<input name="DATA[REPLIED_ID]" type="hidden" value="<?=$activity['REPLIED_ID'] ?>">
	<? endif ?>
	<input type="hidden" name="DATA[content_type]" value="<?=\CCrmContentType::Html ?>">

	<? 

	$inlineFiles = array();
	$quote = preg_replace_callback(
		'#/bitrix/tools/crm_show_file\.php\?fileId=(\d+)#i',
		function ($matches) use (&$inlineFiles)
		{
			$inlineFiles[] = $matches[1];
			return sprintf('%s&__bxacid=n%u', $matches[0], $matches[1]);
		},
		$activity['DESCRIPTION_HTML']
	);

	$attachedFiles = (array) $activity['STORAGE_ELEMENT_IDS'];
	if (
		empty($activity['FORWARDED_ID'] ?? null)
		&& ($activity['REPLIED_ID'] ?? null) > 0
	)
	{
		$attachedFiles = array_intersect($attachedFiles, $inlineFiles);
	}

	$selectorParams = array(
//		'pathToAjax'               => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?soc_net_log_dest=search_email_comms',
		'CrmTypes'                 => array('CRMCONTACT', 'CRMCOMPANY', 'CRMLEAD'),
		'extranetUser'             => false,
		'isCrmFeed'                => true,
		'useClientDatabase'        => false,
		'enableUsers'              => false,
		'enableEmailUsers'         => true,
		'allowAddUser'             => true,
		'allowAddCrmContact'       => false,
		'allowSearchEmailUsers'    => false,
		'allowSearchCrmEmailUsers' => false,
		'allowUserSearch'          => false,
		'items'                    => $rcptList,
		'itemsLast'                => $rcptLast,
		'emailDescMode'            => true,
		'searchOnlyWithEmail'      => true,
	);

	if ($arParams['DOCS_READONLY'])
	{
		if (count($arParams['DOCS_BINDINGS']) > 0)
		{
			$dealsField = array(
				'name'  => 'DATA[docs]',
				'title' => getMessage('CRM_ACT_EMAIL_DEAL'),
				'type'   => 'custom',
				'height' => 25,
				'value'  => $arParams['DOCS_BINDINGS'],
				'render' => function($field)
				{
					ob_start();

					$k = count($field['value']);
					foreach ($field['value'] as $item)
					{
						?><a class="crm-task-list-mail-additionally-info-text-bold" href="<?=htmlspecialcharsbx($item['DOC_URL']) ?>"><?
							echo htmlspecialcharsbx($item['DOC_NAME']), ' - ', htmlspecialcharsbx($item['TITLE']);
						?></a><? if (--$k > 0) echo ', ';
					}

					return ob_get_clean();
				},
			);
		}
	}
	elseif (
		$activity['OWNER_TYPE_ID'] !== \CCrmOwnerType::Order
		&& !\CCrmOwnerType::isUseDynamicTypeBasedApproach($activity['OWNER_TYPE_ID'])
	)
	{
		$dealsField = array(
			'name'  => 'DATA[docs]',
			'title' => getMessage('CRM_ACT_EMAIL_DEAL'),
			'placeholder' => getMessage('CRM_ACT_EMAIL_REPLY_SET_DOCS'),
			'type'        => 'entity',
			//'value'       => $docsSelected,
			'email'       => false,
			'multiple'    => false,
			'selector'    => array(
				'extranetUser'             => false,
				'isCrmFeed'                => true,
				'useClientDatabase'        => false,
				'allowAddUser'             => false,
				'allowAddCrmContact'       => false,
				'allowSearchEmailUsers'    => false,
				'allowSearchCrmEmailUsers' => false,
				'allowUserSearch'          => false,
				'CrmTypes'                 => array('CRMDEAL'),
				'items'                    => $docsList,
				'itemsLast'                => $docsLast,
				'itemsSelected'            => $docsSelected,
			),
		);
	}

	$fromValue = \CUserOptions::getOption('crm', 'activity_email_addresser', '');
	if ('RE' == ($activity['__message_type'] ?? null) && !empty($activity['__parent']['SETTINGS']['EMAIL_META']['__email']))
	{
		$fromValue = $activity['__parent']['SETTINGS']['EMAIL_META']['__email'];
	}

	$APPLICATION->includeComponent(
		'bitrix:main.mail.form', '',
		array(
			'VERSION' => 2,
			'FORM_ID' => $formId,
			'LAYOUT_ONLY' => true,
			'SUBMIT_AJAX' => true,
			'FOLD_FILES' => ($activity['REPLIED_ID'] ?? null) > 0,
			'EDITOR_TOOLBAR' => true,
			'USE_SIGNATURES' => true,
			'FIELDS' => array(
				array(
					'name'     => 'DATA[from]',
					'title'    => getMessage('CRM_ACT_EMAIL_CREATE_FROM'),
					'type'     => 'from',
					'value'    => $fromValue,
					'isFormatted' => true,
					'required' => true,
					'copy' => 'DATA[from_copy]',
				),
				array(
					'type' => 'separator',
				),
				array(
					'name'        => 'DATA[to]',
					'title'       => getMessage('CRM_ACT_EMAIL_CREATE_TO'),
					'placeholder' => getMessage('CRM_ACT_EMAIL_REPLY_ADD_RCPT'),
					'type'        => 'rcpt',
					//'value'       => $rcptSelected,
					'selector'    => array_merge(
						$selectorParams,
						array('itemsSelected' => $rcptSelected)
					),
					'required' => true,
				),
				array(
					'name'        => 'DATA[cc]',
					'title'       => getMessage('CRM_ACT_EMAIL_CREATE_CC'),
					'placeholder' => getMessage('CRM_ACT_EMAIL_REPLY_ADD_RCPT'),
					'type'        => 'rcpt',
					'folded'      => empty($rcptCcSelected),
					//'value'       => $rcptCcSelected,
					'selector'    => array_merge(
						$selectorParams,
						array('itemsSelected' => $rcptCcSelected)
					),
				),
				array(
					'name'        => 'DATA[bcc]',
					'title'       => getMessage('CRM_ACT_EMAIL_CREATE_BCC2'),
					'placeholder' => getMessage('CRM_ACT_EMAIL_REPLY_ADD_RCPT'),
					'type'        => 'rcpt',
					'folded'      => true,
					'selector'    => $selectorParams,
				),
				array(
					'name'        => 'DATA[subject]',
					'title'       => getMessage('CRM_ACT_EMAIL_CREATE_SUBJECT'),
					'placeholder' => getMessage('CRM_ACT_EMAIL_CREATE_SUBJECT_PH'),
					'value'       => $activity['SUBJECT'],
				),
				array(
					'name'  => 'DATA[message]',
					'type'  => 'editor',
					'value' => $quote,
				),
				array(
					'name'  => 'DATA[__diskfiles]',
					'type'  => 'files',
					'value' => array_map(
						function ($item)
						{
							return is_scalar($item) ? sprintf('n%u', $item) : $item;
						},
						$attachedFiles
					),
				),
			),
			'FIELDS_EXT' => !empty($dealsField) ? array($dealsField) : array(),
			'BUTTONS' => array(
				'submit' => array(
					'class' => 'ui-btn-primary',
					'title' => getMessage('CRM_ACT_EMAIL_CREATE_SEND'),
				),
				'cancel' => array(
					'title' => getMessage('CRM_ACT_EMAIL_CREATE_CANCEL'),
				),
			),
			'FOOTER' => '<label class="crm-task-list-mail-additionally-info-name" style="display: flex; align-items: center; ">
				<input type="checkbox" name="save_as_template" value="1"
					style="margin: 0 5px; ">'.getMessage('CRM_ACT_EMAIL_CREATE_SAVE_TEMPLATE').'</label>',
		)
	);

	?>

</form>

<?
	if(!Loader::includeModule("mail"))
	{
		echo getMessage('CRM_ACT_EMAIL_NO_MAIL');
		die();
	}
?>

<script type="text/javascript">

BX.message({
	CRM_ACT_EMAIL_REPLY_EMPTY_RCPT: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_EMPTY_RCPT')) ?>',
	CRM_ACT_EMAIL_REPLY_UPLOADING: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_REPLY_UPLOADING')) ?>',
	CRM_ACT_EMAIL_MAX_SIZE: <?=Helper\Message::getMaxAttachedFilesSize();?>,
	CRM_ACT_EMAIL_MAX_SIZE_EXCEED: '<?=\CUtil::jsEscape(getMessage(
		'CRM_ACTIVITY_EMAIL_MAX_SIZE_EXCEED',
		['#SIZE#' => \CFile::formatSize(Helper\Message::getMaxAttachedFilesSizeAfterEncoding(),1)]
	)) ?>',
	CRM_ACT_EMAIL_CREATE_NOTEMPLATE: '<?=\CUtil::jsEscape(getMessage('CRM_ACT_EMAIL_CREATE_NOTEMPLATE')) ?>'
});

BX.ready(function ()
{
	BXCrmActivityEmailController.init({
		activityId: <?=intval($activity['ID'] ?? null) ?>,
		type: 'edit',
		templates: <?=\Bitrix\Main\Web\Json::encode($arParams['TEMPLATES_BY_TYPE']) ?>
	});
	var instance = new BXCrmActivityEmail({
		activityId: <?=intval($activity['ID'] ?? null) ?>,
		formId: '<?=\CUtil::jsEscape($formId) ?>'
	});

	setTimeout(function ()
	{
		var mailForm = BXMainMailForm.getForm('<?=\CUtil::jsEscape($formId) ?>');

		mailForm.init();

		BX.bind(BX('crm_act_email_create_batch'), 'change', function ()
		{
			instance.batch(this.checked);
		});

		var wrapper  = BX.findChildByClassName(document, 'crm-activity-email-create-template', true);
		var selector = BX.findChildByClassName(wrapper, 'crm-activity-planner-slider-header-control-text', true);
		BX.bind(wrapper, 'click', function ()
		{
			instance.templateMenu(
				'<?=\CUtil::jsEscape($activity['INITIAL_OWNER_TYPE']) ?>',
				<?=intval($activity['INITIAL_OWNER_ID']) ?>,
				selector
			);
		});
	}, 10);
});

</script>
