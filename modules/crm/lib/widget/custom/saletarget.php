<?php
namespace Bitrix\Crm\Widget\Custom;

use Bitrix\Crm\Widget\Custom\Entity\SaleTargetTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class SaleTarget
{
	const TYPE_COMPANY = 'COMPANY';
	const TYPE_CATEGORY = 'CATEGORY';
	const TYPE_USER = 'USER';

	const PERIOD_TYPE_YEAR = 'Y';
	const PERIOD_TYPE_HALF = 'H';
	const PERIOD_TYPE_QUARTER = 'Q';
	const PERIOD_TYPE_MONTH = 'M';

	const TARGET_TYPE_SUM = 'S';
	const TARGET_TYPE_QUANTITY = 'Q';

	//region Singleton
	/** @var $this|null */
	protected static $instance = null;
	/**
	 * @return $this
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	private function __construct()
	{
	}

	private function __clone()
	{
	}
	//endregion

	public function getDataFor($userId, $configurationId = null)
	{
		$configuration = $configurationId ? $this->getConfigurationById($configurationId) : $this->getVisibleConfiguration();

		if (!$configuration)
		{
			$configuration = $this->makeDemoConfiguration();
		}
		else
		{
			$this->applyReadPermissions($configuration, $userId);

			if ($configuration['type'] === static::TYPE_USER && is_array($configuration['target']['goal']))
			{
				$configuration['users'] = $this->getUsers(array_keys($configuration['target']['goal']), true);
			}
		}
		return array(
			'configuration' => $configuration,
			'nextConfigurationId' => $this->getNextConfigurationId($configuration),
			'previousConfigurationId' => $this->getPreviousConfigurationId($configuration),
		);
	}

	public function canEdit($userId)
	{
		if (\CCrmPerms::IsAdmin($userId))
		{
			return true;
		}
		$permissions = new \CCrmPerms($userId);
		return $permissions->HavePerm('SALETARGET', $permissions::PERM_ALL, 'WRITE');
	}

	public function getConfigurations()
	{
		$configurations = array();
		$result = SaleTargetTable::getList(array(
			'order' => array('LEFT_BORDER' => 'ASC')
		));

		foreach ($result as $row)
		{
			$configuration = $this->makeConfiguration($row);
			if ($configuration['type'] === static::TYPE_USER && is_array($configuration['target']['goal']))
			{
				$configuration['users'] = $this->getUsers(array_keys($configuration['target']['goal']), true);
			}
			$configurations[] = $configuration;
		}
		return $configurations;
	}

	public function getActiveUsers()
	{
		$leftTimestamp = mktime(0, 0, 0, date('n') - 1, 1, date('Y'));
		$leftDate = Main\Type\DateTime::createFromTimestamp($leftTimestamp);

		$result = \Bitrix\Crm\DealTable::getList(array(
			'select' => array('ASSIGNED_BY_ID'),
			'filter' => array(
				'>=DATE_CREATE' => $leftDate,
			),
			'group' => array('ASSIGNED_BY_ID')
		));
		$ids = array();

		while ($row = $result->fetch())
		{
			$ids[] = $row['ASSIGNED_BY_ID'];
		}

		return $this->getUsers($ids);
	}

	public function saveConfiguration($formData, $editorId)
	{
		$result = new Main\Result();

		$fields = array(
			'TYPE_ID' => $formData['type'],

			'PERIOD_TYPE' => $formData['period_type'],
			'PERIOD_YEAR' => (int)$formData['period_year'],
			'PERIOD_HALF' => (int)$formData['period_half'],
			'PERIOD_QUARTER' => (int)$formData['period_quarter'],
			'PERIOD_MONTH' => (int)$formData['period_month'],

			'TARGET_TYPE' => $formData['target_type'],
			'TARGET_GOAL' => $formData['target_goal'],

			'MODIFIED' => new Main\Type\DateTime(),
			'EDITOR_ID' => $editorId,
		);

		$periodBorders = $this->getPeriodBorders(array(
			'type' => $fields['PERIOD_TYPE'],
			'year' => $fields['PERIOD_YEAR'],
			'half' => $fields['PERIOD_HALF'],
			'quarter' => $fields['PERIOD_QUARTER'],
			'month' => $fields['PERIOD_MONTH'],
		));

		$fields['LEFT_BORDER'] = $periodBorders[0];
		$fields['RIGHT_BORDER'] = $periodBorders[1];

		$found = $this->findDuplicate($fields);

		if ($found)
		{
			$updateResult = SaleTargetTable::update($found['ID'], $fields);
			if (!$updateResult->isSuccess())
			{
				$result->addErrors($updateResult->getErrors());
			}

			$fields['ID'] = $found['ID'];
			$fields['CREATED'] = $found['CREATED'];
			$fields['AUTHOR_ID'] = $found['AUTHOR_ID'];
		}
		else
		{
			$fields['CREATED'] = new Main\Type\DateTime();
			$fields['AUTHOR_ID'] = $editorId;

			$addResult = SaleTargetTable::add($fields);
			if ($addResult->isSuccess())
			{
				$fields['ID'] = $addResult->getId();
			}
			else
			{
				$result->addErrors($addResult->getErrors());
			}
		}

		if ($result->isSuccess())
		{
			$result->setData($this->makeConfiguration($fields));
		}

		return $result;
	}

	public function saveConfigurations(array $configurations, $editorId, $actualPeriodType = false)
	{
		$result = new Main\Result();

		foreach ($configurations as $configuration)
		{
			$configResult = $this->saveConfiguration($configuration, $editorId);
			//TODO: check result
		}

		if ($result->isSuccess() && $actualPeriodType !== false)
		{
			SaleTargetTable::deleteConflicted($actualPeriodType);
		}

		return $result;
	}

	public function getPeriodBorders(array $period)
	{
		switch ($period['type'])
		{
			case static::PERIOD_TYPE_HALF:
				$leftMonth = ($period['half'] === 1) ? 1 : 7;
				$rightMonth = ($period['half'] === 1) ? 6 : 12;
				break;
			case static::PERIOD_TYPE_QUARTER:
				$leftMonth = ($period['quarter'] - 1) * 3 + 1;
				$rightMonth = $leftMonth + 2;
				break;
			case static::PERIOD_TYPE_MONTH:
				$leftMonth = $period['month'];
				$rightMonth = $period['month'];
				break;
			default:
				$leftMonth = 1;
				$rightMonth = 12;
		}
		$left = mktime(0, 0, 0, $leftMonth, 1, $period['year']);
		$right = mktime(0, 0, 0, $rightMonth + 1, 0, $period['year']);

		return array($left, $right);
	}

	public function getAdmins()
	{
		$users = array();
		// site admins
		$res = \Bitrix\Main\UserGroupTable::getList(array(
			'filter' => array(
				'GROUP_ID' => 1
			)
		));
		while ($row = $res->fetch())
		{
			$users[] = $row['USER_ID'];
		}

		if (!empty($users))
		{
			$res = \CUser::GetList(
				'timestamp_x',
				'desc',
				array(
					'ID' => implode(' | ', $users),
					'ACTIVE' => 'Y'
				)
			);
			$users = array();
			while ($row = $res->fetch())
			{
				if ($row['PERSONAL_PHOTO'])
				{
					$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet(
						$row['PERSONAL_PHOTO'],
						array('width' => 38, 'height' => 38),
						BX_RESIZE_IMAGE_EXACT
					);
					if ($row['PERSONAL_PHOTO'])
					{
						$row['PERSONAL_PHOTO'] = $row['PERSONAL_PHOTO']['src'];
					}
				}
				$users[$row['ID']] = array(
					'id' => $row['ID'],
					'name' => \CUser::FormatName(
						\CSite::GetNameFormat(false),
						$row, true, false
					),
					'img' => $row['PERSONAL_PHOTO']
				);
			}
		}

		return $users;
	}

	private function findDuplicate(array $fields)
	{
		$found = SaleTargetTable::getList(array(
			'select' => array('ID', 'CREATED', 'AUTHOR_ID'),
			'filter' => array(
				'=LEFT_BORDER' => $fields['LEFT_BORDER'],
				'=RIGHT_BORDER' => $fields['RIGHT_BORDER']
			)
		))->fetch();

		return $found;
	}

	private function makeDemoConfiguration()
	{
		$config = array(
			'id' => 0,
			'type' => static::TYPE_COMPANY,
			'period' => array(
				'type' => static::PERIOD_TYPE_MONTH,
				'year' => (int)date('Y'),
				'half' => null,
				'quarter' => null,
				'month' => (int)date('n')
			),
			'target' => $this->makeConfigurationTarget(
				static::TARGET_TYPE_SUM, array(
					static::TYPE_COMPANY => 0
				)
			)
		);

		$borders = $this->getPeriodBorders($config['period']);
		$config['leftBorder'] = $borders[0];
		$config['rightBorder'] = $borders[1];
		return $config;
	}

	private function getVisibleConfiguration()
	{
		$fields = SaleTargetTable::getList(array(
			'filter' => array(
				'>RIGHT_BORDER' => time()
			),
			'order' => array('LEFT_BORDER' => 'ASC'),
			'limit' => 1,
		))->fetch();

		return $fields ? $this->makeConfiguration($fields) : $this->getPreviousConfiguration();
	}

	private function getPreviousConfiguration()
	{
		$fields = SaleTargetTable::getList(array(
			'filter' => array(
				'<LEFT_BORDER' => time()
			),
			'order' => array('LEFT_BORDER' => 'DESC'),
			'limit' => 1,
		))->fetch();

		return $fields ? $this->makeConfiguration($fields) : null;
	}

	private function getConfigurationById($id)
	{
		$fields = SaleTargetTable::getList(array(
			'filter' => array(
				'=ID' => $id
			),
			'limit' => 1,
		))->fetch();

		return $fields ? $this->makeConfiguration($fields) : null;
	}

	private function getNextConfigurationId(array $configuration)
	{
		$fields = SaleTargetTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=PERIOD_TYPE' => $configuration['period']['type'],
				'>LEFT_BORDER' => $configuration['leftBorder']
			),
			'order' => array('LEFT_BORDER' => 'ASC'),
			'limit' => 1,
		))->fetch();

		return $fields ? (int)$fields['ID'] : null;
	}

	private function getPreviousConfigurationId(array $configuration)
	{
		$fields = SaleTargetTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=PERIOD_TYPE' => $configuration['period']['type'],
				'<LEFT_BORDER' => $configuration['leftBorder']
			),
			'order' => array('LEFT_BORDER' => 'DESC'),
			'limit' => 1,
		))->fetch();

		return $fields ? (int)$fields['ID'] : null;
	}

	private function makeConfiguration(array $fields)
	{
		$config = array(
			'id' => (int)$fields['ID'],
			'type' => $fields['TYPE_ID'],
			'period' => array(
				'type' => $fields['PERIOD_TYPE'],
				'year' => (int)$fields['PERIOD_YEAR'],
				'half' => (int)$fields['PERIOD_HALF'],
				'quarter' => (int)$fields['PERIOD_QUARTER'],
				'month' => (int)$fields['PERIOD_MONTH']
			),
			'target' => $this->makeConfigurationTarget($fields['TARGET_TYPE'], $fields['TARGET_GOAL'])
		);

		$borders = $this->getPeriodBorders($config['period']);
		$config['leftBorder'] = $borders[0];
		$config['rightBorder'] = $borders[1];

		return $config;
	}

	private function makeConfigurationTarget($type, $goal)
	{
		$goal = (array)$goal;
		$totalGoal = array_sum($goal);

		return array(
			'type' => $type,
			'goal' => $goal,
			'totalGoal' => $totalGoal
		);
	}

	private function applyReadPermissions(array &$configuration, $userId)
	{
		if (!$configuration['id'] || \CCrmPerms::IsAdmin($userId))
		{
			return;
		}

		$permissions = new \CCrmPerms($userId);
		$permission = $permissions->GetPermType('SALETARGET', 'READ');

		if ($permission === $permissions::PERM_NONE)
		{
			$configuration['target']['totalGoal'] = -1;
			foreach ($configuration['target']['goal'] as $id => $value)
			{
				$configuration['target']['goal'][$id] = -1;
			}
		}
		elseif ($permission === $permissions::PERM_ALL || $configuration['type'] === static::TYPE_COMPANY)
		{
			return;
		}
		elseif ($configuration['type'] === static::TYPE_CATEGORY)
		{
			foreach ($configuration['target']['goal'] as $id => $value)
			{
				$dealPermission = $permissions->GetPermType($id > 0 ? 'DEAL_C'.$id : 'DEAL', 'READ');
				if ($dealPermission === $permissions::PERM_NONE)
				{
					$configuration['target']['goal'][$id] = -1;
				}
			}
		}
		elseif ($configuration['type'] === static::TYPE_USER)
		{
			$userIds = $this->splitUsersByPermission($userId, array_keys($configuration['target']['goal']), $permission);
			foreach ($configuration['target']['goal'] as $id => $value)
			{
				if (!in_array($id, $userIds))
				{
					$configuration['target']['goal'][$id] = -1;
				}
			}
		}
	}

	private function splitUsersByPermission($targetUserId, $userIds, $permission)
	{
		$targetUserId = (int)$targetUserId;
		$resultIds = array();

		if (
			$permission !== \CCrmPerms::PERM_SELF
			&& $permission !== \CCrmPerms::PERM_DEPARTMENT
			&& $permission !== \CCrmPerms::PERM_SUBDEPARTMENT
		)
		{
			return $resultIds;
		}

		$targetDepartments = $this->getUserDepartments($targetUserId, $permission);

		foreach ($userIds as $checkUserId)
		{
			$checkUserId = (int)$checkUserId;
			if ($checkUserId === $targetUserId)
			{
				$resultIds[] = $checkUserId;
				continue;
			}
			if ($permission !== \CCrmPerms::PERM_SELF)
			{
				$checkDepartments = $this->getUserDepartments($checkUserId, \CCrmPerms::PERM_DEPARTMENT);
				$sect = array_intersect($targetDepartments, $checkDepartments);

				if ($sect)
				{
					$resultIds[] = $checkUserId;
				}
			}
		}

		return $resultIds;
	}

	private function getUserDepartments($userId, $permission)
	{
		$departments = array();
		$permissions = \CCrmPerms::GetUserAttr($userId);
		if (
			isset($permissions['INTRANET'])
			&& (
				$permission === \CCrmPerms::PERM_DEPARTMENT || $permission === \CCrmPerms::PERM_SUBDEPARTMENT
			)
		)
		{
			foreach ($permissions['INTRANET'] as $code)
			{
				if (mb_strpos($code, 'D') === 0)
				{
					$departments[] = (int)mb_substr($code, 1);
				}
			}
		}
		if (isset($permissions['SUBINTRANET']) && $permission === \CCrmPerms::PERM_SUBDEPARTMENT)
		{
			foreach ($permissions['SUBINTRANET'] as $code)
			{
				if (mb_strpos($code, 'D') === 0)
				{
					$departments[] = (int)mb_substr($code, 1);
				}
			}
		}

		return $departments;
	}

	private function getUsers(array $userIds, $inactive = false)
	{
		$users = array();
		if (!$userIds)
		{
			return $users;
		}
		$userNameFormat = \CSite::GetNameFormat(false);

		$filter = ["ID" => implode("|", array_unique($userIds)), 'ACTIVE' => 'Y'];

		if ($inactive)
		{
			unset($filter['ACTIVE']);
		}

		$dbRes = \CUser::getList("ID", "ASC",
			$filter,
			["FIELDS" => ["ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "TITLE", "PERSONAL_PHOTO", "WORK_POSITION", 'ACTIVE']]
		);

		while($user = $dbRes->fetch())
		{
			$users[] = array(
				'id' => $user['ID'],
				'name' => \CUser::FormatName($userNameFormat, $user, false, false),
				'title' => $user['ACTIVE'] === 'Y' ? $user['WORK_POSITION'] : Loc::getMessage("CRM_WIDGET_SALETARGET_USER_INACTIVE"),
				'photo' => $this->getUserAvatarSrc($user['PERSONAL_PHOTO']),
				'active' => ($user['ACTIVE'] === 'Y')
			);
		}

		if ($users)
		{
			Main\Type\Collection::sortByColumn($users, ['active' => SORT_DESC]);
		}

		return $users;
	}

	private function getUserAvatarSrc($fileId)
	{
		$photo = \CFile::ResizeImageGet(
			$fileId,
			array('width' => 35, 'height' => 35),
			BX_RESIZE_IMAGE_EXACT,
			true,
			false,
			true
		);

		return is_array($photo) ? $photo['src'] : null;
	}
}
