<?php if ( ! defined('EXT')) exit('Invalid file request.');

/**
 * OmniLog module control panel.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Omnilog
 */

require_once PATH_MOD .'omnilog/models/omnilog_model' .EXT;

class Omnilog_CP {

  private $_base_qs;
  private $_base_url;
  private $_model;
  private $_theme_url;
  private $_view;
  private $_view_crumbs;
  private $_view_vars;

  
  /* --------------------------------------------------------------
   * PUBLIC METHODS
   * ------------------------------------------------------------ */
  
  /**
   * Constructor.
   *
   * @access  public
   * @param   bool      $switch       Goodness knows.
   * @param   object    $mock_model   Mock model, used for testing.
   * @return  void
   */
  public function __construct($switch = TRUE, $mock_model = NULL)
  {
    $this->_model = $mock_model ? $mock_model : new Omnilog_model();

    // If the module isn't installed, exit stage left.
    if ( ! $installed_version = $this->_model->get_installed_version())
    {
      return;
    }

    // Run the update script.
    $this->_model->update_package(
      $installed_version, $this->_model->get_package_version());

    if ($switch === TRUE)
    {
      $this->_base_qs = 'C=modules' .AMP .'M='
        .$this->_model->get_package_name();
      
      $this->_base_url  = BASE .AMP .$this->_base_qs;
      $this->_theme_url = $this->_model->get_package_theme_url();

      // Reset the view.
      $this->_view        = '';
      $this->_view_crumbs = array();
      $this->_view_vars   = array();

      // Handle the request.
      $this->_handle_action();
      $this->_display_view();
    }
  }


  /**
   * Uninstalls the module.
   *
   * @access  public
   * @return  bool
   */
  public function omnilog_module_deinstall()
  {
    return $this->_model->uninstall_module();
  }


  /**
   * Installs the module.
   *
   * @access  public
   * @return  bool
   */
  public function omnilog_module_install()
  {
    return $this->_model->install_module();
  }



  /* --------------------------------------------------------------
   * PRIVATE METHODS
   * ------------------------------------------------------------ */
  
  /**
   * Displays the requested view.
   *
   * @access  private
   * @return  void
   */
  private function _display_view()
  {
    global $FNS, $IN, $LANG, $PREFS;

    /**
     * The 'handle action' code always gets called first. If something goes
     * wrong, the view variables are set, and override anything we were
     * planning to do here...
     */

    if ($this->_view)
    {
      $this->_load_view();
      return;
    }

    switch ($IN->GBL('P', 'GET'))
    {
      case 'log':
      default:
        $this->_view_crumbs = array();
        $this->_view        = 'log';
        $this->_view_vars   = array(
          'browser_title' => $LANG->line('hd_log'),
          'js_lang'       => array(
            'lblHide' => $LANG->line('lbl_hide'),
            'lblShow' => $LANG->line('lbl_show')
          ),
          'log_entries'     => $this->_model->get_log_entries(),
          'webmaster_email' => $PREFS->ini('webmaster_email')
        );
        break;
    }

    $this->_load_view();
  }


  /**
   * Loads the specified view, and sets some common view properties.
   * 
   * @access  private
   * @return  void
   */
  private function _load_view()
  {
    global $DSP, $LANG;

    $package_name     = strtolower($this->_model->get_package_name());
    $lang_module_name = $LANG->line($package_name .'_module_name');

    // CSS and JS.
    $headers = '<link rel="stylesheet"
      href="' .$this->_theme_url .'css/cp.css" />';

    $footers = '<script src="' .$this->_theme_url .'js/cp.js"></script>';

    // Add some extra goodies to the view variables array.
    $common_vars = array(
      'browser_title'     => '',
      'include_path'      => PATH_MOD .$package_name .'/views/',
      'module_name'       => $lang_module_name,
      'module_version'    => $this->_model->get_package_version(),
      'theme_url'         => $this->_theme_url,
    );

    $vars = array_merge($common_vars, $this->_view_vars);

    // Append the standard string to the browser title.
    $vars['browser_title'] = $vars['browser_title']
      ? $vars['browser_title'] .' | ' .$lang_module_name
      : $lang_module_name;

    // Output everything.
    $DSP->extra_header .= $headers;
    $DSP->title     = $vars['browser_title'];
    $DSP->crumbline = TRUE;

    $DSP->crumb = $DSP->anchor($this->_base_url .AMP .'P=log' .AMP
      .'A=search_log', $lang_module_name);

    $DSP->body .= $DSP->view($this->_view, $vars, TRUE) .$footers;
  }


  /**
   * Handles the requested action.
   *
   * @access  private
   * @return  void
   */
  private function _handle_action()
  {
    // Does nothing.
  }


}


/* End of file      : mcp.omnilog.php */
/* File location    : /system/modules/omnilog/mcp.omnilog.php */
