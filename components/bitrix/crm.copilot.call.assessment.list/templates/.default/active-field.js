import { ajax as Ajax, Dom, Reflection, Runtime, Text } from 'main.core';
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

			const switcher = new Switcher({
				checked: this.#checked,
				disabled: this.#readOnly,
				handlers: {
					checked: (event) => {
						event.stopPropagation();
						this.#changeCallAssessmentActive(false);
					},
					unchecked: (event) => {
						event.stopPropagation();
						this.#changeCallAssessmentActive(true);
					},
				},
			});

			Dom.clean(this.#targetNode);
			switcher.renderTo(this.#targetNode);
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
