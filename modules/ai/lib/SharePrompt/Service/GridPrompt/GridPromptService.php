<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\GridPrompt;

use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Integration\Socialnetwork\GroupService;
use Bitrix\AI\SharePrompt\Repository\GridPromptRepository;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\GridParamsDto;
use Bitrix\AI\SharePrompt\Repository\ShareRepository;
use Bitrix\AI\SharePrompt\Repository\UserRepository;
use Bitrix\AI\SharePrompt\Service\CategoryService;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\SharingInfoDto;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\GridPromptDto;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\ShareDto;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Enum\OrderEnum;
use Bitrix\Intranet\User\Grid\Row\Assembler\Field\Helpers\UserPhoto;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\AccessRights\DataProvider;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\UI\AccessRights\Entity\AccessRightEntityInterface;
use Bitrix\Main\UI\AccessRights\Entity\UserAll;
use Bitrix\Main\UI\AccessRights\Exception\UnknownEntityTypeException;
use Bitrix\AI\SharePrompt\Repository\UserAccessRepository;

class GridPromptService
{
	use UserPhoto;
	use TranslateTrait;

	const MAX_SHARE_DATA = 5;

	protected DataProvider $accessRightsDataProvider;

	protected string $allUsersEntityName;

	protected string $nameFormat;

	public function __construct(
		protected GridPromptRepository $gridPromptRepository,
		protected ShareRepository $shareRepository,
		protected UserRepository $userRepository,
		protected CategoryService $categoryService,
		protected DateFormatService $dateFormatService,
		protected UserAccessRepository $userAccessRepository,
		protected GroupService $groupService
	)
	{
	}

	public function fillPromptIdsInFilter(int $userId, GridParamsDto $params): void
	{
		$params->filter->promptIds = $this->getIdsFromList(
			$this->gridPromptRepository->getAvailablePromptsForGrid($userId, $params)
		);

		if (empty($params->filter->promptIds) || empty($params->filter->share))
		{
			return;
		}

		$params->filter->promptIds = $this->getIdsFromList(
			$this->gridPromptRepository->getAvailablePromptsForGridWithUserInShare(
				$params->filter->share, $params->filter->promptIds
			)
		);
	}

	/**
	 * @param int $userId
	 * @param GridParamsDto $params
	 * @return array|GridPromptDto[]
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function getPromptsForGrid(int $userId, GridParamsDto $params): array
	{
		if (empty($params->filter->promptIds))
		{
			return [];
		}

		$prompts = $this->gridPromptRepository->getPromptsForGrid($userId, $params);
		if (empty($prompts))
		{
			return [];
		}

		list($gridPromptDtoList, $sharingInfoDto) = $this->fillSharing($prompts, $userId);

		if ($sharingInfoDto->isEmptyUsersIdList())
		{
			return $gridPromptDtoList;
		}

		return $this->intExtraSortPrompts(
			$this->fillSharingUsersInfo($gridPromptDtoList, $sharingInfoDto),
			$params
		);
	}

	/**
	 * @param GridPromptDto[] $gridPromptDtoList
	 * @param SharingInfoDto $sharingInfoDto
	 * @return GridPromptDto[]
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function fillSharingUsersInfo(array $gridPromptDtoList, SharingInfoDto $sharingInfoDto): array
	{
		$this->fillUsersSharingInfo($sharingInfoDto);

		if ($sharingInfoDto->isEmptyUsers())
		{
			return $gridPromptDtoList;
		}

		foreach ($gridPromptDtoList as $gridPromptDto)
		{
			$userIdList = $gridPromptDto->getUserIdsInShare();
			if (empty($userIdList))
			{
				continue;
			}

			$countInShare = $gridPromptDto->getCountInFillShare();
			if (empty($gridPromptDto->getCountShare()) || $countInShare >= static::MAX_SHARE_DATA)
			{
				continue;
			}

			foreach (array_slice($userIdList, 0, static::MAX_SHARE_DATA - $countInShare) as $userId)
			{
				$shareDto = $sharingInfoDto->getUserById($userId);
				if (!empty($shareDto))
				{
					$gridPromptDto->addInShare(
						$this->getCodeForUser($userId),
						$shareDto
					);
				}
			}
		}

		return $gridPromptDtoList;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function fillUsersSharingInfo(SharingInfoDto $sharingInfoDto): SharingInfoDto
	{
		$usersData = $this->userRepository->getMainUserData($sharingInfoDto->getUserIdList());
		if (empty($usersData))
		{
			return $sharingInfoDto;
		}

		$format = $this->getNameFormat();

		foreach ($usersData as $userData)
		{
			$sharingInfoDto->addByUserData(
				(int)$userData['ID'],
				new ShareDto(
					\CUser::formatName(
						$format,
						[
							'NAME' => $userData['NAME'] ?? '',
							'LAST_NAME' => $userData['LAST_NAME'] ?? '',
							'SECOND_NAME' => $userData['SECOND_NAME'] ?? '',
							'EMAIL' => $userData['EMAIL'] ?? '',
							'ID' => $userData['ID'] ?? '',
							'LOGIN' => $userData['LOGIN'] ?? '',
						],
						true,
						false
					),
					$this->getCodeForUser((int)$userData['ID']),
					$this->getUserPhotoUrl([
						'PERSONAL_PHOTO' => $userData['PERSONAL_PHOTO'],
						'PERSONAL_GENDER' => $userData['PERSONAL_GENDER']
					])
				)
			);
		}

		return $sharingInfoDto;
	}

	/**
	 * @param list<array{ ID: string, SHARE_CODES: string}> $prompts
	 * @param int $userId
	 * @return array{GridPromptDto[], SharingInfoDto}
	 */
	protected function fillSharing(array $prompts, int $userId): array
	{
		$sharingInfoDto = $this->getSharingInfoDto();
		$format = $this->getNameFormat();

		$result = [];
		foreach ($prompts as $prompt)
		{
			$gridPromptDto = $this->getGridPromptDto(
				$this->prepareForDTO($prompt, $format)
			);

			if (empty($prompt['SHARE_CODES']))
			{
				continue;
			}

			$codesInPrompt = explode(',', $prompt['SHARE_CODES']);
			if (empty($codesInPrompt))
			{
				continue;
			}

			$this->updateSharingInfo($codesInPrompt, $sharingInfoDto, $gridPromptDto, $userId);

			$result[] = $gridPromptDto;
		}

		return [$result, $sharingInfoDto];
	}

	protected function updateSharingInfo(
		array $codesInPrompt,
		SharingInfoDto $sharingInfoDto,
		GridPromptDto $gridPromptDto,
		int $userId
	): void
	{
		$userGroups = array_flip($this->userAccessRepository->getCodesForUserGroup($userId));
		$accessRightsDataProvider = $this->getAccessRightsDataProvider();
		foreach (array_unique($codesInPrompt) as $code)
		{
			if (UserAccessRepository::CODE_ALL_USER == $code)
			{
				$gridPromptDto->setShare(
					new ShareDto($this->getAllUsersEntityName(), $code)
				);

				break;
			}

			$gridPromptDto->incrementCountShare();

			if ($gridPromptDto->getCountInFillShare() >= static::MAX_SHARE_DATA)
			{
				continue;
			}

			if ($sharingInfoDto->hasCode($code))
			{
				$gridPromptDto->addInShare($code, $sharingInfoDto->getByCode($code));
				continue;
			}

			$accessCode = new AccessCode($code);
			if ($accessCode->getEntityType() === AccessCode::TYPE_USER)
			{
				$gridPromptDto->addUserIdInShare(
					$accessCode->getEntityId()
				);
				$sharingInfoDto->addUserId($accessCode->getEntityId());
				continue;
			}

			try
			{
				$entity = $accessRightsDataProvider->getEntity(
					$accessCode->getEntityType(),
					$accessCode->getEntityId()
				);
			}
			catch (UnknownEntityTypeException $exception)
			{
				$this->log('Not Found ' . $accessCode->getEntityType());
				continue;
			}

			$sharingInfoDto->addForCodes(
				$this->getShareDTO($entity, $userGroups, $code)
			);

			$gridPromptDto->addInShare($code, $sharingInfoDto->getByCode($code));
		}
	}

	protected function getShareDTO(AccessRightEntityInterface $entity, array $userGroups, string $code): ShareDto
	{
		if (!array_key_exists($entity->getId(), $this->groupService->getNotVisibleGroupListInKeys()))
		{
			return new ShareDto($entity->getName(), $code, $entity->getAvatar());
		}

		$name = $entity->getName();
		$img = $entity->getAvatar();

		if (!array_key_exists($code, $userGroups))
		{
			$name = $this->getHiddenName();
			$img = null;
		}

		return new ShareDto($name, $code, $img);
	}

	protected function prepareForDTO(array $prompt, string $format): array
	{
		$prompt['AUTHOR'] = \CUser::formatName($format, [
			'NAME' => $prompt['AUTHOR_NAME'] ?? '',
			'LAST_NAME' => $prompt['AUTHOR_LAST_NAME'] ?? '',
			'SECOND_NAME' => $prompt['AUTHOR_SECOND_NAME'] ?? '',
			'EMAIL' => $prompt['AUTHOR_EMAIL'] ?? '',
			'ID' => $prompt['AUTHOR_ID'] ?? '',
			'LOGIN' => $prompt['AUTHOR_LOGIN'] ?? '',
		], true, false);

		$prompt['EDITOR'] = \CUser::formatName($format, [
			'NAME' => $prompt['EDITOR_NAME'] ?? '',
			'LAST_NAME' => $prompt['EDITOR_LAST_NAME'] ?? '',
			'SECOND_NAME' => $prompt['EDITOR_SECOND_NAME'] ?? '',
			'EMAIL' => $prompt['EDITOR_EMAIL'] ?? '',
			'ID' => $prompt['EDITOR_ID'] ?? '',
			'LOGIN' => $prompt['EDITOR_LOGIN'] ?? '',
		], true, false);

		if (!empty($prompt['AUTHOR_PHOTO_ID']) && array_key_exists('AUTHOR_GENDER', $prompt))
		{
			$prompt['AUTHOR_PHOTO_URL'] = $this->getUserPhotoUrl([
				'PERSONAL_PHOTO' => $prompt['AUTHOR_PHOTO_ID'],
				'PERSONAL_GENDER' => $prompt['AUTHOR_GENDER']
			]);
		}

		if (!empty($prompt['EDITOR_PHOTO_ID']) && array_key_exists('EDITOR_GENDER', $prompt))
		{
			$prompt['EDITOR_PHOTO_URL'] = $this->getUserPhotoUrl([
				'PERSONAL_PHOTO' => $prompt['EDITOR_PHOTO_ID'],
				'PERSONAL_GENDER' => $prompt['EDITOR_GENDER']
			]);
		}

		if (is_string($prompt['CATEGORIES']))
		{
			$prompt['CATEGORIES'] = $this->categoryService->getCategoriesWithNameByCodes(
				array_unique(explode(',', $prompt['CATEGORIES']))
			);
		}

		if (!empty($prompt['DATE_CREATE']) && $prompt['DATE_CREATE'] instanceof DateTime)
		{
			$prompt['DATE_CREATE_STRING'] = $this->dateFormatService->formatDate(
				$prompt['DATE_CREATE']->getTimestamp()
			);
		}

		if (!empty($prompt['DATE_MODIFY']) && $prompt['DATE_MODIFY'] instanceof DateTime)
		{
			$prompt['DATE_MODIFY_STRING'] = $this->dateFormatService->formatDate(
				$prompt['DATE_MODIFY']->getTimestamp()
			);
		}

		return $prompt;
	}

	protected function getSharingInfoDto(): SharingInfoDto
	{
		return new SharingInfoDto();
	}

	protected function getGridPromptDto(array $prompt): GridPromptDto
	{
		return new GridPromptDto($prompt);
	}

	protected function log(string $text): void
	{
		AddMessage2Log('AI_GRID_PROMPT_SERVICE_ERROR: ' . $text);
	}

	protected function getAllUsersEntityName(): string
	{
		if (empty($this->allUsersEntityName))
		{
			$this->allUsersEntityName = (new UserAll(0))->getName();
		}

		return $this->allUsersEntityName;
	}

	protected function getCodeForUser(int $userId): string
	{
		return 'U' . $userId;
	}

	protected function getIdsFromList(array $list): array
	{
		if (empty($list))
		{
			return [];
		}

		$result = [];
		foreach ($list as $item)
		{
			if (!empty($item['ID']))
			{
				$result[] = (int)$item['ID'];
			}
		}

		return array_unique($result);
	}

	protected function getAccessRightsDataProvider(): DataProvider
	{
		if (empty($this->accessRightsDataProvider))
		{
			$this->accessRightsDataProvider = new DataProvider();
		}

		return $this->accessRightsDataProvider;
	}

	protected function getNameFormat(): string
	{
		if (empty($this->nameFormat))
		{
			$this->nameFormat = \CSite::GetNameFormat();
		}

		return $this->nameFormat;
	}

	protected function getHiddenName(): string
	{
		return Loc::getMessage('AI_SERVICE_GRID_HIDDEN_ITEM_TITLE') ?? '';
	}

	/**
	 * @param GridPromptDto[] $gridPromptDtoList
	 * @param GridParamsDto $params
	 * @return GridPromptDto[]
	 */
	protected function intExtraSortPrompts(array $gridPromptDtoList, GridParamsDto $params): array
	{
		if (empty($params->order[0]) || empty($params->order[1]))
		{
			return $gridPromptDtoList;
		}

		$fieldForOrder = $params->order[0];

		if ($fieldForOrder !== OrderEnum::AUTHOR->value && $fieldForOrder !== OrderEnum::EDITOR->value)
		{
			return $gridPromptDtoList;
		}

		return $this->sortPrompts(
			$gridPromptDtoList,
			$fieldForOrder,
			$params->order[1]
		);
	}

	/**
	 * @param GridPromptDto[] $gridPromptDtoList
	 * @param string $fieldForOrder
	 * @param string $order
	 * @return array
	 */
	private function sortPrompts(array $gridPromptDtoList, string $fieldForOrder, string $order): array
	{
		usort($gridPromptDtoList, function (GridPromptDto $a, GridPromptDto $b) use ($fieldForOrder, $order) {
			$aProperty = $a->getAuthor();
			$bProperty = $b->getAuthor();
			if ($fieldForOrder === OrderEnum::EDITOR->value)
			{
				$aProperty = $a->getEditor();
				$bProperty = $b->getEditor();
			}


			if ($aProperty == $bProperty)
			{
				return 0;
			}

			if ($order === OrderEnum::ASC)
			{
				return ($aProperty < $bProperty) ? -1 : 1;
			} else
			{
				return ($aProperty > $bProperty) ? -1 : 1;
			}
		});

		return $gridPromptDtoList;
	}
}
