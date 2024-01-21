export type RecentSearchModelState = {
	id: string| number,
	dateMessage: Date,
}

export type RecentSearchModelActions =
	'recentModel/searchModel/set'
	| 'recentModel/searchModel/clear'

export type RecentSearchModelMutation =
	'recentModel/searchModel/set'
	| 'recentModel/searchModel/clear'