<?php
/**
 * Mautic-Joomla plugin
 * @author      Mautic
 * @copyright   Copyright (C) 2014 Mautic All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * Website      http://www.mautic.org
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Include the MauticApi file which handles the API class autoloading
require_once __DIR__ . '/lib/Mautic/MauticApi.php';

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

		$table = new JTableExtension(JFactory::getDbo());
		$table->load(array('element' => 'mautic'));

		return $table;
	}

	/**
	 * Create settings needed for Mautic authentication
	 * 
	 * @return array
	 */
	public function getApiSettings()
	{
		$mauticBaseUrl = $this->getMauticBaseUrl();

		$settings = array(
			'clientKey'		 => $this->params->get('public_key'),
			'clientSecret'	  => $this->params->get('secret_key'),
			'callback'		  => JURI::root() . '/administrator',
			'accessTokenUrl'	=> $mauticBaseUrl . '/oauth/v1/access_token',
			'authorizationUrl'  => $mauticBaseUrl . '/oauth/v1/authorize',
			'requestTokenUrl'   => $mauticBaseUrl . '/oauth/v1/request_token'
		);

		if ($this->params->get('access_token'))
		{
			$settings['accessToken']		= $this->params->get('access_token');
			$settings['accessTokenSecret']  = $this->params->get('access_token_secret');
			$settings['accessTokenExpires'] = $this->params->get('access_token_expires');
		}

		return $settings;
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
			unset($_SESSION['OAuth1a']);
			unset($_SESSION['oauth']);
		}

		return \Mautic\Auth\ApiAuth::initiate($settings);
	}
}
