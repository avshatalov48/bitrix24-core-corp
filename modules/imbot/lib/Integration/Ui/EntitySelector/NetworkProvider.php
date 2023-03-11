<?php
namespace Bitrix\ImBot\Integration\Ui\EntitySelector;

use Bitrix\Im\Model\BotTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class NetworkProvider extends BaseProvider
{
	protected $searchText = '';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['doNotCheckBlackList'] = false;
		$this->options['filterExistingLines'] = false;

		if (isset($options['doNotCheckBlackList']) && is_bool($options['doNotCheckBlackList']))
		{
			$this->options['doNotCheckBlackList'] = $options['doNotCheckBlackList'];
		}

		if (isset($options['filterExistingLines']) && is_bool($options['filterExistingLines']))
		{
			$this->options['filterExistingLines'] = $options['filterExistingLines'];
		}
	}

	public function getEntityId(): string
	{
		return 'imbot-network';
	}

	public function getEntityType(): string
	{
		return 'NETWORK';
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$this->setSearchText($searchQuery);

		$networkLineList = $this->getNetworkLineList();

		if($this->options['filterExistingLines'])
		{
			$networkLineList = $this->filterExistingLines($networkLineList);
		}

		$itemList = $this->createItemList($networkLineList);

		$dialog->addItems($itemList);
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	public function getSelectedItems(array $ids): array
	{
		return [];
	}

	public function getNetworkLineList(): array
	{
		$searchText = $this->getSearchText();

		$result =
			\Bitrix\ImBot\Bot\Network::search(
				$searchText,
				$this->getOption('doNotCheckBlackList', false)
		);

		return $result ?: [];
	}

	/**
	 * @param array $networkLineList
	 * @return Item[]
	 */
	public function createItemList(array $networkLineList): array
	{
		$result = [];

		foreach ($networkLineList as $lineData)
		{
			$result[] = $this->createItem($lineData);
		}

		return $result;
	}

	public function createItem(array $lineData): Item
	{
		return new Item([
			'id' => $lineData['CODE'],
			'entityId' => $this->getEntityId(),
			'entityType' => $this->getEntityType(),
			'title' => htmlspecialcharsbx($lineData["LINE_NAME"]),
			'subtitle' => $this->getSubTitle($lineData["LINE_DESC"]),
			'avatar' => empty($lineData['LINE_AVATAR']) ? '' : $lineData['LINE_AVATAR'],
			'avatarOptions' => [
				'color' => \Bitrix\Im\Color::getColorByNumber(preg_replace('/\D/', '', $lineData['CODE']))
			],
		]);
	}

	protected function getSubTitle($row): string
	{
		return $row ? htmlspecialcharsbx($row) : Loc::getMessage('NETWORK_PROVIDER_DEFAULT_SUB_TITLE');
	}

	protected function setSearchText(SearchQuery $searchQuery): NetworkProvider
	{
		if (!method_exists($searchQuery, 'getRawQuery'))
		{
			$this->searchText = $searchQuery->getQuery();

			return $this;
		}
		$this->searchText = $searchQuery->getRawQuery();

		return $this;
	}

	protected function getSearchText(): string
	{
		return $this->searchText;
	}

	protected function filterExistingLines(array $appList): array
	{
		if(empty($appList))
		{
			return $appList;
		}

		$filteringCodeList = [];
		foreach ($appList as $app)
		{
			$filteringCodeList[] = 'network_' . $app['CODE'];
		}

		$query =
			BotTable::query()
			->addSelect('CODE')
			->addSelect('APP_ID')
			->where('TYPE', 'N')
			->whereIn('CODE', $filteringCodeList)
		;

		$installedAppList = [];
		foreach ($query->exec() as $app)
		{
			$installedAppList[$app['APP_ID']] = $app;
		}

		if(empty($installedAppList))
		{
			return $appList;
		}

		$result = [];
		foreach ($appList as $app)
		{
			if(isset($installedAppList[$app['CODE']]))
			{
				continue;
			}
			$result[] = $app;
		}

		return $result;
	}
}