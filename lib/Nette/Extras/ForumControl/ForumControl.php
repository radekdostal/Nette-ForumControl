<?php
 /**
  * Forum Control
  *
  * @package   Nette\Extras\ForumControl
  * @example   http://addons.nette.org/forumcontrol
  * @version   $Id: ForumControl.php,v 1.1.0 2011/08/14 08:36:22 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2011 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 namespace Nette\Extras\ForumControl;

 use Nette\Application\UI;

 class ForumControl extends UI\Control
 {
   /**#@+
    * Alerts
    *
    * @ignore
    * @since 1.0.0
    */
   const EXCEPTION_MESSAGE = 'An error occurred while discussion forum initialization.';
   /**#@- */

   /**
    * Global container
    *
    * @access private
    * @var Nette\DI\Container
    * @since 1.0.0
    */
   private $context;

   /**
    * Instance of model
    *
    * @access protected
    * @var IForumControlModel
    * @since 1.0.0
    */
   protected $model;

   /**
    * Params for mapping user params to forum params
    *
    * @access protected
    * @var array
    * @since 1.0.0
    */
   protected $forumParams;

   /**
    * Threads with topics
    *
    * @access protected
    * @var array
    * @since 1.0.0
    */
   protected $forumThreads;

   /**
    * Topic ID to reply
    *
    * @access protected
    * @var int
    * @since 1.0.0
    */
   protected $forumTopicId;

   /**
    * Show all topics?
    *
    * @access protected
    * @var bool
    * @since 1.0.0
    */
   protected $forumAllTopics;

   /**
    * Topic ID's to show
    *
    * @access protected
    * @var array
    * @since 1.0.0
    */
   protected $forumSelectedTopicsIds;

   /**
    * Initialization
    *
    * @access public
    * @param Nette\DI\Container $context global context
    * @param IForumControlModel $model instance of model
    * @param array $params forum params
    * @throws ForumControlException
    * @return void
    * @uses ForumControlModel::getThreads()
    * @since 1.0.0
    */
   public function __construct(\Nette\DI\Container $context, IForumControlModel $model, array $params)
   {
     parent::__construct();

     $this->context = $context;
     $this->model = $model;
     $this->forumParams = $params;

     try
     {
       $this->forumThreads = $this->model->getThreads();
     }
     catch (\DibiException $e)
     {
       throw new ForumControlException(self::EXCEPTION_MESSAGE);
     }
   }

   /**
    * Form to add topic
    *
    * @access protected
    * @return Nette\Application\UI\Form
    * @uses setForumParams()
    * @since 1.0.0
    */
   protected function createComponentForumForm()
   {
     $this->setForumParams();

     $form = new UI\Form();

     $form->addGroup(!$this->forumTopicId ? 'Topic' : 'Reply to topic');

     $form->addText('name', 'Name:', 50, 40)
          ->addRule($form::FILLED, 'Name must be filled.');

     if (!$this->forumTopicId)
     {
       $form->addText('title', 'Title:', 50, 100)
            ->addRule($form::FILLED, 'Title must be filled.');
     }

     $form->addTextArea('topic', 'Comment:', 87, 5)
          ->addRule($form::FILLED, 'Comment must be filled.');

     $form->addProtection('Security token did not match. Possible CSRF attack.');

     $form->addSubmit('insert', 'Insert');
     $form->onSuccess[] = callback($this, 'forumFormSubmitted');

     return $form;
   }

   /**
    * Form to show list of topics with checkboxes
    *
    * @access protected
    * @return Nette\Application\UI\Form
    * @since 1.0.0
    */
   protected function createComponentForumTopicsForm()
   {
     $form = new UI\Form();

     $form->setMethod('get');

     $container = $form->addContainer($this->forumParams['selectedTopicsIds']);

     foreach ($this->forumThreads as $thread)
       $container->addCheckbox($thread->id_thread, $thread->title);

     $form->addSubmit('show', 'View selected');

     return $form;
   }

   /**
    * Inserting a topic to a forum
    *
    * @access public
    * @param Nette\Application\UI\Form $form formulář
    * @return void
    * @uses ForumControlModel::getTopic()
    * @uses ForumControlModel::insert()
    * @since 1.0.0
    */
   public function forumFormSubmitted(UI\Form $form)
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
           $values['title'] = (\Nette\Utils\Strings::startsWith($replyTo->title, 'Re: ')) ? $replyTo->title : 'Re: '.$replyTo->title;
         }

         $values['ip'] = $this->context->httpRequest->remoteAddress;
         $values['date_time'] = date('Y-m-d H:i:s');

         $this->model->insert($values, $this->forumTopicId);

         $this->presenter->flashMessage('Your topic has been successfully inserted.');
       }
     }
     catch (\DibiException $e)
     {
       $this->presenter->flashMessage('An error occured while adding your topic.', 'error');
     }

     $this->presenter->redirect($this->presenter->view);
   }

   /**
    * Creating a template
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
   protected function createTemplate($class = NULL)
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

       $template->registerHelper('timeAgoInWords', 'Nette\Extras\ForumControl\ForumControlModel::timeAgoInWords');
     }
     catch (\DibiException $e)
     {
       throw new ForumControlException(self::EXCEPTION_MESSAGE);
     }

     return $template;
   }

   /**
    * Rendering template
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
    * Exists topic to reply?
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
       throw new ForumControlException('An error occured while attempting to respond to non-existing topic.');
   }

   /**
    * Sets forum params
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