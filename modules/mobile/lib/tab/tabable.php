<?
namespace Bitrix\Mobile\Tab;

use Bitrix\Mobile\Context;

interface Tabable
{
	/**
	 * @return boolean
	 */
	public function isAvailable();

	/**
	 * @return mixed
	 */
	public function getData();

	/**
	 * @return null|array
	 */
	public function getMenuData();

	/**
	 * @return boolean
	 */
	public function shouldShowInMenu();

	/**
	 * @return boolean
	 */
	public function canBeRemoved();

	/**
	 * @return integer
	 */
	public function defaultSortValue();

	/**
	 * @return boolean
	 */
	public function canChangeSort();

	public function getTitle();

	public function getShortTitle();

	public function getId();

	public function getIconId(): string;

	/**
	 * @param Context $context
	 * @return void
	 */
	public function setContext($context);

}