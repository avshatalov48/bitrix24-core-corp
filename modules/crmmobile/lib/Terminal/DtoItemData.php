<?php

namespace Bitrix\CrmMobile\Terminal;

use Bitrix\Mobile\UI\SimpleList\Dto\Data;

final class DtoItemData extends Data
{
	public ?string $accountNumber;
	public ?string $accessCode;

	public ?float $sum;
	public ?string $currency;

	public ?string $phoneNumber;

	public ?int $companyId;
	public array $contactIds = [];

	public ?bool $isPaid;
	public ?int $datePaid;
	public ?int $paymentSystemId;
	public ?string $paymentSystemName;

	public ?string $slipLink;

	public array $permissions = [];
	public array $paymentSystems = [];
}
