<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\SharePrompt\Dto\CreateDto;
use Bitrix\AI\SharePrompt\Model\OwnerTable;
use Bitrix\AI\SharePrompt\Model\ShareTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Data\AddResult;

class ShareRepository extends BaseRepository
{
	public function __construct(
		protected UserAccessRepository $userAccessRepository
	)
	{
	}

	/**
	 * @param int $promptId
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findByPromptId(int $promptId): array
	{
		return ShareTable::query()
			->setSelect(['ID'])
			->where('PROMPT_ID', $promptId)
			->setLimit(1)
			->fetchAll()
		;
	}

	/**
	 * @param CreateDto $createRequestDTO
	 * @return AddResult
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function create(CreateDto $createRequestDTO): AddResult
	{
		$rows = [];
		foreach ($createRequestDTO->accessCodesData as $code)
		{
			$rows[] = [
				'PROMPT_ID' => $createRequestDTO->promptId,
				'ACCESS_CODE' => $code,
				'DATE_CREATE' => $createRequestDTO->dateCreate,
				'CREATED_BY' => $createRequestDTO->userCreatorId,
			];
		}

		return ShareTable::addMulti($rows, true);
	}

	/**
	 * @param int[] $promptId
	 * @param int $userId
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public function getInfoAccessPrompt(array $promptId, int $userId): array
	{
		return ShareTable::query()
			->setSelect([
				'SHARE_ID' => 'ID',
				'PROMPT_ID',
				'OWNER_ID' => 'PROMPT_OWNERS.ID',
			])
			->registerRuntimeField(new Reference(
				'PROMPT_OWNERS',
				OwnerTable::class,
				Join::on('this.PROMPT_ID', 'ref.PROMPT_ID')
					->where('ref.USER_ID', '=', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->whereIn('PROMPT_ID', $promptId)
			->where($this->userAccessRepository->getAccessConditionsForUser($userId, ''))
			->fetchAll();
	}

	public function deletePromptId(int $promptId): void
	{
		ShareTable::deleteByFilter([
			'PROMPT_ID' => $promptId
		]);
	}

	/**
	 * @param int $promptId
	 * @return list<array{ACCESS_CODE: string}>
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getAccessCodesForPrompt(int $promptId): array
	{
		return ShareTable::query()
			->setSelect(['ACCESS_CODE'])
			->where('PROMPT_ID', $promptId)
			->fetchAll()
		;
	}
}
