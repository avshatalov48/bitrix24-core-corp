/** @memberof BX.Crm.Timeline.Animation */
export default class Item
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

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	run()
	{
		this._node = this._initialItem.getWrapper();
		const originalPosition = BX.pos(this._node);
		this._initialYPosition = originalPosition.top;
		this._initialXPosition = originalPosition.left;
		this._initialWidth = this._node.offsetWidth;
		this._initialHeight = this._node.offsetHeight;

		this._anchorYPosition = BX.pos(this._anchor).top;

		this.createStub();
		this.createGhost();
		this.moveGhost();
	}

	createStub()
	{
		this._stub = BX.create(
			"DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-shadow" },
				children :
					[
						BX.create(
							"DIV",
							{
								props: { className: "crm-entity-stream-section-content" },
								style: { height: this._initialHeight + "px" }
							}
						)
					]
			}
		);

		this._node.parentNode.insertBefore(this._stub, this._node);
	}

	createGhost()
	{
		this._ghostNode = this._node;
		this._ghostNode.style.position = "absolute";
		this._ghostNode.style.width = this._initialWidth + "px";
		this._ghostNode.style.height = this._initialHeight + "px";
		this._ghostNode.style.top = this._initialYPosition + "px";
		this._ghostNode.style.left = this._initialXPosition + "px";
		document.body.appendChild(this._ghostNode);
		setTimeout(BX.proxy(function (){BX.addClass(this._ghostNode, "crm-entity-stream-section-casper" )}, this), 20);
	}

	moveGhost()
	{
		const node = this._ghostNode;
		const movingEvent = new BX.easing({
			duration: 500,
			start: {top: this._initialYPosition},
			finish: {top: this._anchorYPosition},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				node.style.top = state.top + "px";
			}, this)
		});
		setTimeout( BX.proxy(function () {
			movingEvent.animate();
			node.style.boxShadow = "";
		}, this), 500);

		const placeEventAnim = new BX.easing({
			duration: 500,
			start: {height: 0},
			finish: {height: this._initialHeight + 20},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				this._anchor.style.height = state.height + "px";
			}, this),
			complete: BX.proxy(function () {
				if (BX.type.isFunction(this._events["complete"]))
				{
					this._events["complete"]();
				}

				this.addHistoryItem();
				this.removeGhost();
			}, this)
		});
		setTimeout(function (){placeEventAnim.animate()}, 500);
	}

	addHistoryItem()
	{
		const node = this._finalItem.getWrapper();

		this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);

		this._finalItemHeight = this._anchor.offsetHeight - node.offsetHeight;
		this._anchor.style.height = 0;
		node.style.marginBottom = this._finalItemHeight + "px";
	}

	removeGhost()
	{
		const ghostNode = this._ghostNode;
		const finalNode = this._finalItem.getWrapper();

		ghostNode.style.overflow = "hidden";
		const hideCasperItem = new BX.easing({
			duration: 70,
			start: {opacity: 100, height: ghostNode.offsetHeight, marginBottom: this._finalItemHeight},
			finish: {opacity: 0, height: finalNode.offsetHeight, marginBottom: 20},
			// transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				ghostNode.style.opacity = state.opacity / 100;
				ghostNode.style.height = state.height + "px";
				finalNode.style.marginBottom = state.marginBottom + "px";
			}, this),
			complete: BX.proxy(function () {
				ghostNode.remove();
				finalNode.style.marginBottom = "";
				this.collapseStub();

			}, this)
		});
		hideCasperItem.animate();
	}

	collapseStub()
	{
		const removePlannedEvent = new BX.easing({
			duration: 500,
			start: {opacity: 100, height: this._initialHeight, marginBottom: 15},
			finish: {opacity: 0, height: 0, marginBottom: 0},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				this._stub.style.height = state.height + "px";
				this._stub.style.marginBottom = state.marginBottom + "px";
				this._stub.style.opacity = state.opacity / 100;
			}, this),
			complete: BX.proxy(function () {
				this.inited = false
			}, this)

		});
		removePlannedEvent.animate();

	}

	static create(id, settings)
	{
		const self = new Item();
		self.initialize(id, settings);
		return self;
	}
}
