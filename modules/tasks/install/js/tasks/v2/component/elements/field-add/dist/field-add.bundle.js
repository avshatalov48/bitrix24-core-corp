/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
this.BX.Tasks.V2.Component = this.BX.Tasks.V2.Component || {};
(function (exports,ui_iconSet_api_vue,tasks_v2_component_elements_hoverPill) {
	'use strict';

	const FieldAdd = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    HoverPill: tasks_v2_component_elements_hoverPill.HoverPill
	  },
	  props: {
	    icon: {
	      type: String,
	      required: true
	    }
	  },
	  template: `
		<HoverPill>
			<div class="b24-field-add">
				<BIcon :name="icon"/>
				<div>{{ loc('TASKS_V2_FIELD_ADD') }}</div>
			</div>
		</HoverPill>
	`
	};

	exports.FieldAdd = FieldAdd;

}((this.BX.Tasks.V2.Component.Elements = this.BX.Tasks.V2.Component.Elements || {}),BX.UI.IconSet,BX.Tasks.V2.Component.Elements));
//# sourceMappingURL=field-add.bundle.js.map
