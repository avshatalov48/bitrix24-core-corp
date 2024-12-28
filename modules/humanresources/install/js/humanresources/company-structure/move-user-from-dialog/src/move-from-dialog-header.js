import { Event, Tag, Loc } from 'main.core';
import { BaseHeader } from 'ui.entity-selector';

export class MoveFromDialogHeader extends BaseHeader
{
	render(): HTMLElement
	{
		const { header, headerCloseButton } = Tag.render`
			<div ref="header" class="hr-move-user-from-dialog__header">
				<div ref="headerCloseButton" class="hr-move-user-from-dialog__header-close_button"></div>
				<div class="hr-move-user-from__header-text-container">
					<span class="hr-move-user-from-dialog__header-title">
						${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_TITLE')}
					</span>
					<span class="hr-move-user-from-dialog__header-description">
						${Loc.getMessage('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_MOVE_USER_FROM_DIALOG_DESCRIPTION')}
					</span>
				</div>
			</div>
		`;

		Event.bind(headerCloseButton, 'click', (event) => {
			this.getDialog().hide();
		});

		return header;
	}
}
