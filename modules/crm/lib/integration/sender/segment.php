<?php
namespace Bitrix\Crm\Integration\Sender;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
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
	 * @param $entityTypeName
	 * @param array $entities
	 * @return array
	 */
	public function uploadAction($segmentId = null, $entityTypeName, array $entities)
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

		$segment = new Sender\Entity\Segment($segmentId);
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

	protected static function getAddresses($entityTypeName, array $entities)
	{
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

		$listDb = $entityObject->getListEx([], ['ID' => $entities], false, false, ['ID']);
		$entities = [];
		while ($entity = $listDb->Fetch())
		{
			$entities[] = $entity['ID'];
		}

		$result = [];

		$typeMap = [
			\CCrmFieldMulti::PHONE => Sender\Recipient\Type::PHONE,
			\CCrmFieldMulti::EMAIL => Sender\Recipient\Type::EMAIL,
		];
		$list = Crm\FieldMultiTable::getList([
			'select' => ['TYPE_ID', 'VALUE'],
			'filter' => [
				'=ENTITY_ID' => $entityTypeName,
				'=ELEMENT_ID' => $entities,
				'=TYPE_ID' => array_keys($typeMap)
			],
		]);
		foreach ($list as $item)
		{
			//$typeMap[$item['TYPE_ID']]
			$result[] = $item['VALUE'];
		}

		return $result;
	}
}