<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(["voximplant.common"]);

use Bitrix\Voximplant as VI;

function getBalance($amount)
{
	$amount = round(floatval($amount), 2);
	$amount = $amount.'';
	$str = '';
	$amountCount = mb_strlen($amount);
	for ($i = 0; $i < $amountCount; $i++)
	{
		if ($amount[$i] == '.')
			$str .= '<span class="tel-num tel-num-point">.</span>';
		else if ($amount[$i] == '-')
			$str .= '<span class="tel-num tel-num-minus">-</span>';
		else
			$str .= '<span class="tel-num tel-num-'.$amount[$i].'">'.$amount[$i].'</span>';
	}

	return $str;
}
$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
?>

<div class="tel-title"></div>
<div class="tel-inner">
	<div class="tel-inner-left">
		<div class="tel-balance">
			<table class="tel-balance-table">
				<tr>
					<td class="tel-balance-left">
						<?if(in_array($arResult['LANG'], Array('ua', 'kz', 'by'))):?>
						<div class="tel-balance-title"><?=GetMessage('TELEPHONY_BALANCE_2')?></div>
						<div class="tel-balance-sum-wrap">

						</div>
						<?else:?>
						<div class="tel-balance-title"><?=GetMessage('TELEPHONY_BALANCE')?></div>
						<div class="tel-balance-sum-wrap">
							<span class="tel-balance-box">
								<span class="tel-balance-box-inner">
									<?=getBalance($arResult['AMOUNT']);?>
								</span>
								<span class="tel-balance-box-line"></span>
							</span>
							<span class="tel-balance-sum-currency sum-currency-<?= mb_strtoupper($arResult['CURRENCY']);?>"></span>
						</div>
						<?endif;?>
					</td>
					<td class="tel-balance-right">
						<div class="tel-balance-btn-wrap">
							<a href="?REFRESH" class="tel-balance-update-btn">
								<span class="tel-balance-update-btn-icon"></span>
								<span class="tel-balance-update-btn-text">
									<?=GetMessage('TELEPHONY_REFRESH')?>
								</span>
							</a>
						</div>
						<div class="tel-balance-btn-wrap">
							<?if(VI\Limits::isRestOnly()):?>

							<?elseif (in_array($arResult['LANG'], Array('ua', 'kz'))):?>
							<a href="<?=$arResult['LINK_TO_BUY']?>" target="_blank" class="tel-balance-update-btn tel-balance-update-btn2">
								<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_TARIFFS_2')?></span>
							</a>
							<?elseif($arResult['LINK_TO_BUY']):?>
							<a href="<?=GetMessage('TELEPHONY_TARIFFS_LINK')?>" target="_blank" class="tel-balance-update-btn tel-balance-update-btn2">
								<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_TARIFFS_2')?></span>
							</a>
							<?endif;?>
						</div>
						<?if ($arResult['SHOW_PAY_BUTTON']):?>
							<div class="tel-balance-btn-wrap">
								<?if ($arResult['LINK_TO_BUY']):?>
									<a href="<?=$arResult['LINK_TO_BUY']?>" class="tel-balance-blue-btn"><?=GetMessage('TELEPHONY_PAY')?></a>
								<?else:?>
									<span onclick="alert('<?=CUtil::JSEscape(GetMessage('TELEPHONY_PAY_DISABLE'))?>')" class="tel-balance-update-btn tel-balance-update-btn2">
										<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_PAY')?></span>
									</span>
								<?endif;?>
							</div>
						<?endif;?>
					</td>
				</tr>
			</table>
		</div>
		<?
		if($arResult['SHOW_LINES'])
		{
			$APPLICATION->IncludeComponent(
				"bitrix:voximplant.regular_payments", 
				"", 
				Array(
					'AMOUNT' => $arResult['AMOUNT'], 
					'CURRENCY' => $arResult['CURRENCY'], 
					'LANG' => $arResult['LANG']
				)
			);
			$APPLICATION->IncludeComponent("bitrix:voximplant.sip_payments", "", array());
		}
		?>
	</div>

<!-- statistic-->
<? if($arResult['SHOW_STATISTICS'] && !empty($arResult['STATISTICS'])): ?>
	<div class="tel-inner-right">
		<div class="tel-history-block">
			<div class="tel-history-title"><?=GetMessage(!in_array($arResult['LANG'], Array('ua', 'kz'))? 'TELEPHONY_HISTORY_2': 'TELEPHONY_HISTORY_3')?></div>
			<? $firstLine = true; ?>
			<? foreach ($arResult['STATISTICS'] as $statLine): ?>
				<div class="tel-history-block-info <?=$firstLine ? 'tel-history-block-info-current' : ''?>">
					<span>
						<strong><?=GetMessage('TELEPHONY_MONTH_'.sprintf('%\'02u', $statLine['MONTH']))?> <?=$statLine['YEAR']?></strong> &mdash; <?=htmlspecialcharsbx($statLine["DURATION_FORMATTED"])?>
					</span>
					<span class="tel-history-text-right"><?= $statLine['COST_FORMATTED']?></span>
				</div>
				<? $firstLine = false; ?>
			<? endforeach;?>

			<div class="tel-history-more">
				<a href="<?=CVoxImplantMain::GetPublicFolder()?>detail.php" class="tel-history-more-link"><?=GetMessage('TELEPHONY_DETAIL')?></a>
			</div>
		</div>
		<?if ($arResult['RECORD_LIMIT']['ENABLE'] && CModule::IncludeModule('bitrix24')):?>
		<div class="tel-history-block">
			<div class="tel-history-title"><?=GetMessage("VI_LOCK_RECORD_TITLE")?></div>
      		<?=GetMessage("VI_LOCK_RECORD_TEXT", Array("#LIMIT#" => '<b>'.$arResult['RECORD_LIMIT']['LIMIT'].'</b>', '#REMAINING#' => '<b>'.$arResult['RECORD_LIMIT']['REMAINING'].'</b>'))?>
			<div class="tel-history-more">
				<span class="tel-history-more-link" onclick="BX.UI.InfoHelper.show('limit_contact_center_telephony_records')"><?=GetMessage("VI_LOCK_RECORD_LINK")?></span>
			</div>
		</div>
		<?endif?>
	</div>
<? endif ?>
<?
if (!empty($arResult['ERROR_MESSAGE']))
{
	?><script>alert('<?=$arResult['ERROR_MESSAGE'];?>');</script><?
}
?>
</div>