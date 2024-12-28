<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\ShareRole\Dto\ChangeActivityDto;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\RoleValidator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * @method ChangeActivityDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?ChangeActivityDto $object
 */
class ChangeActivityRequest extends BaseRequest
{

	public function __construct(
		protected BaseValidator $baseValidator,
		protected RoleValidator $roleValidator,
		protected ShareService $shareService
	)
	{
	}
	/**
	 * @inheritDoc
	 */
	protected function getObjectWithData(): ChangeActivityDto
	{
		$dto = new ChangeActivityDto();

		$dto->userId = (int)$this->currentUser?->getId();
		$dto->roleCode = $this->getString('roleCode');
		$this->baseValidator->strRequire($dto->roleCode,'roleCode');

		$dto->roleId = $this->roleValidator->getRoleIdNotSystemByCode($dto->roleCode, 'roleCode');
		$this->roleValidator->hasRoleIdInShare($dto->roleId, 'roleCode');
		$this->roleValidator->accessOnRole($dto->roleId, 'roleCode', $dto->userId);

		$dto->needActivate = $this->getBool('needActivate', '1');

		return $dto;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function prepareData(): ChangeActivityDto
	{
		$this->object->shareType = $this->shareService->getShareType(
			$this->shareService->getAccessCodesForRole($this->object->roleId),
			(int)$this->currentUser?->getId()
		);

		return $this->object;
	}
}
