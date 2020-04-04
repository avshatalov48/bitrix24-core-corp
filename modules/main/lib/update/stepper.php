<?
namespace Bitrix\Main\Update;
use Bitrix\Main\HttpApplication;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Context;
use \Bitrix\Main\Localization\Loc;

/**
 * Class Stepper
 * @package Bitrix\Main\Update
 * This class can be used if only:
 * 1. you do not alter tables in DB. Agent will not be executed if module is not installed.
 * Code to bind agent in updater:
 * \Bitrix\Main\Update\Stepper::bindClass('Bitrix\Tasks\Update1701', 'tasks');
 * or
 * if($updater->CanUpdateDatabase()) {
$basePath = $updater->CanUpdateKernel() ? $updater->curModulePath.'/lib/somepath' : BX_ROOT.'/modules/lists/lib/somepath';
if(include_once($_SERVER["DOCUMENT_ROOT"].$basePath."ecrmpropertyupdate.php"))
\Bitrix\Lists\SomePath\EcrmPropertyUpdate::bind();
}
 */
abstract class Stepper
{
	protected static $moduleId = "main";
	protected $deleteFile = false;
	private static $filesToUnlink = array();
	private static $countId = 0;
	const CONTINUE_EXECUTION = true;
	const FINISH_EXECUTION = false;

	protected $queueName = "Queue";
	protected $checkerName = "Checker_";
	protected $baseName = "Base_";
	protected $errorName = "Error_";

	/**
	 * Returns HTML to show updates.
	 * @param array|string $ids
	 * @param string $title
	 * @return string
	 */
	public static function getHtml($ids = array(), $title = "")
	{
		if (static::class !== __CLASS__)
		{
			$title = static::getTitle();
			$ids = [static::$moduleId => [ static::class ]];
			return call_user_func(array(__CLASS__, "getHtml"), $ids, $title);
		}

		$return = array();
		$count = 0;
		$steps = 0;

		if (is_string($ids))
		{
			$ids = array($ids => null);
		}

		foreach($ids as $moduleId => $classesId)
		{
			if (is_string($classesId))
				$classesId = array($classesId);
			if (is_array($classesId))
			{
				foreach($classesId as $classId)
				{
					if (($option = Option::get("main.stepper.".$moduleId, $classId, "")) !== "")
					{
						$option = unserialize($option);
						if (is_array($option))
						{
							$return[] = array(
								"moduleId" => $moduleId,
								"class" => $classId,
								"title" => $option["title"],
								"steps" => $option["steps"],
								"count" => $option["count"]
							);
							$count += $option["count"];
							$steps += ($option["count"] > $option["steps"] ? $option["steps"] : $option["count"]);
						}
					}
				}
			}
			else if (is_null($classesId))
			{
				$options = Option::getForModule("main.stepper.".$moduleId);
				foreach($options as $classId => $option)
				{
					$option = unserialize($option);
					if (is_array($option))
					{
						$return[] = array(
							"moduleId" => $moduleId,
							"class" => $classId,
							"title" => $option["title"],
							"steps" => $option["steps"],
							"count" => $option["count"]
						);
						$count += $option["count"];
						$steps += ($option["count"] > $option["steps"] ? $option["steps"] : $option["count"]);
					}
				}
			}
		}

		$result = '';
		if (!empty($return))
		{
			$id = ++self::$countId;
			\CJSCore::Init(array('update_stepper'));
			$title = empty($title) ? self::getTitle() : $title;
			$progress = $count > 0 ? intval( $steps * 100 / $count) : 0;
			$result .= <<<HTML
<div class="main-stepper main-stepper-show" id="{$id}-container" data-bx-steps-count="{$count}">
	<div class="main-stepper-info" id="{$id}-title">{$title}</div>
	<div class="main-stepper-inner">
		<div class="main-stepper-bar">
			<div class="main-stepper-bar-line" id="{$id}-bar" style="width:{$progress}%;"></div>
		</div>
		<div class="main-stepper-steps"><span id="{$id}-steps">{$steps}</span> / <span id="{$id}-count">{$count}</span></div>
		<div class="main-stepper-error-text" id="{$id}-error"></div>
	</div>
</div>
HTML;
			$return = \CUtil::PhpToJSObject($return);
			$result = <<<HTML
<div class="main-stepper-block">{$result}
<script>BX.ready(function(){ if (BX && BX["UpdateStepperRegister"]) { BX.UpdateStepperRegister({$id}, {$return}); }});</script>
</div>
HTML;
		}
		return $result;
	}

	public static function getTitle()
	{
		return Loc::getMessage("STEPPER_TITLE");
	}
	/**
	 * Execute an agent
	 * @return string
	 */
	public static function execAgent()
	{
		$updater = self::createInstance();
		$className = get_class($updater);

		$option = Option::get("main.stepper.".$updater->getModuleId(), $className, "");
		if ($option !== "" )
			$option = unserialize($option);
		$option = is_array($option) ? $option : array();
		if ($updater->execute($option) === self::CONTINUE_EXECUTION)
		{
			$option["steps"] = (array_key_exists("steps", $option) ? intval($option["steps"]) : 0);
			$option["count"] = (array_key_exists("count", $option) ? intval($option["count"]) : 0);
			$option["title"] = $updater::getTitle();

			Option::set("main.stepper.".$updater->getModuleId(), $className, serialize($option));
			return $className . '::execAgent();';
		}
		if ($updater->deleteFile === true && \Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24") !== true)
		{
			$res = new \ReflectionClass($updater);
			self::$filesToUnlink[] = $res->getFileName();
		}
		Option::delete("main.stepper.".$updater->getModuleId(), array("name" => $className));

		return '';
	}

	public function __destruct()
	{
		if (!empty(self::$filesToUnlink))
		{
			while ($file = array_pop(self::$filesToUnlink))
			{
				$file = \CBXVirtualIo::GetInstance()->GetFile($file);

				$langDir = $fileName = "";
				$filePath = $file->GetPathWithName();
				while(($slashPos = strrpos($filePath, "/")) !== false)
				{
					$filePath = substr($filePath, 0, $slashPos);
					$langPath = $filePath."/lang";
					if(is_dir($langPath))
					{
						$langDir = $langPath;
						$fileName = substr($file->GetPathWithName(), $slashPos);
						break;
					}
				}
				if ($langDir <> "" && ($langDir = \CBXVirtualIo::GetInstance()->GetDirectory($langDir)) &&
					$langDir->IsExists())
				{
					$languages = $langDir->GetChildren();
					foreach ($languages as $language)
					{
						if ($language->IsDirectory() &&
							($f = \CBXVirtualIo::GetInstance()->GetFile($language->GetPathWithName().$fileName)) &&
							$f->IsExists())
						{
							$f->unlink();
						}
					}
					unset($f);
				}
				$file->unlink();
			}
			unset($file);
		}
	}
	/**
	 * Executes some action, and if return value is false, agent will be deleted.
	 * @param array $option Array with main data to show if it is necessary like {steps : 35, count : 7}, where steps is an amount of iterations, count - current position.
	 * @return boolean
	 */
	abstract function execute(array &$option);
	/**
	 * Just fabric method.
	 * @return Stepper
	 */
	public static function createInstance()
	{
		return new static;
	}
	/**
	 * Wrap-function to get moduleId.
	 * @return string
	 */
	public static function getModuleId()
	{
		return static::$moduleId;
	}
	/**
	 * Adds agent for current class.
	 * @param int $delay Delay for running agent
	 * @return void
	 */
	public static function bind($delay = 180)
	{
		/** @var Stepper $c */
		$c = get_called_class();
		self::bindClass($c, $c::getModuleId(), $delay);
	}

	/**
	 * Adds agent for class $className for $moduleId module. Example for updater: \Bitrix\Main\Stepper::bindClass('\Bitrix\SomeModule\SomeClass', 'somemodule').
	 * @param string $className Class like \Bitrix\SomeModule\SomeClass.
	 * @param string $moduleId Module ID like somemodule.
	 * @param int $delay Delay for running agent
	 * @return void
	 */
	public static function bindClass($className, $moduleId, $delay = 180)
	{
		if (class_exists("\CAgent"))
		{
			$addAgent = true;

			if ($delay <= 0)
			{
				/** @var Stepper $className */
				$addAgent = $className::execAgent() !== '';
			}

			if ($addAgent)
			{
				\CAgent::AddAgent(
					$className.'::execAgent();',
					$moduleId,
					"Y",
					1,
					"",
					"Y",
					\ConvertTimeStamp(time()+\CTimeZone::GetOffset() + (int) $delay, "FULL"),
					100,
					false,
					false
				);
				if (Option::get("main.stepper.".$moduleId, $className, "") === "")
					Option::set("main.stepper.".$moduleId, $className, serialize([]));
			}
		}
		else
		{
			global $DB;
			$name = $DB->ForSql($className.'::execAgent();', 2000);
			$className = $DB->ForSql($className);
			$moduleId = $DB->ForSql($moduleId);
			if (!(($agent = $DB->Query("SELECT ID FROM b_agent WHERE MODULE_ID='".$moduleId."' AND NAME = '".$name."' AND USER_ID IS NULL")->Fetch()) && $agent))
			{
				$DB->Query("INSERT INTO b_agent (MODULE_ID, SORT, NAME, ACTIVE, AGENT_INTERVAL, IS_PERIOD, NEXT_EXEC) VALUES ('".$moduleId."', 100, '".$name."', 'Y', 1, 'Y', ".($delay > 0 ? "DATE_ADD(now(), INTERVAL ". ((int) $delay)." SECOND)" : $DB->GetNowFunction()).")");
				$DB->Query("INSERT INTO b_option (`MODULE_ID`, `NAME`, `VALUE`)".
					"VALUES ('main.stepper.{$moduleId}', '".$className."', 'a:0:{}')".
					"ON DUPLICATE KEY UPDATE `VALUE` = 'a:0:{}'"
				);
			}
		}
	}
	/**
	 * Just method to check request.
	 * @return void
	 */
	public static function checkRequest()
	{
		$result = array();
		$data = Context::getCurrent()->getRequest()->getPost("stepper");
		if (is_array($data))
		{
			foreach ($data as $stepper)
			{
				if (($option = Option::get("main.stepper.".$stepper["moduleId"], $stepper["class"], "")) !== "" &&
					($res = unserialize($option)) && is_array($res))
				{
					$r = array(
						"moduleId" => $stepper["moduleId"],
						"class" => $stepper["class"],
						"steps" => $res["steps"],
						"count" => $res["count"]
					);
					$result[] = $r;
				}
			}
		}
		self::sendJson($result);
	}
	/**
	 * Sends json.
	 * @param $result
	 * @return void
	 */
	private static function sendJson($result)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		while(ob_end_clean());

		header('Content-Type:application/json; charset=UTF-8');

		echo Json::encode($result);
		\CMain::finalActions();
		die;
	}

	protected function writeToLog(\Exception $exception)
	{
		$application = HttpApplication::getInstance();
		$exceptionHandler = $application->getExceptionHandler();
		$exceptionHandler->writeToLog($exception);
	}

	protected function getQueue(): array
	{
		return $this->getOptionData($this->queueName);
	}

	protected function setQueue(array $queue): void
	{
		$queueId = (string) current($queue);
		$this->checkerName = (strpos($this->checkerName, $queueId) === false ?
			$this->checkerName.$queueId : $this->checkerName);
		$this->baseName = (strpos($this->baseName, $queueId) === false ?
			$this->baseName.$queueId : $this->baseName);
		$this->errorName = (strpos($this->errorName, $queueId) === false ?
			$this->errorName.$queueId : $this->errorName);
	}

	protected function getQueueOption()
	{
		return $this->getOptionData($this->baseName);
	}

	protected function saveQueueOption(array $data)
	{
		Option::set(static::$moduleId, $this->baseName, serialize($data));
	}

	protected function deleteQueueOption()
	{
		$queue = $this->getQueue();
		$this->setQueue($queue);
		$this->deleteCurrentQueue($queue);
		Option::delete(static::$moduleId, ["name" => $this->checkerName]);
		Option::delete(static::$moduleId, ["name" => $this->baseName]);
	}

	protected function deleteCurrentQueue(array $queue): void
	{
		$queueId = current($queue);
		$currentPos = array_search($queueId, $queue);
		if ($currentPos !== false)
		{
			unset($queue[$currentPos]);
			Option::set(static::$moduleId, $this->queueName, serialize($queue));
		}
	}

	protected function isQueueEmpty()
	{
		$queue = $this->getOptionData($this->queueName);
		return empty($queue);
	}

	protected function getOptionData($optionName)
	{
		$option = Option::get(static::$moduleId, $optionName);
		$option = ($option !== "" ? unserialize($option) : []);
		return (is_array($option) ? $option : []);
	}

	protected function deleteOption($optionName)
	{
		Option::delete(static::$moduleId, ["name" => $optionName]);
	}
}
?>