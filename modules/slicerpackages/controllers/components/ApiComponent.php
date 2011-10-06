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

/** Component for api methods */
class Slicerpackages_ApiComponent extends AppComponent
{

  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }

  /**
   * Helper function to get the user from token or session authentication
   */
  private function _getUser($args)
    {
    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    return $authComponent->getUser($args, $this->userSession->Dao);
    }

  /**
   * Get the name of the requested dashboard
   * @param os the target operating system of the package
   * @param arch the os chip architecture (i386, amd64, etc)
   * @param name the name of the installer
   * @param revision the svn or git revision of the installer
   * @param submissiontype whether this is from a nightly, release etc dashboard
   * @param packagetype installer, data, module, etc
   * @return status of the upload
   */
  public function uploadPackage($args)
    {
    $this->_checkKeys(array('os',
                            'arch',
                            'name',
                            'revision',
                            'submissiontype',
                            'packagetype'), $args);

    $userDao = $this->_getUser($args);
    if($userDao === false)
      {
      throw new Exception('Invalid user authentication', -1);
      }

    $inputfile = 'php://input';
    $tmpfile = tempnam(BASE_PATH.'/tmp/misc', 'slicerpackage');
    $in = fopen($inputfile, 'rb');
    $out = fopen($tmpfile, 'wb');

    $bufSize = 1024 * 1024;

    $size = 0;
    // read from input and write into file
    while(connection_status() == CONNECTION_NORMAL && ($buf = fread($in, $bufSize)))
      {
      $size += strlen($buf);
      fwrite($out, $buf);
      }
    fclose($in);
    fclose($out);

    $modelLoader = new MIDAS_ModelLoader();
    $communityModel = $modelLoader->loadModel('Community');
    $community = $communityModel->getByName('Slicer');

    if(!$community)
      {
      throw new Exception('The Slicer community does not exist', -1);
      }
    $folderModel = $modelLoader->loadModel('Folder');
    $name = ucfirst($args['submissiontype']);
    $typeFolder = $folderModel->getFolderExists($name, $community->getPublicfolder());

    if(!$typeFolder)
      {
      throw new Exception('Folder '.$name.' does not exist in the Slicer community');
      }
    $componentLoader = new MIDAS_ComponentLoader();
    $uploadComponent = $componentLoader->loadComponent('Upload');
    $item = $uploadComponent->createUploadedItem($userDao, $args['name'], $tmpfile, $typeFolder);
    //TODO create package entry
    return array('item' => $item);
    }

  /**
   * Get all available slicer packages
   * @return an array of slicer packages
   */
  public function getAllPackages($value)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Package', 'slicerpackages');
    $model->loadDaoClass('PackageDao', 'slicerpackages');
    $daos = $model->getAll();

    $results = array();
    foreach($daos as $dao)
      {
      $results[] = array('package_id' => $dao->getKey(),
                         'item_id' => $dao->getItmeId(),
                         'os' => $dao->getOs(),
                         'arch' => $dao->getArch(),
                         'revision' => $dao->getRevision(),
                         'submission' => $dao->getSubmission(),
                         'package' => $dao->getPackage());
      }
    return $results;
    }

} // end class
