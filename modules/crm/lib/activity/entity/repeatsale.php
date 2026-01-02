<?php

namespace Bitrix\Crm\Activity\Entity;

use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use CCrmActivity;
use CCrmContentType;

final class RepeatSale extends BaseActivity
{
	public function isValidProviderId(string $providerId): bool
	{
		return $this->provider::getId() === Provider\RepeatSale::getId()
			&& $providerId === Provider\RepeatSale::getId()
		;
	}

	public function getProviderId(): string
	{
		return Provider\RepeatSale::getId();
	}

	public function getProviderTypeId(): string
	{
		return Provider\RepeatSale::PROVIDER_TYPE_ID_DEFAULT;
	}

	public function setDescription(?string $description): self
	{
		$this->description = $description ?? '';
		
		return $this;
	}
	
	public function save(array $options = [], $useCurrentSettings = false): Result
	{
		$result = new Result();
		
		$fields = [
			'SUBJECT' => $this->getSubject(),
			'DESCRIPTION' => $this->getDescription(),
			'DESCRIPTION_TYPE' => CCrmContentType::BBCode,
			'RESPONSIBLE_ID' => $this->getResponsibleId(),
			'AUTHOR_ID' => $this->getAuthorId() ?? $this->getResponsibleId(),
		];
		
		if ($this->getDeadline())
		{
			$fields['START_TIME'] = $this->getDeadline()->toString();
			$fields['END_TIME'] = $this->getDeadline()->toString();
		}

		$fields = array_merge($fields, $this->getAdditionalFields());

		if ($useCurrentSettings)
		{
			$fields['SETTINGS'] = $this->getSettings();
		}
		
		if (
			$this->checkPermissions
			&& !CCrmActivity::CheckUpdatePermission($this->getOwner()->getEntityTypeId(), $this->getOwner()->getEntityId())
		)
		{
			$result->addError(ErrorCode::getAccessDeniedError());
			
			return $result;
		}

		if ($this->getId())
		{
			$existedActivity = CCrmActivity::GetList(
				[],
				[
					'=ID' => $this->getId(),
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				false,
				[
					'ID',
					'COMPLETED',
					'PROVIDER_ID',
				]
			)?->Fetch();
			if (!$existedActivity)
			{
				$result->addError(ErrorCode::getNotFoundError());
				
				return $result;
			}

			if (!$this->isValidProviderId($existedActivity['PROVIDER_ID']))
			{
				$result->addError(ErrorCode::getNotFoundError());
				
				return $result;
			}
			
			$isSuccess = CCrmActivity::Update($this->getId(), $fields, $this->checkPermissions, true, $options);
			if (!$isSuccess)
			{
				foreach (CCrmActivity::GetErrorMessages() as $errorMessage)
				{
					$result->addError(new Error($errorMessage));
				}
			}
		}
		else
		{
			$fields['BINDINGS'] = [
				[
					'OWNER_TYPE_ID' => $this->owner->getEntityTypeId(),
					'OWNER_ID' => $this->owner->getEntityId(),
				],
			];

			$provider = new Provider\RepeatSale();
			$result = $provider->createActivity($this->getProviderTypeId(), $fields, $options);
			if ($result->isSuccess())
			{
				$this->id = (int)$result->getData()['id'];
			}
		}
		
		return $result;
	}
}
