<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\B2e\RegionDocumentType;
use Bitrix\Sign\Item\B2e\RegionDocumentTypeCollection;

class RegionDocumentTypeRepository
{
	public function listByRegionCode(string $regionCode): RegionDocumentTypeCollection
	{
		return match ($regionCode)
		{
			'ru' => $this->getRuTypes(),
			default => new RegionDocumentTypeCollection(),
		};
	}

	private function getRuTypes(): RegionDocumentTypeCollection
	{
		$collection = new RegionDocumentTypeCollection();

		$collection->add(new RegionDocumentType('01.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.001', null, 'ru')));
		$collection->add(new RegionDocumentType('01.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.002', null, 'ru')));
		$collection->add(new RegionDocumentType('01.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.003', null, 'ru')));
		$collection->add(new RegionDocumentType('01.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.004', null, 'ru')));
		$collection->add(new RegionDocumentType('01.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.005', null, 'ru')));
		$collection->add(new RegionDocumentType('01.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.006', null, 'ru')));
		$collection->add(new RegionDocumentType('01.007', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.007', null, 'ru')));
		$collection->add(new RegionDocumentType('01.008', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.008', null, 'ru')));
		$collection->add(new RegionDocumentType('01.009', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.009', null, 'ru')));
		$collection->add(new RegionDocumentType('01.010', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.010', null, 'ru')));
		$collection->add(new RegionDocumentType('01.011', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.011', null, 'ru')));
		$collection->add(new RegionDocumentType('01.012', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.012', null, 'ru')));
		$collection->add(new RegionDocumentType('01.013', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_01.013', null, 'ru')));
		$collection->add(new RegionDocumentType('02.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.001', null, 'ru')));
		$collection->add(new RegionDocumentType('02.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.002', null, 'ru')));
		$collection->add(new RegionDocumentType('02.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.004', null, 'ru')));
		$collection->add(new RegionDocumentType('02.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.005', null, 'ru')));
		$collection->add(new RegionDocumentType('02.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.006', null, 'ru')));
		$collection->add(new RegionDocumentType('02.007', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.007', null, 'ru')));
		$collection->add(new RegionDocumentType('02.008', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.008', null, 'ru')));
		$collection->add(new RegionDocumentType('02.009', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.009', null, 'ru')));
		$collection->add(new RegionDocumentType('02.010', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.010', null, 'ru')));
		$collection->add(new RegionDocumentType('02.011', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.011', null, 'ru')));
		$collection->add(new RegionDocumentType('02.012', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.012', null, 'ru')));
		$collection->add(new RegionDocumentType('02.013', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.013', null, 'ru')));
		$collection->add(new RegionDocumentType('02.014', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.014', null, 'ru')));
		$collection->add(new RegionDocumentType('02.015', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.015', null, 'ru')));
		$collection->add(new RegionDocumentType('02.016', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.016', null, 'ru')));
		$collection->add(new RegionDocumentType('02.017', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.017', null, 'ru')));
		$collection->add(new RegionDocumentType('02.018', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.018', null, 'ru')));
		$collection->add(new RegionDocumentType('02.019', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.019', null, 'ru')));
		$collection->add(new RegionDocumentType('02.020', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.020', null, 'ru')));
		$collection->add(new RegionDocumentType('02.021', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.021', null, 'ru')));
		$collection->add(new RegionDocumentType('02.022', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.022', null, 'ru')));
		$collection->add(new RegionDocumentType('02.023', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.023', null, 'ru')));
		$collection->add(new RegionDocumentType('02.024', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.024', null, 'ru')));
		$collection->add(new RegionDocumentType('02.025', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.025', null, 'ru')));
		$collection->add(new RegionDocumentType('02.026', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_02.026', null, 'ru')));
		$collection->add(new RegionDocumentType('03.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_03.001', null, 'ru')));
		$collection->add(new RegionDocumentType('03.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_03.002', null, 'ru')));
		$collection->add(new RegionDocumentType('03.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_03.003', null, 'ru')));
		$collection->add(new RegionDocumentType('03.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_03.004', null, 'ru')));
		$collection->add(new RegionDocumentType('03.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_03.005', null, 'ru')));
		$collection->add(new RegionDocumentType('03.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_03.006', null, 'ru')));
		$collection->add(new RegionDocumentType('04.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_04.001', null, 'ru')));
		$collection->add(new RegionDocumentType('04.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_04.002', null, 'ru')));
		$collection->add(new RegionDocumentType('04.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_04.003', null, 'ru')));
		$collection->add(new RegionDocumentType('04.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_04.004', null, 'ru')));
		$collection->add(new RegionDocumentType('04.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_04.005', null, 'ru')));
		$collection->add(new RegionDocumentType('04.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_04.006', null, 'ru')));
		$collection->add(new RegionDocumentType('05.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_05.001', null, 'ru')));
		$collection->add(new RegionDocumentType('05.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_05.002', null, 'ru')));
		$collection->add(new RegionDocumentType('05.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_05.003', null, 'ru')));
		$collection->add(new RegionDocumentType('05.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_05.004', null, 'ru')));
		$collection->add(new RegionDocumentType('05.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_05.005', null, 'ru')));
		$collection->add(new RegionDocumentType('05.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_05.006', null, 'ru')));
		$collection->add(new RegionDocumentType('06.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_06.001', null, 'ru')));
		$collection->add(new RegionDocumentType('06.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_06.002', null, 'ru')));
		$collection->add(new RegionDocumentType('07.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.001', null, 'ru')));
		$collection->add(new RegionDocumentType('07.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.002', null, 'ru')));
		$collection->add(new RegionDocumentType('07.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.003', null, 'ru')));
		$collection->add(new RegionDocumentType('07.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.004', null, 'ru')));
		$collection->add(new RegionDocumentType('07.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.005', null, 'ru')));
		$collection->add(new RegionDocumentType('07.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.006', null, 'ru')));
		$collection->add(new RegionDocumentType('07.007', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.007', null, 'ru')));
		$collection->add(new RegionDocumentType('07.008', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.008', null, 'ru')));
		$collection->add(new RegionDocumentType('07.009', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.009', null, 'ru')));
		$collection->add(new RegionDocumentType('07.010', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.010', null, 'ru')));
		$collection->add(new RegionDocumentType('07.011', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.011', null, 'ru')));
		$collection->add(new RegionDocumentType('07.012', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.012', null, 'ru')));
		$collection->add(new RegionDocumentType('07.013', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.013', null, 'ru')));
		$collection->add(new RegionDocumentType('07.014', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.014', null, 'ru')));
		$collection->add(new RegionDocumentType('07.015', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.015', null, 'ru')));
		$collection->add(new RegionDocumentType('07.016', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.016', null, 'ru')));
		$collection->add(new RegionDocumentType('07.017', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.017', null, 'ru')));
		$collection->add(new RegionDocumentType('07.018', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_07.018', null, 'ru')));
		$collection->add(new RegionDocumentType('08.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_08.001', null, 'ru')));
		$collection->add(new RegionDocumentType('08.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_08.002', null, 'ru')));
		$collection->add(new RegionDocumentType('08.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_08.003', null, 'ru')));
		$collection->add(new RegionDocumentType('08.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_08.004', null, 'ru')));
		$collection->add(new RegionDocumentType('08.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_08.005', null, 'ru')));
		$collection->add(new RegionDocumentType('08.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_08.006', null, 'ru')));
		$collection->add(new RegionDocumentType('09.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_09.001', null, 'ru')));
		$collection->add(new RegionDocumentType('09.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_09.002', null, 'ru')));
		$collection->add(new RegionDocumentType('09.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_09.003', null, 'ru')));
		$collection->add(new RegionDocumentType('09.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_09.004', null, 'ru')));
		$collection->add(new RegionDocumentType('09.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_09.005', null, 'ru')));
		$collection->add(new RegionDocumentType('10.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.001', null, 'ru')));
		$collection->add(new RegionDocumentType('10.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.002', null, 'ru')));
		$collection->add(new RegionDocumentType('10.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.003', null, 'ru')));
		$collection->add(new RegionDocumentType('10.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.004', null, 'ru')));
		$collection->add(new RegionDocumentType('10.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.005', null, 'ru')));
		$collection->add(new RegionDocumentType('10.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.006', null, 'ru')));
		$collection->add(new RegionDocumentType('10.007', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.007', null, 'ru')));
		$collection->add(new RegionDocumentType('10.008', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.008', null, 'ru')));
		$collection->add(new RegionDocumentType('10.009', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.009', null, 'ru')));
		$collection->add(new RegionDocumentType('10.010', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.010', null, 'ru')));
		$collection->add(new RegionDocumentType('10.011', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.011', null, 'ru')));
		$collection->add(new RegionDocumentType('10.012', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.012', null, 'ru')));
		$collection->add(new RegionDocumentType('10.013', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_10.013', null, 'ru')));
		$collection->add(new RegionDocumentType('11.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.001', null, 'ru')));
		$collection->add(new RegionDocumentType('11.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.002', null, 'ru')));
		$collection->add(new RegionDocumentType('11.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.003', null, 'ru')));
		$collection->add(new RegionDocumentType('11.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.004', null, 'ru')));
		$collection->add(new RegionDocumentType('11.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.005', null, 'ru')));
		$collection->add(new RegionDocumentType('11.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.006', null, 'ru')));
		$collection->add(new RegionDocumentType('11.007', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.007', null, 'ru')));
		$collection->add(new RegionDocumentType('11.008', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.008', null, 'ru')));
		$collection->add(new RegionDocumentType('11.009', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.009', null, 'ru')));
		$collection->add(new RegionDocumentType('11.010', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.010', null, 'ru')));
		$collection->add(new RegionDocumentType('11.011', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.011', null, 'ru')));
		$collection->add(new RegionDocumentType('11.012', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.012', null, 'ru')));
		$collection->add(new RegionDocumentType('11.013', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_11.013', null, 'ru')));
		$collection->add(new RegionDocumentType('12.001', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.001', null, 'ru')));
		$collection->add(new RegionDocumentType('12.002', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.002', null, 'ru')));
		$collection->add(new RegionDocumentType('12.003', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.003', null, 'ru')));
		$collection->add(new RegionDocumentType('12.004', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.004', null, 'ru')));
		$collection->add(new RegionDocumentType('12.005', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.005', null, 'ru')));
		$collection->add(new RegionDocumentType('12.006', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.006', null, 'ru')));
		$collection->add(new RegionDocumentType('12.007', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.007', null, 'ru')));
		$collection->add(new RegionDocumentType('12.008', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.008', null, 'ru')));
		$collection->add(new RegionDocumentType('12.009', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.009', null, 'ru')));
		$collection->add(new RegionDocumentType('12.999', (string)Loc::getMessage('SIGN_RU_REGION_DOCUMENT_TYPE_12.999', null, 'ru')));

		return $collection;
	}

}
