<?php
 class DefaultPresenter extends BasePresenter
 {
   /**
    * Diskuzní fórum
    *
    * @access public
    * @param int $id ID vlákna na které se reaguje (0 = nový názor)
    * @param int $id2 příznak pro zobrazení všech příspěvků (hodnota 1)
    * @return void
    */
   public function renderDefault($id, $id2)
   {
     $this->template->dataOk = TRUE;

     try
     {
       // Tento způsob je kvůli zpracování výjimek v presenteru, jinak by se musely zachytávat až v šabloně
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
    * Komponenta diskuzního fóra
    *
    * @access protected
    * @return ForumControl
    */
   protected function createComponentForumControl()
   {
     // 1 = ID diskuzního fóra z tabulky forum
     $model = new ForumControlModel(1, NEnvironment::getConfig('database'));

     // Mapování parametrů
     $params = array(
       'topicId' => 'id',
       'allTopics' => 'id2',
       'selectedTopicsIds' => 'o'
     );

     return new ForumControl($model, $params);
   }
 }
?>