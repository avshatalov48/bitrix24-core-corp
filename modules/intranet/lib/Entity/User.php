<?php

namespace Bitrix\Intranet\Entity;

use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Intranet\Infrastructure\UserNameFormatter;
use Bitrix\Intranet\Integration\HumanResources\HrUserService;
use Bitrix\Intranet\Internal\Repository\Mapper\UserMapper;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\UserTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Socialnetwork\Collab\CollabFeature;

class User
{
	public function __construct(
		private ?int $id = null,
		private ?string $login = null,
		private ?string $email = null,
		private ?string $name = null,
		private ?string $lastName = null,
		private ?string $confirmCode = null,
		private ?array $groupIds = null,
		private ?string $xmlId = null,
		private ?bool $active = null,
		private ?string $externalAuthId = null,
		private ?string $authPhoneNumber = null,
		private ?string $secondName = null,
		private ?int $personalPhoto = null,
		private ?string $lid = null,
		private ?string $languageId = null,
		private ?string $personalMobile = null,
		private ?string $password = null,
		private mixed $ufCrmEntity = null, //UF_USER_CRM_ENTITY
		private ?Date $lastLogin = null,
	)
	{
	}

	public static function initByArray(array $userData): self
	{
		return (new UserMapper())->convertFromArray($userData);
	}

	public function toArray(): array
	{
		return (new UserMapper())->convertToArray($this);
	}

	public function getUfCrmEntity(): mixed
	{
		return $this->ufCrmEntity;
	}

	public function setUfCrmEntity(mixed $ufCrmEntity): void
	{
		$this->ufCrmEntity = $ufCrmEntity;
	}

	public function getLanguageId(): ?string
	{
		return $this->languageId;
	}

	public function setLanguageId(?string $languageId): void
	{
		$this->languageId = $languageId;
	}

	public function getLid(): ?string
	{
		return $this->lid;
	}

	public function setLid(?string $lid): void
	{
		$this->lid = $lid;
	}

	public function getAuthPhoneNumber(): ?string
	{
		return $this->authPhoneNumber;
	}

	public function setAuthPhoneNumber(?string $authPhoneNumber): void
	{
		$this->authPhoneNumber = $authPhoneNumber;
	}

	public function getPersonalMobile(): ?string
	{
		return $this->personalMobile;
	}

	public function setPersonalMobile(?string $personalMobile): void
	{
		$this->personalMobile = $personalMobile;
	}

	public function getInviteStatus(): InvitationStatus
	{
		if (empty($this->confirmCode) && $this->active)
		{
			return InvitationStatus::ACTIVE;
		}
		elseif (!empty($this->confirmCode) && $this->active)
		{
			return InvitationStatus::INVITED;
		}
		elseif (ModuleManager::isModuleInstalled('bitrix24') && !empty($this->confirmCode) && !$this->active)
		{
			return InvitationStatus::INVITE_AWAITING_APPROVE;
		}
		else
		{
			return InvitationStatus::FIRED;
		}
	}

	public function getRole(): UserRole
	{
		if ($this->isIntegrator())
		{
			return UserRole::INTEGRATOR;
		}
		elseif ($this->isAdmin())
		{
			return UserRole::ADMIN;
		}
		elseif ($this->isIntranet())
		{
			return UserRole::INTRANET;
		}
		elseif ($this->isCollaber())
		{
			return UserRole::COLLABER;
		}
		elseif (
			$this->isExtranet()
			&& (in_array(\CExtranet::getExtranetUserGroupId(), \CUser::GetUserGroup($this->getId())))
			&& \Bitrix\Extranet\PortalSettings::getInstance()->isExtranetUsersAvailable()
		)
		{
			return UserRole::EXTRANET;
		}
		elseif ($this->isEmail())
		{
			return UserRole::EMAIL;
		}
		elseif ($this->isShop())
		{
			return UserRole::SHOP;
		}
		elseif ($this->isExternal())
		{
			return UserRole::EXTERNAL;
		}
		else
		{
			return UserRole::VISITOR;
		}
	}

	public function isIntegrator(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24')
			&& in_array($this->id, ServiceContainer::getInstance()->getUserService()->getIntegratorUserIds());
	}

	public function isAdmin(): bool
	{
		return in_array($this->id, ServiceContainer::getInstance()->getUserService()->getAdminUserIds());
	}

	public function isCollaber(): bool
	{
		return Loader::includeModule('socialnetwork')
			&& CollabFeature::isOn()
			&& Loader::includeModule('extranet')
			&& \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById($this->id);
	}

	public function getFormattedName(
		bool $useLogin = false,
		bool $useHtmlSpec = true,
	): string
	{
		return (new UserNameFormatter($this, $useLogin, $useHtmlSpec))->formatByCulture();
	}

	public function isEmail(): bool
	{
		return $this->externalAuthId === 'email';
	}

	public function isShop(): bool
	{
		return in_array($this->externalAuthId, ['shop', 'sale', 'saleanonymous']);
	}

	public function isExternal(): bool
	{
		return in_array($this->externalAuthId, UserTable::getExternalUserTypes());
	}

	public function getExternalAuthId(): ?string
	{
		return $this->externalAuthId;
	}

	public function setExternalAuthId(?string $externalAuthId): void
	{
		$this->externalAuthId = $externalAuthId;
	}

	public function getActive(): ?bool
	{
		return $this->active;
	}

	public function setActive(?bool $active): void
	{
		$this->active = $active;
	}

	public function getXmlId(): ?string
	{
		return $this->xmlId;
	}

	public function setXmlId(?string $xmlId): void
	{
		$this->xmlId = $xmlId;
	}

	public function getPhoneNumber(): ?string
	{
		return $this->getAuthPhoneNumber();
	}

	public function setPhoneNumber(?string $phoneNumber): void
	{
		$this->setAuthPhoneNumber($phoneNumber);
	}

	public function getGroupIds(): ?array
	{
		return $this->groupIds;
	}

	public function setGroupIds(?array $groupIds): void
	{
		$this->groupIds = $groupIds;
	}

	public function getConfirmCode(): ?string
	{
		return $this->confirmCode;
	}

	public function setConfirmCode(?string $confirmCode): void
	{
		$this->confirmCode = $confirmCode;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): void
	{
		$this->id = $id;
	}

	public function getLogin(): ?string
	{
		return $this->login;
	}

	public function setLogin(?string $login): void
	{
		$this->login = $login;
	}

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail(?string $email): void
	{
		$this->email = $email;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(?string $name): void
	{
		$this->name = $name;
	}

	public function getLastName(): ?string
	{
		return $this->lastName;
	}

	public function setLastName(?string $lastName): void
	{
		$this->lastName = $lastName;
	}

	public function getSecondName(): ?string
	{
		return $this->secondName;
	}

	public function isExtranet(): bool
	{
		return (
			ModuleManager::isModuleInstalled('extranet')
			&& !(new HrUserService())->isEmployee($this)
		);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isIntranet(): bool
	{
		return (new HrUserService())->isEmployee($this);
	}

	public function getAccessCode(): string
	{
		return 'U' . $this->getId();
	}

	public function setPassword(string $password): void
	{
		$this->password = $password;
	}

	public function getPassword(): ?string
	{
		return $this->password;
	}

	public function getPersonalPhoto(): ?int
	{
		return $this->personalPhoto;
	}

	public function setPersonalPhoto(?int $personalPhoto): void
	{
		$this->personalPhoto = $personalPhoto;
	}

	public function getLastLogin(): ?Date
	{
		return $this->lastLogin;
	}

	public function setLastLogin(?Date $lastLogin): void
	{
		$this->lastLogin = $lastLogin;
	}
}