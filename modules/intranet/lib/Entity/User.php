<?php

namespace Bitrix\Intranet\Entity;

use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\UserTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\Collab\CollabFeature;

class User
{
	public function __construct(
		private ?int $id = null,
		private ?array  $departmetnsIds = null,
		private ?string $login = null,
		private ?string $email = null,
		private ?string $name = null,
		private ?string $lastName = null,
		private ?string $confirmCode = null,
		private ?array  $groupIds = null,
		private ?string $phoneNumber = null,
		private ?string $xmlId = null,
		private ?bool   $active = null,
		private ?string $externalAuthId = null,
		private ?string $authPhoneNumber = null,
	)
	{}

	public function getAuthPhoneNumber(): ?string
	{
		return $this->authPhoneNumber;
	}

	public function setAuthPhoneNumber(?string $authPhoneNumber): void
	{
		$this->authPhoneNumber = $authPhoneNumber;
	}

	public static function initByArray(array $userData): self
	{
		$departmetnsIds = null;
		$departments = $userData['UF_DEPARTMENT'] ?? null;
		if (is_array($departments))
		{
			$departmetnsIds = $departments;
		}
		elseif ((int)$departments > 0)
		{
			$departmetnsIds = [(int)$departments];
		}

		$active = null;
		if (!empty($userData['ACTIVE']))
		{
			$active = $userData['ACTIVE'] === 'Y' ? true : false;
		}

		return new \Bitrix\Intranet\Entity\User(
			$userData['ID'] ?? null,
				$departmetnsIds,
			$userData['LOGIN'] ?? null,
			$userData['EMAIL'] ?? null,
			$userData['NAME'] ?? null,
			$userData['LAST_NAME'] ?? null,
			$userData["CONFIRM_CODE"] ?? null,
			$userData['GROUP_ID'] ?? null,
			$userData['PHONE_NUMBER'] ?? null,
			$userData['XML_ID'] ?? null,
				$active,
			$userData['EXTERNAL_AUTH_ID'] ?? null,
			$userData['AUTH_PHONE_NUMBER'] ?? null,
		);
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
		elseif (!empty($this->confirmCode) && !$this->active)
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
		return in_array($this->id, ServiceContainer::getInstance()->getUserService()->getIntegratorUserIds());
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
		return $this->phoneNumber;
	}

	public function setPhoneNumber(?string $phoneNumber): void
	{
		$this->phoneNumber = $phoneNumber;
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

	public function getDepartmetnsIds(): ?array
	{
		return $this->departmetnsIds;
	}

	public function setDepartmetnsIds(?array $departmetnsIds): void
	{
		$this->departmetnsIds = $departmetnsIds;
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

	public function isExtranet(): bool
	{
		return (
			ModuleManager::isModuleInstalled('extranet')
			&& (
				empty($this->departmetnsIds)
				|| (
					is_array($this->departmetnsIds)
					&& (int)$this->departmetnsIds[0] <= 0
				)
			)
		);
	}

	public function isIntranet(): bool
	{
		return $this->getDepartmetnsIds()
			&& (
				(
					!empty($this->getDepartmetnsIds())
					&& (int)$this->getDepartmetnsIds()[0] > 0
				)
			);
	}

	public function getAccessCode(): string
	{
		return 'U' . $this->getId();
	}
}