<?php
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../..');

//select status,count(*) from b_controller_task group by status
//update b_controller_task set status='L' where status='F' and task_id='REMOTE_COMMAND'

/*Command line arguments*/
$limit = 10000;
$show_eta = false;
$c = count($argv);
for ($i = 1; $i < $c; $i++)
{
	if (preg_match('/^--limit=(\d+)$/', $argv[$i], $match))
	{
		$limit = intval($match[1]);
	}
	elseif (preg_match('/^--show-eta=([yYnN])$/', $argv[$i], $match))
	{
		$show_eta = $match[1] === 'y' || $match[1] === 'Y';
	}
	elseif (preg_match('/^--document-root=(.+)$/', $argv[$i], $match) && is_dir($match[1]))
	{
		$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] = $match[1];
	}
	else
	{
		fwrite(STDERR, "usage: [--show-eta=y] [--limit=10000] [--document-root=/var/www/html]\n");
		exit(1);
	}
}

/*Bitrix init starts here*/
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);
define('BX_CRONTAB', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
@set_time_limit (0);
@ignore_user_abort(true);
@session_destroy();
while (@ob_end_clean());

if (CModule::IncludeModule('controller'))
{
	if (!$show_eta)
	{
		CControllerTask::ProcessAllTask($limit);
	}
	else
	{
		$interval = 60; //Seconds
		$last_count = false;
		$last_time = false;
		do
		{
			$stime = microtime(true);
			$rs = CControllerTask::GetList([], [
				'=STATUS' => ['P', 'N', 'L'],
			], true);
			$current_count = $rs->Fetch()['C'];
			if ($last_count === false)
			{
				echo sprintf("%s tasks remains: %d\n"
					,date('Y-m-d H:i:s')
					,$current_count
				);
			}
			else
			{
				$tasks_done = $last_count - $current_count;
				if ($tasks_done > 0 && $current_count > 0)
				{
					$tasks_per_second = $tasks_done / ($stime - $last_time);
					$seconds_remains = $current_count / $tasks_per_second;
					$eta = time() + $seconds_remains;
					$hours_remains = intval($seconds_remains / 3600);
					$seconds_remains -= $hours_remains * 3600;
					$minutes_remains = intval($seconds_remains / 60);
					$seconds_remains -= $minutes_remains * 60;
					$seconds_remains = intval($seconds_remains);

					echo sprintf("%s tasks remains: %d; done: %d; per second: %0.2f; todo: %02d:%02d:%02d; eta: %s\n"
						,date('Y-m-d H:i:s')
						,$current_count
						,$tasks_done
						,$tasks_per_second
						,$hours_remains, $minutes_remains, $seconds_remains
						,date('Y-m-d H:i:s', $eta)
					);
				}
				else
				{
					echo sprintf("%s tasks remains: %d;  done: %d\n"
						,date('Y-m-d H:i:s')
						,$current_count
						,$tasks_done
					);
				}
			}
			$last_count = $current_count;
			$last_time = $stime;
			usleep(($interval - (microtime(true) - $stime)) * 1000000);
		}
		while ($current_count);
	}
}
