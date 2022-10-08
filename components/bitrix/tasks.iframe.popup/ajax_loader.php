<?php

use \Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('PUBLIC_AJAX_MODE', true);

$SITE_ID = '';
if (isset($_GET["SITE_ID"]) && is_string($_GET['SITE_ID']))
	$SITE_ID = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $_GET["SITE_ID"]), 0, 2);

if ($SITE_ID != '')
	define("SITE_ID", $SITE_ID);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->ShowAjaxHead();

CUtil::JSPostUnescape();
CModule::IncludeModule('tasks');
CModule::IncludeModule('intranet');
CModule::IncludeModule('socialnetwork');

Loc::loadMessages(__FILE__);

$SITE_ID = isset($_GET["SITE_ID"]) ? $_GET["SITE_ID"] : SITE_ID;

if ( ! check_bitrix_sessid() )
	exit();

$nameTemplateForSite = CSite::GetNameFormat(false);

try
{
	CTaskAssert::assert(isset($_POST['requestsCount']));

	for ($i = 0; $i < $_POST['requestsCount']; $i++)
	{
		$inData = $_POST['data_' . $i];
		CTaskAssert::assert(isset($inData['requestedObject']));
		$nameTemplate = $nameTemplateForSite;

		if (isset($inData['nameTemplate']))
		{
			preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($inData['nameTemplate']), $matches);
			$nameTemplate = implode("", $matches[0]);
		}

		switch ($inData['requestedObject'])
		{
			case 'intranet.user.selector.new':
				if ( ! isset($inData['namespace']) )
					exit();

				$namespace = $inData['namespace'];
				$inputId   = null;

				if (isset($inData['inputId']))
					$inputId = $inData['inputId'];

				$multiple = 'N';
				if (isset($inData['multiple']) && ($inData['multiple'] === 'Y'))
					$multiple = 'Y';

				$onSelectFunctionName = null;
				if (isset($inData['onSelectFunctionName']) && mb_strlen($inData['onSelectFunctionName']))
					$onSelectFunctionName = $inData['onSelectFunctionName'];

				$onChangeFunctionName = null;
				if (isset($inData['onChangeFunctionName']) && mb_strlen($inData['onChangeFunctionName']))
					$onChangeFunctionName = $inData['onChangeFunctionName'];

				$selectedUsersIds = array();
				if (isset($inData['selectedUsersIds']))
				{
					if (is_array($inData['selectedUsersIds']))
						$selectedUsersIds = array_map('intval', $inData['selectedUsersIds']);
					else
						$selectedUsersIds = (int) $inData['selectedUsersIds'];
				}

				$GROUP_ID_FOR_SITE = false;
				if (isset($inData['GROUP_ID_FOR_SITE']) && ($inData['GROUP_ID_FOR_SITE'] > 0))
					$GROUP_ID_FOR_SITE = $inData['GROUP_ID_FOR_SITE'];

				$APPLICATION->IncludeComponent(
					'bitrix:intranet.user.selector.new',
					'.default',
					array(
						'MULTIPLE'          =>  $multiple,
						'NAME'              =>  $namespace,
						'INPUT_NAME'        =>  $inputId,
						'VALUE'             =>  $selectedUsersIds,
						'POPUP'             => 'Y',
						'ON_SELECT'         =>  $onSelectFunctionName,
						'ON_CHANGE'         =>  $onChangeFunctionName,
						//'PATH_TO_USER_PROFILE' => 'sdfgtdy',
						'SITE_ID'           =>  $SITE_ID,
						'GROUP_ID_FOR_SITE' =>  $GROUP_ID_FOR_SITE,
						'GROUP_SITE_ID'     =>  $SITE_ID,
						'SHOW_EXTRANET_USERS' => 'FROM_MY_GROUPS',
						'DISPLAY_TAB_GROUP' => 'Y',
						'NAME_TEMPLATE'     =>  $nameTemplate,
						'SHOW_LOGIN'		=> 'Y'
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);
			break;

			case 'socialnetwork.group.selector':
				CTaskAssert::assert(isset($inData['bindElement'], $inData['jsObjectName']));
				$onSelectFuncName = null;
				if (isset($inData['onSelectFuncName']) && mb_strlen($inData['onSelectFuncName']))
					$onSelectFuncName = $inData['onSelectFuncName'];

				$APPLICATION->IncludeComponent(
					"bitrix:socialnetwork.group.selector",
					".default",
					array(
						'BIND_ELEMENT'   => $inData['bindElement'],
						'ON_SELECT'      => $onSelectFuncName,
						'JS_OBJECT_NAME' => $inData['jsObjectName'],
						'FEATURES_PERMS' => array('tasks', 'create_tasks'),
						'SELECTED'       => 0
					),
					null,
					array("HIDE_ICONS" => "Y")
				);
			break;

			case 'LHEditor':
				CTaskAssert::assert(
					isset($inData['jsObjectName'], $inData['elementId'])
				);

				if(!CModule::IncludeModule("fileman"))
					ShowError('Cannot include fileman module');
				else
				{
					$Editor = new CHTMLEditor;
					$res = array_merge(
						array(
							'minBodyWidth' => 350,
							'minBodyHeight' => 200,
							'normalBodyWidth' => 555,
							'bAllowPhp' => false,
							'limitPhpAccess' => false,
							'showTaskbars' => false,
							'showNodeNavi' => false,
							'askBeforeUnloadPage' => true,
							'bbCode' => true,
							'siteId' => SITE_ID,
							'autoResize' => true,
							'autoResizeOffset' => 40,
							'saveOnBlur' => true,
							'setFocusAfterShow' => false,
							'controlsMap' => array(
								array('id' => 'Bold',  'compact' => true, 'sort' => 80),
								array('id' => 'Italic',  'compact' => true, 'sort' => 90),
								array('id' => 'Underline',  'compact' => true, 'sort' => 100),
								array('id' => 'Strikeout',  'compact' => true, 'sort' => 110),
								array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 120),
								array('id' => 'Color',  'compact' => true, 'sort' => 130),
								array('id' => 'FontSelector',  'compact' => false, 'sort' => 135),
								array('id' => 'FontSize',  'compact' => false, 'sort' => 140),
								array('separator' => true, 'compact' => false, 'sort' => 145),
								array('id' => 'OrderedList',  'compact' => true, 'sort' => 150),
								array('id' => 'UnorderedList',  'compact' => true, 'sort' => 160),
								array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
								array('separator' => true, 'compact' => false, 'sort' => 200),
								array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, /*'wrap' => 'bx-b-link-'.$arParams["FORM_ID"]*/),
								array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
								array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, /*'wrap' => 'bx-b-video-'.$arParams["FORM_ID"]*/),
								array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
								array('id' => 'Code',  'compact' => true, 'sort' => 260),
								array('id' => 'Quote',  'compact' => true, 'sort' => 270, /*'wrap' => 'bx-b-quote-'.$arParams["FORM_ID"]*/),
								//array('id' => 'Smile',  'compact' => false, 'sort' => 280),
								array('separator' => true, 'compact' => false, 'sort' => 290),
								array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
								array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
								array('id' => 'More',  'compact' => true, 'sort' => 400),
							)
						),
						/*(is_array($arParams["LHE"]) ? $arParams["LHE"] : array()),*/
						array(
							'name' => 'DESCRIPTION', // inputName
							'id' => $inData['elementId'],
							'width' => '100%',
							'arSmiles' => array(),
							'fontSize' => '14px',
							'iframeCss' =>
								'.bx-spoiler {border:1px solid #cecece;background-color:#f6f6f6;padding: 8px 8px 8px 24px;color:#373737;border-radius:var(--ui-border-radius-sm, 2px);min-height:1em;margin: 0;}'
								/*.(is_array($arParams["LHE"]) && isset($arParams["LHE"]["iframeCss"]) ? $arParams["LHE"]["iframeCss"] : ""),*/
						)
					);

					$Editor->Show($res);
				}

			break;

			case 'system.field.edit::WEBDAV':
			case 'system.field.edit::CRM':
				CTaskAssert::assert(
					isset(
						$inData['taskId'], $inData['userFieldName'],
						$inData['nameContainerId'], $inData['dataContainerId']
					)
					&& CTaskAssert::isLaxIntegers($inData['taskId'])
					&& is_string($inData['userFieldName'])
					&& ($inData['userFieldName'] !== '')
					&& is_string($inData['nameContainerId'])
					&& ($inData['nameContainerId'] !== '')
					&& is_string($inData['dataContainerId'])
					&& ($inData['dataContainerId'] !== '')
				);

				if (
					($inData['requestedObject'] === 'system.field.edit::CRM')
					&& ($inData['taskId'] == 0)
				)
				{
					break;
				}

				global $USER_FIELD_MANAGER;
				$arAvailableUserFieldsMeta = $USER_FIELD_MANAGER->GetUserFields(
					'TASKS_TASK', $inData['taskId'], LANGUAGE_ID
				);

				// We need only $inData['userFieldName']
				if ( ! isset($arAvailableUserFieldsMeta[$inData['userFieldName']]) )
					break;

				$arUserField = $arAvailableUserFieldsMeta[$inData['userFieldName']];

				if ($arUserField['EDIT_IN_LIST'] !== 'Y')
					break;

				echo '<div id="' . htmlspecialcharsbx($inData['nameContainerId']) . '">'
					. htmlspecialcharsbx($arUserField['EDIT_FORM_LABEL'])
					. '</div>';
				echo '<div id="' . htmlspecialcharsbx($inData['dataContainerId']) . '">';
				$APPLICATION->IncludeComponent(
					'bitrix:system.field.edit',
					$arUserField['USER_TYPE']['USER_TYPE_ID'],
					array(
						'bVarsFromForm'     =>  true,
						'arUserField'       =>  $arUserField,
						'form_name'         => 'quick-task-edit-form',
						'SHOW_FILE_PATH'    =>  false,
						'FILE_URL_TEMPLATE' => '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=#file_id#'
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);
				echo '</div>';
			break;

			default:
				throw new Exception('Unknown requestedObject: ' . $inData['requestedObject']);
			break;
		}
	}
}
catch (Exception $e)
{
	CTaskAssert::log(
		'Exception. Current file: ' . __FILE__
			. '; exception file: ' . $e->GetFile()
			. '; line: ' . $e->GetLine()
			. '; message: ' . $e->GetMessage(),
		CTaskAssert::ELL_ERROR
	);
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
exit();
