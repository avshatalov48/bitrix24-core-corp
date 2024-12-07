<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field;

use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Type\ArrayHelper;

final class UserLinkFieldAssembler extends FieldAssembler
{
	private Broker $userBroker;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);

		$this->userBroker = Container::getInstance()->getUserBroker();
	}

	protected function prepareColumn($value)
	{
		if ((int)$value > 0)
		{
			return $this->prepareLink((int)$value);
		}

		return '';
	}

	private function prepareLink(int $userId): string
	{
		$user = $this->userBroker->getById($userId);
		if (!$user)
		{
			return '';
		}

		return '<a href="'.htmlspecialcharsbx($user['SHOW_URL']).'">'.htmlspecialcharsbx($user['FORMATTED_NAME']).'</a>';
	}

	public function prepareRows(array $rowList): array
	{
		$this->warmupUsersRuntimeCache($rowList);

		return parent::prepareRows($rowList);
	}

	private function warmupUsersRuntimeCache(array $rowList): void
	{
		$columnsWithUsers = $this->getColumnIds();
		if (empty($columnsWithUsers))
		{
			return;
		}

		$userIds = [];

		foreach ($rowList as $row)
		{
			foreach ($columnsWithUsers as $columnId)
			{
				$userIds[] = $row['data'][$columnId] ?? null;
			}
		}

		ArrayHelper::normalizeArrayValuesByInt($userIds);

		$this->userBroker->getBunchByIds($userIds);
	}
}
