import { Dom, Tag } from 'main.core';

import '../../css/skeletons/composite-actions-bar-skeleton.css';

export type ActionsBarSkeletonOptions = {
	showNavigationPanel?: boolean;
	showCounterPanel?: boolean;
	rightButtonsCount?: boolean;
};

export const createActionsBarSkeleton = (options: ActionsBarSkeletonOptions = {}): HTMLElement => {
	const { showNavigationPanel = true, showCounterPanel = false, rightButtonsCount = 0 } = options;

	return Tag.render`
		<div class="actions-bar-skeleton">
			${showNavigationPanel ? createNavigationPanelSkeleton() : null}
			${showCounterPanel ? createCounterPanelSkeleton() : null}
			${rightButtonsCount > 0 ? createRightButtons(rightButtonsCount) : null}
		</div>
	`;
};

function createNavigationPanelSkeleton(): HTMLElement
{
	return Tag.render`
		<div class="navigation-skeleton">
				<div class="navigation-skeleton__item">
					<div class="navigation-skeleton__item-text"></div>
				</div>
			</div>
	`;
}

function createCounterPanelSkeleton(): HTMLElement
{
	return Tag.render`
		<div class="counters-skeleton">
			<div class="counters-skeleton__item">
				<div class="counters-skeleton__item-text"></div>
			</div>
		</div>
	`;
}

function createRightButtons(count: number): HTMLElement
{
	const wrapper = Tag.render`<div class="actions-bar-skeleton__right-buttons"></div>`;

	for (let i = 0; i < count; i++)
	{
		const button = Tag.render`
			<div class="actions-bar-skeleton__right-button">
				<div class="actions-bar-skeleton__right-button-text"></div>
			</div>
		`;

		Dom.append(button, wrapper);
	}

	return wrapper;
}
