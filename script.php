<?php
/**
 * Mautic-Joomla plugin
 * @author		Mautic
 * @copyright	Copyright (C) 2014 Mautic All Rights Reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * Website		http://www.mautic.org
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  1.1
 */
class PlgContentPodcastManagerInstallerScript
{
	/**
	 * Minimum supported Joomla! version
	 *
	 * @var    string
	 * @since  1.1
	 */
	protected $minimumJoomlaVersion = '2.5.0';

	/**
	 * Minimum supported PHP version
	 *
	 * @var    string
	 * @since  1.1
	 */
	protected $minimumPHPVersion = '5.3.0';

	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string            $type    The action being performed
	 * @param   JInstallerPlugin  $parent  The function calling this method
	 *
	 * @return  boolean
	 *
	 * @since   1.1
	 */
	public function preflight($type, $parent)
	{
		// Make sure we aren't uninstalling first
		if ($type != 'uninstall')
		{
			// Check PHP version
			if (version_compare(PHP_VERSION, $this->minimumPHPVersion, 'lt'))
			{
				JError::raiseNotice(
					null, JText::sprintf('PLG_MAUTIC_ERROR_INSTALL_PHPVERSION', $this->minimumPHPVersion)
				);

				return false;
			}

			// Check Joomla! version
			if (version_compare(JVERSION, $this->minimumJoomlaVersion, 'lt'))
			{
				JError::raiseNotice(
					null, JText::sprintf('PLG_MAUTIC_ERROR_INSTALL_JVERSION', $this->minimumJoomlaVersion)
				);

				return false;
			}
		}

		return true;
	}

	/**
	 * Function to perform changes during update
	 *
	 * @param   JInstallerPlugin  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	public function update($parent)
	{
		// Get the pre-update version
		$version = $this->getVersion();

		// If in error, throw a message about the language files
		if ($version == 'Error')
		{
			JError::raiseNotice(null, JText::_('COM_PODCASTMANAGER_ERROR_INSTALL_UPDATE'));

			return;
		}

		// If coming from 1.0, remove old language folders
		if (version_compare($version, '1.1', 'lt'))
		{
			$this->removeLanguageFiles();
		}
	}

	/**
	 * Function to get the currently installed version from the manifest cache
	 *
	 * @return  string  The version that is installed
	 *
	 * @since   1.1
	 */
	private function getVersion()
	{
		// Get the record from the database
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('manifest_cache'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('element') . ' = ' . $db->quote('mautic'), 'AND');
		$query->where($db->quoteName('folder') . ' = ' . $db->quote('system'), 'AND');
		$db->setQuery($query);

		if (!($manifest = $db->loadObject()))
		{
			JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
			$version = 'Error';

			return $version;
		}

		// Decode the JSON
		$record = json_decode($manifest->manifest_cache);

		// Get the version
		$version = $record->version;

		return $version;
	}

	/**
	 * Function to remove language files from the system language folders in favor of extension folders
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	private function removeLanguageFiles()
	{
		jimport('joomla.filesystem.file');

		$adminBase = JPATH_ADMINISTRATOR . '/language/en-GB/';

		// The language files for pre-1.1
		$adminFiles = array('en-GB.plg_system_mautic.ini', 'en-GB.plg_system_mautic.sys.ini');

		// Remove the admin files
		foreach ($adminFiles as $adminFile)
		{
			if (is_file($adminBase . $adminFile))
			{
				JFile::delete($adminBase . $adminFile);
			}
		}
	}
}
