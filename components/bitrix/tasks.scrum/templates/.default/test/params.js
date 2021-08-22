export default function getInputParams(entityName: string, seed: number): Object
{
	const views = {
		plan: {
			name: 'Planning',
			url: '/plan',
			active: true
		},
		activeSprint: {
			name: 'Active sprint',
			url: '/activeSprint',
			active: true
		},
		completedSprint: {
			name: 'Completed sprint',
			url: '/completedSprint',
			active: true
		},
	};

	switch (entityName)
	{
		case 'EmptyEntity':
			return {
				id: 1,
				numberTasks: 0,
				isExactSearchApplied: 'N'
			};
		case 'EmptyBacklog':
			return {
				id: 2,
				items: []
			};
		case 'ActiveSprint':
			return {
				id: 3,
				name: '::name::',
				sort: 1,
				dateStart: 1596723266,
				dateEnd: 1597881600,
				weekendDaysTime: (2 * 86400),
				defaultSprintDuration: 604800,
				status: 'active',
				storyPoints: 0,
				completedStoryPoints: 0,
				uncompletedStoryPoints: 0,
				completedTasks: 0,
				uncompletedTasks: 0,
				items: [],
				info: {
					sprintGoal: 'goal text'
				},
				views: views
			};
		case 'PlannedSprint':
			return {
				id: 4,
				name: '::name::',
				sort: 1,
				dateStart: 1596723266,
				dateEnd: 1597881600,
				weekendDaysTime: (2 * 86400),
				defaultSprintDuration: 604800,
				status: 'planned',
				storyPoints: 0,
				completedStoryPoints: 0,
				uncompletedStoryPoints: 0,
				completedTasks: 0,
				uncompletedTasks: 0,
				items: [],
				info: {
					sprintGoal: 'goal text'
				},
				views: views
			};
		case 'CompletedSprint':
			return {
				id: 5,
				name: '::name::',
				sort: 1,
				dateStart: 1596723266,
				dateEnd: 1597881600,
				weekendDaysTime: (2 * 86400),
				defaultSprintDuration: 604800,
				status: 'completed',
				storyPoints: 3,
				completedStoryPoints: 1,
				uncompletedStoryPoints: 2,
				completedTasks: 1,
				uncompletedTasks: 2,
				items: [],
				info: {
					sprintGoal: 'goal text'
				},
				views: views
			};
		case 'SimpleItem':
			return {
				itemId: seed ? 1 + seed : 1,
				name: '::name::',
				sort: seed ? 1 + seed : 1,
				storyPoints: seed ? 3 + seed : 3,
			};
		case 'Item':
			return {
				itemId: 7,
				name: '::name::',
				itemType: 'task',
				sort: 1,
				entityId: 1,
				entityType: 'backlog',
				parentId: 0,
				sourceId: 1,
				responsible: {
					id: 1,
					name: '::username::',
					pathToUser: '/',
					photo: {
						src: '::avatar/path::'
					}
				},
				storyPoints: '3',
				completed: 'N',
				allowedActions:  {
					task_edit: true,
					task_remove: true,
				}
			};
	}
}