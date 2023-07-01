<?
namespace Bitrix\Intranet;

use Bitrix\Bitrix24\Sso;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialservices\Network;

class Invitation
{
	/**
	 * @param array $params
	 * @return bool
	 */

	const TYPE_EMAIL = 'email';
	const TYPE_PHONE = 'phone';

	protected static function getTypesAvailable()
	{
		return [
			self::TYPE_EMAIL,
			self::TYPE_PHONE,
		];
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
					'INVITATION_TYPE' => $type
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

	private static function getRegisterSettings()
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

	public static function getRegisterUrl(): string
	{
		$result = '';

		$registerSettings = self::getRegisterSettings();
		if (
			!empty($registerSettings)
			&& isset($registerSettings['REGISTER'])
			&& $registerSettings['REGISTER'] == 'Y'
		)
		{
			$secret = $registerSettings['REGISTER_SECRET'];
			$result = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https://' : 'http://')
				.(defined('BX24_HOST_NAME')? BX24_HOST_NAME: SITE_SERVER_NAME).'/?secret='.($secret <> '' ? urlencode($secret) : 'yes');
		}

		return $result;
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
		return Loc::getMessage('INTRANET_INVITATION_SHARING_MESSAGE');
	}

	public static function canCurrentUserInvite(): bool
	{
		global $USER;

		if (
			Loader::includeModule('bitrix24')
			&& class_exists(Sso\Configuration::class)
			&& Sso\Configuration::isSsoEnabled()
		)
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
}