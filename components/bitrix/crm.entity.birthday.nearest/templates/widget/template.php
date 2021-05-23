<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
?><div class="crm-right-block sidebar-widget sidebar-widget-birthdays">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title"><?=GetMessage('CRM_ENT_BDAY_TITLE')?></div>
	</div><?

$items = $arResult['ITEMS'];
$nameFormat = $arResult['NAME_FORMAT'];
$dateFormat = $arResult['DATE_FORMAT'];

$count = count($items);
for($i = 0; $i < $count; $i++)
{
	$item = $items[$i];
	$isBirthday = (isset($item['IS_BIRTHDAY']) && $item['IS_BIRTHDAY']);

	$className = 'sidebar-widget-item';
	if($isBirthday)
	{
		$className .= ' today-birth';
	}

	if($i === ($count - 1))
	{
		$className .= ' widget-last-item';
	}

	if($item['ENTITY_TYPE_ID'] === CCrmOwnerType::Contact)
	{
		$name = CCrmContact::PrepareFormattedName(
			array(
				'HONORIFIC' => isset($item['HONORIFIC']) ? $item['HONORIFIC'] : '',
				'NAME' => isset($item['NAME']) ? $item['NAME'] : '',
				'LAST_NAME' => isset($item['LAST_NAME']) ? $item['LAST_NAME'] : '',
				'SECOND_NAME' => isset($item['SECOND_NAME']) ? $item['SECOND_NAME'] : ''
			),
			$nameFormat
		);
	}
	else//if($item['ENTITY_TYPE_ID'] === CCrmOwnerType::Lead)
	{
		$name = CCrmLead::PrepareFormattedName(
			array(
				'HONORIFIC' => isset($item['HONORIFIC']) ? $item['HONORIFIC'] : '',
				'NAME' => isset($item['NAME']) ? $item['NAME'] : '',
				'LAST_NAME' => isset($item['LAST_NAME']) ? $item['LAST_NAME'] : '',
				'SECOND_NAME' => isset($item['SECOND_NAME']) ? $item['SECOND_NAME'] : ''
			),
			$nameFormat
		);
	}

	$text = (isset($item['IS_BIRTHDAY']) && $item['IS_BIRTHDAY'])
		? FormatDate('today').'!'
		: (isset($item['BIRTHDATE']) ? FormatDateEx($item['BIRTHDATE'], false, $dateFormat) : '');

	$url = '';
	$imageID = $item['IMAGE_ID'];
	if($imageID > 0)
	{
		$file = new CFile();
		$imageFileArray = $file->GetFileArray($imageID);
		if(is_array($imageFileArray))
		{
			$imageFile = $file->ResizeImageGet($imageFileArray, array('width' => 58, 'height' => 58), BX_RESIZE_IMAGE_EXACT, true);
			$url = is_array($imageFile) ? $imageFile['src'] : '';
		}
	}

	$avatarStyle = $url !== '' ? 'background:url(\''.htmlspecialcharsbx($url).'\') no-repeat center center;' : '';
	?><a class="<?=$className?>" href="<?=htmlspecialcharsbx($item['SHOW_URL'])?>" target="_blank">
		<span class="user-avatar" <?=$avatarStyle !== '' ? " style=\"$avatarStyle\"" : ''?>></span>
		<span class="sidebar-user-info">
			<span class="user-birth-name"><?=htmlspecialcharsEx($name)?></span>
			<span class="user-birth-date"><?=htmlspecialcharsEx($text)?></span>
		</span>
	</a><?
}
?></div><?
