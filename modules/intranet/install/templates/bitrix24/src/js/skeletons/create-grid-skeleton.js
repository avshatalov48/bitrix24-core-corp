import { Tag } from 'main.core';

import '../../css/skeletons/composite-grid-skeleton.css';

export const createGridSkeleton = (): HTMLElement => {
	return Tag.render`
		<div class="grid-skeleton-container --ui-context-content-light">
			<table class="grid-skeleton">
				<thead>
					<tr class="grid-skeleton__header-row">
						<th class="grid-skeleton__header-cell">
							<div class="grid-skeleton__checkbox"></div>
						</th>
						<th class="grid-skeleton__header-cell">
							<div class="grid-skeleton__avatar"></div>
						</th>
						<th class="grid-skeleton__header-cell">
							<div class="grid-skeleton__cell-title --short"></div>
						</th>
						<th class="grid-skeleton__header-cell">
							<div class="grid-skeleton__cell-title"></div>
						</th>
						<th class="grid-skeleton__header-cell">
							<div class="grid-skeleton__cell-title"></div>
						</th>
						<th class="grid-skeleton__header-cell">
							<div class="grid-skeleton__cell-title"></div>
						</th>
						<th class="grid-skeleton__header-cell">
							<div class="grid-skeleton__cell-title"></div>
						</th>
					</tr>
				</thead>
				${getGridSkeletonRows()}
			</table>
		</div>
	`;
};

function getGridSkeletonRows(): HTMLElement[]
{
	return Tag.render`
		<tbody>
			${createGridSkeletonRow()}
			${createGridSkeletonRow()}
			${createGridSkeletonRow()}
			${createGridSkeletonRow()}
			${createGridSkeletonRow()}
			${createGridSkeletonRow()}
			${createGridSkeletonRow()}
		</tbody>
	`;
}

function createGridSkeletonRow(): HTMLElement
{
	return Tag.render`
		<tr class="grid-skeleton__row">
			<td class="grid-skeleton__cell">
				<div class="grid-skeleton__checkbox"></div>
			</td>
			<td class="grid-skeleton__cell">
				<div class="grid-skeleton__avatar"></div>
			</td>
			<td class="grid-skeleton__cell">
				<div class="grid-skeleton__cell-two-text">
					<div class="grid-skeleton__cell-title --long"></div>
					<div class="grid-skeleton__cell-title --short"></div>
				</div>
			</td>
			<td class="grid-skeleton__cell">
				<div class="grid-skeleton__cell-button"></div>
			</td>
			<td class="grid-skeleton__cell">
				<div class="grid-skeleton__avatar-title">
					<div class="grid-skeleton__avatar"></div>
					<div class="grid-skeleton__cell-title"></div>
				</div>
			</td>
			<td class="grid-skeleton__cell">
				<div class="grid-skeleton__avatar-title">
					<div class="grid-skeleton__avatar"></div>
					<div class="grid-skeleton__cell-title"></div>
				</div>
			</td>
			<td class="grid-skeleton__cell">
				<div class="grid-skeleton__avatar-title">
					<div class="grid-skeleton__avatar"></div>
					<div class="grid-skeleton__cell-title"></div>
				</div>
			</td>
		</tr>
	`;
}
