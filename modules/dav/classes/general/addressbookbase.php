<?php

/**
 * Class CDavAddressbookResource
 */
abstract class CDavAddressbookBase
{

	private $name;
	private $namespace;
	private $resourceUri;
	private $minimumPrivileges;
	protected $groupdav;

	const V_CARD_CONTENT_TYPE = "VCARD";
	const V_CARD_CONTENT_VERSION = "3.0";

	/**
	 * CDavResource constructor.
	 * @param CDavGroupDav $groupdav
	 */
	public function __construct($groupdav)
	{
		$this->groupdav = $groupdav;
	}

	/**
	 * Get display name of subgroup
	 * @return string
	 */
	public function GetName()
	{
		return $this->name;
	}

	/**
	 * Set display name of subgroup
	 * @param string $name
	 */
	public function SetName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get namespace of group, use in namespace param for DAV Protocol
	 * @return string
	 */
	public function GetNamespace()
	{
		return $this->namespace;
	}

	/**
	 * Set namespace of group, use in namespace param for DAV Protocol
	 * @param string $namespace
	 */
	public function SetNamespace($namespace)
	{
		$this->namespace = $namespace;
	}

	/**
	 * Get uri of subgroup
	 * @return string
	 */
	public function GetUri()
	{
		return $this->resourceUri;
	}

	/**
	 * Set uri of subgroup
	 * @param string $uri
	 */
	public function SetUri($uri)
	{
		$this->resourceUri = $uri;
	}

	/**
	 * @return mixed
	 */
	public function GetMinimumPrivileges()
	{
		return $this->minimumPrivileges;
	}

	/**
	 * @param array $minPrivileges
	 */
	public function SetMinimumPrivileges(array $minPrivileges)
	{
		$this->minimumPrivileges = $minPrivileges;
	}

	/**
	 * Id of vcf file, which will return  as result of synchronisation
	 * @param int|array $entity
	 * @return string
	 */
	protected function GetPath($entity)
	{
		return (is_numeric($entity) ? $entity : $entity['ID']) . '.vcf';
	}

	/**
	 * Write parameters in /addressbook directory resource for DAV protocol
	 * @param CDavResource $resource
	 * @param $collectionId
	 * @param $account
	 * @param $arPath
	 * @param $options
	 */
	public function GetAddressbookProperties(CDavResource $resource, $collectionId, $account, $arPath, $options)
	{
		$resource->AddProperty('resourcetype',
			array(
				array('collection', ''),
				array('vcard-collection', '', CDavGroupDav::GROUPDAV),
				array('addressbook', '', CDavGroupDav::CARDDAV),
			)
		);
		$resource->AddProperty('component-set', 'VCARD', CDavGroupDav::GROUPDAV);
		$resource->AddProperty('supported-report-set', array(
				array('supported-report',
					array(CDavResource::MakeProp('report', array(CDavResource::MakeProp('addressbook-query', '', CDavGroupDav::CARDDAV)))),
				),
				array('supported-report',
					array(CDavResource::MakeProp('report', array(CDavResource::MakeProp('addressbook-multiget', '', CDavGroupDav::CARDDAV))))
				))
		);

		$resource->AddProperty('getctag', $this->GetCTag($collectionId), CDavGroupDav::CALENDARSERVER);

		$arAccount = null;
		if ($account != null)
		{
			$arAccount = CDavAccount::GetAccountById($account);

			$resource->AddProperty('addressbook-description', $arAccount["NAME"], CDavGroupDav::CARDDAV);
		}
	}


	/**
	 * Catalog getctag property for DAV protocol
	 * @param $collectionId
	 * @param array $filter
	 * @return string getctag property
	 */
	public function GetCTag($collectionId, $filter = array())
	{
		return 'BX:' . MakeTimeStamp($this->CatalogLastModifiedAt($collectionId, $filter));
	}


	/**
	 * Catalog getetag property for DAV protocol
	 * @param  array $collectionId
	 * @param $entity
	 * @return string getetag property
	 */
	public function GetETag($collectionId, $entity)
	{
		if (!is_array($entity))
			$entity = $this->Read($collectionId, $entity);


		return 'BX:' . $entity['ID'] . ':' . MakeTimeStamp($this->EntityLastModifiedAt($entity));
	}

	/**
	 * @param $collectionId
	 * @param array $filter
	 * @return int unix timestamp
	 */
	abstract protected function CatalogLastModifiedAt($collectionId, $filter = array());

	/**
	 * Timestamp of last modification
	 * @param $entity
	 * @return mixed
	 */
	abstract protected function EntityLastModifiedAt($entity);

	/**
	 * @param $requestDocument
	 * @param $id
	 * @return array
	 */
	protected function PrepareFilters($requestDocument, $id)
	{
		$arFilter = array();

		if ($id)
		{
			if (is_numeric($id))
				$arFilter["ID"] = intval($id);
			else
				$arFilter['XML_ID'] = basename($id, '.vcf');
		}
		elseif (!empty($requestDocument) && $requestDocument->GetRoot() !== null && $requestDocument->GetRoot()->GetTag() == 'addressbook-multiget')
		{

			$arIds = array();
			$arXmlIds = array();

			$arProp = $requestDocument->GetPath('/addressbook-multiget/DAV::href');

			foreach ($arProp as $prop)
			{
				$parts = explode('/', $prop->GetContent());
				if (!($idTmp = basename(array_pop($parts), '.vcf')))
					continue;

				if (is_numeric($idTmp))
					$arIds[] = $idTmp;
				else
					$arXmlIds[] = $idTmp;
			}

			if ($arIds)
				$arFilter["ID"] = (count($arIds) > 1 ? $arIds : $arIds[0]);

			if ($arXmlIds)
				$arFilter["XML_ID"] = (count($arXmlIds) > 1 ? $arXmlIds : $arXmlIds[0]);

			if (is_array($arFilter['ID']))
				$arFilter['ID'] = array_unique($arFilter['ID']);
		}
		return $arFilter;
	}

	/**
	 * Load entity select filters
	 * @param $id
	 * @return array of filter
	 */
	protected function LoadFilters($id)
	{
		$request = $this->groupdav->GetRequest();
		$requestDocument = null;
		$requestMethod = $request->GetParameter('REQUEST_METHOD');
		if (!empty($requestMethod) && $requestMethod !== 'PUT')
			$requestDocument = $request->GetXmlDocument();
		$filters = $this->PrepareFilters($requestDocument, $id);
		return $filters;
	}

	/**
	 * Check Privileges for principal
	 * @param $testPrivileges
	 * @param $principal
	 * @param $collectionId
	 * @return bool
	 */
	public function CheckPrivileges($testPrivileges, $principal, $collectionId)
	{
		if (is_object($principal) && ($principal instanceof CDavPrincipal))
			$principal = $principal->Id();

		if (!is_numeric($principal))
			return false;

		$principal = IntVal($principal);

		if (!is_array($collectionId))
			$collectionId = array($collectionId);
		$collectionIdNorm = implode("-", $collectionId);

		static $arCollectionPrivilegesCache = array();
		if (!isset($arCollectionPrivilegesCache[$collectionIdNorm][$principal]))
		{
			$arCollectionPrivilegesCache[$collectionIdNorm][$principal] = CDav::PackPrivileges($this->GetMinimumPrivileges());
		}

		$testPrivilegesBits = CDav::PackPrivileges($testPrivileges);
		return (($arCollectionPrivilegesCache[$collectionIdNorm][$principal] & $testPrivilegesBits) > 0) && $this->AdditionalPrivilegesCheck($principal);
	}

	/**
	 * @param $testPrivileges
	 * @param $principal
	 * @param $collectionId
	 * @param $account
	 * @param $arPath
	 * @return bool
	 */
	public function CheckPrivilegesByPath($testPrivileges, $principal, $collectionId, $account, $arPath)
	{
		return $this->CheckPrivileges($testPrivileges, $principal, $collectionId);
	}

	/**
	 * Add checking of some additional cases
	 * @param $principal
	 * @return bool
	 */
	protected function AdditionalPrivilegesCheck($principal)
	{
		return true;
	}

	/**
	 * @param $collectionId
	 * @param $id
	 * @return array|null|bool
	 */
	public function Read($collectionId, $id)
	{
		$request = $this->groupdav->GetRequest();
		if (!$this->CheckPrivileges('DAV:read', $request->GetPrincipal(), $collectionId))
			return false;
		return $this->ReadEntity($collectionId, $request->GetPrincipal(), $id);
	}

	/**
	 * @param $collectionId
	 * @param $account
	 * @param $id
	 * @return array|null
	 */
	protected function ReadEntity($collectionId, $account, $id)
	{
		$entity = $this->LoadEntities($collectionId, $account, $this->LoadFilters($id));
		return (count($entity)) ? $entity[0] : null;
	}

	/**
	 * Handler for PROPFIND request for current resource
	 * @param $arDavResources
	 * @param $collectionId
	 * @param $account
	 * @param $arPath
	 * @param null $id
	 * @return bool|string
	 */
	public function Propfind(&$arDavResources, $collectionId, $account, $arPath, $id = null)
	{
		if ($collectionId == null)
			return '404 Not Found';

		$request = $this->groupdav->GetRequest();
		$currentPrincipal = $request->GetPrincipal();
		if (!$this->CheckPrivileges('DAV:read', $currentPrincipal, $collectionId))
			return '403 Forbidden';

		$requestDocument = $request->GetXmlDocument();

		if (($id || $requestDocument->GetRoot() && $requestDocument->GetRoot()->GetTag() != 'propfind') && !$this->LoadFilters($id))
			return false;
		$path = CDav::CheckIfRightSlashAdded($request->GetPath());
		if ($this->IsResourcePath($path))
		{

			$filter = $this->LoadFilters($id);
			$entities = $this->LoadEntities($collectionId, $account, $filter);

			foreach ($entities as $entity)
			{
				$resource = new CDavResource($path . $this->GetPath($entity));
				$resource->AddProperty('getetag', $this->GetETag($collectionId, $entity));
				$resource->AddProperty('getcontenttype', 'text/vcard');
				$resource->AddProperty('getlastmodified', MakeTimeStamp($this->EntityLastModifiedAt($entity)));
				$resource->AddProperty('resourcetype', '');
				if ($this->IsAddressData($request))
				{
					$content = $this->GetVCardContent($entity);
					$resource->AddProperty('getcontentlength', strlen($content));
					$resource->AddProperty('address-data', $content, CDavGroupDav::CARDDAV);
				}
				else
				{
					$resource->AddProperty('getcontentlength', "");
				}

				$arDavResources[] = $resource;
			}
		}


		return true;
	}

	/**
	 * Handler for GET request of DAV protocol
	 * @param $collectionId
	 * @param $entity
	 * @return mixed
	 */
	public function Get($collectionId, $entity)
	{
		$result['data'] = $this->GetVCardContent($entity);
		$result['mimetype'] = 'text/x-vcard; charset=utf-8';
		$result['headers'] = array('Content-Encoding: identity', 'ETag:' . $this->GetETag($collectionId, $entity));
		return $result;
	}


	/**
	 * Handler for PUT request of DAV protocol
	 * @param $id
	 * @param CDavICalendarComponent $card
	 * @return bool
	 */
	public function Put($id, $card)
	{
		return false;
	}

	/**
	 * Handler for DELETE request of DAV protocol
	 * @param $id
	 * @return bool
	 */
	public function Delete($id)
	{
		//CDav::WriteToLog(serialize($id));
		return true;
	}


	/**
	 * Return entities array
	 * @param $collectionId
	 * @param $account
	 * @param array $filter
	 * @return mixed
	 */
	abstract protected function LoadEntities($collectionId, $account, $filter = array());

	/**
	 * Check if current path is current resource path
	 * @param $path
	 * @return bool
	 */
	private function IsResourcePath($path)
	{
		return strpos($path, $this->resourceUri) !== false ? true : false;
	}

	/**
	 * Check is Data from request is address data
	 * @param CDavRequest $request
	 * @return bool
	 */
	private function IsAddressData(CDavRequest $request)
	{
		$requestDocument = $request->GetXmlDocument();
		$isAddressData = (count($requestDocument->GetPath('/*/DAV::allprop')) > 0);
		if (!$isAddressData || $requestDocument->GetRoot()->GetXmlns() != CDavGroupDav::CARDDAV)
		{
			$arProp = $requestDocument->GetPath('/*/DAV::prop/*');
			foreach ($arProp as $prop)
			{
				if ($prop->GetTag() == 'address-data')
				{
					$isAddressData = true;
					break;
				}
			}
		}
		return $isAddressData;
	}

	/**
	 * Map: key=>value vCard properties of entity
	 * @param $entity
	 * @return mixed
	 */
	abstract protected function GetVCardDataMap($entity);

	/**
	 * @param $id
	 * @param CDavICalendarComponent $card
	 * @return array
	 */
	protected function PrepareEntityParamsFromVCard($id, $card)
	{
		return array();
	}

	/**
	 * Perform vCard content
	 * @param array $entity performed by each resource
	 * @return string
	 */
	private function GetVCardContent($entity)
	{
		$arVCardContact = array();
		$map = array(
			"TYPE" => self::V_CARD_CONTENT_TYPE,
			"VERSION" => self::V_CARD_CONTENT_VERSION,
		);
		$map = array_merge($map, $this->GetVCardDataMap($entity));

		if (!empty($map['IMG']))
		{
			if (is_array($map['IMG']))
			{
				if (!empty($map['IMG']['src']))
					$arTempFile = $map['IMG'];
			}
			else if (intval($map['IMG']) > 0)
			{
				$arTempFile = CFile::ResizeImageGet(
					$map['IMG'],
					array("width" => \Bitrix\Main\Config\Option::get("dav", "vcard_image_width", 400), "height" => \Bitrix\Main\Config\Option::get("dav", "vcard_image_width", 400)),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false,
					false,
					true,
					\Bitrix\Main\Config\Option::get("dav", "vcard_image_quality", 60)
				);
			}

			if (!empty($arTempFile) && file_exists($_SERVER["DOCUMENT_ROOT"] . $arTempFile['src']))
			{
				$cnt = file_get_contents($_SERVER["DOCUMENT_ROOT"] . $arTempFile['src']);
				if (!empty($cnt))
				{
					$arImageTypes = array(
						IMAGETYPE_JPEG => 'JPEG',
						IMAGETYPE_GIF => 'GIF',
						IMAGETYPE_PNG => 'PNG'
					);

					$imageType = "JPEG";
					if ($imageInfo = CFile::GetImageSize($_SERVER["DOCUMENT_ROOT"] . $arTempFile['src']) and isset($arImageTypes[$imageInfo[2]]))
						$imageType = $arImageTypes[$imageInfo[2]];

					$map["PHOTO"] = array(
						"VALUE" => /*chunk_split(*/
							base64_encode($cnt)/*)*/,
						"PARAMETERS" => array("ENCODING" => "BASE64", "TYPE" => $imageType)
					);
				}
			}
		}
		unset($map['IMG']);
		foreach ($map as $propertyTitle => $property)
		{
			$arVCardContact[$propertyTitle] = $property;
		}

		$cal = new CDavICalendarComponent($arVCardContact);
		return $cal->Render();
	}
}