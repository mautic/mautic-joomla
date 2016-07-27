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
     * Regex to capture all {mautic} tags in content
     *
     * @var string
     */
    protected $mauticRegex = '/\{(\{?)(mautic)(?![\w-])([^\}\/]*(?:\/(?!\})[^\}\/]*)*?)(?:(\/)\}|\}(?:([^\{]*+(?:\[(?!\/\2\])[^\[]*+)*+)\{\/\2\})?)(\}?)/i';

    /**
     * Taken from WP get_shortcode_atts_regex
     *
     * @var string
     */
    protected $attsRegex   = '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

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
     * Taken from WP wp_parse_shortcode_atts
     *
     * @param $text
     *
     * @return array|string
     */
    private function parseShortcodeAtts($text)
    {
        $atts = array();
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);

        if ( preg_match_all($this->attsRegex, $text, $match, PREG_SET_ORDER) ) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                    $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                    $atts[] = stripcslashes($m[8]);
            }
            // Reject any unclosed HTML elements
            foreach( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }

        return $atts;
    }

    /**
     * Taken fro WP wp_shortcode_atts
     *
     * @param array $pairs
     * @param array $atts
     *
     * @return array
     */
    private function filterAtts(array $pairs, array $atts)
    {
        $out = array();

        foreach ($pairs as $name => $default) {
            if (array_key_exists($name, $atts)) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }
        }

        return $out;
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

		// Replace {mauticform with {mautic type="form"
        $article->text = str_replace('{mauticform', '{mautic type="form"', $article->text);

        preg_match_all($this->mauticRegex, $article->text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match)
        {
            $atts = $this->parseShortcodeAtts($match[3]);
            $method = 'do' . ucfirst(strtolower($atts['type'])) . 'Shortcode';
            $newContent = '';

            if (method_exists($this, $method))
            {
                $newContent = call_user_func(array($this, $method), $atts, $match[5]);
            }

            $article->text = str_replace($match[0], $newContent, $article->text);
        }
	}

    /**
     * Do a find/replace for Mautic forms
     *
     * @param array $atts
     *
     * @return string
     */
	public function doFormShortcode($atts)
    {
        $id = isset($atts['id']) ? $atts['id'] : $atts[0];

        return '<script type="text/javascript" src="' . trim($this->params->get('base_url'), ' \t\n\r\0\x0B/') . '/form/generate.js?id=' . $id . '"></script>';
    }

    /**
     * Do a find/replace for Mautic dynamic content
     *
     * @param array  $atts
     * @param string $content
     *
     * @return string
     */
    public function doContentShortcode($atts, $content)
    {
        return '<div class="mautic-slot" data-slot-name="' . $atts['slot'] . '">' . $content . '</div>';
    }

    /**
     * Do a find/replace for Mautic gated video
     *
     * @param $content
     *
     * @return string
     */
    public function doVideoShortcode($atts, $content)
    {
        $video_type = '';
        $atts = $this->filterAtts(array(
            'gate-time' => 15,
            'form-id' => '',
            'src' => '',
            'width' => 640,
            'height' => 360
        ), $atts);

        if (empty($atts['src']))
        {
            return 'You must provide a video source. Add a src="URL" attribute to your shortcode. Replace URL with the source url for your video.';
        }

        if (empty($atts['form-id']))
        {
            return 'You must provide a mautic form id. Add a form-id="#" attribute to your shortcode. Replace # with the id of the form you want to use.';
        }

        if (preg_match('/^.*((youtu.be)|(youtube.com))\/((v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))?\??v?=?([^#\&\?]*).*/', $atts['src']))
        {
            $video_type = 'youtube';
        }

        if (preg_match('/^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/', $atts['src']))
        {
            $video_type = 'vimeo';
        }

        if (strtolower(substr($atts['src'], -3)) === 'mp4')
        {
            $video_type = 'mp4';
        }

        if (empty($video_type))
        {
            return 'Please use a supported video type. The supported types are youtube, vimeo, and MP4.';
        }

        return '<video height="' . $atts['height'] . '" width="' . $atts['width'] . '" data-form-id="' . $atts['form-id'] . '" data-gate-time="' . $atts['gate-time'] . '">' .
        '<source type="video/' . $video_type . '" src="' . $atts['src'] . '" /></video>';
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
