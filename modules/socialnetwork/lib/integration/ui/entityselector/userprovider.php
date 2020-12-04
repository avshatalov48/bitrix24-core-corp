<?
namespace Bitrix\Socialnetwork\Integration\UI\EntitySelector;

use Bitrix\Intranet\Integration\Mail\EmailUser;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\UserAbsence;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Router;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\EO_User;
use Bitrix\Main\EO_User_Collection;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Search\Content;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupSiteTable;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class UserProvider extends BaseProvider
{
	private const EXTRANET_ROLES = [
		UserToGroupTable::ROLE_USER,
		UserToGroupTable::ROLE_OWNER,
		UserToGroupTable::ROLE_MODERATOR,
		UserToGroupTable::ROLE_REQUEST
	];

	public function __construct(array $options = [])
	{
		parent::__construct();

		if (isset($options['nameTemplate']) && is_string($options['nameTemplate']))
		{
			preg_match_all(
				'/#NAME#|#LAST_NAME#|#SECOND_NAME#|#NAME_SHORT#|#SECOND_NAME_SHORT#|\s|,/',
				urldecode($options['nameTemplate']),
				$matches
			);

			$this->options['nameTemplate'] = implode('', $matches[0]);
		}
		else
		{
			$this->options['nameTemplate'] = \CSite::getNameFormat(false);
		}

		if (isset($options['onlyWithEmail']) && is_bool($options['onlyWithEmail']))
		{
			$this->options['onlyWithEmail'] = $options['onlyWithEmail'];
		}

		if (isset($options['extranetUsersOnly']) && is_bool($options['extranetUsersOnly']))
		{
			$this->options['extranetUsersOnly'] = $options['extranetUsersOnly'];
		}

		if (isset($options['intranetUsersOnly']) && is_bool($options['intranetUsersOnly']))
		{
			$this->options['intranetUsersOnly'] = $options['intranetUsersOnly'];
		}

		$this->options['emailUsers'] = false;
		if (isset($options['emailUsers']) && is_bool($options['emailUsers']))
		{
			$this->options['emailUsers'] = $options['emailUsers'];
		}

		$this->options['myEmailUsers'] = true;
		if (isset($options['myEmailUsers']) && is_bool($options['myEmailUsers']))
		{
			$this->options['myEmailUsers'] = $options['myEmailUsers'];
		}

		if (isset($options['emailUsersOnly']) && is_bool($options['emailUsersOnly']))
		{
			$this->options['emailUsersOnly'] = $options['emailUsersOnly'];
		}

		$this->options['networkUsers'] = false;
		if (isset($options['networkUsers']) && is_bool($options['networkUsers']))
		{
			$this->options['networkUsers'] = $options['networkUsers'];
		}

		if (isset($options['networkUsersOnly']) && is_bool($options['networkUsersOnly']))
		{
			$this->options['networkUsersOnly'] = $options['networkUsersOnly'];
		}

		$intranetInstalled = ModuleManager::isModuleInstalled('intranet');
		$this->options['showLogin'] = $intranetInstalled;
		$this->options['showEmail'] = $intranetInstalled;

		$this->options['inviteEmployeeLink'] = true;
		if (isset($options['inviteEmployeeLink']) && is_bool($options['inviteEmployeeLink']))
		{
			$this->options['inviteEmployeeLink'] = $options['inviteEmployeeLink'];
		}

		$this->options['inviteGuestLink'] = false;
		if (isset($options['inviteGuestLink']) && is_bool($options['inviteGuestLink']))
		{
			$this->options['inviteGuestLink'] = $options['inviteGuestLink'];
		}
	}

	public function isAvailable(): bool
	{
		if (!$GLOBALS['USER']->isAuthorized())
		{
			return false;
		}

		$intranetInstalled = ModuleManager::isModuleInstalled('intranet');
		if ($intranetInstalled)
		{
			return self::isIntranetUser() || self::isExtranetUser();
		}

		return true;
	}

	public function getItems(array $ids): array
	{
		return $this->getUserItems([
			'userId' => $ids
		]);
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->getUserItems([
			'userId' => $ids,
			'activeUsers' => null // to see fired employees
		]);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$maxUsersInRecentTab = 50;

		// Preload first 50 users ('doSearch' method has to have the same filter).
		$preloadedUsers = $this->getUserCollection([
			'order' => ['ID' => 'asc'],
			'limit' => $maxUsersInRecentTab
		]);

		if ($preloadedUsers->count() < $maxUsersInRecentTab)
		{
			// Turn off the user search
			$entity = $dialog->getEntity('user');
			if ($entity)
			{
				$entity->setDynamicSearch(false);
			}
		}

		$recentUsers = new EO_User_Collection();

		// Recent Items
		$recentItems = $dialog->getRecentItems()->getEntityItems('user');
		$recentIds = array_map('intval', array_keys($recentItems));
		$this->fillRecentUsers($recentUsers, $recentIds, $preloadedUsers);

		// Global Recent Items
		if ($recentUsers->count() < $maxUsersInRecentTab)
		{
			$recentGlobalItems = $dialog->getGlobalRecentItems()->getEntityItems('user');
			$recentGlobalIds = [];

			if (!empty($recentGlobalItems))
			{
				$recentGlobalIds = array_map('intval', array_keys($recentGlobalItems));
				$recentGlobalIds = array_values(array_diff($recentGlobalIds, $recentUsers->getIdList()));
				$recentGlobalIds = array_slice($recentGlobalIds, 0, $maxUsersInRecentTab - $recentUsers->count());
			}

			$this->fillRecentUsers($recentUsers, $recentGlobalIds, $preloadedUsers);
		}

		// The rest of preloaded users
		foreach ($preloadedUsers as $preloadedUser)
		{
			$recentUsers->add($preloadedUser);
		}

		$dialog->addRecentItems($this->makeUserItems($recentUsers));

		// Footer
		if (Loader::includeModule('intranet'))
		{
			$inviteEmployeeLink = null;
			if ($this->options['inviteEmployeeLink'] && self::isIntranetUser() && Invitation::canCurrentUserInvite())
			{
				$inviteEmployeeLink = UrlManager::getInstance()->create('getSliderContent', [
					'c' => 'bitrix:intranet.invitation',
					'mode' => Router::COMPONENT_MODE_AJAX,
				]);
			}

			$inviteGuestLink = null;
			if ($this->options['inviteGuestLink'] && ModuleManager::isModuleInstalled('mail') && self::isIntranetUser())
			{
				$inviteGuestLink = UrlManager::getInstance()->create('getSliderContent', [
					'c' => 'bitrix:intranet.invitation.guest',
					'mode' => Router::COMPONENT_MODE_AJAX,
				]);
			}

			if ($inviteEmployeeLink || $inviteGuestLink)
			{
				$dialog->setFooter('BX.SocialNetwork.EntitySelector.Footer', [
					'inviteEmployeeLink' => $inviteEmployeeLink,
					'inviteGuestLink' => $inviteGuestLink
				]);
			}
		}
	}

	private function fillRecentUsers(
		EO_User_Collection $recentUsers,
		array $recentIds,
		EO_User_Collection $preloadedUsers
	): void
	{
		if (count($recentIds) < 1)
		{
			return;
		}

		$ids = array_values(array_diff($recentIds, $preloadedUsers->getIdList()));
		if (!empty($ids))
		{
			$users = $this->getUserCollection(['userId' => $ids]);
			foreach ($users as $user)
			{
				$preloadedUsers->add($user);
			}
		}

		foreach ($recentIds as $recentId)
		{
			$user = $preloadedUsers->getByPrimary($recentId);
			if ($user)
			{
				$recentUsers->add($user);
			}
		}
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$atom = '=_0-9a-z+~\'!\$&*^`|\\#%/?{}-';
		$isEmailLike = (bool)preg_match('#^['.$atom.']+(\\.['.$atom.']+)*@#i', $searchQuery->getQuery());

		if ($isEmailLike)
		{
			$dialog->addItems(
				$this->getUserItems([
					'searchByEmail' => $searchQuery->getQuery(),
					'myEmailUsers' => false
				])
			);
		}
		else
		{
			$dialog->addItems(
				$this->getUserItems([
					'searchQuery' => $searchQuery->getQuery(),
				])
			);
		}
	}

	public function handleBeforeItemSave(Item $item): void
	{
		if ($item->getEntityType() === 'email')
		{
			$user = UserTable::getById($item->getId())->fetchObject();
			if ($user && $user->getExternalAuthId() === 'email' && Loader::includeModule('intranet'))
			{
				EmailUser::invite($user->getId());
			}
		}
	}

	public function getUserCollection(array $options = []): EO_User_Collection
	{
		$options = array_merge($this->getOptions(), $options);

		return self::getUsers($options);
	}

	public function getUserItems(array $options = []): array
	{
		return $this->makeUserItems($this->getUserCollection($options), $options);
	}

	public function makeUserItems(EO_User_Collection $users, array $options = []): array
	{
		return self::makeItems($users, array_merge($this->getOptions(), $options));
	}

	public static function isIntranetUser(int $userId = null): bool
	{
		if (!ModuleManager::isModuleInstalled('intranet'))
		{
			return false;
		}

		static $cache = [];

		if (is_null($userId))
		{
			$userId = is_object($GLOBALS['USER']) ? $GLOBALS['USER']->getId() : 0;
			if ($userId <= 0)
			{
				return false;
			}
		}

		if (!isset($cache[$userId]))
		{
			$cache[$userId] = UserTable::getList([
				'filter' => [
					'=ID' => $userId,
					'!UF_DEPARTMENT' => false,
					'IS_REAL_USER' => true
				],
			])->fetchCollection()->count() === 1;
		}

		return $cache[$userId];
	}

	public static function isExtranetUser(int $userId = null): bool
	{
		if (!ModuleManager::isModuleInstalled('intranet'))
		{
			return false;
		}

		static $cache = [];

		if (is_null($userId))
		{
			$userId = is_object($GLOBALS['USER']) ? $GLOBALS['USER']->getId() : 0;
			if ($userId <= 0)
			{
				return false;
			}
		}

		if (!isset($cache[$userId]))
		{
			$cache[$userId] = UserTable::getList([
				'filter' => [
					'=ID' => $userId,
					'UF_DEPARTMENT' => false,
					'IS_REAL_USER' => true
				],
			])->fetchCollection()->count() === 1;
		}

		return $cache[$userId];
	}

	public static function isIntegrator(int $userId = null): bool
	{
		static $integrators;

		if ($integrators === null)
		{
			$integrators = [];
			if (Loader::includeModule('bitrix24'))
			{
				$integrators = array_fill_keys(\Bitrix\Bitrix24\Integrator::getIntegratorsId(), true);
			}
		}

		if (is_null($userId))
		{
			$userId = is_object($GLOBALS['USER']) ? $GLOBALS['USER']->getId() : 0;
			if ($userId <= 0)
			{
				return false;
			}
		}

		return isset($integrators[$userId]);
	}

	public static function getUsers(array $options = []): EO_User_Collection
	{
		$query = UserTable::query();
		$query->setSelect([
			'ID', 'ACTIVE', 'LAST_NAME', 'NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'TITLE',
			'PERSONAL_GENDER', 'PERSONAL_PHOTO', 'WORK_POSITION',
			'CONFIRM_CODE', 'EXTERNAL_AUTH_ID'
		]);

		$intranetInstalled = ModuleManager::isModuleInstalled('intranet');
		if ($intranetInstalled)
		{
			$query->addSelect('UF_DEPARTMENT');
		}

		$activeUsers = array_key_exists('activeUsers', $options) ? $options['activeUsers'] : true;
		if (is_bool($activeUsers))
		{
			$query->where('ACTIVE', $activeUsers ? 'Y' : 'N');
		}

		if (isset($options['onlyWithEmail']) && is_bool(isset($options['onlyWithEmail'])))
		{
			$query->addFilter(($options['onlyWithEmail'] ? '!' : '').'EMAIL', false);
		}

		if (isset($options['invitedUsers']) && is_bool(isset($options['invitedUsers'])))
		{
			$query->addFilter(($options['invitedUsers'] ? '!' : '').'CONFIRM_CODE', false);
		}

		if (!empty($options['searchQuery']) && is_string($options['searchQuery']))
		{
			$query->registerRuntimeField(
				new Reference(
					'INDEX_SELECTOR',
					\Bitrix\Main\UserIndexSelectorTable::class,
					Join::on('this.ID', 'ref.USER_ID'),
					['join_type' => 'INNER']
				)
			);

			$query->whereMatch(
				'INDEX_SELECTOR.SEARCH_SELECTOR_CONTENT',
				Filter\Helper::matchAgainstWildcard(
					Content::prepareStringToken($options['searchQuery']), '*', 1
				)
			);
		}
		else if (!empty($options['searchByEmail']) && is_string($options['searchByEmail']))
		{
			$query->whereLike('EMAIL', $options['searchByEmail'].'%');
		}

		$currentUserId = (
			!empty($options['currentUserId']) && is_int($options['currentUserId'])
				? $options['currentUserId']
				: $GLOBALS['USER']->getId()
		);

		$isIntranetUser = $intranetInstalled && self::isIntranetUser($currentUserId);
		if ($intranetInstalled)
		{
			$query->registerRuntimeField(new ExpressionField(
				'IS_INTRANET_USER',
				'IF(
					(%s IS NOT NULL AND %s != \'a:0:{}\') AND
					(%s IS NULL OR %s NOT IN (\''.join('\', \'', UserTable::getExternalUserTypes()).'\')), \'Y\', \'N\'
				)',
				['UF_DEPARTMENT', 'UF_DEPARTMENT', 'EXTERNAL_AUTH_ID', 'EXTERNAL_AUTH_ID'])
			);

			$query->registerRuntimeField(new ExpressionField(
				'IS_EXTRANET_USER',
				'IF(
					(%s IS NULL OR %s = \'a:0:{}\') AND
					(%s IS NULL OR %s NOT IN (\''.join('\', \'', UserTable::getExternalUserTypes()).'\')), \'Y\', \'N\'
				)',
				['UF_DEPARTMENT', 'UF_DEPARTMENT', 'EXTERNAL_AUTH_ID', 'EXTERNAL_AUTH_ID'])
			);

			$query->registerRuntimeField(
				new Reference(
					'INVITATION',
					InvitationTable::class,
					Join::on('this.ID', 'ref.USER_ID')->where('ref.ORIGINATOR_ID', $currentUserId),
					['join_type' => 'LEFT']
				)
			);

			$extranetUsersQuery = self::getExtranetUsersQuery($currentUserId);
			$intranetUsersOnly = isset($options['intranetUsersOnly']) && $options['intranetUsersOnly'] === true;
			$extranetUsersOnly = isset($options['extranetUsersOnly']) && $options['extranetUsersOnly'] === true;
			$emailUsersOnly = isset($options['emailUsersOnly']) && $options['emailUsersOnly'] === true;
			$networkUsersOnly = isset($options['networkUsersOnly']) && $options['networkUsersOnly'] === true;

			$emailUsers =
				isset($options['emailUsers']) && is_bool($options['emailUsers']) ? $options['emailUsers'] : true
			;

			$myEmailUsers =
				isset($options['myEmailUsers']) && is_bool($options['myEmailUsers']) ? $options['myEmailUsers'] : false;
			;

			$networkUsers =
				isset($options['networkUsers']) && is_bool($options['networkUsers']) ? $options['networkUsers'] : true
			;

			if ($isIntranetUser)
			{
				if (isset($options['departmentId']) && is_int($options['departmentId']))
				{
					$query->addFilter('UF_DEPARTMENT', $options['departmentId']);
				}

				if ($emailUsersOnly)
				{
					$query->where('EXTERNAL_AUTH_ID', 'email');
					if ($myEmailUsers)
					{
						$query->whereNotNull('INVITATION.ID');
					}
				}
				else if ($networkUsersOnly)
				{
					$query->where('EXTERNAL_AUTH_ID', 'replica');
				}
				else if ($intranetUsersOnly)
				{
					$query->where('IS_INTRANET_USER', 'Y');
				}
				else if ($extranetUsersOnly)
				{
					$query->where('IS_EXTRANET_USER', 'Y');
					$query->whereIn('ID', $extranetUsersQuery);
				}
				else
				{
					$filter = Query::filter()->logic('or');
					$filter->where('IS_INTRANET_USER', 'Y');

					if ($emailUsers === true)
					{
						if ($myEmailUsers)
						{
							$filter->addCondition(Query::filter()
								->where('EXTERNAL_AUTH_ID', 'email')
								->whereNotNull('INVITATION.ID')
							);
						}
						else
						{
							$filter->where('EXTERNAL_AUTH_ID', 'email');
						}
					}

					if ($networkUsers === true)
					{
						$filter->where('EXTERNAL_AUTH_ID', 'replica');
					}

					if ($extranetUsersQuery)
					{
						$filter->whereIn('ID', $extranetUsersQuery);
					}

					$query->where($filter);
				}
			}
			else
			{
				if ($intranetUsersOnly)
				{
					$query->where('IS_INTRANET_USER', 'Y');
				}
				else if ($extranetUsersOnly)
				{
					$query->where('IS_EXTRANET_USER', 'Y');
				}

				if ($extranetUsersQuery)
				{
					$query->whereIn('ID', $extranetUsersQuery);
				}
				else
				{
					$query->where(new ExpressionField('EMPTY_LIST', '1'), '!=', 1);
				}
			}
		}
		else
		{
			$query->addFilter('!=EXTERNAL_AUTH_ID', UserTable::getExternalUserTypes());
		}

		$userIds = [];
		$userFilter = isset($options['userId']) ? 'userId' : (isset($options['!userId']) ? '!userId' : null);
		if (isset($options[$userFilter]))
		{
			if (is_array($options[$userFilter]) && !empty($options[$userFilter]))
			{
				foreach ($options[$userFilter] as $id)
				{
					$id = intval($id);
					if ($id > 0)
					{
						$userIds[] = $id;
					}
				}

				$userIds = array_unique($userIds);

				if (!empty($userIds))
				{
					if ($userFilter === 'userId')
					{
						$query->whereIn('ID', $userIds);
					}
					else
					{
						$query->whereNotIn('ID', $userIds);
					}
				}
			}
			else if (!is_array($options[$userFilter]) && intval($options[$userFilter]) > 0)
			{
				if ($userFilter === 'userId')
				{
					$query->where('ID', intval($options[$userFilter]));
				}
				else
				{
					$query->whereNot('ID', intval($options[$userFilter]));
				}
			}
		}

		if ($userFilter === 'userId' && count($userIds) > 1 && empty($options['order']))
		{
			$query->registerRuntimeField(
				new ExpressionField(
					'ID_SEQUENCE',
					'FIELD(%s, '.join(',', $userIds).')',
					'ID'
				)
			);

			$query->setOrder('ID_SEQUENCE');
		}
		elseif (!empty($options['order']) && is_array($options['order']))
		{
			$query->setOrder($options['order']);
		}
		else
		{
			$query->setOrder(['LAST_NAME' => 'asc']);
		}

		$query->setLimit(isset($options['limit']) && is_int($options['limit']) ? $options['limit'] : 100);

		//echo '<pre>'.$query->getQuery().'</pre>';

		$result = $query->exec();

		return $result->fetchCollection();
	}

	private static function getExtranetUsersQuery(int $currentUserId): ?Query
	{
		$extranetSiteId = Option::get('extranet', 'extranet_site');
		$extranetSiteId = ($extranetSiteId && ModuleManager::isModuleInstalled('extranet') ? $extranetSiteId : false);

		if (!$extranetSiteId)
		{
			return null;
		}

		$query = UserToGroupTable::query();
		$query->addSelect(new ExpressionField('DISTINCT_USER_ID', 'DISTINCT %s', 'USER_ID'));
		// $query->where('ROLE', '<=', UserToGroupTable::ROLE_USER);
		$query->whereIn('ROLE', self::EXTRANET_ROLES);
		$query->registerRuntimeField(
			new Reference(
				'GS',
				WorkgroupSiteTable::class,
				Join::on('ref.GROUP_ID', 'this.GROUP_ID')->where('ref.SITE_ID', $extranetSiteId),
				['join_type' => 'INNER']
			)
		);

		$query->registerRuntimeField(
			new Reference(
				'UG_MY',
				UserToGroupTable::class,
				Join::on('ref.GROUP_ID', 'this.GROUP_ID')
					->where('ref.USER_ID', $currentUserId)
					->whereIn('ref.ROLE', self::EXTRANET_ROLES),
				['join_type' => 'INNER']
			)
		);

		return $query;
	}

	public static function getUser(int $userId, array $options = []): ?EO_User
	{
		$options['userId'] = $userId;
		$users = self::getUsers($options);

		return $users->count() ? $users->getAll()[0] : null;
	}

	public static function makeItems(EO_User_Collection $users, array $options = []): array
	{
		$result = [];
		foreach ($users as $user)
		{
			$result[] = self::makeItem($user, $options);
		}

		return $result;
	}

	public static function makeItem(EO_User $user, array $options = []): Item
	{
		$customData = [];
		foreach (['name', 'lastName', 'secondName', 'email', 'login'] as $field)
		{
			if (!empty($user->{'get'.$field}()))
			{
				$customData[$field] = $user->{'get'.$field}();
			}
		}

		if (isset($options['showLogin']) && $options['showLogin'] === false)
		{
			unset($customData['login']);
		}

		if (isset($options['showEmail']) && $options['showEmail'] === false)
		{
			unset($customData['email']);
		}

		if (!empty($user->getPersonalGender()))
		{
			$customData['gender'] = $user->getPersonalGender();
		}

		if (!empty($user->getWorkPosition()))
		{
			$customData['position'] = $user->getWorkPosition();
		}

		$userType = self::getUserType($user);

		if ($user->getConfirmCode() && in_array($userType, ['employee', 'integrator']))
		{
			$customData['invited'] = true;
		}

		$item = new Item([
			'id' => $user->getId(),
			'entityId' => 'user',
			'entityType' => $userType,
			'title' => self::formatUserName($user, $options),
			'avatar' => self::makeUserAvatar($user),
			'customData' => $customData,
		]);

		if (($userType === 'employee' || $userType === 'integrator') && Loader::includeModule('intranet'))
		{
			$isOnVacation = UserAbsence::isAbsentOnVacation($user->getId());
			if ($isOnVacation)
			{
				$item->getCustomData()->set('isOnVacation', true);
			}
		}

		return $item;
	}

	public static function getUserType(EO_User $user): string
	{
		$type = null;
		if (!$user->getActive())
		{
			$type = 'inactive';
		}
		else if ($user->getExternalAuthId() === 'email')
		{
			$type = 'email';
		}
		else if ($user->getExternalAuthId() === 'replica')
		{
			$type = 'network';
		}
		else if (!in_array($user->getExternalAuthId(), UserTable::getExternalUserTypes()))
		{
			if (ModuleManager::isModuleInstalled('intranet'))
			{
				if (self::isIntegrator($user->getId()))
				{
					$type = 'integrator';
				}
				else
				{
					$type = empty($user->getUfDepartment()) ? 'extranet' : 'employee';
				}
			}
			else
			{
				$type = 'user';
			}
		}
		else
		{
			$type = 'unknown';
		}

		return $type;
	}

	public static function formatUserName(EO_User $user, array $options = []): string
	{
		return \CUser::formatName(
			!empty($options['nameTemplate']) ? $options['nameTemplate'] : \CSite::getNameFormat(false),
			[
				'NAME' => $user->getName(),
				'LAST_NAME' => $user->getLastName(),
				'SECOND_NAME' => $user->getSecondName(),
				'LOGIN' => $user->getLogin(),
				'EMAIL' => $user->getEmail(),
				'TITLE' => $user->getTitle(),
			],
			true,
			false
		);
	}

	public static function makeUserAvatar(EO_User $user): ?string
	{
		if (empty($user->getPersonalPhoto()))
		{
			return null;
		}

		$avatar = \CFile::resizeImageGet(
			$user->getPersonalPhoto(),
			['width' => 100, 'height' => 100],
			BX_RESIZE_IMAGE_EXACT,
			false
		);

		return !empty($avatar['src']) ? $avatar['src'] : null;
	}

	public static function getUserUrl(?int $userId = null): string
	{

		return
			self::isExtranetUser($userId)
				? self::getExtranetUserUrl($userId)
				: self::getIntranetUserUrl($userId)
		;
	}

	public static function getExtranetUserUrl(?int $userId = null): string
	{
		$extranetSiteId = Option::get('extranet', 'extranet_site');
		$userPage = Option::get('socialnetwork', 'user_page', false, $extranetSiteId);
		if (!$userPage)
		{
			$userPage = '/extranet/contacts/personal/';
		}

		return $userPage.'user/'.($userId !== null ? $userId : '#id#').'/';
	}

	public static function getIntranetUserUrl(?int $userId = null): string
	{
		$userPage = Option::get('socialnetwork', 'user_page', false, SITE_ID);
		if (!$userPage)
		{
			$userPage = SITE_DIR.'company/personal/';
		}

		return $userPage.'user/'.($userId !== null ? $userId : '#id#').'/';
	}
}