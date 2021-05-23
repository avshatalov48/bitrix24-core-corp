<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

foreach ($arResult['PROJECTS'] as $groupId => &$arProject)
{
	$image = \Bitrix\Tasks\UI::getAvatarFile($arProject['IMAGE_ID'], array('WIDTH' => 100, 'HEIGHT' => 100));
	$arImage = array(
		'FILE' => $image['ORIGIN'],
		'IMG' => '<a href="'.$arProject['PATHES']['IN_WORK'].'" class="profile-menu-avatar" '.($image['RESIZED']['SRC'] ? 'style="background:url(\''.htmlspecialcharsbx($image['RESIZED']['SRC']).'\') no-repeat center center; background-size: 100%;"' : '').'></a>',
	);

	$arHeads = array();
	$arNotHeads = array();
	$arMembersForJs = array();
	foreach ($arProject['MEMBERS'] as $arMember)
	{
		$arMember['PHOTO_SRC'] = $this->__component->getUserPictureSrc(
			$arMember['PHOTO_ID'],
			$arMember['USER_GENDER'],
			100,
			100
		);

		$arMemberForJs = array(
			'ID'       => $arMember['ID'],
			'NAME'     => $arMember['FORMATTED_NAME'],
			'PHOTO'    => $this->__component->getUserPictureSrc(
				$arMember['PHOTO_ID'],
				$arMember['USER_GENDER'],
				100,
				100
			),
			'PROFILE'  => $arMember['HREF'],
			'POSITION' => $arMember['WORK_POSITION']
		);

		if (
			($arMember['IS_GROUP_OWNER'] === 'Y')
			|| ($arMember['IS_GROUP_MODERATOR'] === 'Y')
		)
		{
			$arHeads[] = $arMember;

			if ($arMember['IS_GROUP_OWNER'] === 'Y')
				$arMemberForJs['IS_HEAD'] = true;
			else
				$arMemberForJs['IS_HEAD'] = false;
		}
		else
		{
			$arNotHeads[] = $arMember;
			$arMemberForJs['IS_HEAD'] = false;
		}

		$arMembersForJs[] = CUtil::PhpToJsObject($arMemberForJs);
	}

	$arProject['IMAGE_HTML']      =  $arImage['IMG'];
	$arProject['MEMBERS_FOR_JS']  = '[' . implode(', ', $arMembersForJs) . ']';
	$arProject['HEADS']           =  $arHeads;
	$arProject['HEADS_COUNT']     =  count($arHeads);
	$arProject['NOT_HEADS_COUNT'] =  count($arNotHeads);
}
unset($arProject);

usort(
	$arResult['PROJECTS'],
	function($a, $b){
		if ($a['COUNTERS']['IN_WORK'] < $b['COUNTERS']['IN_WORK'])
			return (1);
		elseif ($a['COUNTERS']['IN_WORK'] > $b['COUNTERS']['IN_WORK'])
			return (-1);
		else
			return strcmp($a['TITLE'], $b['TITLE']);
	}
);
