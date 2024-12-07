/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */

import { ajax as Ajax, Loc, Type } from 'main.core';
import { ProcessPanel } from './process-panel';
import { ProcessState } from './process-state';

/**
 * @memberof BX.Crm.Autorun
 * @alias BX.AutorunProcessManager
 */
export class Processor
{
	_id = '';
	_settings = {};

	_serviceUrl = '';
	_actionName = '';

	_controllerActionName = '';

	_params = null;

	_container = null;
	_panel = null;
	_runHandle = 0;

	_hasLayout = false;

	_state = ProcessState.intermediate;
	_processedItemCount = 0;
	_totalItemCount = 0;
	_errors = [];

	static messages = {
		// default messages, you can override them via settings.messages
		requestError: Loc.getMessage('CRM_AUTORUN_PROCESS_REQUEST_ERROR'),
	};

	static items = {};

	static create(id, settings): Processor
	{
		const self = new Processor(id, settings);

		Processor.items[self.getId()] = self;

		return self;
	}

	static createIfNotExists(id, settings): Processor
	{
		if (id in Processor.items)
		{
			return Processor.items[id];
		}

		return Processor.create(id, settings);
	}

	constructor(id: string, settings: Object)
	{
		this._id = Type.isStringFilled(id) ? id : `crm_lrp_mgr_${Math.random().toString().slice(2)}`;
		this._settings = settings || {};

		this._serviceUrl = BX.prop.getString(this._settings, 'serviceUrl', '');
		this._actionName = BX.prop.getString(this._settings, 'actionName', '');
		this._controllerActionName = BX.prop.getString(this._settings, 'controllerActionName', '');

		if (this._serviceUrl === '' && this._controllerActionName === '')
		{
			throw 'AutorunProcessManager: Either the serviceUrl or controllerActionName parameter must be specified.';
		}

		this._container = BX(this.getSetting('container'));
		if (!Type.isElementNode(this._container))
		{
			throw 'AutorunProcessManager: Could not find container.';
		}

		this._params = BX.prop.getObject(this._settings, 'params', null);
		if (BX.prop.getBoolean(this._settings, 'enableLayout', false))
		{
			this.layout();
		}
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultval)
	{
		if (name in this._settings)
		{
			return this._settings[name];
		}

		return defaultval;
	}

	getTimeout()
	{
		return BX.prop.getInteger(this._settings, 'timeout', 2000);
	}

	getMessage(name, defaultValue)
	{
		if (name in Processor.messages)
		{
			return Processor.messages[name];
		}

		return Type.isUndefined(defaultValue) ? name : defaultValue;
	}

	getParams()
	{
		return this._params;
	}

	setParams(params)
	{
		this._params = params;
	}

	isHidden()
	{
		return !this._hasLayout || this._panel.isHidden();
	}

	show()
	{
		if (this._hasLayout)
		{
			this._panel.show();
		}
	}

	hide()
	{
		if (this._hasLayout)
		{
			this._panel.hide();
		}
	}

	scrollInToView()
	{
		if (this._panel)
		{
			this._panel.scrollInToView();
		}
	}

	layout()
	{
		if (this._hasLayout)
		{
			return;
		}

		if (!this._panel)
		{
			let title = BX.prop.getString(this._settings, 'title', '');
			if (title === '')
			{
				title = this.getMessage('title', '');
			}

			let stateTemplate = BX.prop.getString(this._settings, 'stateTemplate', '');
			if (stateTemplate === '')
			{
				stateTemplate = this.getMessage('stateTemplate', '');
			}

			const panelSettings = {
				manager: this,
				container: this._container,
				enableCancellation: BX.prop.getBoolean(this._settings, 'enableCancellation', false),
			};

			if (Type.isStringFilled(title))
			{
				panelSettings.title = title;
			}

			if (Type.isStringFilled(stateTemplate))
			{
				panelSettings.stateTemplate = stateTemplate;
			}

			this._panel = ProcessPanel.create(
				this._id,
				panelSettings,
			);
		}
		this._panel.layout();
		this._hasLayout = true;
	}

	clearLayout()
	{
		if (!this._hasLayout)
		{
			return;
		}

		this._panel.clearLayout();
		this._hasLayout = false;
	}

	getPanel()
	{
		return this._panel;
	}

	setPanel(panel)
	{
		this._panel = panel;

		if (this._panel)
		{
			this._panel.setManager(this);
			this._hasLayout = this._panel.hasLayout();
		}
		else
		{
			this._hasLayout = false;
		}
	}

	refresh()
	{
		if (!this._hasLayout)
		{
			this.layout();
		}

		if (this._panel.isHidden())
		{
			this._panel.show();
		}
		this._panel.onManagerStateChange();
	}

	getState()
	{
		return this._state;
	}

	getProcessedItemCount()
	{
		return this._processedItemCount;
	}

	getTotalItemCount()
	{
		return this._totalItemCount;
	}

	getErrorCount()
	{
		return this._errors.length;
	}

	getErrors()
	{
		return this._errors;
	}

	run()
	{
		if (this._state === ProcessState.stopped)
		{
			this._state = ProcessState.intermediate;
		}
		this.startRequest();
	}

	runAfter(timeout)
	{
		this._runHandle = window.setTimeout(this.run.bind(this), timeout);
	}

	stop()
	{
		this._state = ProcessState.stopped;
		BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
	}

	reset()
	{
		if (this._runHandle > 0)
		{
			window.clearTimeout(this._runHandle);
			this._runHandle = 0;
		}

		if (this._panel && this._panel.isHidden())
		{
			this._panel.show();
		}

		this._processedItemCount = 0;
		this._totalItemCount = 0;
	}

	startRequest()
	{
		if (this._state === ProcessState.stopped)
		{
			return;
		}

		if (this._requestIsRunning)
		{
			return;
		}
		this._requestIsRunning = true;

		this._state = ProcessState.running;

		const data = {};
		if (this._serviceUrl === '')
		{
			if (this._params)
			{
				data.params = this._params;
			}

			Ajax.runAction(this._controllerActionName, { data })
				.then((result) => {
					this.onRequestSuccess(BX.prop.getObject(result, 'data', {}));
				}).catch((result) => {
					this.onRequestFailure(BX.prop.getObject(result, 'data', {}));
				});
		}
		else
		{
			if (this._actionName !== '')
			{
				data.ACTION = this._actionName;
			}

			if (this._params)
			{
				data.PARAMS = this._params;
			}
			data.sessid = BX.bitrix_sessid();

			Ajax(
				{
					url: this._serviceUrl,
					method: 'POST',
					dataType: 'json',
					data,
					onsuccess: this.onRequestSuccess.bind(this),
					onfailure: this.onRequestFailure.bind(this),
				},
			);
		}
	}

	onRequestSuccess(result)
	{
		this._requestIsRunning = false;
		if (this._state === ProcessState.stopped)
		{
			return;
		}

		if (this._serviceUrl === '')
		{
			const status = BX.prop.getString(result, 'status', '');

			if (status === 'ERROR')
			{
				this._state = ProcessState.error;
			}
			else if (status === 'COMPLETED')
			{
				this._state = ProcessState.completed;
			}

			if (this._state === ProcessState.error)
			{
				this.#setErrorsFromResponseData(result);
			}
			else
			{
				this._processedItemCount = BX.prop.getInteger(result, 'processedItems', 0);
				this._totalItemCount = BX.prop.getInteger(result, 'totalItems', 0);
				this._errors = BX.prop.getArray(result, 'errors', []);
			}
		}
		else
		{
			const status = BX.prop.getString(result, 'STATUS', '');

			if (status === 'ERROR')
			{
				this._state = ProcessState.error;
			}
			else if (status === 'COMPLETED')
			{
				this._state = ProcessState.completed;
			}

			if (this._state === ProcessState.error)
			{
				this.#setErrorsFromResponseData(result);
			}
			else
			{
				this._processedItemCount = BX.prop.getInteger(result, 'PROCESSED_ITEMS', 0);
				this._totalItemCount = BX.prop.getInteger(result, 'TOTAL_ITEMS', 0);
				this._errors = BX.prop.getArray(result, 'ERRORS', []);
			}
		}

		this.refresh();
		if (this._state === ProcessState.running)
		{
			window.setTimeout(this.startRequest.bind(this), this.getTimeout());
		}
		else if (this._state === ProcessState.completed
			&& BX.prop.getBoolean(this._settings, 'hideAfterComplete', true)
		)
		{
			this.hide();
		}

		BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
	}

	onRequestFailure(result)
	{
		this._requestIsRunning = false;

		this._state = ProcessState.error;
		this.#setErrorsFromResponseData(result);

		this.refresh();
		BX.onCustomEvent(this, 'ON_AUTORUN_PROCESS_STATE_CHANGE', [this]);
	}

	#setErrorsFromResponseData(responseData): void
	{
		const key = this._serviceUrl === '' ? 'errors' : 'ERRORS';

		this._errors = BX.prop.getArray(responseData, key, []);
		if (this._errors.length === 0)
		{
			this._errors.push({ message: this.getMessage('requestError') });
		}
	}
}
