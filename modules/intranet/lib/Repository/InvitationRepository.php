<?php

namespace Bitrix\Intranet\Repository;

use Bitrix\Intranet\Entity\Invitation;
use Bitrix\Intranet\Enum\InvitationType;
use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Internals\EO_Invitation;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Intranet\Exception\CreationFailedException;
use Bitrix\Main\ORM\Data\AddResult;

class InvitationRepository
{
	protected function create(Invitation $invitation): Invitation
	{
		/**
		 * @var AddResult $result
		 */
		$result = InvitationTable::add([
			'USER_ID' => $invitation->getUserId(),
			'ORIGINATOR_ID' => $invitation->getOriginatorId(),
			'INVITATION_TYPE' => $invitation->getType()?->value,
			'IS_INTEGRATOR' => $invitation->isIntegrator() ? 'Y' : 'N',
			'IS_MASS' => $invitation->isMass() ? 'Y' : 'N',
			'IS_DEPARTMENT' => $invitation->isDepartment() ? 'Y' : 'N',
			'IS_REGISTER' => $invitation->isRegister() ? 'Y' : 'N',
			'INITIALIZED' => $invitation->isInitialized() ? 'Y' : 'N',
		]);

		if ($result->isSuccess())
		{
			$invitation->setId($result->getId());

			return $invitation;
		}

		throw new CreationFailedException($result->getErrorCollection());
	}

	protected function update(Invitation $invitation): Invitation
	{
		if ((int)$invitation->getId() <= 0)
		{
			return $invitation;
		}

		$oldInvitation = $this->getById($invitation->getId());
		$updatedField = [];
		if ($invitation->getUserId() && $oldInvitation->getUserId() !== $invitation->getUserId())
		{
			$oldInvitation->setUserId($invitation->getUserId());
			$updatedField['userId'] = $invitation->getUserId();
		}

		if ($invitation->getOriginatorId() && $oldInvitation->getOriginatorId() !== $invitation->getOriginatorId())
		{
			$oldInvitation->setOriginatorId($invitation->getOriginatorId());
			$updatedField['originatorId'] = $invitation->getOriginatorId();
		}

		if ($invitation->getType() && $oldInvitation->getType() !== $invitation->getType())
		{
			$oldInvitation->setType($invitation->getType());
			$updatedField['type'] = $invitation->getType();
		}

		if (!is_null($invitation->isMass()) && $oldInvitation->isMass() !== $invitation->isMass())
		{
			$oldInvitation->setIsMass($invitation->isMass());
			$updatedField['isMass'] = $invitation->isMass();
		}

		if (!is_null($invitation->isIntegrator()) && $oldInvitation->isIntegrator() !== $invitation->isIntegrator())
		{
			$oldInvitation->setIsIntegrator($invitation->isIntegrator());
			$updatedField['isIntegrator'] = $invitation->isIntegrator();
		}

		if (!is_null($invitation->isDepartment()) && $oldInvitation->isDepartment() !== $invitation->isDepartment())
		{
			$oldInvitation->setIsDepartment($invitation->isDepartment());
			$updatedField['isDepartment'] = $invitation->isDepartment();
		}

		if (!is_null($invitation->isRegister()) && $oldInvitation->isRegister() !== $invitation->isRegister())
		{
			$oldInvitation->setIsRegister($invitation->isRegister());
			$updatedField['isRegister'] = $invitation->isRegister();
		}

		if (!empty($updatedField))
		{
			$model = InvitationTable::getById($oldInvitation->getId())->fetchObject();
			$result = $this->makeEntityByModel($model, $oldInvitation)->save();

			if (!$result->isSuccess())
			{
				throw new UpdateFailedException($result->getErrorCollection());
			}
		}

		return $invitation;
	}

	public function save(Invitation $invitation): Invitation
	{
		if ($invitation->getId())
		{
			return $this->update($invitation);
		}

		return $this->create($invitation);
	}

	public function getById(int $id): Invitation
	{
		//TODO: need to add a cache
		$model = InvitationTable::getById($id)->fetchObject();

		return $this->makeEntityByModel($model);
	}

	public function makeEntityByModel($model): Invitation
	{
		return new Invitation(
			$model->getUserId(),
			$model->getInitialized(),
			$model->isMass(),
			$model->isDepartment(),
			$model->isIntegrator(),
			$model->isRegister(),
			id: $model->getId(),
			originatorId: $model->getOriginatorId(),
			type: InvitationType::tryFrom($model->getInvitationType()),
		);
	}

	public function mapEntityToModel(EO_Invitation $model, Invitation $entity): EO_Invitation
	{
		$model
			->setUserId($entity->getUserId())
			->setOriginatorId($entity->getOriginatorId())
			->setInitialized($entity->isInitialized() ? 'Y' : 'N')
			->setInvitationType($entity->getType()?->value)
			->setIsMass($entity->isMass() ? 'Y' : 'N')
			->setIsDepartment($entity->isDepartment() ? 'Y' : 'N')
			->setIsRegister($entity->isRegister() ? 'Y' : 'N')
			->setIsIntegrator($entity->isIntegrator() ? 'Y' : 'N')
			;

		return $model;
	}
}