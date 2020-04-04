<?php
namespace Bitrix\Crm\Integration\Numerator;

use Bitrix\Crm\Invoice\Internals\InvoiceTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Generator\Contract\DynamicConfigurable;
use Bitrix\Main\Numerator\Generator\NumberGenerator;
use Bitrix\Main\Entity\ExpressionField;

/**
 * Class InvoiceUserInvoicesNumberGenerator
 * @package Bitrix\Sale\Integration\Numerator
 */
class InvoiceUserInvoicesNumberGenerator extends NumberGenerator implements DynamicConfigurable
{
	protected $orderId;

	const TEMPLATE_WORD_USER_ID_INVOICES_COUNT = "USER_ID_INVOICES_COUNT";

	/** @inheritdoc */
	public static function getTemplateWordsForParse()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_USER_ID_INVOICES_COUNT),
		];
	}

	/** @inheritdoc */
	public static function getTemplateWordsSettings()
	{
		return [
			static::getPatternFor(static::TEMPLATE_WORD_USER_ID_INVOICES_COUNT)
			=> Loc::getMessage('BITRIX_CRM_INTEGRATION_NUMERATOR_INVOICUSERINVOICESNUMBERGENERATOR_WORD_USER_ID_INVOICES_COUNT'),
		];
	}

	/**
	 * @return string
	 */
	public static function getAvailableForType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	/**
	 * @return string
	 */
	protected function getWordToReplace()
	{
		return self::getPatternFor(self::TEMPLATE_WORD_USER_ID_INVOICES_COUNT);
	}

	/** @inheritdoc */
	protected function getTableName()
	{
		return InvoiceTable::class;
	}

	/** @inheritdoc */
	public function parseTemplate($template)
	{
		$tableName = $this->getTableName();
		/** @var \Bitrix\Main\Entity\DataManager $tableName */
		$userIdOfOrder = $tableName::query()
			->addSelect('USER_ID')
			->where('ID', $this->orderId)
			->exec()
			->fetch();

		if ($userIdOfOrder)
		{
			$userIdOfOrder = intval($userIdOfOrder['USER_ID']);
			$countOrderOfUser = $tableName::query()
				->addSelect('ORDERS_COUNT')
				->registerRuntimeField(
					new ExpressionField(
						'ORDERS_COUNT',
						'COUNT(ID)'
					)
				)
				->where('USER_ID', $userIdOfOrder)
				->addGroup('USER_ID')
				->exec()
				->fetch();

			if ($countOrderOfUser)
			{
				$numID = (intval($countOrderOfUser["ORDERS_COUNT"]) > 0) ? $countOrderOfUser["ORDERS_COUNT"] : 1;
				$value = $userIdOfOrder . "_" . $numID;
			}
			else
			{
				$value = $userIdOfOrder . "_1";
			}
		}
		else
		{
			$value = '';
		}

		return str_replace($this->getWordToReplace(), $value, $template);
	}

	/**
	 * @param array $config
	 */
	public function setDynamicConfig($config)
	{
		if (is_array($config) && array_key_exists('ORDER_ID', $config))
		{
			$this->orderId = $config['ORDER_ID'];
		}
	}
}