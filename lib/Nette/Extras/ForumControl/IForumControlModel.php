<?php
 /**
  * Forum Control Model Interface
  *
  * @package   Nette\Extras\ForumControl
  * @version   $Id: IForumControlModel.php,v 1.2.0 2011/08/23 12:01:44 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2011 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 namespace Nette\Extras\ForumControl;

 interface IForumControlModel
 {
   public function getCount();
   public function getTitle();
   public function getTopic($topicId);
   public function getTopics(array $topicIds);
   public function getThreads();
   public function existsTopic($topicId);
   public function insert(\Nette\ArrayHash $data, $topicId);
   public static function timeAgoInWords($time);
 }
?>