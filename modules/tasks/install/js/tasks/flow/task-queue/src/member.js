import { Tag, Text } from 'main.core';

export type MemberData = {
	id: number,
	name: string,
	avatar: string,
	pathToProfile: string,
}

import './css/member.css';

export class Member
{
	#id: number;
	#name: string;
	#avatar: string;
	#workPosition: string;
	#pathToProfile: string;

	constructor(data: MemberData)
	{
		this.#id = parseInt(data.id, 10);
		this.#name = data.name;
		this.#avatar = data.avatar;
		this.#workPosition = data.workPosition;
		this.#pathToProfile = data.pathToProfile;
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-flow__task-queue-member" data-id="tasks-flow-task-queue-member-${this.getId()}">
				${this.#renderAvatar()}
			</div>
		`;
	}

	#renderAvatar(): HTMLElement
	{
		let photoIcon = '<i></i>';
		if (this.getAvatar())
		{
			photoIcon = `<i style="background-image: url('${encodeURI(this.getAvatar())}')"></i>`;
		}

		return Tag.render`
			<a 
				href="${Text.encode(this.getPathToProfile())}"
				class="tasks-flow__task-queue-member_avatar ui-icon ui-icon-common-user"
			>
				${photoIcon}
			</a>
		`;
	}

	getId(): number
	{
		return this.#id;
	}

	getName(): string
	{
		return this.#name;
	}

	getAvatar(): string
	{
		return this.#avatar;
	}

	getPathToProfile(): string
	{
		return this.#pathToProfile;
	}
}
