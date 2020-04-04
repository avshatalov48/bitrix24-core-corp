<?
/**
 * @internal
 * @access private
 */

namespace Bitrix\Tasks\Util
{
	use Bitrix\Main\ArgumentException;
	use Bitrix\Main\SystemException;
	use Bitrix\Tasks\Util\Process\FlowException;
	use Bitrix\Tasks\Util\Process\ExecutionResult;

	abstract class Process
	{
		protected $stages = 		array();
		protected $stageOrder = 	array();

		protected $stage = 			'';
		protected $step = 			0;

		protected $data = 			array();
		protected $time = 			0;
		protected $timeLimit = 		20; // in seconds
		protected $options = 		array();

		public function __construct($options = array())
		{
			$this->time = isset($options['INITIAL_TIME']) ? intval($options['INITIAL_TIME']) : time();
			$this->options = $options;

			$stages = $this->getStages();
			if(!is_array($stages) || empty($stages))
			{
				throw new ArgumentException('Illegal description of stages');
			}

			$codeRegExp = '^[a-z0-9]{1}[a-z0-9_]{1,}$';
			$i = 0;
			foreach($stages as $code => $info)
			{
				if(!preg_match('#'.$codeRegExp.'#i', $code))
				{
					throw new ArgumentException('Illegal stage code: '.$code);
				}

				if(!is_callable(array($this, $code.'Action')))
				{
					throw new ArgumentException('No action callback for: '.$code);
				}

				$this->stageOrder[$i] = $code;

				$i++;
			}

			$this->stages = $stages;

			$this->errors = new Error\Collection();

			$this->restore();

			if(isset($options['TIME_LIMIT']) && intval($options['TIME_LIMIT']))
			{
				$this->setTimeLimit(intval($options['TIME_LIMIT']));
			}

			$this->saveStartTime();
			$this->saveMemoryPeak();
		}

		protected static function getSessionKey()
		{
			return 'process';
		}

		abstract protected function getStages();

		protected function getMinimumTimeLimit()
		{
			return 2;
		}

		public function restore()
		{
			$sessionKey = static::getSessionKey();

			// ensure session_start() were called above

			if(!isset($_SESSION[$sessionKey]['STAGE']))
			{
				$_SESSION[$sessionKey]['STAGE'] = $this->stageOrder[0];
			}

			if(!isset($_SESSION[$sessionKey]['STEP']))
			{
				$_SESSION[$sessionKey]['STEP'] = 0;
			}

			if(!isset($_SESSION[$sessionKey]['DATA']))
			{
				$_SESSION[$sessionKey]['DATA'] = array();
			}

			$this->stage =& $_SESSION[$sessionKey]['STAGE'];
			$this->step =& $_SESSION[$sessionKey]['STEP'];
			$this->data =& $_SESSION[$sessionKey]['DATA'];
		}

		// reset current condition
		public function reset()
		{
			$this->stage = 	$this->stageOrder[0];
			$this->step = 	0;
			$this->data = 	array();

			$this->saveStartTime();
			$this->saveMemoryPeak();
		}

		public function execute()
		{
			$execResult = new ExecutionResult();

			$this->onBeforePerformIteration();

			if($this->stage === false)
			{
				throw new SystemException('No more stages to perform');
			}

			$stage = $this->stage;

			if(is_callable(array($this, $stage.'Before')))
			{
				call_user_func(array($this, $stage.'Before'));
			}

			$result = null;
			while($this->checkQuota())
			{
				$result = call_user_func_array(array($this, $stage.'Action'), array($execResult));
				$this->nextStep();

				if($result === true)
				{
					break;
				}
			}

			if($result === true)
			{
				$this->nextStage();
			}

			if(is_callable(array($this, $stage.'After')))
			{
				call_user_func(array($this, $stage.'After'));
			}

			$this->onAfterPerformIteration();
			$percent = $this->getPercent();

			$this->saveMemoryPeak();

			$execResult->setPercent($percent);

			return $execResult;
		}

		/////////////////////////////////////////////////
		/// Stageing
		/////////////////////////////////////////////////

		protected function getStageIndexByCode($stageCode)
		{
			$soBack = array_flip($this->stageOrder);

			if(!isset($soBack[$stageCode]))
				throw new FlowException('Stage not found for code: "'.$stageCode.'"');

			return $soBack[$stageCode];
		}

		protected function getStageCodeByIndex($stageIndex)
		{
			if(!isset($this->stageOrder[$stageIndex]))
				throw new FlowException('Stage not found for index: "'.$stageIndex.'"');

			return $this->stageOrder[$stageIndex];
		}

		// move to next stage
		public function nextStage() // todo: rename to moveStageForward()
		{
			$next = $this->getStageIndexByCode($this->stage) + 1;

			if(!isset($this->stageOrder[$next]))
				$this->stage = false;
			else
				$this->stage = $this->stageOrder[$next];

			$this->step = 0;
		}

		// move to next step
		public function nextStep() // todo: rename to moveStepForward()
		{
			$this->step++;
		}

		public function isStage($code)
		{
			return $this->stage == $code;
		}

		public function setStage($stage)
		{
			if(!isset($this->stages[$stage]))
				throw new FlowException('No such stage: "'.$stage.'"');

			$this->stage = $stage;
			$this->step = 0;
		}

		public function onBeforePerformIteration()
		{
		}

		public function onAfterPerformIteration()
		{
		}

		public function getStage()
		{
			return $this->stage;
		}

		public function getPreviousStage()
		{
			return $this->getStageCodeByIndex($this->getStageIndexByCode($this->stage) - 1);
		}

		public function getStep()
		{
			return $this->step;
		}

		public function getStageDescription($stage)
		{
			return isset($this->stages[$stage]) ? $this->stages[$stage] : array();
		}

		/////////////////////////////////////////////////
		/// Percentage
		/////////////////////////////////////////////////

		public function getStagePercent($stage)
		{
			if(intval($stage) < 0)
			{
				return 0;
			}

			if((string) intval($stage) == (string) $stage)
			{
				$stage = $this->getStageCodeByIndex($stage);
			}
			else
			{
				if(!isset($this->stages[$stage]))
					throw new FlowException('No such stage: "'.$stage.'"');
			}

			return $this->stages[$stage]['PERCENT'];
		}

		public function getCurrentPercentRange()
		{
			try
			{
				$prevStage = $this->getPreviousStage();
				$percent = $this->stages[$prevStage]['PERCENT'];
			}
			catch(FlowException $e)
			{
				$percent = 0;
			}

			return $this->getStagePercent($this->stage) - $percent;
		}

		public function getPercent()
		{
			if($this->stage === false)
			{
				return 100;
			}

			try
			{
				$prevStage = $this->getPreviousStage();
				$percent = $this->stages[$prevStage]['PERCENT'];
			}
			catch(FlowException $e)
			{
				$percent = 0;
			}

			$subPercent = 0;
			if(is_callable(array($this, $this->stage.'LocalPercent')))
			{
				$localPercent = call_user_func(array($this, $this->stage.'LocalPercent'));

				$base = $this->getStagePercent($this->stage) - $this->getStagePercent($this->getStageIndexByCode($this->stage) - 1);
				if($base > 0)
				{
					// rescale percent
					$subPercent = floor(($localPercent/100) * $base);
				}
			}

			return $percent + $subPercent;
		}

		/*
		public function calcSubPercent($range)
		{
			if(!$range) return 0;

			return round(($this->step / $range)*($this->getStagePercent($this->stage) - $this->getStagePercent($this->stage - 1)));
		}
		*/

		/////////////////////////////////////////////////
		/// Quotas info
		/////////////////////////////////////////////////

		public function checkQuota()
		{
			return (time() - $this->time) < $this->timeLimit;
		}

		public function setTimeLimit($timeLimit)
		{
			if($timeLimit == intval($timeLimit))
			{
				$minLimit = $this->getMinimumTimeLimit();

				if($timeLimit < $minLimit)
				{
					$timeLimit = $minLimit;
				}

				$this->timeLimit = $timeLimit;
			}
		}

		public function getMemoryPeak()
		{
			return $this->data['memory_peak'];
		}

		protected function saveStartTime()
		{
			if(!isset($this->data['process_time']))
				$this->data['process_time'] = time();
		}

		protected function saveMemoryPeak()
		{
			$mp = memory_get_peak_usage(false);

			if(!isset($this->data['memory_peak']))
				$this->data['memory_peak'] = $mp;
			else
			{
				if($this->data['memory_peak'] < $mp)
					$this->data['memory_peak'] = $mp;
			}
		}

		/////////////////////////////////////////////////
		/// Diagnostics tools
		/////////////////////////////////////////////////

		protected function getHitTime()
		{
			return time() - $this->time;
		}

		protected function getProcessTime()
		{
			return time() - $this->data['process_time'];
		}

		protected function getProcessTimeString()
		{
			return $this->getTimeString($this->getProcessTime());
		}

		protected function getHitTimeString()
		{
			return $this->getTimeString($this->getHitTime());
		}

		protected function getTimeString($time = 0)
		{
			if($time == 0)
			{
				$h = $m = $s = 0;
			}
			else
			{
				$h = floor($time / 3600);
				$m = floor(($time - $h * 3600) / 60);
				$s = $time - $h * 3600 - $m * 60;

				if(strlen($m) == 1)
				{
					$m = '0'.$m;
				}

				if(strlen($s) == 1)
				{
					$s = '0'.$s;
				}
			}

			return $h.':'.$m.':'.$s;
		}

		protected function getTimeStampString()
		{
			return '['.date('H:i:s').']';
		}

		protected function getMemoryPeakString()
		{
			return $this->getMemoryPeak() / 1048576;
		}

		/////////////////////////////////////////////////
		/// Util
		/////////////////////////////////////////////////

		public function getData()
		{
			return $this->data;
		}
	}
}

namespace Bitrix\Tasks\Util\Process
{
	use Bitrix\Main\SystemException;

	class FlowException extends SystemException {};

	final class ExecutionResult extends \Bitrix\Tasks\Util\Result
	{
		protected $percent = 0;

		public function getPercent()
		{
			return $this->percent;
		}

		public function setPercent($percent)
		{
			$this->percent = intval($percent);
		}
	}
}