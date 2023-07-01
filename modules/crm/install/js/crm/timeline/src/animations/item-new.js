import Shift from "./shift";
import {Dom} from 'main.core';
import CompatibleItem from "../items/compatible-item";

/** @memberof BX.Crm.Timeline.Animation */
export default class ItemNew
{
	constructor()
	{
		this._id = "";
		this._settings = {};
		this._initialItem = null;
		this._finalItem = null;
		this._events = null;
		this._areAnimatedItemsVisible = null;
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

	addHistoryItem()
	{
		if (this._finalItem instanceof CompatibleItem)
		{
			const node = this._finalItem.getWrapper();
			this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);
		}
		else
		{
			this._finalItem.initWrapper();
			Dom.insertBefore(this._finalItem.getWrapper(), this._anchor.nextSibling);
			this._finalItem.initLayoutApp({ add: false });
		}
	}

	run()
	{
		this._node = this._initialItem.getWrapper();
		this._areAnimatedItemsVisible = this._node.offsetParent !== null;
		if (this._areAnimatedItemsVisible)
		{
			this.createStub();

			BX.addClass(this._node, 'crm-entity-stream-section-animate-start');

			this._startPosition = BX.pos(this._stub);

			this._node.style.position = "absolute";
			this._node.style.width = this._startPosition.width + "px";
			this._node.style.height = this._startPosition.height + "px";
			this._node.style.top = this._startPosition.top + "px";
			this._node.style.left = this._startPosition.left + "px";
			this._node.style.zIndex = 960;


			document.body.appendChild(this._node);

			const shift = Shift.create(
				this._node,
				this._anchor,
				this._startPosition,
				this._stub,
				{complete: BX.delegate(this.finish, this)}
			);
			shift.run();
		}
		else
		{
			this.finish();
		}
	}

	createStub()
	{
		this._stub = BX.create(
			"DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-shadow" },
				children :
					[
						BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon" } }),
						BX.create(
							"DIV",
							{
								props: { className: "crm-entity-stream-section-content" },
								style: { height: this._initialItem._wrapper.clientHeight + "px" }
							}
						)
					]
			}
		);

		this._node.parentNode.insertBefore(this._stub, this._node);
	}

	finish()
	{
		this._anchor.style.height = 0;
		//this._anchor.parentNode.insertBefore(this._node, this._anchor.nextSibling);

		if (this._areAnimatedItemsVisible)
		{
			const stubContainer = this._stub.querySelector('.crm-entity-stream-section-content');
			setTimeout(
				BX.delegate(function()
				{
					BX.removeClass(this._node, 'crm-entity-stream-section-animate-start');
				}, this),
				0
			);

			this._node.style.opacity = 0;

			setTimeout(BX.delegate(
				function()
				{
					stubContainer.style.height = 0;
					stubContainer.style.opacity = 0;
					stubContainer.style.paddingTop = 0;
					stubContainer.style.paddingBottom = 0;
				},
				this
			), 120);
		}
		setTimeout( BX.delegate(
			function() {
				if (this._areAnimatedItemsVisible)
				{
					BX.remove(this._stub);
				}
				BX.remove(this._node);
				this.addHistoryItem();

				if(BX.type.isFunction(this._events["complete"]))
				{
					this._events["complete"]();
				}
			},
			this
		), 420 );

	}

	static create(id, settings)
	{
		const self = new ItemNew();
		self.initialize(id, settings);
		return self;
	}
}
