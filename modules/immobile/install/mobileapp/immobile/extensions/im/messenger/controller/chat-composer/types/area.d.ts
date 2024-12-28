import { AvatarType } from './element';

declare type EntityInfoProps = {
	title: {
		placeholder: string,
		value: string,
		onChange: (EntityInfoChangeEvent) => void,
		onFocusTitle: (EntityInfoFocusEvent) => void,
	},
	avatar: {
		type: AvatarType,
		preview: string,
		onChange: (params: { avatar: string, preview: string }) => void,
		canClick?: boolean,
	},
	description?: {
		placeholder: string,
		value: string,
		onChange: (EntityInfoChangeEvent) => void,
		onFocusDesc: (EntityInfoFocusEvent) => void,
	},
	shouldForceUpdateState?: boolean
};

declare type EntityInfoState = {
	title: string,
	description: string,
	isInputChanged: boolean,
}

declare type EntityInfoChangeEvent = {
	title: string,
	description: string,
	isInputChanged: boolean,
	inputRef: LayoutComponent
}

declare type EntityInfoFocusEvent = {
	inputRef: LayoutComponent
}

declare type SettingsPanelAction = {
	testId: string,
	title: string,
	subtitle: string,
	icon: Icon,
	divider?: boolean,
	onClick: () => void,
}

declare type SettingsPanelProps = {
	actionList: Array<SettingsPanelAction>
}

declare type ParticipantsListProps = {
	items: Array<>
}

declare type NestedDepartmentSelectorItemId = number | `${number}:F`;

declare type NestedDepartmentSelectorItem = {
	id: NestedDepartmentSelectorItemId,
	type: 'user' | 'department',
	title: string,
	imageUrl: string,
	customData: UserCustomData & DepartmentCustomData
}

declare type UserCustomData = {
	email: string,
	lastName: string,
	login: string,
	name: string,
	position: '',
	sourceEntity: {
		avatar: string,
		contextSort: number,
		customData: object,
		entityId: string,
		entityType: string,
		globalSort: number,
		id: number,
		tabs: ['user'],
		title: string,
	}
}

declare type DepartmentCustomData = {
	userCount: number | null,
	subdepartmentsCount: number | null,
	sourceEntity: {
		children: null,
		contextSort: number,
		customData: object,
		entityId: string,
		id: NestedDepartmentSelectorItemId,
		nodeOptions: object,
		shortTitle: string,
		tabs: ['departments'],
		title: string,
		type: string,
		typeIconFrame,
	}
}
