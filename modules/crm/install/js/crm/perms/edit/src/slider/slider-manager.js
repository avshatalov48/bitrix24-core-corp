import 'ui.design-tokens';
import './../css/slider.css';
import { Loc } from 'main.core';
import { Button } from 'ui.buttons';
import { SidePanel } from 'ui.sidepanel';
import { Layout } from 'ui.sidepanel.layout';
import { BitrixVue } from 'ui.vue3';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';
import { SliderPermissions } from './slider-permissions';

export class SliderManager
{
	#url;

	#containerId;

	#sliderApplication;

	constructor(entityCode, storage)
	{
		const rnd = Math.round(Math.random() * 10000);

		this.#url = `bx-crm-perms-role-edit-slider__${Date.now()}`;
		this.#containerId = `bx-crm-perms-role-edit-slider-container__${Date.now()}__${rnd}`;
		this.#sliderApplication = this.#createApplication(entityCode);
		this.#sliderApplication.use(storage);
	}

	async open() {
		SidePanel.Instance.open(this.#url, await this.#getOptions());
	}

	close() {}

	async #getOptions() {
		const buttons = [
			new Button({
				text: Loc.getMessage('UI_BUTTONS_CLOSE_BTN_TEXT'),
				size: Button.Size.MEDIUM,
				color: Button.Color.LIGHT_BORDER,
				dependOnTheme: false,
				onclick: () => {
					this.#closeApplication();
					SidePanel.Instance.close();
				},
			}),
		];

		const layout = await Layout.createLayout({
			title: '',
			content: () => `<div id="${this.#containerId}"></div>`,
			buttons: () => buttons,
			design: { section: false },
		});

		return {
			contentClassName: '',
			allowChangeTitle: false,
			width: 800,
			cacheable: false,
			allowChangeHistory: false,
			label: '',
			contentCallback: (slider) => {
				return layout.render();
			},
			events: {
				onOpenComplete: () => {
					const rootNode = document.getElementById(this.#containerId);
					this.#sliderApplication.mount(rootNode);
				},
				onClose: () => {
					this.#closeApplication();
				},
			},
		};
	}

	#closeApplication() {
		if (this.#sliderApplication)
		{
			this.#sliderApplication.unmount();
			this.#sliderApplication = null;
		}
	}

	#createApplication(entityCode) {
		return BitrixVue.createApp({
			name: 'CrmConfigPermsRoleEditSlider',
			components: {
				SliderPermissions,
			},
			props: {
				entityCode: {
					required: true,
					type: String,
				},
			},
			computed: {
				...mapGetters(['permissionEntities', 'getMainPermissionEntityByCode']),
				entity() {
					return this.getMainPermissionEntityByCode(this.entityCode);
				},
				entityName() {
					return this?.entity?.name || '';
				},
			},
			methods: {
				...mapMutations(['assignPermissionAttribute']),
			},
			template: `
				<div class="bx-crm-perms-edit-slider">
					<h1>{{ entityName }}</h1>
					<SliderPermissions
						:entity-code="entityCode"
					/>
				</div>
			`,
		}, { entityCode });
	}

	static create(entity, storage) {
		return new SliderManager(entity, storage);
	}
}
