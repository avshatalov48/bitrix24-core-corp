;(function ()
{
	'use strict';
	BX.namespace('BX.Salescenter.UserConsent');

	BX.Salescenter.UserConsent = {
		init: function(parameters)
		{
			this.formNode = BX(parameters.formId);
			this.buttonNode = BX(parameters.buttonId);
			this.activeButtonNode = BX(parameters.activeButtonId);
			this.agreementBlockNode = BX(parameters.agreementBlockId);

			this.bindEvents();
		},

		bindEvents: function()
		{
			BX.bind(this.buttonNode, 'click', BX.proxy(this.saveUserConsent, this));
			BX.addCustomEvent('button-click', BX.proxy(this.turnOffWaitProp, this));

			BX.bind(this.activeButtonNode, 'change', BX.proxy(this.toggleAgreementBlock, this));
		},

		getAllFormData: function()
		{
			var prepared = BX.ajax.prepareForm(this.formNode),
				i;

			for (i in prepared.data)
			{
				if (prepared.data.hasOwnProperty(i) && i == '')
				{
					delete prepared.data[i];
				}
			}

			return !!prepared && prepared.data ? prepared.data : {};
		},

		saveUserConsent: function(e)
		{
			e.preventDefault();

			var formData = this.getAllFormData();

			BX.ajax.runComponentAction(
				'bitrix:salescenter.userconsent',
				'saveUserConsent',
				{
					mode: 'class',
					data: {
						formData: formData
					}
				}
			).then(function(response)
			{
				top.BX.SidePanel.Instance.close();
			});
		},

		turnOffWaitProp: function(button)
		{
			if (button.TYPE === 'save')
			{
				button.WAIT = false;
			}
		},

		toggleAgreementBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(this.agreementBlockNode)
		}
	}
})();

window.BX.animationHandler = {
	animations: [],
	animate: function(params) //creates animation
	{
		params = params || {};
		var node = params.node || null;
		var p = new BX.Promise();
		params.transition = params.transition || BX.easing.transitions.linear;

		if(!BX.type.isElementNode(node))
		{
			p.reject();
			return p;
		}

		var duration = params.duration || 300;

		// add or get animation
		var anim = null;
		for(var k in BX.animationHandler.animations)
		{
			if(BX.animationHandler.animations[k].node == node)
			{
				anim = BX.animationHandler.animations[k];
				break;
			}
		}

		if(anim === null)
		{
			var easing = new BX.easing({
				duration : duration,
				start: params.start,
				finish: params.finish,
				transition: params.transition,
				step : params.step,
				complete: function()
				{
					// cleanup animation
					for(var k in BX.animationHandler.animations)
					{
						if(BX.animationHandler.animations[k].node == node)
						{
							BX.animationHandler.animations[k].easing = null;
							BX.animationHandler.animations[k].node = null;

							BX.animationHandler.animations.splice(k, 1);

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
			anim.easing.animate();

			BX.animationHandler.animations.push(anim);
		}
		else
		{
			anim.easing.stop(true);
			params.duplicate.call(this);

			if(p)
			{
				p.reject();
			}
		}

		return p;
	},
	animateShowHide: function(params) //node toggle event handler method
	{
		params = params || {};
		var node = params.node || null;
		params.transition = params.transition || BX.easing.transitions.linear;

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

		return BX.animationHandler.animate({
			node: node,
			duration: params.duration,
			start: !way ? toShow : toHide,
			finish: way ? toShow : toHide,
			transition: params.transition,
			complete: function(){
				BX[!way ? 'addClass' : 'removeClass'](node, 'invisible');
				node.style.cssText = '';

				if(BX.type.isFunction(params.complete))
				{
					params.complete.call(this);
				}
			},
			duplicate: function(){
				BX[way ? 'addClass' : 'removeClass'](node, 'invisible');
				node.style.cssText = '';
				if(BX.type.isFunction(params.duplicate))
				{
					params.duplicate.call(this);
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
	fadeSlideToggleByClass: function(node, way, duration, onComplete) //node toggle event handler call with params
	{
		return BX.animationHandler.animateShowHide({
			node: node,
			duration: duration,
			toShow: {opacity: 100, height: BX.animationHandler.getInvisibleSize(node).height},
			toHide: {opacity: 0, height: 0},
			complete: onComplete,
			way: way //false - addClass, true - removeClass
		});
	},
	getInvisibleSize: function(node) //automatically calculates node height
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
	}
};