import "./css/field.css";
import "./css/animation.css";

const Field = {
	props: ['field'],
	components: {
	},
	template: `
		<transition name="b24-form-field-a-slide">
			<div class="b24-form-field"
				:class="classes"
				v-show="field.visible"
			>
				<div v-if="field.isComponentDuplicable">
				<transition-group name="b24-form-field-a-slide" tag="div">
					<component v-bind:is="field.getComponentName()"
						v-for="(item, itemIndex) in field.items"
						v-bind:key="field.id"
						v-bind:field="field"
						v-bind:itemIndex="itemIndex"
						v-bind:item="item"
						@input-blur="onBlur"
						@input-focus="onFocus"
						@input-key-down="onKeyDown"
					></component>
				</transition-group>	
					<a class="b24-form-control-add-btn"
						v-if="field.multiple"
						@click="addItem"
					>
						{{ field.messages.get('fieldAdd') }}
					</a>	
				</div>
				<div v-if="!field.isComponentDuplicable">
					<component v-bind:is="field.getComponentName()"
						v-bind:key="field.id"
						v-bind:field="field"
						@input-blur="onBlur"
						@input-focus="onFocus"
						@input-key-down="onKeyDown"
					></component>		
				</div>
			</div>
		</transition>
	`,
	computed: {
		classes()
		{
			let list = [
				'b24-form-field-' + this.field.type,
				'b24-form-control-' + this.field.getOriginalType(),
			];
			/*
			if (this.field.design.dark)
			{
				list.push('b24-form-field-dark');
			}
			*/
			if (this.field.multiple)
			{
				list.push('b24-form-control-group');
			}
			if (this.hasErrors)
			{
				list.push('b24-form-control-alert');
			}
			return list;
		},
		hasErrors()
		{
			return this.field.validated
				&& !this.field.focused
				&& !this.field.valid();
		},
	},
	methods: {
		addItem() {
			this.field.addItem({});
		},
		onFocus()
		{
			this.field.focused = true;
		},
		onBlur()
		{
			this.field.focused = false;
			this.field.valid();
		},
		onKeyDown(e)
		{
			let value = e.key;
			if (this.field.filter(value))
			{
				return;
			}
			if (e.key === 'Esc' || e.key === 'Delete' || e.key === 'Backspace')
			{
				return;
			}

			e.preventDefault();
		},
	}
};


export {
	Field,
}