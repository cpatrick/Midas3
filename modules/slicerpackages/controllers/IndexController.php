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

  public $_models = array('User', 'Item', 'Folder', 'Community');
  public $_moduleModels = array('Package');
  public $_daos = array('User', 'Item', 'Folder', 'Community');
  public $_moduleDaos = array('Package');
  public $_components = array('Utility');
  public $_moduleComponents = array();
  public $_forms = array();
  public $_moduleForms = array();

  /** index action*/
  public function indexAction()
    {
    $this->view->nPackages = count($this->Slicerpackages_Package->getAll());
    $community = $this->Community->getByName('Slicer');
    $folders = $community->getPublicFolder()->getFolders();
    foreach($folders as $folder)
      {
      if($folder->getName() == 'Release')
        {
        $this->view->folders= $folder->getFolders();
        break;
        }
      }
    }

  public function adminAction()
    {
    $this->view->nPackages = count($this->Slicerpackages_Package->getAll());
    }

  /** Function for creating community and folder hierarchy */
  public function createstructureAction()
    {
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $userDao = $this->userSession->Dao;
    if($this->Community->getByName('Slicer'))
      {
      echo json_encode(array('msg' => 'Slicer Community already created.',
                             'stat' => 0));
      exit();
      }
    $communityDao =
      $this->Community->createCommunity('Slicer',
                                        'Community for storing slicer packages',
                                        MIDAS_COMMUNITY_PUBLIC,
                                        $userDao,
                                        true);
    $this->Folder->createFolder('Nightly',
                                'For Nightly Builds',
                                $communityDao->getPublicFolder());
    $this->Folder->createFolder('Experimental',
                                'For Experimental Builds',
                                $communityDao->getPublicFolder());
    $this->Folder->createFolder('Release',
                                'For Release Builds',
                                $communityDao->getPublicFolder());
    echo json_encode(array('msg' => 'Slicer Community created successfully!',
                           'stat' => 1));
    exit();
    }

}//end class
