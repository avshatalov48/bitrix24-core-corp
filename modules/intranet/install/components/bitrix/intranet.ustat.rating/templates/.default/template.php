<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX']))
{
	$APPLICATION->RestartBuffer();
}

$ratingPosition = (int) $arParams['OFFSET'];
$topActivity = $arParams['OFFSET'] ? (int) $arParams['TOP_ACTIVITY'] : $arResult['DATA'][0]['ACTIVITY'];

?>

<? foreach ($arResult['DATA'] as $data): ?>

	<div class="pulse-popup-user-block <?=$USER->getId() == $data['USER_ID']?'pulse-popup-i-am-user':''?>">
		<span class="pulse-popup-user-avatar" data-userid="<?=$data['USER_ID']?>"
			<? if(!empty($arResult['USERS_INFO'][$data['USER_ID']]['AVATAR_SRC'])): ?>
				style="background: url('<?=$arResult['USERS_INFO'][$data['USER_ID']]['AVATAR_SRC']?>') no-repeat center center;"
			<?endif?>
			>
			<span class="pulse-popup-user-avatar-flag <?=$data['IS_INVOLVED']?'pulse-user-online':''?>"></span>
		</span>
		<div class="pulse-popup-user-info">
			<div class="pulse-popup-user-name">
				<span class="pulse-popup-user-num"><?=++$ratingPosition?>.</span>
				<span class="pulse-popup-user-name-text" data-userid="<?=$data['USER_ID']?>"><?=htmlspecialcharsbx($arResult['USERS_INFO'][$data['USER_ID']]['FULL_NAME'])?></span>
			</div>
			<div class="pulse-popup-bar-wrap">
			<span class="pulse-popup-bar">
				<span class="pulse-popup-bar-inner" style="width: <?=$topActivity ? round($data['ACTIVITY']/$topActivity*100) : 0 ?>%;"></span>
				<span class="pulse-popup-bar-caption"><?if(!$arParams['SECTION']):?><?=$data['SERVICES_COUNT'].' '.getNumberEnding($data['SERVICES_COUNT'], array(
						GetMessage('INTRANET_USTAT_RATING_SERVICE_COUNT_1'),
						GetMessage('INTRANET_USTAT_RATING_SERVICE_COUNT_2'),
						GetMessage('INTRANET_USTAT_RATING_SERVICE_COUNT_5')
					))?><?endif?></span>
			</span>
				<?
					$activity = '';
					$formattedAcitivty = \Bitrix\Intranet\UStat\UStat::getFormattedNumber($data['ACTIVITY']);

					foreach ($formattedAcitivty as $numPart)
					{
						$activity .= $numPart['char'];
					}
				?>
				<span class="pulse-popup-bar-size"><?=$activity?></span>
			</div>
		</div>
	</div>

<? endforeach ?>

<? if (!$arParams['OFFSET']): ?>
	<div id="pulse-popup-max-score" style="display: none"><?=$topActivity?></div>
<? endif ?>

<script type="text/javascript">
	var ustatUsers = BX.findChildren(BX('intranet-activity-container'), {className:'pulse-popup-user-name-text'}, true);
	for (i in ustatUsers)
	{
		BX.bind(ustatUsers[i], 'click', function(){
			reloadIntranetUstat({BY: 'user', BY_ID: this.getAttribute('data-userid')});
			pulse_popup.close();
		});
	}

	var ustatUsers = BX.findChildren(BX('intranet-activity-container'), {className:'pulse-popup-user-avatar'}, true);
	for (i in ustatUsers)
	{
		BX.bind(ustatUsers[i], 'click', function(){
			reloadIntranetUstat({BY: 'user', BY_ID: this.getAttribute('data-userid')});
			pulse_popup.close();
		});
	}

</script>

<? die ?>