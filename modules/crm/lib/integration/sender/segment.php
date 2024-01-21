<?php
namespace Bitrix\Crm\Integration\Sender;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sender;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class Segment extends Main\Engine\Controller
{
	/**
	 * Upload entity emails/phone_numbers to new or existed segment.
	 * Example:
	 * BX.ajax.runAction(
	 * 	"crm.integration.sender.segment.upload",
	 * 	{ data: { id: null, entityTypeName: "COMPANY", entities: [1,2,3] }}
	 * );
	 *
	 * @param int|null $segmentId Segment ID.
	 * @param string $entityTypeName Entity type name.
	 * @param array $entities Entities.
	 * @param string $gridId Grid ID.
	 * @return array
	 */

	const ENTITIES_LIMIT = 5000;
	
	/**
	 * @param ?string $segmentId Segment id.
	 * @param string $entityTypeName Entity type name.
	 * @param array $entities Entities.
	 * @param string|null $gridId Grid id.
	 * @return array
	 * @throws ArgumentException
	 * @throws NotSupportedException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function uploadAction(?string $segmentId = null, string $entityTypeName = '', array $entities = [], ?string $gridId = null)
	{
		if (!GridPanel::canCurrentUserModifySegments())
		{
			return ['errors' => ['Access denied.']];
		}

		$entityTypeId = \CCrmOwnerType::resolveID($entityTypeName);
		if (!$entityTypeId)
		{
			return ['errors' => ['Wrong entity type.']];
		}
		if (!in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company, \CCrmOwnerType::Lead]))
		{
			return ['errors' => ['Entity type does not allowed.']];
		}

		if ($gridId) // for all
		{
			$entitiesResult = self::getEntitiesByGridId($entityTypeId, $gridId);
			if (!$entitiesResult->isSuccess())
			{
				return ['errors' => $entitiesResult->getErrorMessages()];
			}
			$entities = $entitiesResult->getData();
		}

		$segment = new Sender\Entity\Segment((int)$segmentId);
		if (!$segment->getId())
		{
			$segmentName = Loc::getMessage(
				'CRM_INTEGRATION_SENDER_SEGMENT_NAME_PATTERN_' . $entityTypeName,
				['%date%' => Sender\Internals\PrettyDate::formatDate()]
			);
			$segment
				->set('NAME', $segmentName)
				->set('HIDDEN', 'N')
				->appendContactSetConnector()
				->save();
			if ($segment->hasErrors())
			{
				return ['errors' => $segment->getErrorMessages()];
			}
		}


		$segment->upload(self::getAddresses($entityTypeName, $entities));
		if ($segment->hasErrors())
		{
			return ['errors' => $segment->getErrorMessages()];
		}
		$segment->save();

		$segmentId = $segment->getId();
		$segmentName = $segment->get('NAME');
		$textSuccess = Loc::getMessage(
			'CRM_INTEGRATION_SENDER_SEGMENT_UPLOAD_SUCCESS',
			[
				'%name%' => $segmentName,
				'%path%' => '/marketing/segment/'
			]
		);

		return [
			'id' => $segmentId,
			'name' => $segmentName,
			'textSuccess' => str_replace('%name%', $segmentName, $textSuccess),
		];
	}

	/**
	 * @param string $entityTypeName Entity type name.
	 * @param array $entities Entities.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected static function getAddresses($entityTypeName, array $entities)
	{
		if (!$entities)
		{
			return [];
		}
		switch ($entityTypeName)
		{
			case \CCrmOwnerType::CompanyName:
				$entityObject = new \CCrmCompany();
				break;
			case \CCrmOwnerType::ContactName:
				$entityObject = new \CCrmContact();
				break;
			case \CCrmOwnerType::LeadName:
				$entityObject = new \CCrmLead();
				break;

			default:
				return [];
		}

		$listDb = $entityObject->getListEx([], ['@ID' => $entities], false, false, ['ID', 'NAME', 'TITLE', 'CONTACT_NAME']);
		$entities = [];
		while ($entity = $listDb->Fetch())
		{
			$entityName = isset($entity['TITLE']) ? $entity['TITLE'] : null;
			$entityName = isset($entity['NAME']) ? $entity['NAME'] : $entityName;
			$entityName = isset($entity['CONTACT_NAME']) ? $entity['CONTACT_NAME'] : $entityName;
			$entities[$entity['ID']] = $entityName;
		}

		$result = [];

		$typeMap = [
			\CCrmFieldMulti::PHONE => Sender\Recipient\Type::PHONE,
			\CCrmFieldMulti::EMAIL => Sender\Recipient\Type::EMAIL,
		];
		$list = Crm\FieldMultiTable::getList([
			'select' => ['TYPE_ID', 'VALUE', 'ELEMENT_ID'],
			'filter' => [
				'=ENTITY_ID' => $entityTypeName,
				'=ELEMENT_ID' => array_keys($entities),
				'=TYPE_ID' => array_keys($typeMap)
			],
		]);
		foreach ($list as $item)
		{
			$result[] = [
				'CODE' => $item['VALUE'],
				'NAME' => isset($entities[$item['ELEMENT_ID']])
					? $entities[$item['ELEMENT_ID']]
					: null
				,
			];
		}

		return $result;
	}

	/**
	 * @param int $entityTypeId Entity type id.
	 * @param string $gridId Grid id.
	 * @return Main\Result
	 * @throws Main\NotSupportedException
	 */
	private static function getEntitiesByGridId($entityTypeId, $gridId)
	{
		$result = new Main\Result();
		$result->setData([]);
		if (!$entityTypeId || !$gridId)
		{
			return $result;
		}

		$filterFields = \Bitrix\Crm\Filter\Factory::createEntityFilter(
			\Bitrix\Crm\Filter\Factory::getSettingsByGridId($entityTypeId, (string)$gridId)
		)->getValue();

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeId);
		if($entity)
		{
			if ($entity->getCount(['filter' => $filterFields]) > self::ENTITIES_LIMIT)
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage('CRM_INTEGRATION_SENDER_SEGMENT_LIMIT_ERROR', array("%limit%" => self::ENTITIES_LIMIT))
					)
				);
			}
			else
			{
				$result->setData($entity->getTopIDs(['filter' => $filterFields, 'limit' => 0]));
			}
		}

		return $result;
	}
}