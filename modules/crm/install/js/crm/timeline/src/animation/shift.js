/** @memberof BX.Crm.Timeline.Animation */
export default class Shift
{
	constructor()
	{
		this._node = null;
		this._anchor = null;
		this._nodeParent  = null;
		this._startPosition = null;
		this._events = null;
	}

	initialize(node, anchor, startPosition, shadowNode, events)
	{
		this._node = node;
		this._shadowNode = shadowNode;
		this._anchor = anchor;
		this._nodeParent  = node.parentNode;
		this._startPosition = startPosition;
		this._events = BX.type.isPlainObject(events) ? events : {};

	}

	run()
	{
		this._anchorPosition = BX.pos(this._anchor);

		setTimeout(
			BX.proxy(
				function (){
					BX.addClass(this._node, "crm-entity-stream-section-casper" )
				},
				this
			),
			0
		);

		const movingEvent = new BX.easing({
			duration: 1500,
			start: {top: this._startPosition.top, height: 0},
			finish: {top: this._anchorPosition.top, height: this._startPosition.height + 20},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				this._node.style.top = state.top + "px";
				this._anchor.style.height = state.height + "px";
			}, this),
			complete: BX.proxy(function () {
				this.finish();
			}, this)
		});
		movingEvent.animate();
	}

	finish()
	{
		if(BX.type.isFunction(this._events["complete"]))
		{
			this._events["complete"]();
		}
		if( this._shadowNode !== false )
		{
			// this._stub.height = 0;
		}
	}

	static create(node, anchor, startPosition, shadowNode, events)
	{
		const self = new Shift();
		self.initialize(node, anchor, startPosition, shadowNode, events);
		return self;
	}
}
