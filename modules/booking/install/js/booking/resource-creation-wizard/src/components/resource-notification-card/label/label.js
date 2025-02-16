import 'ui.label';

export const replaceLabelMixin = {
	methods: {
		getLabel(text: string, isChecked: boolean, hint: string = ''): string
		{
			const uiLabelStyle = isChecked
				? 'ui-label ui-label-tag-secondary notification-label --active'
				: 'ui-label ui-label-tag-light ui-label-fill notification-label'
			;

			return `
				<div data-hint="${hint}" data-hint-no-icon class="${uiLabelStyle}">
					<div class="ui-label-status"></div>
					<span class="ui-label-inner">${text}</span>
				</div>
			`;
		},
	},
};
