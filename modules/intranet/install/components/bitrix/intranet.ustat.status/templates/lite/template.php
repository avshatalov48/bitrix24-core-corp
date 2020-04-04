<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

?>

<script type="text/javascript">

BX.INTRANET_USTAT_LAST_PARAMETERS = null;

BX.ready(function() {
	BX.bind(BX('user-indicator-pulse'), 'click', openIntranetUStat);

	BX.bind(BX('pulse-close-btn'), 'click', function(){
		pulse_loading.close();
	});

	// escape
	BX.bind(BX('pulse-main-wrap'), 'keydown', function(e){
		if (e.keyCode == 27)
		{
			if (pulse_popup.isOpen())
			{
				pulse_popup.close();
			}
			else if (BX.INTRANET_USTAT_LAST_PARAMETERS != null && BX.INTRANET_USTAT_LAST_PARAMETERS.SECTION != '')
			{
				reloadIntranetUstat({SECTION:''});
			}
			else
			{
				pulse_loading.close();
			}
		}
	});
});

// init ustat containers
div = document.createElement('div');
div.id = 'intranet-activity-container';
div.className = 'pulse-top-wrap pulse-top-wrap-light';

// ajax response body
var div2 = document.createElement('div');
div2.id = 'pulse-main-wrap';
div2.className = 'pulse-main-wrap';
div2.tabIndex = 1;
div.appendChild(div2);

// loader stuff
div.innerHTML = div.innerHTML + '<div class="pulse-close-btn" id="pulse-close-btn"></div>' +
	'<div class="pulse-loading-block" id="pulse-loading-curtain">' +
	' <img id="pulse-loading-block-waiter" class="pulse-loading-block-waiter" />' +
	'</div>' +
	'<div class="pulse-loading-first-anim" id="pulse-loading-first-anim">'+
	'<div class="pulse-loading-first-anim-title"><?=GetMessageJS('INTRANET_USTAT_WIDGET_TITLE')?></div>'+
	'<div class="pulse-loading-first-anim-rate">'+
	'<div class="pulse-loading-first-anim-black" id="pulse-load-shadow-black"></div>'+
	'<div class="pulse-loading-first-anim-shadow" id="pulse-load-shadow"></div>'+
	'</div>'+
	'<div class="pulse-loading-first-anim-text"><?=GetMessageJS('INTRANET_USTAT_WIDGET_LOADING')?></div>'+
	'</div>';

document.getElementById('page-wrapper').insertBefore(div, document.getElementById('page-inner'));

function openIntranetUStat()
{
	pulse_loading.toggle_open_close();

	BX.loadScript('/bitrix/js/main/amcharts/3.0/amcharts.js', function(){
		BX.loadScript('/bitrix/js/main/amcharts/3.0/serial.js', function(){
			reloadIntranetUstat({});
		});
	});
}

function reloadIntranetUstat(parameters)
{
	if (pulse_loading.first_show)
	{
		pulse_loading.load_start();
	}

	if (BX.INTRANET_USTAT_LAST_PARAMETERS == null)
	{
		BX.INTRANET_USTAT_LAST_PARAMETERS = {
			BY: 'department',
			BY_ID: 0,
			PERIOD: 'today',
			SECTION: '',
			AJAX: 1
		};
	}

	var i;
	var data = {};

	for (i in BX.INTRANET_USTAT_LAST_PARAMETERS)
	{
		if (parameters[i] != undefined)
		{
			//data[i] = parameters[i];
		}
		else
		{
			data[i] = BX.INTRANET_USTAT_LAST_PARAMETERS[i];
		}
	}

	for (i in parameters)
	{
		data[i] = parameters[i];
	}



	BX.INTRANET_USTAT_LAST_PARAMETERS = data;

	BX.ajax({
		url: '/ustat.php',
		method: 'POST',
		data: data,
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

			// refresh rating popup
			pulse_popup.destroy();

			if (BX.INTRANET_USTAT_RATING_LAST_PARAMETERS != null)
			{
				BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.CURRENT_OFFSET = 0;
				BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.LIST = 'rating';
			}



			var ob = BX.processHTML(html);

			// insert html
			BX('pulse-main-wrap').innerHTML = ob.HTML;

			// process js
			BX.ajax.processScripts(ob.SCRIPT);

			pulse_loading.load_done();

			return true;
		},
		onfailure: function ()
		{
			// to show error
			return;
		}
	});
}

function openIntranetUstatRating()
{
	pulse_popup.show([BX('pulse-cont-rating'),BX('pulse-cont-involve')]);
	loadIntranetUstatRating({});
}

function loadIntranetUstatRating(parameters)
{
	if (BX.INTRANET_USTAT_RATING_LAST_PARAMETERS == null)
	{
		BX.INTRANET_USTAT_RATING_LAST_PARAMETERS = {
			BY: 'rating',
			BY_ID: 0,
			//OFFSET: 0,
			AJAX: 1,
			LIST: 'rating',
			//local usage
			CURRENT_OFFSET: 0,
			LOADING: false
		};
	}

	if (BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.LOADING)
	{
		return;
	}

	BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.LOADING = true;

	BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.OFFSET = BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.CURRENT_OFFSET;
	BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.CURRENT_OFFSET += 20;

	//BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.LIST = 'rating';

	BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.PERIOD = BX.INTRANET_USTAT_LAST_PARAMETERS.PERIOD;
	BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.SECTION = BX.INTRANET_USTAT_LAST_PARAMETERS.SECTION;

	var i;
	var data = {};

	for (i in BX.INTRANET_USTAT_RATING_LAST_PARAMETERS)
	{
		if (parameters[i] != undefined)
		{
			//data[i] = parameters[i];
		}
		else
		{
			data[i] = BX.INTRANET_USTAT_RATING_LAST_PARAMETERS[i];
		}
	}

	for (i in parameters)
	{
		data[i] = parameters[i];
	}

	BX.INTRANET_USTAT_RATING_LAST_PARAMETERS = data;

	if (data.OFFSET == 0)
	{
		BX('pulse-cont-'+data.LIST).innerHTML = '<div id="ustat-rating-loading"><?=GetMessage('INTRANET_USTAT_RATING_LOADING')?></div>';
	}

	BX.ajax({
		url: '/ustat.php',
		method: 'POST',
		data: data,
		dataType: 'html',
		processData: false,
		start: true,
		onsuccess: function (html) {

/*			var ok = html.search('AJAX_EXECUTED_SUCCESSFULLY');

			if (ok < 0)
			{
				// to show error
				return;
			}*/

			var ob = BX.processHTML(html);

			// insert html
			if (BX('ustat-rating-loading'))
			{
				BX('pulse-cont-'+data.LIST).removeChild(BX('ustat-rating-loading'));
			}

			BX('pulse-cont-'+data.LIST).innerHTML += ob.HTML;

			// process js
			BX.ajax.processScripts(ob.SCRIPT);

			// done
			BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.LOADING = false;

			BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.TOP_ACTIVITY = BX('pulse-popup-max-score').innerHTML;

			return true;
		},
		onfailure: function ()
		{
			// to show error
			return;
		}
	});
}


</script>


<script type="text/javascript">

var pulse_loading = {

	pulse_block: BX('intranet-activity-container'),
	img_list:[],
	anim_status : false,
	first_anim_interval:null,
	first_anim_start:0,
	loading_curtain: BX('pulse-loading-curtain'),
	first_show_main_block: BX('pulse-loading-first-anim'),
	first_show_black_frame: BX('pulse-load-shadow-black'),
	first_show_shadow: BX('pulse-load-shadow'),
	pulse_rate: BX('pulse-rate'),
	waiter_gif : BX('pulse-loading-block-waiter'),
	waiter_gif_timeout : null,
	open: false,
	first_show: false,

	img_create: function()
	{
		var img,
			img_list_src = [
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-close-btn.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-num-sidebar-grey.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-num-sidebar-blue.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-sidebar-percent-bg.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-num-light-grey.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-num-light-blue.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-num-blue-big.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-num-blue-normal.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-percent-bar-bg.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-percent-bar-shadow.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-involve-strip.gif?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-num-dark-grey.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-percent-bg.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-sprite.png?1407'),
				BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-main-bg.png?1407')
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

	first_show_anim : function(){

		var counter_black = 682,
			counter = 50,
			_this = this;

		this.first_anim_interval = setInterval(function()
		{
			if(counter_black <= 0)
				_this.first_show_black_frame.style.width = 0;
			else
				_this.first_show_black_frame.style.width = counter_black +'px';

			counter_black = counter_black -12;

			_this.first_show_shadow.style.right = (counter * -1) + 'px';
			counter = counter + 12;

			if(counter >= 732)
				counter = 0;

		}, 50);

		var date = new Date;

		this.first_anim_start = date.getTime();

	},

	show: function()
	{
		var _this = this;

		if(!this.first_show){
			this.pulse_block.appendChild(this.img_create());
			this.first_show_anim();
		}else {
			this.loading_curtain.style.display = 'block';
			this.loading_curtain.style.opacity = 1;

			this.waiter_gif_timeout = setTimeout(function(){
				_this.waiter_gif.setAttribute('src', BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-waiter.gif?1407'))
			}, 1000);
		}

		this.easing(this.pulse_block, 'height', 0, 592, 250, 'px', 1, null, 'cubic');

		this.anim_status = true;
		this.open = true;

		var date = new Date;
		this.first_anim_start = date.getTime();
	},

	load_done : function()
	{
		var _this = this,
			date = new Date(),
			postpone = 0,
			interval_time = 100;

		if(this.first_show){
			interval_time = 0;
		}

		var interval = setInterval(
			function()
			{
				for(var i = _this.img_list.length-1; i>=0; i--)
				{
					if(_this.img_list[i].getAttribute('data-load') == 0){
						break
					}
					else if(i == 0)
					{
						clearInterval(interval);

						if(!_this.first_show){

							postpone = (_this.first_anim_start + 2800) > date.getTime() ? (_this.first_anim_start + 2800) - date.getTime() : 0;

							setTimeout(
								function(){

									clearInterval(_this.first_anim_interval);

									_this.easing(
										_this.first_show_main_block, 'opacity', 10, 0, 300, '', 10,
										function(){
											_this.first_show_main_block.style.display = 'none';
										},
										'linear'
									);
									_this.first_show = true;
									_this.first_anim_start = 0;
								},
								postpone
							);

						}else
						{
							postpone = (_this.first_anim_start + 600) > date.getTime() ? (_this.first_anim_start + 600) - date.getTime() : 0;

							setTimeout(function()
								{
									_this.easing(
										_this.loading_curtain, 'opacity', 10, 0, 300, '', 10,
										function(){
											_this.loading_curtain.style.display = 'none';
										},
										'linear'
									);

									clearTimeout(_this.waiter_gif_timeout);
									_this.waiter_gif.removeAttribute('src');
									_this.first_anim_start = 0;
								},
								postpone);
						}
						_this.anim_status = false;
					}
				}
			},
			interval_time);
	},

	load_start : function(){
		var _this = this;
		this.loading_curtain.style.opacity = 0;
		this.loading_curtain.style.display = 'block';
		this.easing(
			this.loading_curtain, 'opacity', 1, 10, 300, '', 10, null, 'linear'
		);

		this.waiter_gif_timeout = setTimeout(function(){
			_this.waiter_gif.setAttribute('src', BX.getCDNPath('/bitrix/components/bitrix/intranet.ustat/images/pulse-waiter.gif'))
		}, 1000);

		var date = new Date;
		this.first_anim_start = date.getTime();
	},

	close : function()
	{
		this.easing(this.pulse_block, 'height', 647, 0, 250, 'px', 1, null, 'cubic');

		if(this.anim_status)
			this.load_done();

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
	easing : function(obj, prop, start, finish, duration,  px, fraction, complete_func, time_func){

		var easing = new BX.easing({
			duration:duration,
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

var pulse_popup = {

	wrapper : BX('intranet-activity-container'),

	toggle_links_text:['<?=GetMessageJS('INTRANET_USTAT_RATING_COMMON_TAB')?>', '<?=GetMessageJS('INTRANET_USTAT_RATING_INVOLVE_TAB')?>'],

	toggle_links : [],

	is_create:false,

	popup_frame : null,

	toggle_cont:function(cont_block_list, btn)
	{
		var _this = this;

		if(_this.toggle_links.length == cont_block_list.length){

			for(var i = 0; i <= _this.toggle_links.length-1; i++){

				cont_block_list[i].style.display = 'none';
				_this.toggle_links[i].className = 'pulse-popup-link';

				if(_this.toggle_links[i] == btn){
					_this.toggle_links[i].className = 'pulse-popup-link pulse-popup-link-active';
					cont_block_list[i].style.display = 'block';

					// load new tab content
					BX.INTRANET_USTAT_RATING_LAST_PARAMETERS.CURRENT_OFFSET = 0;
					loadIntranetUstatRating({LIST:cont_block_list[i].getAttribute('data-list')});
				}
			}
		}
	},

	show : function(cont_block_list)
	{
		if(this.is_create)
			this.open();
		else
			this.create(cont_block_list)
	},

	open : function(){
		this.popup_frame.style.display = 'block';
	},

	close : function(){
		this.popup_frame.style.display = 'none';
	},

	isOpen : function(){
		return this.popup_frame != null && this.popup_frame.style.display != 'none';
	},

	create : function(cont_block_list)
	{

		var _this = this,
			className,
			cont_block_wrap,
			top_link,
			top_children = [];

		for(var i = 0; i<this.toggle_links_text.length; i++){

			className = 'pulse-popup-link';
			if(i==0)
				className = 'pulse-popup-link pulse-popup-link-active';

			top_link = BX.create('span',{
				props:{className:className},
				events:{click:function(){_this.toggle_cont(cont_block_list, this)}},
				text:this.toggle_links_text[i]
			});

			top_children.push(top_link);

			_this.toggle_links.push(top_link)
		}

		top_children.push(
			BX.create('span',{
				props:{className:'pulse-popup-cls-btn'},
				events:{click:function(){_this.close()}}
			})
		);

		cont_block_wrap = BX.create('div',{
			props:{className:'pulse-popup-cont-wrap', id: 'pulse-popup-cont-wrap'},
			children:cont_block_list
		});

		// load next users
		BX.bind(cont_block_wrap, 'scroll', function(){

			var current_cont = BX.style(BX('pulse-cont-rating'), 'display') == 'none'
				? BX('pulse-cont-involve')
				: BX('pulse-cont-rating');

			var diff = current_cont.offsetHeight - BX('pulse-popup-cont-wrap').scrollTop;

			if (diff < 800)
			{
				loadIntranetUstatRating({});
			}
		});

		// skip cached scroll position
		/*setTimeout(function(){
			cont_block_wrap.scrollTop = 0;
		}, 800);*/

		this.popup_frame = BX.create('div', {
			props:{
				className:'pulse-popup-wrap',
				tabIndex: 2
			},
			children:[
				BX.create('div',{
					props:{className:'pulse-popup-block'},
					children:[
						BX.create('div', {
							props:{className:'pulse-popup-top'},
							children:top_children
						}),
						cont_block_wrap
					]
				})
			]
		});

		BX.bind(this.popup_frame, 'keydown', function(e){
			if (e.keyCode == 27)
			{
				_this.close();
			}
		});

		this.wrapper.appendChild(this.popup_frame);

		cont_block_list[0].style.display = 'block';

		this.is_create = true;
	},

	destroy : function ()
	{
		var oldPopup = BX.findChild(this.wrapper, {className:'pulse-popup-wrap'});

		if (oldPopup != null)
		{
			this.wrapper.removeChild(oldPopup);
			this.is_create = false;
			this.toggle_links = [];
		}
	}
};


</script>

