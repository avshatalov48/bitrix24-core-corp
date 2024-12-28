<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\Application;

class Analytics
{
	private const ANALYTIC_TOOL = 'Invitation';
	private const ANALYTIC_CATEGORY_INVITATION = 'invitation';
	private const ANALYTIC_PARAM_IS_ADMIN_Y = 'isAdmin_Y';
	private const ANALYTIC_PARAM_IS_ADMIN_N = 'isAdmin_N';
	private const ANALYTIC_PARAM_EMPLOYEE_COUNT = 'employeeCount_';
	private const ANALYTIC_CATEGORY_REGISTRATION = 'registration';
	private const ANALYTIC_EVENT_INVITATION = 'invitation';
	private const ANALYTIC_INVITATION_TYPE_EMAIL = 'email';
	private const ANALYTIC_INVITATION_TYPE_PHONE = 'phone';
	const ANALYTIC_EVENT_CHANGE_QUICK_REG = 'change_quick_reg';
	const ANALYTIC_CATEGORY_SETTINGS = 'settings';
	const ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_EMAIL = 'tab_by_email';
	const ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_MASS = 'tab_mass';
	const ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_DEPARTMENT = 'tab_department';
	const ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_INTEGRATOR = 'tab_integrator';

	private function send(array $data): void
	{
		foreach ($data as $onaAnalytic)
		{
			if (isset($onaAnalytic['event'], $onaAnalytic['tool'], $onaAnalytic['category']))
			{
				$event = new AnalyticsEvent($onaAnalytic['event'], $onaAnalytic['tool'], $onaAnalytic['category']);

				if (isset($onaAnalytic['section']))
				{
					$event->setSection($onaAnalytic['section']);
				}
				if (isset($onaAnalytic['type']))
				{
					$event->setType($onaAnalytic['type']);
				}
				if (isset($onaAnalytic['subSection']))
				{
					$event->setSubSection($onaAnalytic['subSection']);
				}
				if (isset($onaAnalytic['status']))
				{
					$event->setStatus($onaAnalytic['status']);
				}
				if (isset($onaAnalytic['p1']))
				{
					$event->setP1($onaAnalytic['p1']);
				}
				if (isset($onaAnalytic['p2']))
				{
					$event->setP2($onaAnalytic['p2']);
				}
				if (isset($onaAnalytic['p3']))
				{
					$event->setP3($onaAnalytic['p3']);
				}
				if (isset($onaAnalytic['p5']))
				{
					$event->setP5($onaAnalytic['p5']);
				}
				$event->send();
			}
		}
	}

	public function sendRegistration(
		int $userId,
		string $category = self::ANALYTIC_CATEGORY_REGISTRATION,
		string $event = self::ANALYTIC_CATEGORY_REGISTRATION,
		string $status = '',
		array $userData = []
	): void
	{
		$analyticData = $this->getData();
		$analytic = [
			'tool' => self::ANALYTIC_TOOL,
			'category' => $category,
			'event' => $event,
			'section' => $analyticData['source'] ?? '',
			'p1' => $this->getAdmin(),
			'p2' => isset($userData['ADD_SEND_PASSWORD']) && $userData['ADD_SEND_PASSWORD'] === 'Y' ? 'Сonfirm_Y' : 'Сonfirm_N',
			'p3' => isset($userData['UF_DEPARTMENT']) && count($userData['UF_DEPARTMENT']) > 0 ? 'department_Y' : 'department_N',
			'p5' => 'userId_' . $userId,
		];

		if ($status !== '')
		{
			$success = 'success';
			$fail = 'fail';
			if ($event === self::ANALYTIC_EVENT_CHANGE_QUICK_REG) {
				$success = 'on';
				$fail = 'off';
			}
			$analytic['status'] = $status === 'Y' ? $success : $fail;
		}
		$analytics[] = $analytic;
		$this->send($analytics);
	}

	private function getData(): array
	{
		$analyticsData = Application::getInstance()->getContext()->getRequest()->getPost('analyticsData');
		$result = [];
		if (is_array($analyticsData))
		{
			$result = $analyticsData;
		}

		return $result;
	}

	public function sendInvitation(
		int $userId,
		string $subSection,
		bool $status,
		int $analyticEmails = 0,
		int $analyticPhones = 0,
	): void
	{
		$analyticData = $this->getData();

		$analyticBase = [
			'tool' => self::ANALYTIC_TOOL,
			'category' => self::ANALYTIC_CATEGORY_INVITATION,
			'event' => self::ANALYTIC_EVENT_INVITATION,
			'section' => $analyticData['source'] ?? '',
			'subSection' => $subSection,
			'status' => $status ? 'success' : 'fail',
			'p1' => $this->getAdmin(),
			'p5' => 'userId_' . $userId,
		];

		if ($analyticEmails > 0)
		{
			$analytic[] = array_merge($analyticBase, [
				'type' => self::ANALYTIC_INVITATION_TYPE_EMAIL,
				'p2' => self::ANALYTIC_PARAM_EMPLOYEE_COUNT . $analyticEmails,
			]);
		}

		if ($analyticPhones > 0)
		{
			$analytic[] = array_merge($analyticBase, [
				'type' => self::ANALYTIC_INVITATION_TYPE_PHONE,
				'p2' => self::ANALYTIC_PARAM_EMPLOYEE_COUNT . $analyticPhones,
			]);
		}

		if (!empty($analytic))
		{
			$this->send($analytic);
		}
	}

	private function getAdmin(): string
	{
		return CurrentUser::get()->isAdmin()
			? self::ANALYTIC_PARAM_IS_ADMIN_Y
			: self::ANALYTIC_PARAM_IS_ADMIN_N
		;
	}
}
