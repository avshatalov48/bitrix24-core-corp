<?php

namespace Bitrix\Sign\Util\UI;

use Bitrix\Main\Application;
use Bitrix\Main\Session\SessionInterface;
use Bitrix\Main\UI;

class PageNavigation extends UI\PageNavigation
{
	protected readonly string $sessionKeyName;

	protected SessionInterface $session;


	public function __construct(string $id)
	{
		parent::__construct($id);

		$this->session = Application::getInstance()->getSession();

		$this->sessionKeyName = 'sign_page_navigation';
	}

	protected function setSessionVar($page = 1, $allRecords = false): void
	{
		if(!isset($this->session[$this->sessionKeyName]))
		{
			$this->session[$this->sessionKeyName] = [];
		}

		$this->session[$this->sessionKeyName][$this->id] = [
			'page' => $page,
			'allRecords' => $allRecords
		];
	}

	protected function getSessionVar(): array
	{
		if (!isset($this->session[$this->sessionKeyName]) || !isset($this->session[$this->sessionKeyName][$this->id]))
		{
			return ['page' => 1, 'allRecords' => false];
		}

		return $this->session[$this->sessionKeyName][$this->id];
	}

	public function initFromUri(): void
	{
		parent::initFromUri();

		$page = $this->currentPage;
		$request = Application::getInstance()->getContext()->getRequest();
		if ($request->get('apply_filter') === 'Y')
		{
			$page = 1;
		}
		if (!$page && $request->get('grid_action') === 'pagination')
		{
			$page = 1;
		}

		if ($page > 0)
		{
			$this->setSessionVar($page, $this->allRecords);
		}
		else
		{
			$page = $this->getSessionVar()['page'];
		}

		$page = $page > 0 ? $page : 1;
		$this->setCurrentPage($page);
		$this->allRecords = $this->getSessionVar()['allRecords'];
	}
}