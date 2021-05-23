<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(is_array($arResult['DATA'])&&count($arResult['DATA'])>0)
{
?>
<span class="site-selector-separator"></span><span class="tm-dashboard"><span id="bx_tm" class="tm-dashboard-inner"></span></span>
<script type="text/javascript">
(function(){
	var BXPLANNER = new BX.CPlanner(<?=CUtil::PhpToJsObject($arResult['DATA']);?>),
		BXPLANNERWND = null,
		NODE_TASKS = null,
		NODE_EVENTS = null,
		NODE_CLOCK = null;

	BX.addCustomEvent(
		BXPLANNER, 'onPlannerDataRecieved', function(ob, DATA)
		{
			if(!!DATA.CALENDAR_ENABLED)
			{
				if(DATA.EVENT_TIME != '')
				{
					if(!NODE_EVENTS)
					{
						NODE_EVENTS = BX.create('SPAN');
					}

					NODE_EVENTS.innerHTML = '<span class="tm-dashboard-bell"></span><span class="tm-dashboard-text">'+DATA.EVENT_TIME+'</span>';
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
					NODE_TASKS = BX.create('SPAN');
				}

				NODE_TASKS.innerHTML = '<span class="tm-dashboard-flag"></span><span class="tm-dashboard-text">'+(parseInt(DATA.TASKS_COUNT)||'0')+'</span>';
				NODE_TASKS.style.display = 'inline-block';
			}

			if(!NODE_CLOCK)
			{
				NODE_CLOCK = BX.create('SPAN', {
					style: {display: 'inline-block'},
					children: [
						BX.create('SPAN', {props:{className:'tm-dashboard-clock'}}),
						BX.create('SPAN', {
							props: {className:'tm-dashboard-text'}
						})
					]
				});
				BX.timer.clock(NODE_CLOCK.lastChild);
			}

			BX.adjust(BX('bx_tm', true), {children: [NODE_EVENTS, NODE_CLOCK, NODE_TASKS]});
		}
	);

	BX.ready(function(){
		BX.bind(BX('bx_tm', true), 'click', function()
		{
			if(!BXPLANNER.WND)
			{
				BXPLANNER.WND = new BX.PopupWindow('planner_main', this, {
					autoHide: true,
					bindOptions: {
						forceBindPosition: true,
						forceTop: true
					},
					angle: {
						position: "top",
						offset: 50
					}
				});
			}

			if(!BXPLANNER.WND.isShown())
			{
				BXPLANNER.update();
				BXPLANNER.WND.setContent(BX.create('DIV', {props: {className:'planner-content'}, children: [
					BXPLANNER.drawAdditional(),
					BXPLANNER.draw()
				]}));
			}

			BXPLANNER.WND.show();
		});
	});

	BXPLANNER.draw();

	window.BXPLANNER = BXPLANNER;
})();
</script>
<?
}
?>

