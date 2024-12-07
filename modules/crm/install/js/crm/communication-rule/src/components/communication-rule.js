import { ajax as Ajax, Loc, Runtime, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { RuleActions } from './rule-actions';
import { RuleProperties } from './rule-properties';
import { QueueConfig } from './queue-config';

const EMPTY = 0;
const LEAD = 1;
const CONTACT = 3;
const COMPANY = 4;

export const CommunicationRule = {
	components: {
		RuleProperties,
		RuleActions,
		QueueConfig,
	},

	props: {
		rule: {
			type: Object,
			required: false,
			default: {},
		},
		channels: {
			type: Array,
			required: true,
			default: [],
		},
		searchTargetEntities: {
			type: Array,
			required: true,
			default: [],
		},
		entities: {
			type: Array,
			required: true,
			default: [],
		},
		selectedChannelId: {
			type: Number,
			required: false,
			default: null,
		},
	},

	data(): Object
	{
		const searchTarget = this.rule?.SEARCH_TARGETS ?? {};

		return {
			currentSelectedChannelId: this.selectedChannelId ?? this.channels[0].id,
			currentSelectedTargetEntitySectionId: searchTarget.sectionId,
			currentSelectedTargetEntityTypeIds: Runtime.clone(searchTarget.entityTypeIds) ?? [],

			ruleId: this.rule?.ID || null,
			actions: this.rule?.ENTITIES || [],
			title: this.rule?.TITLE || [],
			rules: this.rule?.RULES || [],
			queueConfig: this.rule?.QUEUE_CONFIG || [],

			skipNextRules: (this.rule?.SETTINGS?.skipNextRules === 'Y') || false,
			manualItemsCreate: (this.rule?.SETTINGS?.manualItemsCreate === 'Y') || false,
			runWorkflowLater: (this.rule?.SETTINGS?.runWorkflowLater === 'Y') || false,
		};
	},

	mounted()
	{
		EventEmitter.subscribe(BX.UI.ButtonPanel, 'button-click', this.onButtonClick.bind(this));
	},

	beforeUnmount()
	{
		EventEmitter.unsubscribe(BX.UI.ButtonPanel, 'button-click', this.onButtonClick);
	},

	computed: {
		selectedChannelRuleProperties(): ?Object
		{
			return this.getChannelById(this.currentSelectedChannelId)?.properties;
		},

		selectedChannelQueueConfig(): ?Object
		{
			return this.getChannelById(this.currentSelectedChannelId)?.queueConfig;
		},

		targetEntitySections(): Array
		{
			const sections = [];

			this.searchTargetEntities.forEach((item) => {
				sections.push(item.section);
			});

			return sections;
		},

		compatibleEntities(): Array
		{
			// empty -> all excepts repeated lead
			if (this.currentSelectedTargetEntityTypeIds.includes(EMPTY))
			{
				return this.entities.filter((entity) => {
					return (
						entity.entityTypeId !== LEAD
						|| (entity.entityTypeId === LEAD && entity.data?.isReturnCustomer !== true)
					);
				});
			}

			// lead -> lead
			if (
				this.currentSelectedTargetEntityTypeIds.includes(LEAD)
				&& this.currentSelectedTargetEntityTypeIds.length === 1
			)
			{
				const leadEntity = this.entities.find((entity) => {
					return entity.entityTypeId === LEAD && entity.data?.isReturnCustomer !== true;
				});

				return [leadEntity];
			}

			// contact -> repeated lead, contact, deal, dynamics
			if (
				this.currentSelectedTargetEntityTypeIds.includes(CONTACT)
				&& this.currentSelectedTargetEntityTypeIds.length === 1
			)
			{
				return this.entities.filter((entity) => {
					return (
						(entity.entityTypeId !== COMPANY && entity.entityTypeId !== LEAD)
						|| (entity.entityTypeId === LEAD && entity.data?.isReturnCustomer === true)
					);
				});
			}

			if (
				this.currentSelectedTargetEntityTypeIds.includes(LEAD)
				&& this.currentSelectedTargetEntityTypeIds.includes(CONTACT)
				&& this.currentSelectedTargetEntityTypeIds.length === 2
			)
			{
				return this.entities.filter((entity) => {
					return entity.entityTypeId !== COMPANY;
				});
			}

			if (
				this.currentSelectedTargetEntityTypeIds.includes(LEAD)
				&& this.currentSelectedTargetEntityTypeIds.includes(COMPANY)
				&& this.currentSelectedTargetEntityTypeIds.length === 2
			)
			{
				return this.entities.filter((entity) => {
					return entity.entityTypeId !== CONTACT;
				});
			}

			if (this.currentSelectedTargetEntityTypeIds.length === 0)
			{
				return this.entities.filter((entity) => {
					return entity.entityTypeId !== LEAD || entity.data?.isReturnCustomer !== true;
				});
			}

			return this.entities;
		},
	},

	methods: {
		async onButtonClick(event: BaseEvent): void
		{
			const data = event.getData();
			const button = data[0] ?? null;

			if (!Type.isObject(button))
			{
				return;
			}

			if (button.TYPE === 'save')
			{
				await this.save();
			}
			else if (button.TYPE === 'remove')
			{
				await this.delete();
			}

			const currentSlider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			if (currentSlider)
			{
				currentSlider.setCacheable(false);
				currentSlider.close(false);
			}
		},

		async save(): Promise
		{
			const data = {
				id: this.ruleId,
				title: this.title,
				channelId: this.currentSelectedChannelId,
				properties: this.$refs.properties.getData() ?? [],
				queueConfig: this.$refs.queueConfig.getData() ?? [],
				searchTargets: {
					sectionId: this.currentSelectedTargetEntitySectionId,
					entityTypeIds: this.currentSelectedTargetEntityTypeIds,
				},
				actions: this.$refs.actions.getData() ?? [],
				settings: {
					skipNextRules: this.skipNextRules ? 'Y' : 'N',
					manualItemsCreate: this.manualItemsCreate ? 'Y' : 'N',
					runWorkflowLater: this.runWorkflowLater ? 'Y' : 'N',
				},
			};

			return new Promise(async (resolve, reject) => {
				Ajax.runAction('crm.controller.communication.rule.save', {
					data,
				}).then((response) => {
					resolve(response);
				}).catch((response) => {
					const errors = [];
					response.errors.forEach(({ message }) => {
						errors.push(message);
					});
					reject(errors);
				});
			});
		},

		async delete(): Promise
		{
			const data = {
				id: this.ruleId,
				withQueue: true,
			};

			// eslint-disable-next-line no-async-promise-executor
			return new Promise(async (resolve, reject) => {
				Ajax.runAction('crm.controller.communication.rule.delete', {
					data,
				}).then((response) => {
					resolve(response);
				}).catch((response) => {
					const errors = [];
					response.errors.forEach(({ message }) => {
						errors.push(message);
					});
					reject(errors);
				});
			});
		},

		getChannelById(id: string): ?Object
		{
			return this.channels.find((channel) => channel.id === id) ?? null;
		},

		getTargetEntitiesBySectionId(sectionId: string): Array
		{
			const section = this.searchTargetEntities.find((entity) => entity.section.id === sectionId);

			return section?.entities ?? [];
		},
		onChangeCurrentSelectedTargetEntityTypeIds(): void
		{
			this.actions = [];

			if (this.currentSelectedTargetEntityTypeIds.includes(LEAD))
			{
				this.currentSelectedTargetEntityTypeIds = [LEAD];
			}

			void this.$nextTick(() => {
				this.$refs.actions.reset();
			});
		},
	},

	template: `
		<div>
			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_COMMON_SETTINGS_TITLE')}
					</span>
				</div>
				<div class="ui-form">
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${Loc.getMessage('CRM_COMMUNICATION_RULE_TITLE')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
								<input
									type="text" 
									class="ui-ctl-element"
									v-model="title"
								>
							</div>
						</div>
					</div>
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_TITLE')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" v-model="currentSelectedChannelId">
									<option v-for="channel in channels" :value="channel.id" :key="channel.id">
		    							{{ channel.title }}
		  							</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
	
			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_TITLE')}
					</span>
				</div>
				<div class="ui-form">
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_CATEGORY')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" v-model="currentSelectedTargetEntitySectionId">
									<option v-for="section in targetEntitySections" :value="section.id" :key="section.id">
		    							{{ section.title }}
		  							</option>
								</select>
							</div>
						</div>
					</div>
					<div class="ui-form-row">
						<div class="ui-form-label">
							<div class="ui-ctl-label-text">
								${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_ENTITY_TYPE')}
							</div>
						</div>
						<div class="ui-form-content">
							<div class="ui-ctl ui-ctl-w100">
								<select 
									class="ui-ctl-element" 
									v-model="currentSelectedTargetEntityTypeIds"
									multiple
									@change="onChangeCurrentSelectedTargetEntityTypeIds"
								>
									<option :value="0">
		    							${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_SEARCH_TARGET_ENTITY_EMPTY')}
		  							</option>
									<option 
										v-for="entity in getTargetEntitiesBySectionId(currentSelectedTargetEntitySectionId)"
										:value="entity.id"
										:key="entity.id"
									>
		    							{{ entity.title }}
		  							</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
	
			<div class="communication-rule-block-wrapper">
				<RuleProperties
					ref="properties"
					:properties="selectedChannelRuleProperties"
					:rules="rules"
				/>
			</div>

			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_TITLE')}
					</span>
				</div>
				<QueueConfig
					ref="queueConfig"
					:properties="selectedChannelQueueConfig"
				 	:config="queueConfig"
				/>
			</div>

			<div class="communication-rule-block-wrapper">
				<RuleActions
					ref="actions"
					:actions="actions"
					:entities="compatibleEntities"
				/>
			</div>
	
			<div class="communication-rule-block-wrapper">
				<div class="communication-rule-title">
					<span class="communication-rule-title-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_CHANNEL_ADDITIONAL_SETTINGS_TITLE')}
					</span>
				</div>
				<div class="ui-form">
					<div class="ui-form-content">
						<div class="ui-form-row">
							<label for="skipNextRules" class="ui-ctl ui-ctl-checkbox">
								<input 
									class="ui-ctl-element"
									type="checkbox"
									name="skipNextRules"
									v-model="skipNextRules"
								>
								<span class="ui-ctl-label-text">
									${Loc.getMessage('CRM_COMMUNICATION_RULE_CS_SKIP_RULES')}
								</span>
							</label>
						</div>
						<div class="ui-form-row">
							<label for="manualItemsCreate" class="ui-ctl ui-ctl-checkbox">
								<input 
									class="ui-ctl-element"
									type="checkbox"
									name="manualItemsCreate"
									v-model="manualItemsCreate"
								>
								<span class="ui-ctl-label-text">
									${Loc.getMessage('CRM_COMMUNICATION_RULE_CS_MANUAL_ITEMS_CREATE')}
								</span>
							</label>
						</div>
						<div class="ui-form-row">
							<label for="runWorkflowLater" class="ui-ctl ui-ctl-checkbox">
								<input 
									class="ui-ctl-element"
									type="checkbox"
									name="runWorkflowLater"
									v-model="runWorkflowLater"
								>
								<span class="ui-ctl-label-text">
									${Loc.getMessage('CRM_COMMUNICATION_RULE_CS_RUN_WORKFLOW_LATER')}
								</span>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
