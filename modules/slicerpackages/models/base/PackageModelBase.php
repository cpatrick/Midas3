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

/**
 * Slicepackages Model Base
 */
abstract class Slicerpackages_PackageModelBase extends Slicerpackages_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'slicerpackages_package';
    $this->_key = 'package_id';
    $this->_mainData = array(
        'package_id' =>  array('type' => MIDAS_DATA),
        'item_id' => array('type' => MIDAS_DATA),
        'os' => array('type' => MIDAS_DATA),
        'arch' => array('type' => MIDAS_DATA),
        'revision' => array('type' => MIDAS_DATA),
        'submissiontype' => array('type' => MIDAS_DATA),
        'packagetype' => array('type' => MIDAS_DATA),
        'item' =>  array('type' => MIDAS_MANY_TO_ONE,
                         'model' => 'Item',
                         'parent_column' => 'item_id',
                         'child_column' => 'item_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  public abstract function getByItemId($itemId);

} // end class Slicerpackages_PackageModelBase
