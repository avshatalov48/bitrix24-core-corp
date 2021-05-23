<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule("faceid"))
	return;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class FaceIdUserTrackerComponent extends CBitrixComponent
{
	/**
	 * Start Component
	 */
	public function executeComponent()
	{
		global $USER;

		// check agreement
		if (!$USER->getId())
		{
			$this->includeComponentTemplate();
			return;
		}

		if (!empty($_POST['accept']) && !\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
		{
			$signer = new \Bitrix\Main\Security\Sign\Signer;
			$sign = base64_decode($signer->unsign($_POST['sign'], 'bx.faceid.agreement'));
			$ar = explode('_', $sign);
			$userId = end($ar);
			if ((int) $userId === (int) $USER->getId())
			{
				\Bitrix\Faceid\AgreementTable::add(array(
					'USER_ID' => $userId,
					'NAME' => $USER->GetFullName(),
					'EMAIL' => $USER->GetEmail(),
					'DATE' => new \Bitrix\Main\Type\DateTime,
					'IP_ADDRESS' => \Bitrix\Main\Context::getCurrent()->getRequest()->getRemoteAddress()
				));
			}
		}

		$this->arResult['HAS_AGREEMENT'] = \Bitrix\Faceid\AgreementTable::checkUser($USER->getId());

		if (!$this->arResult['HAS_AGREEMENT'])
		{
			$signer = new \Bitrix\Main\Security\Sign\Signer;
			$this->arResult['AGREEMENT_SIGN'] = $signer->sign(
				base64_encode('FACEID_SIGN_USER_ID_'.$USER->getId()),
				'bx.faceid.agreement'
			);
		}

		// today stats
		$fromDate = \Bitrix\Main\Type\DateTime::createFromTimestamp(mktime(-1, 0, 0));
		$fromDateServer = clone $fromDate;

		// adapt to user timezone
		$userTimeOffset = CTimeZone::GetOffset();
		$fromUserTimeInterval = -$userTimeOffset.' seconds';
		$fromDateServer->add($fromUserTimeInterval);

		// count users for today
		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Faceid\TrackingVisitorsTable::getEntity());
		$query->addFilter('>LAST_VISIT', $fromDateServer);

		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('NEW_VISITORS',
			'SUM(CASE WHEN %s = 1 THEN 1 ELSE 0 END)', 'VISITS_COUNT'
		));

		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('OLD_VISITORS',
			'SUM(CASE WHEN %s > 1 THEN 1 ELSE 0 END)', 'VISITS_COUNT'
		));

		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('CRM_VISITORS',
			'SUM(CASE WHEN %s > 0 THEN 1 ELSE 0 END)', 'CRM_ID'
		));

		$query->addSelect(new \Bitrix\Main\Entity\ExpressionField('TOTAL_VISITORS',
			'SUM(1)'
		));

		$this->arResult['STATS'] = $query->exec()->fetch();

		// replace null for 0
		foreach ($this->arResult['STATS'] as $k => $v)
		{
			if ($v === null)
			{
				$this->arResult['STATS'][$k] = 0;
			}
		}

		// last visitors
		$visitors = \Bitrix\Faceid\TrackingVisitorsTable::getList(array(
			'order' => array('LAST_VISIT' => 'DESC'),
			'limit' => 20
		))->fetchAll();

		$visitors = array_reverse($visitors);


		echo $this->getLastVisitorsJson($visitors);

		// balance
		if ($this->arResult['HAS_AGREEMENT'])
		{
			// renew from cloud
			\Bitrix\FaceId\FaceId::getBalance();
		}

		$this->arResult['BALANCE'] = \Bitrix\Main\Config\Option::get('faceid', 'balance', '1000');

		$this->arResult['BUY_MORE_URL'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			? "/settings/license_face.php"
			: "https://www.1c-bitrix.ru/buy/intranet.php#tab-face-link";

		$this->includeComponentTemplate();
	}

	protected function getLastVisitorsJson($visitors)
	{
		$result = array();
		foreach ($visitors as $visitor)
		{
			$result[] = \Bitrix\Faceid\TrackingVisitorsTable::toJson($visitor, 0, true);
		}

		$out =  "<script type=\"text/javascript\">
			window.FACEID_LAST_VISITORS = ".\Bitrix\Main\Web\Json::encode($result).";			
		</script>";

		return $out;
	}

}