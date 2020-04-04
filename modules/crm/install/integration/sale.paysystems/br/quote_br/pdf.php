<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?

use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;

$lng = 'br';
Loc::loadLanguageFile(__FILE__, $lng);

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
$minRowWidth = 10;

$pdf->SetDisplayMode(100, 'continuous');
$pdf->SetMargins($margin['left'], $margin['top'], $margin['right']);
$pdf->SetAutoPageBreak(true, $margin['bottom']);

$pdf->AddPage();
$pdf->SetFont($fontFamily, '', $fontSize);

$y0 = $pdf->GetY();
$logoHeight = 0;
$logoWidth = 0;

if (CSalePaySystemAction::GetParamValue('QUOTE_BR_HEADER_SHOW') == 'Y')
{
	$pathToLogo = CSalePaySystemAction::GetParamValue('PATH_TO_LOGO', false);
	if ($pathToLogo)
	{
		list($imageHeight, $imageWidth) = $pdf->GetImageSize($pathToLogo);

		$imgDpi = intval(CSalePaySystemAction::GetParamValue('LOGO_DPI', false)) ?: 96;
		$imgZoom = 96 / $imgDpi;

		$logoHeight = $imageHeight * $imgZoom + 5;
		$logoWidth  = $imageWidth * $imgZoom + 5;

		$pdf->Image($pathToLogo, $pdf->GetX(), $pdf->GetY(), -$imgDpi, -$imgDpi);
	}
	unset($pathToLogo);

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
		$pdf->SetFont($fontFamily, 'B', $fontSize);

		$text = CSalePdf::prepareToPdf($sellerName);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX() + $textLeftMargin);
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
			$pdf->Ln();
		}
		unset($text, $string);

		$pdf->SetFont($fontFamily, '', $fontSize);
	}

	$sellerAddress = CSalePaySystemAction::GetParamValue("SELLER_ADDRESS", false);
	if($sellerAddress)
	{
		if (is_string($sellerAddress))
		{
			$sellerAddress = explode("\n", str_replace(array("\r\n", "\n", "\r"), "\n", $sellerAddress));
			if (count($sellerAddress) === 1)
				$sellerAddress = $sellerAddress[0];
		}
		if (is_array($sellerAddress))
		{
			foreach ($sellerAddress as $item)
			{
				$text = CSalePdf::prepareToPdf($item);
				while ($pdf->GetStringWidth($text))
				{
					list($string, $text) = $pdf->splitString($text, $textWidth);
					$pdf->SetX($pdf->GetX() + $textLeftMargin);
					$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
					$pdf->Ln();
				}
				unset($text, $string);
			}
			unset($item);
		}
		else
		{
			$text = CSalePdf::prepareToPdf($sellerAddress);
			while ($pdf->GetStringWidth($text))
			{
				list($string, $text) = $pdf->splitString($text, $textWidth);
				$pdf->SetX($pdf->GetX() + $textLeftMargin);
				$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
				$pdf->Ln();
			}
			unset($text, $string);
		}
	}

	$sellerPhone = CSalePaySystemAction::GetParamValue("SELLER_PHONE", false);
	if($sellerPhone)
	{
		$sellerPhone = sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_PHONE', null, $lng).": %s", $sellerPhone);
		$text = CSalePdf::prepareToPdf($sellerPhone);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX() + $textLeftMargin);
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
			$pdf->Ln();
		}
		unset($text, $string);
	}

	$sellerEmail = CSalePaySystemAction::GetParamValue("SELLER_EMAIL", false);
	if($sellerEmail)
	{
		$sellerEmail = sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_EMAIL', null, $lng).": %s", $sellerEmail);
		$text = CSalePdf::prepareToPdf($sellerEmail);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX() + $textLeftMargin);
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
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

	$pdf->SetFont($fontFamily, 'B', $fontSize);

	if (CSalePaySystemAction::GetParamValue("BUYER_NAME", false))
	{
		$pdf->Write(15, CSalePdf::prepareToPdf(CSalePaySystemAction::GetParamValue("BUYER_NAME", false)));
		$pdf->Ln();

		$pdf->SetFont($fontFamily, '', $fontSize);
		$buyerAddress = CSalePaySystemAction::GetParamValue("BUYER_ADDRESS", false);
		if($buyerAddress)
		{
			if (is_string($buyerAddress))
			{
				$buyerAddress = explode("\n", str_replace(array("\r\n", "\n", "\r"), "\n", $buyerAddress));
				if (count($buyerAddress) === 1)
					$buyerAddress = $buyerAddress[0];
			}
			if (is_array($buyerAddress))
			{
				foreach ($buyerAddress as $item)
				{
					$pdf->Write(15, CSalePdf::prepareToPdf($item));
					$pdf->Ln();
				}
				unset($item);
			}
			else
			{
				$pdf->Write(15, CSalePdf::prepareToPdf($buyerAddress));
				$pdf->Ln();
			}
		}
	}

	$textWidth = $width - 10;
	$buyerPayerName = CSalePaySystemAction::GetParamValue("BUYER_PAYER_NAME", false);
	if($buyerPayerName)
	{
		$buyerPayerName = sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_CONTACT_PERSON', null, $lng)." %s", $buyerPayerName);
		$text = CSalePdf::prepareToPdf($buyerPayerName);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX());
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
			$pdf->Ln();
		}
		unset($text, $string);
	}
	unset($buyerPayerName);

	$buyerPhone = CSalePaySystemAction::GetParamValue("BUYER_PHONE", false);
	if($buyerPhone)
	{
		$buyerPhone = sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_PHONE', null, $lng).": %s", $buyerPhone);
		$text = CSalePdf::prepareToPdf($buyerPhone);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX());
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
			$pdf->Ln();
		}
		unset($text, $string);
	}
	unset($buyerPhone);

	$buyerFax = CSalePaySystemAction::GetParamValue("BUYER_FAX", false);
	if($buyerFax)
	{
		$buyerFax = sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_FAX', null, $lng).": %s", $buyerFax);
		$text = CSalePdf::prepareToPdf($buyerFax);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX());
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
			$pdf->Ln();
		}
		unset($text, $string);
	}
	unset($buyerFax);

	$buyerEmail = CSalePaySystemAction::GetParamValue("BUYER_EMAIL", false);
	if($buyerEmail)
	{
		$buyerEmail = sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_EMAIL', null, $lng).": %s", $buyerEmail);
		$text = CSalePdf::prepareToPdf($buyerEmail);
		while ($pdf->GetStringWidth($text))
		{
			list($string, $text) = $pdf->splitString($text, $textWidth);
			$pdf->SetX($pdf->GetX());
			$pdf->Cell($textWidth, 15, $string, 0, 0, 'L');
			$pdf->Ln();
		}
		unset($text, $string);
	}
	unset($textWidth, $buyerEmail);

	$pdf->Ln();
	$pdf->Ln();


	$pdf->SetFont($fontFamily, 'B', $fontSize * 1.5);
	$pdf->Cell(0.35*$width, 15, CSalePdf::prepareToPdf(sprintf(
		Loc::getMessage('SBLP_Q_BR_TEXT_QUOTE', null, $lng).' '.Loc::getMessage('SBLP_Q_BR_TEXT_NUMBER', null, $lng).' %s',
		$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ACCOUNT_NUMBER"]
	)));

	$pdf->SetFont($fontFamily, 'B', $fontSize);
	$pdf->Cell(0, 15, CSalePdf::prepareToPdf(sprintf(
		Loc::getMessage('SBLP_Q_BR_TEXT_ISSUE_DATE', null, $lng).': %s',
		CSalePaySystemAction::GetParamValue("DATE_INSERT", false)
	)), 0, 0, 'R');
	$pdf->Ln();
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

$pdf->SetFont($fontFamily, '', $fontSize);

if (CSalePaySystemAction::GetParamValue("COMMENT1", false)
	|| CSalePaySystemAction::GetParamValue("COMMENT2", false))
{
	$pdf->Ln();
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
}
else
{
	$pdf->Ln();
}

$arBasketItems = CSalePaySystemAction::GetParamValue("BASKET_ITEMS", false);
if(!is_array($arBasketItems))
	$arBasketItems = array();

$pdf->SetFont($fontFamily, '', $fontSize);

// items list
$arCols = array();
$arCells = array();
if (!empty($arBasketItems))
{
	$arBasketItems = getMeasures($arBasketItems);

	$columnList = array('NUMBER', 'NAME', 'QUANTITY', 'MEASURE', 'PRICE', 'VAT_RATE', 'DISCOUNT', 'SUM');
	$vatRateColumn = 0;
	foreach ($columnList as $column)
	{
		if (CSalePaySystemAction::GetParamValue('QUOTE_BR_COLUMN_'.$column.'_SHOW') == 'Y')
		{
			$arCols[$column] = array(
				'NAME' => CSalePdf::prepareToPdf(CSalePaySystemAction::GetParamValue('QUOTE_BR_COLUMN_'.$column.'_TITLE')),
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
	$vat = 0;
	$vats = array();
	$arProps = array();

	foreach($arBasketItems as &$arBasket)
	{
		// @TODO: replace with real vatless price
		if (isset($arBasket['VAT_INCLUDED']) && $arBasket['VAT_INCLUDED'] === 'Y')
			$arBasket["VATLESS_PRICE"] = roundEx($arBasket["PRICE"] / (1 + $arBasket["VAT_RATE"]), SALE_VALUE_PRECISION);
		else
			$arBasket["VATLESS_PRICE"] = $arBasket["PRICE"];

		$productName = $arBasket["NAME"];
		if ($productName == "OrderDelivery")
			$productName = CSalePdf::prepareToPdf(Loc::getMessage('SBLP_Q_BR_TEXT_SHIPPING', null, $lng));
		else if ($productName == "OrderDiscount")
			$productName = CSalePdf::prepareToPdf(Loc::getMessage('SBLP_Q_BR_TEXT_DISCOUNT', null, $lng));

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
		$arCells[++$n] = array();
		foreach ($arCols as $columnId => $col)
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
					$data = CSalePdf::prepareToPdf($arBasket["MEASURE_NAME"] ? $arBasket["MEASURE_NAME"] : Loc::getMessage('SBLP_Q_BR_TEXT_PCS', null, $lng));
					$arCols[$columnId]['IS_DIGIT'] = true;
					break;
				case 'PRICE':
					$data = CSalePdf::prepareToPdf(SaleFormatCurrency($unitPrice, $arBasket['CURRENCY'], false));
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
					$data = CSalePdf::prepareToPdf(SaleFormatCurrency($arBasket["VATLESS_PRICE"] * $arBasket["QUANTITY"], $arBasket["CURRENCY"], false));
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
			foreach($arBasket["PROPS"] as $vv)
				$arProps[$n][] = CSalePdf::prepareToPdf(sprintf("%s: %s", $vv["NAME"], $vv["VALUE"]));
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

	$items = $n;

	if (CSalePaySystemAction::GetParamValue('QUOTE_BR_TOTAL_SHOW') == 'Y')
	{
		$eps = 0.0001;
		if ($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"] - $sum > $eps)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf(Loc::getMessage('SBLP_Q_BR_TEXT_SUBTOTAL', null, $lng).":");
			$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($sum, $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false));
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

				$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf(sprintf(Loc::getMessage('SBLP_Q_BR_TEXT_TAX', null, $lng)." (%s%%):", roundEx($vatRate * 100, SALE_VALUE_PRECISION)));
				$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($vatSum, $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false));
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

					$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf(sprintf(
						"%s%s%s:",
						($arTaxInfo["IS_IN_PRICE"] == "Y") ? Loc::getMessage('SBLP_Q_BR_TEXT_INCLUDED', null, $lng)." " : "",
						$arTaxInfo["TAX_NAME"],
						sprintf(' (%s%%)', roundEx($arTaxInfo["VALUE"],SALE_VALUE_PRECISION))
					));
					$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($arTaxInfo["VALUE_MONEY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false));
				}
				unset($arTaxInfo);
			}
		}

		if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"]) > 0)
		{
			$arCells[++$n] = array();
			for ($i = 0; $i < $columnCount; $i++)
				$arCells[$n][$arColumnKeys[$i]] = null;

			$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf(CSalePdf::prepareToPdf(Loc::getMessage('SBLP_Q_BR_TEXT_DISCOUNT', null, $lng).":"));
			$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false));
		}

		$arCells[++$n] = array();
		for ($i = 0; $i < $columnCount; $i++)
			$arCells[$n][$arColumnKeys[$i]] = null;

		$arCells[$n][$arColumnKeys[$columnCount-2]] = CSalePdf::prepareToPdf(Loc::getMessage('SBLP_Q_BR_TEXT_TOTAL', null, $lng).":");
		$arCells[$n][$arColumnKeys[$columnCount-1]] = CSalePdf::prepareToPdf(SaleFormatCurrency($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"], $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false));
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

	if (CSalePaySystemAction::GetParamValue('QUOTE_BR_COLUMN_NAME_SHOW') == 'Y')
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

if (CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false))
{
	$pdf->Cell(
		$width,
		15,
		$pdf->prepareToPdf(
			sprintf(
				Loc::getMessage('SBLP_Q_BR_TEXT_DUE_DATE', null, $lng).': %s',
				ConvertDateTime(CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false), FORMAT_DATE))
		),
		0,
		0,
		'L'
	);
}

$pdf->Ln();
if (!empty($userFields))
{
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
$pdf->Ln();
$pdf->Ln();

if (CSalePaySystemAction::GetParamValue('QUOTE_BR_SIGN_SHOW') == 'Y')
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
				$margin['left']+$width/2+45, null,
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
	Loc::getMessage('SBLP_Q_BR_TEXT_QUOTE', null, $lng).' '.Loc::getMessage('SBLP_Q_BR_TEXT_NUMBER', null, $lng).' %s ('.Loc::getMessage('SBLP_Q_BR_TEXT_ISSUE_DATE', null, $lng).' %s).pdf',
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

$trFileName = CUtil::translit($fileName, $lng, array('max_len' => 1024, 'safe_chars' => '.', 'replace_space' => '-'));

return $pdf->Output($trFileName, $dest, $fileName);
?>