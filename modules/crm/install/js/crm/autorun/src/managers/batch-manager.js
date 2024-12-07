/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private,no-underscore-dangle,no-throw-literal */
import { ajax as Ajax, Type } from 'main.core';
import { ProcessRegistry } from '../process/process-registry';
import { ProcessState } from '../process/process-state';
import { Processor } from '../process/processor';
import { SummaryPanel } from '../process/summary-panel';

/**
 * @abstract
 */
export class BatchManager
{
	_id = '';
	_settings = {};

	_gridId = '';
	_entityTypeId = BX.CrmEntityType.enumeration.undefined;
	_entityIds = null;
	_filter = null;
	_operationHash = '';

	_containerId = '';
	_errors = null;

	_progress = null;
	_hasLayout = false;

	_isRunning = false;

	_progressChangeHandler = this.onProgress.bind(this);
	_documentUnloadHandler = this.onDocumentUnload.bind(this);

	_summaryLayoutHandler = this.onSummaryLayout.bind(this);
	_summaryClearLayoutHandler = this.onSummaryClearLayout.bind(this);

	static messages = {};

	constructor(id, settings)
	{
		this._id = Type.isStringFilled(id) ? id : `${this.getIdPrefix()}_${Math.random().toString().slice(2)}`;
		this._settings = settings || {};

		this._gridId = BX.prop.getString(this._settings, 'gridId', this._id);
		this._entityTypeId = BX.prop.getInteger(
			this._settings,
			'entityTypeId',
			BX.CrmEntityType.enumeration.undefined,
		);

		this._containerId = BX.prop.getString(this._settings, 'container', '');
		if (this._containerId === '')
		{
			throw `${this.getEventNamespace()}: Could not find container.`;
		}

		// region progress
		this._progress = Processor.create(
			`${this.getIdPrefix()}_${this._id}`,
			{
				controllerActionName: this.getProcessActionName(),
				container: this._containerId,
				enableCancellation: true,
				title: this.getMessage('title'),
				timeout: 1000,
				stateTemplate: BX.prop.getString(this._settings, 'stateTemplate', null),
				enableLayout: false,
			},
		);
		// region
		this._errors = [];
	}

	getEventNamespace(): string
	{
		return `BX.Crm.${this.getEventNamespacePostfix()}`;
	}

	/**
	 * @abstract
	 * @protected
	 */
	getEventNamespacePostfix(): string
	{
		this.#throwNotImplementedError();
	}

	/**
	 * @abstract
	 * @protected
	 */
	getIdPrefix(): string
	{
		this.#throwNotImplementedError();
	}

	/**
	 * @abstract
	 * @protected
	 */
	getProcessActionName(): string
	{
		this.#throwNotImplementedError();
	}

	/**
	 * @abstract
	 * @protected
	 */
	getPrepareActionName(): string
	{
		this.#throwNotImplementedError();
	}

	/**
	 * @abstract
	 * @protected
	 */
	getCancelActionName(): string
	{
		this.#throwNotImplementedError();
	}

	#throwNotImplementedError(): void
	{
		throw new Error('not implemented');
	}

	getId(): string
	{
		return this._id;
	}

	getEntityIds(): null | number[]
	{
		return this._entityIds;
	}

	setEntityIds(entityIds: number[]): void
	{
		this._entityIds = Type.isArray(entityIds) ? entityIds : [];
	}

	resetEntityIds(): void
	{
		this._entityIds = [];
	}

	getFilter(): ?Object
	{
		return this._filter;
	}

	setFilter(filter): void
	{
		this._filter = Type.isPlainObject(filter) ? filter : null;
	}

	getMessage(name: string, defaultValue: ?string = null): string
	{
		const messages = BX.prop.getObject(
			this._settings,
			'messages',
			this.getDefaultMessages(),
		);

		return BX.prop.getString(messages, name, defaultValue ?? name);
	}

	/**
	 * @protected
	 */
	getDefaultMessages(): {[messageKey: string]: string}
	{
		// kinda 'late static binding'
		return this.constructor.messages;
	}

	scrollInToView(): void
	{
		if (this._progress)
		{
			this._progress.scrollInToView();
			this.refreshGridHeader();
		}
	}

	refreshGridHeader(): void
	{
		window.requestAnimationFrame(
			() => {
				const grid = BX.Main.gridManager.getById(this._gridId);
				if (grid && grid.instance && grid.instance.pinHeader)
				{
					grid.instance.pinHeader.refreshRect();
					grid.instance.pinHeader._onScroll();
				}
			},
		);
	}

	layout(): void
	{
		if (this._hasLayout)
		{
			return;
		}

		this._progress.layout();
		this._hasLayout = true;
	}

	clearLayout(): void
	{
		if (!this._hasLayout)
		{
			return;
		}

		this._progress.clearLayout();
		this._hasLayout = false;
	}

	getState(): number
	{
		return this._progress.getState();
	}

	getProcessedItemCount(): number
	{
		return this._progress.getProcessedItemCount();
	}

	getTotalItemCount(): number
	{
		return this._progress.getTotalItemCount();
	}

	execute(): void
	{
		this.layout();
		this.run();

		window.setTimeout(this.scrollInToView.bind(this), 100);
	}

	isRunning(): boolean
	{
		return this._isRunning;
	}

	run(): void
	{
		if (this._isRunning)
		{
			return;
		}
		this._isRunning = true;
		ProcessRegistry.Instance.registerProcessRun(this._gridId);

		BX.bind(window, 'beforeunload', this._documentUnloadHandler);
		this.enableGridFilter(false);

		Ajax.runAction(
			this.getPrepareActionName(),
			{
				data: {
					params: this.getPrepareActionParams(),
				},
			},
		).then((response) => {
			const hash = BX.prop.getString(
				BX.prop.getObject(response, 'data', {}),
				'hash',
				'',
			);

			if (hash === '')
			{
				this.reset();

				return;
			}

			this._operationHash = hash;
			this._progress.setParams({ hash: this._operationHash });
			this._progress.run();

			BX.addCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);
		}).catch(() => {
			this.reset();
		});
	}

	getPrepareActionParams(): Object
	{
		const params = {
			gridId: this._gridId,
			entityTypeId: this._entityTypeId,
			extras: BX.prop.getObject(this._settings, 'extras', {}),
		};

		if (Type.isArray(this._entityIds) && this._entityIds.length > 0)
		{
			params.entityIds = this._entityIds;
		}

		return params;
	}

	stop()
	{
		if (!this._isRunning)
		{
			return;
		}
		this._isRunning = false;

		void Ajax.runAction(
			this.getCancelActionName(),
			{ data: { params: { hash: this._operationHash } } },
		);

		this.reset();
	}

	reset()
	{
		BX.unbind(window, 'beforeunload', this._documentUnloadHandler);
		BX.removeCustomEvent(this._progress, 'ON_AUTORUN_PROCESS_STATE_CHANGE', this._progressChangeHandler);

		this._isRunning = false;
		ProcessRegistry.Instance.registerProcessStop(this._gridId);
		this._operationHash = '';
		this._errors = [];

		const enableGridReload = this._progress.getProcessedItemCount() > 0;
		this._progress.reset();

		if (this._hasLayout)
		{
			window.setTimeout(this.clearLayout.bind(this), 100);
		}

		this.enableGridFilter(true);
		if (enableGridReload)
		{
			BX.Main.gridManager.reload(this._gridId);
		}
	}

	enableGridFilter(enable)
	{
		const container = this._gridId === '' ? null : BX(`${this._gridId}_search_container`);
		if (!container)
		{
			return;
		}

		if (enable)
		{
			BX.removeClass(container, 'main-ui-disable');
		}
		else
		{
			BX.addClass(container, 'main-ui-disable');
		}
	}

	getErrorCount(): number
	{
		return this._errors ? this._errors.length : 0;
	}

	getErrors(): Array
	{
		return this._errors ?? [];
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

	onProgress(sender): void
	{
		const state = this._progress.getState();
		if (state === ProcessState.stopped)
		{
			this.stop();

			return;
		}

		const errors = this._progress.getErrors();
		if (errors.length > 0)
		{
			if (this._errors)
			{
				this._errors = [...this._errors, ...errors];
			}
			else
			{
				this._errors = errors;
			}
		}

		if (state === ProcessState.completed || state === ProcessState.error)
		{
			const failed = this.getErrorCount();
			const succeeded = this.getProcessedItemCount() - failed;

			BX.addCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onLayout', this._summaryLayoutHandler);
			SummaryPanel.create(
				this._id,
				{
					container: this._containerId,
					data: { succeededCount: succeeded, failedCount: failed, errors: this.getErrors() },
					messages: BX.prop.getObject(this._settings, 'messages', this.constructor.messages),
					numberSubstitution: '#number#',
					displayTimeout: 1500,
				},
			).layout();
			this.reset();

			window.setTimeout(
				() => {
					BX.onCustomEvent(
						window,
						`${this.getEventNamespace()}:onProcessComplete`,
						[this],
					);
				},
				300,
			);
		}
	}

	onSummaryLayout()
	{
		this.refreshGridHeader();
		BX.removeCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onLayout', this._summaryLayoutHandler);
		BX.addCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onClearLayout', this._summaryClearLayoutHandler);
	}

	onSummaryClearLayout()
	{
		this.refreshGridHeader();
		BX.removeCustomEvent(window, 'BX.Crm.ProcessSummaryPanel:onClearLayout', this._summaryClearLayoutHandler);
	}
}
