import { BaseField } from './base-field';
import { Tag, Text, Dom } from 'main.core';
import { PhotoField } from './photo-field';
import { FullNameField } from './full-name-field';

export type EmployeeFieldType = {
	fullName: string,
	profileLink: string,
	isConfirmed: boolean,
	isAdmin: boolean,
	position: ?string,
	isInvited: boolean,
	isIntegrator: boolean,
}

export class EmployeeField extends BaseField
{
	render(params: EmployeeFieldType): void
	{
		const photoFieldId = Text.getRandom(6);
		const fullNameFieldId = Text.getRandom(6);
		this.appendToFieldNode(Tag.render`<span id="${photoFieldId}"></span>`);
		this.appendToFieldNode(Tag.render`<span class="user-grid_full-name-wrapper" id="${fullNameFieldId}"></span>`);

		(new PhotoField({ fieldId: photoFieldId })).render(params);
		(new FullNameField({ fieldId: fullNameFieldId })).render(params);

		Dom.addClass(this.getFieldNode(), 'user-grid_employee-card-container');
	}
}