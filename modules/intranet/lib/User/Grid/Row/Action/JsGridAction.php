<?php

namespace Bitrix\Intranet\User\Grid\Row\Action;

use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User\Access\Model\TargetUserModel;
use Bitrix\Intranet\User\Access\UserAccessController;
use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Web\Json;

abstract class JsGridAction extends \Bitrix\Main\Grid\Row\Action\BaseAction
{
	private string $extensionMethod;
	private string $extensionName;
	private string $gridId;

	private UserSettings $settings;

	public function __construct(UserSettings $settings)
	{
		$this->extensionMethod = $this->getExtensionMethod();
		$this->extensionName = $settings->getExtensionName();
		$this->gridId = $settings->getID();
		$this->settings = $settings;
	}

	abstract public function getExtensionMethod(): string;
	abstract protected function getActionParams(array $rawFields): array;
	abstract protected static function getActionType(): UserActionDictionary;

	protected function isCurrentUserAdmin(): bool
	{
		return $this->getSettings()->isUserAdmin($this->getSettings()->getCurrentUserId());
	}

	public function isAvailable(array $rawFields): bool
	{
		$user = $this->settings->getUserCollection()->getByUserId($rawFields['ID']);

		return ServiceContainer::getInstance()->getUserService()->isActionAvailableForUser($user, static::getActionType())
			&& UserAccessController::createByDefault()->check(
				static::getActionType(),
				TargetUserModel::createFromUserEntity($user),
			);
	}

	public function getControl(array $rawFields): ?array
	{
		if ($this->isAvailable($rawFields))
		{
			$extension = $this->extensionName;
			$method = $this->extensionMethod;
			$gridId = $this->gridId;
			$params = Json::encode($this->getActionParams($rawFields));
			$this->onclick = "BX.$extension.GridManager.getInstance('$gridId').$method($params)";

			return parent::getControl($rawFields);
		}

		return null;
	}

	public function getSettings(): UserSettings
	{
		return $this->settings;
	}

	final public static function getId(): string
	{
		return static::getActionType()->value;
	}
}