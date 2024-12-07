import { Base } from './base';
import { Tag } from 'main.core';
import { Icon, Actions } from 'ui.icon-set.api.core';
import 'ui.icon-set.actions';

import '../css/ui/icon-close.css';

export class IconClose extends Base
{
	render(): HTMLElement
	{
		const icon = new Icon({
			icon: Actions.CROSS_40,
			size: 24,
		});

		return Tag.render`
			<div class="ai__picker_icon-close" onclick="${this.props.onClick}">${icon.render()}</div>
		`;
	}
}
