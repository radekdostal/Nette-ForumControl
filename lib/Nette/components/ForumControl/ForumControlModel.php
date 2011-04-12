<?php
 /**
  * Forum Control Model
  *
  * @package   Nette\Extras\ForumControl
  * @version   $Id: ForumControlModel.php,v 1.0.0 2011/04/09 11:38:14 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2011 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 class ForumControlModel extends NObject implements IForumControlModel
 {
   /**
    * ID fóra
    *
    * @access protected
    * @var int
    * @since 1.0.0
    */
   protected $forumId;

   /**
    * Objekt spojení s databází
    *
    * @access protected
    * @var DibiConnection
    * @since 1.0.0
    */
   protected $connection;

   /**#@+
    * Databázové tabulky
    *
    * @access protected
    * @var string
    * @since 1.0.0
    */
   protected $tForum;
   protected $tThreads;
   /**#@- */

   /**
    * Inicializace
    *
    * @access public
    * @param int $forumId ID diskuzního fóra
    * @param mixed $config přihlašovací parametry k databázi
    * @return void
    * @since 1.0.0
    */
   public function __construct($forumId, $config)
   {
     $this->forumId = (int) $forumId;
     $this->connection = new DibiConnection($config);

     $this->tForum = 'forum';
     $this->tThreads = 'forum_threads';
   }

   /**
    * Získání počtu názorů
    *
    * @access public
    * @return int
    * @since 1.0.0
    */
   public function getCount()
   {
     return (int) $this->connection->select('COUNT([id_thread])')
                                   ->from($this->tThreads)
                                   ->where('[id_forum] = %i', $this->forumId)
                                   ->fetchSingle();
   }

   /**
    * Získání názvu fóra
    *
    * @access public
    * @return string
    * @since 1.0.0
    */
   public function getTitle()
   {
     return $this->connection->select('forum')
                             ->from($this->tForum)
                             ->where('[id_forum] = %i', $this->forumId)
                             ->fetchSingle();
   }

   /**
    * Získání příspěvku
    *
    * @access public
    * @param int $topicId ID příspěvku
    * @return DibiRow
    * @since 1.0.0
    */
   public function getTopic($topicId)
   {
     return $this->connection->select('*')
                             ->from($this->tThreads)
                             ->where('[id_forum] = %i', $this->forumId)
                               ->and('[id_thread] = %i', $topicId)
                             ->fetch();
   }

   /**
    * Získání vybraných příspěvků
    *
    * @access public
    * @param array $topicIds pole ID příspěvků
    * @return array
    * @since 1.0.0
    */
   public function getTopics(array $topicIds)
   {
     $topicIds = implode(', ', $topicIds);

     return $this->connection->select('*')
                             ->from($this->tThreads)
                             ->where('[id_forum] = %i', $this->forumId)
                               ->and('[id_thread] IN (%sql)', $topicIds)
                             ->orderBy('[sequence]', dibi::ASC)
                             ->fetchAll();
   }

   /**
    * Získání vlákna
    *
    * @access public
    * @return array
    * @since 1.0.0
    */
   public function getThreads()
   {
     return $this->connection->select(array(
                                 'id_thread',
                                 'depth',
                                 'name',
                                 'title',
                                 'topic',
                                 'date_time')
                               )
                             ->select('DATE_FORMAT([date_time], %s)', '%e. %c. %Y, %H:%i')->as('cz_date_time')
                             ->from($this->tThreads)
                             ->where('[id_forum] = %i', $this->forumId)
                             ->orderBy('[sequence]', dibi::ASC)
                             ->fetchAll();
   }

   /**
    * Vložení příspěvku
    *
    * @access public
    * @param array $data data
    * @param int $topicId ID příspěvku, na který se reaguje
    * @return void
    * @uses getTopic()
    * @since 1.0.0
    */
   public function insert(array $data, $topicId)
   {
     $data['id_forum'] = $this->forumId;

     $this->connection->query('LOCK TABLES ['.$this->tThreads.'] WRITE');

     $re = $this->getTopic($topicId);

     // Zjištění pořadí a hloubky příspěvku, na který se reaguje
     if ($topicId && $re !== FALSE)
     {
       // Zjištění pořadí příspěvku, na jehož místo se bude vkládat - první
       // následující s menší nebo stejnou hloubkou jako rodič
       $re = $this->connection->select('MIN([sequence]) - 1')->as('new_sequence')
                              ->select('%i + %i', $re->depth, 1)->as('new_depth')
                              ->from($this->tThreads)
                              ->where('[id_forum] = %i', $this->forumId)
                                ->and('[sequence] > %i', $re->sequence)
                                ->and('[depth] <= %i', $re->depth)
                              ->fetch();

       // Bude se vkládat doprostřed tabulky, posunou se následující záznamy
       if ($re->new_sequence)
       {
         $this->connection->query('UPDATE ['.$this->tThreads.'] SET
                                     [sequence] = [sequence] + %i
                                   WHERE [id_forum] = %i
                                     AND [sequence] > %i',
                                   1, $this->forumId, $re->new_sequence);
       }
       // Bude se vkládat na konec tabulky
       else
       {
         $re = $this->connection->select('MAX([sequence])')->as('new_sequence')
                                ->select('%i', $re->new_depth)->as('new_depth')
                                ->from($this->tThreads)
                                ->where('[id_forum] = %i', $this->forumId)
                                ->fetch();
       }
     }
     // Pokud se nejedná o reakci, vloží se na konec
     else
     {
       $re = $this->connection->select('MAX([sequence])')->as('new_sequence')
                              ->select('%i', 0)->as('new_depth')
                              ->from($this->tThreads)
                              ->where('[id_forum] = %i', $this->forumId)
                              ->fetch();
     }

     $data['sequence'] = $re->new_sequence + 1;
     $data['depth'] = $re->new_depth;

     $this->connection->insert($this->tThreads, $data)->execute();
     $this->connection->query('UNLOCK TABLES');
   }

   /**
    * Test na existenci příspěvku
    *
    * @access public
    * @param int $topicId ID příspěvku
    * @return bool
    * @since 1.0.0
    */
   public function existsTopic($topicId)
   {
     return (bool) $this->connection->select('COUNT([id_thread])')
                                    ->from($this->tThreads)
                                    ->where('[id_forum] = %i', $this->forumId)
                                      ->and('[id_thread] = %i', $topicId)
                                    ->fetchSingle();
   }

   /**
    * Získání relativního času
    *
    * @author Jakub Vrána
    * @access public
    * @param mixed $time časový údaj
    * @return string
    * @static
    * @since 1.0.0
    */
   public static function timeAgoInWords($time)
   {
     if (!$time)
       return FALSE;
     elseif (is_numeric($time))
       $time = (int) $time;
     elseif ($time instanceof DateTime)
       $time = $time->format('U');
     else
       $time = strtotime($time);

     $delta = time() - $time;
     $delta = round($delta / 60);

     if ($delta == 0)
       return 'před okamžikem';

     if ($delta == 1)
       return 'před minutou';

     if ($delta < 45)
       return 'před '.$delta.' minutami';

     if ($delta < 90)
       return 'před hodinou';

     if ($delta < 1440)
       return 'před '.round($delta / 60).' hodinami';

     if ($delta < 2880)
       return 'včera';

     if ($delta < 43200)
       return 'před '.round($delta / 1440).' dny';

     if ($delta < 86400)
       return 'před měsícem';

     if ($delta < 525960)
       return 'před '.round($delta / 43200).' měsíci';

     if ($delta < 1051920)
       return 'před rokem';

     return 'před '.round($delta / 525960).' lety';
   }
 }
?>