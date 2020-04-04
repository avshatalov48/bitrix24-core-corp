<?php
namespace Bitrix\Crm\Controller;
use Bitrix\Main;
use Bitrix\Main\Web\Uri;

use Bitrix\Crm;
use Bitrix\Crm\Search\SearchEnvironment;

class Order extends Main\Engine\Controller
{
	public function configureActions()
	{
		return array(
			'searchBuyer' => array('class' => Crm\Controller\Action\Order\SearchBuyerAction::class)
		);
	}
}
