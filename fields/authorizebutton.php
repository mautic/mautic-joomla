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
 * Provides a button for re/authorization.
 */
class JFormFieldAuthorizeButton extends JFormField
{

	/**
	 * The form field type.
	 *
	 * @var	string
	 */
	protected $type = 'authorizebutton';

	/**
	 * Method to get the field input markup for a spacer.
	 * The spacer does not have accept input.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
        $apiHelper = new mauticApiHelper;
        $settings = $apiHelper->getApiSettings();
        $url = Juri::root() . 'administrator/?plugin=mautic';
        $text = 'PLG_MAUTIC_AUTHORIZE_BTN';

        if ($settings['accessToken'] && $settings['accessTokenSecret'])
        {
            $url .= '&reauthorize=true';
            $text = 'PLG_MAUTIC_REAUTHORIZE_BTN';
        }

        if ($settings['clientKey'] && $settings['clientSecret'])
        {
            // Note: style is added for Joomla 2.5
            return Jhtml::link($url, JText::_($text), array('class' => 'btn btn-small btn-success', 'style' => 'float: left;'));
        }
        else
        {
            return JText::_('PLG_MAUTIC_SAVE_KEYS_FIRST');
        }
		
	}
}
