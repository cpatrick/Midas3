<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 20 rue de la Villette. 69328 Lyon, FRANCE
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
include_once BASE_PATH . '/library/KWUtils.php';

/**
 * an executor class for the batchmake module, used to forward calls
 * to exec on to KWUtils.
 */
class Batchmake_Executor
{
  /**
   * forwards a call to this method on to KWUtils.exec, with the same
   * method signature.
   * @param type $command
   * @param type $output
   * @param type $chdir
   * @param type $return_val
   */
  public function exec($command, &$output = null, $chdir = "", &$return_val = null)
    {
    KWUtils::exec($command, $output, $chdir, $return_val);
    }
} // end class



