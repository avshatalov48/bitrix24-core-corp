<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Crm;

class FieldSet extends Main\Engine\JsonController
{
	public function listAction(): array
	{
		return [];
	}

	public function loadAction(int $entityTypeId, int $entityId): array
	{
		$item = Crm\Integration\Sign\Form::getFieldSet($entityTypeId);
		if (!$item)
		{
			$this->addError(new Main\Error("FieldSet not found for entity type `$entityTypeId`"));
			return [];
		}

		$fields = [];
		$values = Crm\Integration\Sign\Form::getFieldSetValues(
			$entityTypeId,
			$entityId,
			['appendExtended' => true]
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
			if ($fieldEntityTypeId === \CCrmOwnerType::Requisite)
			{
				$field['editing']['url'] = $rqEditUrl;
			}

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
			$this->addError(new Main\Error('User permissions `read crm-config` required.'));
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
			$this->addError(new Main\Error('User permissions `write crm-config` required.'));
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