<?php
// $HeadURL: https://joomgallery.org/svn/joomgallery/JG-3/Plugins/JoomLytebox/trunk/joomlytebox.php $
// $Id: joomlytebox.php 4193 2013-04-12 18:10:21Z erftralle $
/******************************************************************************\
**   JoomGallery Plugin 'Integrate Lytebox'                                   **
**   By: JoomGallery::ProjectTeam                                             **
**   Copyright (C) 2012 - 2013 JoomGallery::ProjectTeam                       **
**   Released under GNU GPL Public License                                    **
**   License: http://www.gnu.org/copyleft/gpl.html                            **
\******************************************************************************/

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR.'/components/com_joomgallery/helpers/openimageplugin.php';

/**
 * JoomGallery Plugin 'Integrate Lytebox'
 *
 * With this plugin JoomGallery is able to use the Lytebox Javascript library
 * (http://lytebox.com/) for displaying images.
 *
 * NOTE: Please remember that Lytebox is licensed under the terms of the
 * 'Creative Commons Attribution 3.0 License':
 *
 * http://lytebox.com/license.php
 * http://creativecommons.org/licenses/by/3.0/
 *
 * @package     JoomGallery
 * @since       2.0
 */
class plgJoomGalleryJoomLytebox extends JoomOpenImagePlugin
{
  /**
   * Joomgallery configuration
   *
   * @var     object
   * @since   2.0
   */
  private $_jg_config = null;

  /**
   * Name of this popup box
   *
   * @var   string
   * @since 3.0
   */
  protected $title = 'Lytebox';

  /**
   * Initializes the box by adding all necessary iamge group independent JavaScript and CSS files.
   * This is done only once per page load.
   *
   * @return  void
   * @since   3.0
   */
  protected function init()
  {
    JHtml::_('behavior.framework');

    $doc = JFactory::getDocument();

    $this->loadLanguage();

    // Add variables to forward color theme and some translations to Lytebox
    $script   = array();
    $script[] = "    var lyteboxTheme         = '".$this->params->get('cfg_theme')."';";
    $script[] = "    var lyteboxCloseLabel    = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_CLOSE_LBL')."';";
    $script[] = "    var lyteboxPrevLabel     = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_PREV_LBL')."';";
    $script[] = "    var lyteboxNextLabel     = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_NEXT_LBL')."';";
    $script[] = "    var lyteboxPlayLabel     = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_PLAY_LBL')."';";
    $script[] = "    var lyteboxPauseLabel    = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_PAUSE_LBL')."';";
    $script[] = "    var lyteboxPrintLabel    = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_PRINT_LBL')."';";
    $script[] = "    var lyteboxImageLabel    = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_IMAGE_LBL')."';";
    $script[] = "    var lyteboxPageLabel     = '".JText::_('PLG_JOOMGALLERY_JOOMLYTEBOX_PAGE_LBL')."';";
    $script[] = "    window.addEvent('domready', function() {";
    $script[] = "      var sstr = '".strtolower($this->title)."';";
    $script[] = "      $$('a[rel^=' + sstr + ']').each(function(el) {";
    $script[] = "        el.addClass(sstr);";
    $script[] = "      });";
    $script[] = "    });";

    $doc->addScriptDeclaration(implode("\n", $script));

    // Add lytebox css and js
    $doc->addStyleSheet(JURI::root().'media/plg_joomgallery_joomlytebox/lytebox.css');
    $doc->addScript(JURI::root().'media/plg_joomgallery_joomlytebox/lytebox.js');

    // Get JoomGallery configuration
    $this->_jg_config = JoomConfig::getInstance();
  }

  /**
   * This method sets an associative array of attributes for the 'a'-Tag (key/value pairs)
   * which opens the image.
   *
   * @param   array   $attribs  Associative array of HTML attributes which you have to fill
   * @param   object  $image    An object holding all the relevant data about the image to open
   * @param   string  $img_url  The URL to the image which shall be openend
   * @param   string  $group    The name of an image group, most popup boxes are able to group the images with that
   * @param   string  $type     'orig' for original image, 'img' for detail image or 'thumb' for thumbnail
   * @return  void
   * @since   3.0
   */
  protected function getLinkAttributes(&$attribs, $image, $img_url, $group, $type)
  {
    // Prepare the data title used in lytebox
    $data_title = '';
    if($this->_jg_config->get('jg_show_title_in_popup'))
    {
      $data_title .= $image->imgtitle;
    }
    if($this->_jg_config->get('jg_show_description_in_popup') && !empty($image->imgtext))
    {
      if($this->_jg_config->get('jg_show_description_in_popup') == 1)
      {
        $data_title .= htmlspecialchars($image->imgtext);
      }
      else
      {
        if(!empty($data_title))
        {
          $data_title .= '<br />';
        }
        $data_title .= htmlspecialchars(strip_tags($image->imgtext));
      }
    }

    // Prepare some lytebox options
    $autoResize   = $this->_jg_config->get('jg_resize_js_image') ? 'true' : 'false';
    $resizeSpeed  = $this->params->get('cfg_resizespeed', 5);
    $navType      = $this->params->get('cfg_navtype');
    $navTop       = $this->params->get('cfg_navbarpos') ? 'false' : 'true';
    $titleTop     = $this->params->get('cfg_titlepos') ? 'false' : 'true';

    // Common attributes
    $attribs['rel']        = strtolower($this->title);
    $attribs['data-title'] = $data_title;

    // Prepare the attributes link dependent on lytebox operation mode
    switch((int)$this->params->get('cfg_operationmode'))
    {
      case 1:
        // Prepare some extra slideshow options
        $loopSlideshow = $this->params->get('cfg_slideloop') ? 'true':'false';
        $autoEnd = 'false';
        if($loopSlideshow === 'false')
        {
          $autoEnd = 'true';
        }
        // Slideshow
        $attribs['data-lyte-options'] = 'slide:true'
                                      .' loopSlideshow:'.$loopSlideshow
                                      .' autoEnd:'.$autoEnd
                                      .' autoResize:'.$autoResize
                                      .' resizeSpeed:'.$resizeSpeed
                                      .' navType:'.$navType
                                      .' navTop:'.$navTop
                                      .' titleTop:'.$titleTop
                                      .' group:'.$group
                                      .' slideInterval:'.(int)$this->params->get('cfg_slideinterval');
        break;
      default:
        // Grouped popup box
        $attribs['data-lyte-options'] = 'autoResize:'.$autoResize
                                      .' resizeSpeed:'.$resizeSpeed
                                      .' navType:'.$navType
                                      .' navTop:'.$navTop
                                      .' titleTop:'.$titleTop
                                      .' group:'.$group;
        break;
    }
  }
}