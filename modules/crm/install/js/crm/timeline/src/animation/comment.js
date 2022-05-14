import Shift from "./shift";

/** @memberof BX.Crm.Timeline.Animation */
export default class Comment
{
	constructor()
	{
		this._node = null;
		this._anchor = null;
		this._nodeParent  = null;
		this._startPosition = null;
		this._events = null;
	}

	initialize(node, anchor, startPosition, events)
	{
		this._node = node;
		this._anchor = anchor;
		this._nodeParent  = node.parentNode;
		this._startPosition = startPosition;
		this._events = BX.type.isPlainObject(events) ? events : {};

	}

	run()
	{
		BX.addClass(this._node, 'crm-entity-stream-section-animate-start');

		this._node.style.position = "absolute";
		this._node.style.width = this._startPosition.width + "px";
		this._node.style.height = this._startPosition.height + "px";
		this._node.style.top = this._startPosition.top - 30 + "px";
		this._node.style.left = this._startPosition.left + "px";
		this._node.style.opacity = 0;
		this._node.style.zIndex = 960;

		document.body.appendChild(this._node);

		const nodeOpacityAnim = new BX.easing({
			duration: 350,
			start: {opacity: 0},
			finish: {opacity: 100},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				this._node.style.opacity = state.opacity / 100;
			}, this),
			complete: BX.proxy(function () {
				if (BX.type.isFunction(this._events["start"]))
				{
					this._events["start"]();
				}
				const shift = Shift.create(
					this._node,
					this._anchor,
					this._startPosition,
					false,
					{complete: BX.delegate(this.finish, this)}
				);
				shift.run();
			}, this)
		});
		nodeOpacityAnim.animate();


		if(BX.type.isFunction(this._events["complete"]))
		{
			this._events["complete"]();
		}
	}

	finish()
	{
		this._node.style.position = "";
		this._node.style.width = "";
		this._node.style.height = "";
		this._node.style.top = "";
		this._node.style.left = "";
		this._node.style.opacity = "";
		this._node.style.zIndex = "";
		this._anchor.style.height = "";
		this._anchor.parentNode.insertBefore(this._node, this._anchor.nextSibling);
		setTimeout(
			BX.delegate(function() {
				BX.removeClass(this._node, 'crm-entity-stream-section-animate-start');
				BX.remove(this._anchor);
			}, this),
			0
		);
	}

	static create(node, anchor, startPosition, events)
	{
		const self = new Comment();
		self.initialize(node, anchor, startPosition, events);
		return self;
	}
}
