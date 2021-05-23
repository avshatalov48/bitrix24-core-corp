<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
?><div class="bx-birthday-layout-include crm-right-block"><?

$items = $arResult['ITEMS'];
$nameFormat = $arResult['NAME_FORMAT'];
$dateFormat = $arResult['DATE_FORMAT'];

$count = count($items);
for($i = 0; $i < $count; $i++):
	$item = $items[$i];
	$isBirthday = (isset($item['IS_BIRTHDAY']) && $item['IS_BIRTHDAY']);

	$className = 'sidebar-widget-item';
	if($isBirthday)
		$className .= ' today-birth';

	if($i === ($count - 1))
		$className .= ' widget-last-item';

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

	$imageID = $item['IMAGE_ID'];
	if($imageID <= 0):
		$imageUrl = '';
	else:
		$file = new CFile();
		$imageFile = $file->ResizeImageGet(
			$imageID,
			array('width' => 42, 'height' => 42),
			BX_RESIZE_IMAGE_EXACT, true
		);
		$imageUrl = is_array($imageFile) ? $imageFile['src'] : '';
	endif;
	$avatarClassName = "bx-user-image";
	if($imageUrl === '')
		$avatarClassName .= " bx-user-image-default";
	?><div class="bx-user-info">
		<div class="bx-user-info-inner">
			<div class="<?=$avatarClassName?>"><?
				if($imageUrl !== ''):
				?><a href="<?=htmlspecialcharsbx($item['SHOW_URL'])?>" target="_blank">
					<img width="42" height="42" alt="" src="<?=htmlspecialcharsbx($imageUrl)?>" />
				</a><?
				endif;
			?></div>
			<div class="bx-user-birthday"><?=htmlspecialcharsEx($text)?></div>
			<div class="bx-user-name">
				<a href="<?=htmlspecialcharsbx($item['SHOW_URL'])?>" target="_blank">
					<?=htmlspecialcharsEx($name)?>
				</a>
			</div>
			<div style="clear: both;"></div>
		</div>
	</div><?
endfor;
?></div><?
