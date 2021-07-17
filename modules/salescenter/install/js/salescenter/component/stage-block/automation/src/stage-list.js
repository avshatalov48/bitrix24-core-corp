import {Text} 				from 'main.core';
import {Popup as MainPopup} from 'main.popup';
import {Dom, Tag} 			from 'main.core';
import {SelectArrow} 		from "./select-arrow";

const StageList = {
	props: {
		stages: {
			type: Array,
			required: true
		},
		editable: {
			type: Boolean,
			required: true
		},
	},
	components:
		{
			'select-arrow-block'	:	SelectArrow,
		},
	computed:
		{
			classesObject()
			{
				return {
					'salescenter-app-payment-by-sms-item-container-select-inner' : true
				}
			}
		},
	methods:
	{
		styleObject(stage)
		{
			return {background:stage.color};
		},

		showSelectPopup(target, options)
		{
			if(!target || !this.editable)
			{
				return;
			}

			this.selectPopup = new MainPopup(null, target, {
				closeByEsc: true,
				autoHide: true,
				width: 250,
				offsetTop: 5,
				events: {
					onPopupClose: () => { this.selectPopup.destroy() }
				},
				content: this.getSelectPopupContent(options)
			});

			this.selectPopup.show();
		},

		getSelectPopupContent(options)
		{
			if (!this.selectPopupContent)
			{
				this.selectPopupContent = Tag.render`<div class="salescenter-app-payment-by-sms-select-popup"></div>`;

				const onClickOptionHandler = (event) => {
					this.onChooseSelectOption(event);
				};

				for (let i = 0; i < options.length; i++)
				{
					const option = Tag.render`
						<div data-item-value="${options[i].id}" class="salescenter-app-payment-by-sms-select-popup-option" style="background-color:${options[i].color ? options[i].color : ''};" onclick="${onClickOptionHandler.bind(this)}">
							${Text.encode(options[i].name)}
						</div>
					`;

					if (options[i].colorText === 'light') {
						option.style.color = '#fff';
					}

					Dom.append(option, this.selectPopupContent);
				}
			}

			return this.selectPopupContent;
		},

		onChooseSelectOption(event)
		{
			const currentOption = this.$refs['selectedOptions'][0];
			currentOption.textContent = event.currentTarget.textContent;
			currentOption.style.color = event.currentTarget.style.color;
			currentOption.nextElementSibling.style.borderColor = event.currentTarget.style.color;
			currentOption.parentNode.style.background = event.currentTarget.style.backgroundColor;

			this.$emit('on-choose-select-option', {data: event.currentTarget.getAttribute('data-item-value')});

			this.selectPopup.destroy();
		}
	},
	template: `
		<div class="salescenter-app-payment-by-sms-item-container-select">
			<div class="salescenter-app-payment-by-sms-item-container-select-text">
				<slot name="stage-list-text"/>
			</div>
			<template v-for="stage in stages">
				<div 
					v-if="stage.selected" 
					:class="classesObject" 
					:style="styleObject(stage)" 
					v-on:click="showSelectPopup($event.currentTarget, stages)"
				>
					<div ref="selectedOptions" class="salescenter-app-payment-by-sms-item-container-select-item">{{stage.name}}</div>
					<select-arrow-block/>
				</div>
			</template>
		</div>
	`
};
export {
	StageList
}
