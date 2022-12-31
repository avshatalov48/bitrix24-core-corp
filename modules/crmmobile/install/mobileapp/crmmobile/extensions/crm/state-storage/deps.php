<?php
return [
	'extensions' => [
		'statemanager/vuex',
		'statemanager/vuex-manager',
		'utils/object',
		'crm:ajax',
	],
	'bundle' => [
		'./manager/base',

		'./model/category-counters',
		'./manager/category-counters',

		'./model/activity-counters',
		'./manager/activity-counters',
	],
];
