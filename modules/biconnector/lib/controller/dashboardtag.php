<?php

namespace Bitrix\BIConnector\Controller;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTagTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetTagTable;
use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class DashboardTag extends Controller
{
	protected function processBeforeAction(Action $action)
	{
		return $this->checkPermission();
	}

	/**
	 * @return array
	 */
	protected function getDefaultPreFilters(): array
	{
		$additionalFilters = [
			new Scope(Scope::AJAX),
		];

		if (Loader::includeModule('intranet'))
		{
			$additionalFilters[] = new IntranetUser();
		}

		return [
			...parent::getDefaultPreFilters(),
			...$additionalFilters,
		];
	}

	/**
	 * @param string $title
	 *
	 * @return array|null
	 */
	public function addAction(string $title): ?array
	{
		$userId = $this->getCurrentUser()->getId();

		$userTag = SupersetTagTable::getRow([
			'filter' => [
				'=TITLE' => $title,
			],
		]);

		if ($userTag)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_TAG_SAVE_ERROR_HAS_EXIST_TAG')));

			return null;
		}

		$result = SupersetTagTable::add([
			'USER_ID' => $userId,
			'TITLE' => $title,
		]);


		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return [
			'ID' => $result->getId(),
			'TITLE' => $title,
		];
	}

	/**
	 * @param int $id
	 * @param string $title
	 *
	 * @return bool|null
	 */
	public function renameAction(int $id, string $title): ?bool
	{
		$tag = SupersetTagTable::getList([
				'filter' => [
					'=ID' => $id,
				],
			])
			->fetchObject()
		;

		if (!$tag)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_TAG_SAVE_ERROR_EMPTY_TAG')));

			return null;
		}

		$existedTitle = SupersetTagTable::getRow([
			'filter' => [
				'=TITLE' => $title,
			],
			'select' => ['ID'],
		]);

		if ($existedTitle && $id !== (int)$existedTitle['ID'])
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_TAG_SAVE_ERROR_HAS_EXIST_TAG')));

			return null;
		}

		$tag->setTitle($title);

		$result = $tag->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @param int $id
	 *
	 * @return bool|null
	 */
	public function deleteAction(int $id): ?bool
	{
		$tag = SupersetTagTable::getList([
				'filter' => [
					'=ID' => $id,
				],
			])
			->fetchObject()
		;

		if (!$tag)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_DASHBOARD_TAG_SAVE_ERROR_EMPTY_TAG')));

			return null;
		}

		$tagBindings = SupersetDashboardTagTable::getList([
				'filter' => [
					'=TAG_ID' => $id,
				],
			])
			->fetchCollection()
		;

		foreach ($tagBindings as $binding)
		{
			$binding->delete();
		}

		$tag->delete();

		return true;
	}

	private function checkPermission(): bool
	{
		if (
			(Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled('bi_constructor'))
			|| !AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DASHBOARD_TAG_MODIFY)
		)
		{
			$this->addError(new Error('Access denied.'));

			return false;
		}

		return true;
	}
}
