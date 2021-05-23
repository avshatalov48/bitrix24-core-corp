<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
$arPaySysAction["ENCODING"] = "";
if (!CSalePdf::isPdfAvailable())
	die();

if ($_REQUEST['BLANK'] == 'Y')
	$blank = true;
/** @var CSaleTfpdf $pdf */
$pdf = new CSalePdf('P', 'pt', 'A4');
if (CSalePaySystemAction::GetParamValue('BACKGROUND', false))
{
	$pdf->SetBackground(
		CSalePaySystemAction::GetParamValue('BACKGROUND', false),
		CSalePaySystemAction::GetParamValue('BACKGROUND_STYLE', false)
	);
}

$pageWidth  = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();

$pdf->AddFont('Font', '', 'pt_sans-regular.ttf', true);
$pdf->AddFont('Font', 'B', 'pt_sans-bold.ttf', true);

$fontFamily = 'Font';
$fontSize   = 10.5;

$margin = array(
	'top' => intval(CSalePaySystemAction::GetParamValue('MARGIN_TOP', false) ?: 15) * 72/25.4,
	'right' => intval(CSalePaySystemAction::GetParamValue('MARGIN_RIGHT', false) ?: 15) * 72/25.4,
	'bottom' => intval(CSalePaySystemAction::GetParamValue('MARGIN_BOTTOM', false) ?: 15) * 72/25.4,
	'left' => intval(CSalePaySystemAction::GetParamValue('MARGIN_LEFT', false) ?: 20) * 72/25.4
);

$width = $pageWidth - $margin['left'] - $margin['right'];

$pdf->SetDisplayMode(100, 'continuous');
$pdf->SetMargins($margin['left'], $margin['top'], $margin['right']);
$pdf->SetAutoPageBreak(true, $margin['bottom']);

$pdf->AddPage();
$pdf->SetFont($fontFamily, 'B', $fontSize);

$y0 = $pdf->GetY();
$logoHeight = 0;
$logoWidth = 0;
if (CSalePaySystemAction::GetParamValue('QUOTE_HEADER_SHOW') == 'Y')
{
	if (CSalePaySystemAction::GetParamValue('PATH_TO_LOGO', false))
	{
		list($imageHeight, $imageWidth) = $pdf->GetImageSize(CSalePaySystemAction::GetParamValue('PATH_TO_LOGO', false));

		$imgDpi = intval(CSalePaySystemAction::GetParamValue('LOGO_DPI', false)) ?: 96;
		$imgZoom = 96 / $imgDpi;

		$logoHeight = $imageHeight * $imgZoom + 5;
		$logoWidth  = $imageWidth * $imgZoom + 5;

		$pdf->Image(CSalePaySystemAction::GetParamValue('PATH_TO_LOGO', false), $pdf->GetX(), $pdf->GetY(), -$imgDpi, -$imgDpi);
	}

	// region Seller info
	$topLogo = false;
	$minTextWidth = 20;
	$textWidth = $width - $logoWidth - 10;

	if ($textWidth < $minTextWidth)
		$topLogo = true;

	if ($topLogo)
	{
		$textLeftMargin = 0;
		$textWidth = $width - 10;
		$pdf->SetY(max($y0 + $logoHeight, $pdf->GetY()));
		$pdf->Ln(10);
	}
	else
	{
		$textLeftMargin = $logoWidth;
	}

	$sellerName =  CSalePaySystemAction::GetParamValue("SELLER_NAME", false);
	if($sellerName)
	{
		$text = CSalePdf::prepareToPdf($sellerName);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX() + $textLeftMargin);
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'R');
			$pdf->Ln();
		}
		unset($text, $string);
	}

	$sellerAddr = CSalePaySystemAction::GetParamValue("SELLER_ADDRESS", false);
	if($sellerAddr)
	{
		if (is_array($sellerAddr))
			$sellerAddr = implode(', ', $sellerAddr);
		else
			$sellerAddr = str_replace(array("\r\n", "\n", "\r"), ', ', strval($sellerAddr));
			$text = CSalePdf::prepareToPdf($sellerAddr);
			while ($pdf->GetStringWidth($text))
			{
				list($string, $text) = $pdf->splitString($text, $textWidth);
				$pdf->SetX($pdf->GetX() + $textLeftMargin);
				$pdf->Cell($textWidth, 15, $string, 0, 0, 'R');
				$pdf->Ln();
			}
			unset($text, $string);
		}

	$sellerPhone = CSalePaySystemAction::GetParamValue("SELLER_PHONE", false);
	if($sellerPhone)
	{
		$sellerPhone = sprintf("Тел.: %s", $sellerPhone);
		$text = CSalePdf::prepareToPdf($sellerPhone);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX() + $textLeftMargin);
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'R');
			$pdf->Ln();
		}
		unset($text, $string);
	}

	$sellerEmail = CSalePaySystemAction::GetParamValue("SELLER_EMAIL", false);
	if($sellerEmail)
	{
		$sellerEmail = sprintf("E-mail: %s", $sellerEmail);
		$text = CSalePdf::prepareToPdf($sellerEmail);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX() + $textLeftMargin);
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'R');
			$pdf->Ln();
		}
		unset($text, $string);
	}

	if (!$topLogo)
	{
		$pdf->SetY(max($y0 + $logoHeight, $pdf->GetY()));
		$pdf->Ln(10);
	}

	unset($topLogo, $minTextWidth, $textWidth, $textLeftMargin);
	// endregion Seller info

	$pdf->SetFont($fontFamily, '', $fontSize);
	$pdf->Ln();

	$pdf->SetFont($fontFamily, 'B', $fontSize*1.5);
	$billNo_tmp = CSalePdf::prepareToPdf(sprintf(
		"КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ № %s от %s",
		$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ACCOUNT_NUMBER"],
		CSalePaySystemAction::GetParamValue("DATE_INSERT", false)
	));
	$billNo_width = $pdf->GetStringWidth($billNo_tmp);
	$pdf->Cell(0, 20, $billNo_tmp, 0, 0, 'C');
	$pdf->Ln();

	$pdf->SetFont($fontFamily, '', $fontSize);
	if (CSalePaySystemAction::GetParamValue("ORDER_SUBJECT", false))
	{
		$pdf->Cell($width/2-$billNo_width/2-2, 15, '');
		$pdf->MultiCell(0, 15, HTMLToTxt(preg_replace(
			array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
			CSalePdf::prepareToPdf(CSalePaySystemAction::GetParamValue("ORDER_SUBJECT", false))
		), '', array(), 0), 0, 'L');
		$pdf->Ln();
	}

	if (CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false))
	{
		$pdf->Cell($width/2-$billNo_width/2-2, 15, '');
		$pdf->MultiCell(0, 15, CSalePdf::prepareToPdf(sprintf(
			"Срок действия %s",
			ConvertDateTime(CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false), FORMAT_DATE)
				?: CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false)
		)), 0, 'L');
		$pdf->Ln();
	}
}

$userFields = array();
for($i = 1; $i <= 5; $i++)
{
	$fildValue = CSalePaySystemAction::GetParamValue("USER_FIELD_{$i}", false);
	if($fildValue)
	{
		$userFields[] = $fildValue;
	}
}

if (CSalePaySystemAction::GetParamValue("COMMENT1", false)
	|| CSalePaySystemAction::GetParamValue("COMMENT2", false)
	|| !empty($userFields))
{
	$pdf->SetFont($fontFamily, 'B', $fontSize);
	$pdf->Write(15, CSalePdf::prepareToPdf('Условия и комментарии'));
	$pdf->Ln();

	$pdf->SetFont($fontFamily, '', $fontSize);

	if (CSalePaySystemAction::GetParamValue("COMMENT1", false))
	{
		$pdf->Write(15, HTMLToTxt(preg_replace(
			array('#</div>\s*<div[^>]*>#i', '#</?div>#i', '#<br/?>$#'), array('<br>', '<br>', ''),
			CSalePdf::prepareToPdf(CSalePaySystemAction::GetParamValue("COMMENT1", false))
		), '', array(), 0));
		$pdf->Ln();
		$pdf->Ln();
	}

	if (CSalePaySystemAction::GetParamValue("COMMENT2", false))
	{
		$pdf->Write(15, HTMLToTxt(preg_replace(
			array('#</div>\s*<div[^>]*>#i', '#</?div>#i', '#<br/?>$#'), array('<br>', '<br>', ''),
			CSalePdf::prepareToPdf(CSalePaySystemAction::GetParamValue("COMMENT2", false))
		), '', array(), 0));
		$pdf->Ln();
		$pdf->Ln();
	}

	foreach($userFields as &$userField)
	{
		$pdf->Write(15, HTMLToTxt(preg_replace(
			array('#</div>\s*<div[^>]*>#i', '#</?div>#i', '#<br/?>$#'), array('<br>', '<br>', ''),
			CSalePdf::prepareToPdf($userField)
		), '', array(), 0));
		$pdf->Ln();
		$pdf->Ln();
	}
	unset($userField);
}
else
{
	$pdf->Ln();
}

// Список товаров
$arBasketItems = CSalePaySystemAction::GetParamValue("BASKET_ITEMS", false);
if(!is_array($arBasketItems))
	$arBasketItems = array();

$arCurFormat = CCurrencyLang::GetCurrencyFormat($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"]);
$currency = trim(preg_replace('/(^|[^&])#/', '${1}', $arCurFormat['FORMAT_STRING']));

$vat = 0;
$arCols = array();
$arCells = array();

$vat = 0;
if (!empty($arBasketItems))
{
	$arBasketItems = getMeasures($arBasketItems);

	$arProps = array();

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
				'NAME' => CSalePdf::prepareToPdf($caption),
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
				'NAME' => CSalePdf::prepareToPdf($val['NAME']),
				'SORT' => $val['SORT']
			);
		}
	}

	uasort($arCols, function ($a, $b) {return ($a['SORT'] < $b['SORT']) ? -1 : 1;});

	$arColumnKeys = array_keys($arCols);
	$columnCount = count($arColumnKeys);

	$n = 0;
	$sum = 0.00;

	foreach($arBasketItems as &$arBasket)
	{
		$productName = $arBasket["NAME"];
		if ($productName == "OrderDelivery")
			$productName = htmlspecialcharsbx("Доставка");
		else if ($productName == "OrderDiscount")
			$productName = htmlspecialcharsbx("Скидка");

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
					$discountValue = SaleFormatCurrency($discountSum, $arBasket["CURRENCY"], true);
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

		$arCells[++$n] = array();
		foreach ($arCols as $columnId => $caption)
		{
			$data = null;

			switch ($columnId)
			{
				case 'NUMBER':
					$data = CSalePdf::prepareToPdf($n);
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				case 'NAME':
					$data = CSalePdf::prepareToPdf($productName);
					break;
				case 'QUANTITY':
					$data = CSalePdf::prepareToPdf(roundEx($arBasket["QUANTITY"], SALE_VALUE_PRECISION));
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				case 'MEASURE':
					$data = CSalePdf::prepareToPdf($arBasket["MEASURE_NAME"] ? $arBasket["MEASURE_NAME"] : 'шт.');
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				case 'PRICE':
					$data = CSalePdf::prepareToPdf(SaleFormatCurrency($unitPrice, $arBasket['CURRENCY'], true));
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				case 'DISCOUNT':
					$data = CSalePdf::prepareToPdf($discountValue);
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				case 'VAT_RATE':
					$data = CSalePdf::prepareToPdf(roundEx($arBasket["VAT_RATE"]*100, SALE_VALUE_PRECISION)."%");
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				case 'SUM':
					$data = CSalePdf::prepareToPdf(SaleFormatCurrency($arBasket["PRICE"] * $arBasket["QUANTITY"], $arBasket["CURRENCY"], true));
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				default:
					if ($arBasket[$columnId] != '' && preg_match('/[^0-9 ,\.]/', $arBasket[$columnId]) === 0)
					{
						if (!array_key_exists('IS_DIGIT', $arCols[$columnId]))
							$arCols[$columnId]['IS_DIGIT'] = true;
					}
					else
					{
						$arCols[$columnId]['IS_DIGIT'] = false;
					}
					$data = ($arBasket[$columnId]) ? CSalePdf::prepareToPdf($arBasket[$columnId]) : '';
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

			$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf("Подытог:");
			$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($sum, $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true));
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

				$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf(sprintf(
					"%s%s%s:",
					($arTaxInfo["IS_IN_PRICE"] == "Y") ? "В том числе " : "",
					$arTaxInfo["TAX_NAME"],
					($vat <= 0 && $arTaxInfo["IS_PERCENT"] == "Y") ? sprintf(' (%s%%)', roundEx($arTaxInfo["VALUE"],SALE_VALUE_PRECISION)) : ""
				));
				$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(CSalePdf::prepareToPdf(SaleFormatCurrency($arTaxInfo["VALUE_MONEY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true)));
			}
			unset($arTaxInfo);
		}
		else
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf("НДС:");
			$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf("Без НДС");
		}

		if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"]) > 0)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf(CSalePdf::prepareToPdf("Скидка:"));
			$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true));
		}

		$arCells[++$n] = array();
		for ($i = 0; $i < $columnCount; $i++)
			$arCells[$n][$arColumnKeys[$i]] = null;

		$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf("Итого:");
		$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], true));
	}

	$rowsInfo = $pdf->calculateRowsWidth($arCols, $arCells, $items, $width);
	$arRowsWidth = $rowsInfo['ROWS_WIDTH'];
	$arRowsContentWidth = $rowsInfo['ROWS_CONTENT_WIDTH'];
}

$x0 = $pdf->GetX();

$l = 0;
do
{
	$y0 = $pdf->GetY();
	$newLine = false;
	foreach ($arCols as $columnId => $column)
	{
		list($string, $arCols[$columnId]['NAME']) = $pdf->splitString($column['NAME'], $arRowsContentWidth[$columnId]);
		if (($vat > 0 || $columnId !== 'VAT_RATE') && ($bShowDiscount || $columnId !== 'DISCOUNT'))
			$pdf->Cell($arRowsWidth[$columnId], 20, $string, 0, 0, 'C');

		if ($arCols[$columnId]['NAME'])
			$newLine = true;

		$i = array_search($columnId, $arColumnKeys);
		${"x".($i+1)} = $pdf->GetX();
	}
	if ($arCols && $l === 0)
	{
		$y = $pdf->GetY();
		if ($y0 > $y)
			$y0 = $margin['top'];
		unset($y);
		$pdf->Line($x0, $y0, ${"x".$columnCount}, $y0);
	}

	$pdf->Ln();
	$l++;

	$y5 = $pdf->GetY();

	if ($y0 > $y5)
		$y0 = $margin['top'];

	for ($i = 0; $i <= $columnCount; $i++)
	{
		if (($vat > 0 || $arColumnKeys[$i] !== 'VAT_RATE') && ($bShowDiscount || $arColumnKeys[$i] !== 'DISCOUNT'))
			$pdf->Line(${"x$i"}, $y0, ${"x$i"}, $y5);
	}
}
while($newLine);

if ($arCols)
	$pdf->Line($x0, $y5, ${'x'.$columnCount}, $y5);

$rowsCnt = count($arCells);
for ($n = 1; $n <= $rowsCnt; $n++)
{
	$arRowsWidth_tmp = $arRowsWidth;
	$arRowsContentWidth_tmp = $arRowsContentWidth;
	$accumulated = 0;
	$accumulatedContent = 0;
	foreach ($arCols as $columnId => $column)
	{
		if (is_null($arCells[$n][$columnId]))
		{
			$accumulated += $arRowsWidth_tmp[$columnId];
			$arRowsWidth_tmp[$columnId] = null;
			$accumulatedContent += $arRowsContentWidth_tmp[$columnId];
			$arRowsContentWidth_tmp[$columnId] = null;
		}
		else
		{
			$arRowsWidth_tmp[$columnId] += $accumulated;
			$arRowsContentWidth_tmp[$columnId] += $accumulatedContent;
			$accumulated = 0;
			$accumulatedContent = 0;
		}
	}

	$x0 = $pdf->GetX();

	$pdf->SetFont($fontFamily, '', $fontSize);

	$l = 0;
	do
	{
		$y0 = $pdf->GetY();
		$newLine = false;
		foreach ($arCols as $columnId => $column)
		{
			$string = '';
			if (!is_null($arCells[$n][$columnId]))
				list($string, $arCells[$n][$columnId]) = $pdf->splitString($arCells[$n][$columnId], $arRowsContentWidth_tmp[$columnId]);

			$rowWidth = $arRowsWidth_tmp[$columnId];

			if (in_array($columnId, array('QUANTITY', 'MEASURE', 'PRICE', 'SUM')))
			{
				if (!is_null($arCells[$n][$columnId]))
				{
					$pdf->Cell($rowWidth, 15, $string, 0, 0, 'R');
				}
			}
			elseif ($columnId == 'NUMBER')
			{
				if (!is_null($arCells[$n][$columnId]))
					$pdf->Cell($rowWidth, 15, ($l == 0) ? $string : '', 0, 0, 'C');
			}
			elseif ($columnId == 'NAME')
			{
				if (!is_null($arCells[$n][$columnId]))
					$pdf->Cell($rowWidth, 15, $string, 0, 0,  ($n > $items) ? 'R' : '');
			}
			elseif ($columnId == 'VAT_RATE')
			{
				if (!is_null($arCells[$n][$columnId]))
				{
					if (is_null($arCells[$n][$columnId]))
						$pdf->Cell($rowWidth, 15, $string, 0, 0, 'R');
					else if ($vat > 0)
						$pdf->Cell($rowWidth, 15, ($l == 0) ? $string : '', 0, 0, 'R');
				}
			}
			elseif ($columnId == 'DISCOUNT')
			{
				if (!is_null($arCells[$n][$columnId]))
				{
					if (is_null($arCells[$n][$columnId]))
						$pdf->Cell($rowWidth, 15, $string, 0, 0, 'R');
					else if ($bShowDiscount)
						$pdf->Cell($rowWidth, 15, ($l == 0) ? $string : '', 0, 0, 'R');
				}
			}
			else
			{
				if (!is_null($arCells[$n][$columnId]))
				{
					$pdf->Cell($rowWidth, 15, $string, 0, 0, 'R');
				}
			}

			if ($l == 0)
			{
				$pos = array_search($columnId, $arColumnKeys);
				${'x'.($pos+1)} = $pdf->GetX();
			}

			if ($arCells[$n][$columnId])
				$newLine = true;
		}

		$pdf->Ln();
		$l++;

		$y5 = $pdf->GetY();

		if ($y0 > $y5)
			$y0 = $margin['top'];

		for ($i = ($n > $items) ? $columnCount - 1 : 0; $i <= $columnCount; $i++)
		{
			if ($vat > 0 || $arColumnKeys[$i] != 'VAT_RATE')
				$pdf->Line(${"x$i"}, $y0, ${"x$i"}, $y5);
		}
	}
	while($newLine);

	if (CSalePaySystemAction::GetParamValue('QUOTE_COLUMN_NAME_SHOW') == 'Y')
	{
		if (isset($arProps[$n]) && is_array($arProps[$n]))
		{
			$pdf->SetFont($fontFamily, '', $fontSize - 2);
			foreach ($arProps[$n] as $property)
			{
				$y0 = $pdf->GetY();
				$i = 0;
				$line = 0;
				foreach ($arCols as $columnId => $caption)
				{
					$i++;
					if ($i == $columnCount)
						$line = 1;
					if ($columnId == 'NAME')
						$pdf->Cell($arRowsWidth_tmp[$columnId], 12, $property, 0, $line);
					else
						$pdf->Cell($arRowsWidth_tmp[$columnId], 12, '', 0, $line);
				}
				$y5 = $pdf->GetY();

				if ($y0 > $y5)
					$y0 = $margin['top'];

				for ($i = ($n > $items) ? $columnCount - 1 : 0; $i <= $columnCount; $i++)
				{
					if ($vat > 0 || $arColumnKeys[$i] != 'VAT_RATE')
						$pdf->Line(${"x$i"}, $y0, ${"x$i"}, $y5);
				}
			}
		}
	}

	$pdf->Line(($n <= $items) ? $x0 : ${'x'.($columnCount-1)}, $y5, ${'x'.$columnCount}, $y5);
}
$pdf->Ln();


if (CSalePaySystemAction::GetParamValue('QUOTE_TOTAL_SHOW') == 'Y')
{
	$pdf->SetFont($fontFamily, '', $fontSize);
	$pdf->Write(15, CSalePdf::prepareToPdf(sprintf(
		"Всего наименований %s, на сумму %s",
		$items,
		SaleFormatCurrency(
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"],
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
			false
		)
	)));
	$pdf->Ln();

	$pdf->SetFont($fontFamily, 'B', $fontSize);
	if (in_array($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], array("RUR", "RUB")))
	{
		$pdf->Write(15, CSalePdf::prepareToPdf(Number2Word_Rus($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"])));
	}
	else
	{
		$pdf->Write(15, CSalePdf::prepareToPdf(SaleFormatCurrency(
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"],
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
			false
		)));
	}
	$pdf->Ln();
}
$sellerInfo = array(
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

);

$customerInfo = array(
	'NAME' => CSalePaySystemAction::GetParamValue("BUYER_NAME", false),
	'ADDRESS' => CSalePaySystemAction::GetParamValue("BUYER_ADDRESS", false),
	'PAYER_NAME' => CSalePaySystemAction::GetParamValue("BUYER_PAYER_NAME", false),
	'PHONE' => CSalePaySystemAction::GetParamValue("BUYER_PHONE", false),
	'FAX' => CSalePaySystemAction::GetParamValue("BUYER_FAX", false),
	'EMAIL' => CSalePaySystemAction::GetParamValue("BUYER_EMAIL", false),
	'INN' => CSalePaySystemAction::GetParamValue("BUYER_INN", false)
);

$pdf->Ln();

$x0 = $pdf->GetX();
$y0 = $pdf->GetY();

$colWidth = $width / 2;
$textWidth = $colWidth - 5;
$sellerX = $x0;
$customerX = $x0 + $colWidth + 5;

$cols = array(array(), array());
$colRows = array(0, 0);
$boldCount = -1;

if($sellerInfo['NAME'] || $customerInfo['NAME'])
{
	$boldCount = 0;
	$text = CSalePdf::prepareToPdf($sellerInfo['NAME']);
	while ($pdf->GetStringWidth($text))
		list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
	if ($colRows[0] === 0)
		$cols[0][$colRows[0]++] = '';
	$text = CSalePdf::prepareToPdf($customerInfo['NAME']);
	while ($pdf->GetStringWidth($text))
	{
		list($cols[1][$colRows[1]++], $text) = $pdf->splitString($text, $textWidth);
	}
	if ($colRows[1] === 0)
		$cols[1][$colRows[1]++] = '';
	$boldCount = max($colRows);
	while ($colRows[0] < $boldCount)
		$cols[0][$colRows[0]++] = '';
	while ($colRows[1] < $boldCount)
		$cols[1][$colRows[1]++] = '';
	$boldCount--;
}

if ($boldCount >= 0)
	$pdf->SetFont($fontFamily, 'B', $fontSize);
else
	$pdf->SetFont($fontFamily, '', $fontSize);

if($sellerInfo['ADDRESS'] || $customerInfo['ADDRESS'])
{
	$sellerAddr = $sellerInfo['ADDRESS'];
	if($sellerAddr)
	{
		if (is_array($sellerAddr))
			$sellerAddr = implode(', ', $sellerAddr);
		else
			$sellerAddr = str_replace(array("\r\n", "\n", "\r"), ', ', strval($sellerAddr));
		$text = CSalePdf::prepareToPdf('Адрес: '.$sellerAddr);
		while ($pdf->GetStringWidth($text))
			list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
	}
	unset($sellerAddr);
	$customerAddr = $customerInfo['ADDRESS'];
	if($customerAddr)
	{
		if (is_array($customerAddr))
			$customerAddr = implode(', ', $customerAddr);
		else
			$customerAddr = str_replace(array("\r\n", "\n", "\r"), ', ', strval($customerAddr));
		$text = CSalePdf::prepareToPdf('Адрес: '.$customerAddr);
		while ($pdf->GetStringWidth($text))
			list($cols[1][$colRows[1]++], $text) = $pdf->splitString($text, $textWidth);
	}
	unset($customerAddr);
}

if($customerInfo['PAYER_NAME'])
{
	$text = CSalePdf::prepareToPdf('Контактное лицо: '.$customerInfo['PAYER_NAME']);
	while ($pdf->GetStringWidth($text))
		list($cols[1][$colRows[1]++], $text) = $pdf->splitString($text, $textWidth);
}

if($sellerInfo['PHONE'] || $customerInfo['PHONE'])
{
	if ($sellerInfo['PHONE'])
	{
		$text = CSalePdf::prepareToPdf('Телефон: '.$sellerInfo['PHONE']);
		while ($pdf->GetStringWidth($text))
			list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
	}
	if ($customerInfo['PHONE'])
	{
		$text = CSalePdf::prepareToPdf('Телефон: '.$customerInfo['PHONE']);
		while ($pdf->GetStringWidth($text))
			list($cols[1][$colRows[1]++], $text) = $pdf->splitString($text, $textWidth);
	}
}

if($customerInfo['FAX'])
{
	if ($customerInfo['FAX'])
	{
		$text = CSalePdf::prepareToPdf('Факс: '.$customerInfo['FAX']);
		while ($pdf->GetStringWidth($text))
			list($cols[1][$colRows[1]++], $text) = $pdf->splitString($text, $textWidth);
	}
}

if($sellerInfo['EMAIL'] || $customerInfo['EMAIL'])
{
	if ($sellerInfo['EMAIL'])
	{
		$text = CSalePdf::prepareToPdf('E-mail: '.$sellerInfo['EMAIL']);
		while ($pdf->GetStringWidth($text))
			list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
	}
	if ($customerInfo['EMAIL'])
	{
		$text = CSalePdf::prepareToPdf('E-mail: '.$customerInfo['EMAIL']);
		while ($pdf->GetStringWidth($text))
			list($cols[1][$colRows[1]++], $text) = $pdf->splitString($text, $textWidth);
	}
}

if($sellerInfo['INN'] || $customerInfo['INN'])
{
	if ($sellerInfo['INN'])
	{
		$text = CSalePdf::prepareToPdf('ИНН: '.$sellerInfo['INN']);
		while ($pdf->GetStringWidth($text))
			list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
	}
	if ($customerInfo['INN'])
	{
		$text = CSalePdf::prepareToPdf('ИНН: '.$customerInfo['INN']);
		while ($pdf->GetStringWidth($text))
			list($cols[1][$colRows[1]++], $text) = $pdf->splitString($text, $textWidth);
	}
}

if($sellerInfo['KPP'])
{
	$text = CSalePdf::prepareToPdf('КПП: '.$sellerInfo['KPP']);
	while ($pdf->GetStringWidth($text))
		list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
}

if($sellerInfo['RS'])
{
	$text = CSalePdf::prepareToPdf('Расчётный счёт: '.$sellerInfo['RS']);
	while ($pdf->GetStringWidth($text))
		list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
}

if($sellerInfo['BANK'])
{
	$bankName = $sellerInfo['BANK'];

	$sellerBankCity = '';
	if($sellerInfo['BANK_CITY'])
	{
		$sellerBankCity = $sellerInfo['BANK_CITY'];
		if (is_array($sellerBankCity))
			$sellerBankCity = implode(', ', $sellerBankCity);
		else
			$sellerBankCity = str_replace(array("\r\n", "\n", "\r"), ', ', strval($sellerBankCity));
		$bankName .= ', ';
		$bankName .= $sellerBankCity;
	}

	$text = CSalePdf::prepareToPdf('Банк: '.$bankName);
	while ($pdf->GetStringWidth($text))
		list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
}

if($sellerInfo['BIK'])
{
	$text = CSalePdf::prepareToPdf('БИК: '.$sellerInfo['BIK']);
	while ($pdf->GetStringWidth($text))
		list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
}

if($sellerInfo['KS'])
{
	$text = CSalePdf::prepareToPdf('Корреспондентский счет: '.$sellerInfo['KS']);
	while ($pdf->GetStringWidth($text))
		list($cols[0][$colRows[0]++], $text) = $pdf->splitString($text, $textWidth);
}

$nRows = max($colRows[0], $colRows[1]);
for ($i = 0; $i < $nRows; $i++)
{
	if (isset($cols[0][$i]))
	{
		$pdf->SetX($sellerX);
		$pdf->Cell($textWidth, 18, $cols[0][$i], 0, 'L');
	}
	if (isset($cols[1][$i]))
	{
		$pdf->SetX($customerX);
		$pdf->Cell($textWidth, 18, $cols[1][$i], 0, 'L');
	}
	$pdf->Ln();
	if ($i === $boldCount)
	{
		$pdf->SetFont($fontFamily, '', $fontSize);
	}
}
if ($i === 0)
	$pdf->SetFont($fontFamily, '', $fontSize);
$pdf->Ln();
unset($cols, $colRows, $colWidth, $textWidth, $sellerX, $customerX, $nRows, $i);

if (CSalePaySystemAction::GetParamValue('QUOTE_SIGN_SHOW') == 'Y')
{
	if (!$blank && CSalePaySystemAction::GetParamValue('PATH_TO_STAMP', false))
	{
		list($stampHeight, $stampWidth) = $pdf->GetImageSize(CSalePaySystemAction::GetParamValue('PATH_TO_STAMP', false));

		if ($stampHeight && $stampWidth)
		{
			if ($stampHeight > 120 || $stampWidth > 120)
			{
				$ratio = 120 / max($stampHeight, $stampWidth);
				$stampHeight = $ratio * $stampHeight;
				$stampWidth  = $ratio * $stampWidth;
			}

			$imageY = $pdf->GetY();
			$pageNumBefore = $pdf->PageNo();

			$pdf->Image(
				CSalePaySystemAction::GetParamValue('PATH_TO_STAMP', false),
				$margin['left']+40, null,
				$stampWidth, $stampHeight
			);

			$pageNumAfter = $pdf->PageNo();
			if ($pageNumAfter === $pageNumBefore)
				$pdf->SetY($imageY);
			else
				$pdf->SetY($pdf->GetY() - $stampHeight);
			unset($imageY, $pageNumBefore, $pageNumAfter);
		}
	}

	$pdf->Ln();

	$pdf->SetFont($fontFamily, 'B', $fontSize);

	if (CSalePaySystemAction::GetParamValue("SELLER_DIR_POS", false))
	{
		$isDirSign = false;
		if (!$blank && CSalePaySystemAction::GetParamValue('SELLER_DIR_SIGN', false))
		{
			list($signHeight, $signWidth) = $pdf->GetImageSize(CSalePaySystemAction::GetParamValue('SELLER_DIR_SIGN', false));

			if ($signHeight && $signWidth)
			{
				$ratio = min(37.5/$signHeight, 150/$signWidth);
				$signHeight = $ratio * $signHeight;
				$signWidth  = $ratio * $signWidth;

				$isDirSign = true;
			}
		}

		$sellerDirPos = HTMLToTxt(
			preg_replace(
				array('#</div>\s*<div[^>]*>#i', '#</?div>#i'),
				array('<br>', '<br>'),
				CSalePdf::prepareToPdf(CSalePaySystemAction::GetParamValue("SELLER_DIR_POS", false))
			), '', array(), 0
		);
		if ($isDirSign && $pdf->GetStringWidth($sellerDirPos) <= 160)
			$pdf->SetY($pdf->GetY() + min($signHeight, 30) - 15);
		$pdf->MultiCell(150, 15, $sellerDirPos, 0, 'L');
		$pdf->SetXY($margin['left'] + 150, $pdf->GetY() - 15);

		if ($isDirSign)
		{
			$pdf->Image(
				CSalePaySystemAction::GetParamValue('SELLER_DIR_SIGN', false),
				$pdf->GetX() + 80 - $signWidth/2, $pdf->GetY() - $signHeight + 15,
				$signWidth, $signHeight
			);
		}

		$x1 = $pdf->GetX();
		$pdf->Cell(160, 15, '');
		$x2 = $pdf->GetX();

		if (CSalePaySystemAction::GetParamValue("SELLER_DIR", false))
			$pdf->Write(15, CSalePdf::prepareToPdf('('.CSalePaySystemAction::GetParamValue("SELLER_DIR", false).')'));
		$pdf->Ln();

		$y2 = $pdf->GetY();
		$pdf->Line($x1, $y2, $x2, $y2);

		$pdf->Ln();
	}

	if (CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false))
	{
		$isAccSign = false;
		if (!$blank && CSalePaySystemAction::GetParamValue('SELLER_ACC_SIGN', false))
		{
			list($signHeight, $signWidth) = $pdf->GetImageSize(CSalePaySystemAction::GetParamValue('SELLER_ACC_SIGN', false));

			if ($signHeight && $signWidth)
			{
				$ratio = min(37.5/$signHeight, 150/$signWidth);
				$signHeight = $ratio * $signHeight;
				$signWidth  = $ratio * $signWidth;

				$isAccSign = true;
			}
		}

		$sellerAccPos = HTMLToTxt(
			preg_replace(
				array('#</div>\s*<div[^>]*>#i', '#</?div>#i'),
				array('<br>', '<br>'),
				CSalePdf::prepareToPdf(CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false))
			), '', array(), 0
		);
		if ($isAccSign && $pdf->GetStringWidth($sellerAccPos) <= 160)
			$pdf->SetY($pdf->GetY() + min($signHeight, 30) - 15);
		$pdf->MultiCell(150, 15, $sellerAccPos, 0, 'L');
		$pdf->SetXY($margin['left'] + 150, $pdf->GetY() - 15);

		if ($isAccSign)
		{
			$pdf->Image(
				CSalePaySystemAction::GetParamValue('SELLER_ACC_SIGN', false),
				$pdf->GetX() + 80 - $signWidth/2, $pdf->GetY() - $signHeight + 15,
				$signWidth, $signHeight
			);
		}

		$x1 = $pdf->GetX();
		$pdf->Cell((CSalePaySystemAction::GetParamValue("SELLER_DIR", false)) ? $x2-$x1 : 160, 15, '');
		$x2 = $pdf->GetX();

		if (CSalePaySystemAction::GetParamValue("SELLER_ACC", false))
			$pdf->Write(15, CSalePdf::prepareToPdf('('.CSalePaySystemAction::GetParamValue("SELLER_ACC", false).')'));
		$pdf->Ln();

		$y2 = $pdf->GetY();
		$pdf->Line($x1, $y2, $x2, $y2);
	}
}

$dest = 'I';
if ($_REQUEST['GET_CONTENT'] == 'Y')
	$dest = 'S';
else if ($_REQUEST['DOWNLOAD'] == 'Y')
	$dest = 'D';

$fileName = sprintf(
	'Quote No %s ot %s.pdf',
	str_replace(
		array(
			chr(0), chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), chr(9), chr(10), chr(11),
			chr(12), chr(13), chr(14), chr(15), chr(16), chr(17), chr(18), chr(19), chr(20), chr(21), chr(22),
			chr(23), chr(24), chr(25), chr(26), chr(27), chr(28), chr(29), chr(30), chr(31),
			'"', '*', '/', ':', '<', '>', '?', '\\', '|'
		),
		'_',
		strval($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ACCOUNT_NUMBER"])
	),
	ConvertDateTime($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"], 'YYYY-MM-DD')
);

$trFileName = CUtil::translit($fileName, 'ru', array('max_len' => 1024, 'safe_chars' => '.', 'replace_space' => '-'));

return $pdf->Output($trFileName, $dest, $fileName);
?>