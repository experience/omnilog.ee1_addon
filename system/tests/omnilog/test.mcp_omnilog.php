<?php if ( ! defined('EXT')) exit('Invalid file request.');

/**
 * OmniLog module control panel tests.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Omnilog
 */

require_once PATH .'tests/omnilog/mocks/mock.omnilog_model.php';
require_once PATH_MOD .'omnilog/classes/omnilog_entry.php';
require_once PATH_MOD .'omnilog/mcp.omnilog.php';

class Test_omnilog_CP extends Testee_unit_test_case {
    
  private $_model;
  private $_subject;
  
  
  /* --------------------------------------------------------------
   * PUBLIC METHODS
   * ------------------------------------------------------------ */
  
  /**
   * Constructor.
   *
   * @access  public
   * @return  void
   */
  public function setUp()
  {
      parent::setUp();
      
      Mock::generate('Mock_omnilog_model', get_class($this) .'_mock_model');
      $this->_model   = $this->_get_mock('model');
  }


  public function test__display_log__success()
  {
    global $DSP, $IN, $LANG, $PREFS;

    // Model methods.
    $log_entries      = array('a', 'b', 'c');
    $package_name     = 'Example_package';
    $package_version  = '1.1.0';
    $theme_url        = '/path/to/themes/';
    $webmaster        = 'webmaster@website.com';

    $this->_model->expectOnce('get_log_entries');
    $this->_model->setReturnValue('get_log_entries', $log_entries);

    $this->_model->expectAtLeastOnce('get_package_name');
    $this->_model->setReturnValue('get_package_name', $package_name);

    $this->_model->expectOnce('get_package_theme_url');
    $this->_model->setReturnValue('get_package_theme_url', $theme_url);

    $this->_model->expectAtLeastOnce('get_package_version');
    $this->_model->setReturnValue('get_package_version', $package_version);

    $this->_model->expectOnce('get_installed_version');
    $this->_model->setReturnValue('get_installed_version',
      $package_version);

    // Language strings.
    $browser_title  = 'Example Page Title';
    $lbl_hide       = 'Hide';
    $lbl_show       = 'Show';
    $module_name    = 'Example Module';

    $LANG->setReturnValue('line', $browser_title, array('hd_log'));
    $LANG->setReturnValue('line', $lbl_hide, array('lbl_hide'));
    $LANG->setReturnValue('line', $lbl_show, array('lbl_show'));

    $LANG->setReturnValue('line', $module_name,
      array(strtolower($package_name) .'_module_name'));

    // GET data.
    $IN->expectOnce('GBL', array('P', 'GET'));
    $IN->setReturnValue('GBL', 'log', array('P', 'GET'));

    // System preferences.
    $PREFS->setReturnValue('ini', $webmaster, array('webmaster_email'));

    $view = 'log';
    $view_vars = array(
      'browser_title' => $browser_title .' | ' .$module_name,
      'include_path'  => PATH_MOD .strtolower($package_name) .'/views/',
      'module_name'   => $module_name,
      'module_version' => $package_version,
      'theme_url'     => $theme_url,
      'js_lang'       => array(
        'lblHide' => $lbl_hide,
        'lblShow' => $lbl_show
      ),
      'log_entries'     => $log_entries,
      'webmaster_email' => $webmaster
    );

    $DSP->expectOnce('view', array($view, $view_vars, TRUE));
    $subject = new Omnilog_CP(TRUE, $this->_model);
  }


  public function test__omnilog_module_deinstall__returns_model_return_value()
  {
    $expected_result = 'Wibble';

    $this->_model->expectOnce('uninstall_module');
    $this->_model->setReturnValue('uninstall_module', $expected_result);

    $subject = new Omnilog_CP(FALSE, $this->_model);

    $this->assertIdentical($expected_result,
      $subject->omnilog_module_deinstall());
  }


  public function test__omnilog_module_install__returns_model_return_value()
  {
    $expected_result = 'Wibble';

    $this->_model->expectOnce('install_module');
    $this->_model->setReturnValue('install_module', $expected_result);

    $subject = new Omnilog_CP(FALSE, $this->_model);

    $this->assertIdentical($expected_result,
      $subject->omnilog_module_install());
  }


}


/* End of file      : test.mcp_omnilog.php */
/* File location    : /system/tests/omnilog/test.mcp_omnilog.php */
