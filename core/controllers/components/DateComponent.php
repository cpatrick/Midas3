<?php
class DateComponent extends AppComponent
{ 
  // format date (ex: 01/14/2011 or 14/01/2011 (fr or en)
  static public function formatDate($timestamp)
    {
    if(!is_numeric($timestamp))
      {
      $timestamp=strtotime($timestamp);
      if($timestamp==false)
        {
        return "";
        }
      }
    if(Zend_Registry::get('configGlobal')->application->lang=='fr')
       {
       return date('d',$timestamp).'/'.date('m',$timestamp).'/'.date('Y',$timestamp);
       }
     else
       {
       return date('m',$timestamp).'/'.date('d',$timestamp).'/'.date('Y',$timestamp);
       }
    }
  //format the date (ex: 5 days ago)
  static public function ago($timestamp,$only_time=false)
    {
    if(!is_numeric($timestamp))
      {
      $timestamp=strtotime($timestamp);
      if($timestamp==false)
        {
        return "";
        }
      }
     $difference = time() - $timestamp;
     $periods = array("second", "minute", "hour", "day", "week", "month", "years", "decade");
     $periodsFr = array("seconde", "minute", "heure", "jour", "semaine", "mois", "ann�e", "d�cades");
     $lengths = array("60","60","24","7","4.35","12","10");
     for($j = 0; $difference >= $lengths[$j]; $j++)
     $difference /= $lengths[$j];
     $difference = round($difference);
     if($difference != 1)
       {
       $periods[$j].= "s";
       if($periodsFr[$j]!='mois') $periodsFr[$j].= "s";
       }
      
     if($only_time)
       {
       return $difference.' '.$periodsFr[$j];
       }
     if(Zend_Registry::get('configGlobal')->application->lang=='fr')
       {
       $text = "Il y a $difference $periodsFr[$j]";
       }
     else
       {
       $text = "$difference $periods[$j] ago";
       }
     return $text;
    }
    
} // end class
?>