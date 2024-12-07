import { Loc, Type } from 'main.core';
import { TagSelector } from 'ui.entity-selector';

export const QueueConfig = {
	props: {
		properties: {
			type: Object,
			required: true,
			default: {},
		},
		config: {
			type: Object,
			required: true,
			default: {},
		},
	},

	data(): Object
	{
		return {
			isMembersSelectorReadOnly: false,
			filledMembers: this.config.MEMBERS || [],
			isForwardTo: this.config.SETTINGS?.FORWARD_TO || false,
			isTimeTracking: this.config.SETTINGS?.TIME_TRACKING || false,
			filledMemberRequestDistribution: this.config.SETTINGS?.MEMBER_REQUEST_DISTRIBUTION || 'STRICTLY',
			filledTimeBeforeRequestNextMember: this.config.SETTINGS?.TIME_BEFORE_REQUEST_NEXT_MEMBER || 5,
		};
	},

	methods: {
		isPropertyEnabled(code: string): boolean
		{
			return Object.prototype.hasOwnProperty.call(this.properties, code)
				&& (
					this.properties[code] === true
					|| Type.isArrayFilled(this.properties[code])
				);
		},

		getSelectedMembers(): Array
		{
			return this.filledMembers.map((item: Object) => {
				return [item.ENTITY_TYPE, parseInt(item.ENTITY_ID, 10)];
			});
		},

		getData(): Object[]
		{
			const selectedMembers = this.membersSelector.getDialog().getSelectedItems();
			if (Type.isArrayFilled(selectedMembers))
			{
				this.filledMembers = selectedMembers
					.map((item: Item) => ({ ENTITY_ID: item.getId(), ENTITY_TYPE: item.getEntityId() }))
				;
			}

			return {
				members: this.filledMembers,
				properties: {
					FORWARD_TO: this.isForwardTo,
					TIME_TRACKING: this.isTimeTracking,
					MEMBER_REQUEST_DISTRIBUTION: this.filledMemberRequestDistribution,
					TIME_BEFORE_REQUEST_NEXT_MEMBER: this.filledTimeBeforeRequestNextMember,
				},
			};
		},
	},

	mounted(): void
	{
		this.membersSelector = new TagSelector({
			id: 'queue-config-members-tag-selector',
			context: 'QUEUE_CONFIG_MEMBERS_SELECTOR',
			readonly: this.isMembersSelectorReadOnly,
			dialogOptions: {
				id: 'queue-config-members-tag-selector',
				preselectedItems: this.getSelectedMembers(),
				entities: [{
					id: 'user',
					options: {
						inviteEmployeeLink: false,
						intranetUsersOnly: true,
					},
				}, {
					id: 'department',
					options: {
						inviteEmployeeLink: false,
						selectMode: 'usersAndDepartments',
					},
				}],
			},
		});
		this.membersSelector.renderTo(this.$refs.membersSelector);
	},

	template: `
		<div class="ui-form">
			<div class="ui-form-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_SELECTOR_TITLE')}
					</div>
				</div>
				<div class="ui-form-content" ref="membersSelector"></div>
			</div>
			<div
				v-if="isPropertyEnabled('FORWARD_TO')"
				class="ui-form-row"
			>
				<label for="isForwardTo" class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-checkbox">
					<input
						type="checkbox"
						class="ui-ctl-element"
						name="isForwardTo"
						v-model="isForwardTo"
					>
					<span class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_FORWARD_TO_TITLE')}
					</span>
				</label>
			</div>
			<div
				v-if="isPropertyEnabled('TIME_TRACKING')"
				class="ui-form-row"
			>
				<label for="isTimeTracking" class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-checkbox">
					<input
						type="checkbox"
						class="ui-ctl-element"
						name="isTimeTracking"
						v-model="isTimeTracking"
					>
					<span class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_TIME_TRACKING_TITLE')}
					</span>
				</label>
			</div>
			<div
				v-if="isPropertyEnabled('MEMBER_REQUEST_DISTRIBUTION_STRICTLY')"
				class="ui-form-row"
			>
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_NAME')}
					</div>
				</div>
				<div class="ui-form-content">
					<select
						class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-row ui-ctl-w100 ui-ctl-element"
						v-model="filledMemberRequestDistribution"
					>
						<option 
							value="STRICTLY"
						>
							${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_STRICTLY')}
						</option>
						<option 
							v-if="isPropertyEnabled('MEMBER_REQUEST_DISTRIBUTION_EVENLY')"
							value="EVENLY"
						>
							${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_EVENLY')}
						</option>
						<option
							v-if="isPropertyEnabled('MEMBER_REQUEST_DISTRIBUTION_ALL')"
							value="ALL"
						>
							${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_MEMBER_REQUEST_DISTRIBUTION_ALL')}
						</option>
					</select>
				</div>
			</div>
			<div
				v-if="isPropertyEnabled('TIME_BEFORE_REQUEST_NEXT_MEMBER')"
				class="ui-form-row"
			>
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">
						${Loc.getMessage('CRM_COMMUNICATION_RULE_QUEUE_CONFIG_TIME_BEFORE_REQUEST_NEXT_MEMBER_NAME')}
					</div>
				</div>
				<div class="ui-form-content">
					<select
						class="communication-queue-checkbox-block-wrapper ui-ctl ui-ctl-row ui-ctl-w100 ui-ctl-element"
						v-model="filledTimeBeforeRequestNextMember"
					>
						<option v-for="value in this.properties.TIME_BEFORE_REQUEST_NEXT_MEMBER" :value="value">
							{{ value }}
						</option>
					</select>
				</div>
			</div>
		</div>
	`,
};
