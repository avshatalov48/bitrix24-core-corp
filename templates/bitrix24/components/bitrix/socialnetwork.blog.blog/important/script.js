;(function(window){
if (top.BSBBW)
	return true;

	function animation(message, main_block){
		if(!BX.browser.isPropertySupported('transform'))
			return false;

		function vendor(props){
			if(BX.browser.isPropertySupported(props))
				return BX.browser.isPropertySupported(props);
			else
				return false
		}

		function getPrefix() {
			var vendorPrefixes = ['moz','webkit', 'o', 'ms'],
				len = vendorPrefixes.length,
				vendor = '';

			while (len--)
				if ('transform' in document.body.style ){
					return vendor
				}else if((vendorPrefixes[len] + 'Transform') in document.body.style){
					vendor='-'+vendorPrefixes[len].toLowerCase()+'-';
				}
			return vendor;
		}

		var corner_gradient = BX.create('div',{
			props:{
				className:'anim-corner-gradient'
			}
		});

		var corner = BX.create('div', { props : { className:'anim-corner' }, children : [ corner_gradient ]}),
			corner_wrap = BX.create('div',{ props:{className:'anim-corner-wrap'}, children:[corner] }),
			distort_shadow = BX.create('div',{ props:{className:'block-distort-shadow-wrap'},
				children:[ BX.create('div',{ props:{className:'block-distort-shadow'} }) ] }),
			distort = BX.create('div', { props:{ className:'block-distort' }, children:[message,corner_wrap] }),
			main_wrap = BX.create('div',{ props:{className:'main-mes-wrap'}, children:[distort, distort_shadow] });

		main_block.appendChild(main_wrap);


		distort.style [vendor('transformOrigin')] = '180px 130px';

		distort.style[vendor('transform')] = 'rotate(42deg)';

		message.style[vendor('transformOrigin')] = '50% 100%';

		message.style[vendor('transformOrigin')] = '50% 100%';
		message.style[vendor('transform')] = 'rotate(-42deg)';

		corner_wrap.style[vendor('transform')] = 'rotate(-42deg)';


		var easing = new BX.easing({
			duration:100,
			start:{
				height:475,
				bottom:-182,
				left:-124,
				shadow_height:0,
				shadow_bottom:-74,
				gradient_height:0,
				gradient_width:0
			},
			finish:{
				height:342,
				bottom:-50,
				left:-72,
				shadow_height:130,
				shadow_bottom:-52,
				gradient_height:172,
				gradient_width:197
			},
			transition : BX.easing.transitions.linear(),
			step:function(state){
				distort.style.height = state.height + 'px';
				corner_wrap.style.left = state.left + 'px';
				corner_wrap.style.bottom = state.bottom + 'px';
				distort_shadow.style.height = state.shadow_height + 'px';
				distort_shadow.style.bottom = state.shadow_bottom + 'px';
				corner_gradient.style.height = state.gradient_height + 'px';
				corner_gradient.style.width = state.gradient_width + 'px';

			},
			complete:function(){

				var gradient_rotate;

				corner_wrap.style[vendor('transformOrigin')] = '62px 0';
				corner_wrap.style.left = -17 + 'px';
				corner_wrap.style.bottom = -183 + 'px';

				distort_shadow.style[vendor('transformOrigin')] = '28px 0';
				distort_shadow.style.left = '-28px';
				distort_shadow.style.bottom = '46px';

				distort.style[vendor('transformOrigin')] = '47px 100%';
				distort.style.top = -195+'px';
				distort.style.left = -46+'px';

				message.style[vendor('transformOrigin')] = '0 0';
				message.style.top = 337 + 'px';
				message.style.left = 41 + 'px';


				var easing_2 = new BX.easing({
					duration:200,
					start:{
						distort_rotate:42,
						shadow_rotate:42,
						shadow_skew:0,
						corner_rotate:-42,
						corner_height:180,
						corner_bottom:-183,
						message_rotate: -42,
						gradient_rotate:42
					},
					finish:{
						distort_rotate:34,
						shadow_rotate:34,
						shadow_skew:11,
						corner_rotate:-50,
						corner_height:251,
						corner_bottom:-248,
						message_rotate: -34,
						gradient_rotate:48
					},
					transition : BX.easing.transitions.linear(),

					step:function(state){

						distort.style[vendor('transform')] = 'rotate('+ state.distort_rotate + 'deg)';

						corner_wrap.style[vendor('transform')] = 'rotate('+ state.corner_rotate + 'deg)';
						corner_wrap.style.bottom = state.corner_bottom + 'px';

						corner.style.height = state.corner_height + 'px';

						distort_shadow.style[vendor('transform')] = 'rotate('+ state.shadow_rotate + 'deg)';

						message.style[vendor('transform')] = 'rotate('+ state.message_rotate + 'deg)';

						corner_gradient.style.height = state.corner_height + 'px';

						corner_gradient.style.backgroundImage = getPrefix()+'linear-gradient('+state.gradient_rotate+'deg, #ece297 42%, #e5d38e 57%, #f6e9a3 78%)';

					},
					complete:function(){

						corner.style[vendor('transformOrigin')] = '100% 0';
						corner.style.boxShadow = 'none';

						if(getPrefix() == '-webkit-') gradient_rotate = 24;
						else gradient_rotate = 67;

						var easing_3 = new BX.easing({
							duration:200,
							start:{
								distort_rotate:34,
								corner_rotate:-50,
								corner_width:260,
								corner_height:251,
								corner_skew:0,
								message_rotate:-34,
								shadow_rotate:34,
								shadow_skew:0,
								shadow_width:340,
								opacity:10,
								gradient_rotate:48,
								gradient_percent:57
							},
							finish:{
								distort_rotate:16,
								corner_rotate:-60,
								corner_width:236,
								corner_height:256,
								corner_skew:8,
								message_rotate:-16,
								shadow_rotate:16,
								shadow_skew:15,
								shadow_width:301,
								opacity:0,
								gradient_rotate:gradient_rotate,
								gradient_percent:50
							},
							transition:BX.easing.transitions.linear(),
							step:function(state){

								distort.style[vendor('transform')] = 'rotate('+ state.distort_rotate + 'deg)';
								distort.style.opacity = (state.opacity/10);

								corner_wrap.style[vendor('transform')] = 'rotate('+ state.corner_rotate + 'deg)';

								corner.style[vendor('transform')] = 'skew('+ state.corner_skew +'deg, 0deg)';
								corner.style.width = state.corner_width + 'px';
								corner.style.height = state.corner_height + 'px';

								corner_gradient.style.height = state.corner_height + 'px';

								message.style[vendor('transform')] = 'rotate('+ state.message_rotate + 'deg)';

								distort_shadow.style[vendor('transform')] = 'rotate('+ state.shadow_rotate + 'deg) skew('+ state.shadow_skew +'deg, 0)';
								distort_shadow.style.width = state.shadow_width + 'px';
								distort_shadow.style.opacity = (state.opacity/10);

								corner_gradient.style.backgroundImage = getPrefix()+'linear-gradient('+state.gradient_rotate+'deg, #ece297 42%, #e5d38e '+state.gradient_percent+'%, #f6e9a3 78%)';
							},
							complete:function(){
								main_wrap.style.display = 'none';
							}
						});
						easing_3.animate()
					}
				});
				easing_2.animate();
			}
		});
		easing.animate();
	}

top.BSBBW = function(params) {
	this.CID = params["CID"];
	this.controller = params["controller"];

	this.nodes = params["nodes"];
	this.tMessage = this.nodes['template'].innerHTML;

	this.url = params["url"];

	this.options = params["options"];
	this.post_info = params["post_info"];
	this.post_info['AJAX_POST'] = "Y";

	this.sended = false;
	this.active = false;
	this.inited = false;
	this.busy = false;
	this.userCounter = 0;

	this.inited = this.init(params);
	this.show();

	BX.addCustomEvent(this.controller, "onDataAppeared", BX.delegate(this.onDataAppeared, this));
	BX.addCustomEvent(this.controller, "onDataRanOut", BX.delegate(this.onDataRanOut, this));
	BX.addCustomEvent(this.controller, "onReachedLimit", BX.delegate(this.onReachedLimit, this));
	BX.addCustomEvent(this.controller, "onRequestSend", BX.delegate(this.showWait, this));
	BX.addCustomEvent(this.controller, "onResponseCame", BX.delegate(this.hideWait, this));
	BX.addCustomEvent(this.controller, "onResponseFailed", BX.delegate(this.hideWait, this));
	BX.addCustomEvent(window, "onImUpdateCounter", BX.delegate(this.onImUpdateCounter, this));
	BX.addCustomEvent("onPullEvent-main", BX.delegate(function(command,params){
		if (command == 'user_counter'
				&& params[BX.message('SITE_ID')]
				&& params[BX.message('SITE_ID')]["BLOG_POST_IMPORTANT"]
			)
		{
			this.onImUpdateCounter(params[BX.message('SITE_ID')]);
		}
	}, this));
	BX.addCustomEvent(window, 'onSonetLogCounterClear', BX.delegate(function(){this.onImUpdateCounter({"BLOG_POST_IMPORTANT" : 0});}, this));
	BX.addCustomEvent(window, 'onImportantPostRead', BX.delegate(this.onImportantPostRead, this));
}

top.BSBBW.prototype = {
	init : function(params) {
		this.page_settings = params["page_settings"];
		this.page_settings["NavRecordCount"] = parseInt(this.page_settings["NavRecordCount"]);

		this.limit = (this.page_settings["NavPageCount"] > 1 ? 3 : 0);
		this.current = 0;

		if (this.active)
			clearTimeout(this.active);
		this.active = false;

		this.data_id = {};
		this.data = params["data"];
		for (var ii in this.data)
			this.data_id['id' + this.data[ii]["id"]] = 'normal';

		if (this.data.length <= 0)
			BX.onCustomEvent(this.controller, "onDataRanOut");
		else
			BX.onCustomEvent(this.controller, "onDataAppeared");

		if (!this.inited)
		{
			BX.bind(this.nodes["right"], "click", BX.delegate(function(){this.onShiftPage("right")}, this));
			BX.bind(this.nodes["left"], "click", BX.delegate(function(){this.onShiftPage("left")}, this));
			BX.adjust(this.nodes["btn"], {attrs : {url : this.url}, events: {click : BX.delegate(this.onClickToRead, this)}});
		}
		return true;
	},
	show : function() {
		var
			message = this.tMessage,
			data = this.data[this.current];
		if (!data)
			return;
		for (var ii in data)
			message = message.replace("__" + ii + "__", data[ii]);
		this.nodes["leaf"].innerHTML = message;
		this.nodes["text"].innerHTML = message;
		this.nodes["counter"].innerHTML = (this.current + 1);
		this.nodes["total"].innerHTML = this.page_settings["NavRecordCount"];
		var btn = BX.findChild(this.nodes["text"], {"className" : "sidebar-imp-mess-text"}, true);
		var avatar = BX.findChild(this.nodes["text"], {attribute : {"data-bx-author-avatar" : true}}, true);
		if (!!btn)
		{
			BX.adjust(btn, {attrs : {url : this.url}, events: {click : BX.delegate(this.onClickToRead, this)}});
		}

		if (data["author_avatar_style"] !== "" && !!avatar)
		{
			BX.adjust(avatar, {
				style: {
					backgroundImage: data["author_avatar_style"],
					backgroundRepeat: "no-repeat",
					backgroundPosition: "center",
					backgroundSize: "cover",
					backgroundColor: "transparent"
				}
			});
		}

		btn = BX.findChild(this.nodes["leaf"], {"className" : "sidebar-imp-mess-text"}, true);
		avatar = BX.findChild(this.nodes["leaf"], {attribute : {"data-bx-author-avatar" : true}}, true);

		if (data["author_avatar_style"] !== "" && !!avatar)
		{
			BX.adjust(avatar, {
				style: {
					backgroundImage: data["author_avatar_style"],
					backgroundRepeat: "no-repeat",
					backgroundPosition: "center",
					backgroundSize: "cover",
					backgroundColor: "transparent"
				}
			});
		}
	},
	showWait : function() { /* showWait */ },
	hideWait : function() { /* hideWait */ },
	onImUpdateCounter : function(arCount)
	{
		var counter = parseInt(arCount['BLOG_POST_IMPORTANT']);
		if (this.userCounter != counter)
		{
			this.userCounter = counter;
			if (this.userCounter > 0)
			{
				this.startCheck();
			}
		}
	},
	startCheck : function()
	{
		if (this.busy !== true)
		{
			var request = this.post_info;
			request['sessid'] = BX.bitrix_sessid();
			request['page_settings'] = this.page_settings;
			request['page_settings']['iNumPage'] = null;
			BX.ajax({
				'method': 'POST',
				'processData': false,
				'url': this.url,
				'data': request,
				'onsuccess': BX.delegate(function(data){this.busy = false; this.parseResponse(data, true);}, this),
				'onfailure': BX.delegate(function(data){this.busy = false; this.onResponseFailed(data);}, this)
			});
		}
	},
	parseResponse : function(response, fromCheck)
	{
		var data = false, result = false;
		try{eval("result="+ response + ";");} catch(e) {}
		if (!result || !result.data || result.data.length <= 0)
			data = false;
		else if (fromCheck === true)
		{
			var dataNew = [], data = result.data;
			for (var ii in data )
			{
				if (typeof data[ii] == "object" && !this.data_id['id' + data[ii]["id"]])
				{
					dataNew.push(data[ii]);
				}
			}
			result.page_settings["NavRecordCount"] = parseInt(result.page_settings["NavRecordCount"]);
			this.page_settings["NavRecordCount"] = parseInt(this.page_settings["NavRecordCount"]);
			if (this.data.length > 0 &&
				dataNew.length == (result.page_settings["NavRecordCount"] - this.page_settings["NavRecordCount"]))
			{
				var d = dataNew.pop();
				while(!!d)
				{
					this.data_id['id' + d["id"]] = 'normal';
					this.data.unshift(d);
					this.current++;
					d = dataNew.pop();
				}
				this.page_settings["NavPageCount"] = result.page_settings["NavPageCount"];
				this.page_settings["NavRecordCount"] = result.page_settings["NavRecordCount"];
				this.show();
			}
			else
			{
				var current = 0, res = this.data[this.current];
				if (this.data.length > 0 && !!res)
				{
					for (var ii = 0; ii < data.length; ii++)
					{
						if (typeof data[ii] == "object" && data[ii]["id"] == res["id"])
						{
							current = ii;
							break;
						}
					}
				}
				this.init(result);
				this.current = current;
				this.show();
			}
		}
		else
		{
			this.page_settings["NavPageNomer"] = result.page_settings["NavPageNomer"];
			data = result.data;
			for (var ii in data )
			{
				if (typeof data[ii] == "object" && !this.data_id['id' + data[ii]["id"]])
				{
					this.data_id['id' + data[ii]["id"]] = 'normal';
					this.data.push(data[ii]);
				}
			}
			if (this.data.length > 0)
				BX.onCustomEvent(this.controller, "onDataAppeared");
		}
		return true;
	},
	onClickToRead : function(send)
	{
		var
			data = this.data[this.current], options = [], ii;
		for (ii in this.options)
			options.push({post_id : data["id"], name : this.options[ii]['name'], value:this.options[ii]['value']});
		var request = this.post_info;
		request['options'] = options;
		request['page_settings'] = this.page_settings;
		request['sessid'] = BX.bitrix_sessid();
		send = (send === false ? false : true);

		request = BX.ajax.prepareData(request);

		if (send)
		{
			BX.ajax({
				method: 'GET',
				url: this.url + (this.url.indexOf('?') !== -1 ? "&" : "?") + request,
				onsuccess: BX.delegate(this.onAfterClickToRead, this),
				onfailure: function(data){}
			});
		}
		this.onShiftPage('drop');
		animation(this.nodes["leaf"], this.nodes["block"]);
	},
	onAfterClickToRead : function ()
	{
	},
	onShiftPage : function(status)
	{
		if (this.active)
			clearTimeout(this.active);
		this.active = setTimeout(BX.delegate(function(){this.active=false;}, this), 120000);

		if (status == 'drop')
		{
			this.page_settings["NavRecordCount"]--;
			this.data_id['id' + this.data[this.current]["id"]] = 'readed';
			this.data = BX.util.deleteFromArray(this.data, this.current);
			if (!!this.data && this.data.length > 0)
			{
				this.current = this.current - 1;
				status = 'left';
			}
			else
			{
				BX.onCustomEvent(this.controller, "onDataRanOut");
				return;
			}
		}

		if (status == 'right')
		{
			if (this.current <= 0)
			{
				this.page_settings["NavRecordCount"] = parseInt(this.page_settings["NavRecordCount"]);
				if (this.data.length < this.page_settings["NavRecordCount"])
					this.current = 1;
				else
					this.current = this.data.length;
			}
			this.current = this.current - 1;
		}
		else
		{
			if (this.current >= (this.data.length - 1))
				this.current = 0;
			else
				this.current = this.current + 1;
		}
		if (this.limit > 0 && this.current >= (this.data.length - 1 - this.limit))
			BX.onCustomEvent(this.controller, "onReachedLimit");

		this.show();
	},
	onDataRanOut: function()
	{
		if ((!this.data || this.data.length <= 0) && this.controller.style.display != "none")
		{
			this.bodyAnimationheight = this.controller.offsetHeight;
			(this.bodyAnimation = new BX.easing({
				duration : 200,
				start : { height : this.controller.offsetHeight, opacity : 100},
				finish : { height : 0, opacity : 0},
				transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
				step : BX.delegate(function(state){
					BX.adjust(this.controller, {style:{height : state.height + 'px', opacity : (state.opacity/100)}});
				}, this),
				complete : BX.delegate(function(){
					this.controller.style.display = "none";
				}, this)
			})).animate();
		}
	},
	onDataAppeared: function()
	{
		if (!!this.data && this.data.length > 0 && this.controller.style.display == "none")
		{
			var height = (!!this.bodyAnimationheight ? this.bodyAnimationheight : 200);
			this.controller.style.display = "block";
			(this.bodyAnimation = new BX.easing({
				duration : 200,
				start : { height : 0, opacity : 0},
				finish : { height : height, opacity : 100},
				transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
				step : BX.delegate(function(state){
					BX.adjust(this.controller, {style:{height : state.height + 'px', opacity : (state.opacity/100)}});
				}, this),
				complete : BX.delegate(function(){
					BX.adjust(this.controller, {style:{display : "block", height : "auto", opacity : "auto"}});
				}, this)
			})).animate();
		}
	},
	onReachedLimit : function()
	{
		if (this.sended === true)
			return;

		var
			request = this.post_info,
			needToUnbind = false;

		this.page_settings["NavPageNomer"] = parseInt(this.page_settings["NavPageNomer"]);
		this.page_settings["NavPageCount"] = parseInt(this.page_settings["NavPageCount"]);

		if (this.page_settings["NavPageCount"] <= 1)
			needToUnbind = true;
		else if (this.page_settings["bDescPageNumbering"] == true)
		{
			if (this.page_settings["NavPageNomer"] > 1)
				this.page_settings["iNumPage"] = parseInt(this.page_settings["NavPageNomer"]) - 1;
			else
				needToUnbind = true;
		}
		else if (this.page_settings["NavPageNomer"] < this.page_settings["NavPageCount"])
			this.page_settings["iNumPage"] = parseInt(this.page_settings["NavPageNomer"]) + 1;
		else
			needToUnbind = true;
		if (needToUnbind === true)
		{
			BX.removeCustomEvent(this.controller, "onReachedLimit", BX.delegate(this.onReachedLimit, this));
			return true;
		}
		BX.onCustomEvent(this.controller, "onRequestSend");
		this.sended = true;
		request['page_settings'] = this.page_settings;
		request['sessid'] = BX.bitrix_sessid();
		BX.ajax({
			'method': 'POST',
			'processData': false,
			'url': this.url,
			'data': request,
			'onsuccess': BX.delegate(this.onResponseCame, this),
			'onfailure': BX.delegate(this.onResponseFailed, this)
		});
	},
	onResponseCame : function(data)
	{
		this.sended = false;
		BX.onCustomEvent(this.controller, "onResponseCame");
		this.parseResponse(data);
	},
	onResponseFailed : function(data)
	{
		this.sended = false;
		BX.onCustomEvent(this.controller, "onResponseFailed");
	},
	onImportantPostRead : function(postId, CID)
	{
		if (postId > 0)
		{
			for (var ii in this.data)
			{
				if (this.data[ii]["id"] == postId)
				{
					this.current = ii;
					this.onClickToRead((CID == this.CID));
					break;
				}
			}
		}
	}
}
})(window);