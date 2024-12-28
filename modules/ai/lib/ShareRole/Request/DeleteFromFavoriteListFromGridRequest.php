<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\ShareRole\Dto\RoleUserDto;
use Bitrix\AI\Validator\RoleValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method RoleUserDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?RoleUserDto $object
 */
class DeleteFromFavoriteListFromGridRequest extends BaseRequest
{

	public function __construct(
		protected RoleValidator $roleValidator
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
		$dto->roleId = $this->roleValidator->getRoleByCode($dto->roleCode, 'roleCode');

		$this->roleValidator->hasInFavoriteList(
			$dto->roleCode,
			'roleCode',
			(int)$this->currentUser?->getId()
		);

		return $dto;
	}
}
