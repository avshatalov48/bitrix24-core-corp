/**
 * The file contains functionality that is used NOT very often
 */

BX.namespace('Tasks.Util');

BX.mergeEx(BX.Tasks.Util, {

	formatTimeAmount : function(time, format)
	{
		time = parseInt(time);
		if(isNaN(time))
		{
			return '';
		}

		var sign = time < 0 ? '-' : '';
		time = Math.abs(time);

		var hours = '' + Math.floor(time / 3600);
		var minutes = '' + (Math.floor(time / 60) % 60);
		var seconds = '' + time % 60;

		var nPad = function(num){
			return '00'.substring(0, 2 - num.length)+num;
		};

		var result = nPad(hours)+':'+nPad(minutes);

		if(!format || format == 'HH:MI:SS')
		{
			result += ':'+nPad(seconds);
		}

		return sign+result;
	},

	// todo: may be move\copy to BX.Tasks
	delay: function(action, actionCancel, delay, ctx)
	{
		action = action || BX.DoNothing;
		actionCancel = actionCancel || BX.DoNothing;
		delay = delay || 300;
		ctx = ctx || this;

		var timer = null;

		var f = function()
		{
			var args = arguments;
			timer = setTimeout(function(){
				action.apply(ctx, args);
			}, delay);
		};
		f.cancel = function()
		{
			actionCancel.apply(ctx, []);
			clearTimeout(timer);
		};

		return f;
	},

	showByClass: function(node)
	{
		if(BX.hasClass(node, 'invisible'))
		{
			BX.removeClass(node, 'invisible');
		}
	},

	hideByClass: function(node)
	{
		if(!BX.hasClass(node, 'invisible'))
		{
			BX.addClass(node, 'invisible');
		}
	},

	/*
	 Function assumes presence of the following css definition:
	 .invisible{height:0;}
	 */
	fadeToggleByClass: function(node, duration, onComplete)
	{
		return BX.Tasks.Util.animateShowHide({
			node: node,
			duration: duration,
			toShow: {opacity: 100},
			toHide: {opacity: 0},
			complete: onComplete
		})
	},

	/*
	 Function assumes presence of the following css definition:
	 .invisible{height:0;opacity:0;}
	 */
	fadeSlideToggleByClass: function(node, duration, onComplete)
	{
		return BX.Tasks.Util.animateShowHide({
			node: node,
			duration: duration,
			toShow: {opacity: 100, height: BX.Tasks.Util.getInvisibleSize(node).height},
			toHide: {opacity: 0, height: 0},
			complete: onComplete
		});
	},

	/*
	 Function assumes presence of the following css definition:
	 .invisible{width:0;opacity:0;}
	 */
	fadeSlideHToggleByClass: function(node, duration, onComplete)
	{
		return BX.Tasks.Util.animateShowHide({
			node: node,
			duration: duration,
			toShow: {opacity: 100, width: BX.Tasks.Util.getInvisibleSize(node).width},
			toHide: {opacity: 0, width: 0},
			complete: onComplete
		});
	},

	animateShowHide: function(params)
	{
		params = params || {};
		var node = params.node || null;

		if(!BX.type.isElementNode(node))
		{
			var p = new BX.Promise();
			p.reject();
			return p;
		}

		var invisible = BX.hasClass(node, 'invisible');
		var way = (typeof params.way == 'undefined' || params.way === null) ? invisible : !!params.way;

		if(invisible != way)
		{
			var p = new BX.Promise();
			p.resolve();
			return p;
		}

		var toShow = params.toShow || {};
		var toHide = params.toHide || {};

		return BX.Tasks.Util.animate({
			node: node,
			duration: params.duration,
			start: !way ? toShow : toHide,
			finish: way ? toShow : toHide,
			complete: function(){
				BX[!way ? 'addClass' : 'removeClass'](node, 'invisible');
				node.style.cssText = '';

				if(BX.type.isFunction(params.complete))
				{
					params.complete.call(this);
				}
			},
			step: function(state){

				if(typeof state.opacity != 'undefined')
				{
					node.style.opacity = state.opacity/100;
				}
				if(typeof state.height != 'undefined')
				{
					node.style.height = state.height+'px';
				}
				if(typeof state.width != 'undefined')
				{
					node.style.width = state.width+'px';
				}
			}
		});
	},

	/*
	 Launching multiple animations on the same node at the same time is not supported.
	 */
	animate: function(params)
	{
		params = params || {};
		var node = params.node || null;

		var p = new BX.Promise();

		if(!BX.type.isElementNode(node))
		{
			p.reject();
			return p;
		}

		var duration = params.duration || 300;

		var rt = BX.Tasks.Runtime;

		if(typeof rt.animations == 'undefined')
		{
			rt.animations = [];
		}

		// add or get animation
		var anim = null;
		for(var k in rt.animations)
		{
			if(rt.animations[k].node == node)
			{
				anim = rt.animations[k];
				break;
			}
		}

		if(anim === null)
		{
			var easing = new BX.easing({
				duration : duration,
				start: params.start,
				finish: params.finish,
				transition: BX.easing.transitions.linear,
				step : params.step,
				complete: function()
				{
					// cleanup animation
					for(var k in rt.animations)
					{
						if(rt.animations[k].node == node)
						{
							rt.animations[k].easing = null;
							rt.animations[k].node = null;

							rt.animations.splice(k, 1);

							break;
						}
					}

					node = null;
					anim = null;

					params.complete.call(this);

					if(p)
					{
						p.fulfill();
					}
				}
			});
			anim = {node: node, easing: easing};

			rt.animations.push(anim);
		}
		else
		{
			anim.easing.stop();

			if(p)
			{
				p.reject();
			}
		}

		anim.easing.animate();

		return p;
	},

	getInvisibleSize: function(node)
	{
		var invisible = BX.hasClass(node, 'invisible');

		if(invisible)
		{
			BX.removeClass(node, 'invisible');
		}
		var p = BX.pos(node);
		if(invisible)
		{
			BX.addClass(node, 'invisible');
		}

		return p;
	},

	isEnter: function(e)
	{
		return this.getKeyFromEvent(e) == 13;
	},

	isEsc: function(e)
	{
		return this.getKeyFromEvent(e) == 27;
	},

	getKeyFromEvent: function(e)
	{
		e = e || window.event;
		return e.keyCode || e.which;
	},

	filterFocusBlur: function(node, cbFocus, cbBlur, timeout)
	{
		if(!BX.type.isElementNode(node))
		{
			return false;
		}

		var timer = false;

		cbFocus = cbFocus || BX.DoNothing;
		cbBlur = cbBlur || BX.DoNothing;
		timeout = timeout || 50;

		var f = function(focus, eventArgs)
		{
			if(focus)
			{
				if(timer != false)
				{
					clearTimeout(timer);
					timer = false;
				}
				else
				{
					cbFocus.apply(this, eventArgs);
				}
			}
			else
			{
				timer = setTimeout(function(){
					timer = false;
					cbBlur.apply(this, eventArgs);
				}, timeout);
			}
		};

		BX.bind(node, 'blur', function(){f.apply(this, [false, arguments])});
		BX.bind(node, 'focus', function(){f.apply(this, [true, arguments])});

		return true;
	},

	bindInstantChange: function(node, cb, ctx)
	{
		if(!BX.type.isElementNode(node))
		{
			return BX.DoNothing;
		}

		ctx = ctx || node;

		var value = node.value;

		var f = BX.debounce(function(e){

			if(node.value.toString() != value.toString())
			{
				cb.apply(ctx, arguments);

				value = node.value;
			}
		}, 3, ctx);

		BX.bind(node, 'input', f);
		BX.bind(node, 'keyup', f);
		BX.bind(node, 'change', f);
	},

	disable: function(node)
	{
		if(BX.type.isElementNode(node))
		{
			node.setAttribute('disabled', 'disabled');
		}
	},

	enable: function(node)
	{
		if(BX.type.isElementNode(node))
		{
			node.removeAttribute('disabled');
		}
	},

	getMessagePlural: function(n, msgId)
	{
		var pluralForm, langId;

		langId = BX.message('LANGUAGE_ID');
		n = parseInt(n);

		if (n < 0)
		{
			n = (-1) * n;
		}

		if (langId)
		{
			switch (langId)
			{
				case 'de':
				case 'en':
					pluralForm = ((n !== 1) ? 1 : 0);
					break;

				case 'ru':
				case 'ua':
					pluralForm = ( ((n%10 === 1) && (n%100 !== 11)) ? 0 : (((n%10 >= 2) && (n%10 <= 4) && ((n%100 < 10) || (n%100 >= 20))) ? 1 : 2) );
					break;

				default:
					pluralForm = 1;
					break;
			}
		}
		else
		{
			pluralForm = 1;
		}

		if(BX.type.isArray(msgId))
		{
			return msgId[pluralForm];
		}

		return (BX.message(msgId + '_PLURAL_' + pluralForm));
	},

	fireGlobalTaskEvent: function(type, taskData, options, taskDataUgly)
	{
		if(!type)
		{
			return false;
		}

		type = type.toString();
		options = options || {};

		if(
			type != 'ADD' && type != 'UPDATE' && 
			type != 'UPDATE_STAGE' &&  type != 'DELETE' && 
			type != 'NOOP'
		)
		{
			return false;
		}

		var eventArgs = [type, {task: taskData, taskUgly: taskDataUgly, options: options}];

		BX.onCustomEvent(window, 'tasksTaskEvent', eventArgs);
		if(window != window.top) // if we are inside iframe, translate event to the parent window also
		{
			window.top.BX.onCustomEvent(window.top, 'tasksTaskEvent', eventArgs);
		}

		return true;
	}
});

BX.Tasks.Util.hintManager = {

	bindHelp: function(scope)
	{
		var target = {className: 'js-id-hint-help'};

		BX.bindDelegate(scope, 'mouseover', target, BX.Tasks.passCtx(this.onHelpShow, this));
		BX.bindDelegate(scope, 'mouseout', target, BX.Tasks.passCtx(this.onHelpHide, this));
	},

	showDisposable: function(node, body, id, parameters)
	{
		if(!BX.type.isPlainObject(parameters))
		{
			parameters = {};
		}
		if(!('closeLabel' in parameters))
		{
			parameters.closeLabel = BX.message('TASKS_COMMON_DONT_SHOW_AGAIN');
		}
		if(!('autoHide' in parameters))
		{
			parameters.autoHide = true;
		}

		this.show(node, body, false, id, parameters);
	},

	/**
	 * @access private
	 * @param node
	 * @param body
	 * @param callback
	 * @param id
	 * @param parameters
	 */
	show: function(node, body, callback, id, parameters)
	{
		id = id || BX.util.hashCode((Math.random()*100).toString()).toString();
		parameters = parameters || {};

		var rt = BX.Tasks.Runtime;

		rt.hintPopup = rt.hintPopup || {};

		if(typeof rt.hintPopup[id] == 'undefined')
		{
			rt.hintPopup[id] = {
				popup: null,
				disable: false
			};
		}

		if(this.wasDisposed(id))
		{
			return;
		}

		if(rt.hintPopup[id].popup == null)
		{
			var content = [];
			if(BX.type.isNotEmptyString(parameters.title))
			{
				content.push(BX.create("SPAN",
					{attrs: {className: "task-hint-popup-title"}, text: parameters.title}
				));
			}
			if(!BX.type.isNotEmptyString(body))
			{
				body = '';
			}
			body = BX.util.htmlspecialchars(body).replace(/#BR#/g, '<br />');

			content.push(BX.create("P", {html: body, style: {margin: '10px 20px 10px 5px'}}));

			if(BX.type.isNotEmptyString(parameters.closeLabel))
			{
				content.push(BX.create("P",
					{
						style: {margin: '10px 20px 10px 5px'},
						children: [
							BX.create("A",
								{
									props: {href: "javascript:void(0)"},
									text: parameters.closeLabel,
									events: {"click": function(){
										BX.Tasks.Util.hintManager.disable(id);
										BX.Tasks.Util.hintManager.hide(id);
									}}
								}
							)
						]
					}
				));
			}

			rt.hintPopup[id].popup = BX.PopupWindowManager.create(id,
				node,
				{
					closeByEsc: false,
					closeIcon: true,
					angle: {},
					autoHide: parameters.autoHide === true,
					offsetLeft: 50,
					offsetTop : 5,
					events: {onPopupClose: BX.delegate(this.onViewModeHintClose, this)},
					content: BX.create("DIV",
						{
							attrs: {className: "task-hint-popup-contents"},
							children: content
						}
					)
				}
			)
		}

		rt.hintPopup[id].popup.show();
	},

	wasDisposed: function(id)
	{
		BX.Tasks.Runtime.hintPopup = BX.Tasks.Runtime.hintPopup || {};
		BX.Tasks.Runtime.hintPopup[id] = BX.Tasks.Runtime.hintPopup[id] || {};

		return BX.Tasks.Runtime.hintPopup[id].disable;
	},

	hide: function(id)
	{
		try
		{
			BX.Tasks.Runtime.hintPopup[id].popup.close();
		}
		catch(e)
		{
		}
	},

	disable:  function(id)
	{
		BX.Tasks.Runtime.hintPopup = BX.Tasks.Runtime.hintPopup || {};
		BX.Tasks.Runtime.hintPopup[id] = BX.Tasks.Runtime.hintPopup[id] || {};

		BX.Tasks.Runtime.hintPopup[id].disable = true;
		BX.userOptions.save(
			"tasks",
			"task_hints",
			id,
			"N",
			false
		);
	},

	disableSeveral: function(pack)
	{
		if(BX.type.isPlainObject(pack))
		{
			var rt = BX.Tasks.Runtime;
			rt.hintPopup = rt.hintPopup || {};

			for(var id in pack)
			{
				rt.hintPopup[id] = rt.hintPopup[id] || {};
				rt.hintPopup[id].disable = !pack[id];
			}
		}
	},

	onHelpShow: function(node)
	{
		var enabled = BX.data(node, 'hint-enabled');
		if(enabled !== null && typeof enabled != 'undefined' && enabled != '1')
		{
			return;
		}

		var text = BX.data(node, 'hint-text');
		if(!text)
		{
			text = node.innerHTML;
		}

		if(BX.type.isNotEmptyString(text))
		{
			this.onHelpHide();

			var popup = new BX.PopupWindow('tasks-generic-help-popup', node, {
				lightShadow: true,
				autoHide: false,
				darkMode: true,
				offsetLeft: 0,
				offsetTop: 2,
				bindOptions: {position: "top"},
				zIndex: 200,
				events : {
					onPopupClose : function() {
						this.destroy();
						BX.Tasks.Runtime.helpWindow = null;
					}
				},
				content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: text})
			});
			popup.setAngle({offset:13, position: 'bottom'});
			popup.show();

			BX.Tasks.Runtime.helpWindow = popup;
		}
	},

	onHelpHide: function()
	{
		if(BX.Tasks.Runtime.helpWindow)
		{
			BX.Tasks.Runtime.helpWindow.close();
		}
	}
};

BX.Tasks.Util.MouseTracker = function(){

	this.coords = {x: 0, y: 0};

	BX.bind(document, 'mousemove', BX.delegate(function(e){
		this.coords = {
			x: e.pageX ? e.pageX :(e.clientX ? e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft : 0),
			y: e.pageY ? e.pageY :(e.clientY ? e.clientY + (document.documentElement.scrollTop || document.body.scrollTop) - document.documentElement.clientTop : 0)
		}
	}, this));
};
BX.Tasks.Util.MouseTracker.getCoordinates = function()
{
	return BX.clone(BX.Tasks.Util.MouseTracker.getInstance().coords);
};
BX.Tasks.Util.MouseTracker.getInstance = function()
{
	if(typeof BX.Tasks.Runtime.mouseTracker == 'undefined')
	{
		BX.Tasks.Runtime.mouseTracker = new BX.Tasks.Util.MouseTracker();
	}

	return BX.Tasks.Runtime.mouseTracker;
};

if(typeof BX.Tasks.Runtime == 'undefined')
{
	BX.Tasks.Runtime = {
	};
}