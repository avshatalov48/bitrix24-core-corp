import { Loc, Tag, Dom, Reflection, ajax } from 'main.core';
import { DashboardParametersSelector } from 'biconnector.dashboard-parameters-selector';
import { ApacheSupersetAnalytics } from 'biconnector.apache-superset-analytics';
import { ButtonManager } from 'ui.buttons';

type Props = {
	nodeId: string,
	componentName: string,
	signedParameters: string,
	defaultValues: Object,
	scopeParamsMap: Object,
};

/**
 * @namespace BX.BIConnector
 */
class SupersetDashboardCreateManager
{
	#props: Props;
	#node: HTMLElement;
	#paramsSelector: DashboardParametersSelector;

	constructor(props: Props)
	{
		this.#props = props;
		this.#node = document.querySelector(`#${this.#props.nodeId}`);
		this.#render();
	}

	#render(): void
	{
		Dom.append(this.#getTopBlock(), this.#node);

		Dom.append(this.#getMainContent(), this.#node);

		this.#paramsSelector = new DashboardParametersSelector({
			scopes: new Set(),
			params: new Set(),
			scopeParamsMap: this.#props.scopeParamsMap,
		});
		Dom.append(this.#paramsSelector.getLayout(), this.#node);
	}

	#getMainContent(): HTMLElement
	{
		return Tag.render`
			<div>
				<div class="dashboard-params-title-container">
					<div class="dashboard-params-title">
						${Loc.getMessage('DASHBOARD_CREATE_NAME')}
					</div>
				</div>
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100 dashboard-title-wrapper">
					<input type="text" class="ui-ctl-element" id="dashboard-title-field" value="${this.#props.defaultValues.title}">
				</div>
			</div>
		`;
	}

	#getTopBlock(): HTMLElement
	{
		return Tag.render`
			<div class="dashboard-create-top-block">
				<div class="dashboard-create-top-block-image"></div>
				<div class="dashboard-create-top-block-text">
					${Loc.getMessage('DASHBOARD_CREATE_TOP_BLOCK')}
				</div>
			</div>
		`;
	}

	// noinspection JSUnusedGlobalSymbols
	onClickSave(): void
	{
		const selectorData = this.#paramsSelector.getValues();
		const titleField = document.querySelector('#dashboard-title-field');
		const saveData = {
			scopes: [...selectorData.scopes],
			params: [...selectorData.params],
			title: titleField.value,
		};
		const saveButton = ButtonManager.createFromNode(document.querySelector('#dashboard-button-save'));
		saveButton.setWaiting(true);

		ajax.runComponentAction(
			this.#props.componentName,
			'save',
			{
				mode: 'class',
				signedParameters: this.#props.signedParameters,
				data: {
					data: saveData,
				},
			},
		)
			.then((response) => {
				ApacheSupersetAnalytics.sendAnalytics('new', 'report_new', {
					type: 'custom',
					c_element: 'new_button',
				});
				parent.BX.Event.EventEmitter.emit('BIConnector.CreateForm:onDashboardCreated', {
					dashboard: response.data.dashboard,
				});
				BX.SidePanel.Instance.getTopSlider().close();
			})
			.catch((response) => {
				BX.UI.Notification.Center.notify({
					content: response.errors[0].message,
				});
				saveButton.setWaiting(false);
			});
	}
}

Reflection.namespace('BX.BIConnector').SupersetDashboardCreateManager = SupersetDashboardCreateManager;
