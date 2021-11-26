<?php

namespace Bitrix\Crm\Workflow;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

final class PaymentStage extends BaseStage
{
	/**
	 * Initial payment stage
	 */
	public const NOT_PAID = 'NOT_PAID';

	/**
	 * Payment sent to customer, but no viewed yet
	 */
	public const SENT_NO_VIEWED = 'SENT_NO_VIEWED';

	/**
	 * Customer receive payment, but not pay yet
	 */
	public const VIEWED_NO_PAID = 'VIEWED_NO_PAID';

	/**
	 * Payment successfully paid
	 */
	public const PAID = 'PAID';

	/**
	 * Payment was canceled
	 */
	public const CANCEL = 'CANCEL';

	/**
	 * Payment was refunded
	 */
	public const REFUND = 'REFUND';
}
