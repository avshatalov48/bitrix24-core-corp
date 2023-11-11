export type RecentSearchModelState = {
	id: string| number,
	date_update: Date,
}

export type RecentSearchModelActions =
	'recentModel/searchModel/set'
	| 'recentModel/searchModel/clear'

export type RecentSearchModelMutation =
	'recentModel/searchModel/set'
	| 'recentModel/searchModel/clear'