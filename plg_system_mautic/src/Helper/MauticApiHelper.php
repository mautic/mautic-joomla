<?php
/**
 * @package     Mautic-Joomla.Plugin
 * @subpackage  System.Mautic
 * @author	    Mautic, Martina Scholz
 * @copyright   Copyright (C) 2014 - 2023 Mautic All Rights Reserved.
 * @license	    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link	    http://www.mautic.org
 */

 namespace Joomla\Plugin\System\Mautic\Helper;

 // phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die('Restricted access');
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Helper class which initialize Mautic API library with needed data.
 * It is stand-alone class with no dependencies so it can be used from
 * other extensions easily.
 */
class MauticApiHelper
{
	/**
	 * The application instance
	 *
	 * @var    CMSApplicationInterface
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Table
	 *
	 * @var \Joomla\CMS\Table\Table
	 */
	protected $table;

	/**
	 * Registry
	 *
	 * @var \Joomla\Registry\Registry
	 */
	protected $params;

	/**
	 * Constructor initialize necessary variables
	 *
	 * @param \Joomla\CMS\Table\Table|null $table
	 *
	 * @return void
	 */
	public function __construct($table = null)
	{
		$this->table = $table;
		$this->table = $this->getTable();
		$this->params = new Registry($this->table->get('params'));
		$this->app = Factory::getApplication();
		Log::addLogger(['text_file' => 'plg_system_mautic.php'], Log::ALL, ['plg_system_mautic']);
	}

	/**
	 * Create sanitized Mautic Base URL without the slash at the end.
	 *
	 * @return string
	 */
	public function getMauticBaseUrl()
	{
		return ($this->params->get('base_url')) ? trim($this->params->get('base_url'), " \t\n\r\0\x0B/") : '';
	}

	/**
	 * Get Table instance of this plugin
	 *
	 * @return JTableExtension
	 */
	public function getTable()
	{
		if ($this->table) {
			return $this->table;
		}

		/** @var \Joomla\Component\Categories\Administrator\Model\PluginModel $pluginModel */
		$pluginModel = Factory::getApplication()->bootComponent('com_plugins')
			->getMVCFactory()->createModel('Plugin', 'Administrator', ['ignore_request' => true]);
		$this->table = $pluginModel->getTable();
		$this->table->load(['element' => 'mautic', 'folder' => 'system']);

		return $this->table;
	}

	/**
	 * Create settings needed for Mautic authentication
	 *
	 * @return array
	 */
	public function getApiSettings()
	{
		$settings = [
			'baseUrl'		=> $this->getMauticBaseUrl(),
			'version'		=> 'OAuth2',
			'clientKey'		=> $this->params->get('public_key'),
			'clientSecret'	=> $this->params->get('secret_key'),
			'callback'		=> Uri::root() . trim($this->params->get('callback_path'), " \t\n\r\0\x0B/")
		];

		if (($token = $this->params->get('token')) && property_exists($token, 'access_token') && $token->access_token) {
			$settings['accessToken']		= $token->access_token;
			$settings['accessTokenSecret']	= property_exists($token, 'access_token_secret') ? $token->access_token_secret : '';
			$settings['accessTokenExpires']	= property_exists($token, 'access_token_expires') ? $token->access_token_expires : $token->expires;
			$settings['refreshToken']		= property_exists($token, 'refresh_token') ? $token->refresh_token : '';
		}

		return $settings;
	}

	/**
	 * Returns params of Mautic plugin
	 *
	 * @return Registry
	 */
	public function getPluginParams()
	{
		return $this->params;
	}

	/**
	 * Initiate Auth object
	 *
	 * @param  bool  $clearAccessToken
	 *
	 * @return  \Mautic\Auth\OAuth
	 */
	public function getMauticAuth($clearAccessToken = false)
	{
		$settings = $this->getApiSettings();

		if ($clearAccessToken) {
			unset($settings['accessToken']);
			unset($settings['accessTokenSecret']);
			unset($settings['accessTokenExpires']);
			unset($settings['refreshToken']);
			unset($_SESSION['OAuth1a']);
			unset($_SESSION['OAuth2']);
			unset($_SESSION['oauth']);
		}

		$auth = new \Mautic\Auth\ApiAuth();

		// Update the user state
		Factory::getApplication()->setUserState('mauticapi.data.oauth_gentoken', 1);

		return $auth->newAuth($settings);
	}

	/**
	 * Try to store new Token Data after refresh
	 *
	 * @param \Mautic\Auth\OAuth $auth
	 *
	 * @return	void
	 */
	public function storeRefreshedToken($auth)
	{
		try {
				$accessTokenData = new Registry(['token' => array_merge($auth->getAccessTokenData(), ['created' => Factory::getDate()->toSql()])]);
				$this->log('refresh::accessTokenData: ' . var_export($accessTokenData, true), Log::INFO);
				$this->params->merge($accessTokenData);
				$table = $this->table;
				$table->set('params', $this->params->toString());
				$table->store();
		} catch (Exception $e) {
			if ($this->app->isClient('administrator')) {
				$this->app->enqueueMessage($e->getMessage(), 'error');
			}
			$this->log($e->getMessage(), Log::ERROR);
		}
	}

	/**
	 * Log helper function
	 *
	 * @return	string
	 */
	public function log($msg, $type)
	{
		if ($this->params->get('log_on', 1)) {
			Log::add($msg, $type, 'plg_system_mautic');
		}
	}
}
