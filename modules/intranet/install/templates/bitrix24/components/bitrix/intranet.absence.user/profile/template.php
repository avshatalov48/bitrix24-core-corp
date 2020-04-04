<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

if (!(is_array($arResult['ENTRIES']) && count($arResult['ENTRIES']) > 0))
	return;
?>
<div class="intranet-user-profile-absence">
	<div class="intranet-user-profile-absence-title"><?=Loc::getMessage("SONET_USER_ABSENCE")?>:</div>
	<div class="intranet-user-profile-absence-value">
		<?
		foreach ($arResult['ENTRIES'] as $key => $arEntry)
		{
			if ($key >= 5)
				break;

			$ts_start = MakeTimeStamp($arEntry['DATE_ACTIVE_FROM']);
			$ts_finish = MakeTimeStamp($arEntry['DATE_ACTIVE_TO']);
			$ts_now = time();
			$bNow = $ts_now >= $ts_start && $ts_now <= $ts_finish;
			?>
			<div class="intranet-user-profile-absence-value-item">
				<?=Loc::getMessage('INTR_IAU_TPL'.($bNow ? '_TO' : '_FROM')) ?> <? echo FormatDate($DB->DateFormatToPHP(FORMAT_DATE), MakeTimeStamp($arEntry['DATE_ACTIVE'.($bNow ? '_TO' : '_FROM')])) ?>
				(<?=htmlspecialcharsbx($arEntry['TITLE'])?>)
			</div>
			<?
			$bFirst = false;
		}
		?>
	</div>
</div>
