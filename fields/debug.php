<?php
/**
 * Mautic-Joomla plugin
 * @author	  Mautic
 * @copyright   Copyright (C) 2014 Mautic All Rights Reserved.
 * @license	 http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * Website	  http://www.mautic.org
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once __DIR__ . '/../mauticApiHelper.php';

/**
 * Form Field class for the Mautic-Joomla plugin.
 * Provides a debug info.
 */
class JFormFieldDebug extends JFormField
{

	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'debug';

	/**
	 * Display debug info
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getLabel()
	{
		$apiHelper  = new mauticApiHelper;
		$params	    = $apiHelper->getPluginParams();

		if ($params->get('debug_on'))
		{
			return parent::getLabel();
		}
	}

	/**
	 * Display debug info
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$apiHelper  = new mauticApiHelper;
		$settings   = $apiHelper->getApiSettings();
		$params	 = $apiHelper->getPluginParams();
		$debug	  = '';

		if ($params->get('debug_on'))
		{
			$debug = '<pre>';
			$debug .= var_export($_SESSION['oauth']['debug'], true);
			$debug .= '</pre>';
			$debug .= '<h3>' . JText::_('PLG_MAUTIC_OAUTH_SETTINGS') . '</h3>';
			$debug .= '<pre>';
			$debug .= var_export($settings, true);
			$debug .= '</pre>';

			return $debug;
		}
	}
}
