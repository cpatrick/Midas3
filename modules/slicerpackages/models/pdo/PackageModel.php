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
   * @param exactmatches Optional associative array specifying an 'os', 'arch', 'submissiontype' and 'packagetype'.
   * @return Array of SlicerpackagesDao
   */
  function get($exactmatches = array('os' =>'any', 'arch' => 'any', 
                                     'submissiontype' => 'any', 'packagetype' => 'any'))
    {
    $sql = $this->database->select();
    foreach(array('os', 'arch', 'submissiontype', 'packagetype') as $option)
      {
      if(array_key_exists($option, $exactmatches) && $exactmatches[$option] != "any")
        {
        $sql->where("$option = ?", $exactmatches[$option]);
        }
      }
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('Package', $row, 'slicerpackages');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }

  /**
   * Return a slicerpackage_Package dao based on an itemId.
   */
  public function getByItemId($itemId)
    {
    $sql = $this->database->select()->where('item_id = ?', $itemId);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Package', $row, 'slicerpackages');
    return $dao;
    }

}  // end class
