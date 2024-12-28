<?

use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\UI;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$helper = new \Bitrix\Tasks\UI\Component\TemplateHelper('TasksWidgetMemberSelectorView', $this, array(
	'RELATION' => array(
		'tasks_util',
		'tasks_itemsetpicker',
	),
	'METHODS' => array(
		'formatUser' => function(array $user, array $arParams)
		{
			if(!count($user))
			{
				return array();
			}
			if (($user['COLLABER_NO_HAS_COMMON_GROUP'] ?? null))
			{
				$collaberNoHasCommonGroup = $user['COLLABER_NO_HAS_COMMON_GROUP'];
			}

			$user = User::extractPublicData($user);
			$user['COLLABER_NO_HAS_COMMON_GROUP'] = !empty($collaberNoHasCommonGroup) ?? null;
			$user['VALUE'] = $user['ID'];
			$user['DISPLAY'] = User::formatName($user, false, $arParams['NAME_TEMPLATE']);

			$user['AVATAR'] = UI::getAvatar($user['PERSONAL_PHOTO'] ?? null, 100, 100);
			$user['AVATAR_CSS'] =
				$user['AVATAR'] ?
					"background: url('".Uri::urnEncode($user['AVATAR'])."') center no-repeat; background-size: 35px;" :
					''
			;

			$userType = match (true)
			{
				$user['IS_CRM_EMAIL_USER'] => 'crmemail',
				$user['IS_EMAIL_USER'] => 'mail',
				$user['IS_COLLABER_USER'] ?? false => 'collaber',
				$user['IS_EXTRANET_USER'] => 'extranet',
				$user['IS_NETWORK_USER'] ?? false => 'network',
				default => 'employee',
			};


			$user['USER_TYPE'] = $userType;
			$user['ITEM_SET_INVISIBLE'] = '';

			if($user['ID'])
			{
				$user['URL'] = CComponentEngine::makePathFromTemplate($arParams["PATH_TO_USER_PROFILE"], array('user_id' => $user['ID']));
			}
			else
			{
				$user['URL'] = 'javascript:void(0);';
			}

			return $user;
		}
	),
));

return $helper;
