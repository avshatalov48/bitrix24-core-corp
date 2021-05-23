import {Loc} 								from 'main.core';
import {Block} 		from 'salescenter.component.stage-block';
import {StageList} 							from 'salescenter.component.stage-block.automation';
import * as AutomationStage 				from 'salescenter.automation-stage';
import {StageMixin} 						from "./stage-mixin";

const Automation = {
	props: {
		status: {
			type: String,
			required: true
		},
		counter: {
			type: String,
			required: true
		},
		items: {
			type: Array,
			required: true
		},
		initialCollapseState: {
			type: Boolean,
			required: true
		},
	},
	mixins:[StageMixin],
	data()
	{
		return {
			stages: []
		}
	},
	components:
		{
			'stage-block-item'			:	Block,
			'stage-item-list'			:	StageList
		},
	methods:
		{
			loadStageCollection()
			{
				Object.values(this.items).forEach(
					options => this.stages.push(AutomationStage.Factory.create(options)));
			},

			setStageOnOrderPaid(e)
			{
				this.$root.$app.stageOnOrderPaid = e.data;
			},

			saveCollapsedOption(option)
			{
				this.$emit('on-save-collapsed-option', 'automation', option);
			},

			updateSelectedStage(e)
			{
				let newStageId = e.data;
				this.stages.forEach((stage) => {
					stage.selected = stage.id === newStageId;
				});
			}
		},
	computed:
		{
			configForBlock()
			{
				return {
					counter: this.counter,
					checked: this.counterCheckedMixin,
					collapsible: true,
					initialCollapseState: this.initialCollapseState,
					titleName: this.selectedStage.name,
				}
			},
			selectedStage()
			{
				return this.stages.find((stage) =>
				{
					return stage.selected;
				});
			}
		},
	created()
	{
		this.loadStageCollection()
	},
	template: `
		<stage-block-item
			:config="configForBlock"
			:class="statusClassMixin"
			@on-adjust-collapsed="saveCollapsedOption"
		>
			<template v-slot:block-title-title>${Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TITLE')}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<stage-item-list 
						v-on:on-choose-select-option="updateSelectedStage($event); setStageOnOrderPaid($event)"
						:stages="stages">
						<template v-slot:stage-list-text>${Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TEXT')}</template>
					</stage-item-list>
				</div>
			</template>
		</stage-block-item>
	`
};
export {
	Automation
}