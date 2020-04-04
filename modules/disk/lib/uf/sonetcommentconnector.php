<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Main\Loader;

final class SonetCommentConnector extends StubConnector implements ISupportForeignConnector
{
	private $canRead = null;
	private $logCommentData;

	public function getDataToShow()
	{
		if(!($comment = $this->loadLogCommentData()))
		{
			return null;
		}

		$return = array();

		if (
			strpos($comment["EVENT_ID"], "crm_") === 0
			&& Loader::includeModule('crm')
		)
		{
			if (strpos($comment["EVENT_ID"], "_message_comment") > 0)
			{
				$connector = new CrmMessageCommentConnector($comment["ID"], $comment["LOG_ID"]);
				$subData = $connector->getDataToShow();
				if($subData["DETAIL_URL"])
				{
					$return["DETAIL_URL"] = $subData["DETAIL_URL"];
				}
				if($subData["DESCRIPTION"])
				{
					$return["DESCRIPTION"] = $subData["DESCRIPTION"];
				}
			}

			$connector = false;

			if ($comment["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Deal)
			{
				$connector = new CrmDealConnector($comment["ENTITY_ID"]);
			}
			elseif ($comment["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Lead)
			{
				$connector = new CrmLeadConnector($comment["ENTITY_ID"]);
			}
			elseif ($comment["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Company)
			{
				$connector = new CrmCompanyConnector($comment["ENTITY_ID"]);
			}
			elseif ($comment["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Contact)
			{
				$connector = new CrmContactConnector($comment["ENTITY_ID"]);
			}

			if ($connector)
			{
				$subData = $connector->getDataToShow();
				$return = array_merge($return, $subData);
			}

			return $return;
		}
		else
		{
			return array();
		}
	}

	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		if (\CSocNetUser::isCurrentUserModuleAdmin())
		{
			$this->canRead = true;
			return $this->canRead;
		}

		if ($comment = $this->loadLogCommentData())
		{
			if (strpos($comment["EVENT_ID"], "crm_") === 0)
			{
				$queryLog = \CSocNetLog::getList(
					array(),
					array(
						"ID" => intval($comment["LOG_ID"])
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID")
				);
				if (
					($log = $queryLog->fetch())
					&& Loader::includeModule("crm")
				)
				{
					$userPermissions = \CCrmPerms::getUserPermissions($userId);
					if ($log["ENTITY_TYPE"] == "CRMACTIVITY")
					{
						$bindings = \CCRMActivity::getBindings($log["ENTITY_ID"]);
						foreach($bindings as $binding)
						{
							if (\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission(
								$binding["OWNER_TYPE_ID"],
								$binding["OWNER_ID"],
								$userPermissions
							))
							{
								$this->canRead = true;

								return $this->canRead;
							}
						}
					}
					else
					{
						if (\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission(
							\CCrmLiveFeedEntity::resolveEntityTypeID($log["ENTITY_TYPE"]),
							$log["ENTITY_ID"],
							$userPermissions
						))
						{
							$this->canRead = true;

							return $this->canRead;
						}
						elseif (
							intval($comment["LOG_ID"]) > 0
							&& \CSocNetLogRights::checkForUser($comment["LOG_ID"], $userId)
						)
						{
							$this->canRead = true;

							return $this->canRead;
						}
					}
				}
			}
			elseif (
				intval($comment["LOG_ID"]) > 0
				&& \CSocNetLogRights::checkForUser($comment["LOG_ID"], $userId)
			)
			{
				$this->canRead = true;

				return $this->canRead;
			}
		}

		$this->canRead = false;

		return $this->canRead;
	}

	public function canUpdate($userId)
	{
		return $this->canRead($userId);
	}

	public function canConfidenceReadInOperableEntity()
	{
		return true;
	}

	public function canConfidenceUpdateInOperableEntity()
	{
		return true;
	}

/*
	public function addComment($authorId, array $data)
	{
	
	}
*/
	protected function loadLogCommentData()
	{
		$queryLogComment = \CSocNetLogComments::getList(
			array(),
			array(
				"ID" => $this->entityId
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "SOURCE_ID", "LOG_ID")
		);

		return ($this->logCommentData = $queryLogComment->fetch());
	}
}
