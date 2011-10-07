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
  
  /** Helper function allowing to generate breadcrumb */
  private function _breadcrumb($subfolder = "", $name = "")
    {
    // TODO Generalize the concept of 'breadcrumb' for plugins ? Look at Zend BreadCrumb ?
    $breadcrumb  = '<link type="text/css" rel="stylesheet" href="'.$this->view->coreWebroot.'/public/css/common/common.browser.css" />';
    $breadcrumb .= '<link type="text/css" rel="stylesheet" href="'.$this->view->coreWebroot.'/public/css/folder/folder.view.css" />';
    $breadcrumb .= '<ul class="pathBrowser">';
    $breadcrumb .= '  <li class ="pathCommunity"><img alt = "" src = "'.$this->view->moduleWebroot.'/public/images/'.$this->moduleName.'.png" /><span><a href="'.$this->view->webroot.'/'.$this->moduleName.'">&nbsp;'.$this->view->moduleFullName.'</a></span></li>';
    if($subfolder != "")
      {
      if($name == "")
        {
        $name = $subfolder;
        }
      $breadcrumb .= '  <li class ="pathFolder"><img alt = "" src = "'.$this->view->moduleWebroot.'/public/images/'.$this->moduleName.'_'.$subfolder.'.png" /><span><a href="'.$this->view->webroot.'/'.$this->moduleName.'/'.$subfolder.'">&nbsp;'.$name.'</a></span></li>';
      }
    $breadcrumb .= '</ul>';
    return $breadcrumb;
    }

  /** index action*/
  public function indexAction()
    {
    $this->view->header = $this->_breadcrumb();
    
    $this->view->nPackages = count($this->Slicerpackages_Package->getAll());
    $community = $this->Community->getByName('Slicer');
    $folders = $community->getPublicFolder()->getFolders();
    foreach($folders as $folder)
      {
      if($folder->getName() == 'Release')
        {
        $this->view->packageSets = array();
        foreach($folder->getFolders() as $subFolder)
          {
          $this->view->packageSets[$subFolder->getName()] = array();
          foreach( $subFolder->getItems() as $item )
            {
            $package = $this->Slicerpackages_Package->getByItemId($item->getKey());
            if($package)
              {
              $this->view->packageSets[$subFolder->getName()][$item->getName()] = $package;
              }
            }
          }
        break;
        }
      }
    }

  /** Admin action */
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
    $communityDao = $this->Community->createCommunity('Slicer',
                                                      'Community for storing slicer packages',
                                                      MIDAS_COMMUNITY_PUBLIC,
                                                      $userDao,
                                                      true);
                                                      
    $this->Folder->createFolder('Release',
                                'For Release Builds',
                                $communityDao->getPublicFolder());
    $this->Folder->createFolder('Nightly',
                                'For Nightly Builds',
                                $communityDao->getPublicFolder());
    $this->Folder->createFolder('Experimental',
                                'For Experimental Builds',
                                $communityDao->getPublicFolder());
    echo json_encode(array('msg' => 'Slicer Community created successfully!',
                           'stat' => 1));
    exit();
    }

  public function advancedAction()
    {
    $this->view->header = $this->_breadcrumb("advanced", "Search");
    $this->view->packages = $this->Slicerpackages_Package->getAll();
    $this->view->nPackages = count($this->view->packages);
    $community = $this->Community->getByName('Slicer');
    $folders = array();
    foreach($community->getPublicFolder()->getFolders() as $folder)
      {
      array_push($folders, $folder->getName());
      }
    $this->view->folders = $folders;
    }

}//end class
