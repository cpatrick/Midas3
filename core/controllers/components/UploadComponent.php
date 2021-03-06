<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
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

/** This class handles the upload of files into the different assetstores */
class UploadComponent extends AppComponent
{

  /** Helper function to create the two-level hierarchy */
  private function _createAssetstoreDirectory($directorypath)
    {
    if(!file_exists($directorypath))
      {
      if(!mkdir($directorypath))
        {
        throw new Zend_Exception("Cannot create directory: ".$directorypath);
        }
      chmod($directorypath, 0777);
      }
    } // end _createAssetstoreDirectory()

  /** Upload local bitstream */
  private function _uploadLocalBitstream($bitstreamdao, $assetstoredao, $copy = false)
    {
    // Check ifthe type of the assestore is suitable
    if($assetstoredao->getType() != MIDAS_ASSETSTORE_LOCAL)
      {
      throw new Zend_Exception("The assetstore type should be local to upload.");
      }

    // Check ifthe path of the assetstore exists on the server
    if(!is_dir($assetstoredao->getPath()))
      {
      throw new Zend_Exception("The assetstore path doesn't exist.");
      }

    // Check ifthe MD5 exists for the bitstream
    $checksum = $bitstreamdao->getChecksum();
    if(empty($checksum))
      {
      throw new Zend_Exception("Checksum is not set.");
      }

    // Two-level hierarchy.
    $path = substr($checksum, 0, 2).'/'.substr($checksum, 2, 2).'/'.$checksum;
    $fullpath = $assetstoredao->getPath().'/'.$path;

    // We assume this is the same file contents rather than an md5 collision
    if(file_exists($fullpath))
      {
      if($copy === false)
        {
        unlink($bitstreamdao->getPath()); // Remove the temporary uploaded file
        }
      $bitstreamdao->setPath($path);
      return;
      }

    //Create the directories
    $currentdir = $assetstoredao->getPath().'/'.substr($checksum, 0, 2);
    $this->_createAssetstoreDirectory($currentdir);
    $currentdir .= '/'.substr($checksum, 2, 2);
    $this->_createAssetstoreDirectory($currentdir);

    if($copy)
      {
      copy($bitstreamdao->getPath(), $fullpath);
      }
    else
      {
      rename($bitstreamdao->getPath(), $fullpath);
      }

    // Set the new path
    $bitstreamdao->setPath($path);
    } // end _uploadLocalBitstream()

  /** Upload a bitstream */
  function uploadBitstream($bitstreamdao, $assetstoredao, $copy = false)
    {
    $assetstoretype = $assetstoredao->getType();
    switch($assetstoretype)
      {
      case MIDAS_ASSETSTORE_LOCAL:
        $this->_uploadLocalBitstream($bitstreamdao, $assetstoredao, $copy);
        break;
      case MIDAS_ASSETSTORE_REMOTE:
        // Nothing to upload in that case, we return silently
        return true;
      case MIDAS_ASSETSTORE_AMAZON:
        throw new Zend_Exception("Amazon support is not implemented yet.");
        break;
      default :
        break;
      }
    return true;
    } // end uploadBitstream()

  /** save upload item in the DB */
  public function createLinkItem($userDao, $name, $url, $parent = null)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');
    $feedModel = $modelLoad->loadModel('Feed');
    $folderModel = $modelLoad->loadModel('Folder');
    $bitstreamModel = $modelLoad->loadModel('Bitstream');
    $assetstoreModel = $modelLoad->loadModel('Assetstore');
    $feedpolicygroupModel = $modelLoad->loadModel('Feedpolicygroup');
    $itemRevisionModel = $modelLoad->loadModel('ItemRevision');
    $feedpolicyuserModel = $modelLoad->loadModel('Feedpolicyuser');
    $itempolicyuserModel = $modelLoad->loadModel('Itempolicyuser');

    if($userDao == null)
      {
      throw new Zend_Exception('Please log in');
      }

    if($parent == null)
      {
      $parent = $userDao->getPrivateFolder();
      }
    if(is_numeric($parent))
      {
      $parent = $folderModel->load($parent);
      }

    if($parent == false || !$folderModel->policyCheck($parent, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Parent permissions errors');
      }

    Zend_Loader::loadClass('ItemDao', BASE_PATH.'/core/models/dao');
    $item = new ItemDao;
    $item->setName($itemModel->updateItemName($name, $parent));
    $item->setDescription('');
    $item->setType(0);
    $item->setThumbnail('');
    $itemModel->save($item);

    $feed = $feedModel->createFeed($userDao, MIDAS_FEED_CREATE_ITEM, $item);

    $folderModel->addItem($parent, $item);
    $itemModel->copyParentPolicies($item, $parent, $feed);

    $feedpolicyuserModel->createPolicy($userDao, $feed, MIDAS_POLICY_ADMIN);
    $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);

    Zend_Loader::loadClass('ItemRevisionDao', BASE_PATH . '/core/models/dao');
    $itemRevisionDao = new ItemRevisionDao;
    $itemRevisionDao->setChanges('Initial revision');
    $itemRevisionDao->setUser_id($userDao->getKey());
    $itemRevisionDao->setDate(date('c'));
    $itemRevisionDao->setLicenseId(null);
    $itemModel->addRevision($item, $itemRevisionDao);

    // Add bitstreams to the revision
    Zend_Loader::loadClass("BitstreamDao", BASE_PATH . '/core/models/dao');
    $bitstreamDao = new BitstreamDao;
    $bitstreamDao->setName($url);
    $bitstreamDao->setPath($url);
    $bitstreamDao->setMimetype('url');
    $bitstreamDao->setSizebytes(0);
    $bitstreamDao->setChecksum(' ');

    $assetstoreDao = $assetstoreModel->getDefault();
    $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());

    $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);

    $this->getLogger()->info(__METHOD__." Upload ok ");
    return $item;
    }//end createUploadedItem


  /**
   * Save an uploaded file in the database as an item with a new revision
   * @param userDao The user who is uploading the item
   * @param name The name of the item
   * @param path The path of the uploaded file on disk
   * @param parent The id of the parent folder to create the item in
   * @param license [optional][default=null] License text for the item
   * @param filemd5 [optional][default=''] If passed, will be used instead of calculating it ourselves
   * @param copy [optiona][default=false] Boolean value for whether to copy or just move the item into the assetstore
   */
  public function createUploadedItem($userDao, $name, $path, $parent = null, $license = null, $filemd5 = '', $copy = false)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');
    $feedModel = $modelLoad->loadModel('Feed');
    $folderModel = $modelLoad->loadModel('Folder');
    $bitstreamModel = $modelLoad->loadModel('Bitstream');
    $assetstoreModel = $modelLoad->loadModel('Assetstore');
    $feedpolicygroupModel = $modelLoad->loadModel('Feedpolicygroup');
    $itemRevisionModel = $modelLoad->loadModel('ItemRevision');
    $feedpolicyuserModel = $modelLoad->loadModel('Feedpolicyuser');
    $itempolicyuserModel = $modelLoad->loadModel('Itempolicyuser');

    if($userDao == null)
      {
      throw new Zend_Exception('Please log in');
      }

    if($parent == null)
      {
      $parent = $userDao->getPrivateFolder();
      }
    if(is_numeric($parent))
      {
      $parent = $folderModel->load($parent);
      }

    if($parent == false || !$folderModel->policyCheck($parent, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Parent permissions errors');
      }

    Zend_Loader::loadClass("ItemDao", BASE_PATH . '/core/models/dao');
    $item = new ItemDao;
    $item->setName($name);
    $item->setDescription('');
    $item->setType(0);
    $item->setThumbnail('');
    $itemModel->save($item);

    $feed = $feedModel->createFeed($userDao, MIDAS_FEED_CREATE_ITEM, $item);

    $folderModel->addItem($parent, $item);
    $itemModel->copyParentPolicies($item, $parent, $feed);

    $feedpolicyuserModel->createPolicy($userDao, $feed, MIDAS_POLICY_ADMIN);
    $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);

    Zend_Loader::loadClass("ItemRevisionDao", BASE_PATH . '/core/models/dao');
    $itemRevisionDao = new ItemRevisionDao;
    $itemRevisionDao->setChanges('Initial revision');
    $itemRevisionDao->setUser_id($userDao->getKey());
    $itemRevisionDao->setDate(date('c'));
    $itemRevisionDao->setLicenseId($license);
    $itemModel->addRevision($item, $itemRevisionDao);

    // Add bitstreams to the revision
    Zend_Loader::loadClass('BitstreamDao', BASE_PATH.'/core/models/dao');
    $bitstreamDao = new BitstreamDao;
    $bitstreamDao->setName($name);
    $bitstreamDao->setPath($path);
    $bitstreamDao->setChecksum($filemd5);
    $bitstreamDao->fillPropertiesFromPath();

    $assetstoreDao = $assetstoreModel->getDefault();
    $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());

    if($assetstoreDao == false)
      {
      throw new Zend_Exception("Unable to load default assetstore");
      }

    // Upload the bitstream if necessary (based on the assetstore type)
    $this->uploadBitstream($bitstreamDao, $assetstoreDao, $copy);
    $checksum = $bitstreamDao->getChecksum();
    $tmpBitstreamDao = $bitstreamModel->getByChecksum($checksum);
    if($tmpBitstreamDao != false)
      {
      $bitstreamDao->setPath($tmpBitstreamDao->getPath());
      $bitstreamDao->setAssetstoreId($tmpBitstreamDao->getAssetstoreId());
      }
    $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);

    $this->getLogger()->info(__METHOD__.' Upload ok :'.$path);
    Zend_Registry::get('notifier')->notifyEvent("EVENT_CORE_UPLOAD_FILE", array($item->toArray(), $itemRevisionDao->toArray()));
    return $item;
    }//end createUploadedItem

  /**
   * Save new revision in the database
   * @param userDao The user who is creating the revision
   * @param name The name of the file being used to create the revision
   * @param changes The changes comment by the user
   * @param itemId The item to create the new revision in
   * @param itemRevisionNumber [optional][default=null] Revision number for the item
   * @param license [optional][default=null] License text for the revision
   * @param filemd5 [optional][default=''] If passed, will use it instead of calculating it ourselves
   * @param copy [optional][default=false] If true, will copy the file. Otherwise it will just move it into the assetstore.
   */
  public function createNewRevision($userDao, $name, $path, $changes, $itemId, $itemRevisionNumber = null, $license = null, $filemd5 = '', $copy = false)
    {
    if($userDao == null)
      {
      throw new Zend_Exception('Please log in');
      }

    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');
    $feedModel = $modelLoad->loadModel('Feed');
    $bitstreamModel = $modelLoad->loadModel('Bitstream');
    $assetstoreModel = $modelLoad->loadModel('Assetstore');
    $feedpolicygroupModel = $modelLoad->loadModel('Feedpolicygroup');
    $itemRevisionModel = $modelLoad->loadModel('ItemRevision');
    $feedpolicyuserModel = $modelLoad->loadModel('Feedpolicyuser');

    $item = $itemModel->load($itemId);

    if($item == false)
      {
      throw new Zend_Exception('Unable to find item');
      }

    $itemRevisionDao = null;
    if(isset($itemRevisionNumber))
      {
      $revisions = $item->getRevisions();
      foreach($revisions as $revision)
        {
        if($itemRevisionNumber == $revision->getRevision())
          {
          $itemRevisionDao = $revision;
          break;
          }
        }
      }

    if(!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Parent permissions errors');
      }


    if($itemRevisionDao == null)
      {
      Zend_Loader::loadClass("ItemRevisionDao", BASE_PATH . '/core/models/dao');
      $itemRevisionDao = new ItemRevisionDao;
      $itemRevisionDao->setChanges($changes);
      $itemRevisionDao->setUser_id($userDao->getKey());
      $itemRevisionDao->setDate(date('c'));
      $itemRevisionDao->setLicenseId($license);
      $itemModel->addRevision($item, $itemRevisionDao);

      $feed = $feedModel->createFeed($userDao, MIDAS_FEED_CREATE_REVISION, $itemRevisionDao);

      $groupPolicies = $item->getItempolicygroup();
      $userPolicies = $item->getItempolicyuser();

      //copy policies
      if($feed != null && $feed instanceof FeedDao)
        {
        foreach($groupPolicies as $key => $policy)
          {
          $feedpolicygroupModel->createPolicy($policy->getGroup(), $feed, $policy->getPolicy());
          }
        foreach($userPolicies as $key => $policy)
          {
          $feedpolicyuserModel->createPolicy($policy->getUser(), $feed, $policy->getPolicy());
          }
        }
      }
    else
      {
      $itemRevisionDao->setChanges($changes);
      $itemRevisionDao->setLicenseId($license);
      $itemRevisionModel->save($itemRevisionDao);
      }


    // Add bitstreams to the revision
    Zend_Loader::loadClass("BitstreamDao", BASE_PATH . '/core/models/dao');
    $bitstreamDao = new BitstreamDao;
    $bitstreamDao->setName($name);
    $bitstreamDao->setPath($path);
    $bitstreamDao->setChecksum($filemd5);
    $bitstreamDao->fillPropertiesFromPath();

    $assetstoreDao = $assetstoreModel->getDefault();
    $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());

    // Upload the bitstream if necessary (based on the assetstore type)
    $this->uploadBitstream($bitstreamDao, $assetstoreDao, $copy);
    $checksum = $bitstreamDao->getChecksum();
    $tmpBitstreamDao = $bitstreamModel->getByChecksum($checksum);
    if($tmpBitstreamDao != false)
      {
      $bitstreamDao->setPath($tmpBitstreamDao->getPath());
      $bitstreamDao->setAssetstoreId($tmpBitstreamDao->getAssetstoreId());
      }
    $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);
    // now that we have updated the itemRevision, the item may be stale
    $item = $itemModel->load($itemId);

    $this->getLogger()->info(__METHOD__." Upload ok :".$path);
    Zend_Registry::get('notifier')->notifyEvent("EVENT_CORE_UPLOAD_FILE", array($itemRevisionDao->getItem()->toArray(), $itemRevisionDao->toArray()));

    return $item;
    }//end
} // end class UploadComponent
?>
