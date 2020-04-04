<?
if (!CModule::IncludeModule("intranet"))
	return;

/**
 * Class CDavAddressbookHandler
 */
class CDavAddressbookHandler
	extends CDavGroupdavHandler
{
	const DEFAULT_COLLECTION_NAME = 'accounts';
	/**
	 * @var CDavAddressbookBase[]
	 */
	private $addressbookCollections = array();

	public function __construct($groupdav, $app)
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->addressbookCollections['crmContacts'] = new CDavCrmContacts($groupdav);
			$this->addressbookCollections['crmCompanies'] = new CDavCrmCompanies($groupdav);
		}

		$this->addressbookCollections['accounts'] = new CDavAccounts($groupdav);

		if (\Bitrix\Main\Loader::includeModule('extranet'))
		{
			$this->addressbookCollections['extranetAccounts'] = new CDavExtranetAccounts($groupdav);
		}

		parent::__construct($groupdav, $app);
	}

	protected function GetMethodMinimumPrivilege($method)
	{
		static $arMethodMinimumPrivilegeMap = array(
			'GET' => 'DAV:read',
			'PUT' => 'DAV:write',
			'DELETE' => 'DAV:write',
		);
		return $arMethodMinimumPrivilegeMap[$method];
	}

	public function GetCollectionId($siteId, $account, $arPath)
	{
		return array($siteId);
	}

	public function GetHomeCollectionUrl($siteId, $account, $arPath)
	{
		if (is_null($siteId))
			return "";

		$url = "/" . $siteId;

		if (is_null($account))
		{
			if (is_null($arPath) || count($arPath) == 0)
				return "";

			return $url . "/addressbook/" . $arPath[0] . "/";
		}

		$arAccount = CDavAccount::GetAccountById($account);

		if (is_null($arAccount))
			return "";

		return $url . "/" . $arAccount["CODE"] . "/addressbook/";
	}

	public function GetCollectionProperties(CDavResource $resource, $siteId, $account = null, $currentApplication = null, $arPath = null, $options = 0)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return false;
		$request = $this->groupdav->GetRequest();
		$currentPrincipal = $request->GetPrincipal();
		$homeUrl = $this->GetHomeCollectionUrl($siteId, $account, $arPath);

		$resource->AddProperty('addressbook-home-set', array('href', $request->GetBaseUri() . $homeUrl), CDavGroupDav::CARDDAV);
		if ($currentApplication == "addressbook")
		{
			if ($this->IsMacAgent())
			{
				if (!$this->GetDefaultResourceProvider()->CheckPrivileges('DAV:read', $currentPrincipal, $collectionId))
					return '403 Access denied';
				$this->GetDefaultResourceProvider()->GetAddressbookProperties($resource, $collectionId, $account, $arPath, $options);
			}
			else
			{
				if (!empty($arPath[0]) && isset($this->addressbookCollections[$arPath[0]]))
				{
					if (!$this->addressbookCollections[$arPath[0]]->CheckPrivileges('DAV:read', $currentPrincipal, $collectionId))
						return '403 Access denied';
					$this->addressbookCollections[$arPath[0]]->GetAddressbookProperties($resource, $collectionId, $account, $arPath, $options);
				}
				else
				{
					$this->GetAddressbookProperties($resource, $collectionId, $account, $arPath, $options);
				}
			}
		}

	}

	public function GetAddressbookProperties(CDavResource $resource, $siteId, $account = null, $arPath = null, $options = 0)
	{
		if ($this->IsMacAgent())
		{
			$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
			$this->GetDefaultResourceProvider()->GetAddressbookProperties($resource, $collectionId, $account, $arPath, $options);

		}
		else
		{
			$resource->AddProperty('resourcetype',
				array(
					array('collection', ''),
				)
			);
		}

	}

	public function Propfind(&$arResources, $siteId, $account, $arPath, $id = null)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);

		$currentPrincipal = $this->groupdav->GetRequest()->GetPrincipal();
		$currentPrincipalId = $currentPrincipal->Id();
		//HACK: maybe it should extract here to higher for all DAV requests
		if ($account[1] !== $currentPrincipalId)
			return '403 Access denied';

		if ($collectionId == null)
			return '404 Not Found';
		$request = $this->groupdav->GetRequest();
		$path = CDav::CheckIfRightSlashAdded($request->GetPath());


		if (!$this->IsMacAgent())
		{
			if (!empty($arPath[0]) && !empty($this->addressbookCollections[$arPath[0]]))
			{
				return $this->addressbookCollections[$arPath[0]]->Propfind($arResources, $collectionId, $account, $arPath, $id);
			}
			elseif (!empty($arPath[0]))
			{
				return '501 Not Implemented';
			}
			elseif (CDav::EndsWith($path, 'addressbook/'))
			{
				foreach ($this->addressbookCollections as $key => $collection)
				{
					if ($collection->CheckPrivileges('DAV:read', $currentPrincipal, $collectionId))
					{
						$resource = new CDavResource($path . $collection->GetUri());
						$resource->AddProperty('resourcetype',
							array(
								array('collection', ''),
								array('vcard-collection', '', CDavGroupDav::GROUPDAV),
								array('addressbook', '', CDavGroupDav::CARDDAV),
							)
						);
						$resource->AddProperty('displayname', $collection->GetName());
						$arResources[] = $resource;
					}
				}
			}
		}
		else
		{
			return $this->GetDefaultResourceProvider()->Propfind($arResources, $collectionId, $account, $arPath, $id);
		}


		return true;
	}

	// return array/boolean array with entry, false if no read rights, null if $id does not exist
	public function Read($collectionId, $id)
	{
		if ($this->IsMacAgent())
		{
			$res = $this->GetDefaultResourceProvider()->Read($collectionId, $id);
		}
		else
		{
			$arPath = explode('/', $this->groupdav->GetRequest()->GetPath());

			if (!empty($this->addressbookCollections[$arPath[4]]))
				$res = $this->addressbookCollections[$arPath[4]]->Read($collectionId, $id);
			else
				$res = false;
		}

		return $res;
	}

	public function Get(&$arResult, $id, $siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return '404 Not Found';

		$oldCard = $this->GetEntry('GET', $id, $collectionId);
		if (is_null($oldCard) || !is_array($oldCard))
			return $oldCard;

		if ($this->IsMacAgent())
		{
			$res = $this->GetDefaultResourceProvider()->Get($collectionId, $oldCard);
		}
		elseif (!empty($this->addressbookCollections[$arPath[0]]))
		{
			$res = $this->addressbookCollections[$arPath[0]]->Get($collectionId, $oldCard);

		}

		if (!empty($res))
		{
			$res['data'] = $this->groupdav->GetResponse()->Encode($res['data']);
			$arResult = $res;
		}
		return true;
	}

	public function Put($id, $siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return '404 Not Found';

		$request = $this->groupdav->GetRequest();

		$oldCard = $this->GetEntry('PUT', $id, $collectionId);
		if (!is_null($oldCard) && !is_array($oldCard))
			return $oldCard;

		$charset = "utf-8";
		$arContentParameters = $request->GetContentParameters();

		if (!empty($arContentParameters['CONTENT_TYPE']))
		{
			$arContentType = explode(';', $arContentParameters['CONTENT_TYPE']);
			if (count($arContentType) > 1)
			{
				array_shift($arContentType);
				foreach ($arContentType as $attribute)
				{
					$attribute = trim($attribute);
					list($key, $value) = explode('=', $attribute);
					if (strtolower($key) == 'charset')
						$charset = strtolower($value);
				}
			}
		}

		$content = $request->GetRequestBody();
		$content = htmlspecialcharsback($content);

		if (is_array($oldCard))
			$contactId = $oldCard['ID'];
		else
			$contactId = 0;

		$cs = CDav::GetCharset($siteId);
		if (is_null($cs) || empty($cs))
			$cs = "utf-8";

		$content = $GLOBALS["APPLICATION"]->ConvertCharset($content, $charset, $cs);

		if ($this->IsMacAgent())
		{
			$card = new CDavICalendarComponent($content);
			$res = $this->GetDefaultResourceProvider()->Put($id, $card);
		}
		else
		{
			if (!empty($this->addressbookCollections[$arPath[0]]))
			{
				$card = new CDavICalendarComponent($content);
				$res = $this->addressbookCollections[$arPath[0]]->Put($id, $card);
			}
			else
			{
				$res = false;
			}
		}

		return $res ? "201 Created" : "501 Not Implemented";
	}

	public function Delete($id, $siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return '404 Not Found';
		$oldCard = $this->GetEntry('DELETE', $id, $collectionId);;
		if (!is_array($oldCard))
			return $oldCard;

		if ($this->IsMacAgent())
		{
			return $this->GetDefaultResourceProvider()->Delete($id);
		}
		else
		{
			if (!empty($this->addressbookCollections[$arPath[0]]))
				return $this->addressbookCollections[$arPath[0]]->Delete($id);
		}


		return false;

	}

	public function CheckPrivilegesByPath($testPrivileges, $principal, $siteId, $account, $arPath)
	{
		if ($this->IsMacAgent())
		{
			return $this->GetDefaultResourceProvider()->CheckPrivilegesByPath($testPrivileges, $principal, $siteId, $account, $arPath);
		}
		else
		{
			if (!empty($this->addressbookCollections[$arPath[0]]))
				return $this->addressbookCollections[$arPath[0]]->CheckPrivilegesByPath($testPrivileges, $principal, $siteId, $account, $arPath);
		}


		return false;
	}

	public function CheckPrivileges($testPrivileges, $principal, $collectionId)
	{
		if ($this->IsMacAgent())
		{
			return $this->GetDefaultResourceProvider()->CheckPrivileges($testPrivileges, $principal, $collectionId);
		}
		else
		{
			$arPath = explode('/', $this->groupdav->GetRequest()->GetPath());
			if (!empty($this->addressbookCollections[$arPath[4]]))
				return $this->addressbookCollections[$arPath[4]]->CheckPrivileges($testPrivileges, $principal, $collectionId);
		}


		return false;
	}

	public function GetETag($collectionId, $entity)
	{
		if ($this->IsMacAgent())
		{
			return $this->GetDefaultResourceProvider()->GetETag($collectionId, $entity);
		}
		else
		{
			$arPath = explode('/', $this->groupdav->GetRequest()->GetPath());
			if (!empty($this->addressbookCollections[$arPath[4]]))
				return $this->addressbookCollections[$arPath[4]]->GetETag($collectionId, $entity);
		}

		return false;

	}

	private function IsMacAgent()
	{
		return in_array($this->groupdav->GetRequest()->GetAgent(), array('davkit', 'mac os', 'mac_os_x'));
	}

	private function GetDefaultResourceProvider()
	{
		static $resourceProvider;

		if (!$resourceProvider)
		{
			$userId = $this->groupdav->GetRequest()->GetPrincipal()->Id();
			if ($userId)
				$defaultCollectionName = self::GetDefaultResourceProviderName($userId);
			else
			{
				$defaultCollectionName = self::DEFAULT_COLLECTION_NAME;
			}
			$resourceProvider = $this->addressbookCollections[$defaultCollectionName];
			$resourceProvider->setUri('addressbook');
		}
		return $resourceProvider;
	}

	/**
	 * @param $userId
	 * @return string
	 */
	public static function GetDefaultResourceProviderName($userId)
	{
		$default = self::DEFAULT_COLLECTION_NAME;
		return CUserOptions::GetOption('DAV_SYNC', 'DEFAULT_COLLECTION_NAME', $default, $userId);
	}

	/**
	 * @param $settings
	 * @param $userId
	 * @return bool
	 */
	public static function SetDefaultResourceProviderName($settings, $userId)
	{
		return CUserOptions::SetOption('DAV_SYNC', 'DEFAULT_COLLECTION_NAME', $settings,false, $userId);
	}
}
