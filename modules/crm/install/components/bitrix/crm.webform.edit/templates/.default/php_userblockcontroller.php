<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();


class CrmWebFormEditUserBlockController
{
	const USER_OPTION = 'webform_edit_blocks';

	protected $userOptions = array();
	protected $blocks = array();

	protected $currentId = '';
	protected $currentCaption = '';
	protected $currentNavCaption = '';

	protected $blockTemplate = '';
	protected $navTemplate = '';

	public function __construct($userId, $blockTemplate, $navTemplate)
	{
		$this->blockTemplate = $blockTemplate;
		$this->navTemplate = $navTemplate;
		$this->userOptions = \CUserOptions::GetOption('crm', self::USER_OPTION, array(), $userId);
	}

	public function start($id, $caption, $navCaption = null)
	{
		$this->end();

		$this->currentId = $id;
		$this->currentCaption = $caption;
		$this->currentNavCaption = $navCaption;
		ob_start();
	}

	public function end()
	{
		if(!$this->currentId)
		{
			return;
		}

		$content = ob_get_clean();
		if(!$content)
		{
			return;
		}

		$isFixed =  (isset($this->userOptions[$this->currentId]) && $this->userOptions[$this->currentId] == 'Y');
		$this->blocks[$this->currentId] = array(
			'ID' => $this->currentId,
			'CAPTION' => $this->currentCaption,
			'NAV_CAPTION' => $this->currentNavCaption,
			'CONTENT' => $content,
			'IS_FIXED' => $isFixed
		);
	}

	public function showNavigation()
	{
		foreach($this->blocks as $blockId => $block)
		{
			if(!$this->blocks[$blockId]['NAV_CAPTION'])
			{
				continue;
			}

			echo str_replace(
				array('%ID%', '%ID_LOWER%', '%CAPTION%', '%DISPLAY_CLASS%'),
				array(
					$blockId,
					strtolower($blockId),
					$this->blocks[$blockId]['NAV_CAPTION'],
					$this->blocks[$blockId]['IS_FIXED'] ? 'crm-webform-display-none' : ''
				),
				$this->navTemplate
			);
		}
	}

	public function showFixed()
	{
		$this->show(true);
	}

	public function show($showFixed = false)
	{
		foreach($this->blocks as $blockId => $block)
		{
			if($showFixed && !$block['IS_FIXED'])
			{
				continue;
			}

			$blockLayout = $this->getBlock($blockId);

			if($showFixed)
			{
				echo $blockLayout;
				continue;
			}

			echo '<div id="ADDITIONAL_OPTION_PLACE_' . $blockId . '">';
			if(!$block['IS_FIXED'])
			{
				echo $blockLayout;
			}
			echo '</div>';
		}
	}

	public function getBlock($blockId)
	{
		return str_replace(
			array('%ID%', '%ID_LOWER%', '%CAPTION%', '%CONTENT%', '%IS_FIXED%', '%FIXED_CLASS%'),
			array(
				$this->blocks[$blockId]['ID'],
				strtolower($this->blocks[$blockId]['ID']),
				$this->blocks[$blockId]['CAPTION'],
				$this->blocks[$blockId]['CONTENT'],
				$this->blocks[$blockId]['IS_FIXED'] ? 'Y' : 'N',
				$this->blocks[$blockId]['IS_FIXED'] ? 'task-option-fixed-state' : '',
			),
			$this->blockTemplate
		);
	}
}