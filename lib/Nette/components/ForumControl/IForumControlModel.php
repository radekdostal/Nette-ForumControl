<?php
 /**
  * Forum Control Model Interface
  *
  * @package   Nette\Extras\ForumControl
  * @version   $Id: IForumControlModel.php,v 1.0.0 2011/04/09 11:53:10 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2011 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 interface IForumControlModel
 {
   public function getCount();
   public function getTitle();
   public function getTopic($topicId);
   public function getTopics(array $topicIds);
   public function getThreads();
   public function existsTopic($topicId);
   public function insert(array $data, $topicId);
   public static function timeAgoInWords($time);
 }
?>