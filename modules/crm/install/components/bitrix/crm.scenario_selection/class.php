<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox;

/**
 * Class ScenarioSelection
 */
class ScenarioSelection extends CBitrixComponent implements Bitrix\Main\Engine\Contract\Controllerable
{
	private const SCENARIO_ORDER_DEAL = 'order_deal';
	private const SCENARIO_DEAL = 'deal';

	/**
	 * @return void
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function executeComponent()
	{
		Main\Loader::includeModule('crm');

		$this->arResult['selected_scenario'] = \CCrmSaleHelper::isWithOrdersMode()
			? self::SCENARIO_ORDER_DEAL
			: self::SCENARIO_DEAL;
		$this->arResult['order_count'] = Bitrix\Crm\Update\Order\DealGenerator::getUnbindingActiveOrdersCount();
		$this->arResult['order_info_always_hidden'] = $this->arResult['order_count'] === 0;
		$this->arResult['deal_list_url'] = SITE_DIR . 'crm/' . mb_strtolower(CCrmOwnerType::DealName) . '/?redirect_to';

		$this->includeComponentTemplate();
	}

	/**
	 * @param $selectedScenario
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function saveSelectedScenarioAction($params)
	{
		Main\Loader::includeModule('sale');
		Main\Loader::includeModule('crm');

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			return [
				'success' => false,
				'error' => Loc::getMessage('CRM_SCENARIO_SELECTION_NOT_ENOUGH_PERMISSIONS'),
			];
		}

		$selectedScenario = $params['selectedScenario'];

		if ($selectedScenario === self::SCENARIO_ORDER_DEAL)
		{
			\CCrmSaleHelper::activateModeOrders();
		}
		elseif ($selectedScenario === self::SCENARIO_DEAL)
		{
			if ($params['isConvertActiveDealsEnabled'] === 'Y')
			{
				Crm\Update\Order\DealGenerator::bind(10);
			}

			\CCrmSaleHelper::deactivateModeOrders();
		}

		return ['success' => true];
	}

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [];
	}
}
