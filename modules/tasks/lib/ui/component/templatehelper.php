<?
/**
 * This class contains ui helper for a component template
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Tasks\UI\Component;

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Error;

final class TemplateHelper
{
	private $template = null;
	private $id = '';
	private $name = 'ComponentTemplate';
	private $methods = array();
	private $runtimeActions = array();

	public function __construct($name, $template, array $parameters = array())
	{
		if(array_key_exists('METHODS', $parameters) && is_array($parameters['METHODS']))
		{
			foreach($parameters['METHODS'] as $methodName => $cb)
			{
				$this->addMethod($methodName, $cb);
			}
		}

		if($template instanceof \CBitrixComponentTemplate)
		{
			$this->template = $template;
			$this->id = $this->pickId();
			$this->name = trim((string) $name);
			if(!$this->name)
			{
				$this->name = preg_replace('#Component$#', '', $template->__component->getComponentClassName());
			}

			Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/'.$this->template->getFolder().'/js.php'); // make all js-messages available in php also

			$title = Loc::getMessage('TASKS_'.ToUpper($this->name).'_TEMPLATE_TITLE');
			if($title != '')
			{
				$this->setTitle($title);
			}

			if(!is_array($parameters['RELATION']))
			{
				$parameters['RELATION'] = array();
			}
			$this->registerExtension($parameters['RELATION']);

			// dispatch runtime
			if(!empty($this->runtimeActions))
			{
				$trigger = $this->getComponent()->getDispatcherTrigger();
				if($trigger)
				{
					$component = $this->getComponent();

					// attach runtime operations to the dispatcher
					$dispatcher = $component->getDispatcher();
					$dispatcher->addRuntimeActions($this->getRunTimeActions());

					// execute again
					$result = $dispatcher->run($trigger->find(array('~ACTION' => '#^runtime:templateAction#i')));
					if(!$result->getErrors()->isEmpty())
					{
						$component->getErrors()->load($result->getErrors()->transform(array('TYPE' => Error::TYPE_WARNING)));
					}
				}
			}
		}
	}

	/**
	 * Register a js-controller extension for this template
	 *
	 * @param array $relations
	 */
	public function registerExtension(array $relations = array())
	{
		\CJSCore::Init('tasks');
		\CJSCore::registerExt(
			$this->getExtensionId(),
			array(
				'js'  => $this->template->getFolder().'/logic.js',
				'rel' =>  array_merge($relations, array('tasks_component')),
				'lang' => $this->template->getFolder().'/lang/'.LANGUAGE_ID.'/js.php'
			)
		);
	}

	/**
	 * Initialize the js-controller for this template, using data from $arResult['JS_DATA'] as an input object of options.
	 * Each field in this object will be accessible via .option('fieldName') call inside js-controller.
	 *
	 * @param array $data
	 */
	public function initializeExtension(array $data = array())
	{
		\CJSCore::Init($this->getExtensionId());

		$component = $this->template->__component;

		$arResult = $component->arResult;
		$jsData = $arResult['JS_DATA'];

		$data = array_merge((is_array($jsData) ? $jsData : array()), $data, array(
			'id' => $this->id, // to register in dispatcher
			'url' => $component->__path.'/ajax.php',
			'viewUrl' => $this->template->getFolder().'/ajax.php',
			'componentClassName' => $component->getComponentClassName(),
			'componentId' => $component->getId(), // md5() from component signature :)
			'hintState' => UI::getHintState(), // todo: when collection implemented, move this outside, leave handy shortcut in component.js
			'user' => is_array($arResult['AUX_DATA']['USER']) ? $arResult['AUX_DATA']['USER'] : array(), // todo: the same as above
			'userNameTemplate' => $this->findParameterValue('NAME_TEMPLATE'), // todo: the same as above
			'modulesAvailable' => $this->getComponent()->arResult['COMPONENT_DATA']['MODULES'],
		));
		?>
		<script>new BX.Tasks.Component.<?=$this->name?>(<?=UI::toJSON($data)?>);</script>
		<?
	}

	public function pickId()
	{
		$id = trim((string) $this->template->__component->arParams['TEMPLATE_CONTROLLER_ID']);
		if($id)
		{
			$id = ToLower($id);
			if(!preg_match('#^[a-z0-9_-]+$#', $id))
			{
				$this->template->__component->getErrors()->addWarning('ILLEGAL_CALL_ID', 'Illegal CALL_ID passed');
				$id = false;
			}
		}

		if(!$id)
		{
			$id = $this->template->__component->getSignature();
		}

		return $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getScopeId()
	{
		return 'bx-component-scope-'.$this->getId();
	}

	public function getComponent()
	{
		return $this->template->__component;
	}

	/**
	 * Show blocks of fatal messages, if any
	 */
	public function displayFatals()
	{
		$errors = $this->getErrors();
		foreach($errors as $error)
		{
			if($error->getType() != Error::TYPE_FATAL)
			{
				continue;
			}

			?>
			<div class="task-message-label error"><?=htmlspecialcharsbx($error->getMessage())?></div>
			<?
		}
	}

	/**
	 * Show blocks of warnings, if any
	 */
	public function displayWarnings()
	{
		$errors = $this->getErrors();
		foreach($errors as $error)
		{
			if($error->getType() == Error::TYPE_FATAL)
			{
				continue;
			}

			?>
			<div class="task-message-label warning"><?=htmlspecialcharsbx($error->getMessage())?></div>
			<?
		}
	}

	public function checkHasFatals()
	{
		return $this->getErrors()->checkHasFatals();
	}

	/**
	 * Find a parameter in $arParams of the current component call (or of parent components nested calls, if any)
	 *
	 * @param $parameter
	 * @return mixed
	 */
	public function findParameterValue($parameter)
	{
		return $this->getComponent()->findParameterValue($parameter);
	}

	public function fillTemplate($template, $data)
	{
		$replacement = array();
		foreach($data as $k => $v)
		{
			if(array_key_exists('CAN_SHOW_URL', $data))
			{
				if (in_array($k, array('TITLE', 'DISPLAY')))
				{
//					$v = UI::sanitizeString($v, array('a' => array('href'), 'img' => array('src')));
					$v = UI::convertBBCodeToHtml($v, array('PRESET' => 'BASIC'));
				}

				$replacement['{{{'.$k.'}}}'] = $v;
				$replacement['{{'.$k.'}}'] = strip_tags($v);
			}
			else
			{
				$replacement['{{{'.$k.'}}}'] = $v;
				$replacement['{{'.$k.'}}'] = htmlspecialcharsbx($v);
			}
		}

		return str_replace(array_keys($replacement), $replacement, $template);
	}

	public function getErrors()
	{
		return $this->template->__component->getErrors();
	}

	public function addBodyClass($className = '')
	{
		$bodyClass = $GLOBALS['APPLICATION']->GetPageProperty("BodyClass");
		$GLOBALS['APPLICATION']->SetPageProperty("BodyClass", $bodyClass ? $bodyClass." ".$className : $className);
	}

	public function setTitle($title)
	{
		if($this->findParameterValue('SET_TITLE'))
		{
			$GLOBALS['APPLICATION']->setTitle($title);
		}
	}

	public function setNavChain(array $chain)
	{
		if($this->findParameterValue('SET_NAVCHAIN'))
		{
			foreach($chain as $item)
			{
				$GLOBALS['APPLICATION']->addChainItem($item[0], $item[1]);
			}
		}
	}

	public function addMethod($name, $cb)
	{
		$name = trim((string) $name);
		if($name && is_callable($cb))
		{
			// todo: also, when we move to php 5.4, there closure bindTo() can be done, in case of closure passed
			$this->methods[$name] = $cb;

			if(ToLower(substr($name, 0, 14)) == 'templateaction')
			{
				$this->runtimeActions[$name] = $cb;
			}
		}
	}

	public function getRunTimeActions()
	{
		return $this->runtimeActions;
	}

	public function __call($name, $arguments)
	{
		if(array_key_exists($name, $this->methods))
		{
			$arguments[] = $this;

			return call_user_func_array($this->methods[$name], $arguments);
		}

		return null;
	}

	private function getExtensionId()
	{
		return 'tasks_component_ext_'.md5($this->getId());
	}
}