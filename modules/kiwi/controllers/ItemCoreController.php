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
/** demo overwrite component */
class Kiwi_ItemCoreController extends Kiwi_AppController
{

  public $_models = array('User');
  public $_daos = array('Item');
  public $_components = array('Utility');
  public $_forms = array('Install');

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
    $this->callCoreAction();
    }

  /** view action*/
  function viewAction()
    {
    $this->callCoreAction();
    }

}//end class