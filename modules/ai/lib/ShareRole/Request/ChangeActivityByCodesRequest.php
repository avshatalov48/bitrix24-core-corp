<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\ShareRole\Dto\ChangeActivityByCodesDto;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\RoleValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method ChangeActivityByCodesDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?ChangeActivityByCodesDto $object
 */
class ChangeActivityByCodesRequest extends BaseRequest
{
	public function __construct(
		protected BaseValidator $baseValidator,
		protected RoleValidator $roleValidator
	)
	{
	}

	protected function getObjectWithData(): ChangeActivityByCodesDto
	{
		$dto = new ChangeActivityByCodesDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->roleCodes = $this->getArray('selectedShareRolesCodes');
		$this->baseValidator->isNotEmptyArray($dto->roleCodes, 'selectedShareRolesCodes');

		$dto->roleCodes = array_unique($dto->roleCodes);

		$dto->roleIds = $this->roleValidator->getRoleByCodesNotSystems(
			$dto->roleCodes,
			'selectedShareRolesCodes'
		);

		$this->roleValidator->accessOnRoles($dto->roleIds, 'selectedShareRolesCodes', $dto->userId);
		$dto->needActivate = $this->getBool('needActivate', '1');

		return $dto;
	}
}
