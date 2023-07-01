<?php

namespace Bitrix\Market\Application;

use Bitrix\Main\Localization\Loc;

class Rights
{
	private array $rights = [];

	private int $quantityToShow = 0;

	private bool $showMoreButton = false;

	public function __construct(array $rights)
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/rest/scope.php');

		foreach ($rights as $key => $scope) {
			$scopeName = str_replace('/', '', mb_strtolower($key));

			$this->rights[] = [
				'CODE' => $key,
				'TITLE' => Loc::getMessage('REST_SCOPE_' . mb_strtoupper($key)) ?: $scope,
				'DESCRIPTION' => (string)Loc::getMessage('REST_SCOPE_' . mb_strtoupper($key) . '_DESCRIPTION'),
				'ICON' => '/bitrix/images/market/scope/market-icon-' . $scopeName . '.svg',
			];
		}

		usort($this->rights, [$this, 'sort']);

		$currentLength = 0;
		$maxLength = 130;
		foreach ($this->rights as $right) {
			$currentLength += mb_strlen($right['TITLE']);

			if ($currentLength > $maxLength) {
				$this->showMoreButton = true;
			} else {
				$this->quantityToShow++;
			}
		}
	}

	private function sort($a, $b): int
	{
		$lengthA = mb_strlen($a['TITLE']);
		$lengthB = mb_strlen($b['TITLE']);

		if ($lengthA == $lengthB) {
			return 0;
		}

		return ($lengthA < $lengthB) ? -1 : 1;
	}

	public function getInfo(): array
	{
		return $this->rights;
	}

	public function getQuantityToShow(): int
	{
		return $this->quantityToShow;
	}

	public function isShowMoreButton(): bool
	{
		return $this->showMoreButton;
	}

	public static function prepare(array $rights): array
	{
		$result = [];

		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/rest/scope.php');

		foreach ($rights as $key => $scope) {
			$scopeName = str_replace('/', '', mb_strtolower($key));
			$result[] = [
				'CODE' => $key,
				'TITLE' => Loc::getMessage('REST_SCOPE_' . mb_strtoupper($key)) ?: $scope,
				'DESCRIPTION' => (string)Loc::getMessage('REST_SCOPE_' . mb_strtoupper($key) . '_DESCRIPTION'),
				'ICON' => '/bitrix/images/market/scope/market-icon-' . $scopeName . '.svg',
			];
		}

		return $result;
	}
}