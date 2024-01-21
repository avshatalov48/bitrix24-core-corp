import { Type, Dom, Event, ajax, Runtime } from 'main.core';

class DisplayAlertsSupport
{
	DisableAlert = null;

	isShowAlert = null;
	daysUntilDisable = null;
	showAlertUserOption = null;
	alertContainerSelector = null;
	alertContainersStack = [];

	canShowAlerts(): boolean
	{
		return this.isShowAlert === true
			&& this.DisableAlert
			&& Type.isString(this.alertContainerSelector)
			&& Type.isString(this.showAlertUserOption)
			&& Type.isInteger(this.daysUntilDisable)
			&& this.daysUntilDisable > 0
		;
	}

	renderAlerts(): void
	{
		if (!this.canShowAlerts())
		{
			return;
		}

		this.alertContainersStack.forEach((alertContainer) => {
			this.renderAlert(alertContainer);
		});

		this.alertContainersStack = [];
	}

	renderAlert(container)
	{
		if (container.innerHTML !== '')
		{
			return;
		}

		const closeBtnCallback = () => {
			this.isShowAlert = false;

			BX.userOptions.save('crm', this.showAlertUserOption, 'show', 'N');

			const alertContainers = document.querySelectorAll(`.${this.alertContainerSelector}`);
			alertContainers.forEach((alertContainer) => {
				alertContainer.remove();
			});
		};

		Dom.style(container, {
			background: 'white',
			padding: '10px',
			'margin-bottom': '-10px',
			'border-radius': '10px 10px 0 0',
		});

		(new this.DisableAlert({
			alertContainer: container,
			daysUntilDisable: this.daysUntilDisable,
			closeBtnCallback,
		})).render();
	}
}

const isCrm = window.location.pathname.includes('/crm/');
if (!isCrm)
{
	const alertSupport = new DisplayAlertsSupport({});

	Event.EventEmitter.subscribe('crm:disableLFAlertContainerRendered', (event) => {
		const alertContainer = event.data.container;

		if (alertSupport.canShowAlerts())
		{
			alertSupport.renderAlert(alertContainer);
		}
		else
		{
			alertSupport.alertContainersStack.push(alertContainer);
		}
	});

	Runtime.loadExtension('crm.livefeed.disable-alert')
		.then((exports) => {
			if (!exports.DisableAlert)
			{
				alertSupport.isShowAlert = false;

				return;
			}

			alertSupport.DisableAlert = exports.DisableAlert;

			ajax.runAction('crm.controller.integration.socialnetwork.livefeed.getDisablingInfo')
				.then((response) => {
					alertSupport.isShowAlert = response.data.isShowAlert;
					alertSupport.daysUntilDisable = response.data.daysUntilDisable;
					alertSupport.alertContainerSelector = response.data.alertContainerSelector;
					alertSupport.showAlertUserOption = response.data.showAlertUserOption;

					alertSupport.renderAlerts();
				})
				.catch((error) => {
					alertSupport.isShowAlert = false;
				})
			;
		})
		.catch((error) => {
			alertSupport.isShowAlert = false;
		})
	;
}
