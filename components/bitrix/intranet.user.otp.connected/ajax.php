<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class CIntranetUserOtpConnectedAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected $userId;

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		parent::processBeforeAction($action);

		if (!$this->getRequest()->isPost() || !$this->getRequest()->getPost('signedParameters'))
		{
			return false;
		}

		$parameters = $this->getUnsignedParameters();

		if (isset($parameters['USER_ID']))
		{
			$this->userId = $parameters['USER_ID'];
		}
		else
		{
			return false;
		}

		if (Loader::includeModule("security"))
		{
			$this->isOtpMandotary = !\CSecurityUser::IsUserSkipMandatoryRights($this->userId);
		}

		return true;
	}

	protected function canEditOtp()
	{
		global $USER;

		if (
			$USER->GetID() == $this->userId
			|| $USER->CanDoOperation('security_edit_user_otp')
		)
		{
			return true;
		}

		return false;
	}

	protected function isIntegrator()
	{
		global $USER;

		if (
			Loader::includeModule('bitrix24')
			&& \Bitrix\Bitrix24\Integrator::isIntegrator($USER->GetID())
		)
		{
			return true;
		}

		return false;
	}

	public function deactivateOtpAction($numDays)
	{
		global $USER;

		if (!Loader::includeModule("security"))
		{
			$this->addError(new \Bitrix\Main\Error("Module Security is not installed"));
			return false;
		}

		if (
			!$this->canEditOtp()
			|| $this->isIntegrator()
		)
		{
			$this->addError(new \Bitrix\Main\Error("No rights"));
			return false;
		}

		if (
			$USER->GetID() == $this->userId
			&& !$USER->CanDoOperation('security_edit_user_otp')
			&& $this->isOtpMandotary
		)
		{
			$this->addError(new \Bitrix\Main\Error("No rights"));
			return false;
		}

		$res = \CSecurityUser::DeactivateUserOtp($this->userId, $numDays);

		if ($res)
		{
			return true;
		}
		else
		{
			$this->addError(new \Bitrix\Main\Error("Deactivation Error"));
			return false;
		}
	}

	public function activateOtpAction()
	{
		if (!Loader::includeModule("security"))
		{
			$this->addError(new \Bitrix\Main\Error("Module Security is not installed"));
			return false;
		}

		if (!$this->canEditOtp())
		{
			$this->addError(new \Bitrix\Main\Error("No rights"));
			return false;
		}

		$res = \CSecurityUser::ActivateUserOtp($this->userId);
		if ($res)
		{
			return true;
		}
		else
		{
			$this->addError(new \Bitrix\Main\Error("Deactivation Error"));
			return false;
		}
	}

	public function deferOtpAction($numDays)
	{
		if (!Loader::includeModule("security"))
		{
			$this->addError(new \Bitrix\Main\Error("Module Security is not installed"));
			return false;
		}

		if (!$this->canEditOtp())
		{
			$this->addError(new \Bitrix\Main\Error("No rights"));
			return false;
		}

		$res = \CSecurityUser::DeferUserOtp($this->userId, $numDays);

		if ($res)
		{
			return true;
		}
		else
		{
			$this->addError(new \Bitrix\Main\Error("Error"));
			return false;
		}
	}
}
