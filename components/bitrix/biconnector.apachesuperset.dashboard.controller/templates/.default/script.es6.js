import { ajax as Ajax, Reflection, Text, Tag, Loc } from 'main.core';
import { Button } from 'ui.buttons';

const namespace = Reflection.namespace('BX.BIConnector');

type Props = {
	enableButtonSectionId: string,

	canEnable: boolean,
	enableDate: string,
};

class SupersetEnabler
{
	#enableButtonSection: HTMLElement;

	#enableButton: HTMLElement;

	constructor(props: Props)
	{
		this.#enableButtonSection = document.getElementById(props.enableButtonSectionId);
		if (!this.#enableButtonSection)
		{
			throw new Error('Enable button section not found');
		}

		this.#renderEnableSection(props.canEnable, props.enableDate);
	}

	#renderEnableSection(canEnable: boolean, enableDate?: string): void
	{
		this.#enableButton = this.#createEnableButton(!canEnable);

		const buttonWrapper = Tag.render`<div></div>`;
		this.#enableButton.renderTo(buttonWrapper);

		let descriptionBlock = null;
		if (!canEnable)
		{
			const description = Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_CONTROLLER_CREATE_SUPERSET_BUTTON_ENABLE_DATE', {
				'#ENABLE_TIME#': enableDate,
			});

			descriptionBlock = Tag.render`<div class="biconnector-create-superset-button-block-desc">${description}</div>`;
		}

		const content = Tag.render`
			<div class="biconnector-create-superset-button-block">
				${buttonWrapper}
				${descriptionBlock}
			</div>
		`;

		this.#enableButtonSection.append(content);
	}

	#createEnableButton(disabled: boolean): HTMLElement
	{
		return new Button({
			text: Loc.getMessage('BICONNECTOR_SUPERSET_DASHBOARD_CONTROLLER_CREATE_SUPERSET_BUTTON_MSGVER_1'),
			round: true,
			color: Button.Color.PRIMARY,
			onclick: () => {
				this.#enableSuperset();
			},
			disabled: disabled,
			className: disabled ? 'biconnector-create-superset-disabled-button biconnector-create-superset-button' : 'biconnector-create-superset-button',
		});
	}

	#enableSuperset(): void
	{
		this.#enableButton.setWaiting(true);

		Ajax.runAction('biconnector.superset.enable')
			.then(() => {
				window.location.reload();
			})
			.catch((response) => {
				BX.UI.Notification.Center.notify({
					content: Text.encode(response.errors[0].message),
				});

				this.#enableButton.setWaiting(false);
			});
	}
}

namespace.SupersetEnabler = SupersetEnabler;
