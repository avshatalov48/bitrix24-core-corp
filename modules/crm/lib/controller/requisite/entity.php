<?php
namespace Bitrix\Crm\Controller\Requisite;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integration\ClientResolver;
use Bitrix\Main\Engine\Controller;

class Entity extends Controller
{
	public static function searchAction($searchQuery, $options = [])
	{
		$presetId = $options['presetId'];

		$requisiteEntity = new EntityRequisite();
		$countryId = (int)$requisiteEntity->getCountryIdByPresetId((int)$presetId);
		$type = ($countryId ? ClientResolver::getPropertyTypeByCountry($countryId) : []);
		$typeId = empty($type) ? null : (string)$type['VALUE'];

		$preparedSearchQuery = "";
		if ($typeId == ClientResolver::PROP_ITIN && preg_match('/[0-9]{10,12}/', $searchQuery, $matches))
		{
			$preparedSearchQuery = $matches[0];
		}
		if ($typeId == ClientResolver::PROP_SRO && preg_match('/[0-9]{8}/', $searchQuery, $matches))
		{
			$preparedSearchQuery = $matches[0];
		}
		if ($preparedSearchQuery == '')
		{
			return [
				'items' => []
			];
		}

		$result = (new ClientResolver())->resolveClient(
			$typeId,
			$preparedSearchQuery,
			$countryId
		);

		return [
			'items' => $result
		];
	}
}