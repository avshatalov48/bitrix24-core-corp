<?
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Loader,
	\Bitrix\Crm\Order;
use Bitrix\Crm\Service\Container;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!Loader::IncludeModule('crm') || !Loader::IncludeModule('sale'))
{
	return;
}

global $APPLICATION;

if (!Container::getInstance()->getUserPermissions()->entityType()->canReadItems(CCrmOwnerType::Order))
{
	return;
}

$entityId = $_GET['USER_ID'];
$_GET['USER_ID'] = preg_replace('/^(ORDER)_/iu', '', $_GET['USER_ID']);
$orderId = (int) $_GET['USER_ID'];

if ($orderId > 0)
{
	if(!($order = Order\Order::load($orderId)))
	{
		return;
	}

	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$pathToOrderDetails = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Order, $orderId, false);

	$contact = null;
	if(
		($orderContactCompanyCollection = $order->getContactCompanyCollection())
		&& $primaryContact = $orderContactCompanyCollection->getPrimaryContact()
	)
	{
		$contactId = $primaryContact->getField('ENTITY_ID');
		$contact = Container::getInstance()->getFactory(CCrmOwnerType::Contact)->getItem($contactId);
	}

	$company = null;
	if($orderContactCompanyCollection && $orderContactCompanyCollection->getPrimaryCompany())
	{
		$primaryCompany = $orderContactCompanyCollection->getPrimaryCompany();
		$companyId = $primaryCompany->getField('ENTITY_ID');

		$company = Container::getInstance()->getFactory(CCrmOwnerType::Company)->getItem($companyId);
	}

	$statuses = \Bitrix\Crm\Order\OrderStatus::getListInCrmFormat();
	$products = [];
	$PRODUCTS_LIMIT = 3;
	$moreProducts = false;

	if($basket = $order->getBasket())
	{
		$counter = 0;
		/** @var Order\BasketItem $basketItem */
		foreach($basket as $basketItem)
		{
			if($PRODUCTS_LIMIT <= $counter++)
			{
				$moreProducts = true;
				break;
			}

			$products[] = htmlspecialcharsbx($basketItem->getField('NAME'));
		}
	}

	if($moreProducts)
	{
		$products[] = '...';
	}

	$price = \CCrmCurrency::MoneyToString(
		$order->getPrice(),
		$order->getCurrency(),
		'',
	);

	$topic = Loc::getMessage(
		'CRM_ODCA_ORDER_NUM',
		['#ORDER_NUMBER#' => $order->getField('ACCOUNT_NUMBER')]
	);

	if(!empty($order->getField('ORDER_TOPIC')))
	{
	 	$topic = $order->getField('ORDER_TOPIC').' ('.$topic.')';
	}

	$orderTitle = '<a href="'.$pathToOrderDetails.'" target="_blank">'.htmlspecialcharsbx($topic).'</a>';
	$html = '';

	$html .= '<span class="bx-ui-tooltip-field-row">
			<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_ODCA_STATUS').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.htmlspecialcharsbx($statuses[$order->getField('STATUS_ID')]['NAME']).'</span></span>
		</span>';

	if(!empty($products))
	{
		$html .= '<span class="bx-ui-tooltip-field-row">
			<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_ODCA_PRODUCTS').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.implode(', ', $products).'</span></span>
		</span>';
	}

	$html .= '<span class="bx-ui-tooltip-field-row">
			<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_ODCA_PRICE').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration"><nobr>'.$price.'</nobr></span></span>
		</span>';

	$html .= '<span class="bx-ui-tooltip-field-row">
		<span class="bx-ui-tooltip-field-name">'.GetMessage('CRM_ODCA_DATE_UPDATED').'</span>: <span class="bx-ui-tooltip-field-value"><span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($order->getField('DATE_UPDATE')), (time() + CTimeZone::GetOffset())).'</span></span>
	</span>';

	if ($company !== null)
	{
		if (Container::getInstance()->getUserPermissions()->item()->canReadItem($company))
		{
			$pathToCompanyShow = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Company, $companyId, false);

			$html .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_ODCA_COMPANY').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$pathToCompanyShow.'" target="_blank">'.htmlspecialcharsbx($company->getHeading()).'</a></span>
			</span>';
		}
		else
		{
			$html .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_ODCA_COMPANY').'</span>: <span class="bx-ui-tooltip-field-value">'.htmlspecialcharsbx(CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Company)).'</span>
			</span>';
		}
	}

	if ($contact !== null)
	{
		if (Container::getInstance()->getUserPermissions()->item()->canReadItem($contact))
		{
			$pathToContactShow = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Contact, $contact->getId(), false);

			$html .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_ODCA_CONTACT').'</span>: <span class="bx-ui-tooltip-field-value"><a href="'.$pathToContactShow.'" target="_blank">'.htmlspecialcharsbx($contact->getHeading()).'</a></span>
			</span>';
		}
		else
		{
			$html .= '<span class="bx-ui-tooltip-field-row">
				<span class="bx-ui-tooltip-field-name">'.Loc::getMessage('CRM_ODCA_CONTACT').'</span>: <span class="bx-ui-tooltip-field-value">'.htmlspecialcharsbx(CCrmViewHelper::GetHiddenEntityCaption(CCrmOwnerType::Contact)).'</span>
			</span>';
		}
	}

	$strCard = '<div class="bx-ui-tooltip-info-data-cont" id="bx_user_info_data_cont_'.htmlspecialcharsbx($entityId).'"><div class="bx-ui-tooltip-info-data-info crm-tooltip-info">'.$html.'</div></div>';
	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<a href="'.$pathToOrderDetails.'" target="_blank">'.GetMessage('CRM_ODCA_GO_ORDER').'</a>';

	$result = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Name' => $orderTitle,
		'Card' => $strCard,
		'Photo' => ''
	);
}

$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo CUtil::PhpToJsObject(array('RESULT' => $result));
if(!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}
include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
die();
