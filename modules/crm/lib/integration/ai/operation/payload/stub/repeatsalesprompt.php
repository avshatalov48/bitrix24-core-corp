<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Stub;

use Bitrix\Crm\Integration\AI\Operation\Payload\StubInterface;
use Bitrix\Main\Web\Json;

final class RepeatSalesPrompt implements StubInterface
{
	public function makeStub(): string
	{
		$customerInfo = [
			'last_purchase_date' => "12/30/2023",
			'last_purchase_details' => "Classic Napoleon cake, decorated in a New Year's style, weighing one and a half kilograms",
			'orders_overview' => "Larisa regularly orders cakes and desserts, mainly classic Napoleon and mousses of various flavors. She places orders in advance and prefers to pick them up herself.",
			'detailed_issues_summary' => "Issues: Larisa expressed concern about the high cost of delivery and preferred pickup. There were also problems with the availability of some items in the order.",
		];
		
		$actionPlan = [
			'best_wayTo_contact' => "Call Larisa on the phone, as she prefers to discuss orders and details by voice. This will allow you to quickly resolve all issues and clarify the details of the order.",
			'sales_opportunity' => "Offer Larisa the opportunity to pre-order a cake for upcoming holidays or events. Considering her preferences, focus on the classic Napoleon and new flavor variations.",
			'service_improvement_suggestions' => "Provide Larisa a discount on her next order or free delivery to compensate for the inconvenience associated with previous orders. This will help strengthen customer loyalty.",
			'think_before_service_improvement_suggestions' => "Consider offering a loyalty program for regular customers like Larisa, which will include discounts and special offers. This will encourage her to continue ordering from your bakery.",
		];

		return Json::encode([
			'customer_info' => $customerInfo,
			'action_plan' => $actionPlan,
		]);
	}
}
