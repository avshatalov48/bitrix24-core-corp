import {ajax as Ajax, Loc, Text, Tag, Dom, Type} from 'main.core';
import { PopupManager } from 'main.popup';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import 'ui.switcher';
import {PhoneVerify} from 'bitrix24.phoneverify';

const PHONE_VERIFY_FORM_ENTITY = 'crm_webform';

class WebFormList
{
	#groupAction: ?string = null;

	constructor()
	{

	}

	init(params)
	{
		this.reloadGridTimeoutId = 0;
		this.gridId = params.gridId;
		this.gridNode = document.getElementById(this.gridId);

		const hideDescBtnNode = BX('CRM_LIST_DESC_BTN_HIDE');
		if (hideDescBtnNode)
		{
			BX.bind(hideDescBtnNode, 'click', function () {
				BX.addClass(BX('CRM_LIST_DESC_CONT'), 'crm-webform-list-info-hide');
				BX.userOptions.delay = 0;
				BX.userOptions.save('crm', 'webform_list_view', 'hide-desc', 'Y');
			});
		}

		const notifyBtnNode = BX('CRM_LIST_WEBFORM_NOTIFY_BTN_HIDE');
		if (notifyBtnNode)
		{
			BX.bind(notifyBtnNode, 'click', function () {
				BX.addClass(BX('CRM_LIST_DESC_CONT'), 'crm-webform-list-info-hide');
				BX.userOptions.delay = 0;
				BX.userOptions.save('crm', 'notify_webform', 'ru_fz_152', 'Y');
			});
		}

		this.#renderGridRows();
		BX.addCustomEvent('Grid::updated', () => {
			this.#renderGridRows();
		});

		return this;
	}

	#renderGridRows()
	{
		this.#renderEntities();
		this.#renderQrButtons();
		this.#renderActiveSwitchers();
	}

	#renderQrButtons()
	{
		const container = this.#getGridContainer();
		if (!container)
		{
			return;
		}

		const switcherAttr = 'data-crm-form-qr';
		let switchers = container.querySelectorAll('[' + switcherAttr + ']');
		switchers = Array.prototype.slice.call(switchers);
		switchers.forEach(node => {
			if (node.querySelector('.crm-webform-qr-btn'))
			{
				return;
			}

			const data = JSON.parse(node.getAttribute(switcherAttr));
			if (data.needVerify)
			{
				const onClickVerify = () => {
					this.#verifyPhone(
						PHONE_VERIFY_FORM_ENTITY,
						data.id,
						() => {
							(new BX.Crm.Form.Qr({link: data.path})).show();
						},
					);
				}
				node.appendChild(Tag.render`
					<button
						type="button"
						class="crm-webform-qr-btn ui-btn ui-btn-xs ui-btn-light-border ui-btn-round ui-btn-no-caps ui-btn-icon-share"
						onclick="${onClickVerify}"
					>
						${Loc.getMessage('CRM_WEBFORM_QR_OPEN')}
					</button>
				`);
			}
			else
			{
				(new BX.Crm.Form.Qr({link: data.path})).renderTo(node);
			}
		});
	}

	#renderEntities()
	{
		const container = this.#getGridContainer();
		if (!container)
		{
			return;
		}

		const attr = 'data-crm-form-entities';
		let buttons = container.querySelectorAll('[' + attr + ']');
		buttons = Array.prototype.slice.call(buttons);
		buttons.forEach(node => {
			const data = JSON.parse(node.getAttribute(attr));
			const handler = (event: Event) => {
				event.stopPropagation();
				event.preventDefault();
				const id = 'crm-form-grid-entities-' + data.id;
				let popup = PopupManager.getPopupById(id);
				if (popup)
				{
					const hide = popup.getId() === id;
					popup.destroy();
					popup = null;
					if (hide)
					{
						return;
					}
				}

				const contentNode = Tag.render`<div class="crm-webform-list-entities"></div>`;
				data.counters.forEach(counter => {
					const counterHandler = event => {
						event.stopPropagation();
						event.preventDefault();
						BX.SidePanel.Instance.open(counter.LINK);
						return false;
					};
					const caption = Text.encode(counter.ENTITY_CAPTION);
					const value = Text.encode(counter.VALUE);
					const counterNode = !counter.LINK
						? Tag.render`
							<span 
								class="crm-webform-active-popup-item-date" 
								title="${caption}"
							>${caption}</span>
						`
						: Tag.render`
							<a
								href="${Text.encode(counter.LINK)}"
								onclick="${counterHandler}"
								class="crm-webform-active-popup-item-date"
								title="${caption}"
							>${caption}</a>
						`
					;

					contentNode.appendChild(Tag.render`
						<div class="crm-webform-list-active-popup-row">
							${counterNode}
							<span class="crm-webform-list-entity-counter">${value}</span>
						</div>						
					`);
				});

				let popupWidth = 160;

				popup = PopupManager.create({
					id,
					className: 'crm-webform-list-entities-popup',
					closeByEsc: true,
					autoHide: true,
					bindElement: event.target,
					content: contentNode,
					angle: {
						offset: (popupWidth / 2) - 16
					},
					offsetLeft: -(popupWidth / 2) + (event.target.offsetWidth / 2) + 40,
					animation: 'fading-slide',
					width: popupWidth,
					padding: 0,
				});
				popup.show();
			};

			node.addEventListener('click', handler);
		});
	}

	#renderActiveSwitchers()
	{
		const container = this.#getGridContainer();
		if (!container)
		{
			return;
		}

		const switcherAttr = 'data-crm-form-switcher';
		let switchers = container.querySelectorAll('[' + switcherAttr + ']');
		switchers = Array.prototype.slice.call(switchers);
		switchers.forEach(node => {
			node.innerHTML = '';
			const data = JSON.parse(node.getAttribute(switcherAttr));

			const nodeText = Tag.render`
				<div
					class="${data.active ? '' : 'crm-webform-list-text-gray'}"
				>${Text.encode(data.dateActiveShort)}</div>
			`;
			const switcher = new BX.UI.Switcher({
				id: 'crm-form-list-item-' + data.id,
				checked: data.active,
				color: 'green',
				handlers: {
					toggled: () => {
						this.activate(data.id, switcher.isChecked(), false, nodeText);
						switcher.isChecked()
							? Dom.removeClass(nodeText, 'crm-webform-list-text-gray')
							: Dom.addClass(nodeText, 'crm-webform-list-text-gray')
						;
					},
				},
			});
			switcher.renderTo(node);

			const handler = (event: Event) => {
				const id = 'crm-form-grid-active-' + data.id;
				let popup = PopupManager.getPopupById(id);
				if (popup)
				{
					const hide = popup.getId() === id;
					popup.destroy();
					popup = null;
					if (hide)
					{
						return;
					}
				}

				let popupWidth = 250;

				popup = PopupManager.create({
					id,
					className: 'crm-webform-list-active-popup',
					closeByEsc: true,
					autoHide: true,
					angle: {
						offset: (popupWidth / 2) - 16
					},
					offsetLeft: -(popupWidth / 2) + (event.target.offsetWidth / 2) + 40,
					animation: 'fading-slide',
					bindElement: event.target,
					width: popupWidth,
					padding: 0,
					content: Tag.render`
						<div class="crm-webform-list-active-popup-row">
							<div class="crm-webform-list-active-popup-item">
								<div class="crm-webform-active-popup-item-caption">${Text.encode(data.activatedBy.text)}</div>
								<div class="crm-webform-active-popup-item-date"
									title="${Text.encode(data.dateActiveFull)}"
								>${Text.encode(data.dateActiveFull)}</div>
							</div>
							<a 
								href="${Text.encode(data.activatedBy.path)}"
								onclick="BX.SidePanel.Instance.open('${Text.encode(data.activatedBy.path)}')"
								title="${Text.encode(data.activatedBy.name)}"
								class="ui-icon ui-icon-common-user crm-webform-active-popup-item-avatar ${Text.encode(data.activatedBy.iconClass)}"
							>
								<i style="background-image: url(${encodeURI(Text.encode(data.activatedBy.iconPath))});"></i>
							</a>
						</div>
					`,
				});
				popup.show();
			};

			node.appendChild(Tag.render`
				<div class="crm-webform-list-active-desc">
					${nodeText}
					<div>
						<a 
							class="crm-webform-list-active-more"
							onclick="${handler}"
						>${Loc.getMessage('CRM_WEBFORM_LIST_BTN_DETAILS')}</a>
					</div>
				</div>
			`);
		});
	}

	setGroupAction(code: string)
	{
		this.#groupAction = code;
	}

	runGroupAction()
	{
		switch (this.#groupAction)
		{
			case 'activate':
				this.activateList(true);
				return;
			case 'deactivate':
				this.activateList(false);
				return;
			case 'delete':
				this.removeList();
				return;
		}

		if (this.#groupAction)
		{
			throw new Error(`Wrong group action "${this.#groupAction}"`);
		}
	}

	showConfirm(code = 'delete')
	{
		code = code.toUpperCase();
		return new Promise((resolve, reject) => {
			MessageBox.show({
				message: Loc.getMessage('CRM_WEBFORM_LIST_' + code + '_CONFIRM'),
				modal: true,
				title: Loc.getMessage('CRM_WEBFORM_LIST_' + code + '_CONFIRM_TITLE'),
				buttons: MessageBoxButtons.OK_CANCEL,
				onOk: messageBox => {
					messageBox.close();
					resolve();
				},
				onCancel: messageBox => {
					messageBox.close();
					reject();
				},
			});
		});
	}

	#getGrid()
	{
		return BX.Main.gridManager.getInstanceById(this.gridId);
	}

	#getGridContainer()
	{
		const grid = this.#getGrid();
		if (grid)
		{
			return grid.getContainer();
		}
	}

	reloadGrid()
	{
		const grid = this.#getGrid();
		if (grid)
		{
			return grid.reload();
		}
	}

	showGridLoader()
	{
		const grid = this.#getGrid();
		if (grid)
		{
			grid.getLoader().show();
		}
	}

	hideGridLoader()
	{
		const grid = this.#getGrid();
		if (grid)
		{
			grid.getLoader().hide();
		}
	}

	showNotification(message)
	{
		BX.UI.Notification.Center.notify({
			content: message
		});
	}

	remove(id)
	{
		this.showConfirm('delete').then(() => {
			this.showGridLoader();
			Ajax.runAction('crm.form.delete', {
				json: {id},
			}).then(response => {
				if (response.data)
				{
					this.reloadGrid();
				}
				else
				{
					this.hideGridLoader();
					this.showNotification(Loc.getMessage('CRM_WEBFORM_LIST_DELETE_ERROR'));
				}
			}).catch(() => {
				this.hideGridLoader();
				this.showNotification(Loc.getMessage('CRM_WEBFORM_LIST_DELETE_ERROR'));
			});
		});
	}

	removeList()
	{
		this.showConfirm('delete').then(() => {
			const grid = this.#getGrid();
			if (grid)
			{
				this.#getGrid().removeSelected();
			}
		});
	}

	resetCounters(id)
	{
		this.showGridLoader();
		return Ajax.runAction('crm.form.resetCounters', {
			json: {id},
		})
			.then(() => this.reloadGrid())
			.catch(() =>  {
				this.checkOnWriteAccessError(result);
				this.hideGridLoader();
			})
		;
	}

	copy(id)
	{
		this.showGridLoader();
		return Ajax.runAction('crm.form.copy', {
			json: {id},
		})
			.then(() => this.reloadGrid())
			.catch(() =>  {
				this.checkOnWriteAccessError(result);
				this.hideGridLoader();
			});
	}

	showSiteCode(id, options = {}, needVerify: boolean = false)
	{
		if (needVerify)
		{
			this.#verifyPhone(
				PHONE_VERIFY_FORM_ENTITY,
				id,
				() => {
					BX.Crm.Form.Embed.openSlider(id, options);
				}
			);
		}
		else
		{
			BX.Crm.Form.Embed.openSlider(id, options);
		}
	}

	#verifyPhone(entityType: string, entityId: string, runOnVerified: function)
	{
		const
			sliderTitle = Loc.getMessage('CRM_WEBFORM_PHONE_VERIFY_CUSTOM_SLIDER_TITLE'),
			title = Loc.getMessage('CRM_WEBFORM_PHONE_VERIFY_CUSTOM_TITLE'),
			description = Loc.getMessage('CRM_WEBFORM_PHONE_VERIFY_CUSTOM_DESCRIPTION_V1');

		if (typeof PhoneVerify !== 'undefined')
		{
			PhoneVerify.getInstance()
				.setEntityType(entityType)
				.setEntityId(entityId)
				.startVerify({sliderTitle: sliderTitle, title: title, description: description})
				.then((verified) => {
					if (verified)
					{
						runOnVerified();
						this.reloadGrid();
					}
				});
		}
		else
		{
			runOnVerified();
		}
	}

	activateList(mode: boolean = true)
	{
			this.showGridLoader();
			const grid = this.#getGrid();
			if (!grid)
			{
				return;
			}

			const list = grid.getRows().getSelectedIds();
			Ajax.runAction('crm.form.activateList', {
				json: {
					list,
					mode,
				},
			})
				.then(() => this.reloadGrid())
				.catch(() =>  {
					this.checkOnWriteAccessError(result);
					this.hideGridLoader();
				})
			;
	}

	activate(id, mode, reloadGrid: boolean = true, nodeText: Element = null)
	{
		const switcher = BX.UI.Switcher.getById('crm-form-list-item-' + id);
		if (switcher)
		{
			switcher.setLoading(true);
			switcher.check(mode, false);
		}

		return Ajax.runAction('crm.form.activate', {
			json: {
				id: parseInt(id),
				mode,
			},
		}).then(() => {
			if (switcher)
			{
				nodeText.textContent = switcher.isChecked()
					? BX.date.format(BX.date.convertBitrixFormat(BX.message("FORMAT_DATE")))
					: Loc.getMessage('CRM_WEBFORM_LIST_NOT_ACTIVE')
				;
				switcher.setLoading(false)
			}

			if (reloadGrid)
			{
				this.reloadGrid();
			}
		}).catch((result) => {
			this.checkOnWriteAccessError(result);

			if (switcher)
			{
				switcher.setLoading(false);
				switcher.check(!switcher.isChecked(), false);
			}
		});
	}

	checkOnWriteAccessError(result)
	{
		const errors = result.errors;
		errors.forEach(error =>
		{
			if (parseInt(error.code) === 2)
			{
				this.showNotification(Loc.getMessage('CRM_WEBFORM_LIST_ITEM_WRITE_ACCESS_DENIED'));
			}

			if (error.code === 'ERROR_CODE_PHONE_NOT_VERIFIED')
			{
				this.showNotification(Loc.getMessage('CRM_WEBFORM_LIST_ITEM_PHONE_NOT_VERIFIED'));
			}
		});
	}
}
BX.Crm.WebFormList = new WebFormList();
