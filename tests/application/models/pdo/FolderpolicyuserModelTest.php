<?php

require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class FolderpolicyuserModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Folderpolicyuser'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testCreatePolicyAndGetPolicy()
    {
    $folderFile=$this->loadData('Folder','default');
    $usersFile=$this->loadData('User','default');
    $policy=$this->Folderpolicyuser->createPolicy($usersFile[0],$folderFile[5],1);
    $this->assertEquals(true,$policy->saved);
    $policy=$this->Folderpolicyuser->getPolicy($usersFile[0],$folderFile[5]);
    $this->assertNotEquals(false, $policy);
    }
  }
