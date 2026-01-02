import { Tag } from 'main.core';

import '../../css/skeletons/composite-toolbar-skeleton.css';

export type ToolbarSkeletonOptions = {
	showIconButton?: boolean;
};

export const createToolbarSkeleton = (options: ToolbarSkeletonOptions = {}): HTMLElement => {
	const { showIconButton = false } = options;

	return Tag.render`
		<div class="toolbar-skeleton">
			<span class="toolbar-skeleton__page-title"></span>
			${showIconButton ? createIconButton() : null}
		</div>
	`;
};

function createIconButton(): HTMLElement
{
	return Tag.render`
		<span class="toolbar-skeleton__icon-buttons">
			<span class="toolbar-skeleton__icon-button">
				<span class="toolbar-skeleton__icon-button-text"></span>
			</span>
		</span>
	`;
}
