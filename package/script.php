<?php
/**
 * @package    MauticForJoomla4
 *
 * @author		Mautic, Martina Scholz
 * @copyright	Copyright (C) 2014 - 2023 Mautic All Rights Reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link		http://www.mautic.org
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;

class Pkg_MauticForJ4InstallerScript extends InstallerScript
{
	protected $minimumPhp    = '7.4.0';
	protected $minimumJoomla = '4.0.0';

	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string              $type    The action being performed
	 * @param   InstallerAdapter    $parent  The function calling this method
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function postflight($type, $parent)
	{
		if ($parent->getElement() != 'pkg_mauticforj4') {
			return true;
		}

		if ($type != 'install' && $type != 'discover_install') {
			return true;
		}

		$lang = Factory::getApplication()->getLanguage();
		$lang->load('plg_system_mautic', JPATH_ADMINISTRATOR);

		Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_MAUTIC_POSTINSTALL_MSG'));

		return true;
	}
}
