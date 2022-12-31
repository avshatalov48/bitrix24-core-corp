import {EventEmitter} from 'main.core.events';
import Options from "../options";
export default class AdminPanel
{
	static #instance: AdminPanel = null;
	container: Element;

	constructor()
	{
		this.container = BX("bx-panel");
		if (this.container)
		{
			this.bindEvents();
		}
	}

	bindEvents()
	{
		BX.addCustomEvent("onTopPanelCollapse", function(isCollapsed)
		{
			EventEmitter.emit(this, Options.eventName('onPanelHasChanged'), this.top);
		}.bind(this));

		BX.addCustomEvent("onTopPanelFix", function() {
			EventEmitter.emit(this, Options.eventName('onPanelHasChanged'), this.top);
		}.bind(this));
	}

	get height(): number
	{
		return this.container ? this.container.offsetHeight : 0;
	}

	get fixedHeight(): number
	{
		const adminPanelState = BX.getClass("BX.admin.panel.state");
		if (adminPanelState && adminPanelState.fixed)
		{
			return this.height;
		}
		return 0;
	}

	get top()
	{
		if (this.container)
		{
			const rect = this.container.getBoundingClientRect();

			if (rect.bottom > 0)
			{
				return Math.max(rect.bottom, this.fixedHeight);
			}
			return Math.max(0, this.fixedHeight);
		}
		return 0;
	}

	static getInstance()
	{
		if (!this.#instance)
		{
			this.#instance = new AdminPanel();
		}
		return this.#instance;
	}
}