/** @memberof BX.Crm.Timeline.Animation */
export default class Expand
{
	constructor()
	{
		this._node = null;
		this._callback = null;
	}

	initialize(node, callback)
	{
		this._node = node;
		this._callback = BX.type.isFunction(callback) ? callback : null;
	}

	run()
	{
		const position = BX.pos(this._node);

		this._node.style.height = 0;
		this._node.style.opacity = 0;
		this._node.style.overflow = "hidden";

		(new BX.easing(
				{
					duration : 150,
					start : { height: 0 },
					finish: { height: position.height },
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step: BX.delegate(this.onNodeHeightStep, this),
					complete: BX.delegate(this.onNodeHeightComplete, this)
				}
			)
		).animate();
	}

	onNodeHeightStep(state)
	{
		this._node.style.height = state.height + "px";
	}

	onNodeHeightComplete()
	{
		this._node.style.overflow = "";
		(new BX.easing(
				{
					duration : 150,
					start : { opacity: 0 },
					finish: { opacity: 100 },
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step: BX.delegate(this.onNodeOpacityStep, this),
					complete: BX.delegate(this.onNodeOpacityComplete, this)
				}
			)
		).animate();
	}

	onNodeOpacityStep(state)
	{
		this._node.style.opacity = state.opacity / 100;
	}

	onNodeOpacityComplete()
	{
		this._node.style.height = "";
		this._node.style.opacity = "";
		if(this._callback)
		{
			this._callback();
		}
	}

	static create(node, callback)
	{
		const self = new Expand();
		self.initialize(node, callback);
		return self;
	}
}
