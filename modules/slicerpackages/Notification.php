<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

require_once BASE_PATH . '/modules/api/library/APIEnabledNotification.php';

class Slicerpackages_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'slicerpackages';
  public $_moduleComponents = array('Api');
  public $_models = array();

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLinks');

    $this->enableWebAPI($this->moduleName);
    }//end init

  /**
   * Add the link to this module to the left side list
   */
  public function getLeftLinks()
    {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $moduleWebroot = $baseUrl.'/'.$this->moduleName;
    return array('Slicer Packages' => array(
      $moduleWebroot.'/index',
      $baseUrl.'/modules/'.$this->moduleName.'/public/images/slicerpackages.png'));
    }
  } //end class
  
?>