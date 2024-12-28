<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\ShareRole\Dto\CreateDto;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method CreateDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?CreateDto $object
 */
class ChangeRequest extends CreateRequest
{
	protected function getObjectWithData(): CreateDto
	{
		$dto = parent::getObjectWithData();
		$dto->roleCode = $this->getString('roleCode');

		if (empty($dto->roleAvatarFile))
		{
			$imagePath = $this->getString('roleAvatarUrl');
			$dto->roleAvatarPaths = [
				'small' => $imagePath,
				'medium' => $imagePath,
				'large' => $imagePath,
			];
		}

		$this->baseValidator->strRequire($dto->roleCode, 'roleCode');

		$dto->roleId = $this->roleValidator->getRoleIdNotSystemByCode($dto->roleCode, 'roleCode');

		$this->roleValidator->hasRoleIdInShare($dto->roleId, 'roleCode');
		$this->roleValidator->accessOnRole($dto->roleId, 'roleCode', (int)$this->currentUser?->getId());

		return $dto;
	}

	protected function checkCreator()
	{
	}
}
