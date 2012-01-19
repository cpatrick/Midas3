<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 20 rue de la Villette. 69328 Lyon, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

/** Common Form Class*/
class AppForm
  {
  /** constructor*/
  public function  __construct()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->webroot = $fc->getBaseUrl();
    }//end construct


  /** translation  */
  protected  function t($text)
    {
    Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');
    return InternationalizationComponent::translate($text);
    }//en method t
  }//end class
