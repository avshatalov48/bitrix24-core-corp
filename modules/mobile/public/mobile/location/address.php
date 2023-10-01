<?php

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Service\FormatService;
use Bitrix\Location\Entity\Format;
use Bitrix\Main\Application;
use Bitrix\Location\Infrastructure\Service\RecentAddressesService;
use Bitrix\Main\Web\Json;
use Bitrix\Main\ArgumentException;

require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

Loader::requireModule('location');
Loader::requireModule('ui');

Extension::load([
	'ui.vue3',
	'location.mobile',
]);

$request = Application::getInstance()->getContext()->getRequest();
$addressEditorParams = [
	'recentAddresses' => array_map(
		static fn (Address $item) => $item->toArray(),
		RecentAddressesService::getInstance()->get()
	),
	'isEditable' => $request->get('isEditable') === 'Y',
];

$languageId = $request->get('languageId') ? $request->get('languageId') : LANGUAGE_ID;

if ($request->get('uid'))
{
	$addressEditorParams['uid'] = (string)$request->get('uid');
}

if ($request->get('formatCode') && $languageId)
{
	$addressEditorParams['format'] = Format\Converter\ArrayConverter::convertToArray(
		FormatService::getInstance()->findByCode(
			(string)$request->get('formatCode'),
			(string)$languageId
		)
	);
}

if ($request->get('deviceGeoPosition'))
{
	try
	{
		$addressEditorParams['deviceGeoPosition'] = Json::decode($request->get('deviceGeoPosition'));
	}
	catch (ArgumentException $e) {}
}

$address = null;
if ($request->get('address'))
{
	$address = Address::fromJson($request->get('address'));
}
elseif ($request->get('geoPoint'))
{
	try
	{
		$geoPoint = Json::decode($request->get('geoPoint'));
	}
	catch (ArgumentException $e)
	{
		$geoPoint = [];
	}

	$coords = $geoPoint['coords'] ?? [];
	$hasTextAddress = !empty($geoPoint['address']);
	$hasCoords = (
		isset($coords['lat'])
		&& isset($coords['lng'])
	);

	if ($hasTextAddress || $hasCoords)
	{
		$address = new Address($languageId);

		if ($hasTextAddress)
		{
			$address->setFieldValue(
				Address\FieldType::ADDRESS_LINE_2,
				(string)$geoPoint['address']
			);
		}

		if ($hasCoords)
		{
			$address
				->setLatitude((string)$coords['lat'])
				->setLongitude((string)$coords['lng'])
			;
		}
	}
}

$addressEditorParams['initialAddress'] =
	$address
		? Address\Converter\ArrayConverter::convertToArray($address)
		: $address
;

?>
	<div id="address-editor" style="height: 100%; width: 100%;"></div>
	<script>
		BX.Vue3.BitrixVue.createApp(
			BX.Location.Mobile.AddressEditor,
			<?php echo \CUtil::PhpToJSObject($addressEditorParams);?>
		).mount('#address-editor');
	</script>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
