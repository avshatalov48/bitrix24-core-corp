import {Loc, ajax} from 'main.core';
import {Guide} from 'ui.tour';

type DashboardGridOptions = {
	bindElement: HTMLElement,
	article: string,
}

export class DashboardGrid
{
	static gridId = 'biconnector_dashboard_list';
	static componentName = 'bitrix:biconnector.dashboard.list';
	#options;
	#spotLight;
	#guide;

	constructor(options: DashboardGridOptions)
	{
		this.#options = options;
	}

	static deleteRow(id: number): void
	{
		const grid = BX.Main.gridManager.getInstanceById(DashboardGrid.gridId);
		grid.confirmDialog({
			CONFIRM: true,
			CONFIRM_MESSAGE: Loc.getMessage('CC_BBDL_ACTION_MENU_DELETE_CONF'),
		}, () => {
			ajax.runComponentAction(DashboardGrid.componentName, 'deleteRow', {
				mode: 'class',
				data: {
					id: id,
				}
			}).then(() => {
				grid.removeRow(id);
			});
		});
	}

	showOnboarding(): void
	{
		this.#getSpotlight().show();
		this.#getGuide().start();
		this.#getSpotlight().getTargetContainer().addEventListener('mouseover', () => {
			this.#getSpotlight().close();
		});
	}

	#getSpotlight(): BX.SpotLight
	{
		if (this.#spotLight)
		{
			return this.#spotLight;
		}

		this.#spotLight = new BX.SpotLight({
			targetElement: this.#options.bindElement,
			targetVertex: 'middle-center',
			id: DashboardGrid.gridId,
			lightMode: true,
		});

		return this.#spotLight;
	}

	#getGuide(): Guide
	{
		if (this.#guide)
		{
			return this.#guide;
		}

		this.#guide = new Guide({
			simpleMode: true,
			onEvents: true,
			overlay: false,
			steps: [
				{
					target: this.#options.bindElement,
					title: Loc.getMessage('CC_BBDL_ONBOARDING_TITLE'),
					text: Loc.getMessage('CC_BBDL_ONBOARDING_DESCRIPTION'),
					buttons: null,
					events: {
						onClose: () => {
							this.#getSpotlight().close();
						},
						onShow: () => {
							ajax.runComponentAction(DashboardGrid.componentName, 'markShowOnboarding', {
								mode: 'class'
							});
						},
					},
					article: this.#options.article,
				},
			],
			autoHide: true,
		});
		this.#guide.getPopup().setWidth(320);
		this.#guide.getPopup().setAngle({
			offset: this.#options.bindElement.offsetWidth / 2,
		});
		this.#guide.getPopup().setAutoHide(true);

		return this.#guide;
	}
}