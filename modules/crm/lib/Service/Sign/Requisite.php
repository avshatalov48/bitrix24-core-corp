<?php

namespace Bitrix\Crm\Service\Sign;

use Bitrix\Crm\Security\PermissionToken;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sign\Service\Container;

class Requisite
{
	public static function getBannerData(Crm\FieldSet\Item $item, int $entityId, ?string $documentUid = null): array
	{
		$fields = [];
		$presetId = $item->getRequisitePresetId();
		$values = Crm\Integration\Sign\Form::getFieldSetValues(
			$item->getClientEntityTypeId(),
			$entityId,
			['appendExtended' => true],
			$presetId
		);

		$rqId = (int)($values['extended']['requisiteId'] ?? 0);
		$presetId =
			(int)($values['extended']['presetId'] ?? 0)
				?: $presetId;
		$title = $values['extended']['title'] ?? '';
		unset($values['extended']);

		if (!$title)
		{
			switch ($item->getClientEntityTypeId())
			{
				case \CCrmOwnerType::Company:
					$title = Crm\CompanyTable::query()
						->setSelect(['TITLE'])
						->where('ID', $entityId)
						->setLimit(1)
						->fetch()['TITLE'];
					break;

				case \CCrmOwnerType::Contact:
					$row = Crm\ContactTable::query()
						->setSelect(
							[
								'NAME',
								'LAST_NAME'
							]
						)
						->where('ID', $entityId)
						->setLimit(1)
						->fetch()
					;
					if ($row)
					{
						$title = trim(
							str_replace(
								[
									'#NAME#',
									'#LAST_NAME#'
								],
								[
									$row['NAME'],
									$row['LAST_NAME']
								],
								Main\Context::getCurrent()->getCulture()->getFormatName()
							)
						);
					}
					break;
			}
		}

		$permissionToken =  PermissionToken::createEditMyCompanyRequisitesToken(
			$item->getEntityTypeId(),
			$entityId
		);

		$document = null;
		if (Main\Loader::includeModule('sign') && $documentUid !== null)
		{
			$document = Container::instance()->getDocumentRepository()->getByUid($documentUid);
		}

		$rqEditUrl = "/bitrix/components/bitrix/crm.requisite.details/slider.ajax.php"
			. "?requisite_id={$rqId}"
			. "&pid={$presetId}"
			. "&etype={$item->getClientEntityTypeId()}"
			. "&eid={$entityId}"
			. "&mode=" . ($rqId
				? 'edit'
				: 'create')
			. "&doSave=Y"
			. "&" . bitrix_sessid_get();

		foreach ($item->getFields() as $field)
		{
			$name = $field['name'];
			$value = $values[$name] ?? '';
			$field['value'] = $value;
			$field['valuePrintable'] = $value;
			$fieldEntityTypeId = $field['editing']['entityTypeId'];
			$field['permissionToken'] = $permissionToken;

			if (!$fieldEntityTypeId)
			{
				continue;
			}

			$field['editing']['url'] =
				($fieldEntityTypeId === \CCrmOwnerType::Requisite)
					? $rqEditUrl
					: Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
						$fieldEntityTypeId,
						$entityId
					);

			if ($fieldEntityTypeId === \CCrmOwnerType::Requisite)
			{
				$field['editing']['permissionToken'] = $permissionToken;
			}

			if ($fieldEntityTypeId === \CCrmOwnerType::Company)
			{
				$field['editing']['entityId'] = $entityId;
				$field['editing']['documentEntityId'] = $document?->entityId;
				$field['editing']['documentEntityTypeId'] = $document?->entityTypeId;
			}

			$fields[] = $field;
		}

		return [
			'id' => $item->getId(),
			'title' => $title,
			'fields' => $fields,
		];
	}
}