<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\BaseRequest;

use Bitrix\AI\ShareRole\Dto\DeleteRoleIdForUserByCodesDto;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\RoleValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method DeleteRoleIdForUserByCodesDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?DeleteRoleIdForUserByCodesDto $object
 */
class ChangeDeleteForCurrentUserByCodesRequest extends BaseRequest
{

	public function __construct(
		protected BaseValidator $baseValidator,
		protected RoleValidator $roleValidator,
		protected ShareService $shareService
	)
	{
	}

	protected function getObjectWithData(): DeleteRoleIdForUserByCodesDto
	{
		$dto = new DeleteRoleIdForUserByCodesDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->roleCodes = $this->getArray('selectedShareRolesCodes');

		$this->baseValidator->isNotEmptyArray($dto->roleCodes, 'selectedShareRolesCodes');

		$dto->roleCodes = array_unique($dto->roleCodes);

		$dto->roleIds = $this->roleValidator->getRoleByCodesNotSystems(
			$dto->roleCodes,
			'selectedShareRolesCodes'
		);

		$dto->ownerIdsForSharingRoles = $this->roleValidator->accessOnRoles(
			$dto->roleIds,
			'selectedShareRolesCodes',
			$dto->userId
		);

		$dto->needDeleted = $this->getBool('needDeleted', '1');

		return $dto;
	}
}
