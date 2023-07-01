<?php

namespace Bitrix\Rpa\Components;

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\Dispatcher;
use Bitrix\Main\UserField\Types\DoubleType;
use Bitrix\Rpa\Command;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\Item;
use Bitrix\Rpa\Model\Stage;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\Rpa\UserField\UserField;

abstract class ItemDetail extends Base implements Controllerable
{
	/** @var Dispatcher */
	protected $userFieldDispatcher;
	/** @var Type */
	protected $type;
	/** @var Item */
	protected $item;
	/** @var Stage */
	protected $stage;
	protected $editorInitialMode;

	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('typeId', $arParams);
		$this->fillParameterFromRequest('id', $arParams);
		$this->fillParameterFromRequest('stageId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		if($this->getErrors())
		{
			return;
		}

		$command = null;

		$this->userFieldDispatcher = Dispatcher::instance();

		$typeId = (int) $this->arParams['typeId'];
		if($typeId > 0)
		{
			$this->type = TypeTable::getById($typeId)->fetchObject();
		}
		if(!$this->type)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('RPA_NOT_FOUND_ERROR'));
			return;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewType($typeId))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('RPA_TYPE_PERMISSION_ERROR'));
			return;
		}
		$id = (int) $this->arParams['id'];
		if($id > 0)
		{
			$this->item = $this->type->getItem($id);
			if(!$this->item)
			{
				$this->errorCollection[] = new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND'));
				return;
			}

			$this->stage = $this->item->getStage();
			$stageId = (int)($this->arParams['stageId'] ?? null);
			if($stageId > 0)
			{
				$stage = $this->type->getStages()->getByPrimary($stageId);
				if(!$stage)
				{
					$this->errorCollection[] = new Error(Loc::getMessage('RPA_STAGE_NOT_FOUND_ERROR'));
				}
				else
				{
					$this->item->setStageId($stage->getId());
					$this->editorInitialMode = 'edit';
				}
			}
		}
		else
		{
			$this->stage = $this->type->getStages()->getAll()[0];
			if(!$this->stage)
			{
				$this->errorCollection[] = new Error(Loc::getMessage('RPA_FIRST_STAGE_NOT_FOUND_ERROR'));
			}
			else
			{
				$this->item = $this->type->createItem();
			}
		}

		if(!$this->stage)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('RPA_STAGE_NOT_FOUND_ERROR'));
		}

		if(!$this->getErrors())
		{
			$checkAccessResult = $this->checkAccess();
			if(!$checkAccessResult->isSuccess())
			{
				$this->errorCollection->add($checkAccessResult->getErrors());
			}
		}
	}

	protected function checkAccess(): Result
	{
		if($this->item->getId() > 0)
		{
			$command = Driver::getInstance()->getFactory()->getUpdateCommand($this->item);
		}
		else
		{
			$command = Driver::getInstance()->getFactory()->getAddCommand($this->item);
		}

		return $command->checkAccess();
	}

	protected function prepareFormParams(): array
	{
		$params = [];

		$params['GUID'] = 'rpa-type-' . $this->type->getId() . '-item-' . $this->item->getId() . '-editor';
		$params['CONFIG_ID'] = $this->getEditorConfigId();
		$params['INITIAL_MODE'] = $this->editorInitialMode;
		$params['ENTITY_TYPE_NAME'] = $this->type->getName();
		$params['ENTITY_ID'] = $this->item->getId();
		$params['ENTITY_FIELDS'] = $this->prepareFormFields();
		$params['ENTITY_CONFIG'] = $this->prepareFormConfig();
		$params['ENTITY_DATA'] = $this->prepareFormData();
		$params['ENABLE_SECTION_EDIT'] = true;
		$params['ENABLE_SECTION_CREATION'] = false;
		$params['ENABLE_SECTION_DRAG_DROP'] = !$this->isEmbedded();
		$params['ENABLE_FIELDS_CONTEXT_MENU'] = !$this->isEmbedded();
		$params['ENABLE_PERSONAL_CONFIGURATION_UPDATE'] = false;
		$params['ENABLE_COMMON_CONFIGURATION_UPDATE'] = $this->isCommonConfigurationUpdateEnabled();
		$params['ENABLE_SETTINGS_FOR_ALL'] = false;
		$params['ENABLE_AJAX_FORM'] = true;
		$params['ENABLE_FIELD_DRAG_DROP'] = !$this->isEmbedded();
		$params['READ_ONLY'] = false;
		$params['ENABLE_MODE_TOGGLE'] = !$this->isEmbedded();
		$params['ENABLE_TOOL_PANEL'] = !$this->isEmbedded();
		$params['ENABLE_BOTTOM_PANEL'] = false;
		$params['ENABLE_USER_FIELD_CREATION'] = !$this->isEmbedded();
		$params['ENABLE_USER_FIELD_MANDATORY_CONTROL'] = true;
		$params['USER_FIELD_ENTITY_ID'] = $this->type->getItemUserFieldsEntityId();
		$params['USER_FIELD_PREFIX'] = $this->type->getItemUserFieldsEntityId();
		$params['USER_FIELD_CREATE_SIGNATURE'] = $this->userFieldDispatcher->getCreateSignature([
			'ENTITY_ID' => $this->type->getItemUserFieldsEntityId(),
		]);
		$params['COMPONENT_AJAX_DATA'] = [
			'COMPONENT_NAME' => $this->getName(),
			'ACTION_NAME' => 'save',
			'SIGNED_PARAMETERS' => $this->getSignedParameters(),
		];
		$params['IS_EMBEDDED'] = $this->isEmbedded();

		return $params;
	}

	protected function isEmbedded(): bool
	{
		return (isset($this->arParams['isEmbedded']) && $this->arParams['isEmbedded'] === true);
	}

	protected function prepareFormFields(): array
	{
		static $formFields;
		if($formFields === null)
		{
			$formFields = [];
			if($this->stage && !$this->isEmbedded() && $this->item->getId() > 0)
			{
				$userFields = $this->stage->getUserFieldCollection();
			}
			else
			{
				$userFields = $this->type->getUserFieldCollection();
			}

			$enumerationFields = [];
			foreach($userFields as $userField)
			{
				$fieldName = $userField->getName();
				$itemId = (int)$this->item->getId();

				$fieldInfo = [
					'USER_TYPE_ID' => $userField->getUserTypeId(),
					'ENTITY_ID' => $this->type->getItemUserFieldsEntityId(),
					'ENTITY_VALUE_ID' => $itemId,
					'FIELD' => $fieldName,
					'MULTIPLE' => $userField['MULTIPLE'],
					'MANDATORY' => $userField['MANDATORY'],
					'SETTINGS' => $userField['SETTINGS'] ?? null,
				];

				if($userField['USER_TYPE_ID'] === 'enumeration')
				{
					$enumerationFields[$fieldName] = $userField;
				}

				if($userField['USER_TYPE_ID'] === 'file')
				{
					$urlTemplate = Driver::getInstance()
						->getUrlManager()
						->getFileUrlTemplate($this->type->getId(), $itemId, $fieldName);
					$fieldInfo['ADDITIONAL']['URL_TEMPLATE'] = $urlTemplate;
				}

				$formFields[$fieldName] = [
					'name' => $fieldName,
					'title' => $userField->getTitle(),
					'type' => 'userField',
					'data' => ['fieldInfo' => $fieldInfo],
					'required' => $this->isFieldRequired($userField),
//					'editable' => $userField->isEditable(),
				];
			}

			if(!empty($enumerationFields))
			{
				$enumInfos = $this->prepareEnumerationInfos($enumerationFields);
				foreach($enumInfos as $fieldName => $enums)
				{
					if(isset($formFields[$fieldName]['data']['fieldInfo']))
					{
						$formFields[$fieldName]['data']['fieldInfo']['ENUM'] = $enums;
					}
				}
			}

			$formFields['ID'] = [
				'name' => 'ID',
				'title' => 'ID',
				'type' => 'number',
				'editable' => false,
			];

			$formFields['CREATED_BY'] = $this->getOwnUserFieldDescription('CREATED_BY');
			$formFields['UPDATED_BY'] = $this->getOwnUserFieldDescription('UPDATED_BY');
			$formFields['MOVED_BY'] = $this->getOwnUserFieldDescription('MOVED_BY');

			$formFields['CREATED_TIME'] = $this->getOwnDateTimeFieldDescription('CREATED_TIME');
			$formFields['UPDATED_TIME'] = $this->getOwnDateTimeFieldDescription('UPDATED_TIME');
			$formFields['MOVED_TIME'] = $this->getOwnDateTimeFieldDescription('MOVED_TIME');
		}

		return $formFields;
	}

	protected function getOwnUserFieldDescription(string $fieldName): array
	{
		return [
			'name' => $fieldName,
			'title' => Loc::getMessage('RPA_ITEM_' . $fieldName),
			'type' => 'user',
			'editable' => false,
			'data' => [
				'enableEditInView' => false,
				'formated' => $fieldName . '_FORMATTED_NAME',
				'position' => $fieldName . '_WORK_POSITION',
				'photoUrl' => $fieldName . '_PHOTO_URL',
				'showUrl' => $fieldName . '_SHOW_URL',
				'pathToProfile' => Driver::getInstance()->getUrlManager()->getUserPersonalUrlTemplate(),
			],
		];
	}

	protected function getOwnDateTimeFieldDescription(string $fieldName): array
	{
		return [
			'name' => $fieldName,
			'title' => Loc::getMessage('RPA_ITEM_' . $fieldName),
			'type' => 'datetime',
			'editable' => false,
			'data' => [
				'enableTime' => true,
			]
		];
	}

	protected function isFieldVisible(UserField $userField): bool
	{
		return true;
	}

	protected function isFieldRequired(UserField $userField): bool
	{
		return (
			$userField->isMandatoryByDefault() &&
			(
				$this->item->getId() > 0
				|| $this->isFieldVisible($userField)
			)
		);
	}

	protected function prepareEnumerationInfos(array $userFields)
	{
		$callbacks = $map = $results = [];

		foreach($userFields as $userField)
		{
			if(!isset($userField['USER_TYPE']['CLASS_NAME']))
			{
				continue;
			}

			$className = $userField['USER_TYPE']['CLASS_NAME'];
			if(!isset($callbacks[$className]))
			{
				$callbacks[$className] = [];
			}

			$callbacks[$className][] = $userField;
			$map[$userField['ID']] = $userField['FIELD_NAME'];
		}

		foreach($callbacks as $className => $callbackFields)
		{
			$enumResult = call_user_func([$className, 'GetListMultiple'], $callbackFields);
			while($enum = $enumResult->GetNext())
			{
				if(!isset($enum['USER_FIELD_ID']))
				{
					continue;
				}

				$fieldID = $enum['USER_FIELD_ID'];
				if(!isset($map[$fieldID]))
				{
					continue;
				}

				$fieldName = $map[$fieldID];
				if(!isset($results[$fieldName]))
				{
					$results[$fieldName] = [];
				}

				$results[$fieldName][] = ['ID' => $enum['~ID'], 'VALUE' => $enum['~VALUE']];
			}
		}

		return $results;
	}

	protected function prepareFormConfig(): array
	{
		$elements = [];
		$userFields = $this->type->getUserFieldCollection();
		foreach($userFields as $userField)
		{
			if($this->isFieldVisible($userField))
			{
				$elements[] = [
					'name' => $userField->getName(),
				];
			}
		}

		return [
			[
				'name' => 'main',
				'title' => Loc::getMessage('RPA_ITEM_EDITOR_MAIN_SECTION_TITLE'),
				'type' => 'section',
				'elements' => $elements,
				'data' => [
					'isChangeable' => true,
					'isRemovable' => false,
					'enableTitle' => !$this->isEmbedded(),
				],
				'enableTitle' => !$this->isEmbedded(),
			]
		];
	}

	protected function getTitle(): string
	{
		if($this->item->getId() > 0)
		{
			return $this->item->getName();
		}

		return Loc::getMessage(
			'RPA_ITEM_CREATE_TITLE', [
				'#TYPE#' => $this->type->getTitle(),
			]
		);
	}

	protected function prepareFormData(): array
	{
		$userFields = $this->type->getUserFieldCollection();
		$formFields = $this->prepareFormFields();

		$users = static::getUsers($this->item->getUserIds());

		$data = [
			'ID' => $this->item->getId(),
			'CREATED_TIME' => (string) $this->item->getCreatedTime(),
			'UPDATED_TIME' => (string) $this->item->getUpdatedTime(),
			'MOVED_TIME' => (string) $this->item->getMovedTime(),
		];

		foreach(['CREATED_BY', 'MOVED_BY', 'UPDATED_BY'] as $fieldName)
		{
			$userId = $this->item->get($fieldName);
			$data[$fieldName] = $userId;
			$data[$fieldName . '_FORMATTED_NAME'] = $users[$userId]['fullName'] ?? null;
			$data[$fieldName . '_WORK_POSITION'] = $users[$userId]['workPosition'] ?? null;
			$data[$fieldName . '_PHOTO_URL'] = $users[$userId]['photo'] ?? null;
			$data[$fieldName . '_SHOW_URL'] = $users[$userId]['link'] ?? null;
		}

		foreach($userFields as $userField)
		{
			$fieldName = $userField->getName();
			$fieldValue = $this->item->get($fieldName);

			if(is_array($fieldValue))
			{
				foreach($fieldValue as &$value)
				{
					$value = $this->prepareSingleValue($value);
				}
				unset($value);
			}
			else
			{
				$fieldValue = $this->prepareSingleValue($fieldValue);
			}

			$fieldData = $formFields[$fieldName] ?? null;

			if(!is_array($fieldData))
			{
				continue;
			}

			$isEmptyField = true;
			$fieldParams = $fieldData['data']['fieldInfo'];
			if((is_string($fieldValue) && $fieldValue !== '')
				|| (is_numeric($fieldValue) && $fieldValue !== 0)
				|| (is_array($fieldValue) && !empty($fieldValue))
				|| (is_object($fieldValue))
			)
			{
				if(is_array($fieldValue))
				{
					$fieldValue = array_values($fieldValue);
				}
				$fieldParams['VALUE'] = $fieldValue;
				$isEmptyField = false;
			}

			$fieldSignature = $this->userFieldDispatcher->getSignature($fieldParams);
			if($isEmptyField)
			{
				$data[$fieldName] = [
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => true
				];
			}
			else
			{
				$data[$fieldName] = [
					'VALUE' => $fieldValue,
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => false
				];
			}
		}

		return $data;
	}

	protected function prepareSingleValue($value)
	{
		if(is_float($value))
		{
			$value = sprintf('%f', $value);
			$value = rtrim($value, '0');
			$value = rtrim($value, '.');
		}
		elseif(is_object($value) && method_exists($value, '__toString'))
		{
			$value = $value->__toString();
		}

		return $value;
	}

	public function configureActions(): array
	{
		return [];
	}

	protected function processData(array $data): void
	{
		$prepareDataToSet = $this->prepareDataToSet($data);
		foreach($prepareDataToSet as $name => $value)
		{
			$this->item->set($name, $value);
		}
	}

	protected function prepareDataToSet(array $data): array
	{
		$setData = [];
		$userFields = $this->type->getUserFieldCollection();
		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->EditFormAddFields(
			$this->type->getItemUserFieldsEntityId(),
			$data,
			['FORM' => $data]
		);
		foreach($data as $name => $value)
		{
			$userField = $userFields->getByName($name);
			if($userField)
			{
				$currentValue = $this->item->get($userField->getName());
				$isValueEmpty = $userField->isValueEmpty($value);
				if ($userField->isValueEmpty($currentValue) && $isValueEmpty)
				{
					continue;
				}
				if ($isValueEmpty)
				{
					$value = $userField->prepareNullValue($value);
				}
				$deletedFieldName = $name . '_del';
				if(isset($data[$deletedFieldName]) && $userField->isBaseTypeFile())
				{
					if(is_array($data[$name]) && is_array($data[$deletedFieldName]))
					{
						$value = array_diff($data[$name], $data[$deletedFieldName]);
					}
					elseif(is_numeric($data[$name]) && (int) $data[$name] === (int) $data[$deletedFieldName])
					{
						$value = null;
					}
				}
				if(is_string($value) && $userField->getUserTypeId() === DoubleType::USER_TYPE_ID)
				{
					$value = str_replace(',', '.', $value);
				}
				$setData[$name] = $value;
			}
		}

		return $setData;
	}

	protected function getCommand(): Command
	{
		if($this->item->getId() > 0)
		{
			$command = Driver::getInstance()->getFactory()->getUpdateCommand($this->item);
		}
		else
		{
			$command = Driver::getInstance()->getFactory()->getAddCommand($this->item);
		}

		return $command;
	}

	protected function prepareCommand(Command $command): void
	{
		if(isset($this->arParams['eventId']) && is_string($this->arParams['eventId']) && !empty($this->arParams['eventId']))
		{
			$command->setPullEventId($this->arParams['eventId']);
		}
	}

	public function saveAction(array $data, string $eventId = ''): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}

		if(empty($data))
		{
			$this->errorCollection[] = new Error('No data');
			return null;
		}

		$this->processData($data);
		$command = $this->getCommand();
		if(!empty($eventId) && is_string($eventId))
		{
			$command->setPullEventId($eventId);
		}
		$this->prepareCommand($command);

		$result = $command->run();
		if($result->isSuccess())
		{
			$this->item = $command->getItem();
		}

		return $this->prepareEditorResult($result);
	}

	protected function listKeysSignedParameters(): array
	{
		return [
			'typeId',
			'id',
			'stageId',
			'isEmbedded',
			'eventId',
		];
	}

	protected function prepareEditorResult(Result $commandResult): ?array
	{
		if(!$commandResult->isSuccess())
		{
			$result = null;
			foreach($commandResult->getErrors() as $error)
			{
				if($error->getCode() === Command::ERROR_CODE_MANDATORY_FIELD_EMPTY)
				{
					//					if(!is_array($result))
					//					{
					//						$result = [
					//							'CHECK_ERRORS' => [],
					//						];
					//					}
					//					$data = $error->getCustomData();
					//					$result['CHECK_ERRORS'][$data['fieldName']] = $error->getMessage();
					$this->errorCollection[] = $error;
				}
				else
				{
					$this->errorCollection[] = $error;
				}
			}

			return $result;
		}

		$controller = new \Bitrix\Rpa\Controller\Item();

		return [
			'ENTITY_DATA' => $this->prepareFormData(),
			'SUCCESS' => 'Y',
			'ENTITY_ID' => $this->item->getId(),
			'item' => $controller->prepareItemData($this->item, [
				'withDisplay' => true,
				'withTasks' => true,
				'withUsers' => true,
			]),
		];
	}

	protected function getTypeId(): ?int
	{
		if($this->type)
		{
			return $this->type->getId();
		}

		return parent::getTypeId();
	}

	protected function getEditorConfigId(): string
	{
		if($this->isEmbedded())
		{
			return 'rpa-kanban-type-'.$this->getTypeId();
		}

		return 'rpa-type-'.$this->getTypeId();
	}

	protected function isCommonConfigurationUpdateEnabled(): bool
	{
		return Driver::getInstance()->getUserPermissions()->canModifyType($this->type->getId());
	}
}
