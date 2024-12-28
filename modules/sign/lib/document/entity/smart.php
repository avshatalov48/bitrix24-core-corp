<?php
namespace Bitrix\Sign\Document\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Document;
use Bitrix\Sign\Document\Member;
use Bitrix\Sign\Error;
use Bitrix\Sign\File;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Main\User;

Loc::loadMessages(__FILE__);

class Smart extends Dummy
{
	/**
	 * Class constructor.
	 *
	 * @param int|null $id Entity id
	 */
	public function __construct(?int $id = null)
	{
		if ($id !== null && Loader::includeModule('crm'))
		{
			$entityTypeId = \CCrmOwnerType::SmartDocument;
			$factory = Container::getInstance()->getFactory($entityTypeId);
			$this->item = $factory?->getItem($id);
		}
	}

	/**
	 * @param Item|null $item
	 * @return Smart
	 */
	public function setItem(?Item $item): self
	{
		$this->item = $item;
		return $this;
	}

	/**
	 * Returns current entity's id.
	 * @return int
	 */
	public function getId(): int
	{
		return $this->item ? $this->item->getId() : 0;
	}

	/**
	 * Returns current entity's number.
	 * @return int|string
	 */
	public function getNumber()
	{
		return $this->item ? $this->item->getNumber() : 0;
	}

	/**
	 * Refreshes entity number and returns new value.
	 * @return string|int|null
	 */
	public function refreshNumber()
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		if (!$this->item)
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartDocument);
		$field = $factory
			->getFieldsCollection()
			->getField(\Bitrix\Crm\Item\SmartDocument::FIELD_NAME_NUMBER)
		;
		$pseudoItem = $factory->createItem();
		$newValue = $field->processAfterSave($pseudoItem, $pseudoItem)->getNewValues()[$field->getName()];
		if ($newValue)
		{
			$this->item->set($field->getName(), $newValue);
			$factory
				->getUpdateOperation($this->item)
				->disableAllChecks()
				->launch()
			;

			return $newValue;
		}

		return null;
	}

	/**
	 * Returns current entity's title.
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return $this->item ? $this->item->getTitle() : null;
	}

	/**
	 * Saves new title to Document.
	 *
	 * @param string $title New title.
	 * @return bool
	 */
	public function setTitle(string $title): bool
	{
		if ($this->item)
		{
			$this->item->setTitle($title);
			$result = $this->item->save();

			return $result->isSuccess();
		}

		return false;
	}

	/**
	 * Returns current entity's stage.
	 * @return string|null
	 */
	public function getStageId(): ?string
	{
		return $this->item ? $this->item->getStageId() : null;
	}

	/**
	 * Returns entity contact's ids.
	 * @return int[]
	 */
	public function getContactsIds(): array
	{
		return $this->item ? $this->item->getContactIds() : [];
	}

	/**
	 * Returns entity base company id.
	 * @return int
	 */
	public function getCompanyId(): int
	{
		return $this->item ? $this->item->get('MYCOMPANY_ID') : 0;
	}

	/**
	 * Returns entity base company title.
	 * @return string|null
	 */
	public function getCompanyTitle(): ?string
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$companyId = $this->getCompanyId();

		if (!$companyId)
		{
			return null;
		}

		$entityTypeId = \CCrmOwnerType::Company;
		$factory = Container::getInstance()->getFactory($entityTypeId);
		$item = $factory->getItem($companyId);

		if (!$item)
		{
			return null;
		}

		return $item->getTitle();
	}

	/**
	 * Returns crm smart process' id.
	 * @return int
	 */
	public static function getEntityTypeId(): int
	{
		if (!Loader::includeModule('crm'))
		{
			return 0;
		}

		return \CCrmOwnerType::SmartDocument;
	}

	/**
	 * Returns entity detail url.
	 * @return string
	 */
	public static function getEntityDetailUrlId(): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		return '/crm/type/' . \CCrmOwnerType::SmartDocument . '/details/#id#/';
	}

	/**
	 * Returns default stage id.
	 * @param int $categoryId Category id.
	 * @return string|null
	 */
	public static function getDefaultStageId(int $categoryId): ?string
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$entityTypeId = self::getEntityTypeId();

		if (!$entityTypeId)
		{
			return null;
		}

		$container = Container::getInstance();
		$factory = $container->getFactory($entityTypeId);
		if (!$factory || !$factory->isStagesSupported())
		{
			return null;
		}

		$userId = User::getInstance()->getId();
		$stages = $factory->getStages($categoryId);

		return $container->getUserPermissions($userId)->getStartStageId(
			$entityTypeId,
			$stages,
			$categoryId,
			Service\UserPermissions::OPERATION_READ
		);
	}

	/**
	 * Actualize company requisites.
	 * @param Document $document Document.
	 * @return array
	 */
	public function actualizeCompanyRequisites(Document $document): array
	{
		$requisitesCode = $document::META_KEYS['companyRequisites'];
		$requisites = CRM::getRequisitesCompanyFieldSetValues($document);
		if ($requisites)
		{
			$document->setMeta([$requisitesCode => $requisites]);
		}

		return $requisites;
	}

	/**
	 * Calls after member was assigned to doc.
	 * @param Document $document Document instance.
	 * @return Result
	 */
	public function afterAssignMembers(Document $document): Result
	{
		$result = new Result();
		$initiator = $document->getMemberByPart(1);
		if (!$initiator)
		{
			return $result;
		}

		$counterAgent = $document->getMemberByPart(2);
		if (!CRM::getOtherSidePresetId($document->getEntityId()) && $counterAgent && $counterAgent->getContactId())
		{
			CRM::createDefaultRequisite(
				$document->getEntityId(),
				$counterAgent->getContactId(),
				\CCrmOwnerType::Contact
			);
		}

		$validatePresetsResult = $this->validatePresets($document);
		if (!$validatePresetsResult->isSuccess())
		{
			return $validatePresetsResult;
		}

		// actualize requisites(FieldSet) data
		$requisites = $document->actualizeCompanyRequisites();
		$document->setMeta([Document::META_KEYS['companyRequisites'] => $requisites ?? []]);

		// save company files to member
		$file = CRM::getCompanyStamp($this->getCompanyId());
		if ($file && $file->isExist())
		{
			$initiator->setStampFile(new File($file->getPath()));
		}

		// $file = CRM::getCompanySignature($this->getCompanyId());
		// if ($file && $file->isExist())
		// {
		// 	$initiator->setSignatureFile(new File($file->getPath()));
		// }

		$isSmsAllowed = \Bitrix\Sign\Restriction::isSmsAllowed();

		// set default communications to member
		foreach ($document->getMembers() as $member)
		{
			$communications = $this->getCommunications($member);
			if ($communications)
			{
				$communication = null;

				if ($isSmsAllowed)
				{
					foreach ($communications as $item)
					{
						if ($item['type'] === 'PHONE')
						{
							$communication = $item;
							break;
						}
					}
				}

				if (!$communication)
				{
					$communication = $communications[0];
				}

				$member->setCommunication($communication['type'], $communication['value']);
			}
		}

		return $result;
	}

	private function validatePresets(Document $document): Result
	{
		$myPresetId = CRM::getMyDefaultPresetId($document->getEntityId(), $document->getCompanyId());

		if ($myPresetId)
		{
			$validateResult = CRM::validatePresetFields($myPresetId);

			if (!$validateResult->isSuccess())
			{
				return $validateResult;
			}
		}

		$otherPresetId = CRM::getOtherSidePresetId($document->getEntityId());

		if ($otherPresetId)
		{
			$validateResult = CRM::validatePresetFields($otherPresetId);

			if (!$validateResult->isSuccess())
			{
				return $validateResult;
			}
		}

		return new Result();
	}

	/**
	 * Returns communications list for member instance.
	 * @param Member $member Member instance.
	 * @return array
	 */
	public function getCommunications(Member $member): array
	{
		if ($member->getContactId())
		{
			return CRM::getContactCommunications($member->getContactId());
		}
		else if ($this->getCompanyId())
		{
			return CRM::getCompanyCommunications($this->getCompanyId());
		}

		return [];
	}

	/**
	 * Creates new entity and returns its id.
	 * @return int|null
	 */
	public static function create(\Bitrix\Sign\Item\Document $document, bool $checkPermission = true): ?int
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}

		$entityTypeId = self::getEntityTypeId();

		if ($entityTypeId)
		{
			$userPermissions = Container::getInstance()->getUserPermissions();
			if ($checkPermission && !$userPermissions->checkAddPermissions(\CCrmOwnerType::SmartDocument))
			{
				Error::getInstance()->addError(
					'ERROR_CREATING_NEW',
					Loc::getMessage('SIGN_CORE_ENTITY_ACCESS_DENIED')
				);
				return null;
			}
			
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$item = $factory->createItem(self::getItemData($document));
				$operation = $factory->getAddOperation($item)
					->disableAllChecks()
					->disableAutomation()
					->disableBizProc()
					->disableSaveToTimeline()
				;

				$result = $operation->launch();
				if ($result->isSuccess())
				{
					return $item->getId();
				}

				Error::getInstance()->addFromResult($result);
			}
		}

		if (Error::getInstance()->isEmpty())
		{
			Error::getInstance()->addError(
				'ERROR_CREATING_NEW',
				Loc::getMessage('SIGN_CORE_ENTITY_SMART_ERROR_CREATING_NEW')
			);
		}

		return null;
	}

	protected static function getItemData(\Bitrix\Sign\Item\Document $document): array
	{
		return [];
	}
}
