<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Embed;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Entity\Identificator;

/**
 * Class User
 * @package Bitrix\Crm\WebForm\Embed
 */
class User
{
	private static array $fieldsByType = [];
	/**
	 * Get user data from entities.
	 *
	 * @param Identificator\ComplexCollection $entities Entities.
	 * @return array
	 */
	public static function getData(Identificator\ComplexCollection $entities): array
	{
		$data = [
			'fields' => [],
			'entities' => [],
		];

		$fieldsByType = [];
		$formId = (int)$entities->getIdByTypeId(-1);
		if ($formId > 0)
		{
			$form = new Crm\WebForm\Form($formId);
			if ($form->getId() && $form->isActive())
			{
				foreach ($form->getFieldsMap() as $field)
				{
					$typeId = \CCrmOwnerType::resolveID($field['entity_name']);
					if (!isset($fieldsByType[$typeId]))
					{
						$fieldsByType[$typeId] = [
							'fields' => [],
							'reqs' => [],
							'map' => [],
						];
					}

					$name = $field['entity_field_name'];
					if ($name === 'RQ')
					{
						continue;
					}

					if (mb_substr($name, 0, 3) === 'RQ_')
					{
						$fieldsByType[$typeId]['reqs'][] = $name;
						continue;
					}
					else
					{
						$fieldsByType[$typeId]['fields'][] = $name;
					}

					$fieldsByType[$typeId]['map'][$name] = $field['name'];
				}
			}
		}
		self::$fieldsByType = $fieldsByType;

		foreach ($entities as $entity)
		{
			/** @var Identificator\Complex $entity */
			if ($entity->getTypeId() <= 0)
			{
				continue;
			}

			$data['entities'][] = [
				'typeId' => $entity->getTypeId(),
				'id' => $entity->getId(),
			];
			$data['fields'] = $data['fields'] + self::getEntityFields(
					$entity,
					isset($form)
						? ($form->get()['FORM_SETTINGS']['REQUISITE_PRESET_ID'] ?? null)
						: null
				);
		}

		self::$fieldsByType = [];

		return $data;
	}

	protected static function richMap($typeId, array $map = []): array
	{
		return $map + (self::$fieldsByType[$typeId]['map'] ?? []);
	}

	protected static function getEntityFields(Identificator\Complex $entity, ?int $presetId): array
	{
		$data = [
			/*
			'name' => '',
			'last-name' => '',
			'email' => '',
			'phone' => '',
			'second-name' => '',
			'company-name' => '',
			*/
		];

		switch ($entity->getTypeId())
		{
			case \CCrmOwnerType::Deal:
				return self::getClientDataByFields(
					Crm\DealTable::getRow([
						'select' => ['CONTACT_ID', 'COMPANY_ID'],
						'filter' => ['=ID' => $entity->getId()]
					]), $presetId
				);

			case \CCrmOwnerType::Quote:
				return self::getClientDataByFields(
					Crm\QuoteTable::getRow([
						'select' => ['CONTACT_ID', 'COMPANY_ID'],
						'filter' => ['=ID' => $entity->getId()]
					]), $presetId
				);

			case \CCrmOwnerType::Company:
				$map = self::richMap(
					$entity->getTypeId(),
					[
						'TITLE' => 'company-name'
					]
				);
				return self::getDataByFieldsMap(self::loadEntityData($entity), $map);

			case \CCrmOwnerType::Lead:
				$map = self::richMap(
					$entity->getTypeId(),
					[
						'NAME' => 'name',
						'LAST_NAME' => 'last-name',
						'SECOND_NAME' => 'second-name',
						'EMAIL_WORK' => 'email',
						'EMAIL_MAILING' => 'email',
						'EMAIL_HOME' => 'email',
						'PHONE_WORK' => 'phone',
						'PHONE_MAILING' => 'phone',
						'PHONE_MOBILE' => 'phone',
						'COMPANY_TITLE' => 'company-name',
						'COMPANY_ID' => '',
						'CONTACT_ID' => '',
					]
				);
				$fields = Crm\LeadTable::getRow([
					'select' => array_keys($map),
					'filter' => ['=ID' => $entity->getId()]
				]);
				return self::getClientDataByFields($fields, $presetId) + self::getDataByFieldsMap($fields, $map);

			case \CCrmOwnerType::Contact:
				$map = self::richMap(
					$entity->getTypeId(),
					[
						'NAME' => 'name',
						'LAST_NAME' => 'last-name',
						'SECOND_NAME' => 'second-name',
						'EMAIL_WORK' => 'email',
						'EMAIL_MAILING' => 'email',
						'EMAIL_HOME' => 'email',
						'PHONE_WORK' => 'phone',
						'PHONE_MAILING' => 'phone',
						'PHONE_MOBILE' => 'phone',
						'COMPANY_ID' => '',
					]
				);

				$fields = self::loadEntityData($entity);
				return self::getDataByFieldsMap($fields, $map) + self::getClientDataByFields($fields, $presetId);

			default:
				if (!\CCrmOwnerType::isUseDynamicTypeBasedApproach($entity->getTypeId()))
				{
					break;
				}

				$dynamicFactory = Crm\Service\Container::getInstance()->getFactory($entity->getTypeId());
				$dynamicItem = $dynamicFactory->getItem($entity->getId());
				if (!$dynamicItem)
				{
					break;
				}

				$data += self::getDataByFieldsMap(
					$dynamicItem->getData(),
					self::richMap($entity->getTypeId(), [])
				);
				if (!$dynamicItem->getContactId() && !$dynamicItem->getCompanyId())
				{
					break;
				}

				$data += self::getClientDataByFields([
					'CONTACT_ID' => $dynamicItem->getContactId(),
					'COMPANY_ID' => $dynamicItem->getCompanyId(),
				], $presetId);
		}

		return $data;
	}

	protected static function getDataByFieldsMap($fields, array $map): array
	{
		$data = [];
		if (!is_array($fields))
		{
			return $data;
		}

		static $templatedData = null;
		if ($templatedData === null)
		{
			$templatedData = Crm\WebForm\Entity::getMap();
			$templatedData = [
				'name' => $templatedData['CONTACT']['FIELD_AUTO_FILL_TEMPLATE']['NAME']['TEMPLATE'],
				'company-name' => $templatedData['COMPANY']['FIELD_AUTO_FILL_TEMPLATE']['TITLE']['TEMPLATE']
			];
		}

		if (!Main\Type\Collection::isAssociative($map))
		{
			$map = array_combine($map, $map);
		}

		foreach ($map as $fieldKey => $dataKey)
		{
			if (empty($fields[$fieldKey]) || !$dataKey)
			{
				continue;
			}

			if (!empty($templatedData[$dataKey]))
			{
				if ($fields[$fieldKey] === $templatedData[$dataKey])
				{
					continue;
				}
			}

			if ($dataKey === 'name' && $fields[$fieldKey] === $templatedData[$dataKey])
			{
				continue;
			}

			$data[$dataKey] = $fields[$fieldKey];
		}

		return $data;
	}

	protected static function getClientDataByFields($fields, ?int $presetId): array
	{
		$result = [];
		if (!is_array($fields))
		{
			return $result;
		}

		if ($fields['CONTACT_ID'])
		{
			$typeId = \CCrmOwnerType::Contact;
			$entityId = $fields['CONTACT_ID'];
			$result += self::getEntityFields(new Identificator\Complex(
				$typeId,
				$entityId
			), $presetId);

			$result += self::loadReqData($typeId, $entityId, $presetId);
		}

		if ($fields['COMPANY_ID'])
		{
			$typeId = \CCrmOwnerType::Company;
			$entityId = $fields['COMPANY_ID'];
			$result += self::getEntityFields(new Identificator\Complex(
				$typeId,
				$entityId
			), $presetId);

			$result += self::loadReqData($typeId, $entityId, $presetId);
		}

		return $result;
	}

	private static function loadReqData($typeId, $entityId, $presetId): array
	{
		$typeName = \CCrmOwnerType::ResolveName($typeId);
		$reqs = self::$fieldsByType[$typeId]['reqs'] ?? [];
		if (!$reqs)
		{
			return [];
		}

		$reqData = Crm\WebForm\Requisite::instance()->load($typeId, $entityId, $presetId);
		if (!$reqData->isSuccess())
		{
			return [];
		}

		return self::getDataByFieldsMap(
			$reqData->getData() ?: [],
			array_combine(
				$reqs,
				array_map(
					static function (string $key) use ($typeName): string
					{
						return "{$typeName}_{$key}";
					},
					$reqs
				)
			)
		);
	}

	private static function loadEntityData(Crm\Entity\Identificator\Complex $entity): array
	{
		$container = Crm\Service\Container::getInstance();
		$factory = $container->getFactory($entity->getTypeId());
		if (!$factory)
		{
			return [];
		}

		$item = $factory->getItem($entity->getId());
		if (!$item)
		{
			return [];
		}

		$values = $item->getData();
		foreach ($item->getFm()->getAll() as $fmItem)
		{
			$type = $fmItem->getTypeId();
			$value = $fmItem->getValue();
			if ($value && empty($values[$type]))
			{
				$values[$type] = $value;
			}
		}

		return $values;
	}
}
