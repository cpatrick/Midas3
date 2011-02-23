<?php
/**
 * \class BitstreamModel
 * \brief Pdo Model
 */
class BitstreamModel extends AppModelPdo
{
  public $_name = 'bitstream';
  public $_key = 'bitstream_id';

  public $_mainData= array(
      'bitstream_id'=>  array('type'=>MIDAS_DATA),
      'itemrevision_id'=>  array('type'=>MIDAS_DATA),
      'assetstore_id'=>  array('type'=>MIDAS_DATA),
      'name' =>  array('type'=>MIDAS_DATA),
      'mimetype' =>  array('type'=>MIDAS_DATA),
      'sizebytes' =>  array('type'=>MIDAS_DATA),
      'checksum' =>  array('type'=>MIDAS_DATA),
      'path' =>  array('type'=>MIDAS_DATA),
      'assetstore_id' =>  array('type'=>MIDAS_DATA),
      'date' =>  array('type'=>MIDAS_DATA),
      //'itemrevision' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'ItemRevision', 'parent_column'=> 'itemrevision_id', 'child_column' => 'itemrevision_id'),
      'assetstore' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Assetstore', 'parent_column'=> 'assetstore_id', 'child_column' => 'assetstore_id'),
    );

}  // end class
?>
