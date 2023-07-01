<?php

namespace Bitrix\Tasks\Control\Handler;

use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Control\Handler\Exception\TemplateFieldValidateException;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Main\Localization\Loc;

class TemplateFieldHandler
{
	private $fields;
	private $templateData;
	private $templateId;
	private $userId;

	/**
	 *
	 */
	public const DEPRECATED_FIELDS = [
		'ACCOMPLICES',
		'AUDITORS',
		'RESPONSIBLES',
		'TAGS',
		'DEPENDS_ON',
	];

	public function __construct(int $userId, array $fields, array $templateData = null)
	{
		$this->userId = $userId;
		$this->fields = $fields;
		$this->templateData = $templateData;

		$this->setTemplateId();
	}

	/**
	 * @return $this
	 */
	public function prepareTags(): self
	{
		if (
			!array_key_exists('TAGS', $this->fields)
			&& !array_key_exists('SE_TAG', $this->fields)
		)
		{
			return $this;
		}

		$tags = [];

		if (
			isset($this->fields['TAGS'])
			&& is_array($this->fields['TAGS'])
		)
		{
			$tags = $this->fields['TAGS'];
		}

		if (
			isset($this->fields['SE_TAG'])
			&& is_array($this->fields['SE_TAG'])
		)
		{
			foreach ($this->fields['SE_TAG'] as $tag)
			{
				if (empty($tag))
				{
					continue;
				}

				if (is_string($tag))
				{
					$tags[] = $tag;
				}
				else if (is_array($tag))
				{
					$tags[] = $tag['NAME'];
				}
			}
		}

		$this->fields['TAGS'] = array_unique($tags);

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareMultitask(): self
	{
		if (
			array_key_exists('RESPONSIBLES', $this->fields)
			&& is_array($this->fields['RESPONSIBLES'])
		)
		{
			if (count($this->fields['RESPONSIBLES']) > 1)
			{
				$this->fields['MULTITASK'] = 'Y';
			}
			else
			{
				$this->fields['MULTITASK'] = 'N';
			}
		}


		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareDependencies(): self
	{
		if (!array_key_exists('DEPENDS_ON', $this->fields))
		{
			return $this;
		}

		if (!is_array($this->fields['DEPENDS_ON']))
		{
			$this->fields['DEPENDS_ON'] = [];
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareDescription(): self
	{
		if (
			array_key_exists('DESCRIPTION', $this->fields)
			&& $this->fields['DESCRIPTION'] !== ''
		)
		{
			$this->fields['DESCRIPTION'] = Emoji::encode($this->fields['DESCRIPTION']);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareBBCodes(): self
	{
		if (
			$this->templateId
			&& !array_key_exists('DESCRIPTION_IN_BBCODE', $this->fields)
		)
		{
			return $this;
		}

		if (!array_key_exists('DESCRIPTION_IN_BBCODE', $this->fields))
		{
			$this->fields['DESCRIPTION_IN_BBCODE'] = 'Y';
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareSiteId(): self
	{
		if (
			$this->templateId
			&& !array_key_exists('SITE_ID', $this->fields)
		)
		{
			return $this;
		}

		if (
			(string)($this->fields['SITE_ID'] ?? null) === ''
			|| (string)($this->fields['SITE_ID'] ?? null) === \CTaskTemplates::CURRENT_SITE_ID
		)
		{
			$this->fields['SITE_ID'] = SITE_ID;
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function preparePriority(): self
	{
		if (
			isset($this->fields["PRIORITY"])
			&& !in_array($this->fields["PRIORITY"], [\CTasks::PRIORITY_LOW, \CTasks::PRIORITY_AVERAGE, \CTasks::PRIORITY_HIGH])
		)
		{
			$this->fields["PRIORITY"] = \CTasks::PRIORITY_AVERAGE;
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TemplateFieldValidateException
	 */
	public function prepareParentId()
	{
		if(
			array_key_exists('PARENT_ID', $this->fields)
			&& !intval($this->fields['PARENT_ID'])
		)
		{
			$this->fields['PARENT_ID'] = false;
		}

		if (
			isset($this->fields["PARENT_ID"])
			&& intval($this->fields["PARENT_ID"]) > 0
		)
		{
			$parentTask = TaskRegistry::getInstance()->get($this->fields["PARENT_ID"]);
			if (!$parentTask)
			{
				throw new TemplateFieldValidateException(Loc::getMessage("TASKS_BAD_PARENT_ID"));
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TemplateFieldValidateException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function prepareBaseTemplate(): self
	{
		if (!isset($this->fields['BASE_TEMPLATE_ID']))
		{
			return $this;
		}

		if (!intval($this->fields['BASE_TEMPLATE_ID']))
		{
			return $this;
		}

		try
		{
			$baseTemplate = TemplateTable::getById($this->fields['BASE_TEMPLATE_ID'])->fetch();

			if (!$baseTemplate)
			{
				throw new TemplateFieldValidateException(Loc::getMessage("TASKS_TEMPLATE_BASE_TEMPLATE_ID_NOT_EXISTS"));
			}

			// you cannot add a template with both PARENT_ID and BASE_TEMPLATE_ID set. BASE_TEMPLATE_ID has greather priority
			if(isset($this->fields['PARENT_ID']))
			{
				$this->fields['PARENT_ID'] = '';
			}

			// you cannot add REPLICATE parameters here in case of BASE_TEMPLATE_ID is set
			if(isset($this->fields['REPLICATE']))
			{
				$this->fields['REPLICATE'] = 'N';
			}

			$this->fields['REPLICATE_PARAMS'] = [];
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
			throw new TemplateFieldValidateException(Loc::getMessage("TASKS_TEMPLATE_BAD_BASE_TEMPLATE_ID"));
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareReplication(): self
	{
		if (
			$this->templateId
			&& !array_key_exists('REPLICATE', $this->fields)
			&& !array_key_exists('REPLICATE_PARAMS', $this->fields)
			&& !array_key_exists('TPARAM_REPLICATION_COUNT', $this->fields)
		)
		{
			return $this;
		}

		if(array_key_exists('TPARAM_REPLICATION_COUNT', $this->fields))
		{
			$this->fields['TPARAM_REPLICATION_COUNT'] = (int) $this->fields['TPARAM_REPLICATION_COUNT'];
		}
		elseif(!$this->templateId)
		{
			$this->fields['TPARAM_REPLICATION_COUNT'] = 0;
		}


		if (
			$this->templateId
			&& !array_key_exists('REPLICATE_PARAMS', $this->fields)
		)
		{
			return $this;
		}

		if (
			!array_key_exists('REPLICATE_PARAMS', $this->fields)
			|| empty($this->fields['REPLICATE_PARAMS'])
		)
		{
			$this->fields['REPLICATE_PARAMS'] = [];
		}

		if(
			is_string($this->fields['REPLICATE_PARAMS'])
			&& !empty($this->fields['REPLICATE_PARAMS'])
		)
		{
			$this->fields['REPLICATE_PARAMS'] = \Bitrix\Tasks\Util\Type::unSerializeArray($this->fields['REPLICATE_PARAMS']);
		}

		$this->fields['REPLICATE_PARAMS'] = \CTaskTemplates::parseReplicationParams($this->fields['REPLICATE_PARAMS']);

		return $this;
	}

	/**
	 * @return $this
	 * @throws TemplateFieldValidateException
	 */
	public function prepareTitle(): self
	{
		if (
			$this->templateId
			&& !array_key_exists('TITLE', $this->fields)
		)
		{
			return $this;
		}

		if (!array_key_exists('TITLE', $this->fields))
		{
			throw new TemplateFieldValidateException(Loc::getMessage('TASKS_BAD_TITLE'));
		}

		if (
			array_key_exists('TITLE', $this->fields)
			&& $this->fields['TITLE'] === ''
		)
		{
			throw new TemplateFieldValidateException(Loc::getMessage('TASKS_BAD_TITLE'));
		}

		$this->fields['TITLE'] = Emoji::encode($this->fields['TITLE']);

		return $this;
	}

	/**
	 * @return $this
	 * @throws TemplateFieldValidateException
	 */
	public function prepareResponsible(): self
	{
		if (
			$this->templateId
			&& !array_key_exists('RESPONSIBLES', $this->fields)
			&& !array_key_exists('RESPONSIBLE_ID', $this->fields)
		)
		{
			return $this;
		}

		if (
			!array_key_exists('RESPONSIBLES', $this->fields)
			&& !array_key_exists('RESPONSIBLE_ID', $this->fields)
		)
		{
			throw new TemplateFieldValidateException(Loc::getMessage("TASKS_BAD_RESPONSIBLE_ID"));
		}

		if (!array_key_exists('RESPONSIBLE_ID', $this->fields))
		{
			$this->fields['RESPONSIBLE_ID'] = (int) array_values($this->fields['RESPONSIBLES'])[0];
		}

		if (
			array_key_exists('RESPONSIBLES', $this->fields)
			&& is_string($this->fields['RESPONSIBLES'])
		)
		{
			$this->fields['RESPONSIBLES'] = unserialize($this->fields['RESPONSIBLES'], ['allowed_classes' => false]);
		}

		if (
			(
				!$this->templateId
				&& (int)($this->fields['TPARAM_TYPE'] ?? null) !== \CTaskTemplates::TYPE_FOR_NEW_USER
			)
			||
			(
				$this->templateId
				&& (int) $this->templateData['TPARAM_TYPE'] !== \CTaskTemplates::TYPE_FOR_NEW_USER
			)
		)
		{
			if(isset($this->fields["RESPONSIBLE_ID"]))
			{

				$r = \CUser::GetByID($this->fields["RESPONSIBLE_ID"]);
				if (!$r->Fetch())
				{
					throw new TemplateFieldValidateException(Loc::getMessage("TASKS_BAD_RESPONSIBLE_ID_EX"));
				}
			}
			else
			{
				if(!$this->templateId)
				{
					throw new TemplateFieldValidateException(Loc::getMessage("TASKS_BAD_RESPONSIBLE_ID"));
				}
			}
		}

		if (
			!$this->templateId
			&& empty($this->fields['RESPONSIBLES'])
		)
		{
			$this->fields['RESPONSIBLES'] = [$this->fields['RESPONSIBLE_ID']];
		}
		elseif (
			empty($this->fields['RESPONSIBLES'])
			&& empty($this->templateData['RESPONSIBLES'])
		)
		{
			$this->fields['RESPONSIBLES'] = [$this->fields['RESPONSIBLE_ID']];
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareMembers(): self
	{
		if (
			array_key_exists('ACCOMPLICES', $this->fields)
			&& is_string($this->fields['ACCOMPLICES'])
		)
		{
			$this->fields['ACCOMPLICES'] = unserialize($this->fields['ACCOMPLICES'], ['allowed_classes' => false]);
		}

		if (
			array_key_exists('AUDITORS', $this->fields)
			&& is_string($this->fields['AUDITORS'])
		)
		{
			$this->fields['AUDITORS'] = unserialize($this->fields['AUDITORS'], ['allowed_classes' => false]);
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TemplateFieldValidateException
	 */
	public function prepareType(): self
	{
		if (
			$this->templateId
			&& !array_key_exists('TPARAM_TYPE', $this->fields)
		)
		{
			return $this;
		}

		if (
			$this->templateId
			&& (int) $this->fields['TPARAM_TYPE'] !== (int) $this->templateData['TPARAM_TYPE']
		)
		{
			throw new TemplateFieldValidateException('You can not change TYPE of an existing template');
		}

		if (
			($this->fields['TPARAM_TYPE'] ?? null)
			&& (int) $this->fields['TPARAM_TYPE'] !== \CTaskTemplates::TYPE_FOR_NEW_USER
		)
		{
			throw new TemplateFieldValidateException('Unknown template type id passed');
		}

		if (
			(
				!$this->templateId
				&& (int)($this->fields['TPARAM_TYPE'] ?? null) === \CTaskTemplates::TYPE_FOR_NEW_USER
			)
			||
			(
				$this->templateId
				&& (int)($this->templateData['TPARAM_TYPE'] ?? null) === \CTaskTemplates::TYPE_FOR_NEW_USER
			)
		)
		{
			$this->fields['BASE_TEMPLATE_ID'] = '';
			$this->fields['REPLICATE_PARAMS'] = [];
			$this->fields['RESPONSIBLE_ID'] = '0';
			$this->fields['RESPONSIBLES'] = [0];
			$this->fields['MULTITASK'] = 'N';
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getFields(): array
	{
		return $this->fields;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFieldsToDb(): array
	{
		$fields = $this->fields;

		$tableFields = TemplateTable::getEntity()->getFields();

		foreach ($fields as $fieldName => $value)
		{
			if (!array_key_exists($fieldName, $tableFields))
			{
				unset($fields[$fieldName]);
				continue;
			}

			if (in_array($fieldName, self::DEPRECATED_FIELDS))
			{
				unset($fields[$fieldName]);
				continue;
			}

			if (preg_match('/^UF_/', $fieldName))
			{
				unset($fields[$fieldName]);
				continue;
			}

			if (
				$tableFields[$fieldName] instanceof DatetimeField
				&& !empty($value)
			)
			{
				$fields[$fieldName] = \Bitrix\Main\Type\DateTime::createFromUserTime($value);
			}

			if (is_array($value))
			{
				$fields[$fieldName] = serialize($value);
			}
		}

		return $fields;
	}

	/**
	 * @return void
	 */
	private function setTemplateId()
	{
		if (
			$this->templateData
			&& array_key_exists('ID', $this->templateData)
		)
		{
			$this->templateId = (int) $this->templateData['ID'];
		}
	}
}