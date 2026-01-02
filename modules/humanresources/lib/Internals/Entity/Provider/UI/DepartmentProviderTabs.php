<?php

namespace Bitrix\HumanResources\Internals\Entity\Provider\UI;

use Bitrix\HumanResources\Internals\Enum\Provider\UI\DepartmentProviderTabId;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Item\Node;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Tab;
use Bitrix\Main\Localization\Loc;

class DepartmentProviderTabs
{
	private ?array $entityTabsMap = null;

	/**
	 * @param list<NodeEntityType> $includedNodeEntityTypes
	 * @param bool $isUseMultipleTabs
	 */
	public function __construct(
		private readonly array $includedNodeEntityTypes = [NodeEntityType::DEPARTMENT],
		private readonly bool $isUseMultipleTabs = false,
	)
	{
		$this->initEntityTabsMap();
	}

	public function addTabsIntoDialog(Dialog $dialog): void
	{
		$tabs = $this->isUseMultipleTabs
			? $this->getIncludedTypeTabs()
			: $this->getSingleTypeTabs()
		;

		foreach ($tabs as $tab)
		{
			$dialog->addTab($tab);
		}
	}

	/**
	 * @return list<Tab>
	 */
	public function getTabsForNode(Node $node): array
	{
		$entityTabsMap = $this->entityTabsMap;
		if (!$this->isUseMultipleTabs || !array_key_exists($node->type->value, $entityTabsMap))
		{
			return [$entityTabsMap[NodeEntityType::DEPARTMENT->value]];
		}

		$includedEntityTypes = $this->includedNodeEntityTypes;
		if ($node->isTeam() && in_array(NodeEntityType::TEAM, $includedEntityTypes, true))
		{
			return [$entityTabsMap[NodeEntityType::TEAM->value]];
		}
		if ($node->isDepartment() && in_array(NodeEntityType::DEPARTMENT, $includedEntityTypes, true))
		{
			return [$entityTabsMap[NodeEntityType::DEPARTMENT->value]];
		}

		return [];
	}

	private function initEntityTabsMap(): void
	{
		$this->entityTabsMap = [
			NodeEntityType::DEPARTMENT->value => $this->createTab(
				DepartmentProviderTabId::Departments,
				Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_DEPARTMENTS_TAB_TITLE') ?? '',
				$this->getDepartmentTabIconInBase64(),
				$this->getDepartmentTabSelectedIconInBase64(),
			),
			NodeEntityType::TEAM->value => $this->createTab(
				DepartmentProviderTabId::Teams,
				Loc::getMessage('HUMANRESOURCES_ENTITY_SELECTOR_TEAMS_TAB_TITLE') ?? '',
				$this->getTeamTabIconInBase64(),
				$this->getTeamTabSelectedIconInBase64(),
			),
		];
	}

	private function createTab(DepartmentProviderTabId $tabId, string $tabTitle, string $tabIconDefault, string $tabIconSelected): Tab
	{
		return new Tab(
			[
				'id' => $tabId->value,
				'title' => $tabTitle,
				'itemMaxDepth' => 7,
				'icon' => [
					'default' => $tabIconDefault,
					'selected' => $tabIconSelected,
				],
			],
		);
	}

	/**
	 * @return list<Tab>
	 */
	private function getIncludedTypeTabs(): array
	{
		$entityTabsMap = $this->entityTabsMap;
		$tabs = [];

		foreach ($this->includedNodeEntityTypes as $type)
		{
			if (isset($entityTabsMap[$type->value]))
			{
				$tabs[] = $entityTabsMap[$type->value];
			}
		}

		return $tabs;
	}

	/**
	 * @return list<Tab>
	 */
	private function getSingleTypeTabs(): array
	{
		$entityTabsMap = $this->entityTabsMap;
		$included = $this->includedNodeEntityTypes;

		$firstEntityTab = $entityTabsMap[$included[0]?->value] ?? null;
		if ($firstEntityTab !== null && count($included) === 1)
		{
			return [$firstEntityTab];
		}

		return [$entityTabsMap[NodeEntityType::DEPARTMENT->value]];
	}

	private function getDepartmentTabIconInBase64(): string
	{
		return 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2223%22%20height%3D%2223%22%20fill%3D%22'
			. 'none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M15.953%2018.654a29.847%'
			. '2029.847%200%2001-6.443.689c-2.672%200-5.212-.339-7.51-.948.224-1.103.53-2.573.672-3.106.238-.896'
			. '%201.573-1.562%202.801-2.074.321-.133.515-.24.71-.348.193-.106.386-.213.703-.347.036-.165.05-.333.'
			. '043-.5l.544-.064s.072.126-.043-.614c0%200-.61-.155-.64-1.334%200%200-.458.148-.486-.566a1.82%201.'
			. '82%200%2000-.08-.412c-.087-.315-.164-.597.233-.841l-.287-.74S5.87%204.583%207.192%204.816c-.537-.'
			. '823%203.99-1.508%204.29%201.015.119.76.119%201.534%200%202.294%200%200%20.677-.075.225%201.17%200'
			. '%200-.248.895-.63.693%200%200%20.062%201.133-.539%201.325%200%200%20.043.604.043.645l.503.074s-.01'
			. '4.503.085.557c.458.287.96.505%201.488.645%201.561.383%202.352%201.041%202.352%201.617%200%200%20.6'
			. '41%202.3.944%203.802z%22%20fill%3D%22%23ABB1B8%22/%3E%3Cpath%20d%3D%22M21.47%2016.728c-.36.182-.73'
			. '.355-1.112.52h-3.604c-.027-.376-.377-1.678-.58-2.434-.081-.299-.139-.513-.144-.549-.026-.711-1.015-'
			. '1.347-2.116-1.78a1.95%201.95%200%2000.213-.351c.155-.187.356-.331.585-.42l.017-.557-1.208-.367s-.31'
			. '-.14-.342-.14c.036-.086.08-.168.134-.245.023-.06.17-.507.17-.507-.177.22-.383.415-.614.58.211-.363.'
			. '39-.743.536-1.135a7.02%207.02%200%2000.192-1.15%2016.16%2016.16%200%2001.387-2.093c.125-.343.346-.64'
			. '7.639-.876a3.014%203.014%200%20011.46-.504h.062c.525.039%201.03.213%201.462.504.293.229.514.532.64.8'
			. '76.174.688.304%201.387.387%202.092.037.38.104.755.201%201.124.145.4.322.788.527%201.161a3.066%203.06'
			. '6%200%2001-.614-.579s.113.406.136.466c.063.09.119.185.167.283-.03%200-.342.141-.342.141l-1.208.367.0'
			. '17.558c.23.088.43.232.585.419.073.179.188.338.337.466.292.098.573.224.84.374.404.219.847.36%201.306.'
			. '416.463.074.755.8.755.8l.037.729.093%201.811z%22%20fill%3D%22%23ABB1B8%22/%3E%3C/svg%3E'
		;
	}

	private function getDepartmentTabSelectedIconInBase64(): string
	{
		return str_replace('ABB1B8', 'fff', $this->getDepartmentTabIconInBase64());
	}

	private function getTeamTabIconInBase64(): string
	{
		return 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2219%22%20height%3D%2216%22%20viewBox%3D%'
			. '220%200%2019%2016%22%20fill%3D%22none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%'
			. '22M12.4873%204.80859C12.0867%204.17389%2015.465%203.64647%2015.6895%205.58984C15.7777%206.17552%2015.7777%'
			. '206.77078%2015.6895%207.35645C15.6989%207.35544%2016.1909%207.3077%2015.8574%208.25781C15.8574%208.25781%'
			. '2015.6715%208.94875%2015.3867%208.79395C15.3876%208.81186%2015.4282%209.66794%2014.9844%209.81445C14.9846%'
			. '209.81741%2015.0164%2010.2772%2015.0166%2010.3105L15.3916%2010.3672C15.3912%2010.3808%2015.3816%2010.7557%'
			. '2015.4551%2010.7969C15.797%2011.0176%2016.1722%2011.185%2016.5664%2011.293C17.7302%2011.5884%2018.3212%'
			. '2012.0952%2018.3213%2012.5391L18.5303%2013.6025C18.5907%2013.9104%2018.4267%2014.221%2018.1318%2014.3281C16.9051%'
			. '2014.7739%2015.5215%2015.0378%2014.0547%2015.0693H13.4619C11.9878%2015.0377%2010.5975%2014.7711%209.36621%'
			. '2014.3213C9.08415%2014.2181%208.91974%2013.9273%208.96777%2013.6309C9.0136%2013.348%209.06446%2013.0768%209.11621%'
			. '2012.875C9.29341%2012.1846%2010.2894%2011.6717%2011.2061%2011.2773C11.4459%2011.1741%2011.591%2011.092%2011.7373%'
			. '2011.0088C11.8803%2010.9275%2012.0249%2010.8454%2012.2607%2010.7422C12.2875%2010.615%2012.2985%2010.4842%2012.293%'
			. '2010.3545L12.6992%2010.3066C12.701%2010.3098%2012.7512%2010.3934%2012.667%209.83301C12.667%209.83301%2012.2106%'
			. '209.71495%2012.1895%208.80664C12.1895%208.80664%2011.8459%208.92046%2011.8252%208.37012C11.8209%208.2607%2011.7927%'
			. '208.15549%2011.7656%208.05469C11.7005%207.81248%2011.642%207.5952%2011.9385%207.40625L11.7246%206.83496C11.7224%'
			. '206.81325%2011.5048%204.63094%2012.4873%204.80859ZM6.47168%2011.8018H1.22266C0.946516%2011.8018%200.72266%2011.5779%'
			. '200.722656%2011.3018V10.0254C0.722656%209.74925%200.946514%209.52539%201.22266%209.52539H8.31934L6.47168%2011.8018ZM9.06152%'
			. '207.14941H1.22266C0.946516%207.14941%200.72266%206.92555%200.722656%206.64941V5.37305C0.722656%205.0969%200.946514%'
			. '204.87305%201.22266%204.87305H9.38672L9.06152%207.14941ZM14.2393%200.22168C14.5152%200.221912%2014.7393%200.445681%2014.7393%'
			. '200.72168V1.99805C14.7393%202.23139%2014.5786%202.42532%2014.3623%202.48047V2.47949H9.72852L9.72559%202.49805H1.22266C0.946516%'
			. '202.49805%200.72266%202.27419%200.722656%201.99805V0.72168C0.722656%200.445537%200.946514%200.22168%201.22266%200.22168H14.2393Z%'
			. '22%20fill%3D%22%23ABB1B8%22/%3E%3C/svg%3E'
		;
	}

	private function getTeamTabSelectedIconInBase64(): string
	{
		return str_replace('ABB1B8', 'fff', $this->getTeamTabIconInBase64());
	}
}