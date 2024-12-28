<?php

return [
	'extensions' => [
		'loc',
		'selector/widget/entity/tree-selectors/directory-selector',

		'statemanager/redux/store',
		'disk:statemanager/redux/slices/files/selector',
		'disk:statemanager/redux/slices/files/thunk',
		'disk:statemanager/redux/slices/storages',

		'toast',
		'disk:opener/folder',
		'disk:rights',
	],
];