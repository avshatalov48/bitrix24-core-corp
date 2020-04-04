<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO\Path;

$lng = 'br';
Loc::loadLanguageFile(__FILE__);
Loc::loadLanguageFile(Path::getDirectory(Path::normalize(__FILE__)).Path::DIRECTORY_SEPARATOR.'values.php', $lng);

$psTitle = Loc::getMessage("SBLP_DTITLE");
$psDescription = Loc::getMessage("SBLP_DDESCR");

$isAffordPdf = true;

$arPSCorrespondence = array(
		"DATE_INSERT" => array(
				"NAME" => Loc::getMessage("SBLP_DATE"),
				"DESCR" => Loc::getMessage("SBLP_DATE_DESC"),
				"VALUE" => "DATE_INSERT_DATE",
				"TYPE" => "ORDER",
				"GROUP" => "PAYMENT",
				"SORT" => 100
			),

		"DATE_PAY_BEFORE" => array(
				"NAME" => Loc::getMessage("SBLP_PAY_BEFORE"),
				"DESCR" => Loc::getMessage("SBLP_PAY_BEFORE_DESC"),
				"VALUE" => "DATE_PAY_BEFORE",
				"TYPE" => "ORDER",
				"GROUP" => "PAYMENT",
				"SORT" => 200
			),
		"SELLER_NAME" => array(
				"NAME" => Loc::getMessage("SBLP_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 300
			),
		"SELLER_ADDRESS" => array(
				"NAME" => Loc::getMessage("SBLP_ADRESS_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_ADRESS_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 400
			),
		"SELLER_PHONE" => array(
				"NAME" => Loc::getMessage("SBLP_PHONE_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_PHONE_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 500
			),
		"SELLER_EMAIL" => array(
				"NAME" => Loc::getMessage("SBLP_EMAIL_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_EMAIL_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 600
			),

		"SELLER_DIR_POS" => array(
				"NAME" => Loc::getMessage("SBLP_DIR_POS_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_DIR_POS_SUPPLI_DESC"),
				"VALUE" => Loc::getMessage("SBLP_Q_BR_DIR_POS_SUPPLI_VAL", null, $lng),
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1500
			),
		"SELLER_ACC_POS" => array(
				"NAME" => Loc::getMessage("SBLP_ACC_POS_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_ACC_POS_SUPPLI_DESC"),
				"VALUE" => Loc::getMessage("SBLP_Q_BR_ACC_POS_SUPPLI_VAL", null, $lng),
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1600
			),
		"SELLER_DIR" => array(
				"NAME" => Loc::getMessage("SBLP_DIR_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_DIR_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1700
			),
		"SELLER_ACC" => array(
				"NAME" => Loc::getMessage("SBLP_ACC_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_ACC_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1800
			),

		"BUYER_NAME" => array(
				"NAME" => Loc::getMessage("SBLP_CUSTOMER"),
				"DESCR" => Loc::getMessage("SBLP_CUSTOMER_DESC"),
				"VALUE" => "COMPANY_NAME",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2000
			),
		"BUYER_ADDRESS" => array(
				"NAME" => Loc::getMessage("SBLP_CUSTOMER_ADRES"),
				"DESCR" => Loc::getMessage("SBLP_CUSTOMER_ADRES_DESC"),
				"VALUE" => "ADDRESS",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2100
			),
		"BUYER_PAYER_NAME" => array(
				"NAME" => Loc::getMessage("SBLP_CUSTOMER_PERSON"),
				"DESCR" => Loc::getMessage("SBLP_CUSTOMER_PERSON_DESC"),
				"VALUE" => "PAYER_NAME",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2200
			),
		"BUYER_PHONE" => array(
				"NAME" => Loc::getMessage("SBLP_CUSTOMER_PHONE"),
				"DESCR" => Loc::getMessage("SBLP_CUSTOMER_PHONE_DESC"),
				"VALUE" => "PHONE",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2300
			),
		"BUYER_FAX" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_FAX"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_FAX_DESC"),
				"VALUE" => "FAX",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2350
			),
		"BUYER_EMAIL" => array(
				"NAME" => Loc::getMessage("SBLP_CUSTOMER_EMAIL"),
				"DESCR" => Loc::getMessage("SBLP_CUSTOMER_EMAIL_DESC"),
				"VALUE" => "EMAIL",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2400
			),
		"COMMENT1" => array(
				"NAME" => Loc::getMessage("SBLP_COMMENT1"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2500
			),
		"COMMENT2" => array(
				"NAME" => Loc::getMessage("SBLP_COMMENT2"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2600
			),
		"USER_FIELD_1" => array(
				"NAME" => Loc::getMessage("SBLP_USERFIELD1"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2700
			),
		"USER_FIELD_2" => array(
				"NAME" => Loc::getMessage("SBLP_USERFIELD2"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2800
			),
		"USER_FIELD_3" => array(
				"NAME" => Loc::getMessage("SBLP_USERFIELD3"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2900
			),
		"USER_FIELD_4" => array(
				"NAME" => Loc::getMessage("SBLP_USERFIELD4"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 3000
			),
		"USER_FIELD_5" => array(
				"NAME" => Loc::getMessage("SBLP_USERFIELD5"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 3100
			),
		"PATH_TO_LOGO" => array(
				"NAME" => Loc::getMessage("SBLP_LOGO"),
				"DESCR" => Loc::getMessage("SBLP_LOGO_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3200
			),
		"LOGO_DPI" => array(
				"NAME" => Loc::getMessage("SBLP_LOGO_DPI"),
				"DESCR" => "",
				"VALUE" => array(
					'96' => array('NAME' => Loc::getMessage("SBLP_LOGO_DPI_96")),
					'600' => array('NAME' => Loc::getMessage("SBLP_LOGO_DPI_600")),
					'300' => array('NAME' => Loc::getMessage("SBLP_LOGO_DPI_300")),
					'150' => array('NAME' => Loc::getMessage("SBLP_LOGO_DPI_150")),
					'72' => array('NAME' => Loc::getMessage("SBLP_LOGO_DPI_72"))
				),
				"TYPE" => "SELECT",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3300
			),
		"PATH_TO_STAMP" => array(
				"NAME" => Loc::getMessage("SBLP_PRINT"),
				"DESCR" => Loc::getMessage("SBLP_PRINT_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3400
			),
		"SELLER_DIR_SIGN" => array(
				"NAME" => Loc::getMessage("SBLP_DIR_SIGN_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_DIR_SIGN_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3500
			),
		"SELLER_ACC_SIGN" => array(
				"NAME" => Loc::getMessage("SBLP_ACC_SIGN_SUPPLI"),
				"DESCR" => Loc::getMessage("SBLP_ACC_SIGN_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3600
			),
		"BACKGROUND" => array(
				"NAME" => Loc::getMessage("SBLP_BACKGROUND"),
				"DESCR" => Loc::getMessage("SBLP_BACKGROUND_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "VISUAL_SETTINGS",
				"SORT" => 3700
			),
		"BACKGROUND_STYLE" => array(
				"NAME" => Loc::getMessage("SBLP_BACKGROUND_STYLE"),
				"DESCR" => "",
				"VALUE" => array(
					'none' => array('NAME' => Loc::getMessage("SBLP_BACKGROUND_STYLE_NONE")),
					'tile' => array('NAME' => Loc::getMessage("SBLP_BACKGROUND_STYLE_TILE")),
					'stretch' => array('NAME' => Loc::getMessage("SBLP_BACKGROUND_STYLE_STRETCH"))
				),
				"TYPE" => "SELECT",
				"GROUP" => "VISUAL_SETTINGS",
				"SORT" => 3800
			),
		"MARGIN_TOP" => array(
				"NAME" => Loc::getMessage("SBLP_MARGIN_TOP"),
				"DESCR" => "",
				"VALUE" => "15",
				"TYPE" => "",
				"GROUP" => "VISUAL_SETTINGS",
				"SORT" => 3900
			),
		"MARGIN_RIGHT" => array(
				"NAME" => Loc::getMessage("SBLP_MARGIN_RIGHT"),
				"DESCR" => "",
				"VALUE" => "15",
				"TYPE" => "",
				"GROUP" => "VISUAL_SETTINGS",
				"SORT" => 4000
			),
		"MARGIN_BOTTOM" => array(
				"NAME" => Loc::getMessage("SBLP_MARGIN_BOTTOM"),
				"DESCR" => "",
				"VALUE" => "15",
				"TYPE" => "",
				"GROUP" => "VISUAL_SETTINGS",
				"SORT" => 4100
			),
		"MARGIN_LEFT" => array(
				"NAME" => Loc::getMessage("SBLP_MARGIN_LEFT"),
				"DESCR" => "",
				"VALUE" => "20",
				"TYPE" => "",
				"GROUP" => "VISUAL_SETTINGS",
				"SORT" => 4200
			),
		"QUOTE_BR_HEADER_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_HEADER_SHOW"),
				"DESCR" => "",
				"VALUE" => 'Y',
				"GROUP" => 'GENERAL_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 4300
			),
		"QUOTE_BR_TOTAL_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_TOTAL_SHOW"),
				"DESCR" => "",
				"VALUE" => 'Y',
				"GROUP" => 'GENERAL_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 4400
			),
		"QUOTE_BR_SIGN_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_SIGN_SHOW"),
				"DESCR" => "",
				"VALUE" => 'Y',
				"GROUP" => 'SELLER_COMPANY',
				"TYPE" => "CHECKBOX",
				"SORT" => 4500
			),
		"QUOTE_BR_COLUMN_NUMBER_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_NUMBER_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_NUMBER_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 4600
			),
		"QUOTE_BR_COLUMN_NUMBER_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 100,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 4700
			),
		"QUOTE_BR_COLUMN_NUMBER_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 4800
			),
		"QUOTE_BR_COLUMN_NAME_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_NAME_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_NAME_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 4900
			),
		"QUOTE_BR_COLUMN_NAME_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 200,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5000
			),
		"QUOTE_BR_COLUMN_NAME_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 5100
			),
		"QUOTE_BR_COLUMN_QUANTITY_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_QUANTITY_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_QUANTITY_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5200
			),
		"QUOTE_BR_COLUMN_QUANTITY_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 300,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5300
			),
		"QUOTE_BR_COLUMN_QUANTITY_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 5400
			),
		"QUOTE_BR_COLUMN_MEASURE_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_MEASURE_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_MEASURE_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5500
			),
		"QUOTE_BR_COLUMN_MEASURE_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 400,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5600
			),
		"QUOTE_BR_COLUMN_MEASURE_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 5700
			),
		"QUOTE_BR_COLUMN_PRICE_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_PRICE_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_PRICE_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5800
			),
		"QUOTE_BR_COLUMN_PRICE_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 500,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5900
			),
		"QUOTE_BR_COLUMN_PRICE_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 6000
			),
		"QUOTE_BR_COLUMN_VAT_RATE_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_VAT_RATE_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_VAT_RATE_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6100
			),
		"QUOTE_BR_COLUMN_VAT_RATE_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 600,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6200
			),
		"QUOTE_BR_COLUMN_VAT_RATE_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 6300
			),
		"QUOTE_BR_COLUMN_DISCOUNT_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_DISCOUNT_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_DISCOUNT_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6400
			),
		"QUOTE_BR_COLUMN_DISCOUNT_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 700,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6500
			),
		"QUOTE_BR_COLUMN_DISCOUNT_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 6600
			),
		"QUOTE_BR_COLUMN_SUM_TITLE" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_TITLE").'"'.Loc::getMessage("SBLP_Q_BR_COLUMN_SUM_VALUE", null, $lng).'"',
				"DESCR" => "",
				"VALUE" => Loc::getMessage("SBLP_Q_BR_COLUMN_SUM_VALUE", null, $lng),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6700
			),
		"QUOTE_BR_COLUMN_SUM_SORT" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 800,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6800
			),
		"QUOTE_BR_COLUMN_SUM_SHOW" => array(
				"NAME" => Loc::getMessage("SBLP_Q_BR_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 6900
			)
	);
?>