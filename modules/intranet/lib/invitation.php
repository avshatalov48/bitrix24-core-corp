<?php
namespace Bitrix\Intranet;

use Bitrix\Bitrix24\Sso;
use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\Counters\Synchronizations\InvitationAdminDecrementSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\InvitationAdminSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\InvitationDecrementSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\InvitationSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\TotalInvitationSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\WaitConfirmationDecrementSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\WaitConfirmationResetSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\WaitConfirmationSynchronization;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main;
use Bitrix\Main\Result;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialservices\Network;

class Invitation
{
	/**
	 * @param array $params
	 * @return bool
	 */

	const TYPE_EMAIL = 'email';
	const TYPE_PHONE = 'phone';
	const PULL_MESSAGE_TAG = 'INTRANET_USER_INVITATIONS';

	public const INTRANET_INVITE_REGISTER_ERROR = 'INTRANET_CONTROLLER_INVITE_REGISTER_ERROR';

	protected static function getTypesAvailable()
	{
		return [
			self::TYPE_EMAIL,
			self::TYPE_PHONE,
		];
	}

	protected static function isAvailable(): bool
	{
		if (
			Loader::includeModule('bitrix24')
			&& class_exists(Sso\Configuration::class)
			&& Sso\Configuration::isSsoEnabled()
		)
		{
			return false;
		}

		return true;
	}

	/**
	 * Add filter.
	 *
	 * @param array $params Invitation data.
	 * @return array
	 */
	public static function add(array $params = [])
	{
		global $USER;

		$type = (isset($params['TYPE']) && in_array($params['TYPE'], self::getTypesAvailable()) ? $params['TYPE'] : self::TYPE_EMAIL);
		$userIdList = (isset($params['USER_ID']) ? $params['USER_ID'] : []);
		$originatorId = (isset($params['ORIGINATOR_ID']) && intval($params['ORIGINATOR_ID']) > 0 ? intval($params['ORIGINATOR_ID']) : $USER->getId());
		$isIntegrator = isset($params['IS_INTEGRATOR']) ? $params['IS_INTEGRATOR'] : 'N';
		$isMass = isset($params['IS_MASS']) ? $params['IS_MASS'] : 'N';
		$isDepartment = isset($params['IS_DEPARTMENT']) ? $params['IS_DEPARTMENT'] : 'N';
		$isRegister = isset($params['IS_REGISTER']) ? $params['IS_REGISTER'] : 'N';

		if (!is_array($userIdList))
		{
			$userIdList = [ intval($userIdList) ];
		}

		$processedUserIdList = [];
		foreach($userIdList as $userId)
		{
			if (intval($userId) <= 0)
			{
				continue;
			}

			try
			{
				InvitationTable::add([
					'USER_ID' => $userId,
					'ORIGINATOR_ID' => $originatorId,
					'INVITATION_TYPE' => $type,
					'IS_INTEGRATOR' => $isIntegrator,
					'IS_MASS' => $isMass,
					'IS_DEPARTMENT' => $isDepartment,
					'IS_REGISTER' => $isRegister
				]);
				$processedUserIdList[] = $userId;
			}
			catch(\Exception $e)
			{

			}
		}

		if (!empty($processedUserIdList))
		{
			$event = new Event(
				'intranet',
				'onUserInvited',
				[
					'originatorId' => $originatorId,
					'userId' => $processedUserIdList,
					'type' => $type
				]
			);
			$event->send();
		}

		return $processedUserIdList;
	}

	public static function canDelete(array $params = []): bool
	{
		global $USER;

		$targetUserId = (isset($params['USER_ID']) && intval($params['USER_ID']) > 0 ? intval($params['USER_ID']) : 0);
		$currentUserId = (isset($params['CURRENT_USER_ID']) && intval($params['CURRENT_USER_ID']) > 0 ? intval($params['CURRENT_USER_ID']) : $USER->getId());

		if (
			$targetUserId == $currentUserId
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		$currentUserPerms = \CSocNetUserPerms::initUserPerms(
			$currentUserId,
			$targetUserId,
			\CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, !(Loader::includeModule('bitrix24') && \CBitrix24::isPortalAdmin($currentUserId)))
		);

		if (
			$currentUserPerms["Operations"]["modifyuser_main"]
			&& self::canListDelete([
				'CURRENT_USER_ID' => $currentUserId
			])
			&& Util::checkIntegratorActionRestriction([
				'userId' => $targetUserId
			])
		)
		{
			return true;
		}

		$res = InvitationTable::getList([
			'filter' => [
				'ORIGINATOR_ID' => $currentUserId,
				'USER_ID' => $targetUserId
			]
		]);
		if ($res->fetch())
		{
			return true;
		}


		return false;
	}

	public static function canListDelete(array $params = []): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		global $USER;

		$currentUserId = (isset($params['CURRENT_USER_ID']) && intval($params['CURRENT_USER_ID']) > 0 ? intval($params['CURRENT_USER_ID']) : $USER->getId());

		$canEdit = (
			$USER->isAdmin()
			|| Loader::includeModule('bitrix24') && \CBitrix24::isPortalAdmin($currentUserId)
			|| \CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, !(Loader::includeModule('bitrix24') && \CBitrix24::isPortalAdmin($currentUserId)))
		);

		return $canEdit;
	}

	public static function getRegisterSettings(): ?array
	{
		static $result = null;

		if (
			$result === null
			&& ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('socialservices')
		)
		{
			$result = Network::getRegisterSettings();
		}

		return $result;
	}

	public static function getRegisterUri(): ?Main\Web\Uri
	{
		$registerSettings = self::getRegisterSettings();

		if (!empty($registerSettings) && isset($registerSettings['REGISTER_SECRET']))
		{
			$secret = $registerSettings['REGISTER_SECRET'];
			$serverName = Main\Config\Option::get('main', 'server_name');

			if (defined('BX24_HOST_NAME') && !empty(BX24_HOST_NAME))
			{
				$serverName = BX24_HOST_NAME;
			}
			else if (defined('SITE_SERVER_NAME') && !empty(SITE_SERVER_NAME))
			{
				$serverName = SITE_SERVER_NAME;
			}

			$uri = new Main\Web\Uri((Main\Context::getCurrent()->getRequest()->isHttps() ? 'https://' : 'http://') . $serverName);
			$uri->addParams(['secret' => ($secret <> '' ? urlencode($secret) : 'yes')]);

			return $uri;
		}

		return null;
	}

	public static function getRegisterUrl(): string
	{
		$registerSettings = self::getRegisterSettings();

		if (!empty($registerSettings) && isset($registerSettings['REGISTER']) && $registerSettings['REGISTER'] == 'Y')
		{
			return self::getRegisterUri()?->getUri();
		}

		return '';
	}

	public static function getRegisterAdminConfirm(): bool
	{
		$result = false;

		$registerSettings = self::getRegisterSettings();
		if (!empty($registerSettings))
		{
			$result = ($registerSettings['REGISTER_CONFIRM'] == 'Y');
		}

		return $result;
	}

	public static function getRegisterSharingMessage()
	{
		return Loc::getMessage('INTRANET_INVITATION_SHARING_MESSAGE_MSGVER_1');
	}

	public static function canCurrentUserInvite(): bool
	{
		global $USER;

		if (!self::isAvailable())
		{
			return false;
		}

		return (
			Loader::includeModule('bitrix24')
			&& \CBitrix24::isInvitingUsersAllowed()
		)
		|| (
			!ModuleManager::isModuleInstalled('bitrix24')
			&& $USER->CanDoOperation('edit_all_users')
		);
	}

	public static function canCurrentUserInviteByPhone(): bool
	{
		return
			Loader::includeModule('bitrix24')
			&& self::canCurrentUserInvite()
			&& Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y';
	}

	public static function canCurrentUserInviteByLink(): bool
	{
		return
			self::canCurrentUserInvite()
			&& self::getRegisterSettings()['REGISTER'] === 'Y';
	}

	public static function getRootStructureSectionId(): int
	{
		$result = 0;

		$structure = \CIntranetUtils::getSubStructure(0, 1);
		if (
			empty($structure['DATA'])
			|| !is_array($structure['DATA'])
		)
		{
			return $result;
		}

		$rootSection = array_pop($structure['DATA']);
		if (isset($rootSection['ID']))
		{
			$result = intval($rootSection['ID']);
		}

		return $result;
	}

	public static function getInvitedCounterId(): string
	{
		return 'invited_users';
	}

	public static function getWaitConfirmationCounterId(): string
	{
		return 'wait_confirmation';
	}

	public static function getTotalInvitationCounterId(): string
	{
		return 'total_invitation';
	}

	public static function fullSyncCounterByUser(?User $user): void
	{
		$syncStrategy = new InvitationAdminSynchronization();
		if ($user)
		{
			$syncStrategy->setNext(new InvitationSynchronization($user));
		}
		$counter = new Counter(static::getInvitedCounterId(), $syncStrategy);
		$counter->sync();

		$syncStrategy = new WaitConfirmationSynchronization();
		if ($user)
		{
			$syncStrategy->setNext(new WaitConfirmationResetSynchronization($user));
		}
		$counter = new Counter(static::getWaitConfirmationCounterId(), $syncStrategy);
		$counter->sync();

		$totalCounter = new Counter(
			static::getTotalInvitationCounterId(),
			new TotalInvitationSynchronization($user)
		);
		$totalCounter->sync();
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public static function onUserInvitedHandler(Event $event): bool
	{
		$originatorId = (int)$event->getParameter('originatorId');
		$syncStrategy = new InvitationAdminSynchronization();
		$originator = null;
		if ($originatorId > 0)
		{
			$originator = new User($originatorId);
			$syncStrategy->setNext(new InvitationSynchronization($originator));
		}

		$counter = new Counter(static::getInvitedCounterId(), $syncStrategy);
		$counter->sync();

		$totalCounter = new Counter(
			static::getTotalInvitationCounterId(),
			new TotalInvitationSynchronization($originator)
		);
		$totalCounter->sync();

		return true;
	}

	public static function onRegisterUser($userFields): bool
	{
		if (isset($userFields['ACTIVE'])
			&& $userFields['ACTIVE'] === 'N'
			&& isset($userFields['CONFIRM_CODE'])
			&& mb_strlen($userFields['CONFIRM_CODE']) > 0
		)
		{
			$waitConfirmSync = new WaitConfirmationSynchronization();
			$counter = new Counter(static::getWaitConfirmationCounterId(), $waitConfirmSync);
			$counter->sync();

			$totalCounter = new Counter(
				static::getTotalInvitationCounterId(),
				new TotalInvitationSynchronization()
			);
			$totalCounter->sync();
		}

		if (\Bitrix\Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(self::PULL_MESSAGE_TAG, [
				'module_id' => 'intranet',
				'command' => 'userRegister',
				'params' => [
					'userId' => $userFields['ID'],
				],
			]);
		}

		return true;
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public static function onUserInitializeHandler($userId, array $params): bool
	{
		$ownUser = (new User((int)$userId))->fetchOriginatorUser();
		static::fullSyncCounterByUser($ownUser);

		if (\Bitrix\Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(self::PULL_MESSAGE_TAG, [
				'module_id' => 'intranet',
				'command' => 'userInitialized',
				'params' => [
					'userId' => $userId,
				],
			]);
		}

		return true;
	}

	public static function onAfterUserAuthorizeHandler($params): bool
	{
		$userData = $params['user_fields'];
		if (in_array($userData['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes()))
		{
			return true;
		}

		if ($userData['LAST_LOGIN'])
		{
			return true;
		}

		if ($userData['LAST_ACTIVITY_DATE'])
		{
			return true;
		}

		if (empty($userData['CONFIRM_CODE']))
		{
			return true;
		}


		$userId = (int)$userData['ID'];
		$authorizeUser = new User($userId);
		$ownUser = $authorizeUser->fetchOriginatorUser();

		$syncStrategy = new InvitationAdminDecrementSynchronization();
		if ($ownUser)
		{
			$syncStrategy->setNext(new InvitationDecrementSynchronization($ownUser));
		}
		$counter = new Counter(static::getInvitedCounterId(), $syncStrategy);
		$counter->sync();

		$totalCounter = new Counter(
			static::getTotalInvitationCounterId(),
			new TotalInvitationSynchronization($ownUser)
		);
		$totalCounter->sync();

		return true;
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public static function onBeforeUserDeleteHandler(int $userId): bool
	{
		$deleteUser = new User($userId);
		if ($deleteUser->isInitializedUser())
		{
			return true;
		}

		$ownUser = $deleteUser->fetchOriginatorUser();

		$eventManager = EventManager::getInstance();
		$eventManager->addEventHandler(
			'main',
			'OnAfterUserDelete',
			function(int $userId) use ($ownUser)
			{
				static::fullSyncCounterByUser($ownUser);

				if (\Bitrix\Main\Loader::includeModule('pull'))
				{
					\CPullWatch::AddToStack(self::PULL_MESSAGE_TAG, [
						'module_id' => 'intranet',
						'command' => 'userDeleted',
						'params' => [
							'userId' => $userId,
						],
					]);
				}
			}
		);

		return true;
	}

	public static function onSocNetUserToGroupAddHandler($ID, $data)
	{
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return true;
		}

		$userId = (int)$data['USER_ID'];
		if ($userId <= 0 || !in_array($data['ROLE'], UserToGroupTable::getRolesMember()))
		{
			return true;
		}
		static::fullSyncCounterByUser(new User($userId));

		return true;
	}

	public static function onSocNetUserToGroupUpdateHandler($ID, $changedData, $oldData)
	{
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return true;
		}
		$userId = (int)$oldData['USER_ID'];
		$changedRole = isset($changedData['ROLE'])
			&& $changedData['ROLE'] !== $oldData['ROLE']
			&& in_array($changedData['ROLE'], UserToGroupTable::getRolesMember());
		if ($userId <= 0 || !$changedRole)
		{
			return true;
		}
		static::fullSyncCounterByUser(new User($userId));

		return true;
	}

	public static function onSocNetUserToGroupDeleteHandler(Event $event)
	{
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return true;
		}
		$userId = $event->getParameter('USER_ID');
		if ($userId <= 0)
		{
			return true;
		}

		static::fullSyncCounterByUser(new User($userId));

		return true;
	}

	public static function forceConfirmUser(int $userId, bool $isAccept): Main\Result
	{
		$result = new Main\Result();
		if (!Loader::includeModule('bitrix24'))
		{
			return $result->addError(new Main\Error('The bitrix24 module is not installed'));
		}
		$registerType = \CBitrix24EventHandlers::REGISTER_NOTIFY_TAG;
		$dbRes = UserTable::getById($userId);
		$userInfo = $dbRes->fetch();
		if ($userInfo === false)
		{
			return $result->addError(new Main\Error(''));
		}
		$confirmCode = $userInfo["CONFIRM_CODE"];
		$tag = "BITRIX24|$registerType|$userId|$confirmCode";
		$notifyFields = [
			"NOTIFY_SUB_TAG" => '',
		];
		$eventResult = \CBitrix24EventHandlers::OnBeforeConfirmNotify(
			'bitrix24',
			$tag,
			$isAccept ? 'Y' : 'N',
			$notifyFields
		);
		if (isset($eventResult['success']) && $eventResult['success'] === false)
		{
			if (!empty($eventResult['text']))
			{
				$result->addError(new Main\Error($eventResult['text']));
			}
		}

		return $result;
	}

	public static function confirmUserRequest(int $userId, bool $isAccept): Main\Result
	{
		$result = new Main\Result();
		if (!Loader::includeModule('im'))
		{
			return $result->addError(new Main\Error('The bitrix24 module is not installed'));
		}
		$currentUser = CurrentUser::get();
		$sql = "
			SELECT M.*
			FROM b_im_relation R, b_im_message M
			WHERE M.AUTHOR_ID = ".$userId." AND R.USER_ID = ".$currentUser->getId()." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R.CHAT_ID = M.CHAT_ID AND M.NOTIFY_TYPE = ".IM_NOTIFY_CONFIRM;
		$queryResult = Application::getInstance()->getConnection()->query($sql);

		global $APPLICATION;
		$APPLICATION->ResetException();
		if (!($notifyData = $queryResult->fetch()))
		{
			return static::forceConfirmUser($userId, $isAccept);
		}
		$notify = new \CIMNotify();
		$notify->Confirm($notifyData['ID'], $isAccept ? 'Y' : 'N');

		if ($APPLICATION->GetException() !== false)
		{
			return $result->addError(new Main\Error($APPLICATION->GetException()->GetString()));
		}

		return $result;
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public static function onAfterSetUserGroupHandler($userId, array $params): void
	{
		$user = new User((int)$userId);
		static::fullSyncCounterByUser($user);
	}

	public static function inviteUsers(array $fields): Main\Result
	{
		$result = new Main\Result();
		$errorList = [];
		$convertedPhoneNumbers = [];
		$userList = [];
		$userIdList = \CIntranetInviteDialog::registerNewUser(\CSite::getDefSite(), $fields, $errorList) ?? [];

		if (!empty($errorList))
		{
			foreach ($errorList as $error)
			{
				if ($error instanceof Error)
				{
					$result->addError($error);
				}
				elseif (is_string($error) && !empty($error))
				{
					$result->addError(new Error($error, self::INTRANET_INVITE_REGISTER_ERROR));
				}
			}
		}

		if (!empty($userIdList))
		{
			\CIntranetInviteDialog::logAction(
				$userIdList,
				(
				isset($fields['DEPARTMENT_ID'])
				&& (int)$fields['DEPARTMENT_ID'] > 0
					? 'intranet'
					: 'extranet'
				),
				'invite_user',
				(
				!empty($fields['PHONE'])
					? 'sms_dialog'
					: 'invite_dialog'
				),
				(
				!empty($fields['CONTEXT'])
				&& $fields['CONTEXT'] === 'mobile'
					? 'mobile'
					: 'web'
				)
			);

			$userQuery = UserTable::getList([
				'select' => ['ID', 'NAME', 'LAST_NAME', 'PERSONAL_MOBILE', 'EMAIL'],
				'filter' => ['@ID' => $userIdList]
			]);

			while($user = $userQuery->fetch())
			{
				$user["FULL_NAME"] = \CUser::FormatName(\CSite::GetNameFormat(), $user, true, false);
				$userList[] = $user;
				$convertedPhoneNumbers[] = $user['PERSONAL_MOBILE']; // TODO: PERSONAL_MOBILE_FORMATTED
			}
		}

		return $result->setData([
			'userList' => $userList,
			'userIdList' => $userIdList,
			'convertedPhoneNumbers' => $convertedPhoneNumbers,
			'errors' => [],
		]);
	}

	/**
	 * @param array $phoneUsers phoneUser must contain fields ['PHONE', 'PHONE_COUNTRY'] and may contain fields ['NAME', 'LAST_NAME']
	 * @param string $context 'web' or 'mobile'
	 * @param int $departmentId
	 * @return Result
	 */
	public static function inviteUsersByPhoneNumbers(array $phoneUsers, string $context = 'web', int $departmentId = 0): Main\Result
	{
		$fields = [
			'PHONE' => $phoneUsers,
			'CONTEXT' => $context,
		];

		if ($departmentId > 0)
		{
			$fields['DEPARTMENT_ID'] = $departmentId;
		}

		return self::inviteUsers($fields);
	}

	/**
	 * @param array $emailUsers emailUser must contain fields ['EMAIL'] and may contain fields ['NAME', 'LAST_NAME']
	 * @param string $context 'web' or 'mobile'
	 * @param int $departmentId
	 * @return Result
	 */
	public static function inviteUsersByEmails(array $emailUsers, string $context = 'web', int $departmentId = 0): Main\Result
	{
		$fields = [
			'EMAIL' => $emailUsers,
			'CONTEXT' => $context,
		];

		if ($departmentId > 0)
		{
			$fields['DEPARTMENT_ID'] = $departmentId;
		}

		return self::inviteUsers($fields);
	}
}
