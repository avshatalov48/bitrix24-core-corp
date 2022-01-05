<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Main\ArgumentTypeException;

final class ExporterBitrix24Scenario
{
	/** @var Bitrix24Scenario */
	private $bitrix24Scenario;

	public function __construct(Bitrix24Scenario $bitrix24Scenario)
	{
		$this->bitrix24Scenario = $bitrix24Scenario;
	}

	public function exportToArray(): array
	{
		return [
			'disk_onlyoffice_can_edit' => (int)$this->bitrix24Scenario->canUseEdit(),
			'disk_onlyoffice_can_view' => (int)$this->bitrix24Scenario->canUseView(),
			'disk_onlyoffice_got_promo_about' => (int)($this->bitrix24Scenario->isCurrentUserJoinedAfterInstallationOnlyOffice() || $this->bitrix24Scenario->isUserAlreadyGotPromoAboutOnlyOffice()),
			'disk_onlyoffice_demo_ended' => (int)$this->bitrix24Scenario->isTrialEnded(),
			'disk_onlyoffice_got_end_demo' => (int)($this->bitrix24Scenario->isCurrentUserJoinedAfterInstallationOnlyOffice() || $this->bitrix24Scenario->isUserAlreadyGotEndDemoOnlyOffice()),
			'disk_onlyoffice_new_user' => (int)$this->bitrix24Scenario->isCurrentUserJoinedAfterInstallationOnlyOffice(),
		];
	}

	public function exportToBxMessages(): string
	{
		$result = '';
		foreach ($this->exportToArray() as $messageName => $value)
		{
			$stringValue = "BX.message[\"{$messageName}\"] = ";
			if (is_string($value))
			{
				$stringValue .= "'{$value}'";
			}
			elseif (is_numeric($value))
			{
				$stringValue .= "{$value}";
			}
			else
			{
				throw new ArgumentTypeException('value', 'numeric or string');
			}

			$result .= "{$stringValue}\n";
		}

		return $result;
	}
}