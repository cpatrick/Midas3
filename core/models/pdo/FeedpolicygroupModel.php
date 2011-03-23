<?php
/**
 * \class FeedpolicygroupModel
 * \brief Pdo Model
 */
class FeedpolicygroupModel extends MIDASFeedpolicygroupModel
{
  /** create a policy
   * @return FeedpolicygroupDao*/
  public function createPolicy($group, $feed, $policy)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feedDao.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$group->saved && !$feed->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($group,$feed) !== false)
      {
      $this->database->delete($this->getPolicy($group,$feed));
      }
    $this->loadDaoClass('FeedpolicygroupDao');
    $policyGroupDao=new FeedpolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setFeedId($feed->getFeedId());
    $policyGroupDao->setPolicy($policy);
    $this->database->save($policyGroupDao);
    return $policyGroupDao;
    }

  /** getPolicy
   * @return FeedpolicygroupDao
   */
  public function getPolicy($group, $feed)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
    return $this->initDao('Feedpolicygroup',$this->database->fetchRow($this->database->select()->where('feed_id = ?',$feed->getKey())->where('group_id = ?',$group->getKey())));
    }
    
} // end class
?>
