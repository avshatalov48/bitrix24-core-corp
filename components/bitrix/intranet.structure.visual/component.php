<?

use Bitrix\Main\Config\Option;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return;

$arDefaultParams = array(
	'DETAIL_URL' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
	'PROFILE_URL' => '/company/personal/user/#ID#/',
	'PM_URL' => '/company/personal/messages/chat/#ID#/',
	'PATH_TO_VIDEO_CALL' => '/company/personal/video/#ID#/'
);

foreach ($arDefaultParams as $param => $value)
{
	$arParams[$param] = $arParams[$param] ?? $value;
	$arParams['~'.$param] = $arParams['~'.$param] ?? $value;
}

$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID'] ?? null);

if ($arParams['IBLOCK_ID'] <= 0)
	$arParams['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_structure', 0);

if ($arParams["NAME_TEMPLATE"] == '')
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arParams['MAX_DEPTH'] = 3;

$arParams['USE_USER_LINK'] = $arParams['USE_USER_LINK'] == 'N' ? 'N' : 'Y';

$perm = CIBlock::GetPermission($arParams['IBLOCK_ID']);
if ($perm < 'R')
{
	$APPLICATION->AuthForm('');
	return;
}

$arResult['CAN_EDIT'] = $perm  >= 'U';

$newStruct = ($_REQUEST['newStruct'] ?? '') === 'Y';

if (\Bitrix\Main\Loader::includeModule('humanresources')
	&&
	($newStruct
		||
		\Bitrix\HumanResources\Config\Storage::instance()->isPublicStructureAvailable()
	)
)
{
	LocalRedirect('/hr/structure/');
}

$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

if (isset($_REQUEST['action']) && $arResult['CAN_EDIT'] && check_bitrix_sessid())
{
	$action = $_REQUEST['action'];
	$res = null;

	$arUndo = array();

	if (!function_exists('__intr_process_action'))
	{
		function __intr_process_action($action, $arParams, &$res, &$mode, &$arUndo, &$undo_text)
		{
			global $DB;

			$IBLOCK_ID = $arParams['IBLOCK_ID'];

			switch($action)
			{
				case 'set_department_head':
					$dpt = intval($_REQUEST['dpt_id'] ?? 0);
					$user_id = intval($_REQUEST['user_id'] ?? 0);

					$ok = false;

					if ($dpt > 0 && $user_id > 0)
					{
						$dbUser = CUser::GetByID($user_id);
						if ($arUser = $dbUser->Fetch())
						{
							$dbRes = CIBlockSection::GetList(
								array(),
								array("ID" => $dpt,"IBLOCK_ID" => $IBLOCK_ID,"CHECK_PERMISSIONS" => "Y"),
								false,
								array('NAME', 'UF_HEAD')
							);
							if($arSection = $dbRes->GetNext())
							{
								$ok = true;

								$ob = new CIBlockSection();
								if ($ob->Update($dpt, array('UF_HEAD' => $user_id)))
								{
									$arUndo[] = array(
										'action' => 'set_department_head',
										'dpt_id' => $dpt,
										'user_id' => $arSection['UF_HEAD']
									);

									$undo_text = GetMessage('ISV_set_department_head', array(
										"#NAME#" => CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser),
										"#DEPARTMENT#" => $arSection['NAME']
									));

									$mode = 'reload';
								}
								else
								{
									$res = array('error' => trim(str_replace('<br>', "\n", $ob->LAST_ERROR)));
								}
							}
						}
					}

					if (!$ok)
					{
						$res = array('error' => 'Wrong data');
					}

				// we should change user department too, but have to return error if one exists
					if (is_array($res))
						break;

				case 'change_department':
					$user_id = intval($_REQUEST['user_id'] ?? 0);
					$dpt_id = intval($_REQUEST['dpt_id'] ?? 0);
					$dpt_from = intval($_REQUEST['dpt_from'] ?? 0);

					$type = intval($_REQUEST['type'] ?? 0);

					$ok = false;
					if ($user_id > 0 && $dpt_id > 0)
					{
						if (isset($arUser))
						{
							$arRes = $arUser;
						}
						else
						{
							$dbRes = CUser::GetByID($user_id);
							$arRes = $dbRes->Fetch();
						}

						if (!isset($arSection))
						{
							$dbRes =  CIBlockSection::GetList(
								array(),
								array("ID" => $dpt_id, 'IBLOCK_ID' => $IBLOCK_ID, "CHECK_PERMISSIONS" => "Y")
							);
							$arSection = $dbRes->GetNext();
						}

						if ($arRes && $arSection)
						{
							$ok = true;

							$arOldDpt = $arDpt = $arRes['UF_DEPARTMENT'];
							if ($type != 1)
							{
								foreach ($arDpt as $key => $dpt)
								{
									if ($dpt == $dpt_from)
									{
										unset($arDpt[$key]);
										break;
									}
								}
							}

							$arDpt[] = $dpt_id;
						}

						$obUser = new CUser();
						if ($obUser->Update($user_id, array('UF_DEPARTMENT' => array_unique($arDpt))))
						{
							if ($type != 1)
							{
								$arUndo[] = array(
									'action' => 'change_department',
									'user_id' => $user_id,
									'dpt_id' => $dpt_from,
									'dpt_from' => $dpt_id
								);

								if (!$undo_text)
								{
									$undo_text = GetMessage("ISV_change_department", array(
										"#NAME#" => CUser::FormatName($arParams['NAME_TEMPLATE'], $arRes),
										"#DEPARTMENT#" => $arSection['NAME']
									));
								}
							}

							if ($type != 1 && ($action != 'set_department_head' || $dpt_id != $dpt_from))
							{
								$dbRes = CIBlockSection::GetList(array(), array('ID' => $dpt_from, 'IBLOCK_ID' => $IBLOCK_ID, 'UF_HEAD' => $user_id));
								if ($arSection = $dbRes->GetNext())
								{
									$arUndo[] = array(
										'action' => 'set_department_head',
										'dpt_id' => $dpt_from,
										'user_id' => $user_id
									);

									$obIBlockSection = new CIBlockSection();
									$obIBlockSection->Update($dpt_from, array('UF_HEAD' => 0));
								}
							}
						}
					}

					if (!$ok)
					{
						$res = array('error' => 'Wrong data');
					}
					else
					{
						$mode = 'reload';
					}
				break;

				case 'delete_department':
					$dpt = intval($_REQUEST['dpt_id'] ?? 0);

					$dbRes = CIBlockSection::GetList(
						array(),
						array("ID" => $dpt, "IBLOCK_ID" => $IBLOCK_ID, "CHECK_PERMISSIONS" => "Y")
					);
					if($arSection = $dbRes->Fetch())
					{
						if ($arSection['IBLOCK_SECTION_ID'] > 0)
						{
							$dbRes = CUser::GetList(
								'', '',
								array('UF_DEPARTMENT' => $dpt),
								array('SELECT' => array('ID', 'UF_DEPARTMENT'))
							);

							$GLOBALS['DB']->StartTransaction();

							$obUser = new CUser();
							while ($arRes = $dbRes->fetch())
							{
								if (count($arRes['UF_DEPARTMENT']) > 1)
								{
									$newDpt = $arRes['UF_DEPARTMENT'];
									$deletedDptKey = array_search($dpt, $arRes['UF_DEPARTMENT']);
									unset($newDpt[$deletedDptKey]);
								}
								else
								{
									$newDpt = [$arSection['IBLOCK_SECTION_ID']];
								}
								$obUser->update($arRes['ID'], array('UF_DEPARTMENT' => $newDpt));
							}

							$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $IBLOCK_ID, 'SECTION_ID' => $arSection['ID']));

							$obIBlockSection = new CIBlockSection();
							while ($arRes = $dbRes->Fetch())
							{
								$obIBlockSection->Update($arRes['ID'], array(
									'IBLOCK_SECTION_ID' => $arSection['IBLOCK_SECTION_ID'])
								);
							}

							if ($obIBlockSection->Delete($arSection['ID']))
							{
								$GLOBALS['DB']->Commit();
								$mode = 'reload';
							}
							else
							{
								$GLOBALS['DB']->Rollback();
								$res = array('error' => trim(str_replace('<br>', "\n", $ob->LAST_ERROR)));
							}
						}
					}

				break;

				case 'move_department':
					$dpt = intval($_REQUEST['dpt_id'] ?? 0);
					$dpt_to = intval($_REQUEST['dpt_to'] ?? 0);

					$ok = false;

					if ($dpt > 0 && $dpt_to > 0)
					{
						$dbRes = CIBlockSection::GetList(
							array(),
							array("ID" => array($dpt, $dpt_to), 'IBLOCK_ID' => $IBLOCK_ID, "CHECK_PERMISSIONS" => "Y")
						);
						while ($arRes = $dbRes->GetNext())
						{
							if ($arRes['ID'] == $dpt)
								$arSection = $arRes;
							else
								$arSectionTo = $arRes;
						}

						if ($arSection && $arSectionTo)
						{

							$ok = true;

							$ob = new CIBlockSection();

							$DB->startTransaction();
							if ($ob->Update($dpt, array('IBLOCK_SECTION_ID' => $dpt_to)))
							{
								$DB->commit();
								$mode = 'reload';
								$arUndo[] = array(
									'action' => 'move_department',
									'dpt_id' => $dpt,
									'dpt_to' => $arSection['IBLOCK_SECTION_ID']
								);

								$undo_text = GetMessage('ISV_move_department', array(
									"#DEPARTMENT#" => $arSection["NAME"],
									"#DEPARTMENT_TO#" => $arSectionTo["NAME"],
								));
							}
							else
							{
								$DB->rollback();
								$res = array('error' => trim(str_replace('<br>', "\n", $ob->LAST_ERROR)));
							}
						}
					}

					// we should resort departments in this case
					if (is_array($res) || empty($_REQUEST['dpt_parent']))
					{
						break;
					}

				case 'sort_department':

					$dpt_id = intval($_REQUEST['dpt_id'] ?? 0);
					$dpt_before = intval($_REQUEST['dpt_before'] ?? 0);
					$dpt_after = intval($_REQUEST['dpt_after'] ?? 0);
					$dpt_parent = intval($_REQUEST['dpt_parent'] ?? 0);

					$ok = false;

					if ($dpt_id > 0 && $dpt_parent > 0 && ($dpt_before > 0 || $dpt_after > 0))
					{
						$arSections = array();

						$dbRes = CIBlockSection::GetList(array('left_margin' => 'ASC'), array(
							'IBLOCK_ID' => $IBLOCK_ID,
							'SECTION_ID' => $dpt_parent
						));

						$arCurrentSection = array();
						$arSections = array();

						$sortAfter = 0;
						$sortBefore = 0;

						while ($arSection = $dbRes->GetNext())
						{
							if ($arSection['ID'] != $dpt_id)
							{
								$arSections[] = $arSection;
							}
							else
							{
								$arCurrentSection = $arSection;
							}

							if ($arSection['ID'] == $dpt_after)
								$sortAfter = $arSection['SORT'];
							elseif ($arSection['ID'] == $dpt_before)
								$sortBefore = $arSection['SORT'];
						}

						$GLOBALS['APPLICATION']->RestartBuffer();

						$new_sort = -1;
						if (!$dpt_before && $sortAfter > 0)
							$new_sort = $sortAfter + 100;
						elseif (!$dpt_after && $sortBefore > 1)
							$new_sort = round($sortBefore/2);
						elseif ($dpt_before && $dpt_after && abs($sortBefore-$sortAfter) > 2)
							$new_sort = round(abs($sortBefore+$sortAfter)/2);

						$obSection = new CIBlockSection();
						if ($new_sort > 0)
						{
							// simple variant: update just one section

							$ok = true;

							if (!$obSection->Update($dpt_id, array('SORT' => $new_sort)))
							{
								$res = array('error' => trim(str_replace('<br>', "\n", $ob->LAST_ERROR)));
							}
						}
						else
						{
							if ($dpt_before && $dpt_after)
							{
								foreach ($arSections as $key => $arSection)
								{
									if ($arSection['ID'] == $dpt_before || $arSection['ID'] == $dpt_after)
									{
										$arSections = array_merge(
											array_slice($arSections, 0, $key+1),
											array($arCurrentSection),
											array_slice($arSections, $key+1)
										);
										break;
									}
								}
							}
							else if (!$dpt_after)
							{
								array_unshift($arSections, $arCurrentSection);
							}
							else
							{
								$arSections[] = $arCurrentSection;
							}

							$GLOBALS['DB']->StartTransaction();
							$sort = 0;
							foreach ($arSections as $arSection)
							{
								$sort += 100;
								if (!$obSection->Update(
									$arSection['ID'],
									array('SORT' => $sort),
									false
								))
								{
									$res = array('error' => trim(str_replace('<br>', "\n", $ob->LAST_ERROR)));
									$GLOBALS['DB']->Rollback();
									break;
								}
							}

							if (!is_array($res))
							{
								CIBlockSection::ReSort($IBLOCK_ID);
								$GLOBALS['DB']->Commit();
								$ok = true;
							}
						}
					}

					if (!is_array($res))
					{
						if (!$ok)
						{
							$res = array('error' => 'Wrong data');
						}
						else
						{
							$mode = 'reload';
						}
					}

				break;
			}
		}

		function __intr_process_undo($arUndoParams)
		{
			list($arUndoParams, $undo_type) = $arUndoParams;

			if ($undo_type == 'intranet.structure.visual' && is_array($arUndoParams))
			{
				foreach ($arUndoParams as $arUndo)
				{
					$action = $arUndo['action'];

					foreach ($arUndo as $key => $value)
						$_REQUEST[$key] = $value;

					$fake_res = [];
					$fake_mode = '';
					$fake_arUndo = [];
					$fake_undo_text = '';
					__intr_process_action(
						$action,
						['IBLOCK_ID' => $GLOBALS['VISUAL_STRUCTURE_IBLOCK_ID']],
						$fake_res,
						$fake_mode,
						$fake_arUndo,
						$fake_undo_text
					);
				}
			}
		}
	}

	if ($action == 'undo')
	{
		$GLOBALS['VISUAL_STRUCTURE_IBLOCK_ID'] = $arParams['IBLOCK_ID'];
		CUndo::Escape($_REQUEST['undo'] ?? null);
		$mode = 'reload';
	}
	else
	{
		$undo_text = '';
		__intr_process_action($action, $arParams, $res, $mode, $arUndo, $undo_text);
	}

	if (is_array($arUndo) && count($arUndo) > 0)
	{
		$arResult['UNDO_TEXT'] = $undo_text;
		$arResult['UNDO_ID'] = CUndo::Add(array(
			'module' => 'intranet',
			'undoType' => 'intranet.structure.visual',
			'undoHandler' => '__intr_process_undo',
			'arContent' => $arUndo
		));
	}

	if (is_array($res) && $mode != 'reload')
	{
		$APPLICATION->RestartBuffer();

		echo CUtil::PhpToJsObject($res);

		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die();
	}
}

if ($mode == 'subtree')
{
	$arParams['LEVEL'] = intval($_REQUEST['level'] ?? 0);
	$SECTION_ID = intval($_REQUEST['section'] ?? 0);

	$arResult['HAS_MULTIPLE_ROOTS'] = isset($_REQUEST['mr']) && $_REQUEST['mr'] == 'Y';

	$cache_id = $mode.'|'.$SECTION_ID.'|'.$arResult['HAS_MULTIPLE_ROOTS'].'|'.$arParams['LEVEL'];

	$dbRes = CIBlockSection::GetByID($SECTION_ID);
	$arCurrentSection = $dbRes->Fetch();
}
else
{
	$cache_id = '';

	$arResult['HAS_MULTIPLE_ROOTS'] = false;
	$arParams['LEVEL'] = 0;
}

if ($mode == 'subtree')
{
	$APPLICATION->RestartBuffer();
	$arResult['__SKIP_ROOT'] = 'Y';
}
elseif ($mode == 'reload')
{
	$APPLICATION->RestartBuffer();
}

$bCache = true;
if ($this->StartResultCache(false, $arParams['IBLOCK_ID'].'|'.$arResult['CAN_EDIT'].'|'.($arResult['UNDO_ID'] ?? null).'|'.$cache_id))
{
	$bCache = false;

	global $CACHE_MANAGER;

	$CACHE_MANAGER->RegisterTag("intranet_users");
	$CACHE_MANAGER->RegisterTag("iblock_id_".$arParams['IBLOCK_ID']);
	$CACHE_MANAGER->RegisterTag("intranet_department_structure");

	if ($mode != 'subtree' || $arCurrentSection)
	{
		$arFilter = array(
			'IBLOCK_ID' => $arParams['IBLOCK_ID'],
			'GLOBAL_ACTIVE' => 'Y',
		);

		if ($mode == 'subtree')
		{
			$arParams['MAX_DEPTH'] += $arCurrentSection['DEPTH_LEVEL']-1;

			$arFilter = array_merge($arFilter, array(
				'>LEFT_MARGIN' => $arCurrentSection['LEFT_MARGIN'],
				'<RIGHT_MARGIN' => $arCurrentSection['RIGHT_MARGIN'],
				'!ID' => $arCurrentSection['ID'], // little hack because of the iblock module minor bug
			));
		}

		$arFilter['<=DEPTH_LEVEL'] = $arParams['MAX_DEPTH'];

		$dbRes = CIBlockSection::GetList(
			array('left_margin' => 'asc'),
			$arFilter,
			false,
			array('UF_HEAD')
		);

		$arResult['ENTRIES'] = array();
		$arHeads = array();
		while ($arRes = $dbRes->Fetch())
		{

			if ($arRes['IBLOCK_SECTION_ID'] <= 0 && $arRes['LEFT_MARGIN'] > 1)
				$arResult['HAS_MULTIPLE_ROOTS'] = true;

			if ($arRes['UF_HEAD'])
				$arHeads[] = $arRes['UF_HEAD'];

			if ($arParams['DETAIL_URL'])
				$arRes['DETAIL_URL'] = str_replace(array('#ID#', '#SECTION_ID#'), $arRes['ID'], $arParams['DETAIL_URL']);

			if ($arRes['PICTURE'])
			{
				$arRes['PICTURE'] = CIntranetUtils::InitImage($arRes['PICTURE'], 100);
			}

			$arResult['ENTRIES'][$arRes['ID']] = array(
				'ID' => $arRes['ID'],
				'IBLOCK_SECTION_ID' => $arRes['IBLOCK_SECTION_ID'],
				'UF_HEAD' => $arRes['UF_HEAD'],
				'NAME' => $arRes['NAME'],
				'PICTURE' => $arRes['PICTURE'],
				'DETAIL_URL' => $arRes['DETAIL_URL'],
				'RIGHT_MARGIN' => $arRes['RIGHT_MARGIN'],
				'LEFT_MARGIN' => $arRes['LEFT_MARGIN'],
				'DEPTH_LEVEL' => $arRes['DEPTH_LEVEL']
			);
		}

		$result = $DB->query(sprintf(
			"SELECT ID, IBLOCK_SECTION_ID FROM b_iblock_section
				WHERE IBLOCK_ID = %u AND GLOBAL_ACTIVE = 'Y' %s ORDER BY LEFT_MARGIN ASC",
			$arParams['IBLOCK_ID'],
			$mode == 'subtree' && $arCurrentSection['LEFT_MARGIN'] > 0 && $arCurrentSection['RIGHT_MARGIN'] > 0
				? sprintf('AND LEFT_MARGIN > %u AND RIGHT_MARGIN < %u', $arCurrentSection['LEFT_MARGIN'], $arCurrentSection['RIGHT_MARGIN']) : ''
		));

		$tree = array();
		while ($item = $result->fetch())
		{
			$tree[$item['ID']] = array('p' => (int) $item['IBLOCK_SECTION_ID'], 'c' => 0);

			$nodeId   = $item['ID'];
			$parentId = $item['IBLOCK_SECTION_ID'];
			$chain    = array($nodeId);
			while ($parentId > 0 && !in_array($parentId, $chain))
			{
				if (!isset($tree[$parentId]['c']))
				{
					$tree[$parentId]['c'] = 0;
				}

				$tree[$parentId]['c']++;

				$nodeId   = $parentId;
				$parentId = $tree[$parentId]['p'] ?? null;
				$chain[]  = $nodeId;
			}
		}

		foreach ($arResult['ENTRIES'] as $key => $dummy)
			$arResult['ENTRIES'][$key]['__children'] = $tree[$key]['c'];
		unset($tree);

		if ($arResult['HAS_MULTIPLE_ROOTS'])
		{
			foreach($arResult['ENTRIES'] as $key => $arEntry)
			{
				$arEntry['DEPTH_LEVEL']++;
				if ($arEntry['IBLOCK_SECTION_ID'] <= 0)
					$arEntry['IBLOCK_SECTION_ID'] = 'q';

				$arResult['ENTRIES'][$key] = $arEntry;
			}

			if ($mode != 'subtree')
			{
				$company_name = COption::GetOptionString("main", "site_name", "");
				if(!$company_name)
				{
					$dbrs = CSite::GetList('', '', Array("DEFAULT"=>"Y"));
					if($ars = $dbrs->Fetch())
						$company_name = $ars["NAME"];
				}

				if (!$company_name)
					$company_name = 'root';

				// hack to save numerical keys
				$arKeys = array_keys($arResult['ENTRIES']);
				$arEntries = $arResult['ENTRIES'];

				$arResult['ENTRIES'] = array('q' => array(
					'ID' => -1,
					'DEPTH_LEVEL' => 1,
					'NAME' => $company_name,
				));
				foreach ($arKeys as $key) $arResult['ENTRIES'][$key] = $arEntries[$key];
			}
			else
			{
				$arParams['MAX_DEPTH']++;
			}
		}

		$arResult['USERS'] = array();
		if (count($arHeads) > 0)
		{
			$arHeads = array_unique($arHeads);
			$dbRes = CUser::GetList(
				'last_name', 'asc',
				array('ACTIVE' => 'Y', 'ID' => implode('|', $arHeads))
			);
			while ($arRes = $dbRes->Fetch())
			{
				$CACHE_MANAGER->RegisterTag("intranet_user_".$arRes['ID']);

				if ($arParams['PROFILE_URL'])
				{
					$arRes['PROFILE_URL'] = str_replace(array('#ID#', '#USER_ID#'), $arRes['ID'], $arParams['PROFILE_URL']);
				}

				if (intval($arRes["PERSONAL_PHOTO"]) <= 0)
				{
					switch($arRes["PERSONAL_GENDER"])
					{
						case "M":
							$suffix = "male";
							break;
						case "F":
							$suffix = "female";
							break;
						default:
							$suffix = "unknown";
					}
					$arRes["PERSONAL_PHOTO"] = Option::get('socialnetwork', 'default_user_picture_'.$suffix, false, SITE_ID);
				}

				if ($arRes['PERSONAL_PHOTO'] > 0)
				{
					$arRes['PERSONAL_PHOTO'] = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT);
				}

				$arResult['USERS'][$arRes['ID']] = array(
					'ID' => $arRes['ID'],
					'LOGIN' => $arRes['LOGIN'],
					'NAME' => $arRes['NAME'],
					'LAST_NAME' => $arRes['LAST_NAME'],
					'SECOND_NAME' => $arRes['SECOND_NAME'],
					'PROFILE_URL' => $arRes['PROFILE_URL'],
					'PERSONAL_PHOTO' => $arRes['PERSONAL_PHOTO'],
					'WORK_POSITION' => $arRes['WORK_POSITION'],
					'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT'] ?? null,
				);
			}

			foreach ($arResult['ENTRIES'] as $id => $item)
			{
				if ($item['UF_HEAD'] > 0 && empty($arResult['USERS'][$item['UF_HEAD']]))
					$arResult['ENTRIES'][$id]['UF_HEAD'] = null;
			}
		}

		if (count($arResult['ENTRIES']) > 0)
		{
			$dbRes = CUser::GetList(
				'last_name', 'asc',
				array('ACTIVE' => 'Y', 'UF_DEPARTMENT' => array_keys($arResult['ENTRIES'])),
				array('SELECT' => array('UF_DEPARTMENT'))
			);

			$last_id = null;
			while ($arRes = $dbRes->Fetch())
			{
				if ($arRes['ID'] == $last_id) // hack to fix mssql main bug
					continue;

				$CACHE_MANAGER->RegisterTag("intranet_user_".$arRes['ID']);

				$last_id = $arRes['ID'];

				if (intval($arRes["PERSONAL_PHOTO"]) <= 0)
				{
					switch($arRes["PERSONAL_GENDER"])
					{
						case "M":
							$suffix = "male";
							break;
						case "F":
							$suffix = "female";
							break;
						default:
							$suffix = "unknown";
					}
					$arRes["PERSONAL_PHOTO"] = Option::get('socialnetwork', 'default_user_picture_'.$suffix, false, SITE_ID);
				}

				if ($arRes['PERSONAL_PHOTO'] > 0)
				{
					$arRes['PERSONAL_PHOTO'] = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT);
				}

				$arRes['PROFILE_URL'] = str_replace(array('#ID#', '#USER_ID#'), $arRes['ID'], $arParams['PROFILE_URL']);

				$arRes = array(
					'ID' => $arRes['ID'],
					'LOGIN' => $arRes['LOGIN'],
					'NAME' => $arRes['NAME'],
					'LAST_NAME' => $arRes['LAST_NAME'],
					'SECOND_NAME' => $arRes['SECOND_NAME'],
					'PROFILE_URL' => $arRes['PROFILE_URL'],
					'PERSONAL_PHOTO' => $arRes['PERSONAL_PHOTO'],
					'WORK_POSITION' => $arRes['WORK_POSITION'],
					'UF_DEPARTMENT' => $arRes['UF_DEPARTMENT']
				);

				foreach ($arRes['UF_DEPARTMENT'] as $dpt)
				{
					if (!isset($arResult['ENTRIES'][$dpt]))
						continue;

					if (!isset($arResult['ENTRIES'][$dpt]['EMPLOYEES']))
						$arResult['ENTRIES'][$dpt]['EMPLOYEES'] = array($arRes);
					else
						$arResult['ENTRIES'][$dpt]['EMPLOYEES'][] = $arRes;
				}


			}
		}

		$arParams['MODE'] = $mode;
		$this->IncludeComponentTemplate();
	}
}

if ($mode == 'subtree' || $mode == 'reload')
{
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
	die();
}
else
{
	CJSCore::Init(array('ajax', 'popup', 'ui.forms'));

	if ($arResult['CAN_EDIT'])
	{
		CJSCore::Init(array('intranet_structure'));
		$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');
	}

	if ($bCache)
	{
		$this->InitComponentTemplate();
	}

	$template =& $this->GetTemplate();
	$APPLICATION->AddHeadScript($template->GetFolder().'/structure.js');
	$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

	if (!defined('INTRANET_ISV_MUL_INCLUDED'))
	{
		$APPLICATION->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"AJAX_ONLY" => "Y",
				"PATH_TO_SONET_USER_PROFILE" => COption::GetOptionString('intranet', 'search_user_url', '/company/personal/user/#ID#/'),
				"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PM_URL"],
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"] ?? null,
				"SHOW_YEAR" => $arParams["SHOW_YEAR"] ?? null,
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"SHOW_LOGIN" => $arParams["SHOW_LOGIN"] ?? null,
				"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"] ?? null,
				"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		define('INTRANET_ISV_MUL_INCLUDED', 1);
	}
}
?>
