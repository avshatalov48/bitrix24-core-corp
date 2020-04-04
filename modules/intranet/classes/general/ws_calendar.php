<?
if (!CModule::IncludeModule('webservice'))
	return;

class CIntranetCalendarWS extends IWebService
{
	var $arStatusValues = array(
		'free' => 0, 'quest' => 1, 'busy' => 2, 'absent' => 3,
	);

	var $arPriorityValues = array(
		'low' => -1, 'normal' => 0, 'high' => 1,
	);

	var $arWeekDays = array('mo', 'tu', 'we', 'th', 'fr', 'sa', 'su');

	function __getFieldsDefinition()
	{
		$obFields = new CXMLCreator('Fields');

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ID" ColName="tp_ID" RowOrdinal="0" ReadOnly="TRUE" Type="Counter" Name="ID" PrimaryKey="TRUE" DisplayName="ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ID" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="OWSHIDDENVERSION" ColName="tp_Version" RowOrdinal="0" Hidden="TRUE" ReadOnly="TRUE" Type="Integer" SetAs="owshiddenversion" Name="owshiddenversion" DisplayName="owshiddenversion" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="owshiddenversion" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FSOBJTYPE" Name="FSObjType" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Item Type" List="Docs" FieldRef="ID" ShowField="FSType" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FSObjType" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="UNIQUEID" Name="UniqueId" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Unique Id" List="Docs" FieldRef="ID" ShowField="UniqueId" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="UniqueId" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPEID" ColName="tp_ContentTypeId" Sealed="TRUE" Hidden="TRUE" RowOrdinal="0" ReadOnly="TRUE" Type="ContentTypeId" Name="ContentTypeId" DisplaceOnUpgrade="TRUE" DisplayName="Content Type ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentTypeId" FromBaseType="TRUE"'));
		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPE" ColName="tp_ContentType" RowOrdinal="0" ReadOnly="TRUE" Type="Text" Name="ContentType" DisplaceOnUpgrade="TRUE" DisplayName="Content Type" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentType" FromBaseType="TRUE" PITarget="MicrosoftWindowsSharePointServices" PIAttribute="ContentTypeID"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="METAINFO" Name="MetaInfo" DisplaceOnUpgrade="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Property Bag" List="Docs" FieldRef="ID" ShowField="MetaInfo" JoinColName="DoclibRowId" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="MetaInfo" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PERMMASK" Name="PermMask" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" RenderXMLUsingPattern="TRUE" ShowInFileDlg="FALSE" Type="Computed" DisplayName="Effective Permissions Mask" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="PermMask" FromBaseType="TRUE"'));

		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="ID"'));

		$obField->addChild($obDisplayPattern = new CXMLCreator('DisplayPattern'));
		$obDisplayPattern->addChild(new CXMLCreator('CurrentRights'));

		//
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="{fa564e0f-0c70-4ab9-b863-0177e6ddd247}" Type="Text" Name="Title" DisplayName="Title" Required="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Title" FromBaseType="TRUE" ColName="nvarchar1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="LOCATION" Type="Text" Name="Location" DisplayName="Location" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Location" ColName="nvarchar3"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field Type="Note" ID="DESCRIPTION" Name="Description" RichText="TRUE" DisplayName="Description" Sortable="FALSE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Description" ColName="ntext2"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="MODIFIED" ColName="tp_Modified" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Modified" DisplayName="Modified" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Modified" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="AUTHOR" ColName="tp_Author" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Author" DisplayName="Created By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Author" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EDITOR" ColName="tp_Editor" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Editor" DisplayName="Modified By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Editor" FromBaseType="TRUE" '));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field Type="DateTime" ID="DATE_FROM" Name="EventDate" DisplayName="Start Time" Format="DateTime" Sealed="TRUE" Required="TRUE" FromBaseType="TRUE" Filterable="FALSE" FilterableNoRecurrence="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="EventDate" ColName="datetime1"'));
		$obField->addChild(CXMLCreator::createTagAttributed('Default', '[today]'));
		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="fAllDayEvent" RefType="AllDayEvent"'));
		$obField->addChild(CXMLCreator::createTagAttributed('DefaultFormulaValue', $this->__makeDateTime(strtotime(date('Y-m-d')))));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DATE_TO" Type="DateTime" Name="EndDate" DisplayName="End Time" Format="DateTime" Sealed="TRUE" Required="TRUE" Filterable="FALSE" FilterableNoRecurrence="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="EndDate" ColName="datetime2"'));
		$obField->addChild(CXMLCreator::createTagAttributed('Default', '[today]'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DURATION" Type="Integer" Name="Duration" DisplayName="Duration" Hidden="TRUE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Duration" ColName="int2"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ALLDAYEVENT" Type="AllDayEvent" Name="fAllDayEvent" DisplaceOnUpgrade="TRUE" DisplayName="All Day Event" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="fAllDayEvent" ColName="bit1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EVENTTYPE" Type="Integer" Name="EventType" DisplayName="Event Type" Sealed="TRUE" Hidden="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="EventType" ColName="int1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="UID" Type="Guid" Name="UID" DisplayName="UID" Sealed="TRUE" Hidden="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="UID" ColName="uniqueidentifier1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="RECURENCE_DATA" Type="Note" Name="RecurrenceData" DisplayName="RecurrenceData" Hidden="TRUE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="RecurrenceData" ColName="ntext3"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="TIMEZONE" Type="Integer" Name="TimeZone" DisplayName="TimeZone" Sealed="TRUE" Hidden="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="TimeZone" ColName="int3"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="XMLTZONE" Type="Note" Name="XMLTZone" DisplayName="XMLTZone" Hidden="TRUE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="XMLTZone" ColName="ntext4"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="RECURRENCE" Type="Recurrence" Name="fRecurrence" DisplayName="Recurrence"  Title="Recurrence" Sealed="TRUE" NoEditFormBreak="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="fRecurrence" ColName="bit2"'));
		$obField->addChild(CXMLCreator::createTagAttributed('Default', 'FALSE'));
		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="RecurrenceData" RefType="RecurData"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="EventType" RefType="EventType"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="UID" RefType="UID"'));
		//$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="RecurrenceID" RefType="RecurrenceId"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="EventDate" RefType="StartDate"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="EndDate" RefType="EndDate"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="Duration" RefType="Duration"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="TimeZone" RefType="TimeZone"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="XMLTZone" RefType="XMLTZone"'));
		//$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="MasterSeriesItemID" RefType="MasterSeriesItemID"'));


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

	function __getRMIblockID()
	{
		if ($this->RMIblockID)
			return $this->RMIblockID;
		else
		{
			$dbRes = CIBlock::GetList(array('SORT' => 'ASC'), array('CODE' => 'meeting_rooms'), false);

			if ($arRes = $dbRes->Fetch())
			{
				return ($this->RMIblockID = $arRes['ID']);
			}
		}

		return false;
	}

	function GetList($listName)
	{
		global $APPLICATION;

		//todo: check read access for calendar

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault(
				'Data error',
				'Wrong GUID - '.$listName
			);
		}
		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$obRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('XML_ID' => $listName_original));
		if (!$arSection = $obRes->Fetch())
		{
			return new CSoapFault(
				'List not found',
				'List with '.$listName.' GUID not found'
			);
		}

		$dbAuthor = CUser::GetByID($arSection['CREATED_BY']);
		$arAuthor = $dbAuthor->Fetch();

		$data = new CXMLCreator('List');
		$data->setAttribute('ID', $listName);
		$data->setAttribute('Name', $listName);
		$data->setAttribute('Title', $arSection['NAME']);

		$data->setAttribute('Created', date('Ymd H:i:s', MakeTimeStamp($arSection['DATE_CREATE'])));
		$data->setAttribute('Modified', date('Ymd H:i:s', MakeTimeStamp($arSection['TIMESTAMP_X'])));
		$data->setAttribute('Direction', 'none'); // RTL, LTR

		$data->setAttribute('ReadSecurity', '2');
		$data->setAttribute('WriteSecurity', '2');

		$name = $arAuthor['NAME'];
		if ($arAuthor['LAST_NAME'])
			$name .= ' '.$arAuthor['LAST_NAME'];
		if (!$name)
			$name .= $arAuthor['LOGIN'];

		$data->setAttribute('Author', $arAuthor['ID'].';#'.$name);

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

		$obNode->addChild(CXMLCreator::createTagAttributed('ServerVersion', '12.0.0.6219'));
		$obNode->addChild(CXMLCreator::createTagAttributed('RecycleBinEnabled', 'False'));
		$obNode->addChild(CXMLCreator::createTagAttributed('ServerRelativeUrl', '/company/absence.php'));

		return array('GetListResult' => $data);
	}

	function __getRow($arRes, $listName, &$last_change)
	{
		global $APPLICATION, $USER;

		$arStatusValues = $this->arStatusValues;
		$arPriorityValues = $this->arPriorityValues;

		$first_week_day = COption::GetOptionString('intranet', 'first_week_day', 'mo');

		$dbEditor = CUser::GetByID($arRes['MODIFIED_BY']);
		$arEditor = $dbEditor->Fetch();

		$change = MakeTimeStamp($arRes['TIMESTAMP_X']);
		if ($last_change < $change)
			$last_change = $change;

		$row_count++;

		$bRecurrent = (isset($arRes['PERIOD']) && is_array($arRes['PERIOD'])) ? 1 : 0;

		$ts_start = MakeTimeStamp($arRes['DATE_FROM']);
		$ts_finish = MakeTimeStamp($arRes['DATE_TO']);

		$TZBias = intval(date('Z'));
		$TZBiasStart = intval(date('Z', $ts_start));

		$duration = $bRecurrent ? $arRes['PERIOD']['LENGTH'] : ($ts_finish-$ts_start);
		$bAllDay = ($duration % 86400 == 0) ? 1 : 0;

		if (!$bAllDay || defined('OLD_OUTLOOK_VERSION'))
		{
			$ts_start -= $TZBiasStart;
			$ts_finish -= $TZBiasStart;
		}

		$obRow = new CXMLCreator('z:row');
		$obRow->setAttribute('ows_ID', $arRes['ID']);

		$version = $arRes['VERSION'] ? $arRes['VERSION'] : 1;

		$obRow->setAttribute('ows_Attachments', 0);
		$obRow->setAttribute('ows_owshiddenversion', $version);
		$obRow->setAttribute('ows_MetaInfo_vti_versionhistory', md5($arRes['ID']).':'.$version);

		/*
			ows_MetaInfo_BusyStatus='2' - Editor
			ows_MetaInfo_IntendedBusyStatus='-1' - Creator

			values:
				-1 - Unspecified busy status. Protocol clients can choose to display one of the other values if BusyStatus is -1.
				0 - Free - ACCESSIBILITY => 'free'
				1 - Tentative - ACCESSIBILITY => 'quest'
				2 - Busy - ACCESSIBILITY => 'busy'
				3 - Out of Office - ACCESSIBILITY => 'absent'
		*/
		$status = $arStatusValues[$arRes['ACCESSIBILITY']];
		$obRow->setAttribute('ows_MetaInfo_BusyStatus', $status === null ? -1 : $status);
		$obRow->setAttribute('ows_MetaInfo_Priority', intval($arPriorityValues[$arRes['IMPORTANCE']]));

		$obRow->setAttribute('ows_Created', $this->__makeDateTime(MakeTimeStamp($arRes['DATE_CREATE'])-$TZBias));
		$obRow->setAttribute('ows_Modified', $this->__makeDateTime($change-$TZBias));
		$obRow->setAttribute('ows_EventType', $bRecurrent ? 1 : 0);
		$obRow->setAttribute('ows_Title', htmlspecialcharsback($arRes['NAME'])); // we have data htmlspecialchared yet

		if ($arRes['LOCATION'])
		{
			$arLocationInfo = CEventCalendar::ParseLocation($arRes['LOCATION']);
			if ($arLocationInfo['mrid'] === false)
				$obRow->setAttribute('ows_Location', $arLocationInfo['str']);
			else
			{
				if (!($arMRInfo = $this->arMRInfo[$arLocationInfo['mrid']]))
					$arMRInfo = CEventCalendar::GetMeetingRoomById(array(
						'id' => $arLocationInfo['mrid'], 'RMiblockId' => $this->__getRMIblockID()
					));

				if ($arMRInfo)
				{
					$this->arMRInfo[$arLocationInfo['mrid']] = $arMRInfo;
					$obRow->setAttribute('ows_Location', $arMRInfo['NAME']);
				}
				else
				{
					$obRow->setAttribute('ows_Location', $arLocationInfo['str']);
				}
			}
		}

		$obRow->setAttribute('ows_Description', htmlspecialcharsback($arRes['DETAIL_TEXT'])); // we have data htmlspecialchared yet
		$obRow->setAttribute('ows_EventDate', $this->__makeDateTime($ts_start));
		$obRow->setAttribute('ows_EndDate', $this->__makeDateTime(((false && $bRecurrent) ? $ts_start + $arRes['PERIOD']['LENGTH'] : $ts_finish) + ($bAllDay ? 86340 : 0)));
		$obRow->setAttribute('ows_fAllDayEvent', $bAllDay);

		/* Recurrence */
		$obRow->setAttribute('ows_fRecurrence', $bRecurrent);

		if ($bRecurrent)
		{
			$arWeekDays = $this->arWeekDays;

			$obRow->setAttribute('ows_UID', CIntranetUtils::makeGUID(md5($arRes['ID'].'_'.$change)));

			$tz_data = '';
			$tz_data .= '<timeZoneRule>';
			$tz_data .= '<standardBias>'.(-intval(($TZBias - (date('I') ? 3600 : 0)) /60)).'</standardBias>';
			$tz_data .= '<additionalDaylightBias>-60</additionalDaylightBias>';

			$bUseTransition = COption::GetOptionString('intranet', 'tz_transition', 'Y') == 'Y';

			if ($bUseTransition)
			{
				$transition_standard = COption::GetOptionString('intranet', 'tz_transition_standard', '');
				$transition_daylight = COption::GetOptionString('intranet', 'tz_transition_daylight', '');
				if (!$transition_standard) $transition_standard = '<transitionRule month="10" day="su" weekdayOfMonth="last" /><transitionTime>3:0:0</transitionTime>';
				if (!$transition_daylight) $transition_daylight = '<transitionRule  month="3" day="su" weekdayOfMonth="last" /><transitionTime>2:0:0</transitionTime>';

				$tz_data .= '<standardDate>'.$transition_standard.'</standardDate><daylightDate>'.$transition_daylight.'</daylightDate>';
			}

			$tz_data .= '</timeZoneRule>';

			$obRow->setAttribute('ows_XMLTZone', $tz_data);
			//$obRow->setAttribute('ows_TimeZone', 7);

			$recurence_data = '';
			$recurence_data .= '<recurrence>';
			$recurence_data .= '<rule>';
			$recurence_data .= '<firstDayOfWeek>'.$first_week_day.'</firstDayOfWeek>';

			$recurence_data .= '<repeat>';
			switch($arRes['PERIOD']['TYPE'])
			{
				case 'DAILY':
					$recurence_data .= '<daily dayFrequency="'.$arRes['PERIOD']['COUNT'].'" />';
				break;

				case 'WEEKLY':
					$days = '';
					$arDays = explode(',', $arRes['PERIOD']['DAYS']);
					foreach ($arDays as $day) $days .= $arWeekDays[$day].'="TRUE" ';

					$recurence_data .= '<weekly '.$days.'weekFrequency="'.$arRes['PERIOD']['COUNT'].'" />';
				break;

				case 'MONTHLY':
					$recurence_data .= '<monthly monthFrequency="'.$arRes['PERIOD']['COUNT'].'" day="'.date('d', $ts_start).'" />';
				break;

				case 'YEARLY':
					$recurence_data .= '<yearly yearFrequency="'.$arRes['PERIOD']['COUNT'].'" month="'.date('m', $ts_start).'" day="'.date('d', $ts_start).'" />';
				break;
			}


			$recurence_data .= '</repeat>';

			if (date('Y', $ts_finish) == '2038' || date('Y', $ts_finish) == '2037')
				$recurence_data .= '<repeatForever>FALSE</repeatForever>';
			else
				$recurence_data .= '<windowEnd>'.$this->__makeDateTime($ts_finish).'</windowEnd>';

			$recurence_data .= '</rule>';
			$recurence_data .= '</recurrence>';
			$obRow->setAttribute('ows_RecurrenceData', $recurence_data);

			$obRow->setAttribute('ows_Duration', $arRes['PERIOD']['LENGTH'] + ($bAllDay ? 86340 : 0));
		}
		else
		{
			$obRow->setAttribute('ows_Duration', ($duration ? $duration : 86400)-60);
		}

		$obRow->setAttribute('ows_UniqueId', $arRes['ID'].';#'.$listName);
		$obRow->setAttribute('ows_FSObjType', $arRes['ID'].';#0');

		$name = $arEditor['NAME'];
		if ($arEditor['LAST_NAME'])
			$name .= ' '.$arEditor['LAST_NAME'];
		if (!$name)
			$name .= $arEditor['LOGIN'];
		$obRow->setAttribute('ows_Editor', $arEditor['ID'].';#'.$name);

		$obRow->setAttribute('ows_PermMask', '0x7fffffffffffffff');
		$obRow->setAttribute('ows_ContentTypeId', '0x01020005CE290982A58C439E00342702139D1A');

		return $obRow;
	}

	function GetListItemChanges($listName, $viewFields = '', $since = '', $contains = '')
	{
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

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$obRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('XML_ID' => $listName_original));
		if (!$arSection = $obRes->Fetch())
		{
			return new CSoapFault('List not found', 'List with '.$listName.' GUID is not found');
		}

		$arMethodParams = array(
			'ownerType' => 'USER',
			//'ownerId' => (is_object($USER) && $USER->GetID()) ? $USER->GetID() : false,
			'ownerId' => $arSection['CREATED_BY'],
			'bOwner' => true,
			'iblockId' => $arSection['IBLOCK_ID'],
			'sectionId' => $arSection['ID'],
			'arCalendarIds' => array($arSection['ID']),
			'forExport' => true
		);

		if ($changeToken)
		{
			$arMethodParams['bLoadAll'] = false;
			$arMethodParams['timestampFrom'] = ConvertTimeStamp($changeToken, 'FULL');
			if (!$arMethodParams['timestampFrom'])
			{
				return new CSoapFault(
					'Params error',
					'Wrong changeToken: '.$changeToken
				);
			}
		}
		else
		{
			$arMethodParams['bLoadAll'] = true;
		}

		$obCalendar = new CEventCalendar();
		$obCalendar->Init(array(
			'ownerType' => $arMethodParams['ownerType'],
			'ownerId' => $arMethodParams['ownerId'],
			'bOwner' => true,
			'iblockId' => $arMethodParams['iblockId'],
			'bCache' => false,
			'userIblockId' => $arMethodParams['iblockId'],
		));

		// dirty hack
		$arPermissions = $obCalendar->GetPermissions(
			array(
				'setProperties' => true, 'ownerId' => $arSection['CREATED_BY']
			)
		);

		$obCalendar->arCalenderIndex[$arSection['ID']]['PRIVATE_STATUS'] = CECCalendar::GetPrivateStatus($arSection['IBLOCK_ID'], $arSection['ID'], 'USER');

		$arCalendarEntries = $obCalendar->GetEvents($arMethodParams);

		$last_change = 0;
		$data = new CXMLCreator('listitems');
		$data->setAttribute('MinTimeBetweenSyncs', 0);
		$data->setAttribute('RecommendedTimeBetweenSyncs', 180);
		$data->setAttribute('TimeStamp', $this->__makeDateTime());
		$data->setAttribute('EffectivePermMask', 'FullMask');

		$data->addChild($obChanges = new CXMLCreator('Changes'));

		if (!$changeToken && !defined('OLD_OUTLOOK_VERSION'))
		{
			$obChanges->addChild($this->__getFieldsDefinition());
		}

		//TODO: the next thing is to do something like this for all deleted events.
		//$obChanges->addChild($obId = new CXMLCreator('Id'));
		//$obId->setAttribute('ChangeType', 'Delete');
		//$obId->setData('702');
		//<Id ChangeType="Delete">14</Id>

		$data->addChild($obData = new CXMLCreator('rs:data'));

		foreach ($arCalendarEntries as $key => $arRes)
		{
			if ($arRes['STATUS'] == 'Q')
				continue;

			$obData->addChild($this->__getRow($arRes, $listName, $last_change));
		}

		//$last_change = time();
		$obData->setAttribute('ItemCount', count($arCalendarEntries));

		$data->setAttribute('xmlns:rs', 'urn:schemas-microsoft-com:rowset');
		$data->setAttribute('xmlns:z', '#RowsetSchema');

		if ($last_change > 0)
			$obChanges->setAttribute('LastChangeToken', $last_change);


		return array('GetListItemChangesSinceTokenResult' => $data);
	}

	function UpdateListItems($listName, $updates)
	{
		global $USER;

		$arStatusValues = array_flip($this->arStatusValues);
		$arPriorityValues = array_flip($this->arPriorityValues);
		$arWeekDays = array_flip($this->arWeekDays);

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$obResponse = new CXMLCreator('Results');

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$obRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('XML_ID' => $listName_original));
		if (!$arSection = $obRes->Fetch())
		{
			return new CSoapFault('List not found', 'List with '.$listName.' GUID is not found');
		}

		$bGroup = $arSection['SOCNET_GROUP_ID'] != '';

		if ($bGroup)
		{
			CModule::IncludeModule('socialnetwork');
			$arGroupTmp = CSocNetGroup::GetByID($arSection['SOCNET_GROUP_ID']);
			if ($arGroupTmp["CLOSED"] == "Y")
				if (COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
					return new CSoapFault('Cannot modify archive group calendar', 'Cannot modify archive group calendar');
		}


		$obCalendar = new CEventCalendar();
		$obCalendar->Init(array(
			'ownerType' => $bGroup ? 'GROUP' : 'USER',
			'ownerId' => $bGroup ? $arSection['SOCNET_GROUP_ID'] : ((is_object($USER) && $USER->GetID()) ? $USER->GetID() : false),
			'bOwner' => true,
			'iblockId' => $arSection['IBLOCK_ID'],
			'bCache' => false,
			//'userIblockId' => $arSection['IBLOCK_ID'],
		));

		// dirty hack
		$arPermissions = $obCalendar->GetPermissions(
			array(
				'setProperties' => true, //'userId' => $USER->GetID(),//'ownerId' => $arSection['CREATED_BY'],
			)
		);

		$obBatch = $updates->children[0];
		$atrONERROR = $obBatch->getAttribute('OnError');
		$atrDATEINUTC = $obBatch->getAttribute('DateInUtc');
		$atrPROPERTIES = $obBatch->getAttribute('Properties');

		$arChanges = $obBatch->children;

		$arResultIDs = array();
		$dateStart = ConvertTimeStamp(strtotime('-1 hour'), 'FULL');
		$arResponseRows = array();
		$arReplicationIDs = array();

		foreach ($arChanges as $obMethod)
		{
			$arData = array('_command' => $obMethod->getAttribute('Cmd'));

			foreach ($obMethod->children as $obField)
			{
				$name = $obField->getAttribute('Name');
				if ($name == 'MetaInfo')
					$name .= '_'.$obField->getAttribute('Property');

				$arData[$name] = $obField->content;
			}

			if ($arData['_command'] == 'Delete')
			{
				$obRes = new CXMLCreator('Result');
				$obRes->setAttribute('ID', $obMethod->getAttribute('ID').','.$arData['_command']);
				$obRes->setAttribute('List', $listName);

				$obRes->addChild($obNode = new CXMLCreator('ErrorCode'));
				if (CECEvent::Delete(array(
					'id' => $arData['ID'],
					'ownerType' => $bGroup ? 'GROUP' : 'USER',
					'ownerId' => $bGroup ? $arSection['SOCNET_GROUP_ID'] : ((is_object($USER) && $USER->GetID()) ? $USER->GetID() : false),
					'bOwner' => true,
					'iblockId' => $arSection['IBLOCK_ID'],
					'RMiblockId' => $this->__getRMIblockID(),
					'allowResMeeting' => true,
				)))
				{
					CEventCalendar::ClearCache('/event_calendar/events/'.$arSection['IBLOCK_ID'].'/');
					$obNode->setData('0x00000000');
				}
				else
				{
					$obNode->setData('0x81020014');
				}

				/*
					0x00000000 - ok
					0x81020015 - data conflict
					0x81020014 - generic error such as invalid value for Field
					0x81020016 - item does not exist
				*/

				$obResponse->addChild($obRes);
			}
			elseif ($arData['_command'] == 'New' || $arData['_command'] == 'Update')
			{
				$q = ToLower($arData['Description']);
				if (($pos = strrpos($q, '</body>')) !== false) $arData['Description'] = substr($arData['Description'], 0, $pos);
				if (($pos = strpos($q, '<body>')) !== false) $arData['Description'] = substr($arData['Description'], $pos + 6);

				$arData['Description'] = str_replace('</DIV>', "\r\n</DIV>", $arData['Description']);
				$arData['Description'] = str_replace(array("&#10;", "&#13;"), "", $arData['Description']);
				$arData['Description'] = preg_replace("/<![^>]*>/", '', $arData['Description']);
				//$arData['Description'] = strip_tags($arData['Description']);
				$arData['Description'] = trim($arData['Description']);

				$arData['Location'] = trim($arData['Location']);

				$arData['EventDate'] = $this->__makeTS($arData['EventDate']);
				$arData['EndDate'] = $this->__makeTS($arData['EndDate']) + ($arData['fAllDayEvent'] ? -86340 : 0);

				$TZBias = intval(date('Z', $arData['EventDate']));

				$arData['EventType'] = intval($arData['EventType']);

				if ($arData['EventType'] == 2)
					$arData['EventType'] = 0;

				if ($arData['EventType'] > 2 /* || ($arData['EventType'] == 1 && !$arData['RecurrenceData'])*/)
					return new CSoapFault(
						'Unsupported event type',
						'Event type unsupported'
					);

				$arData['fRecurrence'] = intval($arData['fRecurrence']);
				if ($arData['RecurrenceData'])
				{
					//$xmlstr = $arData['XMLTZone'];
					//$arData['XMLTZone'] = new CDataXML();
					//$arData['XMLTZone']->LoadString($xmlstr);

	 				$xmlstr = $arData['RecurrenceData'];
					$obRecurData = new CDataXML();
					$obRecurData->LoadString($xmlstr);

/*
<recurrence>
		<rule>
			<firstDayOfWeek>mo</firstDayOfWeek>
			<repeat>
				<weekly mo='TRUE' tu='TRUE' th='TRUE' sa='TRUE' weekFrequency='1' />
			</repeat>
			<repeatForever>FALSE</repeatForever>
		</rule>
</recurrence>
<deleteExceptions>true</deleteExceptions>
*/


					$obRecurRule = $obRecurData->tree->children[0]->children[0];
					$obRecurRepeat = $obRecurRule->children[1];
					$obNode = $obRecurRepeat->children[0];

					$arData['RecurrenceData'] = array();

					switch($obNode->name)
					{
						case 'daily':
							// hack. we have no "work days" daily recurence
							if ($obNode->getAttribute('weekday') == 'TRUE')
							{
								$arData['RecurrenceData']['PERIOD_TYPE'] = 'WEEKLY';
								$arData['RecurrenceData']['PERIOD_ADDITIONAL'] = '0,1,2,3,4';

								$arData['RecurrenceData']['PERIOD_COUNT'] = 1;
							}
							else
							{
								$arData['RecurrenceData']['PERIOD_TYPE'] = 'DAILY';
								$arData['RecurrenceData']['PERIOD_COUNT'] = $obNode->getAttribute('dayFrequency');
							}

							$time_end = strtotime(date(date('Y-m-d', $arData['EventDate']).' H:i:s', $arData['EndDate']));

							$arData['RecurrenceData']['EVENT_LENGTH'] = $time_end - $arData['EventDate'];
						break;

						case 'weekly':
							$arData['RecurrenceData']['PERIOD_TYPE'] = 'WEEKLY';
							$arData['RecurrenceData']['PERIOD_ADDITIONAL'] = '';
							foreach ($arWeekDays as $day => $value)
							{
								if ($obNode->getAttribute($day))
									$arData['RecurrenceData']['PERIOD_ADDITIONAL'] .= ($arData['RecurrenceData']['PERIOD_ADDITIONAL'] == '' ? '' : ',').$value;
							}

							$arData['RecurrenceData']['PERIOD_COUNT'] = $obNode->getAttribute('weekFrequency');

							$time_end = strtotime(date(date('Y-m-d', $arData['EventDate']).' H:i:s', $arData['EndDate']));

							$arData['RecurrenceData']['EVENT_LENGTH'] = $time_end - $arData['EventDate'];

						break;

						case 'monthly':
							$arData['RecurrenceData']['PERIOD_TYPE'] = 'MONTHLY';
							$arData['RecurrenceData']['PERIOD_COUNT'] = $obNode->getAttribute('monthFrequency');

							$time_end = strtotime(date(date('Y-m', $arData['EventDate']).'-d H:i:s', $arData['EndDate']));

							$arData['RecurrenceData']['EVENT_LENGTH'] = $time_end - $arData['EventDate'];
						break;

						case 'yearly':
							$arData['RecurrenceData']['PERIOD_TYPE'] = 'YEARLY';
							$arData['RecurrenceData']['PERIOD_COUNT'] = $obNode->getAttribute('yearFrequency');

							$time_end = strtotime(date(date('Y', $arData['EventDate']).'-m-d H:i:s', $arData['EndDate']));

							$arData['RecurrenceData']['EVENT_LENGTH'] = $time_end - $arData['EventDate'];
						break;
					}

					$obWhile = $obRule->children[2];
					if ($obWhile->name == 'repeatForever')
						$arData['EndDate'] = MakeTimeStamp('');
					elseif ($obWhile->name == 'windowEnd')
						$arData['EndDate'] = $this->__makeTS($obWhile->textContent());
				}

				$TZBias = $arData['fAllDayEvent'] ? 0 : $TZBias;

				$arData['EventDate'] += $TZBias;
				$arData['EndDate'] += $TZBias;

				$arRes = array(
					'iblockId' => $obCalendar->iblockId,
					'ownerType' => $obCalendar->ownerType,
					'ownerId' => $obCalendar->ownerId,
					'RMiblockId' => $this->__getRMIblockID(),
					'allowResMeeting' => true,
					'bNew' => $arData['_command'] == 'New',
					'fullUrl' => $obCalendar->fullUrl,
					'userId' => $obCalendar->userId,
					'pathToUserCalendar' => $obCalendar->pathToUserCalendar,
					'pathToGroupCalendar' => $obCalendar->pathToGroupCalendar,
					'userIblockId' => $obCalendar->userIblockId,
					'calendarId' => $arSection['ID'],
					'sectionId' => $arSection['IBLOCK_SECTION_ID'],
					'dateFrom' => ConvertTimeStamp($arData['EventDate'], 'FULL'),
					'dateTo' => ConvertTimeStamp($arData['EndDate'], 'FULL'),
					'name' => $arData['Title'],
					'desc' => $arData['Description'],
					'prop' => array(
						'ACCESSIBILITY' => $arStatusValues[$arData['MetaInfo_BusyStatus']],
						'IMPORTANCE' => $arPriorityValues[$arData['MetaInfo_Priority']],
					),
					'notDisplayCalendar' => true,
					'location' => array(
						'new' => CEventCalendar::ParseLocation($arData['Location']),
					),
				);

				if ($arData['fRecurrence'])
				{
					$arRes['prop']['PERIOD_TYPE'] = $arData['RecurrenceData']['PERIOD_TYPE'];
					$arRes['prop']['PERIOD_COUNT'] = $arData['RecurrenceData']['PERIOD_COUNT'];
					$arRes['prop']['EVENT_LENGTH'] = $arData['RecurrenceData']['EVENT_LENGTH'];
					$arRes['prop']['PERIOD_ADDITIONAL'] = $arData['RecurrenceData']['PERIOD_ADDITIONAL'];
				}

				if ($arData['_command'] == 'New')
					$arRes['bNew'] = true;
				else
					$arRes['id'] = $arData['ID'];

				if (!$arRes['bNew'])
				{
					if ($arOldEvent = $obCalendar->GetEvents(array(
						'ownerType' => 'USER',
						'ownerId' => $arSection['CREATED_BY'],
						'bOwner' => true,
						'iblockId' => $arSection['IBLOCK_ID'],
						'sectionId' => $arSection['ID'],
						'eventId' => $arRes['id'],
						'arCalendarIds' => array($arSection['ID']),
						'forExport' => true
					)))
					{
						$arOldEvent = $arOldEvent[0];

						$arRes['prop']['VERSION'] = $arOldEvent['VERSION'];

						if ($arOldEvent['LOCATION']);
						{
							$arRes['location']['old'] = CEventCalendar::ParseLocation($arOldEvent['LOCATION']);
							if ($arRes['location']['old']['mrid'])
							{
								$arRes['location']['new'] = 'ECMR_'.$arRes['location']['old']['mrid'];
								$arRes['prop']['VERSION']++;
							}
						}

						$bMaster = true;
						if (is_array($arOldEvent['GUESTS']) && count($arOldEvent['GUESTS']) > 0)
						{
							$arRes['GUESTS'] = array();

							foreach ($arOldEvent['GUESTS'] as $arGuest)
								$arRes['GUESTS'][] = $arGuest['id'];
						}

						if (is_array($arOldEvent['HOST']))
						{
							$bMaster = false;

							$arRes['prop']['PARENT'] = $arOldEvent['HOST']['parentId'];
						}

						if (!$bMaster)
						{
							$arRes['name'] = $arOldEvent['NAME'];
							$arRes['desc'] = $arOldEvent['DETAIL_TEXT'];
							$arRes['dateFrom'] = $arOldEvent['DATE_FROM'];
							$arRes['dateTo'] = $arOldEvent['DATE_TO'];

							if (is_array($arOldEvent['PERIOD']))
							{
								$arRes['prop']['PERIOD_TYPE'] = $arOldEvent['PERIOD']['TYPE'];
								$arRes['prop']['PERIOD_COUNT'] = $arOldEvent['PERIOD']['COUNT'];
								$arRes['prop']['EVENT_LENGTH'] = $arOldEvent['PERIOD']['LENGTH'];
								$arRes['prop']['PERIOD_ADDITIONAL'] = $arOldEvent['PERIOD']['DAYS'];
							}
							else
							{
								unset($arRes['prop']['PERIOD_TYPE']);
								unset($arRes['prop']['PERIOD_COUNT']);
								unset($arRes['prop']['EVENT_LENGTH']);
								unset($arRes['prop']['PERIOD_ADDITIONAL']);
							}
						}
						else
						{
							if (is_array($arOldEvent['PERIOD']) && !$arData['RecurrenceData'] && $arData['EventType'] == 1)
							{
								$arRes['dateFrom'] = $arOldEvent['DATE_FROM'];
								$arRes['dateTo'] = $arOldEvent['DATE_TO'];
								$arRes['prop']['PERIOD_TYPE'] = $arOldEvent['PERIOD']['TYPE'];
								$arRes['prop']['PERIOD_COUNT'] = $arOldEvent['PERIOD']['COUNT'];
								$arRes['prop']['EVENT_LENGTH'] = $arOldEvent['PERIOD']['LENGTH'];
								$arRes['prop']['PERIOD_ADDITIONAL'] = $arOldEvent['PERIOD']['DAYS'];
							}
						}
					}
					else
					{
						return new CSoapFault(
							'Event not found',
							'Event '.$arRes['id'].' not found on server'
						);
					}
				}

				if (is_array($arRes['location']['old'])) $arRes['location']['old'] = $arRes['location']['old']['str'];
				if (is_array($arRes['location']['new'])) $arRes['location']['new'] = $arRes['location']['new']['str'];

				if ($EventID = $obCalendar->SaveEvent($arRes))
				{
					CEventCalendar::ClearCache('/event_calendar/events/'.$arRes['iblockId'].'/');

					// dirty hack
					$arReplicationIDs[$EventID] = $arData['MetaInfo_ReplicationID'];

					$arResponseRows[$EventID] = new CXMLCreator('Result');
					$arResponseRows[$EventID]->setAttribute('ID', $obMethod->getAttribute('ID').','.$arData['_command']);
					$arResponseRows[$EventID]->setAttribute('List', $listName);

					$arResponseRows[$EventID]->addChild($obNode = new CXMLCreator('ErrorCode'));
					$obNode->setData('0x00000000');
					//$arResponseRows[$EventID]->setAttribute('Version', 3);
				}
			}
		}

		$arMethodParams = array(
			'ownerType' => 'USER',
			'ownerId' => (is_object($USER) && $USER->GetID()) ? $USER->GetID() : false,
			'iblockId' => $arSection['IBLOCK_ID'],
			'sectionId' => $arSection['ID'],
			'arCalendarIds' => array($arSection['ID']),
			'forExport' => true,
			'bLoadAll' => false,
			'timestampFrom' => $dateStart,
		);

		$arCalendarEntries = $obCalendar->GetEvents($arMethodParams);

		foreach ($arCalendarEntries as $arEntry)
		{
			if ($arResponseRows[$arEntry['ID']])
			{
				$obRow = $this->__getRow($arEntry, $listName, $last_change = 0);
				$obRow->setAttribute('xmlns:z', "#RowsetSchema");
				if ($arReplicationIDs[$arEntry['ID']])
					$obRow->setAttribute('MetaInfo_ReplicationID', $arReplicationIDs[$arEntry['ID']]);

				$arResponseRows[$arEntry['ID']]->addChild($obRow);
			}

			$obResponse->addChild($arResponseRows[$arEntry['ID']]);
		}

		return array('UpdateListItemsResult' => $obResponse);
	}

	function GetWebServiceDesc()
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = "bitrix.webservice.intranet.calendar";
		$wsdesc->wsclassname = "CIntranetCalendarWS";
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();

		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();

		$wsdesc->classes = array(
			"CIntranetCalendarWS" => array(
				"GetList" => array(
					"type"		=> "public",
					"name"		=> "GetList",
					"input"		=> array(
						"listName" => array("varType" => "string"),
					),
					"output"	=> array(
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