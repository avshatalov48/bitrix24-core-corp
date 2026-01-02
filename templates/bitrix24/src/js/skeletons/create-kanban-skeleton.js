import { Tag } from 'main.core';

import '../../css/skeletons/composite-kanban-skeleton.css';

export const createKanbanSkeleton = (): HTMLElement => {
	return Tag.render`
		<div class="kanban-skeleton --stage-right">
			<div class="kanban-skeleton__col">
				<div class="kanban-skeleton__col-stage"></div>
				<div class="kanban-skeleton__col-tiles">
					<div class="kanban-skeleton__col-tile --lg"></div>
					<div class="kanban-skeleton__col-tile"></div>
				</div>
			</div>
			<div class="kanban-skeleton__col">
				<div class="kanban-skeleton__col-stage"></div>
				<div class="kanban-skeleton__col-tiles">
					<div class="kanban-skeleton__col-tile"></div>
					<div class="kanban-skeleton__col-tile --lg"></div>
				</div>
			</div>
			<div class="kanban-skeleton__col">
				<div class="kanban-skeleton__col-stage"></div>
				<div class="kanban-skeleton__col-tiles">
					<div class="kanban-skeleton__col-tile --lg"></div>
					<div class="kanban-skeleton__col-tile --lg"></div>
				</div>
			</div>
			<div class="kanban-skeleton__col">
				<div class="kanban-skeleton__col-stage"></div>
				<div class="kanban-skeleton__col-tiles">
					<div class="kanban-skeleton__col-tile --lg"></div>
					<div class="kanban-skeleton__col-tile"></div>
				</div>
			</div>
			<div class="kanban-skeleton__col">
				<div class="kanban-skeleton__col-stage"></div>
				<div class="kanban-skeleton__col-tiles">
					<div class="kanban-skeleton__col-tile --lg"></div>
					<div class="kanban-skeleton__col-tile --lg"></div>
				</div>
			</div>
			<div class="kanban-skeleton__col">
				<div class="kanban-skeleton__col-stage"></div>
				<div class="kanban-skeleton__col-tiles">
					<div class="kanban-skeleton__col-tile"></div>
					<div class="kanban-skeleton__col-tile --lg"></div>
				</div>
			</div>
		</div>
	`;
};
