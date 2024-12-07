import { Tag } from 'main.core';
import { Label } from 'ui.label';

type PromptCategoryItem = {
	name: string;
	code: string;
};

export class PopupCategoriesRenderer
{
	render(list: PromptCategoryItem[]): HTMLElement
	{
		const listElements = list.map((item) => {
			const label = new Label({
				text: item.name,
				fill: true,
				color: Label.Color.LIGHT,
			});

			return Tag.render`<li>${label.render()}</li>`;
		});

		return Tag.render`<ul class="ai__categories-popup_list">${listElements}</ul>`;
	}
}
