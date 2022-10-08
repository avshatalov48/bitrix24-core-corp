/** @memberof BX.Crm.Timeline.Animation */
export default class Fasten
{
	constructor()
	{
		this._id = "";
		this._settings = {};
		this._initialItem = null;
		this._finalItem = null;
		this._events = null;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._initialItem = this.getSetting("initialItem");
		this._finalItem = this.getSetting("finalItem");

		this._anchor = this.getSetting("anchor");
		this._events = this.getSetting("events", {});
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultValue)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultValue;
	}

	addFixedHistoryItem()
	{
		const node = this._finalItem.getWrapper();
		BX.addClass(node, 'crm-entity-stream-section-animate-start');
		if (this._anchor.parentNode && node)
		{
			this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);
		}
		setTimeout( BX.delegate(
			function() {
				BX.removeClass(node, 'crm-entity-stream-section-animate-start');
			},
			this
		), 0);
	}

	run()
	{
		const node = this._initialItem.getWrapper();
		this._clone = node.cloneNode(true);

		BX.addClass(this._clone, 'crm-entity-stream-section-animate-start crm-entity-stream-section-top-fixed');

		this._startPosition = BX.pos(node);
		this._clone.style.position = "absolute";
		this._clone.style.width = this._startPosition.width + "px";

		let _cloneHeight = this._startPosition.height;
		const _minHeight = 65;
		const _sumPaddingContent = 18;
		if (_cloneHeight < _sumPaddingContent + _minHeight)
			_cloneHeight = _sumPaddingContent + _minHeight;

		this._clone.style.height = _cloneHeight + "px";
		this._clone.style.top = this._startPosition.top + "px";
		this._clone.style.left = this._startPosition.left + "px";
		this._clone.style.zIndex = 960;

		document.body.appendChild(this._clone);

		setTimeout(
			BX.proxy(
				function (){
					BX.addClass(this._clone, "crm-entity-stream-section-casper" )
				},
				this
			),
			0
		);

		this._anchorPosition = BX.pos(this._anchor);
		const finish = {
			top: this._anchorPosition.top,
			height: _cloneHeight + 15,
			opacity: 1
		};

		const _difference = this._startPosition.top - this._anchorPosition.bottom;
		const _deepHistoryLimit = 2 * (document.body.clientHeight + this._startPosition.height);

		if (_difference > _deepHistoryLimit)
		{
			finish.top = this._startPosition.top - _deepHistoryLimit;
			finish.opacity = 0;
		}

		let _duration = Math.abs(finish.top - this._startPosition.top) * 2;
		_duration = (_duration < 1500) ? 1500 : _duration;

		const movingEvent = new BX.easing({
			duration: _duration,
			start: {top: this._startPosition.top, height: 0, opacity: 1},
			finish: finish,
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				this._clone.style.top = state.top + "px";
				this._clone.style.opacity = state.opacity;
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
		this._anchor.style.height = 0;
		this.addFixedHistoryItem();
		BX.remove(this._clone);

		if(BX.type.isFunction(this._events["complete"]))
		{
			this._events["complete"]();
		}
	}

	static create(id, settings)
	{
		const self = new Fasten();
		self.initialize(id, settings);
		return self;
	}
}
