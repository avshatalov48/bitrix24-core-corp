<?

namespace Bitrix\DocumentGenerator;

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\DocumentGenerator\Model\RolePermissionTable;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\DocumentGenerator\Model\TemplateUserTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class UserPermissions
{
	const ENTITY_SETTINGS = 'SETTINGS';
	const ENTITY_TEMPLATES = 'TEMPLATES';
	const ENTITY_DOCUMENTS = 'DOCUMENTS';

	const ACTION_VIEW = 'VIEW';
	const ACTION_MODIFY = 'MODIFY';
	const ACTION_CREATE = 'CREATE';

	const PERMISSION_NONE = '';
	const PERMISSION_SELF = 'A';
	const PERMISSION_DEPARTMENT = 'D';
	const PERMISSION_ANY = 'X';
	const PERMISSION_ALLOW = 'X';

	protected $isAdmin;
	protected $userId;
	protected $permissions;
	protected $availableForModifyingTemplateIds;
	protected $relatedTemplateIds;

	public function __construct($userId)
	{
		$this->userId = (int) $userId;
		$this->loadUserPermissions();
	}

	/**
	 * @return bool
	 */
	public function canViewDocuments()
	{
		return (
			$this->canModifyDocuments() ||
			$this->canPerform(static::ENTITY_DOCUMENTS, static::ACTION_VIEW)
		);
	}

	/**
	 * @return bool
	 */
	public function canModifyDocuments()
	{
		return $this->canPerform(static::ENTITY_DOCUMENTS, static::ACTION_MODIFY);
	}

	/**
	 * @param int|Document $documentId
	 * @return bool
	 */
	public function canModifyDocument($documentId)
	{
		if($this->hasAdminAccess())
		{
			return true;
		}
		if($this->canModifyDocuments())
		{
			if($documentId instanceof Document)
			{
				$document = $documentId;
			}
			else
			{
				$document = Document::loadById(intval($documentId));
			}
			if($document && $document->hasAccess($this->userId))
			{
				$template = $document->getTemplate();
				return ($template && isset($this->getRelatedTemplateIds()[$template->ID]));
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function canModifyTemplates()
	{
		return $this->canPerform(static::ENTITY_TEMPLATES, static::ACTION_MODIFY);
	}

	/**
	 * @return bool
	 */
	public function canModifySettings()
	{
		return $this->canPerform(static::ENTITY_SETTINGS, static::ACTION_MODIFY);
	}

	/**
	 * @param $templateId
	 * @return bool
	 */
	public function canCreateDocumentOnTemplate($templateId)
	{
		if($this->hasAdminAccess())
		{
			return true;
		}
		if($this->canModifyDocuments())
		{
			return (isset($this->getRelatedTemplateIds()[$templateId]));
		}

		return false;
	}

	/**
	 * @param int $templateId
	 * @return bool
	 */
	public function canModifyTemplate($templateId)
	{
		if($this->canModifyTemplates())
		{
			if($this->permissions[static::ENTITY_TEMPLATES][static::ACTION_MODIFY] === static::PERMISSION_ANY)
			{
				return true;
			}
			return isset($this->getAvailableForModifyingTemplateIds()[intval($templateId)]);
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getFilterForTemplateList()
	{
		$filter = [];
		if($this->permissions[static::ENTITY_TEMPLATES][static::ACTION_MODIFY] === static::PERMISSION_SELF)
		{
			$filter['=CREATED_BY'] = $this->userId;
		}
		elseif($this->permissions[static::ENTITY_TEMPLATES][static::ACTION_MODIFY] === static::PERMISSION_DEPARTMENT)
		{
			$filter['=CREATED_BY'] = $this->getUserColleagues();
		}
		elseif($this->permissions[static::ENTITY_TEMPLATES][static::ACTION_MODIFY] === static::PERMISSION_NONE)
		{
			$filter['=CREATED_BY'] = '!@#$%';
		}

		return $filter;
	}

	/**
	 * @return array
	 */
	protected function getAvailableForModifyingTemplateIds()
	{
		if(!$this->canModifyTemplates())
		{
			return [];
		}
		if($this->availableForModifyingTemplateIds === null)
		{
			$this->availableForModifyingTemplateIds = [];
			$filter = array_merge($this->getFilterForTemplateList(), [
				'=IS_DELETED' => 'N',
			]);
			$templates = TemplateTable::getList(['select' => ['ID'], 'filter' => $filter]);
			while($template = $templates->fetch())
			{
				$this->availableForModifyingTemplateIds[$template['ID']] = $template['ID'];
			}
		}

		return $this->availableForModifyingTemplateIds;
	}

	/**
	 * @return \Bitrix\Main\ORM\Query\Filter\ConditionTree
	 */
	public function getFilterForRelatedTemplateList()
	{
		return \Bitrix\Main\ORM\Query\Query::filter()
            ->logic('or')
            ->whereIn('USER.ACCESS_CODE', \Bitrix\Main\UserAccessTable::query()->addSelect('ACCESS_CODE')->where('USER_ID', $this->userId))
            ->where('USER.ACCESS_CODE', TemplateUserTable::ALL_USERS);
	}

	/**
	 * @return array
	 */
	protected function getRelatedTemplateIds()
	{
		if(!$this->canModifyDocuments())
		{
			return [];
		}
		if($this->relatedTemplateIds === null)
		{
			$this->relatedTemplateIds = [];
			$templates = TemplateTable::getList(['select' => ['ID'], 'filter' => $this->getFilterForRelatedTemplateList()]);
			while($template = $templates->fetch())
			{
				$this->relatedTemplateIds[$template['ID']] = $template['ID'];
			}
		}

		return $this->relatedTemplateIds;
	}

	protected function loadUserPermissions()
	{
		$this->permissions = [];
		//administrators should have full access despite everything
		if($this->hasAdminAccess())
		{
			$this->permissions = $this->getAdminPermissions();
			return;
		}

		$userAccessCodes = \CAccess::GetUserCodesArray($this->userId);
		if(!is_array($userAccessCodes) || count($userAccessCodes) === 0)
		{
			return;
		}

		$rolePermissions = RolePermissionTable::getList(['filter' => [
			'=ROLE_ACCESS.ACCESS_CODE' => $userAccessCodes
		]]);

		while($rolePermission = $rolePermissions->fetch())
		{
			if (
				!isset($this->permissions[$rolePermission['ENTITY']][$rolePermission['ACTION']]) ||
				$this->permissions[$rolePermission['ENTITY']][$rolePermission['ACTION']] < $rolePermission['PERMISSION']
			)
			{
				$this->permissions[$rolePermission['ENTITY']][$rolePermission['ACTION']] = $rolePermission['PERMISSION'];
			}
		}
	}

	/**
	 * @return bool
	 */
	protected function hasAdminAccess()
	{
		if($this->isAdmin === null)
		{
			$this->isAdmin = false;

			if(!Bitrix24Manager::isPermissionsFeatureEnabled())
			{
				$this->isAdmin = true;
			}
			elseif($this->userId > 0 && (int) Driver::getInstance()->getUserId() === $this->userId)
			{
				$currentUser = CurrentUser::get();
				if(ModuleManager::isModuleInstalled('bitrix24'))
				{
					$this->isAdmin = $currentUser->canDoOperation('bitrix24_config');
				}
				else
				{
					$this->isAdmin = $currentUser->isAdmin();
				}
			}
		}

		return $this->isAdmin;
	}

	/**
	 * @return array
	 */
	public static function getEntityTitles()
	{
		Loc::loadLanguageFile(__FILE__);
		return [
			static::ENTITY_SETTINGS => Loc::getMessage('DOCGEN_USERPERMISSIONS_ENTITY_SETTINGS'),
			static::ENTITY_TEMPLATES => Loc::getMessage('DOCGEN_USERPERMISSIONS_ENTITY_TEMPLATES'),
			static::ENTITY_DOCUMENTS => Loc::getMessage('DOCGEN_USERPERMISSIONS_ENTITY_DOCUMENTS'),
		];
	}

	/**
	 * @return array
	 */
	public static function getActionTitles()
	{
		Loc::loadLanguageFile(__FILE__);
		return [
			static::ACTION_VIEW => Loc::getMessage('DOCGEN_USERPERMISSIONS_ACTION_VIEW'),
			static::ACTION_MODIFY => Loc::getMessage('DOCGEN_USERPERMISSIONS_ACTION_MODIFY_1'),
		];
	}

	/**
	 * @param null $entity
	 * @return array
	 */
	public static function getPermissionTitles($entity = null)
	{
		Loc::loadLanguageFile(__FILE__);
		$titles = [
			static::PERMISSION_NONE => Loc::getMessage('DOCGEN_USERPERMISSIONS_PERMISSION_NONE'),
			static::PERMISSION_SELF => Loc::getMessage('DOCGEN_USERPERMISSIONS_PERMISSION_SELF'),
			static::PERMISSION_DEPARTMENT => Loc::getMessage('DOCGEN_USERPERMISSIONS_PERMISSION_DEPARTMENT'),
			static::PERMISSION_ALLOW => Loc::getMessage('DOCGEN_USERPERMISSIONS_PERMISSION_ALLOW'),
		];
		if($entity === static::ENTITY_TEMPLATES)
		{
			$titles[static::PERMISSION_ANY] = Loc::getMessage('DOCGEN_USERPERMISSIONS_PERMISSION_ANY');
		}

		return $titles;
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function getMap()
	{
		return [
			self::ENTITY_SETTINGS => [
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_ALLOW,
				],
			],
			self::ENTITY_TEMPLATES => [
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY,
				],
			],
			self::ENTITY_DOCUMENTS => [
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_ALLOW,
				],
				self::ACTION_VIEW => [
					self::PERMISSION_NONE,
					self::PERMISSION_ALLOW,
				]
			],
		];
	}

	/**
	 * Returns maximum available permissions
	 * @return array
	 */
	protected static function getAdminPermissions()
	{
		$result = array();
		$permissionMap = static::getMap();

		foreach($permissionMap as $entity => $actions)
		{
			foreach($actions as $action => $permissions)
			{
				foreach($permissions as $permission)
				{
					if(!isset($result[$entity][$action]) || $result[$entity][$action] < $permission)
					{
						$result[$entity][$action] = $permission;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Returns true if user can perform specified action on the entity.
	 * @param string $entityCode Code of the entity.
	 * @param string $actionCode Code of the action.
	 * @return bool
	 * @throws ArgumentException
	 */
	protected function canPerform($entityCode, $actionCode)
	{
		$permissionMap = $this->getMap();
		if(!isset($permissionMap[$entityCode][$actionCode]))
		{
			throw new ArgumentException('Unknown entity or action code');
		}

		return (
			isset($this->permissions[$entityCode][$actionCode]) &&
			$this->permissions[$entityCode][$actionCode] > self::PERMISSION_NONE
		);
	}

	/**
	 * @return array
	 */
	protected function getUserColleagues()
	{
		if(!Loader::includeModule('intranet'))
		{
			return [];
		}

		$result = [];
		$colleagueList = \CIntranetUtils::getDepartmentColleagues($this->userId, true);
		while($colleague = $colleagueList->Fetch())
		{
			$result[] = $colleague['ID'];
		}
		return $result;
	}
}