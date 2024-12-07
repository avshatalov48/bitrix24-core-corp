import { BaseField } from './base-field';
import { Dom, Tag } from 'main.core';

export type PhotoFieldType = {
	photoUrl: string,
	isInvited: boolean,
	isConfirmed: boolean,
}

export class PhotoField extends BaseField
{
	render(params: PhotoFieldType): void
	{
		Dom.addClass(this.getFieldNode(), 'user-grid_user-photo');

		if (params.photoUrl)
		{
			Dom.style(this.getFieldNode(), { 'background-image': `url("${params.photoUrl}")` });
		}
		else
		{
			const iconClass = (params.isInvited || (!params.isConfirmed && params.isInvited))
				? '--person-clock'
				: '--person';

			const photo = Tag.render`
				<div class="ui-icon-set ${iconClass}" style="--ui-icon-set__icon-size: 35px; --ui-icon-set__icon-color: #fff;"></div>
			`;

			Dom.append(photo, this.getFieldNode());
		}
	}
}