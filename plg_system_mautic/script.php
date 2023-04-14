<?php
/**
 * @package     Mautic-Joomla.Plugin
 * @subpackage	System.Mautic
 *
 * @author		Mautic, Martina Scholz
 * @copyright	Copyright (C) 2014 - 2023 Mautic All Rights Reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link		http://www.mautic.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since 2.0.0
 */
class PlgSystemMauticInstallerScript extends InstallerScript
{
	/**
	 * Minimum supported Joomla! version
	 *
	 * @var    string
	 * @since  1.1
	 */
	protected $minimumJoomla = '4.0.0';

	/**
	 * Minimum supported PHP version
	 *
	 * @var    string
	 * @since  1.1
	 */
	protected $minimumPhp = '7.4.0';

	/**
	 * Function called after extension installation/update/removal procedure commences.
	 *
	 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
	 * @param   InstallerAdapter  $adapter  The adapter calling this method
	 *
	 * @return  boolean  True on success
	 *
	 * @since   4.2.0
	 */
	public function postflight(string $type, InstallerAdapter $adapter)
	{
		if ($type != 'install' && $type != 'discover_install') {
			return true;
		}

		$lang = Factory::getApplication()->getLanguage();
		$lang->load('plg_system_mautic', JPATH_ADMINISTRATOR);

		Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_MAUTIC_POSTINSTALL_MSG'));

		return true;
	}
}
