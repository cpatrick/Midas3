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
/** demo controller*/
class Slicerpackages_IndexController extends Slicerpackages_AppController
{

  public $_models = array('User', 'Item', 'Folder');
  public $_moduleModels = array('Package');
  public $_daos = array('Item', 'Folder');
  public $_moduleDaos = array('Package');
  public $_components = array('Utility');
  public $_moduleComponents = array();
  public $_forms = array();
  public $_moduleForms = array();

  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
    {

    } // end method indexAction

  /** index action*/
  function indexAction()
    {
    }

}//end class
