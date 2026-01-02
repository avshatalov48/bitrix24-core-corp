import { ajax as Ajax, Dom, Loc, Reflection, Runtime, Tag, Text } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { UI } from 'ui.notification';

const namespace = Reflection.namespace('BX.Crm.Copilot.CallAssessmentList');

export class ActiveField
{
	#id: string;
	#targetNode: string;
	#checked: boolean;
	#readOnly: boolean;

	constructor({ id, targetNodeId, checked, readOnly })
	{
		this.#id = id;
		this.#targetNode = document.getElementById(targetNodeId);
		this.#checked = checked;
		this.#readOnly = readOnly;
	}

	init(): void
	{
		void Runtime.loadExtension('ui.switcher').then((exports) => {
			const { Switcher } = exports;

			this.switcher = new Switcher({
				checked: this.#checked,
				disabled: this.#readOnly,
				handlers: {
					checked: (event: PointerEvent) => {
						event.stopPropagation();

						const popupContainer = Tag.render`
							<div class="crm-copilot-call-assessment-list-confirm-container">
								<div class="crm-copilot-call-assessment-list-confirm-title">
									${Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_ACTIVITY_CONFIRM_DIALOG_TITLE')}
								</div>
								<div class="crm-copilot-call-assessment-list-confirm-message"></div>
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
							okCaption: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_LIST_COLUMN_ACTIVITY_CONFIRM_DIALOG_OK_BTN'),
							onOk: (messageBox) => {
								messageBox.close();
								this.#changeCallAssessmentActive(false);
							},
							onCancel: (messageBox) => {
								this.switcher.check(true, false);
								messageBox.close();
							},
						});
					},
					unchecked: (event) => {
						event.stopPropagation();
						this.#changeCallAssessmentActive(true);
					},
				},
			});

			Dom.clean(this.#targetNode);
			this.switcher.renderTo(this.#targetNode);
		});
	}

	#changeCallAssessmentActive(isEnabled: boolean): void
	{
		Runtime.throttle(() => {
			Ajax
				.runAction('crm.copilot.callassessment.active', {
					data: {
						id: this.#id,
						isEnabled: isEnabled ? 'Y' : 'N',
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
