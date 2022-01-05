<?

namespace Bitrix\Intranet\Integration\Mail;

use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Intranet\Invitation;
use Bitrix\Main\EO_User;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\UserTable;
use PhpParser\Node\Expr\AssignOp\Mod;

class EmailUser
{
	public static function create(array $fields): ?EO_User
	{
		if (empty($fields['email']) || !\check_email($fields['email']) || !Loader::includeModule('mail'))
		{
			return null;
		}

		$user = UserTable::query()
			->setSelect(['*'])
			->where('EMAIL', $fields['email'])
			->where(
				Query::filter()
					->logic('or')
					->whereNull('EXTERNAL_AUTH_ID')
					->whereNotIn('EXTERNAL_AUTH_ID', array_diff(UserTable::getExternalUserTypes(), ['email']))
			)
			->fetchObject()
		;

		if ($user)
		{
			if ($user->getExternalAuthId() === 'email' && !$user->getActive())
			{
				$user->setActive(true);
				$user->save();
			}
		}
		else
		{
			$userId = \Bitrix\Mail\User::create(
				[
					'EMAIL' => $fields['email'],
					'NAME' => empty($fields['name']) ? '' : $fields['name'],
					'LAST_NAME' => empty($fields['lastName']) ? '' : $fields['lastName']
				]
			);

			if ($userId)
			{
				$user = UserTable::getById($userId)->fetchObject();
			}
		}

		if ($user && $user->getExternalAuthId() === 'email')
		{
			self::invite($user->getId());
		}

		return $user;
	}

	public static function invite(int $userId, int $originatorId = null): void
	{
		if (!ModuleManager::isModuleInstalled('mail'))
		{
			return;
		}

		if (is_null($originatorId))
		{
			$originatorId = is_object($GLOBALS['USER']) ? $GLOBALS['USER']->getId() : 0;
			if ($originatorId <= 0)
			{
				return;
			}
		}

		if ($userId <= 0)
		{
			return;
		}

		try
		{
			InvitationTable::add([
				'USER_ID' => $userId,
				'ORIGINATOR_ID' => $originatorId,
				'INVITATION_TYPE' => Invitation::TYPE_EMAIL
			]);
		}
		catch (\Exception $e)
		{
			// skip duplicate records
		}
	}
}