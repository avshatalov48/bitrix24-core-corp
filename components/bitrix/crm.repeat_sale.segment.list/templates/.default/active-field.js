import { ajax as Ajax, Dom, Loc, Reflection, Runtime, Tag, Text } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { UI } from 'ui.notification';

const namespace = Reflection.namespace('BX.Crm.RepeatSale.SegmentList');

export class ActiveField
{
	#id: string;
	#targetNode: string;
	#checked: boolean;
	#readOnly: boolean;
	#isFlowDisabled: boolean;

	constructor({ id, targetNodeId, checked, readOnly, isFlowDisabled })
	{
		this.#id = id;
		this.#targetNode = document.getElementById(targetNodeId);
		this.#checked = checked;
		this.#readOnly = readOnly;
		this.#isFlowDisabled = isFlowDisabled;
	}

	init(): void
	{
		void Runtime.loadExtension('ui.switcher').then((exports) => {
			const { Switcher } = exports;

			const switcher = new Switcher({
				checked: this.#checked,
				disabled: this.#readOnly,
				handlers: {
					checked: (event) => {
						event.stopPropagation();

						this.#showMessageBox(
							() => {
								this.#changeRepeatSaleSegmentActive(false);
							},
							() => {
								switcher.check(true, false);
							},
						);
					},
					unchecked: (event) => {
						event.stopPropagation();

						if (this.#isFlowDisabled)
						{
							this.#showAllFlowEnableMessageBox(
								() => {
									this.#changeRepeatSaleSegmentActive(true);
									this.#isFlowDisabled = false;
								},
								() => {
									switcher.check(false, false);
								},
							);
						}
						else
						{
							this.#changeRepeatSaleSegmentActive(true);
						}
					},
				},
			});

			Dom.clean(this.#targetNode);
			switcher.renderTo(this.#targetNode);
		});
	}

	#showMessageBox(onOk: () => void, onCancel: () => void): void
	{
		const popupContainer = Tag.render`
			<div class="crm-repeat-sale-segment-list-confirm-container">
				<div class="crm-repeat-sale-segment-list-confirm-message">
					${Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_MESSAGE')}
				</div>
			</div>
		`;

		MessageBox.show({
			modal: true,
			minHeight: 100,
			minWidth: 400,
			popupOptions: {
				content: popupContainer,
				closeIcon: false,
			},
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_CONFIRM_DIALOG_OK_BTN'),
			onOk: (messageBox: MessageBox) => {
				messageBox.close();
				onOk();
			},
			onCancel: (messageBox: MessageBox) => {
				onCancel();
				messageBox.close();
			},
		});
	}

	#showAllFlowEnableMessageBox(onOk: () => void, onCancel: () => void): void
	{
		const popupContainer = Tag.render`
			<div class="crm-repeat-sale-segment-list-confirm-container">
				<div class="crm-repeat-sale-segment-list-confirm-message">
					${Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_MESSAGE')}
				</div>
			</div>
		`;

		MessageBox.show({
			modal: true,
			minHeight: 100,
			minWidth: 400,
			popupOptions: {
				content: popupContainer,
				closeIcon: false,
			},
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_LIST_COLUMN_IS_ENABLED_RS_CONFIRM_DIALOG_OK_BTN'),
			onOk: (messageBox: MessageBox) => {
				messageBox.close();
				onOk();
			},
			onCancel: (messageBox: MessageBox) => {
				onCancel();
				messageBox.close();
			},
		});
	}

	#changeRepeatSaleSegmentActive(isEnabled: boolean): void
	{
		Runtime.throttle(() => {
			Ajax
				.runAction('crm.repeatsale.segment.active', {
					json: {
						id: this.#id,
						isEnabled: isEnabled ? 'Y' : 'N',
					},
					analyticsLabel: {
						tool: 'crm',
						category: 'editor',
						event: 'scenario_enable',
						c_element: `${isEnabled ? 'on' : 'off'}`,
					},
				})
				.catch((response) => {
					UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
						autoHideDelay: 6000,
					});

					throw response;
				})
			;
		}, 100)();
	}
}

namespace.ActiveField = ActiveField;
