<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;

final class SonetLogConnector extends StubConnector implements ISupportForeignConnector
{
	private $canRead = null;
	private $logEntryData;

	public function getDataToShow()
	{
		if(!($log = $this->loadLogEntryData()))
		{
			return null;
		}

		$data = array();

		if (
			strpos($log["EVENT_ID"], "crm_") === 0
			&& Loader::includeModule('crm')
		)
		{
			if (strpos($log["EVENT_ID"], "_message") > 0)
			{
				$connector = new CrmMessageConnector($log["ID"]);
				$subData = $connector->getDataToShow();
				$data = array_merge($data, $subData);
			}

			$connector = null;
			if ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Deal)
			{
				$connector = new CrmDealConnector($log["ENTITY_ID"]);
			}
			elseif ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Lead)
			{
				$connector = new CrmLeadConnector($log["ENTITY_ID"]);
			}
			elseif ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Company)
			{
				$connector = new CrmCompanyConnector($log["ENTITY_ID"]);
			}
			elseif ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Contact)
			{
				$connector = new CrmContactConnector($log["ENTITY_ID"]);
			}

			if ($connector)
			{
				$subData = $connector->getDataToShow();
				$data = array_merge($data, $subData);
			}

			return $data;
		}
		else
		{
			return array();
		}
	}

	public function addComment($authorId, array $data)
	{
		if(!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$query = \CSocNetLog::getList(
			array(),
			array(
				"ID" => $this->entityId
			),
			false,
			false,
			array("ID", "EVENT_ID", "ENTITY_TYPE", "ENTITY_ID")
		);
		$row = $query->fetch();
		if(
			!$row
			|| !($commentEvent = \CSocNetLogTools::FindLogCommentEventByLogEventID($row["EVENT_ID"]))
		)
		{
			return;
		}

		$fieldsForSocnet = array(
			"ENTITY_TYPE" => $row["ENTITY_TYPE"],
			"ENTITY_ID" => $row["ENTITY_ID"],
			"EVENT_ID" => $commentEvent["EVENT_ID"],
			"=LOG_DATE" => Application::getInstance()->getConnection()->getSqlHelper()->getCurrentDateTimeFunction(),
			"MESSAGE" => $data['text'],
			"TEXT_MESSAGE" => $data['text'],
			"URL" => "",
			"LOG_ID" => $row["ID"],
			"USER_ID" => $authorId
		);

		if(!empty($data['fileId']))
		{
			$fieldsForSocnet['UF_SONET_COM_DOC'] = array($data['fileId']);
		}
		elseif(!empty($data['versionId']))
		{
			$fieldsForSocnet['UF_SONET_COM_VER'] = $data['versionId'];
		}

		if ($commentId = \CSocNetLogComments::add($fieldsForSocnet, false, false, false))
		{
			\CSocNetLogComments::update($commentId, array(
				"RATING_TYPE_ID" => "LOG_COMMENT",
				"RATING_ENTITY_ID" => $commentId
			));
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

		if ($log = $this->loadLogEntryData())
		{
			if (strpos($log["EVENT_ID"], "crm_") === 0 && Loader::includeModule('crm'))
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
					elseif (\CSocNetLogRights::checkForUser($log["ID"], $userId))
					{
						$this->canRead = true;

						return $this->canRead;
					}
				}
			}
			elseif (\CSocNetLogRights::checkForUser($log["ID"], $userId))
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

	protected function loadLogEntryData()
	{
		$queryLog = \CSocNetLog::getList(
			array(),
			array(
				"ID" => $this->entityId
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "SOURCE_ID")
		);
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */

		return ($this->logEntryData = $queryLog->fetch());
	}
}
