<?php if ( ! defined('EXT')) exit('Invalid file request.');

/**
 * OmniLog module tests.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Omnilog
 */

require_once PATH_MOD .'omnilog/mod.omnilog' .EXT;
require_once PATH .'tests/omnilog/mocks/mock.omnilog_model' .EXT;

class Test_omnilog extends Testee_unit_test_case {

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
    $this->_model = $this->_get_mock('model');
    $this->_subject = new Omnilog();
  }


}


/* End of file      : test.mod_omnilog.php */
/* File location    : /system/tests/omnilog/test.mod_omnilog.php */
