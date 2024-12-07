import { EntityCloseEvent } from 'crm.integration.analytics';
import { Loc, Reflection, Type } from 'main.core';
import { QueueManager } from 'pull.queuemanager';
import { UI } from 'ui.notification';

const NAMESPACE = Reflection.namespace('BX.CRM.Kanban.Actions');

export default class SimpleAction
{
	#grid: BX.CRM.Kanban.Grid;
	#params: Object;
	#isShowNotify: boolean = true;
	#isApplyFilterAfterAction: boolean = false;
	#useIgnorePostfixForCode: boolean = false;
	#analyticsData: ?EntityCloseEvent = null;

	constructor(grid: BX.CRM.Kanban.Grid, params: Object)
	{
		this.#grid = grid;
		this.#params = params;
	}

	showNotify(value: boolean = true): SimpleAction
	{
		this.#isShowNotify = value;

		return this;
	}

	applyFilterAfterAction(value: boolean = false): SimpleAction
	{
		this.#isApplyFilterAfterAction = value;

		return this;
	}

	setIgnorePostfixForCode(value: boolean = true): SimpleAction
	{
		this.#useIgnorePostfixForCode = value;

		return this;
	}

	execute(): Promise
	{
		this.#prepareExecute();

		if (this.#params.action === 'status')
		{
			this.#prepareAnalyticsData();
			this.#grid.registerAnalyticsCloseEvent(
				this.#analyticsData,
				BX.Crm.Integration.Analytics.Dictionary.STATUS_ATTEMPT,
			);
		}

		return new Promise((resolve, reject) => {
			this.#grid.ajax(
				this.#params,
				(data) => this.#onSuccess(data, resolve),
				(error) => this.#onFailure(error, reject),
			);
		});
	}

	#prepareExecute(): void
	{
		if (this.#grid.isMultiSelectMode())
		{
			this.#grid.resetMultiSelectMode();
		}

		if (!Type.isStringFilled(this.#params.eventId) && QueueManager)
		{
			// eslint-disable-next-line no-param-reassign
			this.#params.eventId = QueueManager.registerRandomEventId();
		}
	}

	#onSuccess(data: Object, resolve: Function): void
	{
		if (!data || data.error)
		{
			this.#grid.registerAnalyticsCloseEvent(this.#analyticsData, BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR);
			this.#handleErrorOnSimpleAction(data, resolve);
		}
		else
		{
			this.#grid.registerAnalyticsCloseEvent(this.#analyticsData, BX.Crm.Integration.Analytics.Dictionary.STATUS_SUCCESS);
			this.#handleSuccessOnSimpleAction(data, resolve);
		}
		this.#analyticsData = null;
	}

	#handleErrorOnSimpleAction(data, callback): void
	{
		const grid = this.#grid;
		const gridData = grid.getData();

		const params = this.#params;
		if (params.action === 'status')
		{
			grid.stopActionPanel();
			grid.onApplyFilter();

			if (grid.getTypeInfoParam('showPersonalSetStatusNotCompletedText'))
			{
				let messageCode = (
					gridData.isDynamicEntity
						? 'CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_DYNAMIC_MSGVER_1'
						: null
				);

				if (!messageCode)
				{
					const codeVer = `CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_${gridData.entityType}`;
					const codeVer1 = `${codeVer}_MSGVER_1`;
					const codeVer2 = `${codeVer}_MSGVER_2`;
					messageCode = BX.Loc.hasMessage(codeVer2) ? codeVer2 : codeVer1;
				}

				BX.Kanban.Utils.showErrorDialog(Loc.getMessage(messageCode));

				callback(new Error(Loc.getMessage(messageCode)));
			}
			else
			{
				BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);

				callback(new Error(data.error));
			}
		}
		else
		{
			BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);

			callback(new Error(data.error));
		}
	}

	#handleSuccessOnSimpleAction(data: Object, callback: Function): void
	{
		const grid = this.#grid;
		const params = this.#params;

		if (this.#isApplyFilterAfterAction)
		{
			grid.onApplyFilter();
		}
		grid.stopActionPanel();

		if (this.#isShowNotify)
		{
			let code = grid.getData().entityType;
			if (code.startsWith('DYNAMIC'))
			{
				code = 'DYNAMIC';
			}

			// @todo replace to useIgnorePostfixForCode check later
			if (params.action === 'delete' && params.ignore === 'Y')
			{
				code = `${code}_IGNORE`;
			}
			else
			{
				code = `${code}_${params.action.toUpperCase()}`;
			}

			this.#notify(code);
		}

		callback(data);
	}

	#notify(code: string): void
	{
		// eslint-disable-next-line no-param-reassign
		code = this.#getPreparedNotifyCode(code);

		const content = this.#getPreparedNotifyContent(code);

		if (Type.isStringFilled(content))
		{
			UI.Notification.Center.notify({
				content,
			});
		}
	}

	#getPreparedNotifyCode(code: string): string
	{
		if (code === 'DEAL_CHANGECATEGORY')
		{
			// eslint-disable-next-line no-param-reassign
			code = 'DEAL_CHANGECATEGORY_LINK2';
		}
		else if (code === 'DYNAMIC_CHANGECATEGORY')
		{
			// eslint-disable-next-line no-param-reassign
			code = 'DYNAMIC_CHANGECATEGORY_LINK2';
		}

		// eslint-disable-next-line no-param-reassign
		code = `CRM_KANBAN_NOTIFY_${code}`;

		const msgVer1Codes = [
			'CRM_KANBAN_NOTIFY_LEAD_STATUS',
			'CRM_KANBAN_NOTIFY_DYNAMIC_STATUS',
			'CRM_KANBAN_NOTIFY_INVOICE_STATUS',
			'CRM_KANBAN_NOTIFY_QUOTE_DELETE',
			'CRM_KANBAN_NOTIFY_QUOTE_SETASSIGNED',
		];

		if (msgVer1Codes.includes(code))
		{
			// eslint-disable-next-line no-param-reassign
			code = `${code}_MSGVER_1`;
		}

		const msgVer2Codes = [
			'CRM_KANBAN_NOTIFY_QUOTE_STATUS',
		];

		if (msgVer2Codes.includes(code))
		{
			// eslint-disable-next-line no-param-reassign
			code = `${code}_MSGVER_2`;
		}

		return code;
	}

	#getPreparedNotifyContent(code: string): ?string
	{
		let content = Loc.getMessage(code);
		if (!Type.isStringFilled(content))
		{
			return null;
		}

		const params = this.#params;

		if (Type.isPlainObject(params))
		{
			Object.entries(params).forEach((entryData) => {
				content = content.replace(`#${entryData[0]}#`, entryData[1]);
			});
		}

		return content;
	}

	#onFailure(error: string, callback: Function): void
	{
		this.#grid.registerAnalyticsCloseEvent(this.#analyticsData, BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR);
		this.#analyticsData = null;

		BX.Kanban.Utils.showErrorDialog(`Error: ${error}`, true);

		callback(new Error(error));
	}

	#prepareAnalyticsData(): void
	{
		const [entityId] = this.#params.entity_id;
		const item = this.#grid.getItem(entityId);
		const targetColumn = this.#grid.getColumn(this.#params.status);

		const type = targetColumn ? targetColumn.getData().type : this.#params.type;
		this.#analyticsData = this.#grid.getDefaultAnalyticsCloseEvent(
			item,
			type,
			this.#params.entity_id.toString(),
		);

		this.#analyticsData.c_element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_WON_TOP_ACTIONS;

		if (type === 'LOOSE')
		{
			this.#analyticsData.c_element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_LOSE_TOP_ACTIONS;
		}
	}
}

NAMESPACE.SimpleAction = SimpleAction;
