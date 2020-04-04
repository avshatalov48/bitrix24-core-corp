<?php
namespace Bitrix\Crm\Controller;
use Bitrix\Main;
use Bitrix\Main\Web\Uri;

use Bitrix\Crm;
use Bitrix\Crm\Search\SearchEnvironment;

class OrderBuyer extends Main\Engine\Controller
{
	public function configureActions()
	{
		return array(
			'search' => array('class' => Crm\Controller\Action\Order\SearchBuyerAction::class)
		);
	}
}
