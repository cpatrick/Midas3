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

/** Api controller for /json */
class Slicerpackages_AdminController extends Slicerpackages_AppController
{

  /** Before filter */
  function preDispatch()
    {
    $this->_forward('admin',
                    'index',
                    'slicerpackages',
                    $this->_getAllParams());
    parent::preDispatch();
    }
  } // end class
?>
