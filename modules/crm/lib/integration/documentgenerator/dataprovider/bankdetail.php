<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\EntityBankDetail;

class BankDetail extends BaseRequisite
{
	/**
	 * Loads data from the database.
	 *
	 * @return array|false
	 */
	protected function fetchData()
	{
		if(!$this->isLoaded())
		{
			if($this->source > 0)
			{
				$this->data = EntityBankDetail::getSingleInstance()->getList(['filter' => ['ID' => $this->source]])->fetch();
			}
		}

		return $this->data;
	}

	protected function getInterfaceLanguageTitles()
	{
		if($this->interfaceTitles === null)
		{
			$this->interfaceTitles = EntityBankDetail::getSingleInstance()->getFieldsTitles($this->getInterfaceCountryId());
		}

		return $this->interfaceTitles;
	}

	protected function getDocumentLanguageTitles()
	{
		if($this->documentTitles === null)
		{
			$documentRegion = $this->getDocumentCountryId();
			if($documentRegion == $this->getInterfaceCountryId())
			{
				$this->documentTitles = $this->getInterfaceLanguageTitles();
			}
			else
			{
				$this->documentTitles = EntityBankDetail::getSingleInstance()->getFieldsTitles($documentRegion);
			}
		}

		return $this->documentTitles;
	}
}