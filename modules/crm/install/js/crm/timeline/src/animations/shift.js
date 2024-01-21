import { Dom } from 'main.core';

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

	initialize(node, anchor, startPosition, shadowNode, events, additionalShift)
	{
		this._node = node;
		this._shadowNode = shadowNode;
		this._anchor = anchor;
		this._nodeParent  = node.parentNode;
		this._startPosition = startPosition;
		this._events = BX.type.isPlainObject(events) ? events : {};
		this.additionalShift = additionalShift ?? 0;
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

		// const nodeHeight = Dom.getPosition(this._node).height;
		const nodeHeight = Dom.getPosition(this._node).height;
		const nodeMarginBottom = parseFloat(getComputedStyle(this._node).marginBottom);
		const nodeMarginTop = parseFloat(getComputedStyle(this._node).marginTop);
		const nodeHeightWithMargin = nodeHeight + nodeMarginBottom + nodeMarginTop;

		const expandPlaceEvent = new BX.easing({
			duration: 600,
			start: { height: 0 },
			finish: { height: nodeHeightWithMargin },
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: (state) => {
				this._anchor.style.height = state.height + "px";
			},
		});

		const movingEvent = new BX.easing({
			duration: 1000,
			start: { top: this._startPosition.top },
			finish: {
				top: this._anchorPosition.top - nodeHeightWithMargin + this.additionalShift,
			},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				this._node.style.top = state.top + "px";
			}, this),
			complete: BX.proxy(function () {
				this.finish();
			}, this)
		});

		expandPlaceEvent.animate();
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

	static create(node, anchor, startPosition, shadowNode, events, additionalShift: number)
	{
		const self = new Shift();
		self.initialize(node, anchor, startPosition, shadowNode, events, additionalShift);
		return self;
	}
}
