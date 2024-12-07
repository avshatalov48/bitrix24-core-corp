<?php
namespace Bitrix\Crm\Controller\Requisite;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integration\BankDetailResolver;
use Bitrix\Crm\Integration\ClientResolver;
use Bitrix\Main\Engine\Controller;

class Entity extends Controller
{
	public static function searchAction($searchQuery, $options = [])
	{
		$presetId = $options['presetId'];

		$requisiteEntity = new EntityRequisite();
		$countryId = (int)$requisiteEntity->getCountryIdByPresetId((int)$presetId);

		$defaultResolverClass = 'ClientResolver';
		$resolverNamespace = 'Bitrix\\Crm\\Integration\\';
		$resolverClass = '';
		$typeId = '';
		$isTypeIdSet = false;
		if (
			isset($options['typeId'])
			&& is_string($options['typeId'])
			&& $options['typeId'] !== ''
		)
		{
			foreach (['ClientResolver', 'BankDetailResolver'] as $resolverClass)
			{
				$resolverClass = $resolverNamespace . $resolverClass;
				/** @var ClientResolver|BankDetailResolver $resolverClass */
				$allowedTypesMap = $resolverClass::getAllowedTypesMap();
				if (
					isset($allowedTypesMap[$countryId])
					&& is_array($allowedTypesMap[$countryId])
					&& in_array($options['typeId'], $allowedTypesMap[$countryId], true)
				)
				{
					$typeId = $options['typeId'];
					$isTypeIdSet = true;
					break;
				}
			}
		}

		if (!$isTypeIdSet)
		{
			$resolverClass = $resolverNamespace . $defaultResolverClass;
			$type = ($countryId ? $resolverClass::getPropertyTypeByCountry($countryId) : []);
			$typeId = empty($type) ? null : (string)$type['VALUE'];
		}

		$preparedSearchQuery = "";
		if ($typeId == ClientResolver::PROP_ITIN && preg_match('/[0-9]{10,12}/', $searchQuery, $matches))
		{
			$preparedSearchQuery = $matches[0];
		}
		elseif ($typeId == BankDetailResolver::PROP_BIC && preg_match('/[0-9]{9}/', $searchQuery, $matches))
		{
			$preparedSearchQuery = $matches[0];
		}
		elseif ($typeId == ClientResolver::PROP_SRO && preg_match('/[0-9]{8}/', $searchQuery, $matches))
		{
			$preparedSearchQuery = $matches[0];
		}

		if ($preparedSearchQuery == '')
		{
			return [
				'items' => []
			];
		}

		$result = (new $resolverClass)->resolveClient(
			$typeId,
			$preparedSearchQuery,
			$countryId
		);

		return [
			'items' => $result
		];
	}
}