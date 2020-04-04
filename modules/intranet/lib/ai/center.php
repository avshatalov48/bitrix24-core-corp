<?
namespace Bitrix\Intranet\AI;

use Bitrix\Bitrix24\Integration\AssistantApp;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\FaceId\FaceId;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Rest\AppTable;

class Center
{
	public static function getAssistantApp()
	{
		$app = null;
		if (Loader::includeModule("bitrix24"))
		{
			$app = AssistantApp::getInfo();
		}
		else if (Loader::includeModule("rest"))
		{
			$app = AppTable::getByClientId("bitrix.assistant");
		}

		return $app;
	}

	public static function getAssistants()
	{
		if (LANGUAGE_ID !== "ru")
		{
			return [];
		}

		$app = static::getAssistantApp();
		if (!Loader::includeModule("rest") || ($app !== null && !\CRestUtil::checkAppAccess("bitrix.assistant")))
		{
			return [];
		}

		$licensePrefix = null;
		if (Loader::includeModule("bitrix24"))
		{
			$licensePrefix = \CBitrix24::getLicensePrefix();
		}

		$items = [];
		$selected = is_array($app) && $app["ACTIVE"] === "Y";

		if ($licensePrefix !== "ua")
		{
			$items[] = [
				"id" => "alice",
				"name" => Loc::getMessage("INTRANET_AI_ASSISTANT_ALICE"),
				"iconClass" => "intranet-ai-center-icon intranet-ai-center-icon-alice",
				"iconColor" => "#9426ff",
				"selected" => $selected,
				"data" => []
			];
		}

		$items[] = [
			"id" => "google",
			"name" => Loc::getMessage("INTRANET_AI_ASSISTANT_GOOGLE"),
			"iconClass" => "intranet-ai-center-icon intranet-ai-center-icon-google",
			"iconColor" => "#ea4335",
			"selected" => $selected,
			"data" => []
		];

		return $items;
	}

	public static function getCrmScoring()
	{
		if (!ModuleManager::isModuleInstalled("crm"))
		{
			return [];
		}

		$scoringExists = false;
		$mlInstalled = false;
		if(Loader::includeModule("ml") && false)
		{
			$mlInstalled = true;
			$modelNames = Scoring::getAvailableModelNames();
			foreach ($modelNames as $modelName)
			{
				$model = Scoring::getModelByName($modelName);
				if($model && $model->isReady())
				{
					$scoringExists = true;
					break;
				}
			}
		}

		return [
			[
				"id" => "crm-scoring",
				"name" => Loc::getMessage("INTRANET_AI_CRM_SCORING"),
				"iconClass" => "intranet-ai-center-icon intranet-ai-center-icon-crm",
				"iconColor" => "#12bff5",
				"comingSoon" => !$mlInstalled,
				"selected" => $scoringExists,
				"data" => [
					"url" => "/crm/ml/model/list/"
				]
			]
		];
	}

	public static function getSegmentScoring()
	{
		if (!ModuleManager::isModuleInstalled("crm"))
		{
			return [];
		}

		return [
			[
				"id" => "segment-scoring",
				"name" => Loc::getMessage("INTRANET_AI_SEGMENT_SCORING"),
				"iconClass" => "intranet-ai-center-icon intranet-ai-center-icon-segment",
				"iconColor" => "#34cde0",
				"comingSoon" => true,
				"data" => []
			]
		];
	}

	public static function getFaceCard()
	{
		if (!Loader::includeModule("rest") || !Loader::includeModule("faceId") || !FaceId::isAvailable())
		{
			return [];
		}

		$appId = AppTable::getByClientId(\CRestUtil::BITRIX_1C_APP_CODE)['CLIENT_ID'];
		$appSettings = Option::get("rest", "options_".$appId, "");
		if (!empty($appSettings))
		{
			$appSettings = unserialize($appSettings);
		}

		return [
			[
				"id" => "facecard",
				"name" => Loc::getMessage("INTRANET_AI_FACE_CARD"),
				"iconClass" => "ui-icon ui-icon-service-1c",
				"iconColor" => "",
				"selected" => isset($appSettings["facecard"]) && $appSettings["facecard"] === "Y" ? true : false,
				"data" => array(
					"url" => SITE_DIR."onec/facecard/"
				)
			]
		];
	}
}