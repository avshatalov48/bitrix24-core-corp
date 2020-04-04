<?
class CDavPrincipalsHandler 
	extends CDavGroupdavHandler
{
	public function __construct($groupdav, $app)
	{
		parent::__construct($groupdav, $app);
	}

	public function GetCollectionProperties(CDavResource $resource, $siteId, $account = null, $currentApplication = null, $arPath = null, $options = 0)
	{
		if ($currentApplication == "principals")
		{
			$resource->AddProperty('resourcetype',
				array(
					array('collection', ''),
					array('principal', '', CDavGroupDav::DAV),
				)
			);
		}
	}

	public function CheckPrivilegesByPath($testPrivileges, $principal, $siteId, $account, $arPath)
	{
		return $this->CheckPrivileges($testPrivileges, $principal, 0);
	}

	public function CheckPrivileges($testPrivileges, $principal, $collectionId)
	{
		if (is_object($principal) && ($principal instanceof CDavPrincipal))
			$principal = $principal->Id();

		if (!is_numeric($principal))
			return false;

		$principal = IntVal($principal);

		return ($testPrivileges == "DAV::read");
	}

	public function GetHomeCollectionUrl($siteId, $account, $arPath)
	{
		if (is_null($siteId))
			return "";

		return "/principals/";
	}

	protected function GetMethodMinimumPrivilege($method)
	{
		return 'DAV:read';
	}

	public function Propfind(&$arResources, $siteId, $account, $arPath, $id = null)
	{
		$request = $this->groupdav->GetRequest();
		$currentPrincipal = $request->GetPrincipal();

		if (!$this->CheckPrivileges('DAV::read', $currentPrincipal, 0))
			return '403 Forbidden';

		$requestDocument = $request->GetXmlDocument();

		if ($requestDocument->GetRoot() && $requestDocument->GetRoot()->GetTag() != 'propfind')
			return '501 Not Implemented';

		if (!is_null($arPath) && count($arPath) > 0)
		{
			if (isset($arPath[1]))
			{
				$u = CDavAccount::GetAccountByName($arPath[1]);
				$account = array($u["TYPE"], $u["ID"]);
			}

			switch ($arPath[0])
			{
				case 'user':
					$res = $this->PropfindUsers($arResources, $siteId, $account, $arPath, $id, $request->GetDepth());
					break;
				case 'group':
					$res = $this->PropfindGroups($arResources, $siteId, $account, $arPath, $id, $request->GetDepth());
					break;
				default:
					return '404 Not Found';
			}
		}
		else
		{
			$res = $this->PropfindPrincipals($arResources, $siteId);
		}

		if ($res !== true)
			return $res;

		return true;
	}

	protected function PropfindPrincipals(&$arResources, $siteId)
	{
		$request = $this->groupdav->GetRequest();

		$resource = new CDavResource("/principals/");
		$resource->AddProperty('current-user-principal', array('href', $request->GetPrincipalUrl()));
		$resource->AddProperty('resourcetype',
			array(
				array('collection', ''),
				array('principal', '', CDavGroupDav::DAV),
			)
		);
		$arResources[] = $resource;

		if ($request->GetDepth())
		{
			$this->PropfindUsers($arResources, $siteId, null, null, null, 0);
			$this->PropfindGroups($arResources, $siteId, null, null, null, 0);
		}

		return true;
	}

	protected function PropfindGroups(&$arResources, $siteId, $account, $arPath, $id = null, $depth = 0)
	{
		$request = $this->groupdav->GetRequest();

		if (is_null($account))
		{
			$resource = new CDavResource("/principals/group/");
			$resource->AddProperty('current-user-principal', array('href', $request->GetPrincipalUrl()));
			$resource->AddProperty('resourcetype',
				array(
					array('collection', ''),
					array('principal', '', CDavGroupDav::DAV),
				)
			);
			$arResources[] = $resource;

			if ($depth)
			{
				$arGroups = CDavAccount::GetAccountsList("group");
				foreach ($arGroups as $v)
					$this->AddGroup($arResources, $siteId, $v);
			}
		}
		else
		{
			$arGroup = CDavAccount::GetAccountById($account);
			if (!$arGroup)
				return '404 Not Found';

			$this->AddGroup($arResources, $siteId, $arGroup);
		}
		return true;		
	}

	protected function PropfindUsers(&$arResources, $siteId, $account, $arPath, $id = null, $depth = 0)
	{
		$request = $this->groupdav->GetRequest();

		if (is_null($account))
		{
			$resource = new CDavResource("/principals/user/");
			$resource->AddProperty('current-user-principal', array('href', $request->GetPrincipalUrl()));
			$resource->AddProperty('resourcetype',
				array(
					array('collection', ''),
					array('principal', '', CDavGroupDav::DAV),
				)
			);
			$arResources[] = $resource;

			if ($depth)
			{
				$arUsers = CDavAccount::GetAccountsList("user", array(), array('!UF_DEPARTMENT' => false));
				foreach ($arUsers as $u)
					$this->AddUser($arResources, $siteId, $u);
			}
		}
		else
		{
			$arUser = CDavAccount::GetAccountById($account);
			if (!$arUser)
				return '404 Not Found';

			$this->AddUser($arResources, $siteId, $arUser);
		}
		return true;
	}

	protected function AddUser(&$arResources, $siteId, $arUser)
	{
		$request = $this->groupdav->GetRequest();

		$resource = new CDavResource('/principals/user/'.$arUser['CODE'].'/');
		$resource->AddProperty('displayname', $arUser["NAME"]);
		$resource->AddProperty('getetag', $this->GetETag($arUser));
		$resource->AddProperty('resourcetype', array(array('principal', '', CDavGroupDav::DAV)));
		$resource->AddProperty('alternate-URI-set', array(array('href', 'MAILTO:'.$arUser['EMAIL'])));
		$resource->AddProperty('principal-URL', array(array('href', $request->GetBaseUri().'/principals/user/'.$arUser['CODE'].'/')));
		$resource->AddProperty('calendar-home-set', array(array('href', $request->GetBaseUri()."/".$siteId."/".$arUser["CODE"]."/calendar/")), CDavGroupDav::CALDAV);
		$resource->AddProperty('calendar-user-address-set',
			array(
				array('href', 'MAILTO:'.$arUser['EMAIL']),
				array('href', $request->GetBaseUri().'/principals/user/'.$arUser['CODE'].'/'),
				array('href', 'urn:uuid:'.$arUser['ID'])
			),
			CDavGroupDav::CALDAV
		);
		$resource->AddProperty('schedule-outbox-URL', array(array('href', $request->GetBaseUri()."/".$siteId."/".$arUser["CODE"]."/calendar/", CDavGroupDav::DAV)), CDavGroupDav::CALDAV);
		$resource->AddProperty('email-address-set', array(array('email-address', $arUser['EMAIL'], CDavGroupDav::CALENDARSERVER)), CDavGroupDav::CALENDARSERVER);
		$resource->AddProperty('last-name', $arUser['LAST_NAME'], CDavGroupDav::CALENDARSERVER);
		$resource->AddProperty('first-name', $arUser['FIRST_NAME'], CDavGroupDav::CALENDARSERVER);
		$resource->AddProperty('record-type', 'user', CDavGroupDav::CALENDARSERVER);
		$resource->AddProperty('calendar-user-type', 'INDIVIDUAL', CDavGroupDav::CALDAV);
		$resource->AddProperty('addressbook-home-set', array(array('href', $request->GetBaseUri()."/".$siteId."/".$arUser["CODE"]."/" . "addressbook/")), CDavGroupDav::CARDDAV);
		$resource->AddProperty('supported-report-set',
			array('supported-report',
				array(CDavResource::MakeProp('report', array(CDavResource::MakeProp('acl-principal-prop-set', ''))))
			)
		);

		//$memberships = array();
		//$arUserGroups = CUser::GetUserGroup($arUser["ID"]);
		//foreach ($arUserGroups as $groupId)
		//	$memberships[] = CDavWebDav::MakeProp('href', $this->baseUri.'/principals/groups/'.$groupId);
		//$resource->AddProperty('group-member-ship', $memberships);

		$arResources[] = $resource;
	}

	protected function AddGroup(&$arResources, $siteId, $arGroup)
	{
		$request = $this->groupdav->GetRequest();

		$resource = new CDavResource('/principals/group/'.$arGroup['CODE'].'/');
		$resource->AddProperty('displayname', $arGroup["NAME"]);
		$resource->AddProperty('getetag', $this->GetETag($arGroup));
		$resource->AddProperty('resourcetype', array(array('principal', '', CDavGroupDav::DAV)));
		$resource->AddProperty('alternate-URI-set', '');
		$resource->AddProperty('calendar-home-set', array(array('href', $request->GetBaseUri()."/".$siteId."/".$arGroup["CODE"]."/calendar/")), CDavGroupDav::CALDAV);
		$resource->AddProperty('record-type', 'group', CDavGroupDav::CALENDARSERVER);
		$resource->AddProperty('calendar-user-type', 'GROUP', CDavGroupDav::CALDAV);

		//$resource->AddProperty('group-member-set', $memberships);

		$arResources[] = $resource;
	}

	private function GetETag($arUser)
	{
		if (!is_array($arUser))
			$arUser = $this->Read($arUser);

		return 'BX-'.$arUser['ID'].':'.md5(serialize($arUser));
	}

	protected function Read($id)
	{
		return false;
	}
}
?>