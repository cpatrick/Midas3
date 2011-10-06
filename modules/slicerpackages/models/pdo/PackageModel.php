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
require_once BASE_PATH.'/modules/slicerpackages/models/base/PackageModelBase.php';

/**
 * Package PDO Model
 */
class Slicerpackages_PackageModel extends Slicerpackages_PackageModelBase
{
  /**
   * Return all the record in the table
   * @return Array of SlicerpackagesDao
   */
  function getAll()
    {
    $sql = $this->database->select();
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('Package', $row, 'slicerpackages');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }

}  // end class