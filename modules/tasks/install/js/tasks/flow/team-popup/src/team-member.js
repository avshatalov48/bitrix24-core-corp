import { Tag, Text, Type } from 'main.core';

export type MemberData = {
	id: number,
	name: string,
	avatar: string,
	workPosition: string,
	pathToProfile: string,
};

export class TeamMember
{
	#data: MemberData;

	constructor(data: MemberData)
	{
		this.#data = data;
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__team-popup_member" data-id="tasks-flow-team-popup-member-${this.#data.id}">
				${this.#renderIcon()}
				<div class="tasks-flow__team-popup_member-name-content">
					<a class="tasks-flow__team-popup_member-name" href="${Text.encode(this.#data.pathToProfile)}">
						${Text.encode(this.#data.name)}
					</a>
					${this.#renderWorkPosition()}
				</div>
			</div>
		`;
	}

	#renderIcon(): HTMLElement
	{
		let photoIcon = '<i></i>';
		if (Type.isStringFilled(this.#data.avatar))
		{
			photoIcon = `<i style="background-image: url('${encodeURI(this.#data.avatar)}')"></i>`;
		}

		return Tag.render`
			<a href="${Text.encode(this.#data.pathToProfile)}">
				<div class="tasks-flow__team-popup_member-avatar ui-icon ui-icon-common-user">
					${photoIcon}
				</div>
			</a>
		`;
	}

	#renderWorkPosition(): HTMLElement|string
	{
		if (!Type.isStringFilled(this.#data.workPosition))
		{
			return '';
		}

		return Tag.render`
			<span class="tasks-flow__team-popup_member-position">
				${Text.encode(this.#data.workPosition)}
			</span>
		`;
	}
}
