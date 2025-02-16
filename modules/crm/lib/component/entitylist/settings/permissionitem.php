<?php

namespace Bitrix\Crm\Component\EntityList\Settings;

use Bitrix\Crm\Integration\Analytics\Builder\Security\ViewEvent;
use Bitrix\Crm\Security\Role\Manage\RoleManagerSelectionFactory;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Buttons\JsHandler;

final class PermissionItem implements \JsonSerializable, Arrayable
{
	public const ID = 'permission-item';
	public const DELIMITER_ID = 'permission-item-delimiter';

	private array $analytics = [];

	public function __construct(
		private readonly ?RoleSelectionManager $manager = null,
	)
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function setAnalytics(array $analytics): self
	{
		$this->analytics = $analytics;

		return $this;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => self::ID,
			'text' => Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM'),
			'href' => $this->getUrl(),
			'onclick' => new JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
		];
	}

	public function toInterfaceToolbarButton(): array
	{
		return [
			'ID' => self::ID,
			'TEXT' => Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM'),
			'TITLE' => Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM'),
			'LINK' => $this->getUrl(),
			'HANDLER' => 'BX.Crm.Router.Instance.closeSettingsMenu',
			'IS_SETTINGS_BUTTON' => true,
		];
	}

	public function interfaceToolbarDelimiter(): array
	{
		return [
			'ID' => self::DELIMITER_ID,
			'SEPARATOR' => true,
		];
	}

	public function delimiter(): array
	{
		return [
			'id' => self::DELIMITER_ID,
			'delimiter' => true,
		];
	}

	private function getUrl(): ?Uri
	{
		$baseUrl = $this->manager?->getUrl();
		if (!$baseUrl)
		{
			return null;
		}

		$viewEvent = ViewEvent::createFromArray($this->analytics);
		if ($viewEvent->validate()->isSuccess())
		{
			return $viewEvent->buildUri($baseUrl);
		}

		return $baseUrl;
	}

	public function canShow(): bool
	{
		if ($this->manager === null)
		{
			return false;
		}

		return
			$this->manager->hasPermissionsToEditRights()
			&& RoleManagerUtils::getInstance()->isUsePermConfigV2()
		;
	}

	public function toArray(): array
	{
		return $this->jsonSerialize();
	}

	public static function createByEntity(int $entityTypeId, ?int $categoryId = null): self
	{
		$manager = (new RoleManagerSelectionFactory())->createByEntity($entityTypeId, $categoryId);

		return new self($manager);
	}
}
