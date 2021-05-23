<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

// create template controller with js-dependency injections
$helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, [
	'RELATION' => [
		'tasks_util',
		'tasks_util_draganddrop',
		'tasks_util_template',
	],
	'METHODS' => [],
]);

/**
you can add methods here and call them from the js controller like this:
this.callRemoteTemplate('doSmth', {some: 'argument'}}).then(function(result){
if(result.isSuccess())
{
console.dir(result.getData());
}
else
{
console.dir(result.getErrors());
}
}.bind(this));
 */

// method name should start from 'templateAction' prefix to be accessible using ajax. also make sure arguments are secure
//$helper->addMethod('templateActionDoSmth', function($some) use ($helper)
//{
//	// do smth
//});

return $helper;