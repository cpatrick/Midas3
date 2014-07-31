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
    // Check if the type of the assestore is suitable
    if($assetstoredao->getType() != MIDAS_ASSETSTORE_LOCAL)
      {
      throw new Zend_Exception("The assetstore type should be local to upload.");
      }

    // Check if the path of the assetstore exists on the server
    if(!is_dir($assetstoredao->getPath()))
      {
      throw new Zend_Exception("The assetstore path doesn't exist.");
      }

    // Check if the MD5 exists for the bitstream
    $checksum = $bitstreamdao->getChecksum();
    if(empty($checksum))
      {
      throw new Zend_Exception("Checksum is not set.");
      }

    // If we already have a file of this checksum in any assetstore, we point to it
    $bitstreamModel = MidasLoader::loadModel('Bitstream');
    $existing = $bitstreamModel->getByChecksum($checksum);
    if($existing)
      {
      if($copy === false)
        {
        unlink($bitstreamdao->getPath()); // Remove the temporary uploaded file
        }
      $bitstreamdao->setPath($existing->getPath());
      $bitstreamdao->setAssetstoreId($existing->getAssetstoreId());
      return;
      }

    // Two-level hierarchy.
    $path = substr($checksum, 0, 2).'/'.substr($checksum, 2, 2).'/'.$checksum;
    $fullpath = $assetstoredao->getPath().'/'.$path;

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
      default:
        break;
      }
    return true;
    } // end uploadBitstream()

  /** save upload item in the DB */
  public function createLinkItem($userDao, $name, $url, $parent = null, $sizebytes = 0, $checksum = ' ')
    {
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $assetstoreModel = MidasLoader::loadModel('Assetstore');
    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');

    if($userDao == null)
      {
      throw new Zend_Exception('Please log in');
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
    $item->setName($name);
    $item->setDescription('');
    $item->setSizebytes($sizebytes);
    $item->setType(0);
    $item->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE); // Must set this flag private initially
    $itemModel->save($item, false);

    //$feed = $feedModel->createFeed($userDao, MIDAS_FEED_CREATE_ITEM, $item);

    $folderModel->addItem($parent, $item);
    $itemModel->copyParentPolicies($item, $parent /*, $feed*/);

    //$feedpolicyuserModel->createPolicy($userDao, $feed, MIDAS_POLICY_ADMIN);
    //$itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);

    Zend_Loader::loadClass('ItemRevisionDao', BASE_PATH . '/core/models/dao');
    $itemRevisionDao = new ItemRevisionDao;
    $itemRevisionDao->setChanges('Initial revision');
    $itemRevisionDao->setUser_id($userDao->getKey());
    $itemRevisionDao->setDate(date("Y-m-d H:i:s"));
    $itemRevisionDao->setLicenseId(null);
    $itemModel->addRevision($item, $itemRevisionDao);

    // Add bitstreams to the revision
    Zend_Loader::loadClass('BitstreamDao', BASE_PATH . '/core/models/dao');
    $bitstreamDao = new BitstreamDao;
    $bitstreamDao->setName($url);
    $bitstreamDao->setPath($url);
    $bitstreamDao->setMimetype('url');
    $bitstreamDao->setSizebytes($sizebytes);
    $bitstreamDao->setChecksum($checksum);

    $assetstoreDao = $assetstoreModel->getDefault();
    $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());

    $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);

    $this->getLogger()->debug('Link item created ('.$item->getName().', id='.$item->getKey().')');
    return $item;
    }

  /**
   * Save an uploaded file in the database as an item with a new revision
   * @param userDao The user who is uploading the item
   * @param name The name of the item
   * @param path The path of the uploaded file on disk
   * @param parent The id of the parent folder to create the item in
   * @param license [optional][default=null] License text for the item
   * @param filemd5 [optional][default=''] If passed, will be used instead of calculating it ourselves
   * @param copy [optional][default=false] Boolean value for whether to copy or just move the item into the assetstore
   * @param revOnCollision[optional][default=false] Boolean value for whether to create a new revision on item name collision
   */
  public function createUploadedItem($userDao, $name, $path, $parent = null, $license = null, $filemd5 = '', $copy = false, $revOnCollision = false)
    {
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $assetstoreModel = MidasLoader::loadModel('Assetstore');
    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');

    if($userDao == null)
      {
      throw new Zend_Exception('Please log in');
      }

    if(is_numeric($parent))
      {
      $parent = $folderModel->load($parent);
      }

    if($parent == false || !$folderModel->policyCheck($parent, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Parent permissions errors');
      }

    // Note the conditional inner assignment of $item if user has selected new revision on name collision.
    // This is done so that we can elegantly fall through to the new item clause unless both the new
    // revision on collision option is on and a collision has actually occurred.
    if($revOnCollision && ($item = $folderModel->getItemByName($parent, $name)) != null)
      {
      $changes = '';
      $this->getLogger()->info('Item uploaded: revision overwrite ('.$item->getName().', id='.$item->getKey().')');
      }
    else // new item
      {
      Zend_Loader::loadClass('ItemDao', BASE_PATH.'/core/models/dao');
      $item = new ItemDao;
      $item->setName($name);
      $item->setDescription('');
      $item->setType(0);
      $item->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE); // Must set this flag private initially
      $itemModel->save($item, false);
      $changes = 'Initial revision';

      $folderModel->addItem($parent, $item);

      //$feed = $feedModel->createFeed($userDao, MIDAS_FEED_CREATE_ITEM, $item);

      $itemModel->copyParentPolicies($item, $parent /*, $feed*/);
      $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);
      //$feedpolicyuserModel->createPolicy($userDao, $feed, MIDAS_POLICY_ADMIN);
      $this->getLogger()->debug('Item uploaded ('.$item->getName().', id='.$item->getKey().')');
      }

    Zend_Loader::loadClass('ItemRevisionDao', BASE_PATH.'/core/models/dao');
    $itemRevisionDao = new ItemRevisionDao;
    $itemRevisionDao->setChanges($changes);
    $itemRevisionDao->setUser_id($userDao->getKey());
    $itemRevisionDao->setDate(date("Y-m-d H:i:s"));
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
    $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);

    Zend_Registry::get('notifier')->notifyEvent('EVENT_CORE_UPLOAD_FILE', array($item->toArray(), $itemRevisionDao->toArray()));
    Zend_Registry::get('notifier')->callback('CALLBACK_CORE_UPLOAD_FILE', array($item->toArray(), $itemRevisionDao->toArray()));
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

    $itemModel = MidasLoader::loadModel('Item');
    $bitstreamModel = MidasLoader::loadModel('Bitstream');
    $assetstoreModel = MidasLoader::loadModel('Assetstore');
    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');

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
      $itemRevisionDao->setDate(date("Y-m-d H:i:s"));
      $itemRevisionDao->setLicenseId($license);
      $itemModel->addRevision($item, $itemRevisionDao);
      }
    else
      {
      $itemRevisionDao->setChanges($changes);
      $itemRevisionDao->setLicenseId($license);
      $itemRevisionModel->save($itemRevisionDao);
      }

    // Add bitstreams to the revision
    Zend_Loader::loadClass('BitstreamDao', BASE_PATH . '/core/models/dao');
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

    $this->getLogger()->debug('Revision uploaded: ['.$bitstreamDao->getName().
                             '] into revision '.$itemRevisionDao->getKey().
                             ' (item '.$item->getKey().')');
    Zend_Registry::get('notifier')->notifyEvent('EVENT_CORE_UPLOAD_FILE', array($itemRevisionDao->getItem()->toArray(), $itemRevisionDao->toArray()));
    Zend_Registry::get('notifier')->callback('CALLBACK_CORE_UPLOAD_FILE', array($itemRevisionDao->getItem()->toArray(), $itemRevisionDao->toArray()));

    return $item;
    }//end
  } // end class
