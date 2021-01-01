<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\WebForm;

/**
 * Class Form
 * @package Bitrix\Crm\Controller
 */
class Form extends Main\Engine\JsonController
{
	/**
	 * List forms action.
	 *
	 * @param array $filter Filter.
	 * @return array|null
	 */
	public function listAction($filter = ['active' => true])
	{
		$this->checkFormAccess();

		$ormFilter = [];
		if (isset($filter['active']))
		{
			$ormFilter['=ACTIVE'] = $filter['active'] ? 'Y' : 'N';
		}
		if (isset($filter['isCallback']))
		{
			$ormFilter['=IS_CALLBACK'] = $filter['isCallback'] ? 'Y' : 'N';
		}

		$result = WebForm\Internals\FormTable::getList([
			'select' => ['ID', 'NAME'],
			'filter' => $ormFilter,
			'order' => ['ID' => 'DESC'],
		]);

		return array_map('array_change_key_case', $result->fetchAll());
	}

	/**
	 * Get form action.
	 *
	 * @param int $id Form ID.
	 * @return array
	 */
	public function getAction($id)
	{
		$this->checkFormAccess();
		return WebForm\Options::create($id)->getArray();
	}

	/**
	 * Get dict.
	 *
	 * @return array
	 */
	public function getDictAction()
	{
		$this->checkFormAccess();
		return WebForm\Options\Dictionary::instance()->toArray();
	}

	/**
	 * Save form action.
	 *
	 * @param array $options Options.
	 * @return array
	 */
	public function saveAction(array $options)
	{
		$this->checkFormAccess(true);

		$formOptions = WebForm\Options::createFromArray($options);
		(new WebForm\FieldSynchronizer())->replaceOptionFields($formOptions);
		$result = $formOptions->save();
		$this->addErrors($result->getErrors());
		if ($result->getErrorCollection()->isEmpty())
		{
			return WebForm\Options::create($formOptions->getForm()->getId())->getArray();
		}

		return $formOptions->getArray();
	}

	/**
	 * Prepare form action.
	 *
	 * @param array $options Options.
	 * @param array $preparing Preparing data.
	 * @return array
	 */
	public function prepareAction(array $options, array $preparing)
	{
		$this->checkFormAccess(true);

		if (!empty($preparing['fields']) && is_array($preparing['fields']))
		{
			$fieldNames = [];
			foreach ($preparing['fields'] as $field)
			{
				if (!is_array($field) || empty($field['name']))
				{
					continue;
				}

				$fieldNames[] = $field['name'];
			}

			if (!empty($options['data']['fields']))
			{
				$options['data']['fields'] = array_filter(
					$options['data']['fields'],
					function ($field) use ($fieldNames)
					{
						return in_array($field['name'], $fieldNames);
					}
				);
			}
		}

		if (!empty($preparing['agreements']) && is_array($preparing['agreements']))
		{
			$existed = [];
			foreach ($preparing['agreements'] as $item)
			{
				if ($item && is_numeric($item))
				{
					$existed[] = $item;
					continue;
				}

				if (!is_array($item) || empty($item['id']))
				{
					continue;
				}

				$existed[] = $item['id'];
			}

			if (!empty($options['data']['agreements']))
			{
				$options['data']['agreements'] = array_filter(
					$options['data']['agreements'],
					function ($item) use ($existed)
					{
						return in_array($item['id'], $existed);
					}
				);
			}
		}

		$formOptions = WebForm\Options::createFromArray($options);

		if (!empty($preparing['agreements']) && is_array($preparing['agreements']))
		{
			$existed = array_column($options['data']['agreements'] ?? [], 'id');
			foreach ($preparing['agreements'] as $agreement)
			{
				$id = is_numeric($agreement) ? $agreement : null;
				$id = !empty($agreement['id']) ? $agreement['id'] : $id;
				if (!in_array($id, $existed))
				{
					$existed[] = $id;
					$formOptions->getConfig()->appendAgreement($id);
				}
			}
		}

		if (!empty($preparing['fields']) && is_array($preparing['fields']))
		{
			$fieldNames = array_column($options['data']['fields'] ?? [], 'name');
			foreach ($preparing['fields'] as $field)
			{
				if (!is_array($field))
				{
					continue;
				}

				if (!empty($field['name']) && in_array($field['name'], $fieldNames))
				{
					continue;
				}

				if (!empty($field['name']) || !empty($field['type']))
				{
					//if (in_array($field['type'], ['br', 'hr', '']))
					$formOptions->getConfig()->appendField($field);
				}
			}
		}

		//$result = $formOptions->save();
		//$this->addErrors($result->getErrors());

		return $formOptions->getArray();
	}

	/**
	 * Check form action.
	 *
	 * @param array $options Options.
	 * @return array
	 */
	public function checkAction(array $options)
	{
		$this->checkFormAccess(true);


		$schemeId = (int) $options['document']['scheme'] ?? null;
		if (!$schemeId || empty($options['data']['fields']) || !is_array($options['data']['fields']))
		{
			return [];
		}

		$fieldNames = [];
		foreach ($options['data']['fields'] as $field)
		{
			if (!is_array($field) || empty($field['name']))
			{
				continue;
			}

			$fieldNames[] = $field['name'];
		}


		if(!in_array($schemeId, WebForm\Entity::getSchemesCodes()))
		{
			return [];
		}

		$syncErrors = [];
		$syncFields = [];
		$fieldNames = (new WebForm\FieldSynchronizer())->getSynchronizeFields($schemeId, $fieldNames);
		foreach ($options['data']['fields'] as $field)
		{
			if ($field['type'] === 'resourcebooking' && !WebForm\Entity::isSchemeSupportEntity($schemeId, (int) $field['editing']['entityId']))
			{
				$syncErrors[] = Main\Localization\Loc::getMessage(
					'CRM_WEBFORM_FIELD_SYNCHRONIZER_ERR_RES_BOOK',
					[
						'%fieldCaption%' => $field['label'],
						'%entityCaption%' => implode(
							', ',
							array_map(
								function ($entityName)
								{
									return \CCrmOwnerType::getCategoryCaption(\CCrmOwnerType::resolveID($entityName));
								},
								WebForm\Entity::getSchemes($schemeId)['ENTITIES'] ?? []
							)
						),
					]
				);
				continue;
			}

			if (!is_array($field) || empty($field['name']) || !in_array($field['name'], $fieldNames))
			{
				continue;
			}

			$syncFields[] = $field;
		}

		return [
			'sync' => [
				'errors' => $syncErrors,
				'fields' => $syncFields,
			]
		];
	}

	/**
	 * Set editor ID action.
	 *
	 * @param int $editorId Editor ID.
	 * @return void
	 */
	public function setEditorAction($editorId)
	{
		$this->checkFormAccess();
		Crm\Settings\WebFormSettings::getCurrent()->setEditorId($editorId);
	}

	protected function checkFormAccess($write = false)
	{
		$access = new \CCrmPerms($this->getCurrentUser()->getId());
		if($access->havePerm('WEBFORM', BX_CRM_PERM_NONE, $write ? 'WRITE' : 'READ'))
		{
			throw new Main\AccessDeniedException();
		}
	}
}
