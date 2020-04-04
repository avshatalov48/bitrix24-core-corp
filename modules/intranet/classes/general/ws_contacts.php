<?
if (!CModule::IncludeModule('webservice'))
	return;

class CIntranetContactsWS extends IWebService
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

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPEID" ColName="tp_ContentTypeId" Sealed="TRUE" Hidden="TRUE" RowOrdinal="0" ReadOnly="TRUE" Type="ContentTypeId" Name="ContentTypeId" DisplaceOnUpgrade="TRUE" DisplayName="Content Type ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentTypeId" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ID" ColName="tp_ID" RowOrdinal="0" ReadOnly="TRUE" Type="Counter" Name="ID" PrimaryKey="TRUE" DisplayName="ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ID" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ATTACHMENTS" ColName="tp_HasAttachment" RowOrdinal="0" Type="Attachments" Name="Attachments" DisplayName="Attachments" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Attachments" FromBaseType="TRUE"'));

		//<Field ID="{67df98f4-9dec-48ff-a553-29bece9c5bf4}" ColName="tp_HasAttachment" RowOrdinal="0" Type="Attachments" Name="Attachments" DisplayName="Attachments" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Attachments" FromBaseType="TRUE"/>

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="TITLE" Type="Text" Name="Title" Sealed="TRUE" DisplayName="Last Name" Required="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Title" FromBaseType="TRUE" ColName="nvarchar1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FIRSTNAME" Name="FirstName" DisplayName="First Name" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FirstName" ColName="nvarchar4"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FULLNAME" Name="FullName" DisplayName="Full Name" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FullName" ColName="nvarchar6"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EMAIL" Name="Email" DisplayName="E-mail Address" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Email" ColName="nvarchar7"'));

		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="BCPICTURE" Name="BCPicture" DisplayName="BCPicture" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="BCPicture" ColName="bcpicture" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PHOTO" Name="Photo" DisplayName="Photo" Type="URL" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Photo" ColName="PERSONAL_PHOTO" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="COMPANY" Name="Company" DisplayName="Company" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Company" ColName="nvarchar8"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="JOBTITLE" Name="JobTitle" DisplayName="Job Title" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="JobTitle" ColName="nvarchar10"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DEPARTMENT" Name="ol_Department" DisplayName="Department" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ol_Department" ColName="nvarchar100"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKPHONE" Name="WorkPhone" DisplayName="Business Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkPhone" ColName="nvarchar11"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="HOMEPHONE" Name="HomePhone" DisplayName="Home Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="HomePhone" ColName="nvarchar12"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="MOBILEPHONE" Name="CellPhone" DisplayName="Mobile Phone" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="CellPhone" ColName="nvarchar13"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKFAX" Name="WorkFax" DisplayName="Fax Number" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkFax" ColName="nvarchar14"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKADDRESS" Name="WorkAddress" DisplayName="Address" Type="Note" NumLines="2" Sortable="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkAddress" ColName="ntext2"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKCITY" Name="WorkCity" DisplayName="City" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkCity" ColName="nvarchar15"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKSTATE" Name="WorkState" DisplayName="State/Province" Type="Text" NumLines="2" Sortable="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkState" ColName="nvarchar16"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKZIP" Name="WorkZip" DisplayName="ZIP/Postal Code" IMEMode="inactive" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkZip" ColName="nvarchar17"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WORKCOUNTRY" Name="WorkCountry" DisplayName="Country/Region" Type="Text" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WorkCountry" ColName="nvarchar18"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="WEBPAGE" Name="WebPage" DisplayName="Web Page" Type="URL" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="WebPage" ColName="nvarchar19" ColName2="nvarchar20"'));

		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPE" ColName="tp_ContentType" RowOrdinal="0" ReadOnly="TRUE" Type="Text" Name="ContentType" DisplaceOnUpgrade="TRUE" DisplayName="Content Type" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentType" FromBaseType="TRUE" PITarget="MicrosoftWindowsSharePointServices" PIAttribute="ContentTypeID"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="MODIFIED" ColName="tp_Modified" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Modified" DisplayName="Modified" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Modified" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CREATED" ColName="tp_Created" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Created" DisplayName="Created" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Created" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="AUTHOR" ColName="tp_Author" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Author" DisplayName="Created By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Author" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EDITOR" ColName="tp_Editor" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Editor" DisplayName="Modified By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Editor" FromBaseType="TRUE"'));

		// ******************* //
		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="OWSHIDDENVERSION" ColName="tp_Version" RowOrdinal="0" Hidden="TRUE" ReadOnly="TRUE" Type="Integer" SetAs="owshiddenversion" Name="owshiddenversion" DisplayName="owshiddenversion" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="owshiddenversion" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FSOBJTYPE" Name="FSObjType" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Item Type" List="Docs" FieldRef="ID" ShowField="FSType" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FSObjType" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PERMMASK" Name="PermMask" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" RenderXMLUsingPattern="TRUE" ShowInFileDlg="FALSE" Type="Computed" DisplayName="Effective Permissions Mask" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="PermMask" FromBaseType="TRUE"'));
		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="ID"'));

		$obField->addChild($obDisplayPattern = new CXMLCreator('DisplayPattern'));
		$obDisplayPattern->addChild(new CXMLCreator('CurrentRights'));


		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="UNIQUEID" Name="UniqueId" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Unique Id" List="Docs" FieldRef="ID" ShowField="UniqueId" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="UniqueId" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="METAINFO" Name="MetaInfo" DisplaceOnUpgrade="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Property Bag" List="Docs" FieldRef="ID" ShowField="MetaInfo" JoinColName="DoclibRowId" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="MetaInfo" FromBaseType="TRUE"'));

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

	function GetList($listName)
	{
		global $APPLICATION;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault(
				'Data error',
				'Wrong GUID - '.$listName
			);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		/*
		$obRes = CIBlockSection::GetList(array('SORT' => 'ASC'), array('XML_ID' => $listName_original));
		if (!$arSection = $obRes->Fetch())
		{
			return new CSoapFault(
				'List not found',
				'List with '.$listName.' GUID not found'
			);
		}
		*/

		//$dbAuthor = CUser::GetByID($arSection['CREATED_BY']);
		//$arAuthor = $dbAuthor->Fetch();

		$data = new CXMLCreator('List');
		$data->setAttribute('ID', $listName);
		$data->setAttribute('Name', $listName);
		//$data->setAttribute('Title', $arSection['NAME']);
		if (defined("IS_EXTRANET") && IS_EXTRANET == "Y" && defined("IS_EMPLOYEES") && IS_EMPLOYEES == "Y")
			$data->setAttribute('Title', GetMessage('INTR_OUTLOOK_TITLE_CONTACTS'));
		elseif (defined("IS_EXTRANET") && IS_EXTRANET == "Y")
			$data->setAttribute('Title', GetMessage('INTR_OUTLOOK_TITLE_CONTACTS_EXTRANET'));
		else
			$data->setAttribute('Title', GetMessage('INTR_OUTLOOK_TITLE_CONTACTS'));

		$data->setAttribute('Created', date('Ymd H:i:s'));
		$data->setAttribute('Modified', date('Ymd H:i:s'));
		$data->setAttribute('Direction', 'none'); // RTL, LTR

		$data->setAttribute('ReadSecurity', '2');
		$data->setAttribute('WriteSecurity', '2');

		$data->setAttribute('Author', '1;#admin');

		$data->setAttribute('EnableAttachments', 'True');

		// it's strange and awful but this thing doesn't work at outlook.
		// he always make 2 additional hits: GetAttachmentCollection and direct attachment call, independently from this settings
		//$data->setAttribute('IncludeAttachmentUrls', 'True');
		//$data->setAttribute('IncludeAttachmentVersion', 'False');

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

		$obNode->addChild(CXMLCreator::createTagAttributed('ServerVersion', '14.0.4762.1000'));
		$obNode->addChild(CXMLCreator::createTagAttributed('RecycleBinEnabled', 'False'));
		$obNode->addChild(CXMLCreator::createTagAttributed('ServerRelativeUrl', '/company/'));

		return array('GetListResult' => $data);
	}

	function __getRow($arRes, $listName, &$last_change)
	{
		global $APPLICATION, $USER;

		$change = MakeTimeStamp($arRes['TIMESTAMP_X']);

		if ($last_change < $change)
			$last_change = $change;

		$obRow = new CXMLCreator('z:row');
		$obRow->setAttribute('ows_ID', $arRes['ID']);

		$version = $arRes['VERSION'] ? $arRes['VERSION'] : 1;

		if ($this->bGetImages && $arRes['PERSONAL_PHOTO'] > 0)
		{
			$arImage = self::InitImage($arRes['PERSONAL_PHOTO'], 100, 100);

			$obRow->setAttribute('ows_Attachments', ';#'.CHTTP::URN2URI($arImage['CACHE']['src']).';#'.CIntranetUtils::makeGUID(md5($arRes['PERSONAL_PHOTO'])).',1;#');
			$obRow->setAttribute('ows_MetaInfo_AttachProps', '<File Photo="-1">'.$arImage['FILE']['FILE_NAME'].'</File>');
		}
		else
		{
			$obRow->setAttribute('ows_Attachments', 0);
		}

		$obRow->setAttribute('ows_owshiddenversion', $version);
		//$obRow->setAttribute('ows_MetaInfo_vti_versionhistory', md5($arRes['ID']).':'.$version);

		$obRow->setAttribute('ows_Created', $this->__makeDateTime(MakeTimeStamp($arRes['DATE_REGISTER'])));
		$obRow->setAttribute('ows_Modified', $this->__makeDateTime($change));

		$obRow->setAttribute('ows_Title', $arRes['LAST_NAME']);
		$obRow->setAttribute('ows_FirstName', $arRes['NAME']);

		$obRow->setAttribute('ows_FullName', $arRes['NAME'].' '.$arRes['SECOND_NAME'].' '.$arRes['LAST_NAME']);

		$obRow->setAttribute('ows_Email', $arRes['EMAIL']);

		$obRow->setAttribute('ows_UniqueId', $arRes['ID'].';#'.$listName);
		$obRow->setAttribute('ows_FSObjType', $arRes['ID'].';#0');

		$obRow->setAttribute('ows_Company', $arRes['WORK_COMPANY']);
		$obRow->setAttribute('ows_JobTitle', $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION']);

		$obRow->setAttribute('ows_ol_Department', $arRes['UF_DEPARTMENT'] ? $arRes['UF_DEPARTMENT'] : '');

		$obRow->setAttribute('ows_WorkPhone', $arRes['WORK_PHONE']);
		$obRow->setAttribute('ows_HomePhone', $arRes['PERSONAL_PHONE']);
		$obRow->setAttribute('ows_CellPhone', $arRes['PERSONAL_MOBILE']);

		$obRow->setAttribute('ows_WorkFax', $arRes['WORK_FAX']);

		$obRow->setAttribute('ows_WorkAddress', $arRes['WORK_STREET']);
		$obRow->setAttribute('ows_WorkCity', $arRes['WORK_CITY']);
		$obRow->setAttribute('ows_WorkState', $arRes['WORK_STATE']);
		$obRow->setAttribute('ows_WorkZip', $arRes['WORK_ZIP']);

		$obRow->setAttribute('ows_PermMask', '0x7fffffffffffffff');
		$obRow->setAttribute('ows_ContentTypeId', '0x010600BAAFA34998B23642B33F6D26E30D55EF');

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
		global $USER;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		if (
			!$USER->IsAdmin()
			&& (
				!defined("IS_EXTRANET")
				|| IS_EXTRANET != "Y"
			)
		) // intranet
		{
			$rsUsers = CUser::GetList(
				($by="id"), ($order="asc"),
				array(
					"ID" => $USER->GetID()
				),
				array("SELECT" => array("UF_DEPARTMENT"))
			);

			$bUserIntranet = false;
			if ($arUser = $rsUsers->Fetch())
			{
				if (
					!empty($arUser["UF_DEPARTMENT"])
					&& !empty($arUser["UF_DEPARTMENT"][0])
					&& intval($arUser["UF_DEPARTMENT"][0]) > 0
				)
				{
					$bUserIntranet = true;
				}
			}

			if (!$bUserIntranet)
			{
				return new CSoapFault('Data error', 'User has no permissions to read intranet contacts');
			}
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$arFilter = array('ACTIVE' => 'Y');

		$page = 1;
		$bUpdateFields = false;

		$tsLastFieldsChange = COption::GetOptionString('intranet', 'ws_contacts_last_fields_change', false);
		$this->bGetImages = COption::GetOptionString('intranet', 'ws_contacts_get_images', 'Y') == 'Y';

		if (strlen($changeToken) > 0)
		{
			if ($pos = strpos($changeToken, ';'))
			{
				list($newChangeToken, $page, $last_change) = explode(';', $changeToken);

				$page++;
				$changeToken = $newChangeToken;
			}

			$arFilter['TIMESTAMP_X_1'] = ConvertTimeStamp($changeToken, 'FULL');
			if (!$arFilter['TIMESTAMP_X_1'])
			{
				return new CSoapFault(
					'Params error',
					'Wrong changeToken: '.$changeToken
				);
			}

			if ($tsLastFieldsChange !== false && $tsLastFieldsChange > $changeToken)
			{
				$bUpdateFields = true;
			}
		}

		if (
			!CModule::IncludeModule('extranet')
			|| (
				defined("IS_EXTRANET")
				&& IS_EXTRANET == "Y"
				&& defined("IS_EMPLOYEES")
				&& IS_EMPLOYEES == "Y"
			)
			|| (
				!defined("IS_EXTRANET")
				|| IS_EXTRANET != "Y"
			)
		)
		{
			$arFilter["!UF_DEPARTMENT"] = false;
		}
		else
		{
			$arFilter["UF_DEPARTMENT"] = false;
		}

		if (
			defined("IS_EXTRANET")
			&& IS_EXTRANET == "Y"
			&& CModule::IncludeModule('extranet')
			&& !CExtranet::IsExtranetAdmin()
		)
		{
			$currentUserId = $USER->getId();

			$arIDs = array_merge(CExtranet::GetMyGroupsUsers(CExtranet::GetExtranetSiteID()), CExtranet::GetPublicUsers());
			$arIDs = array_filter($arIDs, function ($var) use ($currentUserId) { return($var != $currentUserId); });
			$arFilter['ID'] = implode('|', array_unique($arIDs));
		}

// it's needed to check is current user an intranet user if he requested intranet script

		$arListParams = array(
			"SELECT" => array("UF_DEPARTMENT")
		);

		if ($rowLimit > 0)
			$arListParams["NAV_PARAMS"] = array(
				"nPageSize" => $rowLimit,
				"bShowAll" => false,
				"iNumPage" => $page
			);

		$obUsers = CUser::GetList(
			$by='id', $order='asc',
			$arFilter,
			$arListParams
		);

		if (!isset($last_change))
			$last_change = 0;

		$data = new CXMLCreator('listitems');
		$data->setAttribute('MinTimeBetweenSyncs', 0);
		$data->setAttribute('RecommendedTimeBetweenSyncs', 180);
		$data->setAttribute('TimeStamp', $this->__makeDateTime());
		$data->setAttribute('EffectivePermMask', 'FullMask');

		$data->addChild($obChanges = new CXMLCreator('Changes'));

		if ((!$changeToken || $bUpdateFields) && $page <= 1)
		{
			$arGetListResult = $this->GetList($listName);
			$obChanges->addChild($arGetListResult['GetListResult']);
		}

		//TODO: the next thing is to do something like this for all deleted events.
		//$obChanges->addChild($obId = new CXMLCreator('Id'));
		//$obId->setAttribute('ChangeType', 'Delete');
		//$obId->setData('702');
		//<Id ChangeType="Delete">14</Id>

		$data->addChild($obData = new CXMLCreator('rs:data'));

		$counter = 0;

		if (
			CModule::IncludeModule('extranet')
			&& defined("IS_EXTRANET")
			&& IS_EXTRANET == "Y"
		)
		{
			$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(CExtranet::GetExtranetSiteID());
			$arPublicUsersID = CExtranet::GetPublicUsers();
		}

		while ($arUser = $obUsers->Fetch())
		{
			if (
				is_array($arUser['UF_DEPARTMENT'])
				&& count($arUser['UF_DEPARTMENT']) > 0
			)
			{
				if ($this->arDepartmentsCache[$arUser['UF_DEPARTMENT'][0]])
				{
					$arUser['WORK_COMPANY'] = $this->arDepartmentsTopCache[$arUser['UF_DEPARTMENT'][0]];
					$arUser['UF_DEPARTMENT'] = $this->arDepartmentsCache[$arUser['UF_DEPARTMENT'][0]];
				}
				else
				{
					$dbRes = CIBlockSection::GetByID($arUser['UF_DEPARTMENT'][0]);
					if ($arRes = $dbRes->Fetch())
					{
						if ((!defined("IS_EXTRANET") || IS_EXTRANET != "Y") || (defined("IS_EMPLOYEES") && IS_EMPLOYEES == "Y"))
						{
							$arUser['DEPARTMENT'] = $this->arDepartmentsCache[$arUser['UF_DEPARTMENT'][0]] = $arRes['NAME'];
						}
						if ($top_section = CIntranetUtils::GetIBlockTopSection($arUser['UF_DEPARTMENT']))
						{
							$dbRes = CIBlockSection::GetByID($top_section);
							if ($arRes = $dbRes->Fetch())
							{
								$arUser['WORK_COMPANY'] = $this->arDepartmentsTopCache[$arUser['UF_DEPARTMENT'][0]] = $arRes['NAME'];
							}
						}

						if ((!defined("IS_EXTRANET") || IS_EXTRANET != "Y") || (defined("IS_EMPLOYEES") && IS_EMPLOYEES == "Y"))
							$arUser['UF_DEPARTMENT'] = $arUser['DEPARTMENT'];

					}
				}
			}

			$counter++;
			$obData->addChild($this->__getRow($arUser, $listName, $last_change));
		}

		//$last_change = time();
		$obData->setAttribute('ItemCount', $counter);

		$data->setAttribute('xmlns:rs', 'urn:schemas-microsoft-com:rowset');
		$data->setAttribute('xmlns:z', '#RowsetSchema');

		if ($bUpdateFields && $tsLastFieldsChange)
		{
			$last_change = $tsLastFieldsChange;
		}

		if ($last_change > 0)
		{
			if ($rowLimit && $obUsers->NavPageCount > 1 && $obUsers->NavPageCount > $page)
			{
				$last_change = intval($changeToken).';'.$page.';'.$last_change;
				$obChanges->setAttribute('MoreChanges', 'TRUE');
			}
			else
			{
				$last_change += 1;
			}

			$obChanges->setAttribute('LastChangeToken', $last_change);
		}

		return array('GetListItemChangesSinceTokenResult' => $data);
	}

	function GetAttachmentCollection($listName, $listItemID)
	{
		$start = microtime(true);

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));
		$listItemID = intval($listItemID);

		$dbRes = CUser::GetByID($listItemID);
		//$dbRes = CUser::GetList(($by="id"), ($order="asc"), array("ID_EQUAL_EXACT"=>IntVal($listItemID)));
		//$dbRes = $GLOBALS['DB']->Query('SELECT PERSONAL_PHOTO FROM b_user WHERE id=\''.$listItemID.'\'');

		$obData = new CXMLCreator('Attachments');

		if (($arUser = $dbRes->Fetch()) && $arUser['PERSONAL_PHOTO'])
		{
			$arImage = self::InitImage($arUser['PERSONAL_PHOTO'], 100, 100);
			$obData->addChild($obAttachment = new CXMLCreator('Attachment'));
			$obAttachment->setData(CHTTP::URN2URI($arImage['CACHE']['src']));

			//$data = '<Attachments><Attachment>http://'.$_SERVER['SERVER_NAME'].$arImage['CACHE']['src'].'</Attachment></Attachments>';
		}
		// else
		// {
			// $data = '<Attachments></Attachments>';
		// }

		//return array('GetAttachmentCollectionResult' => $data);
		return array('GetAttachmentCollectionResult' => $obData);
	}

	function GetWebServiceDesc()
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = "bitrix.webservice.intranet.contacts";
		$wsdesc->wsclassname = "CIntranetContactsWS";
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();

		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();

		$wsdesc->classes = array(
			"CIntranetContactsWS" => array(
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
			),
		);
		return $wsdesc;
	}

	protected function InitImage($imageID, $imageWidth, $imageHeight = 0)
	{
		$imageFile = false;
		$imageImg = "";

		if(($imageWidth = intval($imageWidth)) <= 0) $imageWidth = 100;
		if(($imageHeight = intval($imageHeight)) <= 0) $imageHeight = $imageWidth;

		$imageID = intval($imageID);

		if($imageID > 0)
		{
			$imageFile = CFile::GetFileArray($imageID);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $imageWidth, "height" => $imageHeight),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false, false, true
				);
				$imageImg = CFile::ShowImage($arFileTmp["src"], $imageWidth, $imageHeight, "border=0", "");
			}
		}

		return array("FILE" => $imageFile, "CACHE" => $arFileTmp, "IMG" => $imageImg);
	}

}
?>