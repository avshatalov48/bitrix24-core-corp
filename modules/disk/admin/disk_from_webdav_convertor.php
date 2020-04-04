<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity;
use Bitrix\Disk\Folder;
use \Bitrix\Disk\ProxyType;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

require_once 'smart_migration_webdav.php';
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
/** @var CAllUser $USER */
/** @var CAllMain $APPLICATION */
global $USER, $APPLICATION;

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

class CFromWebDavConvertor
{
	const DENY_TASK = PHP_INT_MAX;

	const STATUS_FINISH       = 2;
	const STATUS_TIME_EXPIRED = 3;
	const STATUS_ERROR        = 4;

	const UF_DISK_FILE_ID     = 'UF_DISK_FILE_ID';
	const UF_DISK_FILE_STATUS = 'UF_DISK_FILE_STATUS';

	const UF_DISK_FOLDER_ID      = 'UF_DISK_FOLDER_ID';
	const UF_DISK_INTO_TRASH     = 'UF_DISK_INTO_TRASH';
	const UF_DISK_STATUS_MIGRATE = 'UF_DISK_ST_MIGRATE';

	const STATUS_MIGRATE_FINAL             = 2;
	const STATUS_MIGRATE_WITHOUT_TRASH     = 3;
	const STATUS_MIGRATE_WITHOUT_STRUCTURE = 4;
	const STATUS_MIGRATE_SKIP              = 5;
	const STATUS_MIGRATE_WITH_FILES        = 6;

	public static $countConvertElements = 0;
	public static $countConvertSections = 0;
	protected $currentIblockId;
	protected $timeStart = 0;

	/** @var array|null */
	protected $iblockTasks = null;
	protected $diskTasks = null;
	protected $iblockOperationsByTask;
	protected $diskOperationsByTask;
	/**
	 * Seconds
	 * @var int
	 */
	protected $maxExecution = 23;

	/** \Bitrix\Disk\Storage */
	protected $currentStorage;
	/** @var  Folder[] */
	protected $currentFolderMap;
	protected $runWorkWithBizproc = false;

	protected function getIblockWithUserFiles()
	{
		static $userIblockIds = array();
		if($userIblockIds)
		{
			return $userIblockIds;
		}

		$q = CIBlock::GetList(array(), array("CODE" => "user_files%", "TYPE" => "library"));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$userIblockIds[$iblock['ID']] = $iblock;
			}
		}

		return $userIblockIds;
	}

	protected function getIblockIdsWithGroupFiles()
	{
		static $groupIblockIds = array();
		if($groupIblockIds)
		{
			return $groupIblockIds;
		}

		$q = CIBlock::GetList(array(), array("CODE" => "group_files%", "TYPE" => "library"));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$groupIblockIds[$iblock['ID']] = $iblock;
			}
		}

		return $groupIblockIds;
	}

	protected function getIblockIdsWithCommonFiles()
	{
		static $commonIblockIds = array();
		if($commonIblockIds)
		{
			return $commonIblockIds;
		}

		$q = CIBlock::GetList(array('ID' => 'ASC'), array('!CODE' => array('group_files%', 'user_files%'), 'TYPE' => 'library'));
		if($q)
		{
			while($iblock = $q->fetch())
			{
				$commonIblockIds[$iblock['ID']] = $iblock;
			}
		}

		return $commonIblockIds;
	}

	protected function dropSectionProperty($iblockId, $propertyName)
	{
		// Check UF for iblock sections
		global $USER_FIELD_MANAGER;

		$ent_id = "IBLOCK_".$iblockId."_SECTION";
		$db_res = CUserTypeEntity::GetList(array('ID'=>'ASC'), array("ENTITY_ID" => $ent_id, "FIELD_NAME" => $propertyName ));
		if ($db_res && ($r = $db_res->GetNext()))
		{
			$obUserField = new CUserTypeEntity;
			$obUserField->delete($r['ID']);
			$USER_FIELD_MANAGER->arFieldsCache = array();
		}
	}

	protected function dropElementProperty($iblockId, $propertyName)
	{
		$query = CIBlockProperty::GetList(
			Array(
				"SORT" => "ASC",
				"NAME" => "ASC"
			),
			Array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $iblockId,
				'CODE' => $propertyName
			)
		);
		if($query && ($row = $query->fetch()))
		{
			CIBlockProperty::Delete($row['ID']);
		}

	}

	public function revertFlagsAndData()
	{
		COption::RemoveOption('disk');
		foreach($this->getIblocks() as $iblock)
		{
			$this->dropSectionProperty($iblock['ID'], self::UF_DISK_FOLDER_ID);
			$this->dropSectionProperty($iblock['ID'], self::UF_DISK_STATUS_MIGRATE);
			$this->dropSectionProperty($iblock['ID'], self::UF_DISK_INTO_TRASH);

			$this->dropElementProperty($iblock['ID'], self::UF_DISK_FILE_ID);
			$this->dropElementProperty($iblock['ID'], self::UF_DISK_FILE_STATUS);
		}
		
		$tables = array(
			'b_disk_attached_object',
			'b_disk_deleted_log',
			'b_disk_edit_session',
			'b_disk_external_link',
			'b_disk_object',
			'b_disk_object_path',
			'b_disk_right',
			'b_disk_sharing',
			'b_disk_simple_right',
			'b_disk_storage',
			'b_disk_tmp_file',
			'b_disk_version',
		);
		$connection = Application::getInstance()->getConnection();
		foreach($tables as $name)
		{
			$connection->queryExecute('TRUNCATE TABLE ' . $name);
		}
		unset($name);
		$connection->queryExecute("DELETE FROM b_bp_workflow_template WHERE MODULE_ID = 'disk'");
		$connection->queryExecute("DELETE FROM b_bp_workflow_state WHERE MODULE_ID = 'disk'");

		if($connection->isTableExists('b_disk_utm_sonet_log_crm'))
		{
			$connection->dropTable('b_disk_utm_sonet_log_crm');
		}
		if($connection->isTableExists('b_disk_utm_sonet_comment_crm'))
		{
			$connection->dropTable('b_disk_utm_sonet_comment_crm');
		}
		Option::set(
			'disk',
			'successfully_converted',
			false
		);

		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'webdav', 'CUserTypeWebdavElement', 'GetUserTypeDescription');
		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'webdav', 'CUserTypeWebdavElementHistory', 'GetUserTypeDescription');

	}

	public function run()
	{
		$clever = new SmartMigrationWebdav();
		return $clever->run();
	}

	/**
	 * @return array
	 */
	protected function getIblocks()
	{
		return array_merge($this->getIblockWithUserFiles(), $this->getIblockIdsWithGroupFiles(), $this->getIblockIdsWithCommonFiles());
	}
}

class TimeExecutionException extends \Bitrix\Main\SystemException
{}

IncludeModuleLangFile(__FILE__);

if($_GET['revert'])
{
	$convertor = new CFromWebDavConvertor();
	$convertor->revertFlagsAndData();
}

if (isset($_REQUEST['webdav_process']) && ($_REQUEST['webdav_process'] === 'Y'))
{
	CUtil::JSPostUnescape();

	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_js.php');

	if(!empty($_REQUEST['publishDocs']))
	{
		$_SESSION['DISK_FW_publishDocs'] = true;
	}


	$totalCount = 40; //count of steps
	$processedSummary = 0;

	$status = false;
	try
	{
		$convertor = new SmartMigrationWebdav(array('publishDocs' => $_SESSION['DISK_FW_publishDocs']));
		$status = $convertor->run();

		$processedSummary += $convertor::$countSuccessfulSteps;
	}
	catch (TimeExecutionException $e)
	{
		$status = $convertor::STATUS_TIME_EXPIRED;
		$processedSummary += $convertor::$countSuccessfulSteps;
	}
	catch (Exception $e)
	{
		throw $e;
		$status = $convertor::STATUS_ERROR;
		$processedSummary += $convertor::$countSuccessfulSteps;
	}

	?>
	<script>
		CloseWaitWindow();
	</script>
	<?php

	if ($status === $convertor::STATUS_ERROR)
	{
		CAdminMessage::ShowMessage(
			array(
				'MESSAGE' => GetMessage('DISK_FW_CONVERT_FAILED'),
				'DETAILS' => '<div id="wd_convert_finish"></div>',
				'HTML'    => true,
				'TYPE'    => 'ERROR'
			)
		);

		?>
		<script>
			StopConvert();
		</script>
		<?php
	}
	elseif ($status === $convertor::STATUS_FINISH)
	{
		unset($_SESSION['DISK_FW_publishDocs']);

		CAdminMessage::ShowMessage(array(
			"TYPE" => "PROGRESS",
			"HTML" => true,
			"MESSAGE" => GetMessage('DISK_FW_CONVERT_COMPLETE'),
			"DETAILS" => "#PROGRESS_BAR#",
			"PROGRESS_TOTAL" => 1,
			"PROGRESS_VALUE" => 1,
		));

		CAdminMessage::ShowMessage(
			array(
				'MESSAGE' => GetMessage('DISK_FW_CONVERT_COMPLETE'),
				'DETAILS' => '<div id="wd_convert_finish"></div>',
				'HTML'    => true,
				'TYPE'    => 'OK'
			)
		);

		?>
		<script>
			EndConvert();
		</script>
		<?php
	}
	elseif($status === $convertor::STATUS_TIME_EXPIRED)
	{
		CAdminMessage::ShowMessage(array(
			"TYPE" => "PROGRESS",
			"HTML" => true,
			"MESSAGE" => GetMessage('DISK_FW_CONVERT_IN_PROGRESS'),
			"DETAILS" => "#PROGRESS_BAR#",
			"PROGRESS_TOTAL" => $totalCount,
			"PROGRESS_VALUE" => intval($processedSummary),
		));

		?>
		<script>
			DoNext(<?php echo $processedSummary; ?>);
		</script>
		<?php
	}

	require($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin_js.php');
}
else
{

	$listNonSupportedFeatures = array(
		Loc::getMessage('DISK_FW_CONVERT_HELP_3') => "
			SELECT 'navidnavi' FROM b_iblock WHERE IBLOCK_TYPE_ID = 'library' AND WORKFLOW = 'Y'
		",
		Loc::getMessage('DISK_FW_CONVERT_HELP_4') => "
			SELECT 'navidnavi'
				FROM b_iblock_element el
				INNER JOIN b_iblock ib ON ib.IBLOCK_TYPE_ID = 'library' AND ib.ID = el.IBLOCK_ID
			WHERE el.PREVIEW_TEXT <> ''
		",
		Loc::getMessage('DISK_FW_CONVERT_HELP_75') => "
			SELECT 'navidnavi'
				FROM b_iblock_element el
				INNER JOIN b_iblock ib ON ib.IBLOCK_TYPE_ID = 'library' AND ib.ID = el.IBLOCK_ID
			WHERE el.TAGS <> ''
		",
		Loc::getMessage('DISK_FW_CONVERT_HELP_5') => "
			SELECT 'navidnavi'
			FROM b_iblock_element el
				INNER JOIN b_iblock ib ON ib.IBLOCK_TYPE_ID = 'library' AND ib.ID = el.IBLOCK_ID
				INNER JOIN b_forum_message mess ON mess.PARAM2 = el.ID
		",
		Loc::getMessage('DISK_FW_CONVERT_HELP_6') => "
			SELECT 'navidnavi'
				FROM b_iblock_element el
				INNER JOIN b_iblock ib ON ib.IBLOCK_TYPE_ID = 'library' AND ib.ID = el.IBLOCK_ID
			WHERE
				EXISTS(SELECT 'x' FROM b_rating_voting vot WHERE vot.ENTITY_TYPE_ID = 'IBLOCK_ELEMENT' AND vot.ENTITY_ID = el.ID AND vot.TOTAL_VOTES > 0)
		",
		Loc::getMessage('DISK_FW_CONVERT_HELP_7') => "
			SELECT 'navidnavi'
				FROM b_iblock_element origin
				INNER JOIN b_iblock ib ON ib.IBLOCK_TYPE_ID = 'library' AND BIZPROC = 'Y' AND ib.ID = origin.IBLOCK_ID
				WHERE
					EXISTS(SELECT 'x' FROM b_iblock_element child
						WHERE child.WF_PARENT_ELEMENT_ID = origin.ID AND child.WF_PARENT_ELEMENT_ID IS NOT NULL)
		",
	);

	$isMysql = Application::getConnection() instanceof \Bitrix\Main\DB\MysqlCommonConnection;
	foreach($listNonSupportedFeatures as $label => &$feature)
	{
		$feature = Application::getConnection()->query($feature . ($isMysql? ' LIMIT 1' : ''))->fetch();
		$feature = (bool)$feature['navidnavi'];

		if(!$feature)
		{
			unset($listNonSupportedFeatures[$label]);
		}
	}
	unset($feature, $label);

	foreach($listNonSupportedFeatures as $label => &$feature)
	{
		$feature = '<span style="margin-left: 10px;">' . $label . '</span>';
	}
	unset($feature);
	$listNonSupportedFeatures = implode('<br/>', $listNonSupportedFeatures) . '<br/>';

	$APPLICATION->SetTitle(GetMessage('DISK_FW_CONVERT_TITLE'));

	$aTabs = array(
		array(
			'DIV'   => 'edit1',
			'TAB'   => GetMessage('DISK_FW_CONVERT_TAB'),
			'ICON'  => 'main_user_edit',
			'TITLE' => GetMessage('DISK_FW_CONVERT_TAB_TITLE')
		)
	);

	$tabControl = new CAdminTabControl('tabControl', $aTabs, true, true);

	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

	?>
	<style type="text/css">
		.bx-webdav-disk-help-btn {
			background:url("/bitrix/panel/main/images/bx-admin-sprite.png") no-repeat 4px -88px;
			color:#e3ecee;
			cursor:pointer;
			display:inline-block;
			line-height:14px;
			height:24px;
			margin-right:15px;
			padding:5px 0 0 30px;
			text-decoration: none;
		}

		.bx-webdav-disk-help-btn:hover {
			background:url("/bitrix/panel/main/images/bx-admin-sprite.png") no-repeat 4px -88px;
		}
	</style>
	<script language='JavaScript'>
	var wd_stop;
	var wd_dialog;

	function ShowConvert()
	{
		var dialog = new BX.CDialog({
			title: '<?= GetMessageJS('DISK_FW_CONVERT_TAB_TITLE') ?>',
			width: 450,
			heght: 400,
			buttons: [
				{
					title: '<?= GetMessageJS('DISK_FW_CONVERT_START_BUTTON')?>',
					id: 'run',
					name: 'run',
					className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
					action: function () {
						if(!BX('make_public', false).checked && !BX('make_unpublic', false).checked)
						{
							BX('bx-webdav-disk-need-choice').style.border = '1px solid #DF0101';

							return;
						}

						StartConvert();

						BX.cleanNode(this.parentWindow.PARAMS.content);
						this.parentWindow.Close();
					}
				},
				{
					title: BX.message('JS_CORE_WINDOW_CLOSE'),
					id: 'close',
					name: 'close',
					action: function () {
						BX.cleanNode(this.parentWindow.PARAMS.content);

						this.parentWindow.Close();
					}
				}
			],
			content: '<div style="margin-bottom: 10px;"><b><?= GetMessageJs('DISK_FW_CONVERT_HELP_1'); ?></b></div><div><?= GetMessageJs('DISK_FW_CONVERT_HELP_2'); ?></div><?= CUtil::JSEscape($listNonSupportedFeatures) ?><br/><p	style="margin-top: -4px;"><?= GetMessageJs('DISK_FW_CONVERT_HELP_8'); ?></p><div style="margin-bottom: 7px; margin-top: 13px;"><b><?= GetMessageJs('DISK_FW_CONVERT_HELP_9'); ?></b><span		id="bx-webdav-disk-help-btn" onclick="ShowNotice();" class="bx-webdav-disk-help-btn" style="margin-right: 0;">&nbsp;</span></div><div id="bx-webdav-disk-need-choice">	<div style="margin-left: 10px; margin-bottom: 5px;"><input onclick="RemoveBorder();" type="radio" value="P"	                                                           id="make_public" name="make_public"/><label			for="make_public" style="margin-left: 4px;;"><?= GetMessageJs('DISK_FW_CONVERT_HELP_10'); ?></label></div>	<div style="margin-left: 10px;"><input onclick="RemoveBorder();" type="radio" value="UP" id="make_unpublic"	                                       name="make_public"/><label for="make_unpublic"	                                                                  style="margin-left: 4px;;"><?= GetMessageJs('DISK_FW_CONVERT_HELP_11'); ?>			<br/><span				style="margin-left: 25px;color: gray;"><?= GetMessageJs('DISK_FW_CONVERT_HELP_12'); ?></span></label>	</div></div><div class="adm-info-message"><?= GetMessageJs('DISK_FW_CONVERT_HELP_13'); ?></div>'
		});

		dialog.SetSize({width: 700, height: 430});
		dialog.Show();

	}

	function RemoveBorder()
	{
		BX('bx-webdav-disk-need-choice').style.border = '';
	}

	function ShowNotice()
	{
		if(wd_dialog)
		{
			wd_dialog.close();
		}
		wd_dialog = new BX.PopupWindow('bx-webdav-disk-notice', BX('bx-webdav-disk-help-btn'), {
			closeByEsc: true,
			autoHide: true,
			buttons: [],
			zIndex: 10000,
			angle: {
				position: 'top',
				offset: 20
			},
			events: {
				onPopupClose: function(){
					this.destroy();
				}
			},
			content: '<div style="width: 400px;"><?= GetMessageJS('DISK_FW_CONVERT_POPUP_NOTICE') ?></div>'
		});
		wd_dialog.show();
	}

	function StartConvert()
	{
		wd_stop = false;
		document.getElementById('convert_result_div').innerHTML = '';
		document.getElementById('start_button').disabled        = true;
		DoNext(0, {publishDocs: BX('make_public', false).checked});
		BX.remove(BX('tabControl_layout'));
	}

	function StopConvert()
	{
		wd_stop = true;
		document.getElementById('start_button').disabled = false;
	}

	function EndConvert()
	{
		wd_stop = true;
		document.getElementById('start_button').disabled = true;
		BX.remove(BX('tabControl_layout'));
	}

	function DoNext(processedSummary, options)
	{
		options = options || {};
		var queryString = 'webdav_process=Y&lang=<?php echo htmlspecialcharsbx(LANG); ?>';

		queryString += '&<?php echo bitrix_sessid_get(); ?>';
		queryString += '&processedSummary=' + parseInt(processedSummary);

		if(!!options.publishDocs)
		{
			queryString += '&publishDocs=1';
		}

		if ( ! wd_stop )
		{
			ShowWaitWindow();
			BX.ajax.post(
				'disk_from_webdav_convertor.php?' + queryString,
				{},
				function(result)
				{
					document.getElementById('convert_result_div').innerHTML = result;
					if (BX('wd_convert_finish') != null)
					{
						CloseWaitWindow();
						EndConvert();
					}
				}
			);
		}

		return false;
	}
	</script>

	<div id='convert_result_div'>
	</div>

	<form method='POST' action='<?php
		echo $APPLICATION->GetCurPage(); ?>?lang=<?php
		echo htmlspecialcharsbx(LANG);
	?>'>
		<?php
		$tabControl->Begin();
		$tabControl->BeginNextTab();
		$tabControl->Buttons();
		?>

		<input type='button' id='start_button'
			value='<?php echo GetMessage('DISK_FW_CONVERT_START_BUTTON')?>'
			onclick='ShowConvert();');>
		<?php
		$tabControl->End();
		?>
	</form>

	<?php
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
}