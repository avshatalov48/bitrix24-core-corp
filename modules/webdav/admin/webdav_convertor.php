<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
/** @var CAllUser $USER */
/** @var CAllMain $APPLICATION */
global $USER, $APPLICATION;

if (!$USER->IsAdmin())
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

class CWebDavConvertor
{
	const STATUS_FINISH       = 2;
	const STATUS_TIME_EXPIRED = 3;
	const STATUS_ERROR        = 4;

	public static $countConvertElements = 0;
	public static $countConvertSections = 0;
	protected $currentIblockId;
	protected $timeStart = 0;
	/**
	 * Seconds
	 * @var int
	 */
	protected $maxExecution = 2;

	public function __construct()
	{
		$this->setTimeStart(time());
	}

	/**
	 * @param int $timeStart
	 */
	public function setTimeStart($timeStart)
	{
		$this->timeStart = $timeStart;
	}

	/**
	 * @return int
	 */
	public function getTimeStart()
	{
		return $this->timeStart;
	}

	protected function isTimeExpired()
	{
		return (time() - $this->getTimeStart()) > $this->maxExecution;
	}

	/**
	 * Check expired by time and throw exception
	 * @throws TimeExecutionException
	 */
	protected function abortIfNeeded()
	{
		if($this->isTimeExpired())
		{
			if($this->currentIblockId)
			{
				//"time expired" is correct finish if step. This is not error.
				$this->markStepClosed($this->currentIblockId);
			}
			throw new TimeExecutionException();
		}
	}

	protected function checkRequired()
	{
		if(!CModule::includeModule('iblock'))
		{
			throw new Exception('Bad include iblock');
		}
		if(!CModule::includeModule('webdav'))
		{
			throw new Exception('Bad include webdav');
		}
	}

	protected function getIblockIdsWithUserFiles()
	{
		$userLibOptions = COption::GetOptionString('webdav', 'user_files', array());
		if (CheckSerializedData($userLibOptions))
		{
			$userLibOptions = @unserialize($userLibOptions);
		}

		if (!is_array($userLibOptions))
		{
			$userLibOptions = array();
		}

		$userIblockIds = array();
		foreach ($userLibOptions as $siteOption)
		{
			if(isset($siteOption['id']) && ($siteOption['id'] = (int)$siteOption['id']))
			{
				$userIblockIds[] =  $siteOption['id'];
			}
		}
		unset($siteOption);

		return array_filter(array_unique($userIblockIds));
	}

	protected function getIblockIdsWithGroupFiles()
	{
		$groupLibOptions = COption::GetOptionString('webdav', 'group_files', array());
		if (CheckSerializedData($groupLibOptions))
		{
			$groupLibOptions = @unserialize($groupLibOptions);
		}

		if (!is_array($groupLibOptions))
		{
			$groupLibOptions = array();
		}

		$groupIblockIds = array();
		foreach ($groupLibOptions as $siteOption)
		{
			if(isset($siteOption['id']) && ($siteOption['id'] = (int)$siteOption['id']))
			{
				$groupIblockIds[] =  $siteOption['id'];
			}
		}
		unset($siteOption);

		return array_filter(array_unique($groupIblockIds));
	}

	protected function workWithGroupsSection($iblockId)
	{
		$sectionQuery = CIBlockSection::GetList(array(), array(
			"IBLOCK_ID" => $iblockId,
			"CHECK_PERMISSIONS" => "N",
			"SECTION_ID" => 0,
		), false, array('IBLOCK_CODE', 'IBLOCK_TYPE_ID', 'ID', 'NAME', 'CREATED_BY', 'SOCNET_GROUP_ID'));
		while ($sectionQuery && $groupSection = $sectionQuery->Fetch())
		{
			$this->abortIfNeeded();
			if(empty($groupSection['SOCNET_GROUP_ID']) || /*$groupSection['IBLOCK_CODE'] != 'group_files' || */  $groupSection['IBLOCK_TYPE_ID'] != 'library')
			{
				continue;
			}
			$droppedQuery = CIBlockSection::GetList(array(), array(
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => $groupSection['ID'],
				"CHECK_PERMISSIONS" => "N",
				"=NAME" => '.Dropped',
			), false, array('ID', 'IBLOCK_ID', 'NAME'));
			if(!$droppedQuery || !($droppedSection = $droppedQuery->Fetch()))
			{
				continue;
			}

			$this->abortIfNeeded();
			$downloadedSectionId = $this->getDownloadedSectionId($iblockId, $groupSection['ID'], array(
				'CREATED_BY' => $groupSection['CREATED_BY'],
				'MODIFIED_BY' => $groupSection['CREATED_BY'],
			));
			if(!$downloadedSectionId)
			{
				continue;
			}
			if($this->workWithDropped($droppedSection, $downloadedSectionId))
			{
				$this->abortIfNeeded();
				//0 files in section
				if(CIBlockSection::Delete($droppedSection['ID'], false))
				{
					static::$countConvertSections++;
				}
			}
		}
	}

	protected function workWithUsersSection($iblockId)
	{
		$sectionQuery = CIBlockSection::GetList(array(), array(
			"IBLOCK_ID" => $iblockId,
			"SOCNET_GROUP_ID" => false,
			"CHECK_PERMISSIONS" => "N",
			"SECTION_ID" => 0,
		), false, array('IBLOCK_CODE', 'IBLOCK_TYPE_ID', 'ID', 'NAME', 'CREATED_BY'));
		while ($sectionQuery && $userSection = $sectionQuery->Fetch())
		{
			$this->abortIfNeeded();
			if($userSection['IBLOCK_CODE'] != 'user_files' || $userSection['IBLOCK_TYPE_ID'] != 'library')
			{
				continue;
			}
			$droppedQuery = CIBlockSection::GetList(array(), array(
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => $userSection['ID'],
				"CHECK_PERMISSIONS" => "N",
				"=NAME" => '.Dropped',
			), false, array('ID', 'IBLOCK_ID', 'NAME'));
			if(!$droppedQuery || !($droppedSection = $droppedQuery->Fetch()))
			{
				continue;
			}

			$this->abortIfNeeded();
			$downloadedSectionId = $this->getDownloadedSectionId($iblockId, $userSection['ID'], array(
				'CREATED_BY' => $userSection['CREATED_BY'],
				'MODIFIED_BY' => $userSection['CREATED_BY'],
			));
			if(!$downloadedSectionId)
			{
				continue;
			}
			if($this->workWithDropped($droppedSection, $downloadedSectionId))
			{
				$this->abortIfNeeded();
				//0 files in section
				if(CIBlockSection::Delete($droppedSection['ID'], false))
				{
					static::$countConvertSections++;
				}
			}
		}
	}

	protected function getCountDroppedSection($iblockId)
	{
		return CIBlockSection::GetCount(array(
			"IBLOCK_ID" => $iblockId,
			"=NAME" => '.Dropped',
			'>DEPTH_LEVEL' => 0,
		));
	}

	public function getDownloadedSectionId($iblockId, $parentSectionId, array $additionalData = array())
	{
		$metaData = CWebDavIblock::getDroppedMetaData();
		$sectionId = CWebDavIblock::findMetaSection($metaData['name'], $iblockId, $parentSectionId);
		if(!$sectionId)
		{
			$sectionId = CWebDavIblock::createMetaSection($metaData['name'], $iblockId, $parentSectionId, $additionalData);
		}
		return $sectionId;
	}

	protected function workWithDropped(array $droppedSection, $downloadedSectionId)
	{
		$this->abortIfNeeded();
		$droppedElementQuery = CIBlockElement::GetList(array(), array(
			'CHECK_PERMISSIONS' => 'N',
			'INCLUDE_SUBSECTIONS' => 'Y',
			'SECTION_ID' => $droppedSection['ID'],
			'IBLOCK_ID' => $droppedSection['IBLOCK_ID'],
		));

		if(!$droppedElementQuery)
		{
			//empty spaces
			return true;
		}
		while($droppedElement = $droppedElementQuery->Fetch())
		{
			$this->abortIfNeeded();
			//prepare
			$this->uniqualizeElement($droppedElement, $downloadedSectionId);
			//move to new life
			CIBlockElement::SetElementSection($droppedElement['ID'], array($downloadedSectionId));
			static::$countConvertElements++;
		}
		//empty spaces
		return true;
	}

	protected function isUniqueName($name, $iblockId, $targetSectionId)
	{
		$query = CIBlockElement::GetList(array(), array(
			'CHECK_PERMISSIONS' => 'N',
			'SECTION_ID' => $targetSectionId,
			'IBLOCK_ID' => $iblockId,
			'=NAME' => $name,
		));
		if(!$query || !($matchElement = $query->Fetch()))
		{
			return true;
		}
		return false;
	}

	protected function uniqualizeElement(array &$droppedElement, $targetSectionId)
	{
		$mainPartName = $droppedElement['NAME'];
		$newName = $mainPartName;
		$countNonUnique = 0;
		while(!$this->isUniqueName($newName, $droppedElement['IBLOCK_ID'], $targetSectionId))
		{
			$this->abortIfNeeded();
			$countNonUnique++;
			$newName = strstr($mainPartName, '.', true) . " ({$countNonUnique})" . strstr($mainPartName, '.');
		}
		if($countNonUnique)
		{
			$droppedElement['NAME'] = $newName;
			$updateElement = new CIBlockElement();
			$updateElement->update($droppedElement['ID'], array('NAME' => $droppedElement['NAME']));
		}

		return;
	}

	public function run()
	{
		$this->checkRequired();
		if($this->getIsAlreadyConverted())
		{
			return self::STATUS_FINISH;
		}

		$iblockIdData = array(
			'users'  => $this->getIblockIdsWithUserFiles(),
			'groups' => $this->getIblockIdsWithGroupFiles(),
		);
		foreach ($iblockIdData as $category => $listId)
		{
			foreach ($listId as $iblockId)
			{
				$this->currentIblockId = $iblockId;
				if(!$this->getIsPreviousStepCorrect($iblockId))
				{
					$this->runResort($iblockId);
				}

				$this->markStepOpened($iblockId);
				if($category == 'users')
				{
					$this->workWithUsersSection($iblockId);
				}
				elseif($category == 'groups')
				{
					$this->workWithGroupsSection($iblockId);
				}
				else
				{
					continue;
				}
				$this->markStepClosed($iblockId);
			}
			unset($iblockId);
		}
		return self::STATUS_FINISH;
	}

	public function getTotalCountDroppedSection()
	{
		$count = 0;
		$this->checkRequired();
		$iblockIds = array_merge($this->getIblockIdsWithGroupFiles(), $this->getIblockIdsWithUserFiles());
		foreach ($iblockIds as $iblockId)
		{
			$count += $this->getCountDroppedSection($iblockId);
		}
		unset($iblockId);
		return $count;
	}

	protected function runResort($iblockId)
	{
		return CIBlockSection::treeReSort($iblockId);
	}

	public function getIsAlreadyConverted()
	{
		return COption::GetOptionString(
			'webdav',
			'~isAlreadyConvertedDropped',
			'N'
		) == 'Y';
	}

	public function setIsAlreadyConverted()
	{
		CAdminNotify::DeleteByTag('webdav_convertor_14_0_2');
		COption::SetOptionString(
			'webdav',
			'~isAlreadyConvertedDropped',
			'Y'
		);
		return $this;
	}

	/**
	 * If previous step on this iblock aborted, then return false
	 * @param $iblockId
	 * @return bool
	 */
	protected function getIsPreviousStepCorrect($iblockId)
	{
		return 	COption::GetOptionString(
			'webdav',
			'~stepConvertedDroppedStart' . $iblockId,
			'N'
		) == 'N';

	}

	protected function markStepOpened($iblockId)
	{
		COption::SetOptionString(
			'webdav',
			'~stepConvertedDroppedStart' . $iblockId,
			'Y'
		);

		return $this;
	}

	protected function markStepClosed($iblockId)
	{
		COption::RemoveOption(
			'webdav',
			'~stepConvertedDroppedStart' . $iblockId
		);
		return $this;
	}

}

class TimeExecutionException extends Exception
{}

IncludeModuleLangFile(__FILE__);

if (isset($_REQUEST['webdav_process']) && ($_REQUEST['webdav_process'] === 'Y'))
{
	CUtil::JSPostUnescape();

	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_js.php');

	$totalCount = 0;
	$processedSummary = 0;
	if (isset($_REQUEST['processedSummary']))
	{
		$processedSummary = (int) $_REQUEST['processedSummary'];
	}
	if (isset($_REQUEST['totalCount']))
	{
		$totalCount = (int) $_REQUEST['totalCount'];
	}

	$status = false;
	try
	{
		$convertor = new CWebDavConvertor();
		if(!$totalCount)
		{
			$totalCount = $convertor->getTotalCountDroppedSection();
		}
		$status = $convertor->run();
		$processedSummary += $convertor::$countConvertSections;
	}
	catch (TimeExecutionException $e)
	{
		$status = $convertor::STATUS_TIME_EXPIRED;
		$processedSummary += $convertor::$countConvertSections;
	}
	catch (Exception $e)
	{
		$status = $convertor::STATUS_ERROR;
		$processedSummary += $convertor::$countConvertSections;
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
				'MESSAGE' => GetMessage('WD_CONVERT_FAILED'),
				'DETAILS' => GetMessage('WD_PROCESSED_SUMMARY')
					. ' <b>' . $processedSummary . '</b>'
					. '<div id="wd_convert_finish"></div>',
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
		CAdminMessage::ShowMessage(array(
			"TYPE" => "PROGRESS",
			"HTML" => true,
			"MESSAGE" => GetMessage('WD_CONVERT_COMPLETE'),
			"DETAILS" => "#PROGRESS_BAR#",
			"PROGRESS_TOTAL" => 1,
			"PROGRESS_VALUE" => 1,
		));

		CAdminMessage::ShowMessage(
			array(
				'MESSAGE' => GetMessage('WD_CONVERT_COMPLETE'),
				'DETAILS' => GetMessage('WD_PROCESSED_SUMMARY')
					. ' <b>' . $processedSummary . '</b>'
					. '<div id="wd_convert_finish"></div>',
				'HTML'    => true,
				'TYPE'    => 'OK'
			)
		);

		$convertor->setIsAlreadyConverted();
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
			"MESSAGE" => GetMessage('WD_CONVERT_IN_PROGRESS'),
			"DETAILS" => "#PROGRESS_BAR#",
			"PROGRESS_TOTAL" => $totalCount,
			"PROGRESS_VALUE" => intval($processedSummary),
		));

		CAdminMessage::ShowMessage(
			array(
				'MESSAGE' => GetMessage('WD_CONVERT_IN_PROGRESS'),
				'DETAILS' => GetMessage('WD_PROCESSED_SUMMARY')
					. ' <b>' . $processedSummary . '</b>',
				'HTML'    => true,
				'TYPE'    => 'OK'
			)
		);

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
	$APPLICATION->SetTitle(GetMessage('WD_CONVERT_TITLE'));

	$aTabs = array(
		array(
			'DIV'   => 'edit1',
			'TAB'   => GetMessage('WD_CONVERT_TAB'),
			'ICON'  => 'main_user_edit',
			'TITLE' => GetMessage('WD_CONVERT_TAB_TITLE')
		)
	);

	$tabControl = new CAdminTabControl('tabControl', $aTabs, true, true);

	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

	?>
	<script language='JavaScript'>
	var wd_stop;

	function StartConvert(maxMessage)
	{
		wd_stop = false;
		document.getElementById('convert_result_div').innerHTML = '';
		document.getElementById('stop_button').disabled         = false;
		document.getElementById('start_button').disabled        = true;
		DoNext(0, 0, 100);
	}

	function StopConvert()
	{
		wd_stop = true;
		document.getElementById('stop_button').disabled  = true;
		document.getElementById('start_button').disabled = false;
	}

	function EndConvert()
	{
		wd_stop = true;
		document.getElementById('stop_button').disabled  = true;
		document.getElementById('start_button').disabled = true;
	}

	function DoNext(processedSummary)
	{
		var queryString = 'webdav_process=Y&lang=<?php echo htmlspecialcharsbx(LANG); ?>';

		queryString += '&<?php echo bitrix_sessid_get(); ?>';
		queryString += '&processedSummary=' + parseInt(processedSummary);

		if ( ! wd_stop )
		{
			ShowWaitWindow();
			BX.ajax.post(
				'webdav_convertor.php?' + queryString,
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
			value='<?php echo GetMessage('WD_CONVERT_START_BUTTON')?>'
			onclick='StartConvert();');>
		<input type='button' id='stop_button' disabled="disabled"
			value='<?php echo GetMessage('WD_CONVERT_STOP_BUTTON')?>'
			onclick='StopConvert();'>

		<?php
		$tabControl->End();
		?>
	</form>

	<script>
		//StartConvert();
	</script>

	<?php
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
}