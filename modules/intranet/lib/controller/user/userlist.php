<?php

namespace Bitrix\Intranet\Controller\User;

use Bitrix\Intranet\ActionFilter\AdminUser;
use Bitrix\Intranet\ActionFilter\InviteIntranetAccessControl;
use Bitrix\Intranet\ActionFilter\InviteLimitControl;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\Invitation\Register;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User;
use Bitrix\Intranet\Util;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Response;

class UserList extends Controller
{
	const DEFAULT_SELECT = ['ID', 'NAME', 'LAST_NAME', 'LOGIN', 'EMAIL', 'SECOND_NAME'];

	public function getDefaultPreFilters(): array
	{
		$prefilters = parent::getDefaultPreFilters();
		$prefilters[] = new AdminUser();

		return $prefilters;
	}

	public function configureActions(): array
	{
		return [
			'groupReInvite' => [
				'+prefilters' => [
					new InviteIntranetAccessControl(),
					new InviteLimitControl(),
				],
				'-prefilters' => [
					AdminUser::class,
				],
			],
			'createChat' => [
				'-prefilters' => [
					AdminUser::class,
				],
			]
		];
	}

	public function groupDeleteAction(array $fields): Response
	{
		$res = $this->getUserList($fields, ['ACTIVE', 'CONFIRM_CODE']);
		$skippedActiveUsers = [];
		$skippedFiredUsers = [];

		if (!$res)
		{
			return AjaxJson::createError($this->errorCollection);
		}

		while ($user = $res->fetch())
		{
			if ($user['ID'] === CurrentUser::get()->getId())
			{
				continue;
			}

			if (empty($user['CONFIRM_CODE']))
			{
				if ($user['ACTIVE'] === 'Y')
				{
					$skippedActiveUsers[$user['ID']] = $this->getUserFullName($user);
				}
				else
				{
					$skippedFiredUsers[$user['ID']] = $this->getUserFullName($user);
				}
			}
			else
			{
				if (!\CUser::Delete($user['ID']))
				{
					if ($user['ACTIVE'] === 'Y')
					{
						$skippedActiveUsers[$user['ID']] = $this->getUserFullName($user);
					}
					else
					{
						$skippedFiredUsers[$user['ID']] = $this->getUserFullName($user);
					}
				}
			}
		}

		return AjaxJson::createSuccess([
			'skippedActiveUsers' => $skippedActiveUsers,
			'skippedFiredUsers' => $skippedFiredUsers,
		]);
	}

	public function groupFireAction(array $fields): Response
	{
		$res = $this->getUserList($fields, ['ACTIVE', 'CONFIRM_CODE']);
		$skippedFiredUsers = [];
		$originatorUsers = [];

		if (!$res)
		{
			return AjaxJson::createError($this->errorCollection);
		}

		while ($user = $res->fetch())
		{
			if ($user['ID'] === CurrentUser::get()->getId())
			{
				continue;
			}

			if ($user['ACTIVE'] === 'N' && empty($user['CONFIRM_CODE']))
			{
				$skippedFiredUsers[$user['ID']] = $this->getUserFullName($user);
			}
			else
			{
				Util::deactivateUser([
					'userId' => $user['ID'],
					'currentUserId' => CurrentUser::get()->getId(),
					'isCurrentUserAdmin' => CurrentUser::get()->isAdmin(),
				]);

				if (!empty($user['CONFIRM_CODE']))
				{
					$deactivateUser = new User($user['ID']);
					$originatorUser = $deactivateUser->fetchOriginatorUser();

					if (!in_array($originatorUser, $originatorUsers))
					{
						$originatorUsers[] = $originatorUser;
					}
				}
			}
		}

		foreach ($originatorUsers as $originatorUser)
		{
			Invitation::fullSyncCounterByUser($originatorUser);
		}

		return AjaxJson::createSuccess([
			'skippedFiredUsers' => $skippedFiredUsers
		]);
	}

	public function groupConfirmAction(array $fields): Response
	{
		return $this->setConfirmationStatus($fields, true);
	}

	public function groupDeclineAction(array $fields): Response
	{
		return $this->setConfirmationStatus($fields, false);
	}

	private function setConfirmationStatus(array $fields, bool $isConfirm): Response
	{
		$res = $this->getUserList($fields, ['ACTIVE', 'CONFIRM_CODE']);
		$skippedActiveUsers = [];
		$skippedFiredUsers = [];

		if (!$res)
		{
			return AjaxJson::createError($this->errorCollection);
		}

		while ($user = $res->fetch())
		{
			if ($user['ID'] === CurrentUser::get()->getId())
			{
				continue;
			}

			if ($user['ACTIVE'] === 'N' && empty($user['CONFIRM_CODE']))
			{
				$skippedFiredUsers[$user['ID']] = $this->getUserFullName($user);
			}
			elseif ($user['ACTIVE'] === 'Y' && !empty($user['CONFIRM_CODE']) && $isConfirm)
			{
				$skippedActiveUsers[$user['ID']] = $this->getUserFullName($user);
			}
			elseif (empty($user['CONFIRM_CODE']) && !$isConfirm)
			{
				Util::deactivateUser([
					'userId' => $user['ID'],
					'currentUserId' => CurrentUser::get()->getId(),
					'isCurrentUserAdmin' => CurrentUser::get()->isAdmin(),
				]);
			}
			else
			{
				$result = \Bitrix\Intranet\Invitation::confirmUserRequest($user['ID'], $isConfirm);

				if (!$result->isSuccess())
				{
					$this->addErrors($result->getErrors());
				}
			}
		}

		return AjaxJson::createSuccess([
			'skippedActiveUsers' => $skippedActiveUsers,
			'skippedFiredUsers' => $skippedFiredUsers,
		]);
	}

	public function groupReInviteAction(array $fields): Response
	{
		$res = $this->getUserList($fields, ['ACTIVE', 'CONFIRM_CODE', 'PERSONAL_MOBILE', 'UF_DEPARTMENT']);
		$skippedActiveUsers = [];
		$skippedFiredUsers = [];
		$skippedWaitingUsers = [];
		$usersToInvite['ITEMS'] = [];

		if (!$res)
		{
			return AjaxJson::createError($this->errorCollection);
		}

		while ($user = $res->fetch())
		{
			if ($user['ACTIVE'] === 'N' && !empty($user['CONFIRM_CODE']))
			{
				$skippedWaitingUsers[$user['ID']] = $this->getUserFullName($user);
			}
			elseif ($user['ACTIVE'] === 'N' && empty($user['CONFIRM_CODE']))
			{
				$skippedFiredUsers[$user['ID']] = $this->getUserFullName($user);
			}
			elseif ($user['ACTIVE'] === 'N' || empty($user['CONFIRM_CODE']))
			{
				$skippedActiveUsers[$user['ID']] = $this->getUserFullName($user);
			}
			else
			{
				if (!empty($user['PERSONAL_MOBILE']))
				{
					$user['PHONE'] = $user['PERSONAL_MOBILE'];
				}

				$usersToInvite['ITEMS'][] = $user;
			}
		}

		if (!empty($usersToInvite['ITEMS']))
		{
			$errors = [];
			Register::inviteNewUsers(SITE_ID, $usersToInvite, 'mass', $errors);

			foreach ($errors as $errorMessage)
			{
				$this->addError(new Error($errorMessage));
			}
		}

		return AjaxJson::createSuccess([
			'skippedActiveUsers' => $skippedActiveUsers,
			'skippedFiredUsers' => $skippedFiredUsers,
			'skippedWaitingUsers' => $skippedWaitingUsers,
		]);
	}

	public function createChatAction(array $fields): Response
	{
		if (\Bitrix\Main\Loader::includeModule('im'))
		{
			$res = \Bitrix\Intranet\UserTable::getList([
				'select' => ['ID', 'ACTIVE'],
				'filter' => ['=ACTIVE' => 'Y', '@ID' => $fields['userIds']],
			]);

			if ($res && $idList = $res->fetchCollection()->getIdList())
			{
				$messenger = \Bitrix\Im\V2\Service\Locator::getMessenger();
				$result = $messenger->createChat([
					'USERS' => $idList,
				]);

				if ($result->isSuccess())
				{
					return AjaxJson::createSuccess($result->getResult()['CHAT_ID']);
				}
				else
				{
					$this->addErrors($result->getErrors());
				}
			}
			else
			{
				$this->addError(new Error(Loc::getMessage('INTRANET_CONTROLLER_USER_LIST_GROUP_CHAT_FIRED_ERROR')));
			}
		}
		else
		{
			$this->addError(new Error('Module im is not installed'));
		}

		return AjaxJson::createError($this->errorCollection);
	}

	public function groupChangeDepartmentAction(array $fields): Response
	{
		$user = new \CUser;
		$res = $this->getUserList($fields, ['UF_DEPARTMENT', 'GROUPS'])->fetchCollection();
		$skippedExtranetUsers = [];

		foreach ($res as $resUser)
		{
			if (empty($resUser->getUfDepartment()))
			{
				$skippedExtranetUsers[$resUser->getId()] = $this->getUserFullName($resUser);
			}
			else
			{
				$user->Update($resUser->getId(), ['UF_DEPARTMENT' => $fields['departmentIds']]);
			}
		}

		$iblockID = \COption::GetOptionInt('intranet', 'iblock_structure');
		$result = \CIBlockSection::GetList(
			[],
			[
				'@UF_HEAD' => $fields['userIds'],
				'IBLOCK_ID' => $iblockID
			],
			false,
			[
				'UF_HEAD',
				'ID',
				'IBLOCK_ID'
			]
		);

		while ($department = $result->Fetch())
		{
			if (in_array($department['ID'], $fields['departmentIds']))
			{
				continue;
			}

			$depFields = [
				'UF_HEAD' => false
			];
			$section = new \CIBlockSection;
			$section->Update($department['ID'], $depFields);
		}

		return AjaxJson::createSuccess([
			'skippedActiveUsers' => $skippedExtranetUsers,
		]);
	}

	private function getUserFullName($user): array
	{
		return [
			'id' => $user['ID'],
			'fullName' => \CUser::FormatName(\CSite::GetNameFormat(), $user, true),
		];
	}

	private function getUserList(array $fields, array $select = [])
	{
		if (empty($fields['userIds']))
		{
			$this->addError(new Error('no selected users'));

			return null;
		}

		if ($fields['isSelectedAllRows'] === 'N')
		{
			$filter = ['@ID' => $fields['userIds']];
		}
		else
		{
			$filter = $fields['filter'];
		}

		if (ModuleManager::isModuleInstalled('extranet'))
		{
			$select[] = 'EXTRANET_GROUP';
		}

		return \Bitrix\Intranet\UserTable::getList([
			'select' => array_merge($select, self::DEFAULT_SELECT),
			'filter' => $filter
		]);
	}
}