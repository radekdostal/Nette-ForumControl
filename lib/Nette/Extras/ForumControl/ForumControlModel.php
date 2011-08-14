<?php
 /**
  * Forum Control Model
  *
  * @package   Nette\Extras\ForumControl
  * @version   $Id: ForumControlModel.php,v 1.1.0 2011/08/12 17:42:06 dostal Exp $
  * @author    Ing. Radek Dostál <radek.dostal@gmail.com>
  * @copyright Copyright (c) 2011 Radek Dostál
  * @license   GNU Lesser General Public License
  * @link      http://www.radekdostal.cz
  */

 namespace Nette\Extras\ForumControl;

 use Nette\Object;

 class ForumControlModel extends Object implements IForumControlModel
 {
   /**
    * Forum ID
    *
    * @access protected
    * @var int
    * @since 1.0.0
    */
   protected $forumId;

   /**
    * Instance of database connection
    *
    * @access protected
    * @var DibiConnection
    * @since 1.0.0
    */
   protected $connection;

   /**#@+
    * Database tables
    *
    * @access protected
    * @var string
    * @since 1.0.0
    */
   protected $tForum;
   protected $tThreads;
   /**#@- */

   /**
    * Initialization
    *
    * @access public
    * @param int $forumId forum ID
    * @param DibiConnection $connection instance of database connection
    * @return void
    * @since 1.0.0
    */
   public function __construct($forumId, \DibiConnection $connection)
   {
     $this->forumId = (int) $forumId;
     $this->connection = $connection;

     $this->tForum = 'forum';
     $this->tThreads = 'forum_threads';
   }

   /**
    * Returns count of topics
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
    * Returns forum title
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
    * Returns topic
    *
    * @access public
    * @param int $topicId topic ID
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
    * Returns selected topics
    *
    * @access public
    * @param array $topicIds array of topic ID's
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
                             ->orderBy('[sequence]', \dibi::ASC)
                             ->fetchAll();
   }

   /**
    * Returns threads
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
                             ->orderBy('[sequence]', \dibi::ASC)
                             ->fetchAll();
   }

   /**
    * Inserts topic
    *
    * @access public
    * @param array $data data
    * @param int $topicId topic ID to reply
    * @return void
    * @uses getTopic()
    * @since 1.0.0
    */
   public function insert(array $data, $topicId)
   {
     $data['id_forum'] = $this->forumId;

     $this->connection->query('LOCK TABLES ['.$this->tThreads.'] WRITE');

     $re = $this->getTopic($topicId);

     if ($topicId && $re !== FALSE)
     {
       $re = $this->connection->select('MIN([sequence]) - 1')->as('new_sequence')
                              ->select('%i + %i', $re->depth, 1)->as('new_depth')
                              ->from($this->tThreads)
                              ->where('[id_forum] = %i', $this->forumId)
                                ->and('[sequence] > %i', $re->sequence)
                                ->and('[depth] <= %i', $re->depth)
                              ->fetch();

       if ($re->new_sequence)
       {
         $this->connection->query('UPDATE ['.$this->tThreads.'] SET
                                     [sequence] = [sequence] + %i
                                   WHERE [id_forum] = %i
                                     AND [sequence] > %i',
                                   1, $this->forumId, $re->new_sequence);
       }
       else
       {
         $re = $this->connection->select('MAX([sequence])')->as('new_sequence')
                                ->select('%i', $re->new_depth)->as('new_depth')
                                ->from($this->tThreads)
                                ->where('[id_forum] = %i', $this->forumId)
                                ->fetch();
       }
     }
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
    * Exists topic?
    *
    * @access public
    * @param int $topicId topic ID
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
    * Gets relative time
    *
    * @author Jakub Vrána
    * @access public
    * @param mixed $time time
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
       return 'a while ago';

     if ($delta == 1)
       return 'a minute ago';

     if ($delta < 45)
       return $delta.' minutes ago';

     if ($delta < 90)
       return 'an hour ago';

     if ($delta < 1440)
       return round($delta / 60).' hours ago';

     if ($delta < 2880)
       return 'yesterday';

     if ($delta < 43200)
       return round($delta / 1440).' days ago';

     if ($delta < 86400)
       return 'last month';

     if ($delta < 525960)
       return round($delta / 43200).' months ago';

     if ($delta < 1051920)
       return 'last year';

     return round($delta / 525960).' years lety';
   }
 }
?>