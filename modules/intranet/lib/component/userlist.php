<?php
namespace Bitrix\Intranet\Component;

use Bitrix\Bitrix24\Integrator;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;
use Bitrix\Main\UserTable;

class UserList extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	/** @var ErrorCollection errorCollection */
	protected $errorCollection;

	public function configureActions()
	{
		return [
			'export' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod([ ActionFilter\HttpMethod::METHOD_GET ]),
				]
			]
		];
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public static function getDepartmentValue(array $params = [])
	{
		static $departmentsData = null;

		$result = '';

		$userFields = (isset($params['FIELDS']) ? $params['FIELDS'] : []);
		$path = (isset($params['PATH']) ? $params['PATH'] : '');
		$exportMode = (isset($params['EXPORT_MODE']) && $params['EXPORT_MODE']);

		if (
			empty($userFields)
			|| !isset($userFields['UF_DEPARTMENT'])
		)
		{
			return $result;
		}

		$departmentIdList = $userFields['UF_DEPARTMENT'];

		if ($departmentsData === null)
		{
			$structure = \CIntranetUtils::getStructure();
			$departmentsData = $structure['DATA'];
		}

		if (!is_array($departmentIdList))
		{
			$departmentIdList = [ $departmentIdList ];
		}

		$departmentNameList = [];

		foreach($departmentIdList as $departmentId)
		{
			if (
				!empty($departmentsData[$departmentId])
				&& isset($departmentsData[$departmentId]['NAME'])
				&& strlen($departmentsData[$departmentId]['NAME']) > 0
			)
			{
				$departmentName = ($exportMode ? $departmentsData[$departmentId]['NAME'] : htmlspecialcharsbx($departmentsData[$departmentId]['NAME']));
				$departmentNameList[] = (
					strlen($path) > 0
					&& !$exportMode
						? '<a href="'.htmlspecialcharsbx(str_replace('#ID#', $departmentId, $path)).'">'.$departmentName.'</a>'
						: $departmentName
				);
			}
		}

		$result = implode(', ', $departmentNameList);

		return $result;
	}

	public static function getNameFormattedValue(array $params = [])
	{
		static $nameTemplate = null;

		$result = '';

		$userFields = (isset($params['FIELDS']) ? $params['FIELDS'] : []);
		$path = (isset($params['PATH']) ? $params['PATH'] : '');
		$exportMode = (isset($params['EXPORT_MODE']) && $params['EXPORT_MODE']);
		$additionalData = (
			isset($params['ADDITIONAL_DATA'])
			&& is_array($params['ADDITIONAL_DATA'])
				? $params['ADDITIONAL_DATA']
				: []
		);

		if (empty($userFields))
		{
			return $result;
		}

		if ($nameTemplate === null)
		{
			$nameTemplate = \CSite::getNameFormat();
		}

		$result = \CUser::formatName($nameTemplate, $userFields, true, !$exportMode);

		if (
			!$exportMode
			&& strlen($result) > 0
			&& strlen($path) > 0
		)
		{
			$result = '<a href="'.htmlspecialcharsbx(str_replace(['#ID#', '#USER_ID#'], $userFields['ID'], $path)).'">'.$result.'</a>';
		}

		if (!$exportMode)
		{
			$statusClass = 'intranet-user-list-status';
			$statusClass .= ' intranet-user-list-status-'.(!empty($userFields['IS_ONLINE']) && $userFields['IS_ONLINE'] == 'Y' ? 'online' : 'offline');

			$result .= '<div class="'.$statusClass.'">'.Loc::getMessage('INTRANET_USER_LIST_STATUS_'.(!empty($userFields['IS_ONLINE']) && $userFields['IS_ONLINE'] == 'Y' ? 'ONLINE' : 'OFFLINE')).'</div>';

			if (!empty($additionalData['IS_ADMIN']))
			{
				$result .= '<div class="intranet-user-list-role">'.Loc::getMessage('INTRANET_USER_LIST_STATUS_ADMIN').'</div>';
			}
			if (!empty($additionalData['IS_INTEGRATOR']))
			{
				$result .= '<div class="intranet-user-list-role">'.Loc::getMessage('INTRANET_USER_LIST_STATUS_INTEGRATOR').'</div>';
			}
		}

		return $result;
	}

	public static function getPhotoValue(array $params = [])
	{
		$result = '<div class="intranet-user-list-userpic"></div>';

		$userFields = (isset($params['FIELDS']) ? $params['FIELDS'] : []);
//		$path = (isset($params['PATH']) ? $params['PATH'] : '');

		if (
			empty($userFields)
			|| empty($userFields['PERSONAL_PHOTO'])
		)
		{
			return $result;
		}

		$file = \CFile::getFileArray($userFields['PERSONAL_PHOTO']);
		if (!empty($file))
		{
			$fileResized = \CFile::resizeImageGet(
				$file,
				[
					'width' => 100,
					'height' => 100
				],
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);

			$result = '<div class="intranet-user-list-userpic" style="background-image: url(\''.$fileResized['src'].'\'); background-size: cover"></div>';
		}

		return $result;
	}

	public static function getActions(array $params = [])
	{
		global $USER;

		static $constantAllowed = null;

		$userFields = (isset($params['USER_FIELDS']) ? $params['USER_FIELDS'] : []);
		$currentUserId = $USER->getId();

		if ($constantAllowed === null)
		{
			$constantAllowed = [];
			$constantAllowed['MESSAGE'] = (
				ModuleManager::isModuleInstalled('im')
				&& \CBXFeatures::IsFeatureEnabled("WebMessenger")
			);
			$constantAllowed['TASK'] = (
				SITE_TEMPLATE_ID == 'bitrix24'
				&& \CBXFeatures::IsFeatureEnabled("Tasks")
			);
			$constantAllowed['INVITE'] = (
				(
					!ModuleManager::isModuleInstalled('bitrix24')
					&& $USER->canDoOperation('edit_all_users')
				)
				|| (
					ModuleManager::isModuleInstalled('bitrix24')
					&& $USER->canDoOperation('bitrix24_invite')
				)
			);
			$constantAllowed['EDIT_ALL'] = $USER->canDoOperation('edit_all_users');
			$constantAllowed['EDIT_SUBORDINATE'] = $USER->canDoOperation('edit_subordinate_users');
		}

		$result = [
			'view_profile'
		];

		if (
			$constantAllowed['TASK']
			&& empty($userFields['CONFIRM_CODE'])
			&& $userFields['ACTIVE']
		)
		{
			$result[] = 'add_task';
		}

		if (
			$constantAllowed['MESSAGE']
			&& $currentUserId != $userFields["ID"]
			&& $userFields["ACTIVE"] == "Y"
			&& empty($userFields['CONFIRM_CODE'])
		)
		{
			$result[] = 'message';
		}

		if (
			$constantAllowed['MESSAGE']
			&& $currentUserId != $userFields["ID"]
			&& empty($userFields['CONFIRM_CODE'])
		)
		{
			$result[] = 'message_history';
		}

		if (
			$constantAllowed['INVITE']
			&& !empty($userFields['CONFIRM_CODE'])
		)
		{
			$result[] = 'reinvite';
		}

		if (
			$currentUserId != $userFields["ID"]
			&& !in_array($userFields['USER_TYPE'], ['bot', 'imconnector'])
			&& (
				$constantAllowed['EDIT_ALL']
				|| (
					$constantAllowed['EDIT_SUBORDINATE']
					&& (count(array_diff(\CUser::getUserGroup($userFields['ID']), \CSocNetTools::getSubordinateGroups())) == 0)
				)
			)
			&& self::checkIntegratorActionRestriction([
				'userId' => $userFields["ID"]
			])
		)
		{
			if ($userFields["ACTIVE"] != 'Y')
			{
				$result[] = 'restore';
			}
			elseif (!empty($userFields["CONFIRM_CODE"]))
			{
				$result[] = 'delete';
			}
			else
			{
				$result[] = 'deactivate';
			}
		}

		if (
			$constantAllowed['MESSAGE']
			&& $currentUserId != $userFields["ID"]
			&& $userFields["ACTIVE"] == "Y"
			&& empty($userFields['CONFIRM_CODE'])
		)
		{
			$result[] = 'videocall';
		}

		return $result;
	}

	public function setActivityAction(array $params = [])
	{
		global $USER;

		$result = false;

		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$action = (!empty($params['action']) ? trim($params['action']) : '');

		if (
			$userId <= 0
			|| !in_array($action, ['restore', 'delete', 'deactivate'])
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$canEdit = (
			$USER->canDoOperation('edit_own_profile')
			|| $USER->isAdmin()
		);
		$currentUserPerms = \CSocNetUserPerms::initUserPerms(
			$USER->getId(),
			$userId,
			\CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, !(Loader::includeModule('bitrix24') && \CBitrix24::isPortalAdmin($USER->getId())))
		);

		if (
			$currentUserPerms["Operations"]["modifyuser_main"]
			&& $canEdit
			&& $userId != $USER->getId()
			&& self::checkIntegratorActionRestriction([
				'userId' => $userId
			])
		)
		{
			switch ($action)
			{
				case 'delete':
					$result = $USER->delete($userId);
					break;
				case 'restore':
				case 'deactivate':
					$result = $USER->update($userId, ['ACTIVE' => ($action == 'restore' ? 'Y' : 'N')]);
					break;
			}
		}

		return $result;
	}

	public function reinviteUserAction(array $params = [])
	{
		$result = false;

		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);
		$extranet = (!empty($params['extranet']) && $params['extranet'] == 'Y');

		if ($userId <= 0)
		{
			return $result;
		}
		if (!$extranet)
		{
			$result = \CIntranetInviteDialog::reinviteUser(SITE_ID, $userId);
		}
		elseif(preg_match("/^reinvite_user_id_(\\d+)\$/", $_REQUEST["reinvite"], $match))
		{
			$result = \CIntranetInviteDialog::reinviteExtranetUser(SITE_ID, $userId);
		}

		return $result;
	}

	public function exportAction(array $params = [])
	{
		global $APPLICATION;

		$componentParams = $this->arParams;
		$componentParams['EXPORT_MODE'] = 'Y';
		$componentParams['EXPORT_TYPE'] = $params['type'];

		$componentResult = $APPLICATION->includeComponent(
			'bitrix:intranet.user.list',
			'',
			$componentParams
		);
	}

	public static function getUserPropertyListDefault()
	{
		global $USER_FIELD_MANAGER;

		$result = [
			'PERSONAL_PHOTO',
			'FULL_NAME',
			'NAME',
			'SECOND_NAME',
			'LAST_NAME',
			'EMAIL',
			'DATE_REGISTER',
			'LAST_ACTIVITY_DATE',
			'PERSONAL_WWW',
			'PERSONAL_BIRTHDAY',
			'PERSONAL_GENDER',
			'PERSONAL_FAX',
			'PERSONAL_MOBILE',
			'PERSONAL_STREET',
			'PERSONAL_MAILBOX',
			'PERSONAL_CITY',
			'PERSONAL_STATE',
			'PERSONAL_ZIP',
			'PERSONAL_COUNTRY',
			'PERSONAL_NOTES',
			'WORK_POSITION',
			'WORK_PHONE',
			'WORK_FAX',
			'TAGS'
		];

		$profileWhiteList = UserProfile::getWhiteListOption();
		if (!empty($profileWhiteList))
		{
			$result = $profileWhiteList;
		}
		else
		{
			$userFieldsList = $USER_FIELD_MANAGER->getUserFields(UserTable::getUfId(), 0, LANGUAGE_ID, false);
			if (!empty($userFieldsList))
			{
				$result = array_merge($result, array_keys($userFieldsList));
			}
		}

		if (
			\Bitrix\Main\Loader::includeModule('extranet')
			&& \CExtranet::isExtranetSite()
		)
		{
			$result[] = 'WORK_COMPANY';
		}
		else
		{
			$result[] = 'UF_PHONE_INNER';
			$result[] = 'UF_DEPARTMENT';
			$result[] = 'TAGS';
		}

		return array_unique($result);
	}

	private function getUserPropertyListValue()
	{
		$result = [];
		$val = Option::get('intranet', 'user_list_user_property_available', false, SITE_ID);
		if (!empty($val))
		{
			$val = unserialize($val);
			if (
				is_array($val)
				&& !empty($val)
			)
			{
				$result = $val;
			}
		}

		return $result;
	}

	public function getUserPropertyList()
	{
		$optionValue = $this->getUserPropertyListValue();
		if (!empty($optionValue))
		{
			$result = $optionValue;
		}
		else
		{
			$result = self::getUserPropertyListDefault();
		}

		return $result;
	}

	public function setUserPropertyList(array $value = [])
	{
		$optionValue = $this->getUserPropertyListValue();
		$diff1 = array_diff($value, $optionValue);
		$diff2 = array_diff($optionValue, $value);
		if (
			!empty($diff1)
			|| !empty($diff2)
		)
		{
			Option::set('intranet', 'user_list_user_property_available', serialize($value), SITE_ID);
		}
	}

	protected static function checkIntegratorActionRestriction(array $params = [])
	{
		global $USER;
		static $currentIntegrator = null;

		$result = false;
		$userId = (!empty($params['userId']) ? intval($params['userId']) : 0);

		if ($userId <= 0)
		{
			return $result;
		}

		if ($currentIntegrator === null)
		{
			$currentIntegrator = (
				Loader::includeModule('bitrix24')
				&& Integrator::isIntegrator($USER->getId())
			);
		}

		return !(
			$currentIntegrator
			&& Loader::includeModule('bitrix24')
			&& \CBitrix24::isPortalAdmin($userId)
			&& !Integrator::isIntegrator($userId)
		);
	}
}
?>