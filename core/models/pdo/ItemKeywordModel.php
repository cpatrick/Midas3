<?php
/**
 * \class ItemKeywordModel
 * \brief Pdo Model
 */
class ItemKeywordModel extends MIDASItemKeywordModel
{
  
  /** Get the keyword from the search.
   * @return Array of ItemDao */
  function getItemsFromSearch($searchterm,$userDao)
    {
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
          
    // Apparently it's slow to do a like in a subquery so we run it first  
    $sql = $this->database->select()->from(array('itemkeyword'),array('keyword_id'))
                   ->where('value LIKE ?','%'.$searchterm.'%');                 
    $ids = '(';
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      if($ids != '(')
        {
        $ids .= ',';  
        } 
      $ids .= $row->keyword_id;
      }
    $ids .= ')';

    // If we don't have any data we return
    if(count($rowset) == 0)
      {
      return $return;  
      }
    
    $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'itempolicyuser'),
                                 array('item_id'))
                          ->where('policy >= ?', MIDAS_POLICY_READ)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'itempolicygroup'),
                           array('item_id'))
                    ->where('policy >= ?', MIDAS_POLICY_READ)
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->database->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));


    $sql = $this->database->select()->from(array('i' => 'item'),array('item_id','name','count(*)'))
                                          ->join(array('i2k' => 'item2keyword'),'i.item_id=i2k.item_id')
                                          ->where('i2k.keyword_id IN '.$ids)                                         
                                          ->where('( i.item_id IN ('.$subqueryUser.') OR
                                            i.item_id IN ('.$subqueryGroup.'))' )
                                          ->group('i.name')
                                          ->setIntegrityCheck(false)
                                          ->limit(14);
    

    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $row)
      {
      $tmpDao= new ItemDao();
      $tmpDao->setItemId($row->item_id);
      $tmpDao->setName($row->name);
      $tmpDao->count = $row['count(*)'];
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getItemsFromSearch()

  /** Custom insert function
   * @return boolean */
  function insertKeyword($keyword)
    {
    if(!$keyword instanceof ItemKeywordDao)
      {
      throw new Zend_Exception("Should be a keyword" );
      }

    // Check if the keyword already exists
    $row = $this->database->fetchRow($this->database->select()->from($this->_name)
                                          ->where('value=?',$keyword->getValue()));
    if($row)
      {
      $row->relevance += 1; // increase the relevance
      $return =$row->save();
      $keyword->setKeywordId($row->keyword_id);
      }
    else
      {
      $keyword->setRelevance(1);
      $return = parent::save($keyword);
      }
    unset($row);
    return $return;
    } // end insertKeyword()

  /** custom save function
  * @return boolean */
  function save($keyword)
    {
    return $this->insertKeyword($keyword);
    }

} // end class
?>
