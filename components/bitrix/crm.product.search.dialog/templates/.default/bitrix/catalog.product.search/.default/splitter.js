BX.namespace("BX.Crm");

BX.Crm.Splitter = (function() {
	var Splitter = function(params){

		if(!params && typeof params != "object")
			return;

		this.moveBtn = params.splitterMoveBtn;
		this.splitterElem = params.splitterElem;
		this.minValue = params.minValue;
		this.maxValue = params.maxValue;
		this.startPos = {};
		this.isCollaps = !!params.isCollapse;
		this.splitterCallBack = params.splitterCallBack || null;
		this.startPos.blocksPos = [];

		this.splitterCallBackParams = [];

		for(var i=0; i<this.splitterElem.length; i++)
		{
			if(this.splitterCallBack){
				this.splitterCallBackParams.push({
					node : this.splitterElem[i].node,
					attr : this.splitterElem[i].attr,
					val  : 0
				})
			}
			this.startPos.blocksPos.push(
				this.splitterElem[i].expandValue
			)
		}

		if(!!params.collapse)
		{
			this.collapseBtn = params.collapse.collapseBtn;
			this.collapseCallback = params.collapse.collapseCallback || null;

			this.collapseAnimDuration = (!!params.collapse.collapseAnimDuration) ? params.collapse.collapseAnimDuration : 250;
			this.collapseAnimTimeFunc = (!!params.collapse.collapseAnimTimeFunc) ? params.collapse.collapseAnimTimeFunc : BX.easing.transitions.linear;

			BX.bind(this.collapseBtn, 'mousedown', BX.proxy(this.toggle, this));
			BX.bind(this.collapseBtn, 'click', BX.proxy(this.toggle, this));
		}

		BX.bind(this.moveBtn, 'mousedown', BX.proxy(this.scale, this));
	};

	Splitter.prototype.setStartPos = function(e)
	{
		e =  e || window.event;
		BX.fixEventPageX(e);
		BX.PreventDefault(e);

		this.startPos = {
			move : e.pageX,
			blocksPos : []
		};

		for(var i=0; i<this.splitterElem.length; i++)
		{
			if (this.isCollaps)
			{
				this.startPos.blocksPos.push(
					parseInt(BX.style(this.splitterElem[i].node, this.splitterElem[i].attr))
				);
			}
			else
			{
				this.startPos.blocksPos.push(
					parseInt(BX.style(this.splitterElem[i].node, this.splitterElem[i].attr))
				);
			}
		}
	};

	Splitter.prototype.scale = function(e)
	{
		e =  e || window.event;
		BX.PreventDefault(e);

		if(this.isCollaps)
			return;

		document.onmousedown = BX.False;
		document.body.onselectstart = BX.False;
		document.body.ondragstart = BX.False;
		document.body.style.MozUserSelect = "none";
		document.body.style.cursor = "ew-resize";

		this.setStartPos(e);

		BX.bind(document, 'mousemove', BX.proxy(this._scale, this));
		BX.bind(document, "mouseup", BX.proxy(this.stopMove, this));
	};

	Splitter.prototype._scale = function(e)
	{
		e =  e || window.event;
		BX.fixEventPageX(e);
		var diff = e.pageX - this.startPos.move;

		this._move(diff);
	};

	Splitter.prototype._move = function(diff)
	{
		for(var i=0; i<this.splitterElem.length; i++)
		{
			if(this.splitterElem[i].isInversion)
			{
				if(this.startPos.blocksPos[i] - diff >=this.splitterElem[i].minValue && this.startPos.blocksPos[i] - diff <=this.splitterElem[i].maxValue){
					this.splitterElem[i].node.style[this.splitterElem[i].attr] = (this.startPos.blocksPos[i] - diff) + 'px';
				}
			}else {
				if(this.startPos.blocksPos[i] + diff >=this.splitterElem[i].minValue && this.startPos.blocksPos[i] + diff <=this.splitterElem[i].maxValue){
					this.splitterElem[i].node.style[this.splitterElem[i].attr] = (this.startPos.blocksPos[i] + diff) + 'px';
				}
			}
		}
	};

	Splitter.prototype.stopMove = function()
	{
		BX.unbind(document, 'mousemove', BX.proxy(this._scale, this));
		BX.unbind(document, 'mouseup', BX.proxy(this.stopMove, this));

		document.onmousedown = null;
		document.body.onselectstart = null;
		document.body.ondragstart = null;
		document.body.style.MozUserSelect = "";
		document.body.style.cursor = "auto";

		if(this.splitterCallBack){
			for(var i=0; i<this.splitterCallBackParams.length; i++){
				this.splitterCallBackParams[i].val = parseInt(BX.style(this.splitterElem[i].node, this.splitterElem[i].attr));
			}
			this.splitterCallBack(this.isCollaps, this.splitterCallBackParams);
		}
	};

	Splitter.prototype.toggle = function(e)
	{

		BX.PreventDefault(e);
		if(e.type == 'mousedown') return;

		var animParams = {
			start : {},
			finish : {}
		};

		if(this.isCollaps)
			this.expand(animParams);
		else
			this.collapse(e, animParams);
	};

	Splitter.prototype.expand = function(animParams)
	{

		for(var i=0; i<this.splitterElem.length; i++)
		{
			animParams.start['attr_' + i] = this.splitterElem[i].collapseValue;
			animParams.finish['attr_' + i] = this.startPos.blocksPos[i];
		}
		this._easing(animParams);
		this.isCollaps = false;
	};

	Splitter.prototype.collapse = function(e, animParams)
	{
		this.setStartPos(e);

		for(var i=0; i<this.splitterElem.length; i++)
		{
			animParams.start['attr_' + i] = this.startPos.blocksPos[i];
			animParams.finish['attr_' + i] = this.splitterElem[i].collapseValue;
		}
		this._easing(animParams);
		this.isCollaps = true;
	};

	Splitter.prototype._easing = function(params)
	{
		var easing = new BX.easing({
			duration : this.collapseAnimDuration,
			start : params.start,
			finish :  params.finish,
			transition : this.collapseAnimTimeFunc,
			step : BX.proxy(function(state){
				for(var i=0; i<this.splitterElem.length; i++){
					this.splitterElem[i].node.style[this.splitterElem[i].attr] = state['attr_' + i] + 'px';
				}
			}, this),
			complete: BX.proxy( function()
			{
				if(this.collapseCallback)
				{
					for(var i=0; i<this.splitterCallBackParams.length; i++){
						this.splitterCallBackParams[i].val = parseInt(BX.style(this.splitterElem[i].node, this.splitterElem[i].attr));
					}
					this.collapseCallback(this.isCollaps, this.splitterCallBackParams);
				}
			}, this)
		});
		easing.animate();
	};

	return Splitter;
})();
