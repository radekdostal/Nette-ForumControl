<?php
 /**
  * Default presenter
  *
  * @package   Nette\Extras\ForumControl
  * @example   http://addons.nette.org/forumcontrol
  * @version   $Id: DefaultPresenter.php,v 1.1.0 2011/08/13 21:16:02 dostal Exp $
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
    */
   public function renderDefault($id, $id2)
   {
     $this->template->dataOk = TRUE;

     try
     {
       ob_start();
       $this['forumControl']->render();
       $this->template->forumControl = ob_get_clean();
     }
     catch (ForumControlException $e)
     {
       $this->presenter->flashMessage($e->getMessage(), 'error');
       $this->template->dataOk = FALSE;
     }
   }

   /**
    * Forum Control component
    *
    * @access protected
    * @return ForumControl
    */
   protected function createComponentForumControl()
   {
     // 1 = forum ID from table "forum"
     $model = new ForumControl\ForumControlModel(1, new \DibiConnection($this->context->params['database']));

     // Params mapping
     $params = array(
       'topicId' => 'id',
       'allTopics' => 'id2',
       'selectedTopicsIds' => 'o'
     );

     return new ForumControl\ForumControl($this->context, $model, $params);
   }
 }
?>