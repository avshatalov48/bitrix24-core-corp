<?
if (!CModule::IncludeModule('webservice'))
	return;

class CIntranetTasksWS extends IWebService
{
	var $arStatusMappings = array(
		'NotStarted' => 1, 'InProgress' => 2, 'Completed' => 3, 'Deferred' => 4, 'Waiting' => 5, // "NotAccepted" is not here and it's not a bug
	);

	var $arStatusChoices = array(
		'NotStarted' => 'Not started yet', 'InProgress' => 'Already in progress', 'Completed' => 'Wow! It\'s completed!', 'Deferred' => 'Deferred... or maybe not', 'Waiting' => 'Waitin 4 smth'
	);

	var $arProperties = array();

	var $arPriorityMappings = array(
		1 => 1, 2 => 2, 3 => 3,
	);

	/* private section */

	function __getFieldsDefinition()
	{
		$obFields = new CXMLCreator('Fields');

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ID" ColName="tp_ID" RowOrdinal="0" ReadOnly="TRUE" Type="Counter" Name="ID" PrimaryKey="TRUE" DisplayName="ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ID" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPEID" ColName="tp_ContentTypeId" Sealed="TRUE" Hidden="TRUE" RowOrdinal="0" ReadOnly="TRUE" Type="ContentTypeId" Name="ContentTypeId" DisplaceOnUpgrade="TRUE" DisplayName="Content Type ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentTypeId" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ATTACHMENTS" ColName="tp_HasAttachment" RowOrdinal="0" Type="Attachments" Name="Attachments" DisplayName="Attachments" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Attachments" FromBaseType="TRUE"'));

		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPE" ColName="tp_ContentType" RowOrdinal="0" ReadOnly="TRUE" Type="Text" Name="ContentType" DisplaceOnUpgrade="TRUE" DisplayName="Content Type" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentType" FromBaseType="TRUE" PITarget="MicrosoftWindowsSharePointServices" PIAttribute="ContentTypeID"'));

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

		/*******************/

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PRIORITY" Type="Choice" Name="Priority" DisplayName="Priority" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Priority" ColName="nvarchar3"'));

		$obField->addChild($obChoices = new CXMLCreator('CHOICES'));
		$obField->addChild($obMappings = new CXMLCreator('MAPPINGS'));

		foreach ($this->arProperties['TaskPriority']['VARIANTS'] as $mapping => $value)
		{
			$obChoices->addChild(CXMLCreator::createTagAttributed('CHOICE', $value));
			$obMappings->addChild(CXMLCreator::createTagAttributed('MAPPING Value="'.$mapping.'"', $value));
		}

		$obField->addChild(CXMLCreator::createTagAttributed('Default', $this->arPriorityMappings[2]));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="STATUS" Type="Choice" Name="Status" DisplayName="Task Status" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Status" ColName="nvarchar4"'));

		$obField->addChild($obChoices = new CXMLCreator('CHOICES'));
		$obField->addChild($obMappings = new CXMLCreator('MAPPINGS'));

		// $arStatusValues = array();
		// $obStatus = CIBlockProperty::GetPropertyEnum('TaskStatus', array('SORT' => 'asc'), array('IBLOCK_ID' => $this->IBLOCK_ID, 'XML_ID' => 'NotAccepted'));
		// $arNAStatus = $obStatus->Fetch();

		/*
		$i = 1;
		while ($arStatus = $obStatus->Fetch())
		{
			//if ($arStatus['XML_ID'] == 'NotStarted')
			//	$obField->addChild(CXMLCreator::createTagAttributed('Default', $arStatus['VALUE']));

			$obChoices->addChild(CXMLCreator::createTagAttributed('CHOICE', '('.$arStatus['XML_ID'].') '.$arStatus['VALUE']));
			$obMappings->addChild(CXMLCreator::createTagAttributed('MAPPING Value="'.$arStatus['ID'].'"', $arStatus['VALUE']));
		}

		*/

		$i = 0;
		foreach ($this->arProperties['TaskStatus']['VARIANTS'] as $mapping => $value)
		{
			if ($i == 0) {$i = 1; continue;}
			if ($i == 1)
			{
				$obField->addChild(CXMLCreator::createTagAttributed('Default', $value));
			}

			$obChoices->addChild(CXMLCreator::createTagAttributed('CHOICE', $value));
			$obMappings->addChild(CXMLCreator::createTagAttributed('MAPPING Value="'.$i.'"', $value));

			$i++;
		}

		$obChoices->addChild(CXMLCreator::createTagAttributed('CHOICE', $arNAStatus['VALUE']));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PERCENTCOMPLETE" Type="Number" Name="PercentComplete" Percentage="TRUE" Min="0" Max="1" DisplayName="% Complete" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="PercentComplete" ColName="float1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ASSIGNEDTO" Type="User" List="UserInfo" Name="AssignedTo" DisplayName="Assigned To" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="AssignedTo" ColName="int1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DESCRIPTION" Type="Note" RichText="TRUE" Name="Body" DisplayName="Description" Sortable="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Body" ColName="ntext2"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="STARTDATE" Type="DateTime" Name="StartDate" DisplayName="Start Date" Format="DateOnly" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="StartDate" ColName="datetime1"'));

		$obField->addChild(CXMLCreator::createTagAttributed('Default', '[today]'));
		$obField->addChild(CXMLCreator::createTagAttributed('DefaultFormulaValue', $this->__makeDateTime(strtotime(date('Y-m-d')))));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field Type="DateTime" ID="DUEDATE" Name="DueDate" DisplayName="Due Date" Format="DateOnly" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="DueDate" ColName="datetime2"'));

		return $obFields;
	}

	function __makeDateTime($ts = null)
	{
		if (null === $ts)
			$ts = time();

		return date('Y-m-d', $ts).'T'.date('H:i:s', $ts).'Z';
	}

	function __makeTS($datetime = null)
	{
		if (null === $datetime)
			return time();

		if (intval(substr($datetime, 0, 4)) >= 2037)
			$datetime = '2037'.substr($datetime, 4);

		return MakeTimeStamp(substr($datetime, 0, 10).' '.substr($datetime, 11, -1), 'YYYY-MM-DD HH:MI:SS');
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
						.'#'.$arUser['NAME'].' '.$arUser['LAST_NAME'].','
						.'#'.$arUser['LOGIN'].','
						.'#'.$arUser['EMAIL'].','
						.'#,'
						.'#'.($arUser['FULL_NAME'] ? $arUser['FULL_NAME'] : $arUser['NAME'].' '.$arUser['LAST_NAME']);
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
		global $APPLICATION, $USER;

		//print_r($arRes);
		//die();


		$change = MakeTimeStamp($arRes['TIMESTAMP_X']);

		if ($last_change < $change)
			$last_change = $change;

		$obRow = new CXMLCreator('z:row');
		$obRow->setAttribute('ows_ID', $arRes['ID']);

		$ts_start = MakeTimeStamp($arRes['DATE_ACTIVE_FROM']);

		$version = $arRes['PROPERTY_VERSION'] ? $arRes['PROPERTY_VERSION'] : 1;

		if (is_array($arRes['PROPERTY_TaskFiles']) && count($arRes['PROPERTY_TaskFiles']) > 0)
			$obRow->setAttribute('ows_Attachments', 1);
		else
			$obRow->setAttribute('ows_Attachments', 0);

		$obRow->setAttribute('ows_owshiddenversion', $version);
		$obRow->setAttribute('ows_MetaInfo_vti_versionhistory', md5($arRes['ID']).':'.$version);

		$obRow->setAttribute('ows_Created', $this->__makeDateTime(MakeTimeStamp($arRes['DATE_CREATE'])));
		$obRow->setAttribute('ows_Modified', $this->__makeDateTime($change));

		$ows_Body = $arRes['DETAIL_TEXT'] ? $arRes['DETAIL_TEXT'] : $arRes['PREVIEW_TEXT'];
		$bHTML = $arRes['DETAIL_TEXT'] ? ($arRes['DETAIL_TEXT_TYPE'] == 'html') : ($arRes['PREVIEW_TEXT_TYPE'] == 'html');

		if (!$bHTML)
			$ows_Body = nl2br($ows_Body);

		$obRow->setAttribute('ows_Title', $arRes['NAME']);
		$obRow->setAttribute('ows_Body', $ows_Body);

		if ($arRes['DATE_ACTIVE_FROM'])
			$obRow->setAttribute('ows_StartDate', $this->__makeDateTime(MakeTimeStamp($arRes['DATE_ACTIVE_FROM'])));

		if ($arRes['DATE_ACTIVE_TO'])
			$obRow->setAttribute('ows_DueDate', $this->__makeDateTime(MakeTimeStamp($arRes['DATE_ACTIVE_TO'])));

		if ($arRes['PROPERTY_TaskAssignedTo'] > 0)
			$obRow->setAttribute('ows_AssignedTo', $this->__makeUser($arRes['PROPERTY_TaskAssignedTo']));

		$obRow->setAttribute('ows_Editor', $this->__makeUser($arRes['MODIFIED_BY'] ? $arRes['MODIFIED_BY'] : $arRes['CREATED_BY']));

		//$obRow->setAttribute('ows_Priority', $arRes['prop']['TaskPriority']['VALUE_XML_ID']);
		/////////////////////////// remake! //////////////////////////////////////
		$arPriority = array_values($arRes['PROPERTY_TaskPriority']);
		$obRow->setAttribute('ows_Priority', trim($arPriority[0]));

		/////////////////////////////// remake! //////////////////////////////////


		// if ($this->arStatusChoices[$arRes['DocumentState']['STATE_NAME']])
			// $obRow->setAttribute('ows_Status', $this->arProperties['TaskStatus']['VARIANTS'][$arRes['DocumentState']['STATE_NAME']]);
		// // elseif (isset($this->arStatusMappings[$arRes['DocumentState']['STATE_NAME']]))
			// // $obRow->setAttribute('ows_Status', $arRes['DocumentState']['STATE_NAME']);
		// else
			$obRow->setAttribute('ows_Status', $arRes['DocumentState']['STATE_TITLE']);

		$obRow->setAttribute('ows_PercentComplete', ($arRes['PROPERTY_TaskComplete']/100));

		$obRow->setAttribute('ows_MetaInfo_DateComplete', $this->__makeDateTime(MakeTimeStamp($arRes['PROPERTY_TaskFinish'])));

		$obRow->setAttribute('ows_MetaInfo_TotalWork', $arRes['PROPERTY_TaskSize'] * 60);
		$obRow->setAttribute('ows_MetaInfo_ActualWork', $arRes['PROPERTY_TaskSizeReal'] * 60);

		$obRow->setAttribute('ows_UniqueId', $arRes['ID'].';#'.$listName);
		$obRow->setAttribute('ows_FSObjType', $arRes['ID'].';#0');

		// TODO: maybe set here perms from [CurrentUserCanViewTask][CurrentUserCanCommentTask][CurrentUserCanDeleteTask][CurrentUserCanWriteTask]
		// see [MS-WSSFO] 2.2.2.13
		$obRow->setAttribute('ows_PermMask', '0x7fffffffffffffff');
		$obRow->setAttribute('ows_ContentTypeId', '0x0108001E749911F9D25F4D90C446E16EEB2C0E');

		return $obRow;
	}

	function __getSection($SECTION_ID)
	{
		if (!isset($this->arSectionCache[$SECTION_ID]))
		{
			$dbRes = CIBlockSection::GetByID($SECTION_ID);
			if ($arSection = $dbRes->Fetch())
			{
				$this->arSectionCache[$SECTION_ID] = $arSection;
			}
		}

		return $this->arSectionCache[$SECTION_ID];
	}

	function __checkIBlockRights()
	{
		$iblockPerm = CIBlock::GetPermission($this->IBLOCK_ID);
		return ($iblockPerm >= 'R');
	}

	function __checkRights($arSection)
	{
		global $USER;

		$type = $arSection['XML_ID'] == 'users_tasks' ? 'user' : 'group';
		$owner = $type == 'group' ? $arSection['XML_ID'] : $USER->GetID();

		if (!$this->__InTaskCheckActiveFeature($type, $owner))
			return false;

		$arRights = $this->__InTaskInitPerms($type, $owner);

		//little logic change
		if ($type == 'user')
			$arRights['view_all'] = false;

		return $arRights;
	}

	function __getUser($user_str)
	{
		$USER_ID = 0;

		list($USER_ID, $FIELDS) = explode(';', $user_str);

		if ($USER_ID <= 0)
		{
			$arUserFields = explode(',', substr($FIELDS, 1));
			$arKeywords = preg_split('/[^\w@.]+/', $arUserFields[1]);

			$arFilters = array(
				array(
					'LOGIN' => $arUserFields[0]
				),
				array(
					'EMAIL' => $arUserFields[0],
				),
				array(
					'EMAIL' => implode('|', $arKeywords),
				),
				array(
					'NAME' => implode('|', $arKeywords),
				),
			);

			foreach ($arFilters as $arFilter)
			{
				$dbRes = CUser::GetList($by='id', $order='asc', $arFilter);
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

		$taskType = StrToLower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}
		$ownerId = IntVal($ownerId);
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
					$GLOBALS["USER"]->GetID(),
					(($taskType == 'user') ? SONET_ENTITY_USER : SONET_ENTITY_GROUP),
					$ownerId,
					"tasks",
					$key
				);
		}

		return $arResult;
	}

	function __InTaskCheckActiveFeature($taskType, $ownerId)
	{
		$taskType = StrToLower($taskType);
		if (!in_array($taskType, array("group", "user")))
			$taskType = "user";

		$ownerId = IntVal($ownerId);
		if ($ownerId <= 0)
		{
			$taskType = "user";
			$ownerId = $GLOBALS["USER"]->GetID();
		}

		$ownerId = IntVal($ownerId);
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

			if (CModule::IncludeModule('extranet') && defined("IS_EXTRANET") && IS_EXTRANET == "Y")
				$CurrentSiteID = CExtranet::GetExtranetSiteID();
			else
				$CurrentSiteID = false;

			if (!$this->IBLOCK_ID = COption::GetOptionInt('intranet', 'iblock_tasks', false, $CurrentSiteID))
			{
				$this->error = new CSoapFault(
					'Server error',
					'iblock_tasks not set'
				);
			}
			else
			{
				if (!$this->__checkIBlockRights())
				{
					$this->error = new CSoapFault('Access denied', '');
				}
				else
				{
					$dbRes = CIBlock::GetProperties($this->IBLOCK_ID);
					while ($arProperty = $dbRes->Fetch())
					{
						if ($arProperty['PROPERTY_TYPE'] == 'L')
						{
							$arProperty['VARIANTS_ID'] = array();
							$arProperty['VARIANTS'] = array();
							$dbRes1 = CIBlockProperty::GetPropertyEnum($arProperty['ID']);
							while ($arRes = $dbRes1->Fetch())
							{
								$arProperty['VARIANTS_ID'][$arRes['ID']] = trim($arRes['VALUE']);
								$arProperty['VARIANTS'][$arRes['XML_ID']] = trim($arRes['VALUE']);
							}

						}

						$this->arProperties[$arProperty['CODE']] = $arProperty;
					}

					$dbRes = CIBlockSection::GetList(array('ID' => 'ASC'), array('IBLOCK_ID' => $this->IBLOCK_ID, 'XML_ID' => 'users_tasks'));
					if (!$this->arUsersSection = $dbRes->Fetch())
					{
						$this->error = new CSoapFault(
							'Server error',
							'users tasks section is not found'
						);
					}
				}
			}
		}

		return (null === $this->error);
	}

	/* public section */

	function GetList($listName)
	{
		global $APPLICATION, $USER;

		if (!$this->__Init()) return $this->error;

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

		$data->setAttribute('Author', $this->__makeUser($USER->GetID()));

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
		global $USER;

		if (!$this->__Init()) return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$listItemID = intval($listItemID);


		$dbRes = CIBlockElement::GetByID($listItemID);
		if (($obElement = $dbRes->GetNextElement()) && CIntranetTasksDocument::CanUserOperateDocument(INTASK_DOCUMENT_OPERATION_READ_DOCUMENT, 			$USER->GetID(),	$listItemID, array()))
		{
			$arFields = $obElement->GetFields();

			$data = '<Attachments>';

			$arProperty = $obElement->GetProperty('TaskFiles');

			if (is_array($arProperty['VALUE']))
			{
				foreach ($arProperty['VALUE'] as $FILE_ID)
				{
					if ($path = CFile::GetPath($FILE_ID))
					{
						$data .= '<Attachment>'.(CMain::IsHTTPS() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$path.'</Attachment>';
					}
				}
			}

			$data .= '</Attachments>';

			return array('GetAttachmentCollectionResult' => $data);
		}
		else
		{
			return new CSoapFault('0x81020016', 'List item not found');
		}
	}

	function AddAttachment($listName, $listItemID, $fileName, $attachment)
	{
		global $USER;

		if (!$this->__Init()) return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		if (strlen($attachment) <= 0)
		{
			return new CSoapFault('Wrong attachment', 'Wrong attachment');
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$listItemID = intval($listItemID);
		$dbRes = CIBlockElement::GetByID($listItemID);
		if (($obElement = $dbRes->GetNextElement()) && CIntranetTasksDocument::CanUserOperateDocument(INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT, 			$USER->GetID(),	$listItemID, array()))
		{
			$arElement = $obElement->GetFields();
			$arProperty = $obElement->GetProperty('TaskFiles');

			$fileName = str_replace(array('/', '\\', '..'), '', $fileName); // minor security

			$arValue = $arProperty['VALUE'];

			foreach($arValue as $FILE_ID)
			{
				$FILE = ToLower(basename(CFile::GetPath($FILE_ID)));
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

			$path = '/upload/outlook/Lists/'.$listItemID;

			CheckDirPath($_SERVER['DOCUMENT_ROOT'].$path);
			RewriteFile($_SERVER['DOCUMENT_ROOT'].$path.'/'.$fileName, $attachment);

			$arFile = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'].$path.'/'.$fileName);
			$arFile['MODULE_ID'] = 'intranet';
			$arValue = array($FILE_ID = CFile::SaveFile($arFile, 'outlook/Lists/'.$listItemID));

			@unlink($arFile['tmp_name']);

			CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arValue, 'TaskFiles');

			return array('AddAttachmentResult' => CFile::GetPath($FILE_ID));
		}
		else
		{
			return new CSoapFault('0x81020016', 'List item not found');
		}
	}

	function DeleteAttachment($listName, $listItemID, $url)
	{
		if (!$this->__Init()) return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listItemID = intval($listItemID);
		$dbRes = CIBlockElement::GetByID($listItemID);
		if (($obElement = $dbRes->GetNextElement()) && CIntranetTasksDocument::CanUserOperateDocument(INTASK_DOCUMENT_OPERATION_WRITE_DOCUMENT, 			$USER->GetID(),	$listItemID, array()))
		{
			$pos = strrpos($url, '/');
			if ($pos)
				$fileName = ToLower(str_replace(array('/', '\\', '..'), '', substr($url, $pos+1))); // minor security

			if (!$fileName)
				return new CSoapFault('Wrong file', 'Wrong file URL');

			$arNewValue = null;
			$arElement = $obElement->GetFields();
			$arProperty = $obElement->GetProperty('TaskFiles');

			foreach ($arProperty['VALUE'] as $key => $FILE_ID)
			{
				$FILE_NAME = ToLower(basename(CFile::GetPath($FILE_ID)));

				if ($FILE_NAME == $fileName)
				{
					$arNewValue = array(
						$arProperty['PROPERTY_VALUE_ID'][$key] => array(
							'VALUE' => array('del' => 'Y'),
							'DESCRIPTION' => false,
						),
					);
					break;
				}
			}

			if (is_array($arNewValue))
			{
				CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arNewValue, 'TaskFiles');
			}

			return array('DeleteAttachmentResult' => '');
		}
		else
		{
			return new CSoapFault('0x81020016', 'List item not found');
		}
	}

	function GetListItemChanges($listName, $viewFields = '', $since = '', $contains = '')
	{
		if (!$this->__Init()) return $this->error;

		define ('OLD_OUTLOOK_VERSION', true);

		$res = $this->GetListItemChangesSinceToken($listName, $viewFields, '', 0, $since ? $this->__makeTS($since) : '');

		if (is_object($res))
			return $res;
		else
			return array('GetListItemChangesResult' => $res['GetListItemChangesSinceTokenResult']);
	}

	function GetListItemChangesSinceToken($listName, $viewFields = '', $query = '', $rowLimit = 0, $changeToken = '')
	{
		global $APPLICATION, $USER;

		if (!$this->__Init()) return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$arFilter = array(
			'ACTIVE' => 'Y',
			//'IBLOCK_ID' => $this->IBLOCK_ID,
			'CHECK_BP_TASKS_PERMISSIONS' => 'user_'.$USER->GetID().'_read',
		);

		if ($changeToken)
		{
			$arFilter['>TIMESTAMP_X'] = ConvertTimeStamp($changeToken, 'FULL');
			if (!$arFilter['>TIMESTAMP_X'])
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
			$obChanges->addChild($this->__getFieldsDefinition());

		//TODO: the next thing is to do something like this for all deleted events.
		//$obChanges->addChild($obId = new CXMLCreator('Id'));
		//$obId->setAttribute('ChangeType', 'Delete');
		//$obId->setData('702');
		//<Id ChangeType="Delete">14</Id>

		$data->addChild($obData = new CXMLCreator('rs:data'));

		$counter = 0;

		$dbRes = CIntranetTasks::GetList(
			array("ID" => "ASC"),
			$arFilter
		);

		while ($arRes = $dbRes->Fetch())
		{
			$obData->addChild($this->__getRow($arRes, $listName, $last_change));
			$counter++;
		}

		$obData->setAttribute('ItemCount', $counter);

		$data->setAttribute('xmlns:rs', 'urn:schemas-microsoft-com:rowset');
		$data->setAttribute('xmlns:z', '#RowsetSchema');

		if ($last_change > 0)
		{
			$obChanges->setAttribute('LastChangeToken', $last_change);
		}

		return array('GetListItemChangesSinceTokenResult' => $data);
	}

	function __GetTaskStatus($ID, $iblockId)
	{
		$arDocumentState = CBPDocument::GetDocumentStates(
			array("intranet", "CIntranetTasksDocument", "x".$iblockId),
			array("intranet", "CIntranetTasksDocument", $ID)
		);

		$arDocumentState = array_values($arDocumentState);
		return $arDocumentState[0]['STATE_NAME'];
	}

	function UpdateListItems($listName, $updates)
	{
		global $USER;

		$arPaths = array(
			'user' =>
				COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/'),
			'group' =>
				COption::GetOptionString('intranet', 'path_task_group_entry', '/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/'),
			);

		if (!$this->__Init()) return $this->error;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		// one more dirty hack. now for bx.
		//define('BX_INTASKS_FROM_COMPONENT', true);
		//CModule::IncludeModule('socialnetwork');
		//include($_SERVER['DOCUMENT_ROOT'].BX_PERSONAL_ROOT.'/components/bitrix/intranet.tasks/init.php');
		//include($_SERVER['DOCUMENT_ROOT'].BX_PERSONAL_ROOT.'/components/bitrix/intranet.tasks/action.php');

		$obResponse = new CXMLCreator('Results');

		$arStatusValues = array();
		$obStatus = CIBlockProperty::GetPropertyEnum('TaskStatus', array('SORT' => 'asc'), array('IBLOCK_ID' => $this->IBLOCK_ID));
		while ($arStatus = $obStatus->Fetch())
		{
			$arStatusValues[$arStatus['XML_ID']] = $arStatus['ID'];
			if ($arStatus['XML_ID'] == 'NotAccepted')
				$arStatusValues[$arStatus['VALUE']] = $arStatus['ID'];
		}

		/*
		$arPriorityValues = array();
		$obPriority = CIBlockProperty::GetPropertyEnum('TaskPriority', array('SORT' => 'asc'), array('IBLOCK_ID' => $this->IBLOCK_ID));
		while ($arPriority = $obPriority->Fetch())
			$arPriorityValues[$arPriority['XML_ID']] = $arPriority['ID'];
		*/
		$arPriorityValues = array_flip($this->arProperties['TaskPriority']['VARIANTS_ID']);
		$arStatusValues = array_flip($this->arProperties['TaskStatus']['VARIANTS_ID']);
		$arStatusXML_ID = array_flip($this->arProperties['TaskStatus']['VARIANTS']);

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
				//$dbRes = CIBlockElement::GetByID($arData['ID']);
				$arElement = CIntranetTasks::GetById($arData['ID']);
				if (!is_array($arElement))
				{
					$obErrorCode->setData('0x81020016');
					$bUpdate = false;
				}
				else
				{
					//$arElement = $obElement->GetFields();
					//$arElement['prop'] = $obElement->GetProperties();

					if ($arElement['IBLOCK_SECTION_ID'] != $arSection['ID'])
					{
						if (!$arSection = $this->__getSection($arElement['IBLOCK_SECTION_ID']))
							return new CSoapFault('Some error', 'Unknown error with iblock sections');
					}

					// don't forget about depth of sections, Max has modification
					if ($arElement['taskType'] == "group")
					{
						$arGroupTmp = CSocNetGroup::GetByID($arElement['ownerId']);
						if ($arGroupTmp["CLOSED"] == "Y")
							if (COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
								return new CSoapFault('Cannot modify archive group task', 'Cannot modify archive group task');
					}

					$arElement['arParams'] = array(
						($arElement['taskType'] == 'user' ? 'PATH_TO_USER_TASKS_TASK' : 'PATH_TO_GROUP_TASKS_TASK') =>
							str_replace(
								array('#USER_ID#', '#GROUP_ID#', '#TASK_ID#'),
								array($USER->GetID(), $arSection['XML_ID'], $arElement['ID']),
								$arPaths[$arElement['taskType']]
							),
					);
				}
			}

			if ($bUpdate)
			{
				if ($arData['_command'] == 'Delete')
				{
					$arError = false;

					if (CIntranetTasksDocument::CanUserOperateDocument(
						INTASK_DOCUMENT_OPERATION_DELETE_DOCUMENT,
						$USER->GetID(),
						$arElement['ID'],
						array()
					))
					{
						if (!CIntranetTasks::Delete($arElement['ID'], $arError))
						{
							$obErrorCode->setData('0x81020014');
						}
						else
						{
							$obErrorCode->setData('0x00000000');
						}
					}
					else
					{
						$obErrorCode->setData('0x81020014');
					}

					/*
						0x00000000 - ok
						0x81020015 - data conflict
						0x81020014 - generic error such as invalid value for Field
						0x81020016 - item does not exist
					*/
				}
				elseif ($arData['_command'] == 'New' || $arData['_command'] == 'Update')
				{
					/*
					$arData['Body'] = str_replace('</DIV>', "\r\n</DIV>", $arData['Body']);
					$arData['Body'] = strip_tags($arData['Body']);
					$arData['Body'] = trim($arData['Body']);
					$arData['Body'] = htmlspecialcharsback($arData['Body']);
					*/
					$arData['Body'] = trim($arData['Body']);
					$arData['Body'] = str_replace(array("&#10;", "&#13;", '&nbsp;'), "", $arData['Body']);
					$arData['Body'] = preg_replace("/<![^>]*>/", '', $arData['Body']);

					if (($pos = strpos($arData['Body'], '<BODY>')) !== false)
						$arData['Body'] = substr($arData['Body'], $pos+6);
					echo $pos.' ';
					if (($pos = strpos($arData['Body'], '</BODY>')) !== false)
						$arData['Body'] = substr($arData['Body'], 0, $pos);
					echo $pos.' ';

					$TZBias = intval(date('Z'));

					$arData['StartDate'] = $arData['StartDate'] ? $this->__makeTS($arData['StartDate'])+$TZBias : '';
					$arData['DueDate'] = $arData['DueDate'] ? $this->__makeTS($arData['DueDate'])+$TZBias : '';

					$arData['MetaInfo_DateComplete'] = $arData['MetaInfo_DateComplete'] ? $this->__makeTS($arData['EndDate'])+$TZBias : '';

					if ($arData['Status'] == $arStatusValues['NotAccepted'])
						$arData['Status'] = 'NotAccepted';

					$arFields = array(
						'IBLOCK_ID' => $this->IBLOCK_ID,
						'IBLOCK_SECTION_ID' => $arSection['ID'],
						'MODIFIED_BY' => $USER->GetID(),
						'NAME' => $arData['Title'] ? $arData['Title'] : GetMessage('INTR_OUTLOOK_TASK_NO_TITLE'),
						'DATE_ACTIVE_FROM' => $arData['StartDate'] ? ConvertTimeStamp($arData['StartDate']) : '',
						'DATE_ACTIVE_TO' => $arData['DueDate'] ? ConvertTimeStamp($arData['DueDate']) : '',
						'DETAIL_TEXT_TYPE' => 'html',
						'DETAIL_TEXT' => $arData['Body'],
						'PROPERTY_TaskPriority' => $arData['Priority'] ? $arPriorityValues[$arData['Priority']] : false,
						//'PROPERTY_TaskStatus' => $arStatusValues[$arData['Status']],
						'PROPERTY_TaskComplete' => $arData['PercentComplete'] * 100,
						'PROPERTY_TaskSize' => $arData['MetaInfo_TotalWork']/60,
						'PROPERTY_TaskSizeReal' => $arData['MetaInfo_ActualWork']/60,
						'PROPERTY_TaskFinish' => $arData['MetaInfo_DateComplete'] ? ConvertTimeStamp($arData['MetaInfo_DateComplete']) : '',
					);

					if (false === $arFields['PROPERTY_TaskPriority'])
					{
						$arPrior = array_keys($arElement['PROPERTY_TaskPriority']);
						$arFields['PROPERTY_TaskPriority'] = $arPrior[0];
					}

					if ($assigned_to = $arData['AssignedTo'])
					{
						if ($USER_ID = $this->__getUser($assigned_to))
						{
							$arFields['PROPERTY_TaskAssignedTo'] = $USER_ID;
						}
						else
						{
							$obErrorCode->setData('0x81020054');
							$bUpdate = false;
						}
					}

					if ($bUpdate)
					{
						$bConfirm = false;
						$bComplete = false;

						if ($arData['_command'] != 'New')
						{
							if (false && $arElement['PROPERTY_VERSION'] > $arData['owshiddenversion'])
							{
								$obErrorCode->setData('0x81020015');
								$bUpdate = false;
							}
							else
							{
								foreach ($arElement as $fld => $value)
								{
									if (substr($fld, 0, 9) == 'PROPERTY_')
									{
										if (!$arFields[$fld] && $fld != 'PROPERTY_TaskFiles')
										{
											$arFields[$fld] = $arElement[$fld];
										}
									}
								}

								$arFields['PROPERTY_VERSION']++;
							}

							$statusOld = $this->__GetTaskStatus($arElement['ID'], $arElement['IBLOCK_ID']);
						}
						else
						{
							$arFields['PROPERTY_VERSION'] = $arData['owshiddenversion'];

							$statusOld = -1;
						}

						if (!$arFields['PROPERTY_TaskPriority'])
							$arFields['PROPERTY_TaskPriority'] = 2;
						if (!$arFields['PROPERTY_TaskAssignedTo'])
							$arFields['PROPERTY_TaskAssignedTo'] = $USER->GetID();
						elseif ($arFields['PROPERTY_TaskAssignedTo'] != $USER->GetID())
							$arData['Status'] = 'NotAccepted';

						$statusNew = $arData['Status'] ? $arStatusXML_ID[$arData['Status']] : $statusOld;

						$ID = 0;
						if ($bUpdate)
						{
							$arError = false;
							if ($arData['_command'] == 'New')
							{
								if ($ID = CIntranetTasks::Add($arFields, $arError))
								{
									$arDocumentStates = CBPDocument::GetDocumentStates(
										array("intranet", "CIntranetTasksDocument", "x".$this->IBLOCK_ID),
										null
									);

									$arDocumentStates = array_values($arDocumentStates);

									$pathTemplate = str_replace(
										array("#USER_ID#", "#TASK_ID#"),
										array($USER->GetID(), "{=Document:ID}"),
										COption::GetOptionString(
											"intranet",
											"path_task_user_entry",
											"/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/"
										)
									);

									$arErrors = array();
									CBPDocument::StartWorkflow(
										$arDocumentStates[0]["TEMPLATE_ID"],
										array("intranet", "CIntranetTasksDocument", $ID),
										array(
											"OwnerId" => $arFields['PROPERTY_TaskAssignedTo'],
											"TaskType" => 'user',
											"PathTemplate" => $pathTemplate,
											"ForumId" => COption::GetOptionInt('intranet', 'task_forum_id', 0),
											"IBlockId" => $this->IBLOCK_ID,
										),
										$arErrors
									);

									CIntranetTasks::ChangeStatus($ID, $statusNew);

									$arReplicationIDs[$ID] = $arData['MetaInfo_ReplicationID'];
									$obErrorCode->setData('0x00000000');
								}
							}
							else
							{
								if (CIntranetTasks::Update($arData['ID'], $arFields, $arError))
								{
									if ($statusOld != $statusNew)
									{
										CIntranetTasks::ChangeStatus($arData['ID'], $statusNew);
									}

									$ID = $arData['ID'];
									$obErrorCode->setData('0x00000000');
								}
							}

							if (is_array($arError) && count($arError) > 0)
							{
								$ID = 0;
								$obErrorCode->setData('0x81020014');
								$bUpdate = false;
							}
							else
							{
								$taskType = $arElement ? $arElement['taskType'] : 'user';
								$ownerId = $arElement ? $arElement['ownerId'] : $USER->GetID();
								$arParams = $arElement ? $arElement['arParams'] : array(
									'PATH_TO_USER_TASKS_TASK' => str_replace(
										array('#USER_ID#', '#GROUP_ID#', '#TASK_ID#'),
										array($USER->GetID(), $arSection['XML_ID'], $ID),
										$arPaths['user']
									)
								);
							}
						}
					}

				}
			}

			if ($ID > 0)
				$arResponseRows[$ID] = $obResponseRow;
			else
				$arResponseRowsError[] = $obResponseRow;
		}

		$dbRes = CIntranetTasks::GetList(
			array('ID' => 'ASC'),
			array(
				'IBLOCK_ID' => $this->IBLOCK_ID,
				'ID' => array_keys($arResponseRows),
			)
		);

		while ($arRes = $dbRes->Fetch())
		{
			if ($arResponseRows[$arRes['ID']])
			{
				$obRow = $this->__getRow($arRes, $listName, $last_change = 0);

				if ($arReplicationIDs[$arRes['ID']])
					$obRow->setAttribute('ows_MetaInfo_ReplicationID', $arReplicationIDs[$arRes['ID']]);

				$obRow->setAttribute('xmlns:z', "#RowsetSchema");

				$arResponseRows[$arRes['ID']]->addChild($obRow);
				$obResponse->addChild($arResponseRows[$arRes['ID']]);
			}
		}

		foreach ($arResponseRowsError as $obChange)
		{
			$obResponse->addChild($obChange);
		}

		return array('UpdateListItemsResult' => $obResponse);
	}

	function GetWebServiceDesc()
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = "bitrix.webservice.intranet.tasks";
		$wsdesc->wsclassname = "CIntranetTasksWS";
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();

		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();

		$wsdesc->classes = array(
			"CIntranetTasksWS" => array(
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
						"viewFields" => array("varType" => "any", 'strict'=> 'no'),
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
						"viewFields" => array("varType" => "any", 'strict'=> 'no'),
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
?>