<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!Main\Loader::includeModule('sale') || !Main\Loader::includeModule('search') )
{
	return;
}

Loc::loadMessages(__FILE__);

/**
 * Class SearchIndex
 * @package Bitrix\Crm\Order
 */
class SearchIndex
{
	/** @var Sale\Order $order  */
	private $order = null;

	protected static $instance = null;

	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new SearchIndex();
		}
		return self::$instance;
	}

	public function setOrder(Sale\Order $order)
	{
		$this->order = $order;
		return $this;
	}

	private function prepareFields()
	{
		if (!$this->order)
		{
			return '';
		}

		$mainLine = $this->order->getField('ACCOUNT_NUMBER');
		if (!empty($this->order->getField('ORDER_TOPIC')))
		{
			$mainLine .= ", ".$this->order->getField('ORDER_TOPIC');
		}
		$bodyElements = [
			$mainLine,
			Loc::getMessage('CRM_ORDER_SEARCH_FIELD_PRICE').": ". $this->order->getPrice()
		];
		$title = \CCrmOwnerType::GetDescription(\CCrmOwnerType::Order).": ".$mainLine;
		$culture = Main\Context::getCurrent()->getCulture();
		/** @var Main\Type\Date $dateInsert */
		$dateInsert = $this->order->getDateInsert();
		$bodyElements[] = Loc::getMessage('CRM_ORDER_SEARCH_FIELD_DATE_CREATE').": ". $dateInsert->format($culture->getShortDateFormat());
		$statusNames = OrderStatus::getAllStatusesNames();
		$bodyElements[] = Loc::getMessage('CRM_ORDER_SEARCH_FIELD_STATUS').": ". $statusNames[$this->order->getField('STATUS_ID')];
		$bodyElements[] = Loc::getMessage('CRM_ORDER_SEARCH_FIELD_USER').": ". $this->prepareUserInfo($this->order->getUserId(), ['EMAIL']);

		if (!empty($this->order->getField('RESPONSIBLE_ID')))
		{
			$bodyElements[] = Loc::getMessage('CRM_ORDER_SEARCH_FIELD_USER').": ". $this->prepareUserInfo($this->order->getUserId(), ['EMAIL', 'WORK_POSITION']);
		}

		$arResult = Array(
			'LAST_MODIFIED' => $this->order->getField('DATE_UPDATE'),
			'DATE_FROM' =>  $this->order->getDateInsert(),
			'TITLE' => $title,
			'PARAM1' => \CCrmOwnerType::OrderName,
			'PARAM2' => $this->order->getId(),
			'BODY' => implode("\n", $bodyElements),
			'TAGS' => 'sale,'.mb_strtolower(\CCrmOwnerType::OrderName).','.\CCrmOwnerType::GetDescription(\CCrmOwnerType::Order)
		);

		return $arResult;
	}

	private function prepareUserInfo($id, array $additionalFieldNames = [])
	{
		$fields = \CUser::GetByID((int)$id)->Fetch();
		if (!$fields)
			return '';

		$userInfo = \CUser::FormatName(
			Main\Context::getCurrent()->getCulture()->getNameFormat(),
			array(
				'LOGIN' => '',
				'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
				'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : '',
				'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
			),
			false, false
		);
		foreach ($additionalFieldNames as $fieldName)
		{
			if (!empty($fields[$fieldName]))
			{
				$userInfo.= ", ".$fields[$fieldName];
			}
		}

		return $userInfo;
	}

	public function add()
	{
		$result = new Main\Result();
		if ($this->order)
		{
			\CSearch::Index(
				'sale',
				\CCrmOwnerType::Order.'.'.$this->order->getId(),
				$this->prepareFields()
			);
		}
		else
		{
			$result->addError(new Main\Error('Empty order'));
		}
		return $result;
	}

	public function update()
	{
		$result = new Main\Result();
		if ($this->order)
		{
			\CSearch::Index(
				'sale',
				\CCrmOwnerType::Order.'.'.$this->order->getId(),
				$this->prepareFields(),
				true
			);
		}
		else
		{
			$result->addError(new Main\Error('Empty order'));
		}
		return $result;
	}

	public function delete()
	{
		$result = new Main\Result();
		if ($this->order)
		{
			\CSearch::DeleteIndex('sale', $this->order->getId());
		}
		else
		{
			$result->addError(new Main\Error('Empty order'));
		}
		return $result;
	}
}