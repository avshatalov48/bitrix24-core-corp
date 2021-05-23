<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div id="timeman-container" class="timeman-container planner-container<?=(IsAmPmMode() ? " am-pm-mode" : "")?>"><?
	?><div class="timeman-wrap planner-wrap"><?
		?><span id="timeman-block" class="timeman-block"><?
			?><span class="bx-time" id="timeman-timer"></span><?
			?><span class="timeman-right-side"><?
				?><span class="timeman-info" id="timeman-info"></span><?
				?><span class="timeman-task-time" id="timeman-task-time" style="display: none;"><i></i><span id="timeman-task-timer"></span></span><?
			?></span><?
			?><span class="timeman-background"></span><?
		?></span><?
	?></div>
</div>

<?$frame = $this->createFrame("planner")->begin("");

if (is_array($arResult['DATA']) && count($arResult['DATA']) > 0)
{
?>
<script type="text/javascript">
(function(){
	var formatTime = function(ts, bSec)
	{
		return BX.util.str_pad(parseInt(ts/3600), 2, '0', 'left')+':'+BX.util.str_pad(parseInt(ts%3600/60), 2, '0', 'left')+(!!bSec ? (':'+BX.util.str_pad(ts%60, 2, '0', 'left')) : '');
	};

	window.plannerUnFormatTime = function(time)
	{
		var q = time.split(/[\s:]+/);
		if (q.length == 3)
		{
			var mt = q[2];
			if (mt == 'pm' && q[0] < 12)
				q[0] = parseInt(q[0], 10) + 12;

			if (mt == 'am' && q[0] == 12)
				q[0] = 0;

		}
		return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
	};

	var BXPLANNER = new BX.CPlanner(<?=CUtil::PhpToJsObject($arResult['DATA']);?>),
		NODE_TASKS = null,
		NODE_EVENTS = null,
		NODE_TASK_TIME = null,
		NODE_TASK_TIMER = null,

		bTaskTimerSwitch = false,
		timer = null;

	BXPLANNER.WND = null;

	BX.addCustomEvent(
		BXPLANNER, 'onPlannerDataRecieved', function(ob, DATA)
		{
			if(!!DATA.CALENDAR_ENABLED)
			{
				var d = DATA.EVENT_TIME;
				if(d != '')
				{
					var t = plannerUnFormatTime(DATA.EVENT_TIME),
						dt = new Date();
					if(t < dt.getHours()*3600 + dt.getMinutes() * 60)
					{
						d = '';
					}
				}

				if(d != '')
				{
					if(!NODE_EVENTS)
					{
						NODE_EVENTS = BX.create('SPAN', {props: {
							className: 'timeman-event',
							id: 'timeman-event'
						}});
					}

					NODE_EVENTS.innerHTML = d;
					NODE_EVENTS.style.display = 'inline-block';
				}
				else if (!!NODE_EVENTS)
				{
					NODE_EVENTS.style.display = 'none';
				}
			}

			if(!!DATA.TASKS_ENABLED)
			{
				if(!NODE_TASKS)
				{
					NODE_TASKS = BX.create('SPAN', {
						style: {
							display: !!NODE_TASK_TIME && NODE_TASK_TIME.style.display != 'none' ? 'inline-block' : 'block'
						},
						props: {
							className: 'timeman-tasks',
							id: 'timeman-tasks'
						}
					})
				}

				NODE_TASKS.innerHTML = parseInt(DATA.TASKS_COUNT)||'0';
			}


			BX.adjust(BX('timeman-info', true), {children: [NODE_EVENTS, NODE_TASKS]});

			if (!timer)
			{
				timer = BX.timer({container: BX('timeman-timer'), display : "bitrix24_time"});
			}
		}
	);

	BX.addCustomEvent('onTaskTimerChange', function(params)
	{
		if (params.action === 'refresh_daemon_event')
		{
			if(!!bTaskTimerSwitch)
			{
				if(!NODE_TASK_TIME)
				{
					NODE_TASK_TIME = BX('timeman-task-time')
				}

				NODE_TASK_TIME.style.display = '';

				if(!!NODE_TASKS && NODE_TASKS.style.display != 'none')
				{
					NODE_TASKS.style.display = 'inline-block';
				}

				bTaskTimerSwitch = false;
			}

			var s = '';
			s += formatTime(parseInt(params.data.TIMER.RUN_TIME||0) + parseInt(params.data.TASK.TIME_SPENT_IN_LOGS||0), true);

			if(!!params.data.TASK.TIME_ESTIMATE && (params.data.TASK.TIME_ESTIMATE > 0))
			{
				s += ' / ' + formatTime(parseInt(params.data.TASK.TIME_ESTIMATE||0));
			}

			if(!NODE_TASK_TIMER)
			{
				NODE_TASK_TIMER = BX('timeman-task-timer')
			}

			NODE_TASK_TIMER.innerHTML = s;
		}
		else if(params.action === 'start_timer')
		{
			bTaskTimerSwitch = true;
		}
		else if(params.action === 'stop_timer')
		{
			if(!NODE_TASK_TIME)
			{
				NODE_TASK_TIME = BX('timeman-task-time');
			}

			NODE_TASK_TIME.style.display = 'none';
			if(!!NODE_TASKS && NODE_TASKS.style.display != 'none')
			{
				NODE_TASKS.style.display = 'block';
			}
		}
		else if (params.action === 'init_timer_data')
		{
			if(!NODE_TASK_TIME)
				NODE_TASK_TIME = BX('timeman-task-time');

			if (
				params.data.TIMER
				&& params.data.TASK
				&& (params.data.TIMER.TASK_ID == params.data.TASK.ID)
				&& (params.data.TIMER.TIMER_STARTED_AT > 0)
				&& (params.data.TASK.STATUS != 5)
				&& (params.data.TASK.STATUS != 4)
			)
			{
				NODE_TASK_TIME.style.display = '';

				if(!!NODE_TASKS && NODE_TASKS.style.display != 'none')
				{
					NODE_TASKS.style.display = 'inline-block';
				}

				var s = '';
				s += formatTime(parseInt(params.data.TIMER.RUN_TIME||0) + parseInt(params.data.TASK.TIME_SPENT_IN_LOGS||0), true);

				if(!!params.data.TASK.TIME_ESTIMATE && (params.data.TASK.TIME_ESTIMATE > 0))
				{
					s += ' / ' + formatTime(parseInt(params.data.TASK.TIME_ESTIMATE||0));
				}

				if(!NODE_TASK_TIMER)
				{
					NODE_TASK_TIMER = BX('timeman-task-timer');
				}

				NODE_TASK_TIMER.innerHTML = s;
			}
			else
			{
				NODE_TASK_TIME.style.display = 'none';
			}
		}
	});

	BX.ready(function(){
		BX.bind(BX('timeman-block', true), 'click', function()
		{
			if(!BXPLANNER.WND)
			{
				BXPLANNER.WND = new BX.PopupWindow('planner_main', this, {
					lightShadow: true,
					autoHide: true,
					offsetTop : 10,
					offsetLeft : -60,
					zIndex : -1,
					bindOptions: {
						forceBindPosition: true,
						forceTop: true
					},
					angle: {
						position: "top",
						offset: 130
					},
					events: {
						onPopupClose: function() {
							BX.removeClass(BX('timeman-block', true), "timeman-block-active");
						}
					}
				});
			}

			if(!BXPLANNER.WND.isShown())
			{
				BXPLANNER.update();
				BXPLANNER.WND.setContent(BX.create('DIV', {
					props: {
						className: 'tm-popup-content'
					},
					children: [
						BXPLANNER.drawAdditional(),
						BX.create('DIV', {
							props: {className: 'tm-tabs-content tm-tab-content'},
							style: {display: 'block'}, // core_timeman.css is hiding that block by default
							children: [BXPLANNER.draw()]
						})
					]
				}));
			}

			BX.addClass(this, "timeman-block-active");
			BXPLANNER.WND.show();
		});
	});

	BX.timer.registerFormat("bitrix24_time", B24.Timemanager.formatCurrentTime);
	BXPLANNER.draw();

	window.BXPLANNER = BXPLANNER;
})();
</script>
<?
}

$frame->end();?>