<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

class FieldSet extends Main\Engine\JsonController
{
	public function listAction(): array
	{
		return [];
	}

	public function loadAction(int $entityTypeId, int $entityId, ?int $presetId = null): array
	{
		$item = Crm\Integration\Sign\Form::getFieldSet($entityTypeId, $presetId);
		if (!$item)
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_FIELDSET_NOT_FOUND', ['#ENTITY_TYPE#' => $entityTypeId])));
			return [];
		}

		$fields = [];
		$values = Crm\Integration\Sign\Form::getFieldSetValues(
			$entityTypeId,
			$entityId,
			['appendExtended' => true],
			$presetId
		);

		$rqId = (int)($values['extended']['requisiteId'] ?? 0);
		$presetId = (int)($values['extended']['presetId'] ?? 0) ?: $item->getRequisitePresetId();
		$title = $values['extended']['title'] ?? '';
		unset($values['extended']);

		if (!$title)
		{
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Company:
					$title = Crm\CompanyTable::query()
						->setSelect(['TITLE'])
						->where('ID', $entityId)
						->setLimit(1)
						->fetch()['TITLE']
					;
					break;

				case \CCrmOwnerType::Contact:
					$row = Crm\ContactTable::query()
						->setSelect(['NAME', 'LAST_NAME'])
						->where('ID', $entityId)
						->setLimit(1)
						->fetch()
					;
					if ($row)
					{
						$title = trim(str_replace(
							['#NAME#', '#LAST_NAME#'],
							[$row['NAME'], $row['LAST_NAME']],
							Main\Context::getCurrent()->getCulture()->getFormatName()
						));
					}
					break;
			}
		}

		$rqEditUrl = "/bitrix/components/bitrix/crm.requisite.details/slider.ajax.php"
			. "?requisite_id={$rqId}"
			. "&pid={$presetId}"
			. "&etype={$entityTypeId}"
			. "&eid={$entityId}"
			. "&mode=" . ($rqId ? 'edit' : 'create')
			. "&doSave=Y"
			. "&" . bitrix_sessid_get()
		;

		foreach ($item->getFields() as $field)
		{
			$name = $field['name'];
			$value = $values[$name] ?? '';
			$field['value'] = $value;
			$field['valuePrintable'] = $value;
			$fieldEntityTypeId = $field['editing']['entityTypeId'];

			if (!$fieldEntityTypeId)
			{
				continue;
			}

			$field['editing']['url'] =
				($fieldEntityTypeId === \CCrmOwnerType::Requisite)
					? $rqEditUrl
					: Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl($fieldEntityTypeId, $entityId)
			;

			$fields[] = $field;
		}

		return [
			'id' => $item->getId(),
			'title' => $title,
			'fields' => $fields,
		];
	}

	public function getAction(int $id): array
	{
		$permissions = Crm\Service\Container::getInstance()->getUserPermissions();
		if (!$permissions->canReadConfig())
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_FIELDSET_READ_CONFIG_DENIED')));
			return [];
		}


		$item = (new Crm\FieldSet\Factory())->getItem($id);
		if (!$item)
		{
			$this->addError(new Main\Error('Unknown field set with id=' . $id));
			return [];
		}

		return [
			'options' => $item->getOptions(),
		];
	}

	public function setAction(array $options): array
	{
		$permissions = Crm\Service\Container::getInstance()->getUserPermissions();
		if (!$permissions->canWriteConfig())
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_FIELDSET_WRITE_CONFIG_DENIED')));
			return [];
		}

		$factory = new Crm\FieldSet\Factory();
		$item = (new Crm\FieldSet\Item())
			->setOptions($options)
		;
		$result = $factory->save($item);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [
			'options' => $item->getOptions(),
		];
	}
}