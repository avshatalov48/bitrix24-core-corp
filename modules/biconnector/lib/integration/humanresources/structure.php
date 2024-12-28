<?php
namespace Bitrix\BIConnector\Integration\HumanResources;

use Bitrix\BIConnector\DataSource\DatasetFilter;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;
use Bitrix\BIConnector\DataSource\Field\ArrayStringField;
use Bitrix\BIConnector\DataSource\Dataset;
use Bitrix\BIConnector\Dictionary;
use Bitrix\BIConnector\DictionaryManager;

class Structure extends Dataset
{
	protected const FIELD_NAME_PREFIX = 'HR_BIC_STRUCTURE_FIELD_';

	protected function getResultTableName(): string
	{
		return 'org_structure';
	}

	public function getSqlTableAlias(): string
	{
		return 'SN';
	}

	protected function getConnectionTableName(): string
	{
		return 'b_hr_structure_node';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('HR_BIC_STRUCTURE_TABLE');
	}

	/**
	 * @return Result
	 */
	protected function onBeforeEvent(): Result
	{
		$result = parent::onBeforeEvent();

		if (!Loader::includeModule('humanresources'))
		{
			$result->addError(new Error('Module is not installed'));
		}

		return $result;
	}

	protected function getDictionaries(): array
	{
		$dictionaries = [];
		if (DictionaryManager::isAvailable(Dictionary::USER_DEPARTMENT_HEAD))
		{
			$dictionaries[] = Dictionary::USER_DEPARTMENT_HEAD;
		}
		
		return $dictionaries;
	}

	protected function getFilter(): DatasetFilter
	{
		$structure = Container::getStructureRepository()
			->getByXmlId(\Bitrix\HumanResources\Item\Structure::DEFAULT_STRUCTURE_XML_ID)
		;

		return new DatasetFilter(
			[
				'=STRUCTURE_ID' =>  (int)($structure?->id),
			],
			[
				new IntegerField('STRUCTURE_ID')
			]
		);
	}

	protected function getFields(): array
	{
		$parentJoin = $this->createJoin(
			"SNP",
			"INNER JOIN b_hr_structure_node SNP ON SNP.ID = {$this->getAliasFieldName('PARENT_ID')}",
			"LEFT JOIN b_hr_structure_node SNP ON SNP.ID = {$this->getAliasFieldName('PARENT_ID')}"
		);

		$fields = [
			(new IntegerField('ID'))
				->setPrimary()
			,
			(new StringField('ACTIVE')),
			(new StringField('NAME')),
			(new StringField('ID_NAME'))
				->setName(
					"
						concat_ws(
							' ',
							concat('[', {$this->getAliasFieldName('ID')}, ']'),
							nullif({$this->getAliasFieldName('NAME')}, '')
						)
					"
				)
				->setDescription($this->getMessage('HR_BIC_STRUCTURE_FIELD_ID_NAME_MSG_VER1'))
				->setDescriptionFull($this->getMessage('HR_BIC_STRUCTURE_FIELD_ID_NAME_FULL_MSG_VER1'))
			,
			(new StringField('TYPE')),
			(new IntegerField('PARENT_ID'))
				->setName($parentJoin->getJoinFieldName('ID'))
				->setJoin($parentJoin)
			,
			(new StringField('ID_PARENT_NAME'))
				->setName(
					"
						if(
							{$this->getAliasFieldName('PARENT_ID')} > 0,
							concat_ws(
								' ',
								concat('[', {$this->getAliasFieldName('PARENT_ID')}, ']'),
								nullif({$parentJoin->getJoinFieldName('NAME')}, '')
							),
							NULL
						)
					"
				)
				->setJoin($parentJoin)
			,
		];

		if (in_array( Dictionary::USER_DEPARTMENT_HEAD, $this->getDictionaries(), true))
		{
			$dictionaryId = Dictionary::USER_DEPARTMENT_HEAD;
			$dictionaryJoin = $this->createJoin(
				"D",
				"INNER JOIN b_biconnector_dictionary_data D ON D.DICTIONARY_ID = $dictionaryId AND D.VALUE_ID = {$this->getAliasFieldName('ID')}",
				"LEFT JOIN b_biconnector_dictionary_data D ON D.DICTIONARY_ID = $dictionaryId AND D.VALUE_ID = {$this->getAliasFieldName('ID')}"
			);

			$fields['HEAD_ID'] =
				(new ArrayStringField('HEAD_ID'))
					->setName($dictionaryJoin->getJoinFieldName('VALUE_STR'))
					->setSeparator(',')
					->setJoin($dictionaryJoin)
			;
		}

		return $fields;
	}
}
