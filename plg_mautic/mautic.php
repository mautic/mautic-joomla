<?php
/*------------------------------------------------------------------------
# Mautic
# ------------------------------------------------------------------------
# @author Mautic
# @copyright Copyright (C) 2014 Mautic All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.mautic.org
-------------------------------------------------------------------------*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 *
 * @package		Joomla
 * @subpackage	System.Mautic
 */
class plgSystemMautic extends JPlugin
{
	/**
	 * This event is triggered after the framework has loaded and the application initialise method has been called.
	 *
	 * @return	void
	 *
	 */
	public function onAfterDispatch()
	{
		$app 		= JFactory::getApplication();
		$document 	= JFactory::getDocument();
		$input		= $app->input;

		// Check to make sure we are loading an HTML view and there is a main component area
		if ($document->getType() !== 'html' || $input->get('tmpl', '', 'cmd') === 'component' || $app->isAdmin())
		{
			return true;
		}

        // Get additional data to send
        $attrs = array();
        $attrs['title'] = $document->title;
        $attrs['language'] = $document->language;
        $attrs['referrer'] = $_SERVER['HTTP_REFERER'];
        $attrs['url'] = JURI::base();

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

            if (isset($name[count($name) - 1]))
            {
                $attrs['lastname'] = $name[count($name) - 1];
            }
        }

		$encodedAttrs = urlencode(base64_encode(serialize($attrs)));

        $buffer  = $document->getBuffer('component');
        $image   = '<img src="' . $this->params->get('base_url') . '/p/mtracking.gif?d=' . $encodedAttrs . '" />';
        $buffer .= $image;
        
		$document->setBuffer($buffer, 'component');

		return true;
	}

	/**
     * Insert form script to the content
     *
     * @param	string	The context of the content being passed to the plugin.
     * @param	object	The article object.  Note $article->text is also available
     * @param	object	The article params
     * @param	int	The 'page' number
     */
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
    	$app 		= JFactory::getApplication();
		$document 	= JFactory::getDocument();
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
        if (strpos($article->text, '{mauticform') === false)
        {
            return true;
        }

        // expression to search for (positions)
        $regex		= '/{mauticform\s+(.*?)}/i';

        // Find all instances of plugin and put in $matches for githubrepo
        // $matches[0] is full pattern match, $matches[1] is the repo declaration
        preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

        if ($matches && isset($matches[0]))
        {
            foreach ($matches as $match)
            {
            	if (isset($match[1]))
            	{
            		$formId = (int) $match[1];
	                $formTag = '<script type="text/javascript" src="' . $this->params->get('base_url') . '/p/form/generate.js?id=' . $formId . '"></script>';
	                $article->text = str_replace($match[0], $formTag, $article->text);
            	}
            }
        }
    }
}
