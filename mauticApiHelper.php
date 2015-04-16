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

/**
 * Helper class which initialize Mautic API library with needed data.
 * It is stand-alone class with no dependencies so it can be used from
 * other extensions easily.
 */
class mauticApiHelper
{
	/**
	 * JTableExtension
	 */
	protected $table;

	/**
	 * JRegistry
	 */
	protected $params;

	/**
	 * Constructor initialize necessary variables
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->table = $this->getTable();
		$this->params = new JRegistry($this->table->get('params'));

		/*
		 * Register the autoloader for the Mautic library
		 *
		 * Joomla! 3.2 has a native namespace capable autoloader so prefer that and use our fallback loader for older versions
		 */
		if (version_compare(JVERSION, '3.2', 'ge'))
		{
			JLoader::registerNamespace('Mautic', __DIR__ . '/lib');
		}
		else
		{
			include_once __DIR__ . '/lib/Mautic/AutoLoader.php';
		}
	}

	/**
	 * Create sanitized Mautic Base URL without the slash at the end.
	 *
	 * @return string
	 */
	public function getMauticBaseUrl()
	{
		return trim($this->params->get('base_url'), ' \t\n\r\0\x0B/');
	}

	/**
	 * Get Table instance of this plugin
	 *
	 * @return JTableExtension
	 */
	public function getTable()
	{
		if ($this->table)
		{
			return $this->table;
		}

		$table = JTable::getInstance('Extension', 'JTable', array('dbo' => JFactory::getDBO()));
		$table->load(array('element' => 'mautic', 'folder' => 'system'));

		return $table;
	}

	/**
	 * Create settings needed for Mautic authentication
	 *
	 * @return array
	 */
	public function getApiSettings()
	{
		$settings = array(
			'baseUrl'		=> $this->getMauticBaseUrl(),
			'version'		=> $this->params->get('oauth_version'),
			'clientKey'		=> $this->params->get('public_key'),
			'clientSecret'	=> $this->params->get('secret_key'),
			'callback'		=> JURI::root() . 'administrator'
		);

		if ($this->params->get('access_token'))
		{
			$settings['accessTokenSecret']	= $this->params->get('access_token_secret');
			$settings['accessToken']		= $this->params->get('access_token');
			$settings['accessTokenExpires']	= $this->params->get('access_token_expires');
		}

		return $settings;
	}

	/**
	 * Returns params of Mautic plugin
	 * 
	 * @return JRegistry
	 */
	public function getPluginParams()
	{
		return $this->params;
	}

	/**
	 * Initiate Auth object
	 *
	 * @return  string
	 */
	public function getMauticAuth($clearAccessToken = false)
	{
		$settings = $this->getApiSettings();

		if ($clearAccessToken)
		{
			unset($settings['accessToken']);
			unset($settings['accessTokenSecret']);
			unset($settings['accessTokenExpires']);
			unset($settings['expires']);
			unset($settings['token_type']);
			unset($settings['refresh_token']);
			unset($_SESSION['OAuth1a']);
			unset($_SESSION['OAuth2']);
			unset($_SESSION['oauth']);
		}

		return \Mautic\Auth\ApiAuth::initiate($settings);
	}
}
