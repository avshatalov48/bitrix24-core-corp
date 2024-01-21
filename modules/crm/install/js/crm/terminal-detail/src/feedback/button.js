BX.namespace("BX.Crm");

import {Loc, Tag} from 'main.core';
import 'ui.feedback.form';

export default class Button
{
	static render(parentNode) {
		const buttonTitle = main_core.Loc.getMessage('CRM_FEEDBACK_BUTTON_TITLE');
		const button = Tag.render`
			<button class="ui-btn ui-btn-light-border ui-btn-themes" title="${buttonTitle}">
				<span class="ui-btn-text">
					${buttonTitle}
				</span>
			</button>
		`;

		button.addEventListener('click', () => {
			BX.Crm.Terminal.Slider.openFeedbackForm();
		});

		if (!parentNode)
		{
			return;
		}

		parentNode.appendChild(button);
		parentNode.style.justifyContent = 'space-between';

		return button;
	}
}
