<?php

namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Type\Email;
use Bitrix\Intranet\Entity\Type\Phone;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Response;

class UserProfile extends Controller
{
	private const EDIT_FIELDS = ['NAME', 'LAST_NAME', 'EMAIL', 'PERSONAL_MOBILE', 'PERSONAL_PHOTO'];

	/**
	 * @restMethod intranetmobile.userprofile.isNeedToShowMiniProfile
	 * @return bool
	 */
	public function isNeedToShowMiniProfileAction(): bool
	{
		$isNeedToShow = (
				\CUserOptions::GetOption('intranetmobile', 'isNeedToShowMiniProfile', false) ?? false)
			&& !(\CUserOptions::GetOption('intranetmobile', 'isMiniProfileShowed', false) ?? false)
		;

		if ($isNeedToShow)
		{
			\CUserOptions::SetOption('intranetmobile', 'isMiniProfileShowed', true);
			\CUserOptions::DeleteOption('intranetmobile', 'isNeedToShowMiniProfile');
		}

		return $isNeedToShow;
	}

	/**
	 * @restMethod intranetmobile.userprofile.saveProfile
	 * @param array $data
	 * @return Response
	 */
	public function saveProfileAction(array $data): Response
	{
		$userId = $data['ID'] ?? null;

		if (!$userId)
		{
			$this->errorCollection->setError(new Error('Id must be not null'));

			return AjaxJson::createError($this->errorCollection);
		}

		if (!$this->canCurrentUserEdit($userId))
		{
			$this->errorCollection->setError(new Error('access denied'));

			return AjaxJson::createError($this->errorCollection);
		}

		$fields = array_intersect_key($data, array_flip(self::EDIT_FIELDS));

		if (!empty($fields['EMAIL']))
		{
			$email = new Email($fields['EMAIL']);

			if (!$email->isValid())
			{
				$this->errorCollection->setError(new Error('wrong email', 'WRONG_EMAIL'));
			}
		}

		if (!empty($fields['PERSONAL_MOBILE']))
		{
			$phone = new Phone($fields['PERSONAL_MOBILE']);

			if (!$phone->isValid())
			{
				$this->errorCollection->setError(new Error('wrong phone', 'WRONG_PHONE'));
			}
		}

		if (isset($fields['PERSONAL_PHOTO']))
		{
			$fields['PERSONAL_PHOTO'] = \CRestUtil::saveFile($fields['PERSONAL_PHOTO']);

			if (!$fields['PERSONAL_PHOTO'])
			{
				$fields['PERSONAL_PHOTO'] = ['del' => 'Y'];
			}
		}

		if (!$this->errorCollection->isEmpty())
		{
			return AjaxJson::createError($this->errorCollection);
		}

		$user = new \CUser;

		if (!$user->update($userId, $fields))
		{
			$this->errorCollection->setError(new Error($user->LAST_ERROR, 'EMAIL_EXIST'));

			return AjaxJson::createError($this->errorCollection);
		}

		return AjaxJson::createSuccess();
	}

	private function canCurrentUserEdit(int $userId): bool
	{
		Loader::includeModule('socialnetwork');

		$currentUserPerms = \CSocNetUserPerms::initUserPerms(
			CurrentUser::get()->getId(),
			$userId,
			\CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false)
		);

		return $currentUserPerms['IsCurrentUser']
			|| (
				$currentUserPerms['Operations']['modifyuser']
				&& $currentUserPerms['Operations']['modifyuser_main']
			);
	}
}