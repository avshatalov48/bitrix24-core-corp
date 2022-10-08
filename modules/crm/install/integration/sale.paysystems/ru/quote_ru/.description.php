<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?

global $MESS;

$langFile = GetLangFileName(__DIR__."/", "/quote.php");

if(file_exists($langFile))
	include($langFile);

$psTitle = GetMessage("SBLP_DTITLE");
$psDescription = GetMessage("SBLP_DDESCR");

$isAffordPdf = true;

$arPSCorrespondence = array(
		"DATE_INSERT" => array(
				"NAME" => GetMessage("SBLP_DATE"),
				"DESCR" => GetMessage("SBLP_DATE_DESC"),
				"VALUE" => "DATE_INSERT_DATE",
				"TYPE" => "ORDER",
				"GROUP" => "PAYMENT",
				"SORT" => 100
			),

		"ORDER_SUBJECT" => array(
				"NAME" => GetMessage("SBLP_ORDER_SUBJECT"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "PAYMENT",
				"SORT" => 200
			),
		"DATE_PAY_BEFORE" => array(
				"NAME" => GetMessage("SBLP_PAY_BEFORE"),
				"DESCR" => GetMessage("SBLP_PAY_BEFORE_DESC"),
				"VALUE" => "DATE_PAY_BEFORE",
				"TYPE" => "ORDER",
				"GROUP" => "PAYMENT",
				"SORT" => 300
			),
		"SELLER_NAME" => array(
				"NAME" => GetMessage("SBLP_SUPPLI"),
				"DESCR" => GetMessage("SBLP_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 400
			),
		"SELLER_ADDRESS" => array(
				"NAME" => GetMessage("SBLP_ADRESS_SUPPLI"),
				"DESCR" => GetMessage("SBLP_ADRESS_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 500
			),
		"SELLER_PHONE" => array(
				"NAME" => GetMessage("SBLP_PHONE_SUPPLI"),
				"DESCR" => GetMessage("SBLP_PHONE_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 600
			),
		"SELLER_EMAIL" => array(
				"NAME" => GetMessage("SBLP_EMAIL_SUPPLI"),
				"DESCR" => GetMessage("SBLP_EMAIL_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 600
			),
		"SELLER_INN" => array(
				"NAME" => GetMessage("SBLP_INN_SUPPLI"),
				"DESCR" => GetMessage("SBLP_INN_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 700
			),
		"SELLER_KPP" => array(
				"NAME" => GetMessage("SBLP_KPP_SUPPLI"),
				"DESCR" => GetMessage("SBLP_KPP_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 800
			),
		"SELLER_RS" => array(
				"NAME" => GetMessage("SBLP_ORDER_SUPPLI"),
				"DESCR" => GetMessage("SBLP_ORDER_SUPPLI_DESC"),
				"VALUE" => GetMessage("SBLP_ORDER_SUPPLI_VAL"),
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 900
			),
		"SELLER_BANK" => array(
				"NAME" => GetMessage("SBLP_BANK_SUPPLI"),
				"DESCR" => GetMessage("SBLP_BANK_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1000
			),
		"SELLER_BCITY" => array(
				"NAME" => GetMessage("SBLP_BCITY_SUPPLI"),
				"DESCR" => GetMessage("SBLP_BCITY_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1100
			),
		"SELLER_KS" => array(
				"NAME" => GetMessage("SBLP_KORORDER_SUPPLI"),
				"DESCR" => GetMessage("SBLP_KORORDER_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1200
			),
		"SELLER_BIK" => array(
				"NAME" => GetMessage("SBLP_BIK_SUPPLI"),
				"DESCR" => GetMessage("SBLP_BIK_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1300
			),
		"SELLER_DIR_POS" => array(
				"NAME" => GetMessage("SBLP_DIR_POS_SUPPLI"),
				"DESCR" => GetMessage("SBLP_DIR_POS_SUPPLI_DESC"),
				"VALUE" => GetMessage("SBLP_DIR_POS_SUPPLI_VAL"),
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1400
			),
		"SELLER_ACC_POS" => array(
				"NAME" => GetMessage("SBLP_ACC_POS_SUPPLI"),
				"DESCR" => GetMessage("SBLP_ACC_POS_SUPPLI_DESC"),
				"VALUE" => GetMessage("SBLP_ACC_POS_SUPPLI_VAL"),
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1500
			),
		"SELLER_DIR" => array(
				"NAME" => GetMessage("SBLP_DIR_SUPPLI"),
				"DESCR" => GetMessage("SBLP_DIR_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1600
			),
		"SELLER_ACC" => array(
				"NAME" => GetMessage("SBLP_ACC_SUPPLI"),
				"DESCR" => GetMessage("SBLP_ACC_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 1700
			),
		"BUYER_NAME" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_DESC"),
				"VALUE" => "COMPANY_NAME",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 1800
			),
		"BUYER_INN" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_INN"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_INN_DESC"),
				"VALUE" => "INN",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 1900
			),
		"BUYER_ADDRESS" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_ADRES"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_ADRES_DESC"),
				"VALUE" => "ADDRESS",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2000
			),
		"BUYER_PHONE" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_PHONE"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_PHONE_DESC"),
				"VALUE" => "PHONE",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2100
			),
		"BUYER_FAX" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_FAX"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_FAX_DESC"),
				"VALUE" => "FAX",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2200
			),
		"BUYER_EMAIL" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_EMAIL"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_EMAIL_DESC"),
				"VALUE" => "EMAIL",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2250
			),
		"BUYER_PAYER_NAME" => array(
				"NAME" => GetMessage("SBLP_CUSTOMER_PERSON"),
				"DESCR" => GetMessage("SBLP_CUSTOMER_PERSON_DESC"),
				"VALUE" => "PAYER_NAME",
				"TYPE" => "PROPERTY",
				"GROUP" => "BUYER_PERSON_COMPANY",
				"SORT" => 2300
			),
		"COMMENT1" => array(
				"NAME" => GetMessage("SBLP_COMMENT1"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2400
			),
		"COMMENT2" => array(
				"NAME" => GetMessage("SBLP_COMMENT2"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2500
			),
		"USER_FIELD_1" => array(
				"NAME" => GetMessage("SBLP_USERFIELD1"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2600
			),
		"USER_FIELD_2" => array(
				"NAME" => GetMessage("SBLP_USERFIELD2"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2700
			),
		"USER_FIELD_3" => array(
				"NAME" => GetMessage("SBLP_USERFIELD3"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2800
			),
		"USER_FIELD_4" => array(
				"NAME" => GetMessage("SBLP_USERFIELD4"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 2900
			),
		"USER_FIELD_5" => array(
				"NAME" => GetMessage("SBLP_USERFIELD5"),
				"DESCR" => "",
				"VALUE" => "",
				"TYPE" => "",
				"GROUP" => "GENERAL_SETTINGS",
				"SORT" => 3000
			),
		"PATH_TO_LOGO" => array(
				"NAME" => GetMessage("SBLP_LOGO"),
				"DESCR" => GetMessage("SBLP_LOGO_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3100
			),
		"LOGO_DPI" => array(
				"NAME" => GetMessage("SBLP_LOGO_DPI"),
				"DESCR" => "",
				"VALUE" => array(
					'96' => array('NAME' => GetMessage("SBLP_LOGO_DPI_96")),
					'600' => array('NAME' => GetMessage("SBLP_LOGO_DPI_600")),
					'300' => array('NAME' => GetMessage("SBLP_LOGO_DPI_300")),
					'150' => array('NAME' => GetMessage("SBLP_LOGO_DPI_150")),
					'72' => array('NAME' => GetMessage("SBLP_LOGO_DPI_72"))
				),
				"TYPE" => "SELECT",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3200
			),
		"PATH_TO_STAMP" => array(
				"NAME" => GetMessage("SBLP_PRINT"),
				"DESCR" => GetMessage("SBLP_PRINT_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3300
			),
		"SELLER_DIR_SIGN" => array(
				"NAME" => GetMessage("SBLP_DIR_SIGN_SUPPLI"),
				"DESCR" => GetMessage("SBLP_DIR_SIGN_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3400
			),
		"SELLER_ACC_SIGN" => array(
				"NAME" => GetMessage("SBLP_ACC_SIGN_SUPPLI"),
				"DESCR" => GetMessage("SBLP_ACC_SIGN_SUPPLI_DESC"),
				"VALUE" => "",
				"TYPE" => "FILE",
				"GROUP" => "SELLER_COMPANY",
				"SORT" => 3500
			),
		"BACKGROUND" => array(
				"NAME" => GetMessage("SBLP_BACKGROUND"),
				"DESCR" => GetMessage("SBLP_BACKGROUND_DESC"),
				"VALUE" => "",
				"GROUP" => 'VISUAL_SETTINGS',
				"TYPE" => "FILE",
				"SORT" => 3600
			),
		"BACKGROUND_STYLE" => array(
				"NAME" => GetMessage("SBLP_BACKGROUND_STYLE"),
				"DESCR" => "",
				"GROUP" => 'VISUAL_SETTINGS',
				"VALUE" => array(
					'none' => array('NAME' => GetMessage("SBLP_BACKGROUND_STYLE_NONE")),
					'tile' => array('NAME' => GetMessage("SBLP_BACKGROUND_STYLE_TILE")),
					'stretch' => array('NAME' => GetMessage("SBLP_BACKGROUND_STYLE_STRETCH"))
				),
				"TYPE" => "SELECT",
				"SORT" => 3700
			),
		"MARGIN_TOP" => array(
				"NAME" => GetMessage("SBLP_MARGIN_TOP"),
				"DESCR" => "",
				"VALUE" => "15",
				"GROUP" => 'VISUAL_SETTINGS',
				"TYPE" => "",
				"SORT" => 3800
			),
		"MARGIN_RIGHT" => array(
				"NAME" => GetMessage("SBLP_MARGIN_RIGHT"),
				"DESCR" => "",
				"VALUE" => "15",
				"GROUP" => 'VISUAL_SETTINGS',
				"TYPE" => "",
				"SORT" => 3900
			),
		"MARGIN_BOTTOM" => array(
				"NAME" => GetMessage("SBLP_MARGIN_BOTTOM"),
				"DESCR" => "",
				"VALUE" => "15",
				"GROUP" => 'VISUAL_SETTINGS',
				"TYPE" => "",
				"SORT" => 4000
			),
		"MARGIN_LEFT" => array(
				"NAME" => GetMessage("SBLP_MARGIN_LEFT"),
				"DESCR" => "",
				"VALUE" => "20",
				"TYPE" => "",
				"GROUP" => 'GENERAL_SETTINGS',
				"SORT" => 4100
			),
		"QUOTE_HEADER_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_HEADER_SHOW"),
				"DESCR" => "",
				"VALUE" => 'Y',
				"GROUP" => 'GENERAL_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 4200
			),
		"QUOTE_TOTAL_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_TOTAL_SHOW"),
				"DESCR" => "",
				"VALUE" => 'Y',
				"GROUP" => 'GENERAL_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 4300
			),
		"QUOTE_SIGN_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_SIGN_SHOW"),
				"DESCR" => "",
				"VALUE" => 'Y',
				"GROUP" => 'SELLER_COMPANY',
				"TYPE" => "CHECKBOX",
				"SORT" => 4400
			),
		"QUOTE_COLUMN_NUMBER_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_NUMBER_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_NUMBER_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 4500
			),
		"QUOTE_COLUMN_NUMBER_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 100,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 4600
			),
		"QUOTE_COLUMN_NUMBER_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 4700
			),
		"QUOTE_COLUMN_NAME_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_NAME_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_NAME_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 4800
			),
		"QUOTE_COLUMN_NAME_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 200,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 4900
			),
		"QUOTE_COLUMN_NAME_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 5000
			),
		"QUOTE_COLUMN_QUANTITY_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_QUANTITY_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_QUANTITY_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5100
			),
		"QUOTE_COLUMN_QUANTITY_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 300,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5200
			),
		"QUOTE_COLUMN_QUANTITY_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 5300
			),
		"QUOTE_COLUMN_MEASURE_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_MEASURE_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_MEASURE_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5400
			),
		"QUOTE_COLUMN_MEASURE_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 400,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5500
			),
		"QUOTE_COLUMN_MEASURE_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 5600
			),
		"QUOTE_COLUMN_PRICE_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_PRICE_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_PRICE_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5700
			),
		"QUOTE_COLUMN_PRICE_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 500,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 5800
			),
		"QUOTE_COLUMN_PRICE_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 5900
			),
		"QUOTE_COLUMN_VAT_RATE_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_VAT_RATE_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_VAT_RATE_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6000
			),
		"QUOTE_COLUMN_VAT_RATE_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 600,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6100
			),
		"QUOTE_COLUMN_VAT_RATE_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 6200
			),
		"QUOTE_COLUMN_DISCOUNT_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_DISCOUNT_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_DISCOUNT_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6300
			),
		"QUOTE_COLUMN_DISCOUNT_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 700,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6400
			),
		"QUOTE_COLUMN_DISCOUNT_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 6500
			),
		"QUOTE_COLUMN_SUM_TITLE" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SUM_TITLE"),
				"DESCR" => "",
				"VALUE" => GetMessage("SBLP_Q_RU_COLUMN_SUM_VALUE"),
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6700
			),
		"QUOTE_COLUMN_SUM_SORT" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SORT"),
				"DESCR" => "",
				"VALUE" => 800,
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "",
				"SORT" => 6800
			),
		"QUOTE_COLUMN_SUM_SHOW" => array(
				"NAME" => GetMessage("SBLP_Q_RU_COLUMN_SHOW"),
				"DESCR" => "",
				"VALUE" => "Y",
				"GROUP" => 'COLUMN_SETTINGS',
				"TYPE" => "CHECKBOX",
				"SORT" => 6900
			)
	);
?>