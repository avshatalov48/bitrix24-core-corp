<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Коммерческое предложение</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET?>">
<style>
	table { border-collapse: collapse; }
	table.acc td { border: 1pt solid #000000; padding: 0 3pt; line-height: 21pt; }
	table.it td { border: 1pt solid #000000; padding: 0 3pt; }
	table.sign td { font-weight: bold; vertical-align: bottom; }
	table.rq td { vertical-align: top; }
	table.rq td div { line-height: 1.5em; min-height: 1.5em; }
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
<?if (CSalePaySystemAction::GetParamValue('QUOTE_HEADER_SHOW') == 'Y'):?>
<table width="100%" style="padding: 0pt; vertical-align: top;">
	<tr>
		<td style="padding-right: 5pt; padding-bottom: 5pt;">
			<?
			$pathToLogo = CSalePaySystemAction::GetParamValue("PATH_TO_LOGO", false);
			if ($pathToLogo)
			{
				$imgParams = CFile::_GetImgParams(CSalePaySystemAction::GetParamValue('PATH_TO_LOGO', false));
				$imgWidth = $imgParams['WIDTH'] * 96 / (intval(CSalePaySystemAction::GetParamValue('LOGO_DPI', false)) ?: 96);
				?><img src="<?=$imgParams['SRC']; ?>" width="<?=$imgWidth; ?>" /><?
			}
			unset($pathToLogo);
			?>
		</td>
		<td></td>
		<td align="right" style="vertical-align: top;">
			<b><?=htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SELLER_NAME", false), ENT_COMPAT, false); ?></b>
			<?
			$sellerAddr = CSalePaySystemAction::GetParamValue("SELLER_ADDRESS", false);
			if ($sellerAddr)
			{
				if (is_array($sellerAddr))
					$sellerAddr = implode(', ', $sellerAddr);
				else
					$sellerAddr = str_replace(array("\r\n", "\n", "\r"), ', ', strval($sellerAddr));
				?><br><b><?= htmlspecialcharsbx($sellerAddr, ENT_COMPAT, false) ?></b><?
			}
			unset($sellerAddr);
			$sellerPhone = CSalePaySystemAction::GetParamValue("SELLER_PHONE", false);
			if ($sellerPhone)
			{
				?><br><b><?=sprintf("Тел.: %s", htmlspecialcharsbx($sellerPhone)); ?></b><?
			}
			unset($sellerPhone);
			?>
		</td>
	</tr>
</table>
<br>
<table width="100%">
	<colgroup>
		<col width="50%">
		<col width="0">
		<col width="50%">
	</colgroup>
	<tr>
		<td></td>
		<td style="font-size: 1.5em; font-weight: bold; text-align: center;"><nobr><?=sprintf(
			"КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ № %s от %s",
			htmlspecialcharsbx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ACCOUNT_NUMBER"], ENT_COMPAT, false),
			CSalePaySystemAction::GetParamValue("DATE_INSERT", false)
		); ?></nobr></td>
		<td></td>
	</tr>
<? if (CSalePaySystemAction::GetParamValue("ORDER_SUBJECT", false)) { ?>
	<tr>
		<td></td>
		<td><?=htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("ORDER_SUBJECT", false), ENT_COMPAT, false); ?></td>
		<td></td>
	</tr>
<? } ?>
<? if (CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false)) { ?>
	<tr>
		<td></td>
		<td><?=sprintf(
			"Срок действия %s",
			ConvertDateTime(CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false), FORMAT_DATE)
				?: CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false)
		); ?></td>
		<td></td>
	</tr>
<? } ?>
</table>
<?endif;?>
<br>
<?$userFields = array();
for($i = 1; $i <= 5; $i++)
{
	$fildValue = CSalePaySystemAction::GetParamValue("USER_FIELD_{$i}", false);
	if($fildValue)
	{
		$userFields[] = $fildValue;
	}
}?>
<?if (CSalePaySystemAction::GetParamValue("COMMENT1", false)
	|| CSalePaySystemAction::GetParamValue("COMMENT2", false)
	|| !empty($userFields))
{ ?>
<b>Условия и комментарии</b>
<br>
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
	<?foreach($userFields as &$userField){?>
		<?=nl2br(HTMLToTxt(preg_replace(
				array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
				htmlspecialcharsback($userField)
			), '', array(), 0));?>
		<br>
		<br>
	<?}
	unset($userField);?>
<?
}
$arBasketItems = CSalePaySystemAction::GetParamValue("BASKET_ITEMS", false);
if(!is_array($arBasketItems))
	$arBasketItems = array();

$arCurFormat = CCurrencyLang::GetCurrencyFormat($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"]);
$currency = trim(preg_replace('/(^|[^&])#/', '${1}', $arCurFormat['FORMAT_STRING']));

$vat = 0;
$arCols = array();
$arCells = array();
$arProps = array();
if (!empty($arBasketItems))
{
	$arBasketItems = getMeasures($arBasketItems);

	$columnList = array('NUMBER', 'NAME', 'QUANTITY', 'MEASURE', 'PRICE', 'VAT_RATE', 'DISCOUNT', 'SUM');
	$vatRateColumn = 0;
	foreach ($columnList as $column)
	{
		if (CSalePaySystemAction::GetParamValue('QUOTE_COLUMN_'.$column.'_SHOW') == 'Y')
		{
			$caption = CSalePaySystemAction::GetParamValue('QUOTE_COLUMN_'.$column.'_TITLE');
			if (in_array($column, array('PRICE', 'SUM')))
				$caption .= ', '.$currency;

			$arCols[$column] = array(
				'NAME' => htmlspecialcharsbx($caption, ENT_COMPAT, false),
				'SORT' => CSalePaySystemAction::GetParamValue('QUOTE_COLUMN_'.$column.'_SORT')
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
				'NAME' => htmlspecialcharsbx($val['NAME'], ENT_COMPAT, false),
				'SORT' => $val['SORT']
			);
		}
	}

	uasort($arCols, function ($a, $b) {return ($a['SORT'] < $b['SORT']) ? -1 : 1;});

	$arColumnKeys = array_keys($arCols);
	$columnCount = count($arColumnKeys);

	$n = 0;
	$sum = 0.00;
	$bShowDiscount = false;
	foreach($arBasketItems as &$arBasket)
	{
		$productName = $arBasket["NAME"];
		if ($productName == "OrderDelivery")
			$productName = "Доставка";
		else if ($productName == "OrderDiscount")
			$productName = "Скидка";

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
			$unitPrice = $arBasket["PRICE"];
		}
		foreach ($arCols as $columnId => $caption)
		{
			$data = null;

			switch ($columnId)
			{
				case 'NUMBER':
					$data = ++$n;
					break;
				case 'NAME':
					$data = htmlspecialcharsbx($productName);
					break;
				case 'QUANTITY':
					$data = roundEx($arBasket['QUANTITY'], SALE_VALUE_PRECISION);
					break;
				case 'MEASURE':
					$data = $arBasket["MEASURE_NAME"] ? htmlspecialcharsbx($arBasket["MEASURE_NAME"]) : 'шт.';
					break;
				case 'PRICE':
					$data = SaleFormatCurrency($unitPrice, $arBasket['CURRENCY'], true);
					break;
				case 'DISCOUNT':
					$data = $discountValue;
					break;
				case 'VAT_RATE':
					$data = roundEx($arBasket['VAT_RATE'] * 100, SALE_VALUE_PRECISION)."%";
					break;
				case 'SUM':
					$data = SaleFormatCurrency($arBasket["PRICE"] * $arBasket["QUANTITY"], $arBasket["CURRENCY"], true);
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

		$sum += doubleval($arBasket["PRICE"] * $arBasket["QUANTITY"]);
		$vat = max($vat, $arBasket["VAT_RATE"]);
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

	$items = $n;

	if (CSalePaySystemAction::GetParamValue('QUOTE_TOTAL_SHOW') == 'Y')
	{
		$eps = 0.0001;
		if ($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"] - $sum > $eps)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = "Подытог:";
			$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($sum, $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true);
		}

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
					($arTaxInfo["IS_IN_PRICE"] == "Y") ? "В том числе " : "",
					$arTaxInfo["NAME"],
					($vat <= 0 && $arTaxInfo["IS_PERCENT"] == "Y")
						? sprintf(' (%s%%)', roundEx($arTaxInfo["VALUE"],SALE_VALUE_PRECISION))
						: ""
				));
				$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($arTaxInfo["VALUE_MONEY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true);
			}
			unset($arTaxInfo);
		}
		else
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$i] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = htmlspecialcharsbx(htmlspecialcharsbx("НДС:"));
			$arCells[$n][$arColumnKeys[$columnCount-1]] = htmlspecialcharsbx(htmlspecialcharsbx("Без НДС"));
		}

		if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SUM_PAID"]) > 0)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = "Уже оплачено:";
			$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SUM_PAID"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true);
		}

		if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"]) > 0)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = "Скидка:";
			$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true);
		}

		$arCells[++$n] = array();
		for ($i = 0; $i < $columnCount; $i++)
			$arCells[$n][$arColumnKeys[$i]] = null;

		$arCells[$n][$arColumnKeys[$columnCount-2]] = "Итого:";
		$arCells[$n][$arColumnKeys[$columnCount-1]] = SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true);
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
for ($n = 0; $n <= $rowsCnt; $n++)
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
<?if (CSalePaySystemAction::GetParamValue('QUOTE_TOTAL_SHOW') == 'Y'):?>
	<?=sprintf(
		"Всего наименований %s, на сумму %s",
		$items,
		htmlspecialcharsbx(
			SaleFormatCurrency(
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"],
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
				false
			),
			ENT_COMPAT,
			false
		)
	); ?>
	<br>

	<b>
	<?

	if (in_array($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], array("RUR", "RUB")))
	{
		echo Number2Word_Rus($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"]);
	}
	else
	{
		echo htmlspecialcharsbx(SaleFormatCurrency(
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"],
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
				false
			),
			ENT_COMPAT,
			false
		);
	}
endif;

$sellerInfo = [
	'NAME' => CSalePaySystemAction::GetParamValue("SELLER_NAME", false),
	'ADDRESS' => CSalePaySystemAction::GetParamValue("SELLER_ADDRESS", false),
	'PHONE' => CSalePaySystemAction::GetParamValue("SELLER_PHONE", false),
	'EMAIL' => CSalePaySystemAction::GetParamValue("SELLER_EMAIL", false),
	'INN' => CSalePaySystemAction::GetParamValue("SELLER_INN", false),
	'KPP' => CSalePaySystemAction::GetParamValue("SELLER_KPP", false),
	'RS' => CSalePaySystemAction::GetParamValue("SELLER_RS", false),
	'BANK' => CSalePaySystemAction::GetParamValue("SELLER_BANK", false),
	'BIK' => CSalePaySystemAction::GetParamValue("SELLER_BIK", false),
	'BANK_CITY' => CSalePaySystemAction::GetParamValue("SELLER_BCITY", false),
	'KS' => CSalePaySystemAction::GetParamValue("SELLER_KS", false),

];

$customerInfo = [
	'NAME' => CSalePaySystemAction::GetParamValue("BUYER_NAME", false),
	'ADDRESS' => CSalePaySystemAction::GetParamValue("BUYER_ADDRESS", false),
	'PAYER_NAME' => CSalePaySystemAction::GetParamValue("BUYER_PAYER_NAME", false),
	'PHONE' => CSalePaySystemAction::GetParamValue("BUYER_PHONE", false),
	'FAX' => CSalePaySystemAction::GetParamValue("BUYER_FAX", false),
	'EMAIL' => CSalePaySystemAction::GetParamValue("BUYER_EMAIL", false),
	'INN' => CSalePaySystemAction::GetParamValue("BUYER_INN", false),
];

$cols = array(array(), array());
$colRows = array(0, 0);
$boldCount = -1;

$text = '';
if($sellerInfo['NAME'] || $customerInfo['NAME'])
{
	$boldCount = 0;
	$text = (is_string($sellerInfo['NAME']) && $sellerInfo['NAME'] <> '') ? $sellerInfo['NAME'] : '';
	$cols[0][$colRows[0]++] = $text;
	$text = (is_string($customerInfo['NAME']) && $customerInfo['NAME'] <> '') ? $customerInfo['NAME'] : '';
	$cols[1][$colRows[1]++] = $text;
	$boldCount = max($colRows);
}
if($sellerInfo['ADDRESS'] || $customerInfo['ADDRESS'])
{
	$i = 0;
	foreach (array($sellerInfo['ADDRESS'], $customerInfo['ADDRESS']) as $text)
	{
		if ($text)
		{
			if (is_array($text))
			{
				$text = implode(', ', $text);
			}
			else
			{
				$text = str_replace(array("\r\n", "\n", "\r"), ', ', strval($text));
			}
			$text = 'Адрес'.': '.$text;
			$cols[$i][$colRows[$i]++] = $text;
		}
		$i++;
	}
	unset($i);
}
if($customerInfo['PAYER_NAME'])
{
	$text = 'Контактное лицо'.': '.$customerInfo['PAYER_NAME'];
	$cols[1][$colRows[1]++] = $text;
}
if($sellerInfo['PHONE'] || $customerInfo['PHONE'])
{
	$i = 0;
	foreach (array($sellerInfo['PHONE'], $customerInfo['PHONE']) as $text)
	{
		if ($text)
		{
			$text = 'Телефон'.': '.$text;
			$cols[$i][$colRows[$i]++] = $text;
		}
		$i++;
	}
	unset($i);
}
if($customerInfo['FAX'])
{
	$text = 'Факс'.': '.$customerInfo['FAX'];
	$cols[1][$colRows[1]++] = $text;
}
if($sellerInfo['EMAIL'] || $customerInfo['EMAIL'])
{
	$i = 0;
	foreach (array($sellerInfo['EMAIL'], $customerInfo['EMAIL']) as $text)
	{
		if ($text)
		{
			$text = 'E-mail: '.$text;
			$cols[$i][$colRows[$i]++] = $text;
		}
		$i++;
	}
	unset($i);
}
if($sellerInfo['INN'] || $customerInfo['INN'])
{
	$i = 0;
	foreach (array($sellerInfo['INN'], $customerInfo['INN']) as $text)
	{
		if ($text)
		{
			$text = 'ИНН'.': '.$text;
			$cols[$i][$colRows[$i]++] = $text;
		}
		$i++;
	}
	unset($i);
}
if($sellerInfo['KPP'])
{
	$text = 'КПП'.': '.$sellerInfo['KPP'];
	$cols[0][$colRows[0]++] = $text;
}
if($sellerInfo['RS'])
{
	$text = 'Расчётный счёт'.': '.$sellerInfo['RS'];
	$cols[0][$colRows[0]++] = $text;
}
if($sellerInfo['BANK'])
{
	$text = '';
	if ($sellerInfo['BANK_CITY'])
	{
		$text = $sellerInfo['BANK_CITY'];
		if (is_array($text))
		{
			$text = implode(', ', $text);
		}
		else
		{
			$text = str_replace(array("\r\n", "\n", "\r"), ', ', strval($text));
		}
	}
	$text = 'Банк'.': '.
		strval($sellerInfo['BANK']).($text ? ', '.$text : '');
	$cols[0][$colRows[0]++] = $text;
}
if($sellerInfo['BIK'])
{
	$text = 'БИК'.': '.$sellerInfo['BIK'];
	$cols[0][$colRows[0]++] = $text;
}
if($sellerInfo['KS'])
{
	$text = 'Корреспондентский счет'.': '.$sellerInfo['KS'];
	$cols[0][$colRows[0]++] = $text;
}
unset($text);

?><br><br><?
$nCols = 2;
$nRows = max($colRows[0], $colRows[1]);
$showTable = ($nRows > 0);
if ($showTable)
{
?>
<table class="rq" width="100%">
	<colgroup>
		<col width="50%">
		<col width="50%">
	</colgroup>
	<tr>
<?
}
for ($col = 0; $col < $nCols; $col++)
{
	?><td><?
	for ($i = 0; $i < $nRows; $i++)
	{
		if (isset($cols[$col][$i]))
		{
			?><div><? echo ($i < $boldCount ? '<b>' : '').htmlspecialcharsbx($cols[$col][$i], ENT_COMPAT, false).($i < $boldCount ? '</b>' : ''); ?></div><?
		}
	}
	?></td><?
}
unset($cols, $colRows, $boldCount, $nRows, $i);
if ($showTable)
{
?>
	</tr>
</table>
<?
}
?><br><br><?
unset($cols, $colRows, $boldCount, $nCols, $nRows, $showTable);
if (CSalePaySystemAction::GetParamValue('QUOTE_SIGN_SHOW') == 'Y'):?>
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
				<td style="width: 150pt; "><?=htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SELLER_DIR_POS", false), ENT_COMPAT, false); ?></td>
				<td style="width: 160pt; border: 1pt solid #000000; border-width: 0pt 0pt 1pt 0pt; text-align: center; ">
					<? if (!$blank) { ?>
					<?=CFile::ShowImage(CSalePaySystemAction::GetParamValue("SELLER_DIR_SIGN", false), 200, 50); ?>
					<? } ?>
				</td>
				<td>
					<? if (CSalePaySystemAction::GetParamValue("SELLER_DIR", false)) { ?>
					(<?=htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SELLER_DIR", false), ENT_COMPAT, false); ?>)
					<? } ?>
				</td>
			</tr>
			<tr><td colspan="3">&nbsp;</td></tr>
			<? } ?>
			<? if (CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false)) { ?>
			<tr>
				<td style="width: 150pt; "><?=htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false), ENT_COMPAT, false); ?></td>
				<td style="width: 160pt; border: 1pt solid #000000; border-width: 0pt 0pt 1pt 0pt; text-align: center; ">
					<? if (!$blank) { ?>
					<?=CFile::ShowImage(CSalePaySystemAction::GetParamValue("SELLER_ACC_SIGN", false), 200, 50); ?>
					<? } ?>
				</td>
				<td>
					<? if (CSalePaySystemAction::GetParamValue("SELLER_ACC", false)) { ?>
					(<?=htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SELLER_ACC", false), ENT_COMPAT, false); ?>)
					<? } ?>
				</td>
			</tr>
			<? } ?>
		</table>
	</div>
<?endif;?>
</div>

</body>
</html>