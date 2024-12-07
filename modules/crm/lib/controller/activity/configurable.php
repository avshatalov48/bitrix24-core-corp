<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Activity\Entity\AppTypeTable;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Rest\AppTable;

class Configurable extends Base
{
	private const VALID_TRUE_VALUES = ['Y', 'y', true, 1, '1', 'true'];
	private const VALID_FALSE_VALUES = ['N', 'n', false, 0, '0', 'false'];

	public function getAction(int $id): ?array
	{
		$activity = ConfigurableRestApp::load($id);
		if (!$activity)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		return [
			'activity' => [
				'id' => $activity->getId(),
				'ownerTypeId' => $activity->getOwner()->getEntityTypeId(),
				'ownerId' => $activity->getOwner()->getEntityId(),
				'fields' => [
					'typeId' => $activity->getTypeId(),
					'completed' => $activity->getCompleted(),
					'deadline' => $activity->getDeadline(),
					'pingOffsets' => $activity->getPingOffsets(),
					'isIncomingChannel' => $activity->getIsIncomingChannel(),
					'responsibleId' => $activity->getResponsibleId(),
					'badgeCode' => $activity->getBadgeCode(),
					'originatorId' => $activity->getOriginatorId(),
					'originId' => $activity->getOriginId(),
				],
				'layout' => $activity->getLayoutDto(),
			]
		];
	}

	public function addAction(\CRestServer $server, int $ownerTypeId, int $ownerId, array $fields, array $layout): ?array
	{
		$activity = new ConfigurableRestApp(new ItemIdentifier($ownerTypeId, $ownerId));

		$this->saveActivity($activity, $fields, $layout, $server);
		if (!empty($this->getErrors()))
		{
			return null;
		}

		return [
			'activity' => [
				'id' => $activity->getId(),
			]
		];
	}

	public function updateAction(\CRestServer $server, int $id, ?array $fields = null, ?array $layout = null)
	{
		$activity = ConfigurableRestApp::load($id);
		if (!$activity)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		$this->saveActivity($activity, $fields, $layout, $server);
		if (count($this->getErrors()))
		{
			return null;
		}

		return [
			'activity' => [
				'id' => $activity->getId(),
			]
		];
	}

	public function emitRestEventAction(string $signedParams): void
	{
		if ($this->getScope() !== static::SCOPE_AJAX)
		{
			$this->addError(new Error('Rest scope is not supported', 'WRONG_SCOPE'));

			return;
		}
		$result = \Bitrix\Crm\Activity\Entity\ConfigurableRestApp\EventHandler::emitEvent($signedParams);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	private function saveActivity(ConfigurableRestApp $activity, ?array $fields = null, ?array $layout = null, \CRestServer $server): void
	{
		if ($server->getAuthType() !== \Bitrix\Rest\OAuth\Auth::AUTH_TYPE)
		{
			$this->addError(static::getWrongContextError());
		}

		$restAppClientId = $server->getClientId();
		if ($activity->getId())
		{
			if ($activity->getRestClientId() !== $restAppClientId)
			{
				$this->addError(new Error(Loc::getMessage('CRM_CONFIGURABLE_REST_APP_ERROR_WRONG_APPLICATION'), 'ERROR_WRONG_APPLICATION'));
			}
		}
		elseif ($restAppClientId)
		{
			$activity->setRestClientId($restAppClientId);
			$fields['restAppClientId'] = $restAppClientId;
		}
		else
		{
			$this->addError(static::getWrongContextError());
		}

		$validateFieldsResult = $this->validateFields($fields);
		if (!$validateFieldsResult->isSuccess())
		{
			$this->addErrors($validateFieldsResult->getErrors());
		}

		$layoutDto = null;
		if (!empty($layout))
		{
			$layoutDto = new ConfigurableRestApp\Dto\LayoutDto($layout);
			if ($layoutDto->hasValidationErrors())
			{
				$this->addErrors($layoutDto->getValidationErrors()->toArray());
			}
		}
		elseif (!$activity->getId())
		{
			$this->addError(new Error(Loc::getMessage('CRM_CONFIGURABLE_REST_APP_ERROR_EMPTY_LAYOUT'), 'ERROR_EMPTY_LAYOUT'));
		}

		if (empty($this->getErrors()))
		{
			if ($layoutDto)
			{
				$activity->setLayoutDto($layoutDto);
			}
			if (isset($fields['completed']))
			{
				$activity->setCompleted($this->castToBool($fields['completed']));
			}
			if (isset($fields['deadline']) && (string)$fields['deadline'] !== '')
			{
				$activity->setDeadline($this->prepareDatetime((string)$fields['deadline']));
			}
			if (isset($fields['isIncomingChannel']))
			{
				$activity->setIsIncomingChannel($this->castToBool($fields['isIncomingChannel']));
			}
			if (isset($fields['responsibleId']))
			{
				$activity->setResponsibleId((int)$fields['responsibleId']);
			}
			if (isset($fields['pingOffsets']))
			{
				$pingOffsets = is_array($fields['pingOffsets']) ? $fields['pingOffsets'] : [];
				$pingOffsets = array_unique(array_map('intval', $pingOffsets));
				$activity->setPingOffsets($pingOffsets);
			}
			if (isset($fields['badgeCode']))
			{
				$activity->setBadgeCode((string)$fields['badgeCode']);
			}
			if (isset($fields['originatorId']))
			{
				$activity->setOriginatorId((string)$fields['originatorId']);
			}
			if (isset($fields['originId']))
			{
				$activity->setOriginId((string)$fields['originId']);
			}
			if (isset($fields['typeId']))
			{
				$activity->setTypeId($fields['typeId']);
			}

			if (empty($this->getErrors()))
			{
				$saveResult = $activity->save();
				if (!$saveResult->isSuccess())
				{
					$this->addErrors($saveResult->getErrors());
				}
			}
		}
	}

	public static function getWrongContextError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_CONFIGURABLE_REST_APP_ERROR_WRONG_CONTEXT'),
			'ERROR_WRONG_CONTEXT',
		);
	}

	private function validateConfigurableType(?array $fields, Result $result)
	{
		if (!(isset($fields['restAppClientId']) && $fields['restAppClientId']))
		{
			$this->addWrongFieldValueError('typeId', $result);
		}
		else
		{
			$app = AppTable::getList(
				[
					'filter' => ['=CLIENT_ID' => $fields['restAppClientId']],
					'select' => ['ID'],
				]
			)->fetch();

			$row =  AppTypeTable::getList([
				'filter' => [
					'=APP_ID' => $app['ID'] ?? 0,
					'=TYPE_ID' => $fields['typeId'],
					'=IS_CONFIGURABLE_TYPE' => 'Y',
				],
				'select' => ['ID'],
				'limit' => 1,
			])->fetch();

			$typeId = (int)($row['ID'] ?? 0);

			if ($typeId <= 0)
			{
				$this->addWrongFieldValueError('typeId', $result);
			}
		}
	}

	private function validateFields(?array $fields): Result
	{
		$result = new Result();
		$allowedBooleanValues = array_merge(
			self::VALID_TRUE_VALUES,
			self::VALID_FALSE_VALUES
		);

		if (isset($fields['completed']) && !in_array($fields['completed'], $allowedBooleanValues, true))
		{
			$this->addWrongFieldValueError('completed', $result);
		}

		if (isset($fields['isIncomingChannel']) && !in_array($fields['isIncomingChannel'], $allowedBooleanValues, true))
		{
			$this->addWrongFieldValueError('isIncomingChannel', $result);
		}

		if (isset($fields['isIncomingChannel']) && isset($fields['deadline']) && !empty($fields['deadline']) && $this->castToBool($fields['isIncomingChannel']))
		{
			$result->addError(new Error(
				(string)Loc::getMessage('CRM_CONFIGURABLE_REST_APP_INCOMING_NO_DEADLINE'),
				'INCOMING_ACTIVITY_CAN_NOT_BE_WITH_DEADLINE'
			));
		}

		if (isset($fields['pingOffsets']))
		{
			if (!is_array($fields['pingOffsets']))
			{
				$this->addWrongFieldValueError('pingOffsets', $result);
			}
			else
			{
				foreach ($fields['pingOffsets'] as $index => $pingOffsetValue)
				{
					if (!is_scalar($pingOffsetValue) || (int)$pingOffsetValue < 0)
					{
						$this->addWrongFieldValueError("pingOffsets[$index]", $result);
					}
				}
			}
		}

		if (isset($fields['responsibleId']) && (int)$fields['responsibleId'] <= 0)
		{
			$this->addWrongFieldValueError('responsibleId', $result);
		}

		if (
			isset($fields['badgeCode'])
			&& $fields['badgeCode'] !== ''
			&& !\Bitrix\Crm\Badge\Model\CustomBadgeTable::query()
				->setSelect(['ID'])
				->where('CODE', (string)$fields['badgeCode'])
				->setLimit(1)
				->fetch()
		)
		{
			$this->addWrongFieldValueError('badgeCode', $result);
		}

		if (isset($fields['typeId']))
		{
			if (
				!is_string($fields['typeId'])
				|| $fields['typeId'] === ''
			)
			{
				$this->addWrongFieldValueError('typeId', $result);
			}
			else if ($fields['typeId'] !== \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::PROVIDER_TYPE_ID_DEFAULT)
			{
				$this->validateConfigurableType($fields, $result);
			}
		}

		return $result;
	}

	private function addWrongFieldValueError(string $fieldName, Result $result): void
	{
		$result->addError(new Error(
			Loc::getMessage('CRM_CONFIGURABLE_REST_APP_ERROR_WRONG_FIELD_VALUE', ['#FIELD#' => $fieldName]),
			'WRONG_FIELD_VALUE'
		));
	}

	private function castToBool($value): bool
	{
		return in_array($value, self::VALID_TRUE_VALUES, true);
	}
}
