import { Type, Tag, Cache, Dom, ajax as Ajax, Loc, BaseError, Text, Runtime } from 'main.core';
import { ButtonManager } from 'ui.buttons';
import Row from './row';
import type { Button } from 'ui.buttons';
import type { FormOptions } from './form-options';


export default class Form
{
	targetNode: HTMLElement = null;
	cache = new Cache.MemoryCache();
	saveButton: Button = null;
	cancelButton: Button = null;
	rows: Row[] = [];
	error: typeof(BX.UI.Alert) = null;
	userOptions: { [key: string]: any } = {};

	constructor(formOptions: FormOptions)
	{
		const options = Type.isPlainObject(formOptions) ? formOptions : {};

		this.targetNode = options.targetNode;
		this.userOptions = Type.isPlainObject(options.userOptions) ? options.userOptions : {};
		Dom.append(this.getContainer(), this.targetNode);

		if (Type.isElementNode(options.saveButtonNode))
		{
			this.saveButton = ButtonManager.createFromNode(options.saveButtonNode);
			this.saveButton.bindEvent('click', this.handleSaveButtonClick.bind(this));
		}

		if (Type.isElementNode(options.cancelButtonNode))
		{
			this.cancelButton = ButtonManager.createFromNode(options.cancelButtonNode);
			this.cancelButton.bindEvent('click', this.handleCancelButtonClick.bind(this));
		}

		if (Type.isArrayFilled(options.rows))
		{
			options.rows.forEach(row => {
				this.addRow(row);
			});

			this.addRows(Math.max(2, 5 - options.rows.length));
			this.getRows()[0].focus();
		}
		else
		{
			this.addRows();
		}

		Runtime.loadExtension('ui.hint').then(() => {
			const hint = BX.UI.Hint.createInstance();
			const node = hint.createNode(Loc.getMessage('INTRANET_INVITATION_GUEST_HINT'));

			const title = document.querySelector('#pagetitle') || this.getTitleContainer();
			Dom.append(node, title);
		});
	}

	getRows(): Row[]
	{
		return this.rows;
	}

	lock(): void
	{
		Dom.style(this.getContainer(), 'pointer-events', 'none');
	}

	unlock(): void
	{
		Dom.style(this.getContainer(), 'pointer-events', 'none');
	}

	submit(): Promise
	{
		let valid = true;
		const guests = [];
		let invalidRow = null;
		this.getRows().forEach((row: Row) => {

			if (!row.validate())
			{
				invalidRow = invalidRow || row;
				valid = false;
			}

			if (valid && !row.isEmpty())
			{
				guests.push({
					email: row.getEmail(),
					name: row.getName(),
					lastName: row.getLastName()
				});
			}
		});

		if (!valid)
		{
			return Promise.reject(new BaseError(
				Loc.getMessage('INTRANET_INVITATION_GUEST_WRONG_DATA'),
				'wrong_data',
				{ invalidRow }
			));
		}
		else if (!Type.isArrayFilled(guests))
		{
			return Promise.reject(new BaseError(
				Loc.getMessage('INTRANET_INVITATION_GUEST_EMPTY_DATA'),
				'empty_data',
				{ invalidRow: this.getRows()[0] }
			));
		}

		return new Promise((resolve, reject) => {
			return Ajax.runComponentAction(
				'bitrix:intranet.invitation.guest',
				'addGuests',
				{
					mode: 'class',
					json: {
						guests,
						userOptions: this.userOptions
					},
				}
			).then(
				response => {
					resolve(response);
				},
				reason => {
					const error =
						reason && Type.isArrayFilled(reason.errors)
							? reason.errors.map(error => Text.encode(error.message)).join('<br><br>')
							: 'Server Response Error'
					;

					reject(new BaseError(
						error,
						'wrong_response'
					));
				}
			);
		});
	}

	getSaveButton(): ?Button
	{
		return this.saveButton;
	}

	getCancelButton(): ?Button
	{
		return this.cancelButton;
	}

	getContainer(): HTMLElement
	{
		return this.cache.remember('container', () => {
			return Tag.render`
				<div class="invite-wrap">
					${this.getTitleContainer()}
					<div class="invite-content-container">
						${this.getRowsContainer()}
					</div>
					<div class="invite-form-buttons">
						<button 
							class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round"
							onclick="${this.handleAddMoreClick.bind(this)}">${
								Loc.getMessage('INTRANET_INVITATION_GUEST_ADD_MORE')
							}
						</button>
					</div>
				</div>
			`;
		});
	}

	getRowsContainer(): HTMLElement
	{
		return this.cache.remember('rows-container', () => {
			return Tag.render`
				<div class="invite-form-container"></div>
			`;
		});
	}

	getTitleContainer(): HTMLElement
	{
		return this.cache.remember('title-container', () => {
			return Tag.render`
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-message"></div>
					<div class="invite-title-text">${Loc.getMessage('INTRANET_INVITATION_GUEST_TITLE')}</div>
				</div>
			`;
		});
	}

	addRow(rowOptions): Row
	{
		const row = new Row(rowOptions);
		this.rows.push(row);
		Dom.append(row.getContainer(), this.getRowsContainer());

		return row;
	}

	addRows(numberOfRows: number = 5): void
	{
		Array(numberOfRows).fill().forEach((el, index) => {
			const row = this.addRow();
			if (index === 0)
			{
				row.focus();
			}
		});
	}

	removeRows(): void
	{
		this.getRows().forEach((row: Row) => {
			Dom.remove(row.getContainer());
		});

		this.rows = [];
	}

	showError(reason: string): void
	{
		const animate = this.error === null;

		this.hideError();

		this.error = new BX.UI.Alert({
			color: BX.UI.Alert.Color.DANGER,
			animated: animate,
			text: reason
		});

		Dom.prepend(this.error.getContainer(), this.getContainer());
	}

	hideError(): void
	{
		if (this.error !== null)
		{
			Dom.remove(this.error.getContainer());
			this.error = null;
		}
	}

	handleSaveButtonClick(): void
	{
		if (this.getSaveButton().isWaiting())
		{
			return;
		}

		this.getSaveButton().setWaiting();

		this.submit().then(response => {
			this.getSaveButton().setWaiting(false);

			this.hideError();
			this.removeRows();
			this.addRows();

			BX.SidePanel.Instance.postMessageAll(window, 'BX.Intranet.Invitation.Guest:onAdd', response.data);
			BX.SidePanel.Instance.close();
		}).catch((error: BaseError) => {
			this.getSaveButton().setWaiting(false);
			this.showError(error.getMessage());

			if (error.getCustomData() && error.getCustomData()['invalidRow'])
			{
				error.getCustomData()['invalidRow'].focus();
			}
		});
	}

	handleCancelButtonClick(): void
	{
		BX.SidePanel.Instance.close();
	}

	handleAddMoreClick(): void
	{
		const row = this.addRow();
		row.focus();
	}
}