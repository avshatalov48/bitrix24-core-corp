import './style.css';
import * as Mixins from "../base/components/mixins";

const ItemSelector = {
	props: ['field'],
	template: `
		<div>
			<div class="b24-form-control-list-selector-item"
				v-for="(item, itemIndex) in field.unselectedItems()"
				@click="selectItem(item)"
			>
				<img class="b24-form-control-list-selector-item-image"
					v-if="pic(item)" 
					:src="pic(item)"
				>
				<div class="b24-form-control-list-selector-item-title">
					<span >{{ item.label }}</span>
				</div>
	
				<div class="b24-form-control-list-selector-item-price">
					<div class="b24-form-control-list-selector-item-price-old"
						v-if="item.discount"
						v-html="field.formatMoney(item.price + item.discount)"
					></div>
					<div class="b24-form-control-list-selector-item-price-current"
						v-if="item.price || item.price === 0"
						v-html="field.formatMoney(item.price)"
					></div> 
				</div>
			</div>
		</div>
	`,
	computed: {

	},
	methods: {
		pic(item)
		{
			return (
				item
				&& item.pics
				&& item.pics.length > 0
			) ? item.pics[0] : '';
		},
		selectItem (item)
		{
			this.$emit('select', item);
		}
	}
};

const fieldListMixin = {
	props: ['field'],
	mixins: [Mixins.MixinField, Mixins.MixinDropDown],
	components: {
		'item-selector': ItemSelector,
	},
	methods: {
		toggleSelector()
		{
			if (this.field.unselectedItem())
			{
				this.toggleDropDown();
			}
		},
		select(item)
		{
			this.closeDropDown();
			let select = () => {
				if (this.item)
				{
					this.item.selected = false;
				}
				item.selected = true;
			};
			if (this.item && this.item.selected)
			{
				select();
			}
			else
			{
				setTimeout(select, 300);
			}
		},
		unselect: function () {
			this.item.selected = false;
		},
	}
};

const FieldListItem = {
	mixins: [fieldListMixin],
	props: ['field', 'item', 'itemSubComponent'],
	template: `
		<div class="b24-form-control-container b24-form-control-icon-after"
			@click.self="toggleSelector"
		>
			<input readonly="" type="text" class="b24-form-control"
				:value="itemLabel"
				:class="classes"
				@click.capture="toggleSelector"
				@keydown.capture.space.stop.prevent="toggleSelector"
			>
			<div class="b24-form-control-label">
				{{ field.label }}
				<span v-show="field.required" class="b24-form-control-required">*</span>
			</div>
			<div class="b24-form-icon-after b24-form-icon-remove"
				v-if="item.selected"
				@click.capture="unselect"
				:title="field.messages.get('fieldListUnselect')"
			></div>
			<field-item-alert v-bind:field="field"></field-item-alert>
			<field-item-dropdown 
				:marginTop="0" 
				:visible="dropDownOpened"
				:title="field.label"
				@close="closeDropDown()"
				@visible:on="$emit('visible:on')"
				@visible:off="$emit('visible:off')"
			>
				<item-selector
					:field="field"
					@select="select"
				></item-selector>
			</field-item-dropdown>
			<field-item-image-slider 
				v-if="item.selected && field.bigPic" 
				:field="field" 
				:item="item"
			></field-item-image-slider>
			<component v-if="item.selected && itemSubComponent" :is="itemSubComponent"
				:key="field.id"
				:field="field"
				:item="item"
			></component>
		</div>
	`,
	computed: {
		itemLabel()
		{
			if (!this.item || !this.item.selected)
			{
				return '';
			}

			return this.item.label;
		},
		classes()
		{
			let list = [];

			if (this.itemLabel)
			{
				list.push('b24-form-control-not-empty');
			}

			return list;
		},
	},
	methods: {

	}
};

const FieldList = {
	mixins: [fieldListMixin],
	components: {
		'field-list-item': FieldListItem
	},
	template: `
		<div>
			<field-list-item
				v-for="(item, itemIndex) in getItems()"
				:key="itemIndex"
				:field="field"
				:item="item"
				:itemSubComponent="itemSubComponent"
				@visible:on="$emit('input-focus')"
				@visible:off="$emit('input-blur')"
			></field-list-item>
						
			<a class="b24-form-control-add-btn"
				v-if="isAddVisible()"
				@click="toggleSelector"
			>
				{{ field.messages.get('fieldAdd') }}
			</a>
			<field-item-dropdown 
				:marginTop="0" 
				:visible="dropDownOpened"
				:title="field.label"
				@close="closeDropDown()"
				@visible:on="$emit('input-focus')"
				@visible:off="$emit('input-blur')"
			>
				<item-selector
					:field="field"
					@select="select"
				></item-selector>
			</field-item-dropdown>
		</div>
	`,
	computed: {
		itemSubComponent ()
		{
			return null;
		}
	},
	methods: {
		getItems()
		{
			return this.field.selectedItem()
				? this.field.selectedItems()
				: (this.field.item() ? [this.field.item()] : []);
		},
		isAddVisible()
		{
			return this.field.multiple
				&& this.field.item()
				&& this.field.selectedItem()
				&& this.field.unselectedItem();
		},
	}
};

export {
	FieldListItem,
	FieldList,
}