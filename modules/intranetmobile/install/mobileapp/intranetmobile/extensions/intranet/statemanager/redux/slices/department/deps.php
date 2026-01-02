<?php

return [
	'extensions' => [
		'intranet:statemanager/redux/slices/department/meta',
		'intranet:statemanager/redux/slices/department/thunk',
		'intranet:statemanager/redux/slices/department/model/department',
		'type',
		'statemanager/redux/reducer-registry',
		'statemanager/redux/toolkit',
		'statemanager/redux/state-cache',
	],
	'bundle' => [
		'./model/department/exstension',
	],
];
