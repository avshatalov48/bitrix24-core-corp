<?php

class CControllerServerRequestTo extends __CControllerPacketRequest
{
	public $url;
	public $debug_const = 'CONTROLLER_SERVER_DEBUG';
	public $debug_file_const = 'CONTROLLER_SERVER_LOG_DIR';

	public function __construct($member, $operation, $arParameters = [])
	{
		if (is_array($member))
		{
			$arMember = $member;
		}
		else
		{
			$dbr_member = CControllerMember::GetById($member);
			$arMember = $dbr_member->Fetch();
		}

		if ($arMember)
		{
			$this->url = $arMember['URL'];
			$this->hostname = $arMember['HOSTNAME'];
			$this->member_id = $arMember['MEMBER_ID'];
			$this->secret_id = $arMember['SECRET_ID'];
			$this->operation = $operation;
			$this->arParameters = $arParameters;
			$this->session_id = \Bitrix\Main\Security\Random::getString(32);
		}
	}

	public function Send($url = '', $page = '/bitrix/admin/main_controller.php')
	{
		$event = new \Bitrix\Main\Event('controller', 'OnBeforeControllerServerRequestSend', [$this]);
		$event->send();

		$this->Sign();
		$result = parent::Send($this->url, $page);
		if ($result === false)
		{
			return false;
		}

		$oResponse = new CControllerServerResponseFrom($result);
		return $oResponse;
	}
}

class CControllerServerResponseFrom extends __CControllerPacketResponse
{
	public $debug_const = 'CONTROLLER_SERVER_DEBUG';
	public $debug_file_const = 'CONTROLLER_SERVER_LOG_DIR';

	public function __construct($oPacket = false)
	{
		$this->_InitFromRequest($oPacket, []);
	}
}
//
// This class handles clients queries
//
class CControllerServerRequestFrom extends __CControllerPacketRequest
{
	public $debug_const = 'CONTROLLER_SERVER_DEBUG';
	public $debug_file_const = 'CONTROLLER_SERVER_LOG_DIR';

	public function __construct()
	{
		$this->InitFromRequest();
		$this->Debug([
			'Request received from' => $this->member_id,
			'security check' => ($this->Check() ? 'passed' : 'failed'),
			'Packet' => $this,
		]);
	}

	public function Check()
	{
		global $APPLICATION;

		$dbr_member = CControllerMember::GetByGuid($this->member_id);
		$ar_member = $dbr_member->Fetch();
		if (!$ar_member)
		{
			$e = new CApplicationException('Bad member_id: ' . $this->member_id);
			$APPLICATION->ThrowException($e);
			return false;
		}
		$this->secret_id = $ar_member['SECRET_ID'];

		return parent::Check();
	}
}

class CControllerServerResponseTo extends __CControllerPacketResponse
{
	public $debug_const = 'CONTROLLER_SERVER_DEBUG';
	public $debug_file_const = 'CONTROLLER_SERVER_LOG_DIR';

	public function __construct($oPacket = false)
	{
		$this->_InitFromRequest($oPacket);
		$this->secret_id = false;
	}

	public function Sign()
	{
		global $APPLICATION;

		if ($this->secret_id === false)
		{
			$dbr_member = CControllerMember::GetByGuid($this->member_id);
			$ar_member = $dbr_member->Fetch();
			if (!$ar_member)
			{
				$e = new CApplicationException('Bad member_id: ' . $this->member_id);
				$APPLICATION->ThrowException($e);
				return false;
			}
			$this->secret_id = $ar_member['SECRET_ID'];
		}

		return parent::Sign();
	}
}
