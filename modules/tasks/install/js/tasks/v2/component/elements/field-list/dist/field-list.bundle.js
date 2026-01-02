/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports) {
	'use strict';

	// eslint-disable-next-line no-unused-vars

	// @vue/component
	const FieldList = {
	  name: 'FieldList',
	  props: {
	    /** @type Field[] */
	    fields: {
	      type: Array,
	      required: true
	    }
	  },
	  template: `
		<div class="b24-field-list">
			<template v-for="(field, index) in fields" :key="index">
				<div class="b24-field-list-title" :class="{ '--with-separator': field.withSeparator }">
					<div class="b24-field-list-title-text">{{ field.title }}</div>
				</div>
				<div class="b24-field-list-value" :class="{ '--with-separator': field.withSeparator }">
					<component :is="field.component" v-bind="field.props"></component>
				</div>
			</template>
		</div>
	`
	};

	exports.FieldList = FieldList;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {})));
//# sourceMappingURL=field-list.bundle.js.map
