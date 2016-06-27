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

jimport('joomla.plugin.plugin');
jimport('joomla.log.log');

require_once __DIR__ . '/mauticApiHelper.php';

/**
 *
 * @package		Joomla
 * @subpackage	System.Mautic
 */
class plgSystemMautic extends JPlugin
{
	/**
	 * mauticApiHelper
	 */
	protected $apiHelper;

	/**
	 * Object Constructor.
	 *
	 * @access	public
	 * @param	object	$subject The object to observe -- event dispatcher.
	 * @param	object	$config  The configuration object for the plugin.
	 * @since	1.0
	 */
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// Define the logger.
		JLog::addLogger(array('text_file' => 'plg_mautic.php'), JLog::ALL, array('plg_mautic'));
	}

	/**
	 * This event is triggered before the framework creates the Head section of the Document.
	 *
	 * @return	mixed
	 */
	public function onBeforeCompileHead()
	{
		$app      = JFactory::getApplication();
		$document = JFactory::getDocument();
		$input    = $app->input;

		// Check to make sure we are loading an HTML view and there is a main component area
		if ($document->getType() !== 'html' || $input->get('tmpl', '', 'cmd') === 'component' || $app->isAdmin())
		{
			return true;
		}

		$attrs = array();

		$user = JFactory::getUser();

		// Get info about the user if logged in
		if (!$user->guest)
		{
			$attrs['email'] = $user->email;

			$name = explode(' ', $user->name);

			if (isset($name[0]))
			{
				$attrs['firstname'] = $name[0];
			}

			$count = count($name);
			$lastNamePos = $count -1;

			if ($lastNamePos !== 0 && isset($name[$lastNamePos]))
			{
				$attrs['lastname'] = $name[$lastNamePos];
			}
		}

		$mauticUrl = trim($this->params->get('base_url'), ' \t\n\r\0\x0B/');
		$attrs     = json_encode($attrs, JSON_FORCE_OBJECT);

		$mauticTrackingJS = <<<JS
    (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
        w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
        m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
    })(window,document,'script','{$mauticUrl}/mtc.js','mt');

    mt('send', 'pageview', {$attrs});
JS;
		$document->addScriptDeclaration($mauticTrackingJS);

		return true;
	}

	/**
	 * Insert form script to the content
	 *
	 * @param	string	$context The context of the content being passed to the plugin.
	 * @param	object	$article The article object.  Note $article->text is also available
	 * @param	object	$params  The article params
	 * @param	integer	$page    The 'page' number
	 *
	 * @return  mixed
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		$app		= JFactory::getApplication();
		$document	= JFactory::getDocument();
		$input		= $app->input;

		// Check to make sure we are loading an HTML view and there is a main component area and content is not being indexed
		if ($document->getType() !== 'html'
			|| $input->get('tmpl', '', 'cmd') === 'component'
			|| $app->isAdmin()
			|| $context == 'com_finder.indexer')
		{
			return true;
		}

		// simple performance check to determine whether bot should process further
		if (strpos($article->text, '{mautic') === false)
		{
			return true;
		}

		// Should we do mautic form replacements?
		if (strpos($article->text, '{mauticform') !== false || strpos($article->text, '{mautic type="form"') !== false)
		{
			// BC layer to allow old {mauticform tags to work
			$article->text = str_replace('{mauticform', '{mautic type="form"', $article->text);

			// expression to search for (positions)
			$formRegex = '/{mautic type="form" (\d+)}/i';

			// $matches[0] is full pattern match, $matches[1] is the repo declaration
			preg_match_all($formRegex, $article->text, $matches, PREG_SET_ORDER);

			if ($matches && isset($matches[0]))
			{
				foreach ($matches as $match)
				{
					if (isset($match[1]))
					{
						$formId = (int) $match[1];
						$formTag = '<script type="text/javascript" src="' . trim($this->params->get('base_url'), ' \t\n\r\0\x0B/') . '/form/generate.js?id=' . $formId . '"></script>';
						$article->text = str_replace($match[0], $formTag, $article->text);
					}
				}
			}
		}

		// Should we do mautic content replacements?
		if (strpos($article->text, '{mautic type="content"') !== false)
		{
			$article->text = $this->doContentReplacement($article->text);

			// Replace empty default content with full tag, and do it again
			$tmpText = preg_replace('/{mautic type="content" slot="(\w+)"}/i', '{mautic type="content" slot="$1"}{/mautic}', $article->text);

			// If any replacements occured, there is a content item with an empty default content
			if ($article->text !== $tmpText)
			{
				$article->text = $this->doContentReplacement($tmpText);
			}
		}
	}

	/**
	 * Does a search/replace for Mautic dynamic content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	private function doContentReplacement($content)
	{
		// Replace full tags
		$contentRegex = '/{mautic type="content" slot="(\w+)"}(.*){\/mautic}/i';

		// $matches[0] is full pattern match, $matches[1] is the repo declaration
		preg_match_all($contentRegex, $content, $dwcMatches, PREG_SET_ORDER);

		if ($dwcMatches && isset($dwcMatches[0]))
		{
			foreach ($dwcMatches as $dwcMatch)
			{
				if (isset($dwcMatch[1]))
				{
					$slot = $dwcMatch[1];
					$defaultContent = $dwcMatch[2];
					$dwcTag = '<div class="mautic-slot" data-slot-name="' . $slot . '">' . $defaultContent . '</div>';
					$content = str_replace($dwcMatch[0], $dwcTag, $content);
				}
			}
		}

		return $content;
	}

	/**
	* Mautic API call
	*/
	public function onAfterRoute()
	{
		$user	= JFactory::getUser();
		$app	= JFactory::getApplication();
		$input	= $app->input;
		$isRoot	= $user->authorise('core.admin');

		if ($input->get('plugin') == 'mautic')
		{
			$lang = JFactory::getLanguage();
			$lang->load('plg_system_mautic', JPATH_ADMINISTRATOR);

			if ($isRoot)
			{
				if ($input->get('authorize', false, 'BOOLEAN'))
				{
					$this->authorize($input->get('reauthorize', false, 'BOOLEAN'));
				}

				if ($input->get('reauthorize', false, 'BOOLEAN'))
				{
					$this->authorize(true);
				}

				if ($input->get('reset', false, 'BOOLEAN'))
				{
					$this->resetAccessToken();
				}
			}
			else
			{
				$app->enqueueMessage(JText::_('PLG_MAUTIC_ERROR_ONLY_ADMIN_CAN_AUTHORIZE'), 'warning');
				$this->log(JText::_('PLG_MAUTIC_ERROR_ONLY_ADMIN_CAN_AUTHORIZE'), JLog::ERROR);
			}
		}

		if (($input->get('oauth_token') && $input->get('oauth_verifier'))
			|| ($input->get('state') && $input->get('code')))
		{
			$this->authorize($input->get('reauthorize', false, 'BOOLEAN'));
		}
	}

	/**
	 * Reset Access token so the plugin could authorize again
	 *
	 * @return void
	 */
	public function resetAccessToken()
	{
		$app		= JFactory::getApplication();
		$apiHelper	= $this->getMauticApiHelper();
		$table		= $apiHelper->getTable();
		$this->params->set('access_token', '');
		$this->params->set('access_token_secret', '');
		$this->params->set('access_token_expires', '');
		$table->set('params', $this->params->toString());
		$table->store();
		$app->enqueueMessage(JText::_('PLG_MAUTIC_RESET_SUCCESSFUL'));
		$this->log(JText::_('PLG_MAUTIC_RESET_SUCCESSFUL'), JLog::INFO);
		$app->redirect(JRoute::_('index.php?option=com_plugins&view=plugin&layout=edit&extension_id=' . $table->get('extension_id'), false));
	}

	/**
	 * Create sanitized Mautic Base URL without the slash at the end.
	 *
	 * @return string
	 */
	public function getMauticApiHelper()
	{
		if ($this->apiHelper)
		{
			return $this->apiHelper;
		}

		$this->apiHelper = new mauticApiHelper;

		return $this->apiHelper;
	}

	/**
	 * Get Table instance of this plugin
	 *
	 * @return JTableExtension
	 */
	public function authorize($reauthorize = false)
	{
		$app			= JFactory::getApplication();
		$apiHelper		= $this->getMauticApiHelper();
		$mauticBaseUrl	= $apiHelper->getMauticBaseUrl();
		$auth			= $apiHelper->getMauticAuth($reauthorize);
		$table			= $apiHelper->getTable();
		$lang			= JFactory::getLanguage();
		
		$lang->load('plg_system_mautic', JPATH_ADMINISTRATOR);

		$this->log('Authorize method called.', JLog::INFO);

		try 
		{
			if ($auth->validateAccessToken())
			{
				if ($auth->accessTokenUpdated())
				{
					$accessTokenData = new JRegistry($auth->getAccessTokenData());
					$this->log('authorize::accessTokenData: ' . var_export($accessTokenData, true), JLog::INFO);

					$this->params->merge($accessTokenData);
					$table = $apiHelper->getTable();
					$table->set('params', $this->params->toString());
					$table->store();
					$extraWord = $reauthorize ? 'PLG_MAUTIC_REAUTHORIZED' : 'PLG_MAUTIC_AUTHORIZED';
					$app->enqueueMessage(JText::sprintf('PLG_MAUTIC_REAUTHORIZE_SUCCESS', JText::_($extraWord)));
				}
				else
				{
					$app->enqueueMessage(JText::_('PLG_MAUTIC_REAUTHORIZE_NOT_NEEDED'));
					$this->log(JText::_('PLG_MAUTIC_REAUTHORIZE_NOT_NEEDED'), JLog::INFO);
				}
			}
		}
		catch (Exception $e)
		{
			$app->enqueueMessage($e->getMessage(), 'error');
			$this->log($e->getMessage(), JLog::ERROR);
		}

		$app->redirect(JRoute::_('index.php?option=com_plugins&view=plugin&layout=edit&extension_id=' . $table->get('extension_id'), false));
	}

	/**
	 * Create new lead on Joomla user registration
	 *
	 * For debug is better to switch function to:
	 * public function onUserBeforeSave($success, $isNew, $user)
	 *
	 * @param array 	$user 		array with user information
	 * @param boolean 	$isNew 		whether the user is new
	 * @param boolean 	$success 	whether the user was saved successfully
	 * @param string 	$msg 		error message
	 */
	public function onUserAfterSave($user, $isNew, $success, $msg = '')
	{
		$this->log('onUserAfterSave method called.', JLog::INFO);
		$this->log('onUserAfterSave::isNew: ' . var_export($isNew, true), JLog::INFO);
		$this->log('onUserAfterSave::success: ' . var_export($success, true), JLog::INFO);
		$this->log('onUserAfterSave::send_registered: ' . var_export($this->params->get('send_registered'), true), JLog::INFO);

		if ($isNew && $success && $this->params->get('send_registered') == 1)
		{
			$this->log('onUserAfterSave: Send the user to Mautic.', JLog::INFO);

			try
			{
				$apiHelper		= $this->getMauticApiHelper();
				$mauticBaseUrl	= $apiHelper->getMauticBaseUrl();
				$auth			= $apiHelper->getMauticAuth();
				$leadApi		= \Mautic\MauticApi::getContext("leads", $auth, $mauticBaseUrl . '/api/');
				$ip				= $this->getUserIP();
				$name			= explode(' ', $user['name']);

				$mauticUser = array(
					'ipAddress' => $ip,
					'firstname' => isset($name[0]) ? $name[0] : '',
					'lastname'	=> isset($name[1]) ? $name[1] : '',
					'email'		=> $user['email'],
				);

				$this->log('onUserAfterSave::mauticUser: ' . var_export($mauticUser, true), JLog::INFO);

				$result = $leadApi->create($mauticUser);

				if (isset($result['error']))
				{
					$this->log('onUserAfterSave::leadApi::create - response: ' . $result['error']['code'] . ": " . $result['error']['message'], JLog::ERROR);
				}
				elseif (!empty($result['lead']['id']))
				{
					$this->log('onUserAfterSave: Mautic lead was successfully created with ID ' . $result['lead']['id'], JLog::INFO);
				}
				else
				{
					$this->log('onUserAfterSave: Mautic lead was NOT successfully created. ' . var_export($result, true), JLog::ERROR);
				}

			}
			catch (Exception $e)
			{
				$this->log($e->getMessage(), JLog::ERROR);
			}
		}
		else
		{
			$this->log('onUserAfterSave: Do not send the user to Mautic.', JLog::INFO);
		}
	}

	/**
	 * Try to guess the real user IP address
	 *
	 * @return	string
	 */
	public function getUserIP()
	{
		$ip = '';

		if (!empty($_SERVER['HTTP_CLIENT_IP']))
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif (!empty($_SERVER['REMOTE_ADDR']))
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	/**
	 * Log helper function
	 *
	 * @return	string
	 */
	public function log($msg, $type)
	{
		if ($this->params->get('log_on', 1) == 1)
		{
			JLog::add($msg, $type, 'plg_mautic');
		}
	}
}
