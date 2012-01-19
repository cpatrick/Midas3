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

/**
 * Generates and sends piwik statistics reports to admin users
 */
class Statistics_ReportComponent extends AppComponent
{
  /** generate report */
  public function generate()
    {
    $loader = new MIDAS_ModelLoader();
    $errorModel = $loader->loadModel('Errorlog');
    $assetstoreModel = $loader->loadModel('Assetstore');
    $reportContent = '';
    $reportContent .= '<b>Midas Report: '.Zend_Registry::get('configGlobal')->application->name.'</b>';
    $reportContent .= '<br/>http://'.$_SERVER['SERVER_NAME'];

    $reportContent .= '<br/><br/><b>Status</b>';
    $errors = $errorModel->getLog(date('c', strtotime('-1 day'.date('Y-m-j G:i:s'))), date('c'), 'all', 2);
    $reportContent .= "<br/>Yesterday Errors: ".count($errors);
    $assetstores = $assetstoreModel->getAll();
    foreach($assetstores as $key => $assetstore)
      {
      $freeSpace = round((disk_free_space($assetstore->getPath()) / disk_total_space($assetstore->getPath())) * 100, 2);
      $reportContent .= '<br/>Assetstore '.$assetstore->getName().', Free space: '.$freeSpace.'%';
      }

    $reportContent .= '<br/><br/><b>Dashboard</b><br/>';
    $dashboard = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_DASHBOARD');
    ksort($dashboard);
    foreach($dashboard as $module => $dasboard)
      {
      $reportContent .= '-'.ucfirst($module);
      $reportContent .= '<table>';
      foreach($dasboard as $name => $status)
        {
        $reportContent .= '<tr>';
        $reportContent .= '  <td>'.$name.'</td>';
        if($status)
          {
          $reportContent .= '  <td>ok</td>';
          }
        else
          {
          $reportContent .= '  <td>Error</td>';
          }
        if(isset($status[1]))
          {
          $reportContent .= '  <td>'.$status[1].'</td>';
          }
        $reportContent .= '</tr>';
        }
      $reportContent .= '</table>';
      }

    $modulesConfig = Zend_Registry::get('configsModules');
    $content = file_get_contents($modulesConfig['statistics']->piwik->url.'/?module=API&format=json&method=VisitsSummary.get&period=day&date=yesterday&idSite='.$modulesConfig['statistics']->piwik->id.'&token_auth='.$modulesConfig['statistics']->piwik->apikey);
    $piwik = json_decode($content);
    $reportContent .=  '<br/><b>Statistics (yesterday)</b>';
    $reportContent .=  '<br/>Number of visit: '.$piwik->nb_uniq_visitors;
    $reportContent .=  '<br/>Number of actions: '.$piwik->nb_actions;
    $reportContent .=  '<br/>Average time on the website: '.$piwik->avg_time_on_site;
    $this->report = $reportContent;
    return $reportContent;
    }//end generate

  /** send the report to admins */
  public function send()
    {
    $loader = new MIDAS_ModelLoader();
    $userModel = $loader->loadModel('User');
    $mail = new Zend_Mail();
    $mail->setBodyHtml($this->report);
    $mail->setFrom('admin@foo.com', 'MIDAS');
    $mail->setSubject('MIDAS Report');

    $admins = $userModel->getAdmins();
    foreach($admins as $admin)
      {
      $mail->addTo($admin->getEmail(), $admin->getFullName());
      $mail->send();
      }
    }//end send

} // end class
?>
