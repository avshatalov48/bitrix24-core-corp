;(function(){

BX.namespace('BX.Salescenter');

if(window.BX.Salescenter.ComponentAnimation)
{
	return;
}

BX.Salescenter.ComponentAnimation = {
	showClass: 'salescenter-cashbox-page-show',
	hideClass: 'salescenter-cashbox-page-hide',
	invisibleClass: 'salescenter-cashbox-page-invisible',
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
		for(var k in BX.Salescenter.ComponentAnimation.animations)
		{
			if(BX.Salescenter.ComponentAnimation.animations[k].node == node)
			{
				anim = BX.Salescenter.ComponentAnimation.animations[k];
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
					for(var k in BX.Salescenter.ComponentAnimation.animations)
					{
						if(BX.Salescenter.ComponentAnimation.animations[k].node == node)
						{
							BX.Salescenter.ComponentAnimation.animations[k].easing = null;
							BX.Salescenter.ComponentAnimation.animations[k].node = null;

							BX.Salescenter.ComponentAnimation.animations.splice(k, 1);

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

			BX.Salescenter.ComponentAnimation.animations.push(anim);
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

		var invisible = BX.hasClass(node, BX.Salescenter.ComponentAnimation.invisibleClass);
		var way = (typeof params.way == 'undefined' || params.way === null) ? invisible : !!params.way;

		if(invisible != way)
		{
			var p = new BX.Promise();
			p.resolve();
			return p;
		}

		var toShow = params.toShow || {};
		var toHide = params.toHide || {};

		return BX.Salescenter.ComponentAnimation.animate({
			node: node,
			duration: params.duration,
			start: !way ? toShow : toHide,
			finish: way ? toShow : toHide,
			transition: params.transition,
			complete: function(){
				BX[!way ? 'addClass' : 'removeClass'](node, BX.Salescenter.ComponentAnimation.invisibleClass);
				node.style.cssText = '';

				if(BX.type.isFunction(params.complete))
				{
					params.complete.call(this);
				}
			},
			duplicate: function(){
				BX[way ? 'addClass' : 'removeClass'](node, BX.Salescenter.ComponentAnimation.invisibleClass);
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
		return BX.Salescenter.ComponentAnimation.animateShowHide({
			node: node,
			duration: duration,
			toShow: {opacity: 100, height: BX.Salescenter.ComponentAnimation.getInvisibleSize(node).height},
			toHide: {opacity: 0, height: 0},
			complete: onComplete,
			way: way //false - addClass, true - removeClass
		});
	},

	getInvisibleSize: function(node) //automatically calculates node height
	{
		var invisible = BX.hasClass(node, BX.Salescenter.ComponentAnimation.invisibleClass);

		if(invisible)
		{
			BX.removeClass(node, BX.Salescenter.ComponentAnimation.invisibleClass);
		}
		var p = BX.pos(node);
		if(invisible)
		{
			BX.addClass(node, BX.Salescenter.ComponentAnimation.invisibleClass);
		}

		return p;
	},

	smoothScroll: function (node) {
		var posFrom = BX.GetWindowScrollPos().scrollTop,
			posTo = BX.pos(node).top - Math.round(BX.GetWindowInnerSize().innerHeight / 2),
			toBottom = posFrom < posTo,
			distance = Math.abs(posTo - posFrom),
			speed = Math.round(distance / 100) > 20 ? 20 : Math.round(distance / 100),
			step = 4 * speed,
			posCurrent = toBottom ? posFrom + step : posFrom - step,
			timer = 0;

		if (toBottom)
		{
			for (var i = posFrom; i < posTo; i += step)
			{
				setTimeout("window.scrollTo(0," + posCurrent +")", timer * speed);
				posCurrent += step;
				if (posCurrent > posTo)
				{
					posCurrent = posTo;
				}
				timer++;
			}
		}
		else
		{
			for (var i = posFrom; i > posTo; i -= step)
			{
				setTimeout("window.scrollTo(0," + posCurrent +")", timer * speed);
				posCurrent -= step;
				if (posCurrent < posTo)
				{
					posCurrent = posTo;
				}
				timer++;
			}
		}

	},

	smoothShowHide: function (node)
	{
		if (!node.classList.contains(BX.Salescenter.ComponentAnimation.showClass)) {
			setTimeout(function () {
				BX.removeClass(node, BX.Salescenter.ComponentAnimation.invisibleClass);
			}, 100);
			BX.Salescenter.ComponentAnimation.smoothShow(node);
		}
		else
		{
			BX.Salescenter.ComponentAnimation.smoothHide(node);
			BX.addClass(node, BX.Salescenter.ComponentAnimation.invisibleClass);
		}
	},

	smoothShow: function (node)
	{
		BX.removeClass(node, BX.Salescenter.ComponentAnimation.hideClass);
		BX.addClass(node, BX.Salescenter.ComponentAnimation.showClass);
	},

	smoothHide: function (node)
	{
		BX.removeClass(node, BX.Salescenter.ComponentAnimation.showClass);
		BX.addClass(node, BX.Salescenter.ComponentAnimation.hideClass);
	},
};

})(window);