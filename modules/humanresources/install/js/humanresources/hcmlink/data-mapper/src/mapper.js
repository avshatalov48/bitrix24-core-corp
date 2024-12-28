import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { Page } from './components/page';
import { Loader } from './components/loader';
import { Counter } from './components/counter';
import { StateScreen } from './components/state-screen';
import { Api } from 'humanresources.hcmlink.api';
import { Dom, Loc, Tag, Type } from 'main.core';
import { Layout } from 'ui.sidepanel.layout';

import './styles/mapper.css';

type MapperOptions = {
	companyId: number,
	userIds: ?Set,
	mode: 'direct' | 'reverse',
};

export class Mapper
{
	#container: HTMLElement;
	layout: Layout;
	#application: VueCreateAppResult | null = null;
	api: Api;
	options: MapperOptions;

	static MODE_DIRECT = 'direct';
	static MODE_REVERSE = 'reverse';

	constructor(options: MapperOptions)
	{
		this.api = new Api();
		this.options = options;

		if (Type.isNil(this.options.userIds))
		{
			this.options.userIds = new Set();
		}
	}

	static openSlider(
		options: MapperOptions,
		sliderOptions: { onCloseHandler: () => void },
	): void
	{
		BX.SidePanel.Instance.open('humanresources:mapper', {
			width: 800,
			loader: 'default-loader',
			cacheable: false,
			contentCallback: () => {
				return top.BX.Runtime.loadExtension('humanresources.hcmlink.data-mapper').then((exports) => {
					return (new exports.Mapper(options)).getLayout();
				});
			},
			events: {
				onClose: sliderOptions?.onCloseHandler ?? (() => {}),
			},
		});
	}

	renderTo(container: HTMLElement)
	{
		Dom.append(this.render(), container);
	}

	render(): HTMLElement
	{
		this.#container = document.createElement('div');
		if (this.#application === null)
		{
			this.#application = BitrixVue.createApp(this.makeRootVueComponent());
			this.component = this.#application.mount(this.#container);
		}

		return this.#container;
	}

	async getLayout()
	{
		const getContentLayout = function(): HTMLElement {
			return this.render();
		}.bind(this);

		const saveAction = async function() {
			const collection = Object.values(this.component.getUserMappingSet());

			return this.api.saveMapping({
				collection,
				companyId: this.options.companyId,
			});
		}.bind(this);

		const prepareNextUsers = async function() {
			this.component.prepareNextUsers();
		}.bind(this);

		this.layout = await Layout.createLayout({
			extensions: [
				'humanresources.hcmlink.data-mapper',
				'ui.entity-selector',
				'ui.icon-set.actions',
				'ui.select',
				'popup',
			],
			title: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_TITLE'),
			toolbar()
			{
				return [
					Tag.render`<div id="hr-hcmlink-toolbar-container"></div>`,
				];
			},
			content(): HTMLElement
			{
				return getContentLayout();
			},
			buttons({ cancelButton, SaveButton }): Array
			{
				return [
					new SaveButton({
						text: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_BUTTON_SAVE_AND_CONTINUE'),
						async onclick() {
							const result = await saveAction();
							if (result)
							{
								await prepareNextUsers();
							}
						},
						round: true,
					}),
					cancelButton,
				];
			},
		});

		return this.layout.render();
	}

	makeRootVueComponent(): Object
	{
		const context = this;

		return {
			name: 'HumanresourcesHcmlinkMapper',

			components: {
				Page,
				Loader,
				Counter,
				StateScreen,
			},

			data() {
				return {
					loading: false,
					config: {
						companyId: context.options.companyId,
						mode: context.options.mode ?? 'direct',
						isHideInfoAlert: true,
					},
					pageCount: 0,
					mappingEntityCollection: [],
					userMappingSet: {},
					userIdCollection: [...context.options.userIds],
					isJobResolved: false,
					jobId: null,
					isDone: false,
					countAllPersonsForMap: 0,
					countMappedPersons: 0,
					countUnmappedPersons: 0,
					counterContainer: '#hr-hcmlink-toolbar-container',
					isReadyToolbar: false,
					completedStatus: context.options.mode === 'direct' ? 'done' : 'salaryDone',
					jobResolverInterval: null,
					mappedUserIds: [],
				};
			},

			created(): void
			{
				this.footerDisplay(false);
				this.createUpdateEmployeeListJob();
			},

			computed: {
				isJobPending() {
					return !this.isJobResolved && !this.isDone;
				},
				isMappingReady() {
					return this.isJobResolved && !this.isDone;
				},
				isMappingDone() {
					return this.isJobResolved && this.isDone;
				},
			},

			watch: {
				pageCount()
				{
					this.loadConfig();
				},
				isMappingReady(value)
				{
					if (value)
					{
						this.footerDisplay(true);
					}
				},
				isMappingDone(value)
				{
					if (value)
					{
						this.footerDisplay(false);
					}
				},
			},

			mounted()
			{
				this.countAllPersonsForMap = this.userIdCollection.length;
				this.$nextTick(() => {
					this.isReadyToolbar = true;
				});
			},

			unmounted()
			{
				clearInterval(this.jobResolverInterval);
			},

			methods: {
				prepareNextUsers()
				{
					this.userMappingSet = {};
					this.pageCount++;
				},
				getUserMappingSet()
				{
					return this.userMappingSet;
				},
				onCreateLink(options)
				{
					this.userMappingSet[options.userId] = options;
				},
				onRemoveLink(options)
				{
					if (this.userMappingSet[options.userId] !== undefined)
					{
						delete this.userMappingSet[options.userId];
					}
				},
				onCloseAlert()
				{
					context.api.closeInfoAlert();
				},
				onCompleteMapping()
				{
					context.api.createCompleteMappingEmployeeListJob({
						companyId: this.config.companyId,
					});
				},
				async loadConfig()
				{
					this.loading = true;

					const {
						items,
						countMappedPersons,
						countUnmappedPersons,
						isHideInfoAlert,
						mappedUserIds,
					} = await context.api.loadMapperConfig({
						companyId: this.config.companyId,
						userIds: this.userIdCollection,
						mode: this.config.mode,
					});

					this.config.isHideInfoAlert = isHideInfoAlert;
					this.countUnmappedPersons = countUnmappedPersons;
					this.countMappedPersons = countMappedPersons;
					this.mappingEntityCollection = Type.isArray(items) ? items : [];
					this.mappedUserIds = mappedUserIds;

					this.isDone = this.mappingEntityCollection.length === 0;

					this.loading = false;
				},
				async createUpdateEmployeeListJob()
				{
					const data = await context.api.createUpdateEmployeeListJob({
						companyId: this.config.companyId,
					});
					this.jobId = data.jobId;

					this.jobResolverInterval = setInterval(this.jobResolver.bind(this), 30000);

					BX.PULL.subscribe({
						type: BX.PullClient.SubscriptionType.Server,
						moduleId: 'humanresources',
						command: 'external_employee_list_updated',
						callback: async function(params: {jobId: number, status: string}): Promise<void> {
							if (
								(params.jobId === this.jobId)
								&& (params.status === 3)
							)
							{
								clearInterval(this.jobResolverInterval);
								await this.loadConfig(params);
								this.isJobResolved = true;
							}
						}.bind(this),
					});
					BX.PULL.extendWatch('humanresources_person_mapping');
				},
				async jobResolver()
				{
					const { params } = await context.api.getJobStatus({ jobId: this.jobId });

					if (params.status === 3)
					{
						clearInterval(this.jobResolverInterval);
						await this.loadConfig(params);
						this.isJobResolved = true;
					}
				},
				footerDisplay(show: boolean): void
				{
					if (!context.layout)
					{
						return;
					}

					if (context.layout.getFooterContainer())
					{
						Dom.style(context.layout.getFooterContainer(), 'display', show ? 'block' : 'none');
					}

					const footerAnchor = context.layout.getContainer()?.getElementsByClassName('ui-sidepanel-layout-footer-anchor')[0];
					if (footerAnchor)
					{
						Dom.style(footerAnchor, 'display', show ? 'block' : 'none');
					}
				},
			},

			template: `
                <template v-if="isJobPending">
                    <StateScreen
                        status='pending'
                    ></StateScreen>
                </template>
                <template v-if="isMappingReady">
                    <Loader v-if="loading"></Loader>
                    <Page
                        :collection=mappingEntityCollection
						:mappedUserIds=mappedUserIds
                        :config=config
                        @createLink="onCreateLink"
                        @removeLink="onRemoveLink"
						@closeAlert="onCloseAlert"
                    ></Page>
                </template>
                <template v-if="isMappingDone">
                    <StateScreen
	                    :status=completedStatus
						@completeMapping='onCompleteMapping'
                    ></StateScreen>
                </template>
				<Teleport v-if="isReadyToolbar && isMappingReady" :to="counterContainer">
					<Counter
						:countAllPersonsForMap=countAllPersonsForMap
						:countMappedPersons=countMappedPersons
						:countUnmappedPersons=countUnmappedPersons
						:config=config
					></Counter>
				</Teleport>
			`,
		};
	}
}
