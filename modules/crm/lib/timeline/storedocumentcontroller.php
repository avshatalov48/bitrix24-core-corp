<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('catalog'))
{
	return;
}

class StoreDocumentController extends EntityController
{
	public const ADD_EVENT_NAME = 'timeline_store_document_add';

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::StoreDocument;
	}

	public function onCreate($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if ($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$settings = [
			'TOTAL' => $params['FIELDS']['TOTAL'] ?? 0,
			'CURRENCY' => $params['FIELDS']['CURRENCY'] ?? '',
		];

		$authorID = self::resolveCreatorID($params['FIELDS']);
		$bindings = $this->getDefaultBindings($ownerID);
		$historyEntryID = StoreDocumentEntry::create([
			'TYPE_CATEGORY_ID' => TimelineType::CREATION,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::StoreDocument,
			'ENTITY_ID' => $ownerID,
			'AUTHOR_ID' => $authorID,
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);

		$this->onStatusModify($ownerID, $params['FIELDS'], $bindings);
	}

	public function onModify($entityID, array $params)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$updatedFields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : [];
		$oldFields = isset($params['OLD_FIELDS']) && is_array($params['OLD_FIELDS']) ? $params['OLD_FIELDS'] : [];

		$bindings = $params['BINDINGS'] ?? $bindings = $this->getDefaultBindings($entityID);

		if (isset($updatedFields['TOTAL']) || isset($updatedFields['CURRENCY']))
		{
			$this->onTotalModify($entityID, $updatedFields, $oldFields, $bindings);
		}

		if (isset($updatedFields['STATUS']))
		{
			$this->onStatusModify($entityID, $updatedFields, $bindings);
		}

		if (isset($params['ERROR']))
		{
			$this->onConductError($entityID, $params, $bindings);
		}
	}

	private function onTotalModify($documentId, $updatedFields, $oldFields, $bindings)
	{
		$historyEntryID = null;

		if (!isset($updatedFields['TOTAL']) && !isset($updatedFields['CURRENCY']))
		{
			return;
		}

		$oldTotal = $oldFields['TOTAL'];
		$newTotal = $updatedFields['TOTAL'];
		$oldCurrency = $oldFields['CURRENCY'];
		$newCurrency = $updatedFields['CURRENCY'];

		if ((float)$oldTotal === (float)$newTotal && $oldCurrency === $newCurrency)
		{
			return;
		}

		if (!isset($newTotal, $newCurrency))
		{
			$documentInfo = StoreDocumentTable::getList(['select' => ['TOTAL', 'CURRENCY'], 'filter' => ['=ID' => $documentId], 'limit' => 1])->fetch();
			if (!isset($newCurrency))
			{
				$newCurrency = $documentInfo['CURRENCY'];
			}
			if (!isset($newTotal))
			{
				$newTotal = $documentInfo['TOTAL'];
			}
		}

		$authorID = self::resolveEditorID($updatedFields);

		$historyEntryID = StoreDocumentEntry::create(
			[
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::StoreDocument,
				'ENTITY_ID' => $documentId,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => [
					'FIELD' => 'TOTAL',
					'TOTAL' => $newTotal,
					'CURRENCY' => $newCurrency,
				],
				'BINDINGS' => $bindings
			]
		);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new \Bitrix\Crm\ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$historyEntryID
			);
		}
	}

	private function onStatusModify($documentId, $updatedFields, $bindings)
	{
		$historyEntryID = null;
		if (!isset($updatedFields['STATUS']))
		{
			return;
		}
		$newStage = $updatedFields['STATUS'];

		$documentInfo = StoreDocumentTable::getList(['select' => ['TOTAL', 'CURRENCY', 'WAS_CANCELLED'], 'filter' => ['=ID' => $documentId], 'limit' => 1])->fetch();
		$wasCancelledBefore = $documentInfo['WAS_CANCELLED'];
		if ($newStage === 'N')
		{
			if ($wasCancelledBefore === 'N')
			{
				$newStageName = Loc::getMessage('STORE_DOCUMENT_STATUS_DRAFT');
				$newStageClass = StoreDocumentStatusDictionary::DRAFT;
			}
			else
			{
				$newStageName = Loc::getMessage('STORE_DOCUMENT_STATUS_CANCELLED');
				$newStageClass = StoreDocumentStatusDictionary::CANCELLED;
			}
		}
		else
		{
			$newStageName = Loc::getMessage('STORE_DOCUMENT_STATUS_CONDUCTED');
			$newStageClass = StoreDocumentStatusDictionary::CONDUCTED;
		}

		$authorID = self::resolveEditorID($updatedFields);

		$historyEntryID = StoreDocumentEntry::create(
			[
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::StoreDocument,
				'ENTITY_ID' => $documentId,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => [
					'FIELD' => 'STATUS',
					'NEW_VALUE' => $newStageName,
					'CLASS' => $newStageClass,
					'TOTAL' => $documentInfo['TOTAL'] ?? 0,
					'CURRENCY' => $documentInfo['CURRENCY'] ?? '',
				],
				'BINDINGS' => $bindings
			]
		);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new \Bitrix\Crm\ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$historyEntryID
			);
		}
	}

	private function onConductError($documentId, $params, $bindings)
	{
		$historyEntryID = null;
		$error = $params['ERROR'];
		$errorMessage = $params['ERROR_MESSAGE'];

		$authorID = self::resolveEditorID($params['FIELDS']);

		$historyEntryID = StoreDocumentEntry::create(
			[
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::StoreDocument,
				'ENTITY_ID' => $documentId,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => [
					'ERROR' => $error,
					'ERROR_MESSAGE' => $errorMessage,
				],
				'BINDINGS' => $bindings
			]
		);
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		if (empty($data['ASSOCIATED_ENTITY']['TITLE']))
		{
			$typeListDescriptions = StoreDocumentTable::getTypeList(true);
			$data['ASSOCIATED_ENTITY']['TITLE'] = $typeListDescriptions[$data['ASSOCIATED_ENTITY']['DOC_TYPE']] ?? '';
		}
		$data['TITLE_TEMPLATE'] = Loc::getMessage(
			'STORE_DOCUMENT_TITLE',
			[
				'#DATE#' => $data['ASSOCIATED_ENTITY']['DATE_CREATE'],
			]
		);
		$data['TOTAL'] = $data['SETTINGS']['TOTAL'] ?? null;
		$data['CURRENCY'] = $data['SETTINGS']['CURRENCY'] ?? null;

		if (!empty($data['ASSOCIATED_ENTITY']['ID']))
		{
			$data['DETAIL_LINK'] = \CComponentEngine::MakePathFromTemplate(
				\COption::GetOptionString('crm', 'path_to_store_document_details'),
				[
					'store_document_id' => (int)$data['ASSOCIATED_ENTITY']['ID'],
				]
			);
		}

		if ((int)$data['TYPE_CATEGORY_ID'] === TimelineType::MODIFICATION)
		{
			$data['FIELD'] = $data['SETTINGS']['FIELD'] ?? '';
			if ($data['SETTINGS']['FIELD'] === 'STATUS')
			{
				$data['STATUS_TITLE'] = $data['SETTINGS']['NEW_VALUE'];
				$data['STATUS_CLASS'] = $data['SETTINGS']['CLASS'];
			}
			$data['MODIFIED_FIELD'] = $data['FIELD'];

			if (isset($data['SETTINGS']['ERROR']))
			{
				$data['ERROR'] = $data['SETTINGS']['ERROR'];
				$data['ERROR_MESSAGE'] = htmlspecialcharsbx($data['SETTINGS']['ERROR_MESSAGE']);
			}
		}

		return parent::prepareHistoryDataModel($data, $options);
	}

	protected static function resolveCreatorID(array $fields)
	{
		$authorId = 0;
		if (isset($fields['CREATED_BY']))
		{
			$authorId = (int)$fields['CREATED_BY'];
		}

		if ($authorId <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorId = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorId <= 0)
		{
			$authorId = self::getDefaultAuthorId();
		}

		return $authorId;
	}

	protected static function resolveEditorID(array $fields)
	{
		$authorId = 0;
		if (isset($fields['MODIFIED_BY']))
		{
			$authorId = (int)$fields['MODIFIED_BY'];
		}

		if ($authorId <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorId = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorId <= 0 && isset($fields['CREATED_BY']))
		{
			$authorId = (int)$fields['CREATED_BY'];
		}

		if ($authorId <= 0)
		{
			$authorId = self::getDefaultAuthorId();
		}

		return $authorId;
	}

	private function getDefaultBindings($documentId)
	{
		return [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::StoreDocument,
				'ENTITY_ID' => $documentId,
			]
		];
	}
}
