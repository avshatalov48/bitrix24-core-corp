<?php

namespace Bitrix\CrmMobile\Integration\Sale\Payment;

use Bitrix\Mobile\UI\SimpleList\Dto\Data;

final class DtoItemData extends Data
{
	public ?string $accountNumber;
	public ?string $accessCode;

	public ?float $sum;
	public ?string $currency;

	public ?bool $hasEntityBinding;
	public ?int $productsCnt;

	public ?string $phoneNumber;

	public ?int $companyId;
	public array $contactIds = [];

	public ?bool $isPaid;
	public ?int $datePaid;
	public ?int $paymentSystemId;
	public ?string $paymentSystemName;

	public ?string $slipLink;

	public ?int $responsibleId;

	public array $permissions = [];

	public ?bool $isTerminalPayment;

	public ?bool $isPhoneConfirmed;
	public ?int $connectedSiteId;

	public array $terminalPaymentSystems = [];

	public array $paymentSystems = [];
	public bool $isLinkPaymentEnabled = true;
}
