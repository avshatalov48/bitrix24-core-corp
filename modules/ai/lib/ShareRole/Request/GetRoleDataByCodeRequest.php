<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\ShareRole\Dto\RoleUserDto;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\RoleValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method RoleUserDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?RoleUserDto $object
 */
class GetRoleDataByCodeRequest extends BaseRequest
{

	public function __construct(
		protected BaseValidator $baseValidator,
		protected RoleValidator $roleValidator,
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	protected function getObjectWithData(): RoleUserDto
	{
		$dto = new RoleUserDto();

		$dto->userId = (int)$this->currentUser?->getId();
		$dto->roleCode = $this->getString('roleCode');

		$this->baseValidator->strRequire($dto->roleCode, 'roleCode');

		$dto->roleId = $this->roleValidator->getRoleIdNotSystemByCode($dto->roleCode, 'roleCode');

		$this->roleValidator->accessOnRole(
			$dto->roleId,
			'roleCode',
			(int)$this->currentUser?->getId()
		);

		return $dto;
	}
}
