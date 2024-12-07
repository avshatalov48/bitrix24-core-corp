/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */
import { Builder } from 'crm.integration.analytics';
import { ajax as Ajax, Text, Type } from 'main.core';
import { sendData } from 'ui.analytics';
import { MessageBox } from 'ui.dialogs.messagebox';
import { ProcessRegistry } from '../process/process-registry';
import { ProcessState } from '../process/process-state';
import { Processor } from '../process/processor';
import { SummaryPanel } from '../process/summary-panel';

/**
 * @memberOf BX.Crm.Autorun
 * @alias BX.Crm.BatchConversionManager
 */
export class BatchConversionManager
{
	_id = '';
	_settings = {};

	_gridId = '';
	_config = null;
	_entityIds = null;
	_enableUserFieldCheck = true;
	_enableConfigCheck = true;

	_filter = null;

	_serviceUrl = '';
	_containerId = '';
	_errors = null;

	_progress = null;
	_hasLayout = false;

	_succeededItemCount = 0;
	_failedItemCount = 0;
	_isRunning = false;
	_messages = null;

	_progressChangeHandler = this.onProgress.bind(this);
	_documentUnloadHandler = this.onDocumentUnload.bind(this);

	static messages = {};

	static items = {};

	static getItem(id): ?BatchConversionManager
	{
		return BX.prop.get(BatchConversionManager.items, id, null);
	}

	static create(id, settings): BatchConversionManager
	{
		const self = new BatchConversionManager(id, settings);
		BatchConversionManager.items[self.getId()] = self;

		return self;
	}

	constructor(id, settings)
	{
		this._id = Type.isStringFilled(id) ? id : `crm_batch_conversion_mgr_${Math.random().toString().slice(2)}`;
		this._settings = settings || {};

		this._gridId = BX.prop.getString(this._settings, 'gridId', this._id);
		this._config = BX.prop.getObject(this._settings, 'config', {});
		this._entityIds = BX.prop.getArray(this._settings, 'entityIds', []);

		this._serviceUrl = BX.prop.getString(this._settings, 'serviceUrl', '');
		if (this._serviceUrl === '')
		{
			throw "BX.Crm.BatchConversionManager. Could not find 'serviceUrl' parameter in settings.";
		}

		this._containerId = BX.prop.getString(this._settings, 'container', '');
		if (this._containerId === '')
		{
			throw 'BX.Crm.BatchConversionManager: Could not find container.';
		}

		// region progress

		const processorSettings = {
			serviceUrl: this._serviceUrl,
			actionName: 'PROCESS_BATCH_CONVERSION',
			container: this._containerId,
			enableCancellation: true,
			title: this.getMessage('title'),
			enableLayout: false,
		};
		const stateTemplate = BX.prop.getString(this._settings, 'stateTemplate', null);
		if (Type.isStringFilled(stateTemplate))
		{
			processorSettings.stateTemplate = stateTemplate;
		}

		this._progress = Processor.create(
			this._id,
			processorSettings,
		);
		// region
		this._errors = [];
	}

	isRunning(): boolean
	{
		return this._isRunning;
	}

	resetEntityIds(): void
	{
		this._entityIds = [];
	}

	getId()
	{
		return this._id;
	}

	getConfig()
	{
		return this._config;
	}

	setConfig(config)
	{
		this._config = Type.isPlainObject(config) ? config : {};
	}

	getEntityIds()
	{
		return this._entityIds;
	}

	setEntityIds(entityIds)
	{
		this._entityIds = Type.isArray(entityIds) ? entityIds : [];
	}

	getFilter()
	{
		return this._filter;
	}

	setFilter(filter)
	{
		this._filter = Type.isPlainObject(filter) ? filter : null;
	}

	isUserFieldCheckEnabled()
	{
		return this._enableUserFieldCheck;
	}

	enableUserFieldCheck(enableUserFieldCheck)
	{
		this._enableUserFieldCheck = enableUserFieldCheck;
	}

	isConfigCheckEnabled()
	{
		return this._enableConfigCheck;
	}

	enableConfigCheck(enableConfigCheck)
	{
		this._enableConfigCheck = enableConfigCheck;
	}

	getMessage(name)
	{
		if (this._messages && BX.prop.getString(this._messages, name, null))
		{
			return BX.prop.getString(this._messages, name, name);
		}

		const messages = BX.prop.getObject(this._settings, 'messages', BatchConversionManager.messages);

		return BX.prop.getString(messages, name, name);
	}

	layout()
	{
		if (this._hasLayout)
		{
			return;
		}

		this._progress.layout();
		this._hasLayout = true;
	}

	clearLayout()
	{
		if (!this._hasLayout)
		{
			return;
		}

		this._progress.clearLayout();
		this._hasLayout = false;
	}

	getState()
	{
		return this._progress.getState();
	}

	getProcessedItemCount()
	{
		return this._progress.getProcessedItemCount();
	}

	getTotalItemCount()
	{
		return this._progress.getTotalItemCount();
	}

	execute()
	{
		const params = {
			GRID_ID: this._gridId,
			CONFIG: this._config,
			ENABLE_CONFIG_CHECK: this._enableConfigCheck ? 'Y' : 'N',
			ENABLE_USER_FIELD_CHECK: this._enableUserFieldCheck ? 'Y' : 'N',
		};

		if (this._filter === null)
		{
			params.IDS = this._entityIds;
		}
		else
		{
			params.FILTER = this._filter;
		}

		const data = {
			ACTION: 'PREPARE_BATCH_CONVERSION',
			PARAMS: params,
			sessid: BX.bitrix_sessid(),
		};

		this.#sendAnalyticsData('attempt');

		Ajax(
			{
				url: this._serviceUrl,
				method: 'POST',
				dataType: 'json',
				data,
				onsuccess: this.onPrepare.bind(this),
			},
		);
	}

	#sendAnalyticsData(status): void
	{
		if (!BX.CrmEntityType.isDefined(this._settings.entityTypeId))
		{
			return;
		}

		for (const dstEntityTypeName of Object.keys(this._config))
		{
			if (this._config[dstEntityTypeName].active !== 'Y')
			{
				continue;
			}

			const event = Builder.Entity.ConvertBatchEvent.createDefault(
				this._settings.entityTypeId,
				BX.CrmEntityType.resolveId(dstEntityTypeName),
			);

			if (Type.isPlainObject(this._settings.analytics))
			{
				if (Type.isStringFilled(this._settings.analytics.c_section))
				{
					event.setSection(this._settings.analytics.c_section);
				}

				if (Type.isStringFilled(this._settings.analytics.c_sub_section))
				{
					event.setSubSection(this._settings.analytics.c_sub_section);
				}

				if (Type.isStringFilled(this._settings.analytics.c_element))
				{
					event.setElement(this._settings.analytics.c_element);
				}
			}

			event.setStatus(status);

			sendData(event.buildData());
		}
	}

	onPrepare(result)
	{
		const data = Type.isPlainObject(result.DATA) ? result.DATA : {};

		const status = BX.prop.getString(data, 'STATUS', '');
		this._config = BX.prop.getObject(data, 'CONFIG', {});

		if (data.hasOwnProperty('messages') && Type.isPlainObject(data.messages))
		{
			this._messages = data.messages;
			if (!BX.CrmLeadConverter.messages)
			{
				BX.CrmLeadConverter.messages = {};
			}
			BX.CrmLeadConverter.messages = Object.assign(BX.CrmLeadConverter.messages, data.messages);
		}

		if (status === 'ERROR')
		{
			this.#sendAnalyticsData('error');

			const errors = BX.prop.getArray(data, 'ERRORS', []);

			MessageBox.alert(
				errors.map((error) => Text.encode(error)).join('<br/>'),
				this.getMessage('title'),
			);

			return;
		}

		if (status === 'REQUIRES_SYNCHRONIZATION')
		{
			const syncEditor = BX.CrmLeadConverter.getCurrent().createSynchronizationEditor(
				this._id,
				this._config,
				BX.prop.getArray(data, 'FIELD_NAMES', []),
			);
			syncEditor.addClosingListener(this.onSynchronizationEditorClose.bind(this));
			syncEditor.show();

			return;
		}

		this.layout();
		this.run();
	}

	run()
	{
		if (this._isRunning)
		{
			return;
		}
		this._isRunning = true;
		ProcessRegistry.Instance.registerProcessRun(this._gridId);

		this._progress.setParams({ GRID_ID: this._gridId, CONFIG: this._config });
		this._progress.run();

		BX.addCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);
		BX.bind(window, 'beforeunload', this._documentUnloadHandler);
	}

	stop()
	{
		if (!this._isRunning)
		{
			return;
		}
		this._isRunning = false;

		this.#sendAnalyticsData('cancel');

		Ajax(
			{
				url: this._serviceUrl,
				method: 'POST',
				dataType: 'json',
				data: { ACTION: 'STOP_BATCH_CONVERSION', PARAMS: { GRID_ID: this._gridId } },
				onsuccess: this.onStop.bind(this),
			},
		);
	}

	onStop(result)
	{
		this.reset();

		window.setTimeout(
			() => {
				BX.onCustomEvent(
					window,
					'BX.Crm.BatchConversionManager:onStop',
					[this],
				);
			},
			300,
		);
	}

	reset()
	{
		this._progress.reset();

		BX.removeCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);
		BX.unbind(window, 'beforeunload', this._documentUnloadHandler);

		if ((this._succeededItemCount > 0 || this._failedItemCount > 0) && BX.getClass('BX.Main.gridManager'))
		{
			BX.Main.gridManager.reload(this._gridId);
		}

		this._succeededItemCount = this._failedItemCount = 0;
		this._isRunning = false;
		ProcessRegistry.Instance.registerProcessStop(this._gridId);

		if (this._hasLayout)
		{
			window.setTimeout(this.clearLayout.bind(this), 100);
		}

		this._errors = [];
	}

	getSucceededItemCount()
	{
		return this._succeededItemCount;
	}

	getFailedItemCount()
	{
		return this._failedItemCount;
	}

	getErrors()
	{
		return this._errors;
	}

	/**
	 * Triggers a browser native warning when a user tries to close the tab that data may not be saved yet
	 */
	onDocumentUnload(event): string
	{
		// recommended MDN way
		event.preventDefault();

		// compatibility with older browsers
		// eslint-disable-next-line no-param-reassign
		event.returnValue = true;
	}

	onSynchronizationEditorClose(sender, args)
	{
		if (BX.prop.getBoolean(args, 'isCanceled', false))
		{
			this.#sendAnalyticsData('cancel');

			this.clearLayout();

			return;
		}

		this._config = sender.getConfig();
		this.run();
	}

	onProgress(sender)
	{
		const state = this._progress.getState();
		if (state === ProcessState.stopped)
		{
			this.stop();

			return;
		}

		const errors = this._progress.getErrors();
		if (errors.length === 0)
		{
			this._succeededItemCount++;
		}
		else
		{
			if (this._errors)
			{
				this._errors = [...this._errors, ...errors];
			}
			else
			{
				this._errors = errors;
			}

			this._failedItemCount++;
			this.#sendAnalyticsData('error');
		}

		if (state === ProcessState.completed)
		{
			this.#sendAnalyticsData('success');

			SummaryPanel.create(
				this._id,
				{
					container: this._containerId,
					data:
					{
						succeededCount: this.getSucceededItemCount(),
						failedCount: this.getFailedItemCount(),
						errors: this.getErrors(),
					},
					messages: BX.prop.getObject(this._settings, 'messages', null),
					numberSubstitution: '#number_leads#',
				},
			).layout();

			this.reset();

			window.setTimeout(
				() => {
					BX.onCustomEvent(
						window,
						'BX.Crm.BatchConversionManager:onProcessComplete',
						[this],
					);
				},
				300,
			);
		}
	}
}
