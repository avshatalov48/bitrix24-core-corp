import { EntityTypes } from './consts';

/**
 * Type for color picker and structure
 */
export type NodeColorSettingsType = {
	name: string,
	pickerColor: string,
	headBackground: string,
	treeHeadBackground: string,
	namePlaceholder: string,
	headPlaceholder: string,
	previewBorder: string;
	bubbleBackground: string,
	focusedBorderColor: string,
	expandedBorderColor: string,
	avatarImage: string,
}

export type NodeColorNameType = 'blue' | 'green' | 'cyan' | 'orange' | 'purple' | 'pink';

export const NodeColorsSettingsDict: Record<NodeColorNameType, NodeColorSettingsType> = Object.freeze({
	blue: {
		name: 'blue',
		pickerColor: 'rgba(0, 117, 255, 1)',
		headBackground: 'rgba(0, 117, 255, 0.23)',
		treeHeadBackground: 'rgba(0, 117, 255, 0.23)',
		namePlaceholder: 'rgba(0, 117, 255, 0.12)',
		headPlaceholder: 'rgba(0, 117, 255, 0.15)',
		previewBorder: 'rgba(0, 117, 255, 0.5)',
		bubbleBackground: 'rgba(80, 156, 252, 1)',
		focusedBorderColor: 'rgba(0, 117, 255, 0.25)',
		expandedBorderColor: 'rgba(77, 158, 255, 1)',
		avatarImage: 'placeholder-avatar-blue.svg',
	},
	green: {
		name: 'green',
		pickerColor: 'rgba(22, 221, 154, 1)',
		headBackground: 'rgba(22, 221, 154, 0.23)',
		treeHeadBackground: 'rgba(22, 221, 154, 0.27)',
		namePlaceholder: 'rgba(22, 221, 154, 0.23)',
		headPlaceholder: 'rgba(22, 221, 154, 0.2)',
		previewBorder: 'rgba(22, 221, 154, 0.5)',
		bubbleBackground: 'rgba(22, 221, 154, 1)',
		focusedBorderColor: 'rgba(22, 221, 154, 0.25)',
		expandedBorderColor: 'rgba(22, 221, 154, 1)',
		avatarImage: 'placeholder-avatar-green.svg',
	},
	cyan: {
		name: 'cyan',
		pickerColor: 'rgba(25, 202, 212, 1)',
		headBackground: 'rgba(25, 202, 212, 0.23)',
		treeHeadBackground: 'rgba(25, 202, 212, 0.27)',
		namePlaceholder: 'rgba(25, 202, 212, 0.23)',
		headPlaceholder: 'rgba(25, 202, 212, 0.2)',
		previewBorder: 'rgba(25, 202, 212, 0.5)',
		bubbleBackground: 'rgba(25, 202, 212, 1)',
		focusedBorderColor: 'rgba(25, 202, 212, 0.25)',
		expandedBorderColor: 'rgba(25, 202, 212, 1)',
		avatarImage: 'placeholder-avatar-cyan.svg',
	},
	orange: {
		name: 'orange',
		pickerColor: 'rgba(249, 172, 22, 1)',
		headBackground: 'rgba(249, 172, 22, 0.23)',
		treeHeadBackground: 'rgba(249, 172, 22, 0.27)',
		namePlaceholder: 'rgba(249, 172, 22, 0.23)',
		headPlaceholder: 'rgba(249, 172, 22, 0.2)',
		previewBorder: 'rgba(249, 172, 22, 0.5)',
		bubbleBackground: 'rgba(249, 172, 22, 1)',
		focusedBorderColor: 'rgba(249, 172, 22, 0.25)',
		expandedBorderColor: 'rgba(249, 172, 22, 1)',
		avatarImage: 'placeholder-avatar-orange.svg',
	},
	purple: {
		name: 'purple',
		pickerColor: 'rgba(162, 139, 255, 1)',
		headBackground: 'rgba(162, 139, 255, 0.23)',
		treeHeadBackground: 'rgba(162, 139, 255, 0.27)',
		namePlaceholder: 'rgba(162, 139, 255, 0.23)',
		headPlaceholder: 'rgba(162, 139, 255, 0.2)',
		previewBorder: 'rgba(162, 139, 255, 0.5)',
		bubbleBackground: 'rgba(162, 139, 255, 1)',
		focusedBorderColor: 'rgba(162, 139, 255, 0.25)',
		expandedBorderColor: 'rgba(162, 139, 255, 1)',
		avatarImage: 'placeholder-avatar-purple.svg',
	},
	pink: {
		name: 'pink',
		pickerColor: 'rgba(242, 126, 189, 1)',
		headBackground: 'rgba(242, 126, 189, 0.23)',
		treeHeadBackground: 'rgba(242, 126, 189, 0.27)',
		namePlaceholder: 'rgba(242, 126, 189, 0.23)',
		headPlaceholder: 'rgba(242, 126, 189, 0.2)',
		previewBorder: 'rgba(242, 126, 189, 0.5)',
		bubbleBackground: 'rgba(242, 126, 189, 1)',
		focusedBorderColor: 'rgba(242, 126, 189, 0.25)',
		expandedBorderColor: 'rgba(242, 126, 189, 1)',
		avatarImage: 'placeholder-avatar-pink.svg',
	},
});

export const getNodeColorSettings = (colorName: string, entityType: string): ?NodeColorSettingsType => {
	if (entityType !== EntityTypes.team)
	{
		return null;
	}

	return NodeColorsSettingsDict[colorName] ?? NodeColorsSettingsDict.blue;
};
