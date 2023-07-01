<?
use Bitrix\Main\Security\Sign\Signer;

use Bitrix\Tasks\Util\Type\ArrayOption;
use Bitrix\Tasks\Util\Type\StructureChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$helper = new \Bitrix\Tasks\UI\Component\TemplateHelper('TasksWidgetFrameEditForm', (isset($this)? $this : null), array(
	'RELATION' => array('tasks_util', /*etc*/),
));

// ajax action: set option value
$helper->addMethod('templateActionSetState', function($structure, $value) use ($helper)
{
	$result = new \Bitrix\Tasks\Util\Result();

	try
	{
		$signer = new Signer();
		$structure = unserialize($signer->unsign($structure, 'tasks.widget.frame.structure'), ['allowed_classes' => false]);
	}
	catch(\Bitrix\Main\Security\Sign\BadSignatureException $e)
	{
		$result->addError('SIGNATURE_FAILURE', 'Signature failure');
	}

	if($result->isSuccess())
	{
		$helper->getStateInstance($structure)->set($value);
	}

	return $result;
});

// generate option structure by parameters
$helper->addMethod('makeStructure', function() use ($helper) {
	$arParams = $helper->getComponent()->arParams;

	$blocks = array();
	$flags = array(
		'FORM_FOOTER_PIN' => array(),
	);

	$categoryNames = array('HEAD_BOTTOM', 'STATIC', 'DYNAMIC');
	foreach($categoryNames as $name)
	{
		if(!is_array($arParams['BLOCKS'][$name]))
		{
			continue;
		}

		foreach($arParams['BLOCKS'][$name] as $block)
		{
			$blocks[$block['CODE']] = [];

			if (is_array($block['SUB'] ?? null))
			{
				foreach ($block['SUB'] as $subBlock)
				{
					$blocks[$subBlock['CODE']] = [];
				}
			}

		}
	}

	return array(
		'ID' => $arParams['FRAME_ID'],
		'BLOCKS' => $blocks,
		'FLAGS' => $flags,
	);
});

// sign option structure to pass to client
$helper->addMethod('signStructure', function($structure){
	$signer = new Signer();
	return $signer->sign(serialize($structure), 'tasks.widget.frame.structure');
});

// create option controller instance
$helper->addMethod('getStateInstance', function($structure)  use ($helper)
{
	$id = trim((string) $structure['ID']);
	if(!$id)
	{
		return null;
	}

	$blocks = array();
	foreach($structure['BLOCKS'] as $name => $desc)
	{
		$blocks[$name] = array('VALUE' => array(
			'PINNED' => array('VALUE' => StructureChecker::TYPE_BOOLEAN, 'DEFAULT' => false),
		), 'DEFAULT' => array());
	}

	$flags = array();
	foreach($structure['FLAGS'] as $name => $desc)
	{
		$flags[$name] = array('VALUE' => StructureChecker::TYPE_BOOLEAN, 'DEFAULT' => array());
	}

	return new ArrayOption(
		$id,
		array(
			'BLOCKS' => array('VALUE' => $blocks, 'DEFAULT' => array()),
			'FLAGS' => array('VALUE' => $flags, 'DEFAULT' => array())
		)
	);
});

return $helper;