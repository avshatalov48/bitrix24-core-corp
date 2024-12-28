<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\ShareRole\Dto\DeleteRoleIdForUserDto;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\AI\Validator\RoleValidator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * @method DeleteRoleIdForUserDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?DeleteRoleIdForUserDto $object
 */
class ChangeDeleteForCurrentUserRequest extends BaseRequest
{
	public function __construct(
		protected RoleValidator $roleValidator,
		protected ShareService $shareService
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	protected function getObjectWithData(): DeleteRoleIdForUserDto
	{
		$dto = new DeleteRoleIdForUserDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->roleCode = $this->getString('roleCode');
		$dto->roleId = $this->getRoleByCode($dto->roleCode, 'roleCode');

		$dto->hasInOwnerTable = $this->roleValidator->accessOnRole(
			$dto->roleId,
			'roleCode',
			(int)$this->currentUser?->getId()
		);

		$dto->needDeleted = $this->getBool('needDeleted','1');

		return $dto;
	}

	protected function getRoleByCode(string $code, string $fieldName): int
	{
		return $this->roleValidator->getRoleByCode($code, $fieldName);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function prepareData(): DeleteRoleIdForUserDto
	{
		$this->object->shareType = $this->shareService->getShareType(
			$this->shareService->getAccessCodesForRole($this->object->roleId),
			(int)$this->currentUser?->getId()
		);

		return $this->object;
	}
}
