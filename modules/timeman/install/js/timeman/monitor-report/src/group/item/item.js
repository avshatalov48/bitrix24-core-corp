import {Vuex} from 'ui.vuex';
import {BitrixVue} from "ui.vue";
import {Time} from "../../mixin/time";
import {EntityType, EntityGroup} from "timeman.const";
import "ui.icons";
import "ui.vue.components.hint";

import "./item.css"

export const Item = BitrixVue.localComponent('bx-timeman-monitor-report-group-item', {
	mixins: [Time],
	props: [
		'readOnly',
		'group',
		'privateCode',
		'type',
		'title',
		'time',
		'allowedTime',
		'comment',
		'hint',
	],
	data: function() {
		return {
			action: '',
			hintOptions: {
				targetContainer: document.body
			},
			selected: false,
			selectIntervalTimeout: null,
		};
	},
	computed:
	{
		...Vuex.mapGetters('monitor',[
			'getSiteDetailByPrivateCode',
		]),
		...Vuex.mapState({
			monitor: state => state.monitor,
		}),
		EntityType: () => EntityType,
		EntityGroup: () => EntityGroup,
	},
	methods:
	{
		addPersonal(privateCode)
		{
			this.$store.dispatch('monitor/addPersonal', privateCode);
			this.onIntervalUnselected();
		},
		removePersonal(privateCode)
		{
			if (this.type === EntityType.absence && this.comment.trim() === '')
			{
				this.action = () => this.$store.dispatch('monitor/removePersonal', this.privateCode);
				this.onCommentClick();
				return;
			}

			this.$store.dispatch('monitor/removePersonal', privateCode);
			this.onIntervalUnselected();
		},
		addToStrictlyWorking(privateCode)
		{
			if (this.type === EntityType.absence && this.comment.trim() === '')
			{
				this.action = () => this.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
				this.onCommentClick();
				return;
			}

			this.$store.dispatch('monitor/addToStrictlyWorking', privateCode);
		},
		removeFromStrictlyWorking(privateCode)
		{
			this.$store.dispatch('monitor/removeFromStrictlyWorking', privateCode);
		},
		removeEntityByPrivateCode(privateCode)
		{
			this.$store.dispatch('monitor/removeEntityByPrivateCode', privateCode);
		},
		onCommentClick(event)
		{
			this.$emit('commentClick', {
				event,
				group: this.group,
				content: {
					privateCode: this.privateCode,
					title: this.title,
					time: this.time,
					comment: this.comment,
					type: this.type,
				},
				onSaveComment: this.action,
			});
		},
		onDetailClick(event)
		{
			this.$emit('detailClick', {
				event,
				group: this.group,
				content: {
					privateCode: this.privateCode,
					title: this.title,
					detail: this.getSiteDetailByPrivateCode(this.privateCode),
					time: this.time,
				}
			});
		},
		onIntervalSelected()
		{
			this.$emit('intervalSelected', this.privateCode);

			if (this.readOnly)
			{
				return;
			}

			this.selectIntervalTimeout = setTimeout(() => {
				this.selected = true;
			}, 500);
		},
		onIntervalUnselected()
		{
			this.$emit('intervalUnselected');

			if (this.readOnly)
			{
				return;
			}

			clearTimeout(this.selectIntervalTimeout);

			this.selected = false;
		},
	},
	// language=Vue
	template: `
		<div class="bx-monitor-group-item-wrap">
			<div
				:class="[
            		'bx-monitor-group-item',
					this.selected ? 'bx-monitor-group-item-' + this.group + '-selected' : ''
				]"
				@mouseenter="onIntervalSelected"
				@mouseleave="onIntervalUnselected"
			>
				<template
					v-if="
						type !== EntityType.group
						&& type !== EntityType.absenceShort
						&& type !== EntityType.other
					"
				>
					<div class="bx-monitor-group-item-container">
						<div class="bx-monitor-group-item-title-container">
						 	<template v-if="type === EntityType.absence">
								<div
									class="bx-monitor-group-item-icon bx-monitor-group-item-icon-away"
									v-bx-hint="{
										text: $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE'),
										popupOptions: {
											hintOptions,
											id: 'bx-vue-hint-monitor-absence-hint',
										},
									}"
								/>
								<div
									v-if="type === EntityType.absence"
									:class="{
									  'bx-monitor-group-item-title': comment,
									  'bx-monitor-group-item-title-small': !comment
									}"
								>
									<template v-if="comment">
										<div class="bx-monitor-group-item-title">{{ comment }}</div>
										<div class="bx-monitor-group-item-subtitle">{{ title }}</div>
									</template>
									<template v-else>
										{{ title }}
									</template>
								</div>
							</template>
							<template v-else-if="type === EntityType.custom">
								<div
									class="ui-icon ui-icon-common-user bx-monitor-group-item-icon"
									v-bx-hint="{
										text: $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CUSTOM_HINT'),
										popupOptions: {
											hintOptions,
											id: 'bx-vue-hint-monitor-custom-hint',
										},
									}"
								>
									<i/>
								</div>
								<div class="bx-monitor-group-item-title">
									{{ title }}
								</div>
							</template>
							<div v-else class="bx-monitor-group-item-title">
								<template v-if="type !== EntityType.site || readOnly">
									{{ title }}
								</template>
								<template v-else>
									<a
										@click="onDetailClick"
										href="#"
										class="bx-monitor-group-site-title"
									>
										{{ title }}
									</a>
								</template>
							</div>
							<bx-hint v-if="hint" :text="hint" :popupOptions="hintOptions"/>
							<button
								v-if="group === EntityGroup.working.value && !readOnly"
								class="bx-monitor-group-item-button-comment ui-icon ui-icon-xs"
								:class="{
								  'ui-icon-service-imessage': comment,
								  'ui-icon-service-light-imessage': !comment
								}"
							>
								<i
									@click="onCommentClick"
									:style="{
										backgroundColor: comment ? '#77c18d' : 'transparent'
									}"
								/>
							</button>
							<div
								v-else-if="group === EntityGroup.working.value && readOnly && comment"
								class="bx-monitor-group-item-icon bx-monitor-group-item-icon-comment"
								v-bx-hint="{
									text: comment,
									popupOptions: {
										...hintOptions,
										id: 'bx-vue-hint-monitor-comment',
									},
								}"
							/>
						</div>
						<div class="bx-monitor-group-item-time">
							{{ time }}
						</div>
					</div>
					<button
						v-if="group === EntityGroup.personal.value && !readOnly"
						@click="removePersonal(privateCode)"
						class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right"
					>
						{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_TO_WORKING') }}
					</button>
					<button
						v-if="
							group === EntityGroup.working.value
							&& (type !== EntityType.unknown && type !== EntityType.custom)
							&& !readOnly
						"
						@click="addPersonal(privateCode)"
						class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round bx-monitor-group-btn-right"
					>
						{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_TO_PERSONAL') }}
					</button>
					<button
						v-if="
							type === EntityType.custom
							&& !readOnly
						"
						@click="removeEntityByPrivateCode(privateCode)"
						class="ui-btn ui-btn-xs ui-btn-danger-light ui-btn-round bx-monitor-group-btn-right"
					>
						{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_REMOVE') }}
					</button>
				</template>
				<template v-else>
					<div class="bx-monitor-group-item-container">
						<div class="bx-monitor-group-item-title-container">
							<div class="bx-monitor-group-item-title-full">
								{{ title }}
							</div>
							<bx-hint v-if="hint" :text="hint" :popupOptions="hintOptions"/>
						</div>
						<div class="bx-monitor-group-item-menu">
							<div class="bx-monitor-group-item-time">
								{{ time }} / {{ allowedTime }}
							</div>
						</div>
					</div>
				</template>
			</div>
		</div>
	`
});
