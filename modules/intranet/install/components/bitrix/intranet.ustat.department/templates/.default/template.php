<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX']))
{
	$APPLICATION->RestartBuffer();
}
else
{
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/intranet.ustat/style.css');
	$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/amcharts/3.0/amcharts.js');
	$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/amcharts/3.0/serial.js');
}

use Bitrix\Intranet\UStat\UStat;

?>

<? if($arResult['INTERVAL'] == 'day'): ?>
<!-- jumping to custom day -->
<style type="text/css">
	#chartdiv image, svg g g path {cursor: pointer}
</style>
<? endif ?>

<script type="text/javascript">

var chart;
var chartData = <?=CUtil::PhpToJSObject($arResult['DATA'])?>;
var chartCursorData = {};

for (i in chartData)
{
	chartCursorData[chartData[i].date] = chartData[i].cursor_date;
}

<? if($arResult['INTERVAL'] == 'day'): ?>
// jumping to custom day
function reloadIntranetUstatCustomDay(event)
{
	reloadIntranetUstat({PERIOD:'custom_day', CUSTOM_DAY: event.item.dataContext.custom_day});
}
<? endif ?>

<? if (!isset($_REQUEST['AJAX'])): ?>
AmCharts.ready(function () {
<? endif ?>

	// SERIAL CHART
	var chart = new AmCharts.AmSerialChart();
	chart.pathToImages = "/bitrix/js/main/amcharts/3.0/images/";
	chart.dataProvider = chartData;
	chart.categoryField = "date";
	chart.fontFamily = 'Arial';

	chart.balloon.borderThickness = 1;
	chart.balloon.shadowAlpha = 0.05;
	chart.balloon.color = '#444';

	// AXES
	// category
	var categoryAxis = chart.categoryAxis;
	categoryAxis.gridColor = '#000000';
	categoryAxis.gridAlpha = 0.1;
	categoryAxis.axisAlpha = 0.1;
	categoryAxis.gridThickness = 1;
	categoryAxis.color = '#666';

	<? if ($arResult['INTERVAL'] == 'hour'): // 00:00-24:00, 25 hours in a row ?>
	categoryAxis.autoGridCount = false;
	categoryAxis.gridCount = 25;
	<? endif ?>

	var valueAxis = new AmCharts.ValueAxis();
	valueAxis.stackType = "regular";
	valueAxis.gridAlpha = 0;
	valueAxis.axisAlpha = 0;

	<? if ($arResult['INTERVAL'] == 'hour'): ?>
	valueAxis.maximum = <?=$arResult['MAX_ACTIVITY']?>;
	<? else: ?>
	valueAxis.maximum = <?=$arResult['MAX_ACTIVITY']?>;
	<? endif ?>

	valueAxis.labelsEnabled = false;
	valueAxis.autoGridCount = false;
	valueAxis.integersOnly = true;
	chart.addValueAxis(valueAxis);

	var valueAxis2 = new AmCharts.ValueAxis();
	valueAxis2.stackType = "regular";
	valueAxis2.gridAlpha = 0;
	valueAxis2.axisAlpha = 0;
	valueAxis2.position = "right";
	valueAxis2.maximum = 100;
	valueAxis2.labelsEnabled = false;
	chart.addValueAxis(valueAxis2);




	// GRAPHS
	// column graph
	<? if ($arParams['PERIOD'] !== 'today' && $arParams['PERIOD'] !== 'custom_day'): ?>

	var graph1 = new AmCharts.AmGraph();
	graph1.type = "column";
	graph1.valueField = "involvement";
	graph1.lineAlpha = 0;
	graph1.fillAlphas = 0.7;
	graph1.valueAxis = valueAxis2;
	graph1.balloonColor = '#9cc1e7';
	graph1.lineColor = '#c2f0fb';
	graph1.balloonText = '<?=GetMessageJS('INTRANET_USTAT_COMPANY_GRAPH_INVOLVEMENT')?>: [[value]]%';
	chart.addGraph(graph1);

	<? endif ?>

	// line
	var graph2 = new AmCharts.AmGraph();
	graph2.type = "line";
	graph2.valueField = "activity";
	graph2.lineThickness = 1.7;
	graph2.bullet = "round";
	graph2.bulletSize = 21;
	graph2.bulletColor = 'white';
	graph2.bulletBorderColor = '#90b506';
	graph2.bulletBorderThickness = 2;
	graph2.bulletBorderAlpha = 1;
	graph2.customBullet = BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/bullet-green.png?1407');
	graph2.lineColor = '#90b506';
	graph2.balloonText = '<?=GetMessageJS('INTRANET_USTAT_COMPANY_GRAPH_ACTIVITY')?>: [[value]]';
	graph2.dashLengthField = 'dash_length_line';
	chart.addGraph(graph2);

	// CURSOR
	chartCursor = new AmCharts.ChartCursor();
	chartCursor.cursorPosition = "mouse";
	chartCursor.graphBulletSize = 1.2;
	chartCursor.categoryBalloonFunction = function (title) { return chartCursorData[title]; };
	chartCursor.categoryBalloonColor = '#73949d';
	chartCursor.cursorColor = '#69a1db';
	chartCursor.cursorAlpha = 0;
	chartCursor.categoryBalloonAlpha = 0.9;
	chartCursor.bulletsEnabled = false;
	chart.addChartCursor(chartCursor);

	<? if($arResult['INTERVAL'] == 'day'): ?>
	// JUMP TO CUSTOM DAY
	chart.addListener("clickGraphItem", reloadIntranetUstatCustomDay);
	<? endif ?>


	// WRITE
	chart.write("chartdiv");

<? if (!isset($_REQUEST['AJAX'])): ?>
});
<? endif ?>


<? if (!isset($_REQUEST['AJAX'])): ?>
BX.ready(function() {
		var body = BX('header').parentNode;
		var bodyWrapper = BX.nextSibling(BX('header'));
		body.insertBefore(BX('intranet-activity-container'), bodyWrapper);

		pulse_loading.show();
		pulse_loading.load_done();
});
<? endif ?>

function getIntranetUStatPeriodButtons()
{
	var periodBlock = BX.findChild(BX('pulse-main-wrap'), {className:'pulse-header-tabs-block'}, true);
	return BX.findChildren(periodBlock);
}

function getIntranetUStatSections()
{
	return BX.findChildren(BX('intranet-activity-sections-container'), {className:'pulse-nav-item-wrap'});
}

function toggleIntranetUStatBy(event, obj)
{
	var ev = event || window.event;
	var target = event.srcElement || event.target;

	var nowOpened = BX.hasClass(obj, 'pulse-info-toggle-company') ? 'company' : 'people';
	var toOpen = nowOpened;

	if (target == BX('pulse-info-toggle-people'))
	{
		toOpen = 'people';
	}
	else if(target == BX('pulse-info-toggle-company'))
	{
		toOpen = 'company';
	}
	else if(target == BX('pulse-info-toggle-btn') || target == BX('pulse-info-toggle-block'))
	{
		toOpen = BX.hasClass(obj, 'pulse-info-toggle-company') ? 'people' : 'company';
	}

	if (nowOpened != toOpen)
	{
		BX.toggleClass(obj, 'pulse-info-toggle-company');
		BX.toggleClass(obj, 'pulse-info-toggle-people');

		if (toOpen == 'company')
		{
			var call = function () {
				reloadIntranetUstat({BY: 'department', BY_ID: 0});
			};
		}
		else
		{
			var call = function () {
				reloadIntranetUstat({BY: 'user', BY_ID: <?=$USER->getId()?>});
			};
		}

		// let the switcher animate
		setTimeout(call, 90);
	}

	BX.PreventDefault(event);
}

BX.ready(function(){

	// changing period
	var periodControls = getIntranetUStatPeriodButtons();

	for (i in periodControls)
	{
		BX.bind(periodControls[i], 'click', function(){

			// call ajax
			reloadIntranetUstat({PERIOD:this.getAttribute('data-period-id')});

			// change button style
			var buttons = getIntranetUStatPeriodButtons();

			for (i in buttons)
			{
				BX.removeClass(buttons[i], 'pulse-header-tab-active');
			}

			BX.addClass(this, 'pulse-header-tab-active');
		});
	}

	// changing section
	var sections = getIntranetUStatSections();

	for (i in sections)
	{
		BX.bind(BX.findChild(sections[i], {className:'pulse-nav-item'}), 'click', function(){

			var section = BX.hasClass(this.parentNode, 'pulse-nav-item-wrap-active') ? '' : this.parentNode.getAttribute('data-section-id');

			var sections = getIntranetUStatSections();

			for (i in sections)
			{
				BX.removeClass(sections[i], 'pulse-nav-item-wrap-active');
			}

			reloadIntranetUstat({SECTION: section});
		});

		// tell about instrument
		BX.bind(BX.findChild(sections[i], {className:'pulse-nav-item-footer'}), 'click', function(){

			pulse_loading.load_start();

			var section = this.parentNode.getAttribute('data-section-id');

			BX.ajax({
				url: '/ustat.php',
				method: 'POST',
				data: {
					BY: 'tellabout',
					WHAT: section,
					AJAX: 1
				},
				dataType: 'html',
				processData: false,
				start: true,
				onsuccess: function (html) {

					var ok = html.search('AJAX_EXECUTED_SUCCESSFULLY');

					if (ok < 0)
					{
						// to show error

						return;
					}


					var ob = BX.processHTML(html);

					// submit form
					BX('pulse-main-wrap').innerHTML += ob.HTML;
					BX.submit(BX('intranet-ustat-tell-about-form'), 'dummy');

					return true;
				},
				onfailure: function ()
				{
					// to show error
					return;
				}
			});
		});
	}

	BX.bind(BX('pulse-close-top-btn'), 'click', function(event){
		<? if($arResult['SECTION']==='TOTAL'): ?>
		pulse_loading.close();
		<? else: ?>
		reloadIntranetUstat({SECTION: ''});
		<? endif ?>

		BX.PreventDefault(event);
	});

	// help icons
	BX.bind(BX('pulse-company-general-help-icon'), 'click', function()
	{
		var content = BX.create('DIV', {
			html: '<?=(isset($arResult['SECTION_DATA'][$arResult['SECTION']])) ? GetMessageJS('INTRANET_USTAT_SECTION_'.$arResult['SECTION'].'_HELP_GENERAL') : GetMessageJS('INTRANET_USTAT_COMPANY_HELP_GENERAL')?>',
			props: {
				className: 'pulse-info-index-help'
			}
		});

		var popup = BX.PopupWindowManager.create(this.id + '-popup', this, {
			autoHide: true,
			lightShadow: true,
			zIndex: 2,
			content: content,
			closeByEsc: true,
			closeIcon: false,
			offsetLeft: 13,
			offsetTop: 0,
			bindOptions: {position: "bottom"},
			angle: {
				position: 'bottom',
				offset: 0
			}
		});

		popup.setBindElement(this);
		popup.setContent(content);

		this.onPopupShow = function ()
		{
			BX.addClass(this, 'pulse-header-title-info-active');
		}

		this.onPopupClose = function ()
		{
			BX.removeClass(this, 'pulse-header-title-info-active');
		}

		BX.addCustomEvent(popup, "onPopupShow", BX.delegate(this.onPopupShow, this));
		BX.addCustomEvent(popup, "onPopupClose", BX.delegate(this.onPopupClose, this));

		popup.show();
	});


	BX.bind(BX('pulse-company-rating-help-icon'), 'click', function(e)
	{
		var popup = BX.PopupWindowManager.create(this.id + '-popup', this, {
			autoHide: true,
			lightShadow: true,
			zIndex: 2,
			content: BX.create('DIV', {
				html: '<?=GetMessageJS('INTRANET_USTAT_COMPANY_HELP_RATING')?>',
				props: {
					className: 'pulse-info-index-help'
				}
			}),
			closeByEsc: true,
			closeIcon: false,
			offsetLeft: 28,
			offsetTop: -29,
			bindOptions: {position: "bottom"},
			angle: {
				position: 'left',
				offset: 0
			}
		});

		popup.setBindElement(this);

		this.onPopupShow = function ()
		{
			BX.addClass(this, 'pulse-info-index-info-active');
		}

		this.onPopupClose = function ()
		{
			BX.removeClass(this, 'pulse-info-index-info-active');
		}

		BX.addCustomEvent(popup, "onPopupShow", BX.delegate(this.onPopupShow, this));
		BX.addCustomEvent(popup, "onPopupClose", BX.delegate(this.onPopupClose, this));

		popup.show();

		BX.PreventDefault(e);
	});

	BX.bind(BX('pulse-company-activity-help-icon'), 'click', function()
	{
		var popup = BX.PopupWindowManager.create(this.id + '-popup', this, {
			autoHide: true,
			lightShadow: true,
			zIndex: 2,
			content: BX.create('DIV', {
				html: '<?=GetMessageJS('INTRANET_USTAT_COMPANY_HELP_ACTIVITY')?>',
				props: {
					className: 'pulse-info-index-help'
				}
			}),
			closeByEsc: true,
			closeIcon: false,
			offsetLeft: 28,
			offsetTop: -29,
			bindOptions: {position: "bottom"},
			angle: {
				position: 'left',
				offset: 0
			}
		});

		popup.setBindElement(this);

		this.onPopupShow = function ()
		{
			BX.addClass(this, 'pulse-info-index-info-active');
		}

		this.onPopupClose = function ()
		{
			BX.removeClass(this, 'pulse-info-index-info-active');
		}

		BX.addCustomEvent(popup, "onPopupShow", BX.delegate(this.onPopupShow, this));
		BX.addCustomEvent(popup, "onPopupClose", BX.delegate(this.onPopupClose, this));

		popup.show();
	});

	BX.bind(BX('pulse-company-involvement-help-icon'), 'click', function()
	{
		var content = BX.create('DIV', {
			html: '<?=(isset($arResult['SECTION_DATA'][$arResult['SECTION']])) ? GetMessageJS('INTRANET_USTAT_SECTION_'.$arResult['SECTION'].'_HELP_INVOLVEMENT') : GetMessageJS('INTRANET_USTAT_COMPANY_HELP_INVOLVEMENT')?>',
			props: {
				className: 'pulse-info-index-help'
			}
		});

		var popup = BX.PopupWindowManager.create(this.id + '-popup', this, {
			autoHide: true,
			lightShadow: true,
			zIndex: 2,
			content: content,
			closeByEsc: true,
			closeIcon: false,
			offsetLeft: -337,
			offsetTop: -29,
			bindOptions: {position: "bottom"},
			angle: {
				position: 'right',
				offset: 0
			}
		});

		popup.setBindElement(this);
		popup.setContent(content);

		this.onPopupShow = function ()
		{
			BX.addClass(this, 'pulse-info-index-info-active');
		}

		this.onPopupClose = function ()
		{
			BX.removeClass(this, 'pulse-info-index-info-active');
		}

		BX.addCustomEvent(popup, "onPopupShow", BX.delegate(this.onPopupShow, this));
		BX.addCustomEvent(popup, "onPopupClose", BX.delegate(this.onPopupClose, this));

		popup.show();
	});
});

</script>

<? if (!isset($_REQUEST['AJAX'])): ?>
<div class="pulse-top-wrap" id="intranet-activity-container">
<div class="pulse-main-wrap" id='pulse-main-wrap'>
<? endif ?>


	<div class="pulse-wrap">
	<div class="pulse-cont-wrap">
		<div class="pulse-cont-shadow">
			<div class="pulse-header">
				<div class="pulse-header-title">
					<span class="pulse-header-title-text">
						<? if (isset($arResult['SECTION_DATA'][$arResult['SECTION']])): ?>
							<?=htmlspecialcharsbx($arResult['SECTION_DATA'][$arResult['SECTION']]['title'])?>
						<? else: ?>
							<?=GetMessage('INTRANET_USTAT_COMPANY_GRAPH_TITLE')?>
						<? endif ?>
					</span>
					<span class="pulse-header-title-info" id="pulse-company-general-help-icon"></span>
				</div>
				<div class="pulse-header-tabs-wrap">
					<span class="pulse-header-tabs-label"><?=GetMessage('INTRANET_USTAT_PERIOD_TITLE')?></span>
						<span class="pulse-header-tabs-block">
							<? if($arParams['PERIOD'] == 'custom_day'): ?>
								<span class="pulse-header-tab pulse-header-tab-active"
									data-period-id="custom_day"><?=FormatDate('d M Y', strtotime($arParams['CUSTOM_DAY']))?></span>
							<? endif ?>

							<? foreach (array('today', 'week', 'month', 'year') as $period): ?>
								<span class="pulse-header-tab <?=$arParams['PERIOD']===$period?'pulse-header-tab-active':''?>"
									data-period-id="<?=$period?>"><?=GetMessage('INTRANET_USTAT_PERIOD_BUTTON_'.strtoupper($period))?></span>
							<? endforeach ?>
						</span>
				</div>
			</div>
			<div class="pulse-info-block pulse-info-company"><!--pulse-info-people pulse-info-company-->
				<table class="pulse-info-table">
					<tr>
						<td class="pulse-info-cell pulse-info-cell-top">
							<div class="pulse-info-toggle-block pulse-info-toggle-company" onmousedown="toggleIntranetUStatBy(event,this)">
								<span class="pulse-info-toggle-text pulse-info-toggle-text-people" id="pulse-info-toggle-people"><?=GetMessage('INTRANET_USTAT_TOGGLE_PEOPLE')?></span>
										<span class="pulse-info-toggle" id="pulse-info-toggle-block">
											<span class="pulse-info-toggle-btn" id="pulse-info-toggle-btn"></span>
										</span>
								<span class="pulse-info-toggle-text pulse-info-toggle-text-company" id="pulse-info-toggle-company"><?=GetMessage('INTRANET_USTAT_TOGGLE_COMPANY')?></span>
							</div>
							<div class="pulse-info-title"><?if (IsModuleInstalled("bitrix24")) echo htmlspecialcharsbx(COption::GetOptionString("bitrix24", "site_title", "")); else echo htmlspecialcharsbx(COption::GetOptionString("main", "site_name", ""));?></div>
						</td>
						<td class="pulse-info-cell pulse-info-cell-top pulse-info-rating-title-td" onclick="openIntranetUstatRating();">
							<div class="pulse-info-rating-title">
								<?=GetMessage('INTRANET_USTAT_COMPANY_RATING_TITLE')?>
								<span class="pulse-info-index-info" id="pulse-company-rating-help-icon"></span>
							</div>
							<div class="pulse-info-rating-avatars"><!--

								<? $i=0 ?>

								<? if ($arResult['USERS_RATING']['position'] > 5): // 3 users + ... + current user ?>

									--><? foreach($arResult['USERS_RATING']['top'] as $userRating): ?><!--
										<? ++$i ?>

										<? if ($i == 4): ?>
											--><span class="pulse-info-rat-points"></span><!--
										<? endif ?>

										<? if ($i == 4 || $i == 5) continue ?>

										--><span title="<?=htmlspecialcharsbx($arResult['USERS_INFO'][$userRating['USER_ID']]['FULL_NAME'])."\n".GetMessage('INTRANET_USTAT_COMPANY_GRAPH_ACTIVITY').": ".$userRating['ACTIVITY']?>"
											class="pulse-info-rat-avatar <?=$i==1?'pulse-info-rat-green':''?>
												<?=($i>1 && $userRating['USER_ID'] == $USER->getId())?'pulse-info-rat-blue':''?> pulse-info-rat-avatar-<?=$i?>"
											<? if(!empty($arResult['USERS_INFO'][$userRating['USER_ID']]['AVATAR_SRC'])): ?>
												style="background: url('<?=$arResult['USERS_INFO'][$userRating['USER_ID']]['AVATAR_SRC']?>') no-repeat center center;"
											<? endif ?>
											><!--<? if($i==1): ?>
												--><span class="pulse-info-rat-counter">1</span><!--
											<? elseif($userRating['USER_ID'] == $USER->getId()): ?>
												--><span class="pulse-info-rat-counter"><?=$arResult['USERS_RATING']['position']?></span><!--
											<? endif ?>
										--></span><!--

									--><? endforeach ?><!--

								<? else: // 5 users in a row ?>

									--><? foreach($arResult['USERS_RATING']['top'] as $userRating): ?><!--
										--><span title="<?=htmlspecialcharsbx($arResult['USERS_INFO'][$userRating['USER_ID']]['FULL_NAME'])."\n".GetMessage('INTRANET_USTAT_COMPANY_GRAPH_ACTIVITY').": ".$userRating['ACTIVITY']?>"
											class="pulse-info-rat-avatar <?=++$i==1?'pulse-info-rat-green':''?>
												<?=($i>1 && $userRating['USER_ID'] == $USER->getId())?'pulse-info-rat-blue':''?> pulse-info-rat-avatar-<?=$i?>"
											<? if(!empty($arResult['USERS_INFO'][$userRating['USER_ID']]['AVATAR_SRC'])): ?>
												style="background: url('<?=$arResult['USERS_INFO'][$userRating['USER_ID']]['AVATAR_SRC']?>') no-repeat center center;"
											<? endif ?>
										><!--<? if($i==1): ?>
												--><span class="pulse-info-rat-counter">1</span><!--
											<? elseif($userRating['USER_ID'] == $USER->getId()): ?>
												--><span class="pulse-info-rat-counter"><?=$arResult['USERS_RATING']['position']?></span><!--
											<? endif ?>
										--></span><!--
									--><? endforeach ?><!--

								--><? endif ?><!--

							--></div><!--
						--></td>
						<td class="pulse-info-cell pulse-info-cell-center">
									<span class="pulse-info-index-text">
										<?=GetMessage('INTRANET_USTAT_COMPANY_ACTIVITY_TITLE')?>
										<span class="pulse-info-index-info" id="pulse-company-activity-help-icon"></span>
									</span>
									<span class="pulse-info-index">
										<? foreach (UStat::getFormattedNumber($arResult['SUM_ACTIVITY']) as $number): ?><!--
											--><span class="pulse-num pulse-num-<?=$number['code']?>"><?=$number['char']?></span><!--
										--><? endforeach; ?>
									</span>
						</td>
						<td class="pulse-info-cell pulse-info-cell-center">
									<span class="pulse-info-involve-text">
										<? if (HasMessage('INTRANET_USTAT_COMPANY_INV_BAR_'.$arResult['SECTION'])): ?>
											<?=GetMessage('INTRANET_USTAT_COMPANY_INV_BAR_'.$arResult['SECTION'])?>
										<? else: ?>
											<?=GetMessage('INTRANET_USTAT_COMPANY_INV_BAR_TOTAL')?>
										<? endif ?>
										<span class="pulse-info-index-info" id="pulse-company-involvement-help-icon"></span>
									</span>
									<span class="pulse-info-involve-block">
										<? foreach (str_split($arResult['SUM_INVOLVEMENT']) as $digit): ?><!--
											--><span class="pulse-num pulse-num-<?=$digit?>"><?=$digit?></span><!--
										--><? endforeach; ?><!--

										--><span class="pulse-num pulse-num-percent">%</span>
										<span class="pulse-info-inv-bar-wrap">
											<span class="pulse-info-inv-bar-shadow"></span>
											<?
											if ($arResult['SUM_INVOLVEMENT'] > 65)
												$progressColor = 'green';
											elseif ($arResult['SUM_INVOLVEMENT'] > 15)
												$progressColor = 'yellow';
											else
												$progressColor = 'red';
											?>
											<span class="pulse-info-inv-bar-strip"><span class="pulse-inv-bar pulse-inv-bar-<?=$progressColor?>" style="width: <?=$arResult['SUM_INVOLVEMENT']?>%;"></span></span>
										</span>
									</span>
						</td>
					</tr>
				</table>
			</div>

			<div class="pulse-graphics-wrap">
				<div id="chartdiv" style="width:940px; height:200px; background-color: white"></div>
			</div>
		</div>
		<div class="pulse-cont-footer">
			<div class="pulse-cont-footer-1"></div>
			<div class="pulse-cont-footer-2"></div>
		</div>
	</div>
	<div class="pulse-navigation-block-wrap">
		<div class="pulse-nav-block-wrap"><!--
		--><div class="pulse-nav-block" id="intranet-activity-sections-container"><!--

			<? $i = 0 ?>
			--><? foreach($arResult['SECTION_DATA'] as $section => $sectionData): ?><!--
				<? ++$i ?>
				<?
				if ($sectionData['involvement'] > 65)
					$progressColor = 'green';
				elseif ($sectionData['involvement'] > 15)
					$progressColor = 'yellow';
				else
					$progressColor = 'red';
				?>

				--><div class="pulse-nav-item-wrap <?=($arResult['SECTION'] == $section)?'pulse-nav-item-wrap-active':''?>"
					data-section-id="<?=$section?>"><!--
					--><div class="pulse-nav-item"><!--
						--><div class="pulse-nav-item-title">
							<span class="pulse-nav-item-title-num"><?=$i?>.</span>
							<div class="pulse-nav-item-title-text"><?=htmlspecialcharsbx($sectionData['title'])?></div>
						</div>
						<div class="pulse-nav-item-bar">
							<div class="pulse-nav-item-bar-line pulse-bar-<?=$progressColor?>" style="width: <?=$sectionData['involvement']?>%;"></div>
						</div>
						<div class="pulse-nav-item-text">
							<?=GetMessage('INTRANET_USTAT_COMPANY_SECTION_INVOLVEMENT')?> <strong><?=$sectionData['involvement']?>%</strong> <br>
							<strong><?=GetMessage('INTRANET_USTAT_COMPANY_SECTION_ACTIVITY')?></strong>
						</div>
						<div class="pulse-nav-item-index">
							<? foreach (str_split($sectionData['activity']) as $digit): ?><!--
								--><span class="pulse-num pulse-num-<?=$digit?>"><?=$digit?></span><!--
							--><? endforeach; ?>
						</div><!--
					--></div><!--
					--><div class="pulse-nav-item-corner"></div><!--
					--><div class="pulse-nav-item-footer" <?=$arResult['ALLOW_TELL_ABOUT']?'':'style="display:none"'?>><?=GetMessage('INTRANET_USTAT_COMPANY_SECTION_TELL_ABOUT')?><span class="pulse-nav-item-footer-icon"></span></div><!--
				--></div><!--
			--><? endforeach; ?><!--

		--></div><!--
		--><!--<div class="pulse-nav-left-arrow"></div><div class="pulse-nav-right-arrow"></div>-->
	</div>
	</div>
		<a class="<?=$arResult['SECTION']=='TOTAL'?'pulse-close-icon':'pulse-back-icon'?>" id="pulse-close-top-btn" href=""></a>
	</div>



<? if (!isset($_REQUEST['AJAX'])): ?>
</div>
<div class="pulse-loading-block" id="pulse-loading-curtain">
	<div class="pulse-loading-frame"></div>
</div>
</div>
<? endif ?>

<div class="pulse-cont-block" id="pulse-cont-rating" data-list="rating" style="display: none">

</div>
<div class="pulse-cont-block pulse-cont-block-rating" id="pulse-cont-involve" data-list="involve" style="display: none">

</div>

<? if (!isset($_REQUEST['AJAX'])): ?>
<script type="text/javascript">

BX.bind(BX('pulse_open_btn'), 'click', function(){
	pulse_loading.show()
});

var pulse_loading = {

	pulse_block: BX('intranet-activity-container'),
	img_list:[],
	loading_curtain: BX('pulse-loading-curtain'),
	pulse_rate: BX('pulse-rate'),
	open: false,
	img_create: function()
	{
		var img,
			img_list_src = [
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-close-btn.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-num-sidebar-grey.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-num-sidebar-blue.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-sidebar-percent-bg.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-num-light-grey.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-num-light-blue.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-num-blue-big.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-num-blue-normal.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-percent-bar-bg.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-percent-bar-shadow.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-involve-strip.gif',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-num-dark-grey.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-percent-bg.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-sprite.png',
				'/bitrix/components/bitrix/intranet.ustat/images/pulse-main-bg.png'
			];

		var div = BX.create('div',{
			style:{
				height:0,
				width:0,
				overflow:'hidden'
			}
		});

		for(var i=img_list_src.length-1; i>=0; i--){

			img = BX.create('img',
				{
					props:{src:img_list_src[i]},

					style:{
						height:1 +"px",
						width:1 +"px"
					},
					attrs:{
						'data-load':'0'
					},
					events: {
						load: function(){
							this.setAttribute('data-load', '1');
						},
						error:function(){
							this.setAttribute('data-load', '2');
						}
					}
				});

			div.appendChild(img);
			this.img_list.push(img)
		}

		return div;
	},

	show: function()
	{
		var _this = this;

		this.pulse_block.appendChild(this.img_create());

		this.easing(this.pulse_block, 'height', 55, 647, 'px', 1, null, 'cubic');

		this.loading_curtain.style.display = 'block';
		this.loading_curtain.style.opacity = 1;

		this.open = true;
	},

	load_done : function()
	{
		var _this = this;

		var interval = setInterval(
			function()
			{
				for(var i = _this.img_list.length-1; i>=0; i--)
				{
					if(_this.img_list[i].getAttribute('data-load') == 0){
						break
					}
					else {
						clearInterval(interval);
						_this.easing(
							_this.loading_curtain, 'opacity', 10, 0, '', 10,
							function(){
								_this.loading_curtain.style.display = 'none';
							},
							'linear'
						);
					}
				}
			},
			100);

	},

	load_start : function(){
		this.loading_curtain.style.display = 'block';
		this.easing(
			this.loading_curtain, 'opacity', 1, 10, '', 10, null, 'linear'
		);
	},
	close : function()
	{
		this.easing(this.pulse_block, 'height', 647, 55, 'px', 1, null, 'cubic');
		this.open = false;
	},

	toggle_open_close : function()
	{
		if(this.open) {
			this.close();
		}
		else{
			this.show();
		}
	},

	rate_anim:function(){

		var _this = this,
			count = 102;

		setInterval(function(){
			_this.pulse_rate.style.backgroundPosition = '0 '+ (count * -1) +'px';
			count = count + 80;

			if(count == 902) count = 102;

		},150)
	},

	easing : function(obj, prop, start, finish, px, fraction, complete_func, time_func){

		var easing = new BX.easing({
			duration:250,
			delay : 25,
			start : {prop : start},
			finish : {prop : finish},
			transition: BX.easing.makeEaseOut(BX.easing.transitions[time_func]),

			step:function(state){
				obj.style[prop] = state.prop/fraction + px;
			},

			complete:complete_func
		});
		easing.animate()
	}
};

</script>
<? endif ?>

<!-- AJAX_EXECUTED_SUCCESSFULLY -->

<?
if (isset($_REQUEST['AJAX']))
	die();
?>