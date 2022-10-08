import {BitrixVue} from "ui.vue";
import {Time} from "../mixin/time";
import {EntityType, EntityGroup} from "timeman.const";
import {Item} from './item/item';
import {MountingPortal} from 'ui.vue.portal';
import {PopupManager} from 'main.popup';
import {MessageBox} from 'ui.dialogs.messagebox';

import "ui.icons";

import "ui.design-tokens";
import "ui.fonts.opensans";
import "./group.css";


export const Group = BitrixVue.localComponent('bx-timeman-monitor-report-group', {
	components:
	{
		Item,
		MountingPortal
	},
	directives:
	{
		'bx-focus':
		{
			inserted(element)
			{
				element.focus()
			}
		}
	},
	mixins: [Time],
	props: [
		'group',
		'items',
		'time',
		'reportComment',
		'readOnly',
		'hasIntervalsToAdd',
	],
	data: function()
	{
		return {
			popupInstance: null,
			popupIdSelector: !!this.readOnly ? '#bx-timeman-pwt-popup-preview' :  '#bx-timeman-pwt-popup-editor',
			popupContent: {
				privateCode: '',
				title: '',
				time: '',
				comment: '',
				detail: '',
				type: '',
				onSaveComment: '',
			},
			comment: '',
			isCommentPopup: false,
			isDetailPopup: false,
			isReportCommentPopup: false,
		};
	},
	computed:
	{
		EntityType: () => EntityType,
		EntityGroup: () => EntityGroup,
		displayedGroup()
		{
			if (this.EntityGroup.getValues().includes(this.group))
			{
				return this.EntityGroup[this.group];
			}
		},
	},
	methods:
	{
		onCommentClick(event)
		{
			this.isCommentPopup = true;
			this.popupContent.privateCode = event.content.privateCode;
			this.popupContent.title = event.content.title;
			this.popupContent.time = event.content.time;
			this.popupContent.type = event.content.type;
			this.popupContent.onSaveComment = event.onSaveComment;
			this.comment = event.content.comment;

			if (this.popupInstance !== null)
			{
				this.popupInstance.destroy();
				this.popupInstance = null;
			}

			const popup = PopupManager.create({
				id: "bx-timeman-pwt-external-data",
				targetContainer: document.body,
				autoHide: true,
				closeByEsc: true,
				bindOptions: {position: "top"},
				events: {
					onPopupDestroy: () =>
					{
						this.isCommentPopup = false;
						this.popupInstance = null
					}
				},
			});

			//little hack for correct open several popups in a row.
			this.$nextTick(() => this.popupInstance = popup);
		},
		onReportCommentClick()
		{
			this.isReportCommentPopup = true;
			this.popupContent.title = this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_REPORT_COMMENT');
			this.comment = this.reportComment;

			if (this.popupInstance !== null)
			{
				this.popupInstance.destroy();
				this.popupInstance = null;
			}

			const popup = PopupManager.create({
				id: "bx-timeman-pwt-external-data",
				targetContainer: document.body,
				autoHide: true,
				closeByEsc: true,
				bindOptions: {position: "top"},
				events: {
					onPopupDestroy: () =>
					{
						this.isReportCommentPopup = false;
						this.popupInstance = null
					}
				},
			});

			//little hack for correct open several popups in a row.
			this.$nextTick(() => this.popupInstance = popup);
		},
		onDetailClick(event: BaseEvent)
		{
			this.isDetailPopup = true;
			this.popupContent.privateCode = event.content.privateCode;
			this.popupContent.title = event.content.title;
			this.popupContent.time = event.content.time;
			this.popupContent.detail = event.content.detail;

			if (this.popupInstance !== null)
			{
				this.popupInstance.destroy();
				this.popupInstance = null;
			}

			const popup = PopupManager.create({
				id: "bx-timeman-pwt-external-data",
				targetContainer: document.body,
				autoHide: true,
				closeByEsc: true,
				bindOptions: {position: "top"},
				events: {
					onPopupDestroy: () =>
					{
						this.isDetailPopup = false;
						this.popupInstance = null
					}
				},
			});

			//little hack for correct open several popups in a row.
			this.$nextTick(() => this.popupInstance = popup);
		},
		saveComment(privateCode)
		{
			if (this.comment.trim() === '' && this.popupContent.type === EntityType.absence)
			{
				return;
			}

			this.$store.dispatch('monitor/setComment', {
				privateCode,
				comment: this.comment
			});

			if (typeof this.popupContent.onSaveComment === 'function')
			{
				this.popupContent.onSaveComment();
			}

			this.popupInstance.destroy();
		},
		saveReportComment()
		{
			this.$store.dispatch('monitor/setReportComment', this.comment);

			this.popupInstance.destroy();
		},
		addNewLineToComment()
		{
			this.comment += '\n';
		},
		selectIntervalClick(event)
		{
			if (!this.hasIntervalsToAdd)
			{
				return;
			}

			this.$emit('selectIntervalClick', event);
		},
		onIntervalSelected(privateCode)
		{
			this.$emit('intervalSelected', privateCode);
		},
		onIntervalUnselected()
		{
			this.$emit('intervalUnselected');
		},
	},
	// language=Vue
	template: `		  
		<div class="bx-timeman-monitor-report-group-wrap">			
			<div class="bx-monitor-group">				  
				<div class="bx-monitor-group-header" v-bind:style="{ background: displayedGroup.secondaryColor }">
					<div class="bx-monitor-group-title-container">
                      	<div class="bx-monitor-group-title-wrap">
							<div class="bx-monitor-group-title">
								{{ displayedGroup.title }}
							</div>
							<div class="bx-monitor-group-title-wrap">
								<div class="bx-monitor-group-subtitle">
								  {{ formatSeconds(time) }}
								</div>
							</div>
							<button 
								v-if="this.displayedGroup.value === EntityGroup.working.value && !readOnly"
								@click="onReportCommentClick"
								class="bx-monitor-group-item-button-comment ui-icon ui-icon-xs"
								:class="{
									'ui-icon-service-imessage': reportComment, 
									'ui-icon-service-light-imessage': !reportComment 
								}"
							>
								<i 
									:style="{
										backgroundColor: reportComment ? '#77c18d' : 'transparent'
									}"
								/>
							</button>
							<div
								v-else-if="
									this.displayedGroup.value === EntityGroup.working.value 
									&& readOnly 
									&& reportComment
								"
								class="bx-monitor-group-item-icon bx-monitor-group-item-icon-comment"
								v-bx-hint="reportComment"
							/>
						</div>
						<button
							v-if="(
								this.displayedGroup.value === EntityGroup.working.value
								&& !readOnly 
							)"
							@click="selectIntervalClick"
							:class="{
								'bx-monitor-group-btn-add': true,
								'ui-btn': true, 
								'ui-btn-xs': true, 
								'ui-btn-round': true, 
								'ui-btn-light': hasIntervalsToAdd, 
								'ui-btn-disabled': !hasIntervalsToAdd, 
							}"
						>
							<span class="ui-btn-text">
								{{ '+ ' + $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_ADD') }}
							</span>
						</button>
					</div>
					<div v-if="!readOnly" class="bx-monitor-group-subtitle-wrap">
						<div class="bx-monitor-group-hint">
							{{ displayedGroup.hint }}
						</div>
					</div>
				</div>
				<div class="bx-monitor-group-content" v-bind:style="{ background: displayedGroup.lightColor }">
					<transition-group name="bx-monitor-group-item" class="bx-monitor-group-content-wrap">
					
						<Item
							v-for="item of items"
							:key="item.privateCode ? item.privateCode : item.title"
							:group="displayedGroup.value"
							:privateCode="item.privateCode"
							:type="item.type"
							:title="item.title"
							:comment="item.comment"
							:time="formatSeconds(item.time)"
							:allowedTime="item.allowedTime ? formatSeconds(item.allowedTime) : null"
							:readOnly="!!readOnly"
							:hint="item.hint !== '' ? item.hint : null"
							@commentClick="onCommentClick"
							@detailClick="onDetailClick"
							@intervalSelected="onIntervalSelected"
							@intervalUnselected="onIntervalUnselected"
						/>
					  
					</transition-group>
				</div>
			</div>

			<mounting-portal :mount-to="popupIdSelector" append v-if="popupInstance">
				<div class="bx-timeman-monitor-popup-wrap">					
					<div class="popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height" style="padding: 0">
						<div class="bx-timeman-monitor-popup-title popup-window-titlebar">
							<span class="bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text">
								{{ popupContent.title }}
							</span>
							<span 
								v-if="isCommentPopup || isDetailPopup" 
								class="bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text"
							>
								{{ popupContent.time }}
							</span>
						</div>
						<div class="popup-window-content" style="overflow: auto; background: transparent;">
							<textarea 
								class="bx-timeman-monitor-popup-input"
								id="bx-timeman-monitor-popup-input-comment"
								v-if="isCommentPopup || isReportCommentPopup"
								v-model="comment"
								v-bx-focus
								:placeholder="$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_ITEM_COMMENT')"
								@keydown.enter.prevent.exact="
									isCommentPopup 
										? saveComment(popupContent.privateCode) 
										: saveReportComment()
								"
								@keyup.shift.enter.exact="addNewLineToComment"
							/>
							<div v-if="isDetailPopup" class="bx-timeman-monitor-popup-items-container">
								<div 
									v-for="detailItem in popupContent.detail" 
									class="bx-timeman-monitor-popup-item"
								>
									<div class="bx-timeman-monitor-popup-content">
										<div class="bx-timeman-monitor-popup-content-title">
											{{ detailItem.siteTitle }}
										</div>
										<div class="bx-timeman-monitor-popup-content-title">
											<a target="_blank" :href="detailItem.siteUrl" class="bx-timeman-monitor-popup-content-title">
												{{ detailItem.siteUrl }}
											</a>
										</div>
									</div>
									<div class="bx-timeman-monitor-popup-time">
										{{ formatSeconds(detailItem.time) }}
									</div>
								</div>
							</div>
						</div>
						<div class="popup-window-buttons">
							<button 
								v-if="isCommentPopup || isReportCommentPopup" 
								@click="
									isCommentPopup 
										? saveComment(popupContent.privateCode) 
										: saveReportComment()
								"
								class="ui-btn ui-btn-md ui-btn-primary"
								:class="{'ui-btn-disabled': (comment.trim() === '' && popupContent.type === EntityType.absence)}"
							>
								<span class="ui-btn-text">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_OK') }}
								</span>
							</button>
							<button @click="popupInstance.destroy()" class="ui-btn ui-btn-md ui-btn-light">
								<span v-if="isCommentPopup || isReportCommentPopup" class="ui-btn-text">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_CANCEL') }}
								</span>
								<span v-if="isDetailPopup" class="ui-btn-text">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_GROUP_BUTTON_CLOSE') }}
								</span>
							</button>
						</div>
					</div>
				</div>
			</mounting-portal>
		</div>
	`
});