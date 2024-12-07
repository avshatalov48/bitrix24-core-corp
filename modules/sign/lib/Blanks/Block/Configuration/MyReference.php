<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Item;

class MyReference extends Configuration
{
	public function validate(Item\Block $block): Main\Result
	{
		$result = parent::validate($block);

		if (($block->data['field'] ?? null) === null)
		{
			return $result->addError(
				new Main\Error(
					Loc::getMessage('SIGN_BLANKS_BLOCK_CONFIGURATION_MYREFERENCE_ERROR_FIELD_NOT_SELECTED'),
					'REFERENCE_ERROR_FIELD_NOT_SELECTED',
				)
			);
		}

		return $result;
	}

	function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		$data = $block->data;
		if (($data['field'] ?? null) === null)
		{
			return $data;
		}

		if ($member === null)
		{
			return $data;
		}
		$fieldCode = $data['field'];

		$fieldValue = static::getEntityFieldValue($fieldCode, $document, $member);
		if ($fieldValue !== null)
		{
			$data = array_merge(
				$data,
				$fieldValue
			);
		}

		return $data;
	}

	private static function getEntityFieldValue(string $fieldCode, Item\Document $document, Item\Member $member): ?array
	{
		$entityId = static::getEntityIdByFieldCode($fieldCode, $document, $member);
		if ($entityId === null)
		{
			return null;
		}

		$documentId = $document?->id ?? $member?->documentId;

		return CRM::getEntityFieldValue($entityId, $fieldCode, $documentId, $member->presetId);
	}

	private static function getEntityIdByFieldCode(
		string $fieldCode,
		Item\Document $document,
		Item\Member $member
	): ?int
	{
		$entityTypeId = (new CRM\FieldCode($fieldCode))->getEntityTypeId();

		if ($entityTypeId === null)
		{
			return null;
		}

		switch (true)
		{
			case $entityTypeId === \Bitrix\Sign\Document\Entity\Smart::getEntityTypeId():
			{
				return $document->entityId;
			}
			case $entityTypeId === CRM::getOwnerTypeCompany():
			case $entityTypeId === CRM::getOwnerTypeContact():
			{
				return $member->entityId;
			}
		}

		return null;
	}
}