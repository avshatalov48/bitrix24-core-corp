<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Order\Import\Instagram;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

class CrmOrderConnectorInstagramView extends CBitrixComponent
	implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $pageId = 'page_fbinst_store';

	/** @var [] */
	protected $status;

	protected $messages = [];
	protected $errorCollection;

	private static $symbolsRegexp = '[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]';

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return [];
	}

	public function onPrepareComponentParams($params)
	{
		$params['IMPORT_MEDIA_STEP'] = isset($params['IMPORT_MEDIA_STEP']) && (int)$params['IMPORT_MEDIA_STEP'] > 0
			? (int)$params['IMPORT_MEDIA_STEP']
			: 5;

		return $params;
	}

	protected function listKeysSignedParameters()
	{
		return ['IFRAME', 'IMPORT_MEDIA_STEP'];
	}

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		$state = true;

		if (!Loader::includeModule('crm'))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIV_MODULE_NOT_INSTALLED_CRM'));
			$state = false;
		}

		if (!Loader::includeModule('catalog'))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIV_MODULE_NOT_INSTALLED_CATALOG'));
			$state = false;
		}

		return $state;
	}

	protected function initialization()
	{
		$this->status = Instagram::getStatus();

		$this->arResult['STATUS'] = $this->status['STATUS'];
		$this->arResult['ACTIVE_STATUS'] = $this->status['ACTIVE'];
		$this->arResult['CONNECTION_STATUS'] = $this->status['CONNECTION'];
		$this->arResult['REGISTER_STATUS'] = $this->status['REGISTER'];

		$this->arResult['PAGE'] = $this->request->get($this->pageId);
	}

	protected function setStatus($status)
	{
		$status = (bool)$status;

		$this->arResult['STATUS'] = $status;

		$this->status['CONNECTION'] = $status;
		$this->arResult['CONNECTION_STATUS'] = $status;

		$this->status['REGISTER'] = $status;
		$this->arResult['REGISTER_STATUS'] = $status;

		Instagram::setStatus($this->status);
	}

	protected function getRedirectUri()
	{
		$uri = new Uri(Instagram::getCurrentUri().$this->arParams['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL']);
		$uri->addParams([
			'reload' => 'Y',
			'ajaxid' => $this->arParams['AJAX_ID'],
			$this->pageId => 'simple_form',
		]);

		return urlencode($uri->getUri());
	}

	public function obtainForm()
	{
		$this->arResult['FORM']['STEP'] = 1;

		if ($this->arResult['ACTIVE_STATUS'])
		{
			// Reset cache
			if (!empty($this->arResult['PAGE']))
			{
				Instagram::cleanAuthCache();
			}

			$this->arResult['FORM'] = Instagram::getConnection();
			$this->arResult['FORM']['USER']['URI'] .= $this->getRedirectUri();

			if (empty($this->arResult['FORM']['ERRORS']))
			{
				if (!empty($this->arResult['FORM']['PAGE']))
				{
					$this->arResult['FORM']['STEP'] = 3;

					$this->setStatus(true);
				}
				elseif (!empty($this->arResult['FORM']['PAGES']))
				{
					$this->arResult['FORM']['STEP'] = 2;

					$this->setStatus(false);
				}
				elseif (!empty($this->arResult['FORM']['USER']))
				{
					$this->arResult['FORM']['STEP'] = 1;

					$this->setStatus(false);
				}

				if (!empty($this->arResult['FORM']['GROUP_DEL']))
				{
					$this->errorCollection[] = new Error(
						Loc::getMessage('CRM_OIIV_FACEBOOK_REMOVED_REFERENCE_TO_PAGE')
					);
				}
			}
			else
			{
				LocalRedirect($this->arParams['PATH_TO_CONNECTOR_INSTAGRAM_EDIT_FULL']);
			}
		}

		$this->arResult['CONNECTOR'] = Instagram::getConnectorName();
	}

	protected function isImportAvailable()
	{
		return Instagram::isAvailable();
	}

	private function getGridId()
	{
		return 'crm-order-import-instagram';
	}

	private function getGridOptions()
	{
		static $gridOptions = null;

		if ($gridOptions === null)
		{
			$gridOptions = new \CGridOptions($this->getGridId());
		}

		return $gridOptions;
	}

	public function getGridOptionsSorting()
	{
		$gridSort = $this->getGridOptions()->getSorting([
			'sort' => $this->getDefaultSorting(),
			'vars' => ['by' => 'by', 'order' => 'order'],
		]);

		return [$gridSort['sort'], $gridSort['vars']];
	}

	public function getDefaultSorting()
	{
		return ['UPDATE_TIME' => 'DESC'];
	}

	protected function getGridHeaders()
	{
		$defaultColumns = [];

		return [
			[
				'id' => 'ID',
				'name' => 'ID',
				'sort' => false,
				'default' => isset($defaultColumns['ID']),
			],
			[
				'id' => 'CAPTION',
				'name' => Loc::getMessage('CRM_OIIV_COLUMN_CAPTION'),
				'sort' => false,
				'default' => isset($defaultColumns['CAPTION']),
			],
			[
				'id' => 'MEDIA_TYPE',
				'name' => Loc::getMessage('CRM_OIIV_COLUMN_MEDIA_TYPE'),
				'sort' => false,
				'default' => isset($defaultColumns['MEDIA_TYPE']),
			],
			[
				'id' => 'PERMALINK',
				'name' => Loc::getMessage('CRM_OIIV_COLUMN_PERMALINK'),
				'sort' => false,
				'default' => isset($defaultColumns['PERMALINK']),
			],
			[
				'id' => 'TIMESTAMP',
				'name' => Loc::getMessage('CRM_OIIV_COLUMN_TIMESTAMP'),
				'sort' => false,
				'default' => isset($defaultColumns['TIMESTAMP']),
			],
		];
	}

	public static function specializeCharsArray($data)
	{
		if (is_array($data))
		{
			$specialized = [];

			foreach ($data as $itemKey => $item)
			{
				$specialized[mb_strtoupper($itemKey)] = static::specializeCharsArray($item);
			}
		}
		else
		{
			// $specialized = htmlspecialcharsbx($data);
			$specialized = $data;
		}

		return $specialized;
	}

	protected function getGridRowColumns($media)
	{
		$columns = static::specializeCharsArray($media);

		switch ($columns['MEDIA_TYPE'])
		{
			case Instagram::MEDIA_TYPE_VIDEO:
				$columns['IMAGES'][] = $columns['THUMBNAIL_URL'];
				break;

			case Instagram::MEDIA_TYPE_CAROUSEL_ALBUM:
				$children = isset($columns['CHILDREN']['DATA']) ? $columns['CHILDREN']['DATA'] : $columns['CHILDREN'];

				if (!empty($children))
				{
					foreach ($children as $child)
					{
						if ($child['MEDIA_TYPE'] === 'VIDEO')
						{
							$columns['IMAGES'][] = $child['THUMBNAIL_URL'];
						}
						else
						{
							$columns['IMAGES'][] = $child['MEDIA_URL'];
						}
					}
				}

				break;

			case Instagram::MEDIA_TYPE_IMAGE:
			default:
				$columns['IMAGES'][] = $columns['MEDIA_URL'];
				break;

		}

		// $media['NAME'] = "<a href=\"{$linkToDetail}\">{$media['NAME']}</a>";

		return $columns;
	}

	protected static function getPriceRegexp()
	{
		static $priceRegexp = null;

		if ($priceRegexp === null)
		{
			$currencyId = \CCrmCurrency::GetBaseCurrencyID();
			$currencyFormat = \CCurrencyLang::GetFormatDescription($currencyId);

			$priceRegexp = \CCurrencyLang::applyTemplate(
				'(\d+(\.|\,|\s){1})*\d+',
				mb_strtolower($currencyFormat['FORMAT_STRING'])
			);
			$priceRegexp = '/' . str_replace('/', '\/', $priceRegexp) . '/';
		}

		return $priceRegexp;
	}

	protected static function tryToParsePrice($caption)
	{
		if (!is_string($caption) || $caption === '')
		{
			return null;
		}

		$price = null;

		preg_match(static::getPriceRegexp(), mb_strtolower($caption), $matches);

		if (isset($matches[0]))
		{
			$price = (float)str_replace(' ', '', $matches[0]);
		}

		return $price;
	}

	protected function checkJsonEncoding(string $string): bool
	{
		try
		{
			Json::encode($string);
		}
		catch (\Exception $exception)
		{
			return false;
		}

		return true;
	}

	protected function getGridTileRow($media, $row)
	{
		$mediaCaption = $media['CAPTION'];
		$lines = explode("\n", $mediaCaption);
		$lines = array_values(array_filter($lines));

		$name = (string)$lines[0];
		$name = trim((string)preg_replace(static::getPriceRegexp(), '', $name));
		$name = mb_strlen($name) > 150 ? mb_substr($name, 0, 150).'...' : $name;

		if ($name === '' || !$this->checkJsonEncoding($name))
		{
			$name = Loc::getMessage('CRM_OIIV_DEFAULT_PRODUCT_NAME');
		}

		$caption = isset($lines[1]) ? join("\n", array_slice($lines, 1)) : $mediaCaption;
		$caption = trim($caption);

		if ($caption && !$this->checkJsonEncoding($caption))
		{
			$caption = '';
		}

		$price = static::tryToParsePrice($mediaCaption);

		return [
			'id' => $media['ID'],
			'name' => $name,
			'caption' => $caption,
			'new' => $media['NEW'],
			'imported' => $media['IMPORTED'],
			'currency' => $media['CURRENCY'],
			'price' => $price,
			'permalink' => $media['PERMALINK'],
			'images' => $media['IMAGES'],
			'mediaType' => $media['MEDIA_TYPE'],
			'sourceData' => $row,
			'isDraggable' => false,
			'isDroppable' => false,
			'componentName' => $this->getName(),
			'signedParameters' => $this->getSignedParameters(),
		];
	}

	protected function getGridData()
	{
		$grid = [
			'ID' => $this->getGridId(),
			'MODE' => 'tile',
			'SORT_MODE' => 'ord',
			'VIEW_SIZE' => 'm',
			'HEADERS' => $this->getGridHeaders(),
		];

		[$grid['SORT'], $grid['SORT_VARS']] = $this->getGridOptionsSorting();

		$currency = \CCrmCurrency::GetBaseCurrencyID();

		$grid['ROWS'] = [];
		$grid['TILE_ITEMS'] = [];

		$media = $this->getFilteredMedia($this->arResult['MEDIA']);

		foreach ($media as $item)
		{
			$item['currency'] = $currency;

			$itemColumns = $this->getGridRowColumns($item);

			$grid['ROWS'][] = [
				'id' => $item['id'],
				'data' => $item,
				'columns' => $itemColumns,
				'actions' => [],
			];
			$grid['TILE_ITEMS'][] = $this->getGridTileRow($itemColumns, $item);
		}

		$grid['NAV_OBJECT'] = new \Bitrix\Main\UI\PageNavigation('nav-'.$this->getGridId());

		return $grid;
	}

	protected function markNewItems(array &$media)
	{
		foreach ($media as &$item)
		{
			$item['new'] = strtotime($item['timestamp']) > $this->arResult['LAST_VIEWED_TIMESTAMP'];
		}
	}

	protected function markImportedItems(array &$media)
	{
		$mediaIds = array_column($media, 'id');
		$importedIds = Instagram::getImportedMedias($mediaIds);
		$importedIds = array_fill_keys($importedIds, true);

		foreach ($media as &$item)
		{
			$item['imported'] = isset($importedIds[$item['id']]);
		}
	}

	protected function removeUnnecessarySymbols(array &$media)
	{
		foreach ($media as &$item)
		{
			$item['caption'] = preg_replace('/'.self::$symbolsRegexp.'/u', '', $item['caption']);
		}
	}

	protected function getFilteredMedia($media)
	{
		$filter = $this->getFilter();

		if (empty($filter))
		{
			return $media;
		}

		return array_filter($media, function ($media) use ($filter) {
			foreach ($filter as $key => $values)
			{
				$success = false;

				foreach ($values as $value)
				{
					if (isset($media[$key]))
					{
						if ($key === 'timestamp')
						{
							$createTime = strtotime($media[$key]);
							$timestampSuccess = isset($value['from']) || isset($value['to']);

							if (isset($value['from']) && $value['from'] >= $createTime)
							{
								$timestampSuccess = false;
							}

							if (isset($value['to']) && $value['to'] <= $createTime)
							{
								$timestampSuccess = false;
							}

							if ($timestampSuccess)
							{
								$success = true;
								break;
							}
						}
						elseif (is_array($value))
						{
							if (!empty($value) && in_array($media[$key], $value, true))
							{
								$success = true;
								break;
							}
						}
						elseif (is_string($value))
						{
							if ($key === 'permalink')
							{
								$value = (new Uri($value))->getPath();
							}

							$value = mb_strtolower($value);
							$media[$key] = mb_strtolower((string)$media[$key]);

							if ($value && $media[$key] && mb_strpos($media[$key], $value) !== false)
							{
								$success = true;
								break;
							}
						}
						elseif ($value === $media[$key])
						{
							$success = true;
							break;
						}
					}
				}

				if (!$success)
				{
					return false;
				}
			}

			return true;
		});
	}

	protected function getFilter()
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->getGridId());

		if ($this->request->getRequestMethod() === 'GET')
		{
			$currentFilterId = $filterOptions->getCurrentFilterId();

			if ($this->request->get('show_new') === 'y')
			{
				$filterSettings = $filterOptions->getFilterSettings('recent');
				$filterOptions->setFilterSettings('recent', $filterSettings, true, false);
			}
			elseif (
				$currentFilterId === 'tmp_filter'
				|| $currentFilterId === 'default_filter'
				|| $currentFilterId === 'recent'
			)
			{
				$filterSettings = $filterOptions->getFilterSettings('not_imported');
				$filterOptions->setFilterSettings('not_imported', $filterSettings, true, false);
			}
		}

		$requestFilter = $filterOptions->getFilter($this->getGridFilter()['FILTER']);
		$searchString = $filterOptions->getSearchString();

		$filter = [];

		if (!empty($requestFilter['CAPTION']))
		{
			$filter['caption'][] = $requestFilter['CAPTION'];
		}

		if (!empty($requestFilter['IMPORTED']))
		{
			$filter['imported'][] = $requestFilter['IMPORTED'] === 'Y';
		}

		if (!empty($requestFilter['NEW']))
		{
			$filter['new'][] = $requestFilter['NEW'] === 'Y';
		}

		if (!empty($requestFilter['MEDIA_TYPE']))
		{
			$filter['media_type'][] = $requestFilter['MEDIA_TYPE'];
		}

		if (!empty($requestFilter['PERMALINK']))
		{
			$filter['permalink'][] = $requestFilter['PERMALINK'];
		}

		$timestamp = [];

		if (!empty($requestFilter['TIMESTAMP_from']))
		{
			$timestamp['from'] = strtotime($requestFilter['TIMESTAMP_from']);
		}

		if (!empty($requestFilter['TIMESTAMP_to']))
		{
			$timestamp['to'] = strtotime($requestFilter['TIMESTAMP_to']);
		}

		if (!empty($timestamp))
		{
			$filter['timestamp'][] = $timestamp;
		}

		if ($searchString !== '')
		{
			$filter['caption'][] = $searchString;
		}

		return $filter;
	}

	protected function getGridFilter()
	{
		$defaultColumns = [
			'CAPTION' => true,
			'IMPORTED' => true,
		];

		return [
			'FILTER_ID' => $this->getGridId(),
			'FILTER' => [
				[
					'id' => 'CAPTION',
					'name' => Loc::getMessage('CRM_OIIV_COLUMN_CAPTION'),
					'default' => isset($defaultColumns['CAPTION']),
				],
				[
					'id' => 'NEW',
					'name' => Loc::getMessage('CRM_OIIV_COLUMN_NEW'),
					'type' => 'checkbox',
					'default' => isset($defaultColumns['NEW']),
				],
				[
					'id' => 'IMPORTED',
					'name' => Loc::getMessage('CRM_OIIV_COLUMN_IMPORTED'),
					'type' => 'checkbox',
					'default' => isset($defaultColumns['IMPORTED']),
				],
				[
					'id' => 'MEDIA_TYPE',
					'name' => Loc::getMessage('CRM_OIIV_COLUMN_MEDIA_TYPE'),
					'default' => isset($defaultColumns['MEDIA_TYPE']),
					'type' => 'list',
					'items' => [
						Instagram::MEDIA_TYPE_IMAGE => Loc::getMessage('CRM_OIIV_COLUMN_MEDIA_TYPE_PICTURE'),
						Instagram::MEDIA_TYPE_CAROUSEL_ALBUM => Loc::getMessage('CRM_OIIV_COLUMN_MEDIA_TYPE_CAROUSEL_ALBUM'),
						Instagram::MEDIA_TYPE_VIDEO => Loc::getMessage('CRM_OIIV_COLUMN_MEDIA_TYPE_VIDEO'),
					],
					'params' => [
						'multiple' => 'Y',
					],
				],
				[
					'id' => 'PERMALINK',
					'name' => Loc::getMessage('CRM_OIIV_COLUMN_PERMALINK'),
					'default' => isset($defaultColumns['PERMALINK']),
				],
				[
					'id' => 'TIMESTAMP',
					'name' => Loc::getMessage('CRM_OIIV_COLUMN_TIMESTAMP'),
					'default' => isset($defaultColumns['TIMESTAMP']),
					'type' => 'date',
					'time' => true,
				],
			],
			'FILTER_PRESETS' => $this->getPresetFields(),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => false,
		];
	}

	protected function getPresetFields()
	{
		return [
			'recent' => [
				'name' => Loc::getMessage('CRM_OIIV_PRESET_RECENT'),
				'default' => false,
				'fields' => [
					'NEW' => 'Y',
				],
			],
			'imported' => [
				'name' => Loc::getMessage('CRM_OIIV_PRESET_IMPORTED'),
				'default' => false,
				'fields' => [
					'IMPORTED' => 'Y',
				],
			],
			'not_imported' => [
				'name' => Loc::getMessage('CRM_OIIV_PRESET_NOT_IMPORTED'),
				'default' => true,
				'fields' => [
					'IMPORTED' => 'N',
				],
			],
		];
	}

	protected function getCurrency()
	{
		$currencyList = [];

		if (Loader::includeModule('currency'))
		{
			$currencyId = \CCrmCurrency::GetBaseCurrencyID();
			$currencyFormat = CCurrencyLang::GetFormatDescription($currencyId);
			$currencyList[] = [
				'CURRENCY' => $currencyId,
				'FORMAT' => [
					'FORMAT_STRING' => $currencyFormat['FORMAT_STRING'],
					'DEC_POINT' => $currencyFormat['DEC_POINT'],
					'THOUSANDS_SEP' => $currencyFormat['THOUSANDS_SEP'],
					'DECIMALS' => $currencyFormat['DECIMALS'],
					'THOUSANDS_VARIANT' => $currencyFormat['THOUSANDS_VARIANT'],
					'HIDE_ZERO' => $currencyFormat['HIDE_ZERO'],
				],
			];
		}

		return $currencyList;
	}

	protected function obtainImport()
	{
		if ($this->arResult['FORM']['STEP'] < 3)
		{
			return;
		}

		if ($this->request->get('last_viewed_timestamp'))
		{
			$this->arResult['LAST_VIEWED_TIMESTAMP'] = (int)$this->request->get('last_viewed_timestamp');
		}
		else
		{
			$this->arResult['LAST_VIEWED_TIMESTAMP'] = (int)Option::get('crm', Instagram::LAST_VIEWED_TIMESTAMP_OPTION, 0);
		}

		$this->arResult['MEDIA'] = Instagram::getMedia();
		$this->markNewItems($this->arResult['MEDIA']);
		$this->markImportedItems($this->arResult['MEDIA']);
		$this->removeUnnecessarySymbols($this->arResult['MEDIA']);

		$this->arResult['HAVE_ITEMS_TO_IMPORT'] = $this->haveItemsToImport($this->arResult['MEDIA']);

		if (!empty($this->arResult['MEDIA']))
		{
			$mostRecentMedia = reset($this->arResult['MEDIA']);

			if (!empty($mostRecentMedia['timestamp']))
			{
				$mostRecentTimestamp = strtotime($mostRecentMedia['timestamp']);

				if ($mostRecentTimestamp > $this->arResult['LAST_VIEWED_TIMESTAMP'])
				{
					Option::set('crm', Instagram::LAST_VIEWED_TIMESTAMP_OPTION, $mostRecentTimestamp);
				}
			}
		}

		$this->arResult['GRID'] = $this->getGridData();
		$this->arResult['FILTER'] = $this->getGridFilter();
		$this->arResult['CURRENCIES'] = $this->getCurrency();
	}

	protected function haveItemsToImport(array $items)
	{
		foreach ($items as $item)
		{
			if (empty($item['imported']))
			{
				return true;
			}
		}

		return false;
	}

	public function importAjaxAction()
	{
		$response = [];

		if ($this->checkModules() && $this->isImportAvailable())
		{
			$items = $this->request->get('items') ?: [];

			if (!empty($items))
			{
				$result = Instagram::import($items);
				$response = $result->getData();

				if (!$result->isSuccess())
				{
					$this->errorCollection->add($result->getErrors());
				}
			}

			if ($this->request->get('total') === 'Y')
			{
				$response['total'] = Instagram::getProductsCount();
			}
		}

		return $response;
	}

	public function modifyProductAjaxAction()
	{
		if ($this->checkModules() && $this->isImportAvailable())
		{
			$productId = $this->request->get('productId') ?: false;

			$fields = [];

			if ($this->request->get('newName'))
			{
				$fields['NAME'] = trim((string)$this->request->get('newName'));
			}

			if ($this->request->get('newPrice'))
			{
				$fields['PRICE'] = (float)$this->request->get('newPrice');
			}

			if (!empty($productId))
			{
				if (!\CCrmProduct::Update($productId, $fields))
				{
					$this->errorCollection[] = new Error(\CCrmProduct::GetLastError());
				}
			}
		}

		return [];
	}

	protected function showErrors()
	{
		if (!$this->errorCollection->isEmpty())
		{
			ShowError(implode('<br>', $this->errorCollection->toArray()));
		}
	}

	protected function checkSessionNotifications()
	{
		if (!empty($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']) && is_array($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']))
		{
			$this->arResult['NOTIFICATIONS'] = $_SESSION['IMPORT_INSTAGRAM_NOTIFICATION'];
		}
	}

	public static function markSessionNotificationsRead()
	{
		unset($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']);
	}

	protected function addSessionNotification($message)
	{
		if (!is_array($_SESSION['IMPORT_INSTAGRAM_NOTIFICATION']))
		{
			$_SESSION['IMPORT_INSTAGRAM_NOTIFICATION'] = [];
		}

		$_SESSION['IMPORT_INSTAGRAM_NOTIFICATION'][] = $message;
	}

	public function executeComponent()
	{
		global $APPLICATION;

		$APPLICATION->SetTitle(Loc::getMessage('CRM_OIIV_TITLE'));

		Loc::loadMessages(__FILE__);

		if ($this->checkModules())
		{
			if ($this->isImportAvailable())
			{
				$this->initialization();
				$this->obtainForm();
				$this->obtainImport();

				if (!$this->errorCollection->isEmpty())
				{
					$this->arResult['error'] = $this->errorCollection->toArray();
				}

				if (!empty($this->messages))
				{
					$this->arResult['messages'] = $this->messages;
				}

				$this->checkSessionNotifications();

				$this->includeComponentTemplate();
				$this->errorCollection->clear();
			}
			else
			{
				$this->errorCollection[] = new Error(Loc::getMessage('CRM_OIIV_FACEBOOK_NO_ACTIVE_CONNECTOR'));
			}
		}

		$this->showErrors();
	}
}