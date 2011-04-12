<?php
 /**
  * Forum Control
  *
  * TODO zachovávat případné existující parametry v URL při tvorbě odkazů
  *
  * @package   Nette\Extras\ForumControl
  * @example   http://nettephp.com/extras/forumcontrol
  * @version   $Id: ForumControl.php,v 1.0.0 2011/04/11 12:00:22 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2011 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 class ForumControl extends NControl
 {
   /**#@+
    * Upozornění
    *
    * @ignore
    */
   const EXCEPTION_MESSAGE = 'Došlo k chybě při inicializaci diskuzního fóra.';
   /**#@- */

   /**
    * Objekt modelu
    *
    * @access protected
    * @var IForumControlModel
    * @since 1.0.0
    */
   protected $model;

   /**
    * Parametry fóra pro mapování uživatelských parametrů na parametry fóra
    *
    * @access protected
    * @var array
    * @since 1.0.0
    */
   protected $forumParams;

   /**
    * Vlákna s příspěvky
    *
    * @access protected
    * @var array
    * @since 1.0.0
    */
   protected $forumThreads;

   /**
    * ID příspěvku, na který se reaguje
    *
    * @access protected
    * @var int
    * @since 1.0.0
    */
   protected $forumTopicId;

   /**
    * Příznak pro zobrazení všech příspěvků
    *
    * @access protected
    * @var bool
    * @since 1.0.0
    */
   protected $forumAllTopics;

   /**
    * ID příspěvků, které se mají zobrazit
    *
    * @access protected
    * @var array
    * @since 1.0.0
    */
   protected $forumSelectedTopicsIds;

   /**
    * Inicializace
    *
    * @access public
    * @param IForumControlModel $model objekt modelu
    * @param array $params parametry fóra
    * @throws ForumControlException
    * @return void
    * @uses ForumControlModel::getThreads()
    * @since 1.0.0
    */
   public function __construct(IForumControlModel $model, array $params)
   {
     parent::__construct();

     $this->model = $model;
     $this->forumParams = $params;

     try
     {
       $this->forumThreads = $this->model->getThreads();
     }
     catch (DibiException $e)
     {
       throw new ForumControlException(self::EXCEPTION_MESSAGE);
     }
   }

   /**
    * Formulář pro přidání názoru do diskuzního fóra
    *
    * @access protected
    * @return NAppForm
    * @uses setForumParams()
    * @since 1.0.0
    */
   protected function createComponentForumForm()
   {
     $this->setForumParams();

     $form = new NAppForm();

     $form->addGroup(!$this->forumTopicId ? 'Názor' : 'Reakce na názor');

     $form->addText('name', 'Jméno:', 50, 40)
          ->addRule(NForm::FILLED, 'Jméno musí být vyplněno.');

     if (!$this->forumTopicId)
     {
       $form->addText('title', 'Titulek:', 50, 100)
            ->addRule(NForm::FILLED, 'Titulek musí být vyplněn.');
     }

     $form->addTextArea('topic', 'Komentář:', 87, 5)
          ->addRule(NForm::FILLED, 'Komentář musí být vyplněn.');

     $form->addProtection('Vypršel ochranný časový limit, odešlete prosím formulář ještě jednou.');

     $form->addSubmit('insert', 'Vložit');
     $form->onSubmit[] = callback($this, 'forumFormSubmitted');

     return $form;
   }

   /**
    * Formulář pro zobrazení seznamu názorů s checkboxy
    *
    * @access protected
    * @return NAppForm
    * @since 1.0.0
    */
   protected function createComponentForumTopicsForm()
   {
     $form = new NAppForm();

     $form->setMethod('get');

     $container = $form->addContainer($this->forumParams['selectedTopicsIds']);

     foreach ($this->forumThreads as $thread)
       $container->addCheckbox($thread->id_thread, $thread->title);

     $form->addSubmit('show', 'Zobrazit vybrané');

     return $form;
   }

   /**
    * Vložení názoru do diskuzního fóra
    *
    * @access public
    * @param NAppForm $form formulář
    * @return void
    * @uses ForumControlModel::getTopic()
    * @uses ForumControlModel::insert()
    * @since 1.0.0
    */
   public function forumFormSubmitted(NAppForm $form)
   {
     try
     {
       if ($form['insert']->isSubmittedBy())
       {
         $values = (array) $form->values;

         setcookie('forumControl-name', $values['name'], strtotime('+1 month'));

         if ($this->forumTopicId)
         {
           $replyTo = $this->model->getTopic($this->forumTopicId);
           $values['title'] = (NString::startsWith($replyTo->title, 'Re: ')) ? $replyTo->title : 'Re: '.$replyTo->title;
         }

         $values['ip'] = NEnvironment::getHttpRequest()->getRemoteAddress();
         $values['date_time'] = date('Y-m-d H:i:s');

         $this->model->insert($values, $this->forumTopicId);

         $this->presenter->flashMessage('Váš názor byl úspěšně vložen.');
       }
     }
     catch (DibiException $e)
     {
       $this->presenter->flashMessage('Došlo k chybě při vkládání Vašeho názoru.', 'error');
     }

     $this->presenter->redirect($this->presenter->view);
   }

   /**
    * Vytvoření šablony
    *
    * @access protected
    * @throws ForumControlException
    * @return ITemplate
    * @uses ForumControlModel::getCount()
    * @uses ForumControlModel::getTopic()
    * @uses ForumControlModel::getTopics()
    * @uses ForumControlModel::timeAgoInWords()
    * @since 1.0.0
    */
   protected function createTemplate()
   {
     $template = parent::createTemplate();

     $isNew = (bool) !$this->forumTopicId;
     $form = $this['forumForm'];

     if (!$form->isSubmitted() && isset($_COOKIE['forumControl-name']))
     {
       $form->setDefaults(array(
         'name' => $_COOKIE['forumControl-name'])
       );
     }

     $topicsForm = $this['forumTopicsForm'];
     $topicsForm->setAction($this->presenter->link($this->presenter->view));

     if ($this->presenter->getParam($this->forumParams['selectedTopicsIds']))
       $topicsForm->setDefaults(array($this->forumParams['selectedTopicsIds'] => $this->presenter->getParam($this->forumParams['selectedTopicsIds'])));

     try
     {
       $template->forumTopicsCount = $this->model->getCount();
       $template->forumReplyTo = (!$isNew) ? $this->model->getTopic($this->forumTopicId) : '';
       $template->forumShowForm = ($this->forumTopicId !== NULL && $this->forumAllTopics === FALSE && !is_array($this->forumSelectedTopicsIds));
       $template->forumShowAll = ($this->forumAllTopics === TRUE);
       $template->forumSelectedTopics = (is_array($this->forumSelectedTopicsIds)) ? $this->model->getTopics(array_keys($this->forumSelectedTopicsIds)) : FALSE;
       $template->forumSelectedTopicsIds = $this->forumParams['selectedTopicsIds'];
       $template->forumThreads = $this->forumThreads;
       $template->forumTopicsForm = $topicsForm;

       $template->registerHelper('timeAgoInWords', 'ForumControlModel::timeAgoInWords');
     }
     catch (DibiException $e)
     {
       throw new ForumControlException(self::EXCEPTION_MESSAGE);
     }

     return $template;
   }

   /**
    * Renderování šablony
    *
    * @access public
    * @throws ForumControlException
    * @return void
    * @uses setForumParams()
    * @since 1.0.0
    */
   public function render()
   {
     $this->setForumParams();

     try
     {
       $this->template->setFile(dirname(__FILE__).'/ForumFormControl.latte');
       $this->template->render();
     }
     catch (ForumControlException $e)
     {
       throw new ForumControlException($e->getMessage());
     }
   }

   /**
    * Kontrola existence názoru, na který se reaguje
    *
    * @access protected
    * @throws ForumControlException
    * @return void
    * @uses ForumControlModel::existsTopic()
    * @since 1.0.0
    */
   protected function checkTopicId()
   {
     if ($this->forumTopicId && !$this->model->existsTopic($this->forumTopicId))
       throw new ForumControlException('Došlo k chybě při pokusu o reakci na neexistující názor.');
   }

   /**
    * Nastavení parametrů fóra
    *
    * @access private
    * @return void
    * @uses checkTopicId()
    * @since 1.0.0
    */
   private function setForumParams()
   {
     $this->forumTopicId = $this->presenter->getParam($this->forumParams['topicId']);
     $this->forumAllTopics = (bool) $this->presenter->getParam($this->forumParams['allTopics']);
     $this->forumSelectedTopicsIds = $this->presenter->getParam($this->forumParams['selectedTopicsIds']);

     $this->checkTopicId();
   }
 }
?>