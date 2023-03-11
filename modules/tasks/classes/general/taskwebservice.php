<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * Sync with Outlook handled here
 */


use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Tasks\Integration\Recyclebin\Manager;

IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('webservice'))
	return;

if (!CModule::IncludeModule('intranet'))
	return;

class CTasksWebService extends IWebService
{

	var $arPriorities = array(
		CTasks::PRIORITY_LOW     => 3,
		CTasks::PRIORITY_AVERAGE => 2,
		CTasks::PRIORITY_HIGH    => 1
	);
	var $arStatuses = array(
		1 => 2,
		2 => 3,
		3 => 5,
		4 => 6,
		5 => 5
	);
	var $arNotChoiceStatuses = array(1, 4, 7);


	/* private section */

	function __getFieldsDefinition()
	{
		$obFields = new CXMLCreator('Fields');

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ID" ColName="tp_ID" RowOrdinal="0" ReadOnly="TRUE" Type="Counter" Name="ID" PrimaryKey="TRUE" DisplayName="ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ID" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPEID" ColName="tp_ContentTypeId" Sealed="TRUE" Hidden="TRUE" RowOrdinal="0" ReadOnly="TRUE" Type="ContentTypeId" Name="ContentTypeId" DisplaceOnUpgrade="TRUE" DisplayName="Content Type ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentTypeId" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ATTACHMENTS" ColName="tp_HasAttachment" RowOrdinal="0" Type="Attachments" Name="Attachments" DisplayName="Attachments" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Attachments" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="TITLE" Type="Text" Name="Title" DisplayName="Title" Required="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Title" FromBaseType="TRUE" ColName="nvarchar1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="MODIFIED" ColName="tp_Modified" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Modified" DisplayName="Modified" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Modified" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CREATED" ColName="tp_Created" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Created" DisplayName="Created" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Created" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="AUTHOR" ColName="tp_Author" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Author" DisplayName="Created By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Author" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EDITOR" ColName="tp_Editor" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Editor" DisplayName="Modified By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Editor" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="OWSHIDDENVERSION" ColName="tp_Version" RowOrdinal="0" Hidden="TRUE" ReadOnly="TRUE" Type="Integer" SetAs="owshiddenversion" Name="owshiddenversion" DisplayName="owshiddenversion" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="owshiddenversion" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FSOBJTYPE" Name="FSObjType" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Item Type" List="Docs" FieldRef="ID" ShowField="FSType" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FSObjType" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PERMMASK" Name="PermMask" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" RenderXMLUsingPattern="TRUE" ShowInFileDlg="FALSE" Type="Computed" DisplayName="Effective Permissions Mask" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="PermMask" FromBaseType="TRUE"'));
		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="ID"'));

		$obField->addChild($obDisplayPattern = new CXMLCreator('DisplayPattern'));
		$obDisplayPattern->addChild(new CXMLCreator('CurrentRights'));


		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="UNIQUEID" Name="UniqueId" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Unique Id" List="Docs" FieldRef="ID" ShowField="UniqueId" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="UniqueId" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="METAINFO" Name="MetaInfo" DisplaceOnUpgrade="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Property Bag" List="Docs" FieldRef="ID" ShowField="MetaInfo" JoinColName="DoclibRowId" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="MetaInfo" FromBaseType="TRUE"'));

		/*		 * **************** */

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PRIORITY" Type="Choice" Name="Priority" DisplayName="Priority" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Priority" ColName="nvarchar3"'));

		$obField->addChild($obChoices = new CXMLCreator('CHOICES'));
		$obField->addChild($obMappings = new CXMLCreator('MAPPINGS'));

		foreach ($this->arPriorities as $key => $value)
		{
			$obChoices->addChild(CXMLCreator::createTagAttributed('CHOICE', $key));
			$obMappings->addChild(CXMLCreator::createTagAttributed('MAPPING Value="'.$value.'"', $key));
		}

		$obField->addChild(CXMLCreator::createTagAttributed('Default', 2));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="STATUS" Type="Choice" Name="Status" DisplayName="Task Status" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Status" ColName="nvarchar4"'));

		$obField->addChild($obChoices = new CXMLCreator('CHOICES'));
		$obField->addChild($obMappings = new CXMLCreator('MAPPINGS'));

		foreach ($this->arStatuses as $key => $value)
		{
			$obChoices->addChild(CXMLCreator::createTagAttributed('CHOICE', $value));
			$obMappings->addChild(CXMLCreator::createTagAttributed('MAPPING Value="'.$key.'"', $value));
		}

		$obField->addChild(CXMLCreator::createTagAttributed('Default', 1));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ASSIGNEDTO" Type="User" List="UserInfo" Name="AssignedTo" DisplayName="Assigned To" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="AssignedTo" ColName="int1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DESCRIPTION" Type="Note" RichText="TRUE" Name="Body" DisplayName="Description" Sortable="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Body" ColName="ntext2"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="STARTDATE" Type="DateTime" Name="StartDate" DisplayName="Start Date" Format="DateOnly" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="StartDate" ColName="datetime1"'));

		$obField->addChild(CXMLCreator::createTagAttributed('Default', '[today]'));
		$obField->addChild(CXMLCreator::createTagAttributed('DefaultFormulaValue', $this->__makeDateTime(strtotime(date('Y-m-d')))));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field Type="DateTime" ID="DUEDATE" Name="DueDate" DisplayName="Due Date" Format="DateOnly" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="DueDate" ColName="datetime2"'));

		return $obFields;
	}


	function __makeDateTime($ts = null, $stripTime = false)
	{
		if (null === $ts)
			$ts = time();

		if ($stripTime)
			$rc = date('Y-m-d', $ts) . 'T00:00:00Z';
		else
			$rc = date('Y-m-d', $ts) .'T' . date('H:i:s', $ts) . 'Z';

		return ($rc);
	}


	function __makeTS($datetime = null)
	{
		if (null === $datetime)
			return time();

		if (intval(mb_substr($datetime, 0, 4)) >= 2037)
			$datetime = '2037'.mb_substr($datetime, 4);

		return MakeTimeStamp(mb_substr($datetime, 0, 10).' '.mb_substr($datetime, 11, -1), 'YYYY-MM-DD HH:MI:SS');
	}


	function __makeUser($USER_ID)
	{
		if ($USER_ID > 0)
		{
			if (!$this->__arUserCache[$USER_ID])
			{
				$obUser = CUser::GetByID($USER_ID);
				if ($arUser = $obUser->Fetch())
				{
					$this->__arUserCache[$USER_ID] =
							$arUser['ID'].';'
							.'#'.($arUser['NAME'] || $arUser['LAST_NAME'] ? $arUser['NAME'].($arUser['NAME'] && $arUser['LAST_NAME'] ? ' ' : '').$arUser['LAST_NAME'] : $arUser['LOGIN']).','
							.'#'.$arUser['LOGIN'].','
							.'#'.$arUser['EMAIL'].','
							.'#,'
							.'#'.($arUser['NAME'] || $arUser['LAST_NAME'] ? $arUser['NAME'].($arUser['NAME'] && $arUser['LAST_NAME'] ? ' ' : '').$arUser['LAST_NAME'] : $arUser['LOGIN']);
				}
				else
				{
					$this->__arUserCache[$USER_ID] = '';
				}
			}
		}

		return $this->__arUserCache[$USER_ID];
	}


	function __getRow($arRes, $listName, &$last_change)
	{
		static $tzOffset = null;

		if ($tzOffset === null)
			$tzOffset = CTasksTools::getTimeZoneOffset();

		// Make correct unix timestamp
		$change = MakeTimeStamp($arRes['CHANGED_DATE']) - $tzOffset;

		if ($last_change < $change)
			$last_change = $change;

		$obRow = new CXMLCreator('z:row');
		$obRow->setAttribute('ows_ID', $arRes['ID']);

		$version = $arRes['OUTLOOK_VERSION'] ? $arRes['OUTLOOK_VERSION'] : 1;

		if (is_array($arRes['FILES']) && count($arRes['FILES']) > 0)
			$obRow->setAttribute('ows_Attachments', 1);
		else
			$obRow->setAttribute('ows_Attachments', 0);

		$obRow->setAttribute('ows_owshiddenversion', $version);
		$obRow->setAttribute('ows_MetaInfo_vti_versionhistory', md5($arRes['ID']).':'.$version);

		$obRow->setAttribute('ows_Created', $this->__makeDateTime(MakeTimeStamp($arRes['CREATED_DATE']) - $tzOffset));
		$obRow->setAttribute('ows_Modified', $this->__makeDateTime($change));

		$obRow->setAttribute('ows_Title', $arRes['TITLE']);

		if ($arRes['DESCRIPTION_IN_BBCODE'] === 'Y')
		{
			$parser = new CTextParser();

			$obRow->setAttribute(
				'ows_Body',
				str_replace(
					"\t",
					' &nbsp; &nbsp;',
					$parser->convertText($arRes['DESCRIPTION'])
				)
			);
		}
		else
			$obRow->setAttribute('ows_Body', $arRes['DESCRIPTION']);

		if ($arRes['START_DATE_PLAN'])
			$obRow->setAttribute('ows_StartDate', $this->__makeDateTime(MakeTimeStamp($arRes['START_DATE_PLAN']), true));

		if ($arRes['DEADLINE'])
			$obRow->setAttribute('ows_DueDate', $this->__makeDateTime(MakeTimeStamp($arRes['DEADLINE']), true));

		if ($arRes['RESPONSIBLE_ID'] > 0)
			$obRow->setAttribute('ows_AssignedTo', $this->__makeUser($arRes['RESPONSIBLE_ID']));

		$obRow->setAttribute('ows_Editor', $this->__makeUser($arRes['CHANGED_BY'] ? $arRes['CHANGED_BY'] : $arRes['CREATED_BY']));

		$obRow->setAttribute('ows_Priority', $arRes["PRIORITY"]);

		$obRow->setAttribute('ows_Status', in_array($arRes["REAL_STATUS"], $this->arNotChoiceStatuses) ? GetMessage("TASKS_STATUS_".$arRes["REAL_STATUS"]) : $arRes["REAL_STATUS"]);

		$obRow->setAttribute('ows_MetaInfo_DateComplete', $this->__makeDateTime(MakeTimeStamp($arRes['CLOSED_DATE'])));

		$obRow->setAttribute('ows_MetaInfo_TotalWork', $arRes['DURATION_PLAN'] * 60);
		$obRow->setAttribute('ows_MetaInfo_ActualWork', $arRes['DURATION_FACT'] * 60);

		$obRow->setAttribute('ows_UniqueId', $arRes['ID'].';#'.$listName);
		$obRow->setAttribute('ows_FSObjType', $arRes['ID'].';#0');

		$obRow->setAttribute('ows_PermMask', '0x7fffffffffffffff');
		$obRow->setAttribute('ows_ContentTypeId', '0x0108001E749911F9D25F4D90C446E16EEB2C0E');

		return $obRow;
	}


	function __getUser($user_str)
	{
		$USER_ID = 0;

		// examples of $user_str:
		// 468;#Andrey Nikitin,#a.nikitin,#a.nikitin@example.com,#,#Andrey Nikitin
		// 468;#a.nikitin@example.com,Andrey Nikitin

		list($USER_ID, $FIELDS) = explode(';', $user_str);

		if ($USER_ID <= 0)
		{
			$arFilters = array();

			$arUserFields = explode(',', $FIELDS);

			foreach ($arUserFields as $probablyEmail)
			{
				if (mb_strpos($probablyEmail, '@') === false)
					continue;

				$probablyEmail = str_replace('#', '', $probablyEmail);

				$arFilters[] = array('EMAIL' => $probablyEmail);
				$arFilters[] = array('LOGIN' => $probablyEmail);

				$probablyLogin = mb_substr($probablyEmail, 0, mb_strpos($probablyEmail, '@'));
				$arFilters[] = array('LOGIN' => $probablyLogin);

				break;
			}

			foreach ($arFilters as $arFilter)
			{
				$dbRes = CUser::GetList('id', 'asc', $arFilter);
				if ($arUser = $dbRes->Fetch())
				{
					$USER_ID = $arUser['ID'];
					break;
				}
			}
		}

		return $USER_ID;
	}


	/* badly kommunized from socnet */

	function __InTaskInitPerms($taskType, $ownerId)
	{
		$arResult = array(
			"view" => false,
			"view_all" => false,
			"create_tasks" => false,
			"edit_tasks" => false,
			"delete_tasks" => false,
			"modify_folders" => false,
			"modify_common_views" => false,
		);

		$taskType = mb_strtolower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = intval($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}
		$ownerId = intval($ownerId);
		if ($ownerId <= 0)
			return $arResult;

		// added by me
		if ($taskType == 'group')
			CModule::IncludeModule('socialnetwork');

		foreach ($arResult as $key => $val)
		{
			if ($GLOBALS["USER"]->IsAdmin())
				$arResult[$key] = true;
			else
				$arResult[$key] = CSocNetFeaturesPerms::CanPerformOperation(
								$GLOBALS["USER"]->GetID(), (($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP), $ownerId, "tasks", $key
				);
		}

		return $arResult;
	}


	function __InTaskCheckActiveFeature($taskType, $ownerId)
	{
		$taskType = mb_strtolower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = intval($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}

		$ownerId = intval($ownerId);
		if ($ownerId <= 0)
			return false;

		// added by me
		if ($taskType == 'group')
			CModule::IncludeModule('socialnetwork');

		return CSocNetFeatures::IsActiveFeature((($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP), $ownerId, "tasks");
	}


	function __Init()
	{
		if (!$this->bInited)
		{
			$this->bInited = true;
			$this->error = null;
		}

		return (null === $this->error);
	}


	/* public section */

	function GetList($listName)
	{
		if (!$this->__Init())
			return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault(
					'Data error',
					'Wrong GUID - '.$listName
			);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$data = new CXMLCreator('List');
		$data->setAttribute('ID', $listName);
		$data->setAttribute('Name', $listName);

		$data->setAttribute('Direction', 'none'); // RTL, LTR

		$data->setAttribute('ReadSecurity', '2');
		$data->setAttribute('WriteSecurity', '2');

		$data->setAttribute('EnableAttachments', 'TRUE');

		$data->setAttribute('Author', $this->__makeUser(\Bitrix\Tasks\Util\User::getId()));

		$data->addChild($this->__getFieldsDefinition());

		$data->addChild($obNode = new CXMLCreator('RegionalSettings'));

		$obNode->addChild(CXMLCreator::createTagAttributed('Language', '1049'));
		$obNode->addChild(CXMLCreator::createTagAttributed('Locale', '1049'));
		$obNode->addChild(CXMLCreator::createTagAttributed('SortOrder', '1026'));
		$obNode->addChild(CXMLCreator::createTagAttributed('TimeZone', \CIntranetUtils::getOutlookTimeZone()));
		$obNode->addChild(CXMLCreator::createTagAttributed('AdvanceHijri', '0'));
		$obNode->addChild(CXMLCreator::createTagAttributed('CalendarType', '1'));
		$obNode->addChild(CXMLCreator::createTagAttributed('Time24', 'True'));
		$obNode->addChild(CXMLCreator::createTagAttributed('Presence', 'True'));

		$data->addChild($obNode = new CXMLCreator('ServerSettings'));

		$obNode->addChild(CXMLCreator::createTagAttributed('ServerVersion', '12.0.0.0'));
		$obNode->addChild(CXMLCreator::createTagAttributed('RecycleBinEnabled', 'False'));
		$obNode->addChild(CXMLCreator::createTagAttributed('ServerRelativeUrl', '/company/personal/'));

		return array('GetListResult' => $data);
	}


	function GetAttachmentCollection($listName, $listItemID)
	{
		if (!$this->__Init())
			return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$taskId = (int) $listItemID;

		$data = '<Attachments>';

		$rsTask = CTasks::GetList(array(), array("ID" => $taskId), array("ID"));
		// We got this task only if user has rights on it.
		if ($arTask = $rsTask->Fetch())
		{
			$dbRes = CTaskFiles::GetList(array(), array("TASK_ID" => $taskId));

			while ($taskFile = $dbRes->Fetch())
			{
				if ($path = CFile::GetPath($taskFile["FILE_ID"]))
				{
					$data .= '<Attachment>'
						. tasksServerName() . '/tasks/getfile/' . (int) $taskId
						. '/' . (int) $taskFile['FILE_ID']
						. '/' . urlencode(ToLower(basename($path)))
					. '</Attachment>';
				}
			}
		}

		$data .= '</Attachments>';

		return array('GetAttachmentCollectionResult' => $data);
	}


	function AddAttachment($listName, $listItemID, $fileName, $attachment)
	{
		if (!$this->__Init())
			return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		if ($attachment == '')
		{
			return new CSoapFault('Wrong attachment', 'Wrong attachment');
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$listItemID = intval($listItemID);

		$dbRes = CTasks::GetByID($listItemID);
		if ($task = $dbRes->Fetch())
		{
			$fileName = str_replace(array('/', '\\', '..'), '', $fileName); // minor security

			foreach ($task["FILES"] as $fileID)
			{
				$FILE = ToLower(basename(CFile::GetPath($fileID)));
				if ($FILE == ToLower($fileName))
				{
					Header('HTTP/1.1 500 Internal Server Error');

					$obDetail = new CXMLCreator('detail');
					$obDetail->addChild(CXMLCreator::createTagAttributed('errorstring xmlns="http://schemas.microsoft.com/sharepoint/soap/"', 'The specified name is already in use.'));
					$obDetail->addChild(CXMLCreator::createTagAttributed('errorcode xmlns="http://schemas.microsoft.com/sharepoint/soap/"', '0x81020067'));

					return new CSoapFault(
							'soap::Server',
							'Exception of type \'Microsoft.SharePoint.SoapServer.SoapServerException\' was thrown.',
							$obDetail
					);
				}
			}

			$tmpFileName = CTempFile::GetFileName('sheet_happens');

			RewriteFile($tmpFileName, $attachment);

			$arFile = CFile::MakeFileArray($tmpFileName);
			$arFile['name'] = basename($fileName);
			$arFile['MODULE_ID'] = 'tasks';
			$arValue = array($FILE_ID = CFile::SaveFile($arFile, 'outlook/Lists/'.$listItemID));

			CTasks::AddFiles($task["ID"], $arValue);

			return array(
				'AddAttachmentResult' => '/tasks/getfile/' . (int) $listItemID
					. '/' . (int) $FILE_ID
					. '/' . urlencode(ToLower(basename(CFile::GetPath($FILE_ID))))
			);
		}
		else
		{
			return new CSoapFault('0x81020016', 'List item not found');
		}
	}


	function DeleteAttachment($listName, $listItemID, $url)
	{
		if (!$this->__Init())
			return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listItemID = intval($listItemID);

		$pos = mb_strrpos($url, '/');
		if ($pos)
			$fileName = ToLower(str_replace(array('/', '\\', '..'), '', mb_substr($url, $pos + 1))); // minor security

		if (!$fileName)
			return new CSoapFault('Wrong file', 'Wrong file URL');

		$dbRes = CTaskFiles::GetList(array(), array("TASK_ID" => $listItemID));

		while ($taskFile = $dbRes->Fetch())
		{
			$FILE_NAME = ToLower(basename(CFile::GetPath($taskFile["FILE_ID"])));

			if ($FILE_NAME == $fileName)
			{
				$rsTask = CTasks::GetList(array(), array("ID" => $listItemID), array("ID"));
				if ($arTask = $rsTask->Fetch())
				{
					if (CTasks::CanCurrentUserEdit($arTask["ID"]))
					{
						// We got this task only if user has rights on edit it.
						CTaskFiles::Delete($taskFile["TASK_ID"], $taskFile["FILE_ID"]);
					}
				}

				break;
			}
		}

		return array('DeleteAttachmentResult' => '');
	}


	function GetListItemChanges($listName, $viewFields = '', $since = '', $contains = '')
	{
		if (!$this->__Init())
			return $this->error;

		define('OLD_OUTLOOK_VERSION', true);

		$res = $this->GetListItemChangesSinceToken($listName, $viewFields, '', 0, $since ? $this->__makeTS($since) : '');

		if (is_object($res))
			return $res;
		else
			return array('GetListItemChangesResult' => $res['GetListItemChangesSinceTokenResult']);
	}


	function GetListItemChangesSinceToken($listName, $viewFields = '', $query = '', $rowLimit = 0, $changeToken = '')
	{
		if (!$this->__Init())
			return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$arFilter = array();

		if ($changeToken)
		{
			$bitrixTimestamp = $changeToken + CTasksTools::getTimeZoneOffset();
			$arFilter['>CHANGED_DATE'] = ConvertTimeStamp($bitrixTimestamp, 'FULL');
			if (!$arFilter['>CHANGED_DATE'])
			{
				return new CSoapFault(
						'Params error',
						'Wrong changeToken: '.$changeToken
				);
			}
		}

		$last_change = 0;
		$data = new CXMLCreator('listitems');
		$data->setAttribute('MinTimeBetweenSyncs', 0);
		$data->setAttribute('RecommendedTimeBetweenSyncs', 180);
		$data->setAttribute('TimeStamp', $this->__makeDateTime());
		$data->setAttribute('EffectivePermMask', 'FullMask');
		$data->setAttribute('IncludeAttachmentUrls', 'False');

		$data->addChild($obChanges = new CXMLCreator('Changes'));

		if (!$changeToken)
		{
			$obChanges->addChild($this->__getFieldsDefinition());
		}

		$data->addChild($obData = new CXMLCreator('rs:data'));

		$counter = 0;

		$arFilter['MEMBER'] = \Bitrix\Tasks\Util\User::getId();

		$dbRes = CTasks::GetList(array("ID" => "ASC"), $arFilter, array());

		while ($arRes = $dbRes->Fetch())
		{
			$rsFiles = CTaskFiles::GetList(array(), array("TASK_ID" => $arRes["ID"]));
			$arRes["FILES"] = array();

			while ($arFiles = $rsFiles->fetch())
				$arRes["FILES"][] = $arFiles["FILE_ID"];

			$arRes['TITLE'] = \Bitrix\Main\Text\Emoji::decode($arRes['TITLE']);

			$obData->addChild($this->__getRow($arRes, $listName, $last_change));
			$counter++;
		}

		$deletedIds = $this->getDeletedTasks($arFilter);
		foreach ($deletedIds as $deletedId)
		{
			$obId = new CXMLCreator('Id');
			$obId->setAttribute('ChangeType', 'Delete');
			$obId->setData($deletedId);
			$obChanges->addChild($obId);
		}

		$obData->setAttribute('ItemCount', $counter);

		$data->setAttribute('xmlns:rs', 'urn:schemas-microsoft-com:rowset');
		$data->setAttribute('xmlns:z', '#RowsetSchema');

		if ($last_change > 0)
			$obChanges->setAttribute('LastChangeToken', $last_change);

		return array('GetListItemChangesSinceTokenResult' => $data);
	}

	/**
	 * @param array $arFilter
	 * @return array
	 */
	private function getDeletedTasks(array $arFilter): array
	{
		if (!\Bitrix\Main\Loader::includeModule('recyclebin'))
		{
			return [];
		}

		$query = RecyclebinTable::query()
			->setSelect([
				'TASK_ID' => 'ENTITY_ID',
				'DATA' => 'RD.DATA'
			])
			->registerRuntimeField(
				'RD',
				new \Bitrix\Main\Entity\ReferenceField(
					'RD',
					RecyclebinDataTable::getEntity(),
					Join::on('this.ID', 'ref.RECYCLEBIN_ID')->where('ref.ACTION', 'TASK'),
					['join_type' => 'inner']
				)
			)
			->where('ENTITY_TYPE', '=', Manager::TASKS_RECYCLEBIN_ENTITY);

		if (array_key_exists('>CHANGED_DATE', $arFilter))
		{
			$format = \CAllSite::GetDateFormat('FULL');
			$format = \Bitrix\Main\Type\Date::convertFormatToPhp($format);
			$query->where('TIMESTAMP', '>', new \Bitrix\Main\Type\DateTime($arFilter['>CHANGED_DATE'], $format));
		}

		$res = $query->exec();

		$ids = [];
		while ($row = $res->fetch())
		{
			$taskData = unserialize($row['DATA'], ['allowed_classes' => false]);
			if ($taskData['RESPONSIBLE_ID'] == $arFilter['MEMBER'])
			{
				$ids[] = $row['TASK_ID'];
			}
		}

		return $ids;
	}

	function UpdateListItems($listName, $updates)
	{
		$arPaths = array(
			'user' =>
			COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/'),
			'group' =>
			COption::GetOptionString('intranet', 'path_task_group_entry', '/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/'),
		);

		if (!$this->__Init())
			return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$obResponse = new CXMLCreator('Results');

		$obBatch = $updates->children[0];
		$atrONERROR = $obBatch->getAttribute('OnError');
		$atrDATEINUTC = $obBatch->getAttribute('DateInUtc');
		$atrPROPERTIES = $obBatch->getAttribute('Properties');

		$arChanges = $obBatch->children;

		$arResultIDs = array();
		$dateStart = ConvertTimeStamp(strtotime('-1 hour'), 'FULL');
		$arResponseRows = array();
		$arResponseRowsError = array();
		$arReplicationIDs = array();

		$userId = \Bitrix\Tasks\Util\User::getId();

		foreach ($arChanges as $obMethod)
		{
			$arData = array('_command' => $obMethod->getAttribute('Cmd'));

			$ID = false;
			$bUpdate = true;

			$arElement = false;
			$arSection = $this->arUsersSection;

			foreach ($obMethod->children as $obField)
			{
				$name = $obField->getAttribute('Name');
				if ($name == 'MetaInfo')
					$name .= '_'.$obField->getAttribute('Property');

				$arData[$name] = $obField->content;
			}

			$obResponseRow = new CXMLCreator('Result');
			$obResponseRow->setAttribute('ID', $obMethod->getAttribute('ID').','.$arData['_command']);
			$obResponseRow->setAttribute('List', $listName);

			$obResponseRow->addChild($obErrorCode = new CXMLCreator('ErrorCode'));

			if ($arData['ID'] > 0)
			{
				$rsElement = CTasks::GetById($arData['ID']);
				if ($rsElement && $arElement = $rsElement->Fetch())
				{
					if (!is_array($arElement))
					{
						$obErrorCode->setData('0x81020016');
						$bUpdate = false;
					}
					else
					{
						if ($arElement['taskType'] == "group")
						{
							$arGroupTmp = CSocNetGroup::GetByID($arElement['ownerId']);
							if ($arGroupTmp["CLOSED"] == "Y")
								if (COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
									return new CSoapFault('Cannot modify archive group task', 'Cannot modify archive group task');
						}

						$arElement['arParams'] = array(
							(intval($arElement['GROUP_ID']) > 0 ? 'PATH_TO_USER_TASKS_TASK' : 'PATH_TO_GROUP_TASKS_TASK') =>
							str_replace(
									array('#USER_ID#', '#GROUP_ID#', '#TASK_ID#'), array($userId, $arSection['XML_ID'], $arElement['ID']), $arPaths[$arElement['taskType']]
							),
						);
					}
				}
				else
				{
					$obErrorCode->setData('0x81020016');
					$bUpdate = false;
				}
			}

			if ($bUpdate)
			{
				if ($arData['_command'] == 'Delete' && $arElement["CREATED_BY"] == $userId)
				{
					$arError = false;

					if (!CTasks::Delete($arElement['ID']))
					{
						$obErrorCode->setData('0x81020014');
					}
					else
					{
						$obErrorCode->setData('0x00000000');
					}
				}
				elseif ($arData['_command'] == 'New' || $arData['_command'] == 'Update')
				{
					$arData['Body'] = trim($arData['Body']);
					$arData['Body'] = str_replace(array("&#10;", "&#13;", '&nbsp;'), "", $arData['Body']);
					$arData['Body'] = preg_replace("/<![^>]*>/", '', $arData['Body']);

					if (($pos = mb_strpos($arData['Body'], '<BODY>')) !== false)
						$arData['Body'] = mb_substr($arData['Body'], $pos + 6);
					echo $pos.' ';
					if (($pos = mb_strpos($arData['Body'], '</BODY>')) !== false)
						$arData['Body'] = mb_substr($arData['Body'], 0, $pos);
					echo $pos.' ';

					$TZBias = intval(date('Z'));

					$arData['StartDate'] = $arData['StartDate'] ? $this->__makeTS($arData['StartDate']) + $TZBias : '';
					$arData['DueDate'] = $arData['DueDate'] ? $this->__makeTS($arData['DueDate']) + $TZBias : '';

					$arData['MetaInfo_DateComplete'] = $arData['MetaInfo_DateComplete'] ? $this->__makeTS($arData['EndDate']) + $TZBias : '';

					$probablyHtmlInDescription = (mb_strpos($arData['Body'], '<') !== false)
						&& mb_strpos($arData['Body'], '>');

					$arFields = array(
						'DESCRIPTION_IN_BBCODE' => ($probablyHtmlInDescription ? 'N' : 'Y'),
						'CHANGED_BY' => $userId,
						'CHANGED_DATE' => \Bitrix\Tasks\UI::formatDateTime(time()),
						'SITE_ID' => SITE_ID,
						'TITLE' => html_entity_decode($arData['Title']),
						'START_DATE_PLAN' => $arData['StartDate'] ? ConvertTimeStamp($arData['StartDate']) : '',
						'DEADLINE' => $arData['DueDate'] ? ConvertTimeStamp($arData['DueDate']) : '',
						'DESCRIPTION' => $arData['Body'],
						'PRIORITY' => isset($arData['Priority']) ? intval($arData['Priority']) : 1,
						'DURATION_PLAN' => $arData['MetaInfo_TotalWork'] / 60,
						'DURATION_FACT' => $arData['MetaInfo_ActualWork'] / 60,
						'CLOSED_DATE' => $arData['MetaInfo_DateComplete'] ? ConvertTimeStamp($arData['MetaInfo_DateComplete']) : '',
					);

					if (in_array($arData['Status'], $this->arStatuses))
					{
						$arFields["STATUS"] = $arData['Status'];
					}

					if ($assigned_to = $arData['AssignedTo'])
					{
						if ($USER_ID = $this->__getUser($assigned_to))
						{
							$arFields['RESPONSIBLE_ID'] = $USER_ID;
						}
						else
						{
							$obErrorCode->setData('0x81020054');
							$bUpdate = false;
						}
					}
					else
						$arFields['RESPONSIBLE_ID'] = $userId;

					if ($bUpdate)
					{
						CTimeZone::Disable();

						$ID = 0;
						$obTask = new CTasks();
						$arError = false;
						if ($arData['_command'] == 'New')
						{
							if ($arFields["RESPONSIBLE_ID"] == $userId || CTasks::IsSubordinate($arFields["RESPONSIBLE_ID"], $userId))
							{
								$arFields["STATUS"] = 2;
							}
							else
							{
								$arFields["STATUS"] = 1;
							}

							$arFields['OUTLOOK_VERSION'] = 1;

							$arFields["CREATED_BY"] = $userId;
							$arFields["CREATED_DATE"] = \Bitrix\Tasks\UI::formatDateTime(time());

							if ($ID = $obTask->Add($arFields))
							{
								$arReplicationIDs[$ID] = $arData['MetaInfo_ReplicationID'];
								$obErrorCode->setData('0x00000000');

								\Bitrix\Tasks\Kanban\StagesTable::pinInStage($ID);
							}
						}
						else
						{
							if ($arElement["CREATED_BY"] == $userId || $arElement["RESPONSIBLE_ID"] == $userId)
							{
								if ($arElement["CREATED_BY"] != $userId)
								{
									unset($arFields["TITLE"], $arFields["START_DATE_PLAN"], $arFields["DESCRIPTION"], $arFields["PRIORITY"], $arFields["DURATION_PLAN"], $arFields["CLOSED_DATE"]);
									if ($arElement["ALLOW_CHANGE_DEADLINE"] != "Y")
									{
										unset($arFields["DEADLINE"]);
									}
									if ($arElement["TASK_CONTROL"] != "Y" && $arFields["STATUS"] == 5)
									{
										$arFields["STATUS"] = 4;
									}
								}
								elseif ($arElement["RESPONSIBLE_ID"] != $userId && ($arFields["STATUS"] == 6 || $arFields["STATUS"] == 3))
								{
									unset($arFields["STATUS"]);
								}

								$arFields['OUTLOOK_VERSION'] = $arData['owshiddenversion'];

								if (sizeof($arFields) > 0)
								{
									if ($obTask->Update($arData['ID'], $arFields))
									{
										$ID = $arData['ID'];
										$obErrorCode->setData('0x00000000');
									}
								}
							}
						}

						CTimeZone::Enable();

						if (is_array($obTask->GetErrors()) && count($obTask->GetErrors()) > 0)
						{
							$ID = 0;
							$obErrorCode->setData('0x81020014');
							$bUpdate = false;
						}
						else
						{
							$taskType = $arElement ? $arElement['taskType'] : 'user';
							$ownerId = $arElement ? $arElement['ownerId'] : $userId;
							$arParams = $arElement ? $arElement['arParams'] : array(
								'PATH_TO_USER_TASKS_TASK' => str_replace(
										array('#USER_ID#', '#GROUP_ID#', '#TASK_ID#'), array($userId, $arSection['XML_ID'], $ID), $arPaths['user']
								)
							);
						}
					}
				}
			}

			if ($ID > 0)
				$arResponseRows[$ID] = $obResponseRow;
			else
				$arResponseRowsError[] = $obResponseRow;
		}

		if (sizeof($arResponseRows) > 0)
		{
			$dbRes = CTasks::GetList(
							array('ID' => 'ASC'), array(
						'ID' => array_keys($arResponseRows),
						'MEMBER' => $userId
							)
			);

			while ($arRes = $dbRes->Fetch())
			{
				if ($arResponseRows[$arRes['ID']])
				{
					$rsFiles = CTaskFiles::GetList(array(), array("TASK_ID" => $arRes["ID"]));
					$arRes["FILES"] = array();
					while ($arFiles = $rsFiles->Fetch())
					{
						$arRes["FILES"][] = $arFiles["FILE_ID"];
					}
					$arRes['TITLE'] = \Bitrix\Main\Text\Emoji::decode($arRes['TITLE']);
					$last_change = 0;
					$obRow = $this->__getRow($arRes, $listName, $last_change);

					if ($arReplicationIDs[$arRes['ID']])
						$obRow->setAttribute('ows_MetaInfo_ReplicationID', $arReplicationIDs[$arRes['ID']]);

					$obRow->setAttribute('xmlns:z', "#RowsetSchema");

					$arResponseRows[$arRes['ID']]->addChild($obRow);
					$obResponse->addChild($arResponseRows[$arRes['ID']]);
				}
			}
		}

		foreach ($arResponseRowsError as $obChange)
		{
			$obResponse->addChild($obChange);
		}

		return array('UpdateListItemsResult' => $obResponse);
	}


	public static function GetWebServiceDesc()
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = "bitrix.webservice.tasks";
		$wsdesc->wsclassname = "CTasksWebService";
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();

		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();

		$wsdesc->classes = array(
			"CTasksWebService" => array(
				"GetList" => array(
					"type" => "public",
					"name" => "GetList",
					"input" => array(
						"listName" => array("varType" => "string"),
					),
					"output" => array(
						"GetListResult" => array("varType" => 'any'),
					),
					'httpauth' => 'Y'
				),
				'GetListItemChanges' => array(
					'type' => 'public',
					'name' => 'GetListItemChanges',
					'input' => array(
						"listName" => array("varType" => "string"),
						"viewFields" => array("varType" => "any", 'strict' => 'no'),
						'since' => array('varType' => 'string', 'strict' => 'no'),
					),
					'output' => array(
						'GetListItemChangesResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),
				'GetListItemChangesSinceToken' => array(
					'type' => 'public',
					'name' => 'GetListItemChangesSinceToken',
					'input' => array(
						"listName" => array("varType" => "string"),
						"viewFields" => array("varType" => "any", 'strict' => 'no'),
						'query' => array('varType' => 'any', 'strict' => 'no'),
						'rowLimit' => array('varType' => 'string', 'strict' => 'no'),
						'changeToken' => array('varType' => 'string', 'strict' => 'no'),
					),
					'output' => array(
						'GetListItemChangesSinceTokenResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),
				'GetAttachmentCollection' => array(
					'type' => 'public',
					'name' => 'GetAttachmentCollection',
					'input' => array(
						"listName" => array("varType" => "string"),
						"listItemID" => array("varType" => "string"),
					),
					'output' => array(
						'GetAttachmentCollectionResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),
				'DeleteAttachment' => array(
					'type' => 'public',
					'name' => 'DeleteAttachment',
					'input' => array(
						"listName" => array("varType" => "string"),
						"listItemID" => array("varType" => "string"),
						"url" => array("varType" => "string"),
					),
					'output' => array(
						'DeleteAttachmentResult' => array('varType' => 'string'),
					),
					'httpauth' => 'Y'
				),
				'AddAttachment' => array(
					'type' => 'public',
					'name' => 'AddAttachment',
					'input' => array(
						"listName" => array("varType" => "string"),
						"listItemID" => array("varType" => "string"),
						"fileName" => array("varType" => "string"),
						"attachment" => array("varType" => "base64Binary"),
					),
					'output' => array(
						'AddAttachmentResult' => array('varType' => 'string'),
					),
					'httpauth' => 'Y'
				),
				'UpdateListItems' => array(
					'type' => 'public',
					'name' => 'UpdateListItems',
					'input' => array(
						"listName" => array("varType" => "string"),
						'updates' => array('varType' => 'any', 'strict' => 'no'),
					),
					'output' => array(
						'UpdateListItemsResult' => array('varType' => 'any')
					),
					'httpauth' => 'Y'
				),
			),
		);
		return $wsdesc;
	}
}