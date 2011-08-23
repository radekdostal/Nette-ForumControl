<?php
 /**
  * Default presenter
  *
  * @package   Nette\Extras\ForumControl
  * @example   http://addons.nette.org/forumcontrol
  * @version   $Id: DefaultPresenter.php,v 1.2.0 2011/08/23 12:11:42 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2011 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 use Nette\Extras\ForumControl;

 class DefaultPresenter extends BasePresenter
 {
   /**
    * Discussion forum
    *
    * @access public
    * @param int $id topic ID to reply (0 = new topic)
    * @param int $id2 show all topics? (1 = yes)
    * @return void
    * @since 1.0.0
    */
   public function actionDefault($id, $id2)
   {
   }

   /**
    * Forum Control component
    *
    * @access protected
    * @return ForumControl
    * @since 1.0.0
    */
   protected function createComponentForumControl()
   {
     $forumId = 1; // 1 = forum ID from table "forum"
     $model = new ForumControl\ForumControlModel($forumId, new \DibiConnection($this->context->params['database']));

     // Params mapping
     $params = array(
       'topicId' => $this->getParam('id'),
       'allTopics' => $this->getParam('id2'),
       'selectedTopicsIds' => array('name' => 'o', 'value' => $this->getParam('o'))
     );

     return new ForumControl\ForumControl($this->context, $model, $params);
   }
 }
?>