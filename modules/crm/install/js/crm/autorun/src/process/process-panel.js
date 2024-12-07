/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */
import { Loc } from 'main.core';
import { ProcessState } from './process-state';
import '../css/process.css';
import 'ui.design-tokens';

/**
 * @memberOf BX.Crm.Autorun
 * @alias BX.AutorunProcessPanel
 */
export class ProcessPanel
{
	_id = '';
	_settings = {};

	_manager = null;
	_container = null;
	_wrapper = null;
	_stateNode = null;
	_progressNode = null;
	_hasLayout = false;
	_isHidden = false;

	static items = {};

	static isExists(id)
	{
		return (id in ProcessPanel.items);
	}

	static create(id, settings): ProcessPanel
	{
		const self = new ProcessPanel(id, settings);

		ProcessPanel.items[self.getId()] = self;

		return self;
	}

	constructor(id, settings)
	{
		this._id = id;
		this._settings = settings || {};

		this._container = BX(this.getSetting('container'));
		if (!BX.type.isElementNode(this._container))
		{
			throw 'AutorunProcessPanel: Could not find container.';
		}

		this._manager = this.getSetting('manager');
		this._isHidden = this.getSetting('isHidden', false);
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	scrollInToView()
	{
		if (!this._container)
		{
			return;
		}

		const rect = BX.pos(this._container);
		if (window.scrollY > rect.top)
		{
			window.scrollTo(window.scrollX, rect.top);
		}
	}

	layout()
	{
		if (this._hasLayout)
		{
			return;
		}

		this._wrapper = BX.create('DIV', { attrs: { className: 'crm-view-progress' } });
		BX.addClass(
			this._wrapper,
			this._isHidden ? 'crm-view-progress-hide' : 'crm-view-progress-show crm-view-progress-bar-active',
		);

		this._container.appendChild(this._wrapper);

		this._wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs: { className: 'crm-view-progress-info' },
					text: this.getSetting('title', 'Please wait...'),
				},
			),
		);

		this._progressNode = BX.create('DIV', { attrs: { className: 'crm-view-progress-bar-line' } });
		this._stateNode = BX.create('DIV', { attrs: { className: 'crm-view-progress-steps' } });
		this._wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs: { className: 'crm-view-progress-inner' },
					children:
						[
							BX.create(
								'DIV',
								{
									attrs: { className: 'crm-view-progress-bar' },
									children: [this._progressNode],
								},
							),
							this._stateNode,
						],
				},
			),
		);

		if (BX.prop.getBoolean(this._settings, 'enableCancellation', false))
		{
			this._wrapper.appendChild(
				BX.create(
					'a',
					{
						attrs: { className: 'crm-view-progress-link', href: '#' },
						text: Loc.getMessage('JS_CORE_WINDOW_CANCEL'),
						events: { click: this.onCancelButtonClick.bind(this) },
					},
				),
			);
		}

		this._hasLayout = true;
	}

	hasLayout()
	{
		return this._hasLayout;
	}

	isHidden()
	{
		return this._isHidden;
	}

	show()
	{
		if (!this._isHidden)
		{
			return;
		}

		if (!this._hasLayout)
		{
			return;
		}

		BX.removeClass(this._wrapper, 'crm-view-progress-hide');
		BX.addClass(this._wrapper, 'crm-view-progress-show');

		this._isHidden = false;
	}

	hide()
	{
		if (this._isHidden)
		{
			return;
		}

		if (!this._hasLayout)
		{
			return;
		}

		BX.removeClass(this._wrapper, 'crm-view-progress-show');
		BX.addClass(this._wrapper, 'crm-view-progress-hide');

		this._isHidden = true;
	}

	clearLayout()
	{
		if (!this._hasLayout)
		{
			return;
		}

		BX.remove(this._wrapper);
		this._wrapper = this._stateNode = null;

		this._hasLayout = false;
	}

	getManager()
	{
		return this._manager;
	}

	setManager(manager)
	{
		this._manager = manager;
	}

	onManagerStateChange()
	{
		if (!(this._hasLayout && this._manager))
		{
			return;
		}

		const state = this._manager.getState();
		if (state !== ProcessState.error)
		{
			const processed = this._manager.getProcessedItemCount();
			const total = this._manager.getTotalItemCount();

			let progress = 0;
			if (total !== 0)
			{
				progress = Math.floor((processed / total) * 100);
				const offset = progress % 5;
				if (offset !== 0)
				{
					progress -= offset;
				}
			}

			if (processed > 0 && total > 0)
			{
				const template = this.getSetting(
					'stateTemplate',
					Loc.getMessage('CRM_AUTORUN_PROCESS_PANEL_DEFAULT_STATE_TEMPLATE'),
				);

				this._stateNode.innerHTML = template
					.replace('#processed#', processed)
					.replace('#total#', total)
				;
			}
			else
			{
				this._stateNode.innerHTML = '';
			}

			this._progressNode.className = 'crm-view-progress-bar-line';
			if (progress > 0)
			{
				this._progressNode.className += ` crm-view-progress-line-${progress.toString()}`;
			}
		}
	}

	onCancelButtonClick(e)
	{
		this._manager.stop();

		return BX.eventReturnFalse(e);
	}
}
