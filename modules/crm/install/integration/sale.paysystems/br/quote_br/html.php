<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?

use Bitrix\Main\Localization\Loc;

$lng = 'br';
Loc::loadLanguageFile(__FILE__, $lng);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title><?= Loc::getMessage('SBLP_Q_BR_TEXT_QUOTE', null, $lng) ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET?>">
<style>
	table { border-collapse: collapse; }
	table.acc td { border: 1pt solid #000000; padding: 0pt 3pt; line-height: 21pt; }
	table.it td { border: 1pt solid #000000; padding: 0pt 3pt; }
	table.sign td { font-weight: bold; vertical-align: bottom; }
	table.header td { padding: 0pt; vertical-align: top; }
</style>
</head>

<?

if ($_REQUEST['BLANK'] == 'Y')
	$blank = true;

$pageWidth  = 595.28;
$pageHeight = 841.89;

$background = '#ffffff';
if (CSalePaySystemAction::GetParamValue('BACKGROUND', false))
{
	$path = CSalePaySystemAction::GetParamValue('BACKGROUND', false);
	if (intval($path) > 0)
	{
		if ($arFile = CFile::GetFileArray($path))
			$path = $arFile['SRC'];
	}

	$backgroundStyle = CSalePaySystemAction::GetParamValue('BACKGROUND_STYLE', false);
	if (!in_array($backgroundStyle, array('none', 'tile', 'stretch')))
		$backgroundStyle = 'none';

	if ($path)
	{
		switch ($backgroundStyle)
		{
			case 'none':
				$background = "url('" . $path . "') 0 0 no-repeat";
				break;
			case 'tile':
				$background = "url('" . $path . "') 0 0 repeat";
				break;
			case 'stretch':
				$background = sprintf(
					"url('%s') 0 0 repeat-y; background-size: %.02fpt %.02fpt",
					$path, $pageWidth, $pageHeight
				);
				break;
		}
	}
}

$margin = array(
	'top' => intval(CSalePaySystemAction::GetParamValue('MARGIN_TOP', false) ?: 15) * 72/25.4,
	'right' => intval(CSalePaySystemAction::GetParamValue('MARGIN_RIGHT', false) ?: 15) * 72/25.4,
	'bottom' => intval(CSalePaySystemAction::GetParamValue('MARGIN_BOTTOM', false) ?: 15) * 72/25.4,
	'left' => intval(CSalePaySystemAction::GetParamValue('MARGIN_LEFT', false) ?: 20) * 72/25.4
);
$width = $pageWidth - $margin['left'] - $margin['right'];
?>
<body style="margin: 0pt; padding: 0pt; background: <?=$background; ?>"<? if ($_REQUEST['PRINT'] == 'Y') { ?> onload="setTimeout(window.print, 0);"<? } ?>>
<div style="margin: 0pt; padding: <?=join('pt ', $margin); ?>pt; width: <?=$width; ?>pt; background: <?=$background; ?>">
<?if (CSalePaySystemAction::GetParamValue('QUOTE_BR_HEADER_SHOW') == 'Y'):?>
<table class="header">
	<tr><?
		$pathToLogo = CSalePaySystemAction::GetParamValue("PATH_TO_LOGO", false);
		if ($pathToLogo)
		{
			$imgParams = CFile::_GetImgParams($pathToLogo);
			$imgWidth = $imgParams['WIDTH'] * 96 / (intval(CSalePaySystemAction::GetParamValue('LOGO_DPI', false)) ?: 96);
			?><td style="padding-right: 5pt; "><img src="<?=$imgParams['SRC']; ?>" width="<?=$imgWidth; ?>" /></td><?
		}
		?>
		<td><?
			$sellerName = CSalePaySystemAction::GetParamValue("SELLER_NAME", false);
			if (!$sellerName)
				$sellerName = '';
			?>
			<b><?= $sellerName ?></b><br><?
			$sellerAddress = CSalePaySystemAction::GetParamValue("SELLER_ADDRESS", false);
			if ($sellerAddress)
			{
				if (is_array($sellerAddress))
				{
					$addrValue = implode('<br>', $sellerAddress)
					?><div style="display: inline-block; vertical-align: top;"><?= $addrValue ?></div><?
					unset($addrValue);
				}
				else
				{
					?><?= nl2br($sellerAddress) ?><?
				}
				unset($sellerAddress);
				?><br><?
			}
			?>
			<? if (CSalePaySystemAction::GetParamValue("SELLER_PHONE", false)) { ?>
				<?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_PHONE', null, $lng).": %s", CSalePaySystemAction::GetParamValue("SELLER_PHONE", false)); ?><br>
			<? } ?>
			<? if (CSalePaySystemAction::GetParamValue("SELLER_EMAIL", false)) { ?>
				<?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_EMAIL', null, $lng).": %s", CSalePaySystemAction::GetParamValue("SELLER_EMAIL", false)); ?>
			<? } ?>
		</td>
	</tr>
</table>
<br>
<br>
<div style="margin: 0pt; padding: 0pt;">
	<? if (CSalePaySystemAction::GetParamValue("BUYER_NAME", false)) { ?>
		<b><?=CSalePaySystemAction::GetParamValue("BUYER_NAME", false); ?></b>
		<br><?
		if (CSalePaySystemAction::GetParamValue("BUYER_ADDRESS", false))
		{
			$buyerAddress = CSalePaySystemAction::GetParamValue("BUYER_ADDRESS", false);
			if (is_array($buyerAddress))
			{
				$addrValue = implode('<br>', $buyerAddress)
				?><div style="display: inline-block; vertical-align: top;"><?= $addrValue ?></div><?
				unset($addrValue);
			}
			else
			{
				?><?= nl2br($buyerAddress) ?><?
			}
			unset($buyerAddress);
			?><br><?
		} ?>
		<?
		$buyerPayerName = CSalePaySystemAction::GetParamValue("BUYER_PAYER_NAME", false);
		if ($buyerPayerName)
		{
			?><?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_CONTACT_PERSON', null, $lng).": %s", $buyerPayerName); ?><br><?
		}
		unset($buyerPayerName);
		$buyerPhone = CSalePaySystemAction::GetParamValue("BUYER_PHONE", false);
		if ($buyerPhone)
		{
			?><?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_PHONE', null, $lng).": %s", $buyerPhone); ?><br><?
		}
		unset($buyerPhone);
		$buyerFax = CSalePaySystemAction::GetParamValue("BUYER_FAX", false);
		if ($buyerFax)
		{
			?><?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_FAX', null, $lng).": %s", $buyerFax); ?><br><?
		}
		unset($buyerFax);
		$buyerEmail = CSalePaySystemAction::GetParamValue("BUYER_EMAIL", false);
		if ($buyerEmail)
		{
			?><?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_EMAIL', null, $lng).": %s", $buyerEmail); ?><br><?
		}
		unset($buyerEmail);
		?>
	<? } ?>
</div>
<br>
<br>
<table width="100%" style="font-weight: bold">
	<tr>
		<td>
			<span style="font-size: 1.5em; font-weight: bold; text-align: center;">
				<?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_QUOTE', null, $lng).' '.Loc::getMessage('SBLP_Q_BR_TEXT_NUMBER', null, $lng).' %s',
					htmlspecialcharsbx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ACCOUNT_NUMBER"])); ?>
			</span>
		</td>
		<td align="right">
			<?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_ISSUE_DATE', null, $lng).': %s',
					CSalePaySystemAction::GetParamValue("DATE_INSERT", false)); ?>
			</td>
	</tr>
</table>
<br>

<?if (CSalePaySystemAction::GetParamValue("COMMENT1", false)
	|| CSalePaySystemAction::GetParamValue("COMMENT2", false)) { ?>
	<? if (CSalePaySystemAction::GetParamValue("COMMENT1", false)) { ?>
	<?=nl2br(HTMLToTxt(preg_replace(
		array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
		htmlspecialcharsback(CSalePaySystemAction::GetParamValue("COMMENT1", false))
	), '', array(), 0)); ?>
	<br>
	<br>
	<? } ?>
	<? if (CSalePaySystemAction::GetParamValue("COMMENT2", false)) { ?>
	<?=nl2br(HTMLToTxt(preg_replace(
		array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
		htmlspecialcharsback(CSalePaySystemAction::GetParamValue("COMMENT2", false))
	), '', array(), 0)); ?>
	<br>
	<br>
	<? } ?>
<? } ?>
<?endif;
$vat = 0;
$arCols = array();
$bShowDiscount = false;
$arBasketItems = CSalePaySystemAction::GetParamValue("BASKET_ITEMS", false);
if(!is_array($arBasketItems))
	$arBasketItems = array();

if (!empty($arBasketItems))
{
	$arBasketItems = getMeasures($arBasketItems);

	$arCells = array();
	$arProps = array();

	$columnList = array('NUMBER', 'NAME', 'QUANTITY', 'MEASURE', 'PRICE', 'VAT_RATE', 'DISCOUNT', 'SUM');
	$vatRateColumn = 0;
	foreach ($columnList as $column)
	{
		if (CSalePaySystemAction::GetParamValue('QUOTE_BR_COLUMN_'.$column.'_SHOW') == 'Y')
		{
			$arCols[$column] = array(
				'NAME' => CSalePaySystemAction::GetParamValue('QUOTE_BR_COLUMN_'.$column.'_TITLE'),
				'SORT' => CSalePaySystemAction::GetParamValue('QUOTE_BR_COLUMN_'.$column.'_SORT')
			);
		}
	}

	if (CSalePaySystemAction::GetParamValue('USER_COLUMNS'))
	{
		$userColumns = CSalePaySystemAction::GetParamValue('USER_COLUMNS');
		$columnList = array_merge($columnList, array_keys($userColumns));
		foreach ($userColumns as $id => $val)
		{
			$arCols[$id] = array(
				'NAME' => $val['NAME'],
				'SORT' => $val['SORT']
			);
		}
	}

	uasort($arCols, function ($a, $b) {return ($a['SORT'] < $b['SORT']) ? -1 : 1;});

	$arColumnKeys = array_keys($arCols);
	$columnCount = count($arColumnKeys);

	$n = 0;
	$sum = 0.00;
	$vats = array();

	if(is_array($arBasketItems))
	{
		foreach($arBasketItems as &$arBasket)
		{
			$n++;

			// @TODO: replace with real vatless price
			if (isset($arBasket['VAT_INCLUDED']) && $arBasket['VAT_INCLUDED'] === 'Y')
				$arBasket["VATLESS_PRICE"] = roundEx($arBasket["PRICE"] / (1 + $arBasket["VAT_RATE"]), SALE_VALUE_PRECISION);
			else
				$arBasket["VATLESS_PRICE"] = $arBasket["PRICE"];

			$productName = $arBasket["NAME"];
			if ($productName == "OrderDelivery")
				$productName = Loc::getMessage('SBLP_Q_BR_TEXT_SHIPPING', null, $lng);
			else if ($productName == "OrderDiscount")
				$productName = Loc::getMessage('SBLP_Q_BR_TEXT_DISCOUNT', null, $lng);

			// discount
			$discountValue = '0%';
			$discountSum = 0.0;
			$discountIsSet = false;
			if (is_array($arBasket['CRM_PR_FIELDS']))
			{
				if (isset($arBasket['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'])
					&& isset($arBasket['CRM_PR_FIELDS']['DISCOUNT_RATE'])
					&& isset($arBasket['CRM_PR_FIELDS']['DISCOUNT_SUM']))
				{
					if ($arBasket['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::PERCENTAGE)
					{
						$discountValue = round(doubleval($arBasket['CRM_PR_FIELDS']['DISCOUNT_RATE']), 2).'%';
						$discountSum = round(doubleval($arBasket['CRM_PR_FIELDS']['DISCOUNT_SUM']), 2);
						$discountIsSet = true;
					}
					else if ($arBasket['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
					{
						$discountSum = round(doubleval($arBasket['CRM_PR_FIELDS']['DISCOUNT_SUM']), 2);
						$discountValue = SaleFormatCurrency($discountSum, $arBasket["CURRENCY"], false);
						$discountIsSet = true;
					}
				}
			}
			if ($discountIsSet && $discountSum > 0)
				$bShowDiscount = true;
			unset($discountIsSet);

			if ($bShowDiscount
				&& isset($arBasket['CRM_PR_FIELDS']['TAX_INCLUDED'])
				&& isset($arBasket['CRM_PR_FIELDS']['PRICE_NETTO'])
				&& isset($arBasket['CRM_PR_FIELDS']['PRICE_BRUTTO']))
			{
				if ($arBasket['CRM_PR_FIELDS']['TAX_INCLUDED'] === 'Y')
					$unitPrice = $arBasket['CRM_PR_FIELDS']["PRICE_BRUTTO"];
				else
					$unitPrice = $arBasket['CRM_PR_FIELDS']["PRICE_NETTO"];
			}
			else
			{
				$unitPrice = $arBasket["VATLESS_PRICE"];
			}
			foreach ($arCols as $columnId => $caption)
			{
				$data = null;

				switch ($columnId)
				{
					case 'NUMBER':
						$data = $n;
						break;
					case 'NAME':
						$data = htmlspecialcharsbx($productName);
						break;
					case 'QUANTITY':
						$data = roundEx($arBasket['QUANTITY'], SALE_VALUE_PRECISION);
						break;
					case 'MEASURE':
						$data = $arBasket["MEASURE_NAME"] ? htmlspecialcharsbx($arBasket["MEASURE_NAME"]) : Loc::getMessage('SBLP_Q_BR_TEXT_PCS', null, $lng);
						break;
					case 'PRICE':
						$data = SaleFormatCurrency($unitPrice, $arBasket['CURRENCY'], false);
						break;
					case 'DISCOUNT':
						$data = $discountValue;
						break;
					case 'VAT_RATE':
						$data = roundEx($arBasket['VAT_RATE'] * 100, SALE_VALUE_PRECISION)."%";
						break;
					case 'SUM':
						$data = SaleFormatCurrency($arBasket["VATLESS_PRICE"] * $arBasket["QUANTITY"], $arBasket["CURRENCY"], false);
						break;
					default :
						$data = ($arBasket[$columnId]) ?: '';
				}
				if ($data !== null)
					$arCells[$n][$columnId] = $data;
			}

			if(isset($arBasket["PROPS"]) && is_array($arBasket["PROPS"]))
			{
				$arProps[$n] = array();
				foreach ($arBasket["PROPS"] as $vv)
					$arProps[$n][] = htmlspecialcharsbx(sprintf("%s: %s", $vv["NAME"], $vv["VALUE"]));
			}

			$sum += doubleval($arBasket["VATLESS_PRICE"] * $arBasket["QUANTITY"]);
			$vat = max($vat, $arBasket["VAT_RATE"]);
			$vatKey = strval($arBasket["VAT_RATE"]);
			if ($arBasket["VAT_RATE"] > 0)
			{
				if (!isset($vats[$vatKey]))
					$vats[$vatKey] = 0;
				if ($arBasket['VAT_INCLUDED'] === 'Y')
					$vats[$vatKey] += ($arBasket["PRICE"] - $arBasket["VATLESS_PRICE"]) * $arBasket["QUANTITY"];
				else
					$vats[$vatKey] += ($arBasket["PRICE"] * (1 + $arBasket["VAT_RATE"]) - $arBasket["VATLESS_PRICE"]) * $arBasket["QUANTITY"];
			}
		}
		unset($arBasket);

		if ($vat <= 0)
		{
			unset($arCols['VAT_RATE']);
			$columnCount = count($arCols);
			$arColumnKeys = array_keys($arCols);
			foreach ($arCells as $i => $cell)
				unset($arCells[$i]['VAT_RATE']);
		}

		if (!$bShowDiscount)
		{
			unset($arCols['DISCOUNT']);
			$columnCount = count($arCols);
			$arColumnKeys = array_keys($arCols);
			foreach ($arCells as $i => $cell)
				unset($arCells[$i]['DISCOUNT']);
		}
	}

	$items = $n;

	if (CSalePaySystemAction::GetParamValue('QUOTE_BR_TOTAL_SHOW') == 'Y')
	{
		$eps = 0.0001;
		if ($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"] - $sum > $eps)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = Loc::getMessage('SBLP_Q_BR_TEXT_SUBTOTAL', null, $lng).":";
			$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($sum, $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false);
		}

		if (!empty($vats))
		{
			// @TODO: remove on real vatless price implemented
			$delta = intval(roundEx(
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"] - $sum - array_sum($vats),
				SALE_VALUE_PRECISION
			) * pow(10, SALE_VALUE_PRECISION));
			if ($delta)
			{
				$vatRates = array_keys($vats);
				rsort($vatRates);

				$ful = intval($delta / count($vatRates));
				$ost = $delta % count($vatRates);

				foreach ($vatRates as $vatRate)
				{
					$vats[$vatRate] += ($ful + $ost) / pow(10, SALE_VALUE_PRECISION);

					if ($ost > 0)
						$ost--;
				}
			}

			foreach ($vats as $vatRate => $vatSum)
			{
				$arCells[++$n] = array();
				for ($i = 0; $i < $columnCount; $i++)
					$arCells[$n][$arColumnKeys[$i]] = null;

				$arCells[$n][$arColumnKeys[$columnCount-2]] = sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_TAX', null, $lng)." (%s%%):", roundEx($vatRate * 100, SALE_VALUE_PRECISION));
				$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($vatSum, $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false);
			}
		}
		else
		{
			$arTaxList = CSalePaySystemAction::GetParamValue("TAX_LIST", false);
			if(!is_array($arTaxList))
				$arTaxList = array();
			if(!empty($arTaxList))
			{
				foreach($arTaxList as &$arTaxInfo)
				{
					$arCells[++$n] = array();
					for ($i = 0; $i < $columnCount; $i++)
						$arCells[$n][$arColumnKeys[$i]] = null;

					$arCells[$n][$arColumnKeys[$columnCount-2]] = htmlspecialcharsbx(sprintf(
						"%s%s%s:",
						($arTaxInfo["IS_IN_PRICE"] == "Y") ? Loc::getMessage('SBLP_Q_BR_TEXT_INCLUDED', null, $lng)." " : "",
						$arTaxInfo["TAX_NAME"],
						sprintf(' (%s%%)', roundEx($arTaxInfo["VALUE"],SALE_VALUE_PRECISION))
					));
					$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($arTaxInfo["VALUE_MONEY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false);
				}
				unset($arTaxInfo);
			}
		}
		if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"]) > 0)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = Loc::getMessage('SBLP_Q_BR_TEXT_DISCOUNT', null, $lng).":";
			$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false);
		}

		$arCells[++$n] = array();
		for ($i = 0; $i < $columnCount; $i++)
			$arCells[$n][$arColumnKeys[$i]] = null;

		$arCells[$n][$arColumnKeys[$columnCount-2]] = Loc::getMessage('SBLP_Q_BR_TEXT_TOTAL', null, $lng).":";
		$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false);
	}
}

?>
<table class="it" width="100%">
	<tr>
		<?foreach ($arCols as $columnId => $col):?>
			<td><?=$col['NAME'];?></td>
		<?endforeach;?>
	</tr>
<?

$rowsCnt = count($arCells);
for ($n = 1; $n <= $rowsCnt; $n++)
{
	$accumulated = 0;

?>
	<tr valign="top">
		<?foreach ($arCols as $columnId => $col):?>
		<?
			if (!is_null($arCells[$n][$columnId]))
			{
				if ($columnId === 'NUMBER')
				{?>
					<td align="center"><?=$arCells[$n][$columnId];?></td>
				<?}
				elseif ($columnId === 'NAME')
				{
				?>
					<td align="<?=($n > $items) ? 'right' : 'left';?>"
						style="word-break: break-word; word-wrap: break-word; <? if ($accumulated) {?>border-width: 0pt 1pt 0pt 0pt; <? } ?>"
						<? if ($accumulated) { ?>colspan="<?=($accumulated+1); ?>"<? $accumulated = 0; } ?>>
						<?=$arCells[$n][$columnId]; ?>
						<? if (isset($arProps[$n]) && is_array($arProps[$n])) { ?>
						<? foreach ($arProps[$n] as $property) { ?>
						<br>
						<small><?=$property; ?></small>
						<? } ?>
						<? } ?>
					</td>
				<?}
				else
				{
					if (!is_null($arCells[$n][$columnId]))
					{
						if ($columnId != 'VAT_RATE' ||$columnId != 'DISCOUNT' || $vat > 0 || is_null($arCells[$n][$columnId]) || $n > $items)
						{ ?>
							<td align="right"
								<? if ($accumulated) { ?>
								style="border-width: 0pt 1pt 0pt 0pt"
								colspan="<?=(($columnId == 'VAT_RATE' && $vat <= 0) ? $accumulated : $accumulated+1); ?>"
								<? $accumulated = 0; } ?>>
									<?if ($columnId == 'SUM' || $columnId == 'PRICE'):?>
										<nobr><?=$arCells[$n][$columnId];?></nobr>
									<?else:?>
										<?=$arCells[$n][$columnId]; ?>
									<?endif;?>
							</td>
						<? }
					}
					else
					{
						$accumulated++;
					}
				}
			}
			else
			{
				$accumulated++;
			}
		?>
	<?endforeach;?>
	</tr>
<?

}

?>
</table>
<br>
<? if (CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false)) { ?>
<div><?=sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_DUE_DATE', null, $lng).': %s', ConvertDateTime(CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false), FORMAT_DATE))?></div>
<br>
<? } ?>

<?$userFields = array();
for($i = 1; $i <= 5; $i++)
{
	$fildValue = CSalePaySystemAction::GetParamValue("USER_FIELD_{$i}", false);
	if($fildValue)
	{
		$userFields[] = $fildValue;
	}
}?>
<?if (!empty($userFields)) { ?>
	<?foreach($userFields as &$userField){?>
		<?=nl2br(HTMLToTxt(preg_replace(
				array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
				htmlspecialcharsback($userField)
			), '', array(), 0));?>
		<br>
		<br>
	<?}
	unset($userField);?>
<?}?>

<?if (CSalePaySystemAction::GetParamValue('QUOTE_BR_SIGN_SHOW') == 'Y'):?>
	<? if (!$blank) { ?>
	<div style="position: relative; "><?=CFile::ShowImage(
		CSalePaySystemAction::GetParamValue("PATH_TO_STAMP", false),
		160, 160,
		'style="position: absolute; left: 40pt; "'
	); ?></div>
	<? } ?>

	<div style="position: relative">
		<table class="sign">
			<? if (CSalePaySystemAction::GetParamValue("SELLER_DIR_POS", false)) { ?>
			<tr>
				<td style="width: 150pt; "><?=CSalePaySystemAction::GetParamValue("SELLER_DIR_POS", false); ?></td>
				<td style="width: 160pt; border: 1pt solid #000000; border-width: 0pt 0pt 1pt 0pt; text-align: center; ">
					<? if (!$blank) { ?>
					<?=CFile::ShowImage(CSalePaySystemAction::GetParamValue("SELLER_DIR_SIGN", false), 200, 50); ?>
					<? } ?>
				</td>
				<td>
					<? if (CSalePaySystemAction::GetParamValue("SELLER_DIR", false)) { ?>
					(<?=CSalePaySystemAction::GetParamValue("SELLER_DIR", false); ?>)
					<? } ?>
				</td>
			</tr>
			<tr><td colspan="3">&nbsp;</td></tr>
			<? } ?>
			<? if (CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false)) { ?>
			<tr>
				<td style="width: 150pt; "><?=CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false); ?></td>
				<td style="width: 160pt; border: 1pt solid #000000; border-width: 0pt 0pt 1pt 0pt; text-align: center; ">
					<? if (!$blank) { ?>
					<?=CFile::ShowImage(CSalePaySystemAction::GetParamValue("SELLER_ACC_SIGN", false), 200, 50); ?>
					<? } ?>
				</td>
				<td>
					<? if (CSalePaySystemAction::GetParamValue("SELLER_ACC", false)) { ?>
					(<?=CSalePaySystemAction::GetParamValue("SELLER_ACC", false); ?>)
					<? } ?>
				</td>
			</tr>
			<? } ?>
		</table>
	</div>

	<br>
	<br>
	<br>
<?endif;?>

</div>

</body>
</html>