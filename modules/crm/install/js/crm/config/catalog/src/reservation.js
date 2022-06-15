export default {
	props: {
		settings: {
			type: Object,
			required: true
		},
	},
	data ()
	{
		let result = {};

		for (const element of this.settings.scheme)
		{
			result[element.code] = this.settings.values[element.code];
		}

		return result;
	},
	methods: {
		onChanged()
		{
			this.$emit('change', this.$data);
		},
		getWrapperClass(type)
		{
			return (type === 'option')
				? {
					'catalog-settings-editor-checkbox-content-block': true,
				}
				: {
					'catalog-settings-editor-content-block': true,
				};
		}
	},
	mounted()
	{
		BX.UI.Hint.init(this.$el);
	},
	template: `
		<div>
			<div
				v-for="setting in settings.scheme"
				:class="getWrapperClass(setting.type)"
			>
				<template v-if="setting.type === 'list'">
					<div class="ui-ctl-label-text">
						<label>{{setting.name}}</label>
					</div>
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select
							v-model="$data[setting.code]"
							@change="onChanged"
							:disabled="setting.disabled"
							class="ui-ctl-element"
						>
							<option v-for="value in setting.values" :value="value.code">
								{{value.name}}
							</option>						
						</select>
					</div>
				</template>
				<template v-if="setting.type === 'text'">
					<div class="ui-ctl-label-text">
						<label>{{setting.name}}</label>
					</div>
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							v-model="$data[setting.code]"
							@change="onChanged"
							:disabled="setting.disabled"
							type="text"
							class="ui-ctl-element"
						>
					</div>
				</template>
				<template v-if="setting.type === 'int'">
					<div class="ui-ctl-label-text">
						<label>{{setting.name}}</label>
					</div>
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							v-model="$data[setting.code]"
							@input="onChanged"
							:disabled="setting.disabled"
							type="text"
							class="ui-ctl-element"
						>
					</div>
				</template>
				<template v-if="setting.type === 'option'">
					<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
						<input
							v-model="$data[setting.code]"
							@change="onChanged"
							:id="setting.code + '_' + $vnode.key"
							:disabled="setting.disabled"
							type="checkbox"
							class="ui-ctl-element"
						>
						<label
							:for="setting.code + '_' + $vnode.key"
							class="ui-ctl-label-text"
						>
							{{setting.name}}
						</label>
						<span
							v-if="setting.description"
							class="ui-hint"
							:data-hint="setting.description"
						>
							<span class="ui-hint-icon"></span>
						</span>
					</div>
				</template>
			</div>
		</div>
	`
}
