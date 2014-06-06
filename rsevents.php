<?php
/**
 * @version 1.2.0
 * @package RSEvents! 1.2.0
 * @copyright (C) 2009 www.rsjoomla.com
 *                2014 Samuel Mehrbrodt
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * RSEvents Search plugin
 *
 * @package	    RSEvents
 * @subpackage  Search
 * @since       1.6
 */
class PlgSearchRsevents extends JPlugin
{
    /**
     * Constructor
     *
     * @access      protected
     * @param       object  $subject The object to observe
     * @param       array   $config  An array that holds the plugin configuration
     * @since       1.5
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    /**
     * @return array An array of search areas
     */
    function onContentSearchAreas()
    {
        static $areas = array(
            'rsevents' => 'PLG_SEARCH_RSEVENTS_SEARCHAREA'
        );
        return $areas;
    }

    /**
     * RSEvents Search method
     *
     * The sql must return the following fields that are
     * used in a common display routine: href, title, section, created, text,
     * browsernav
     * @param string Target search string
     * @param string mathcing option, exact|any|all
     * @param string ordering option, newest|oldest|popular|alpha|category
     * @param mixed An array if restricted to areas, null if search all
     */
    function onContentSearch($text, $phrase='', $ordering='', $areas=null)
    {
        $db		= JFactory::getDbo();
        $user	= JFactory::getUser();
        $app	= JFactory::getApplication();
        $searchText = $text;

        if (is_array($areas)) {
            if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
                return array();
            }
        }

        $limit = $this->params->def('search_limit',	50);


        $text = trim($text);
        if ($text == '')
            return array();

        switch ($ordering) {
            case 'alpha':
                $order = 'e.EventName ASC';
                break;
            case 'category':
            case 'popular':
            case 'newest':
            case 'oldest':
            default:
                $order = 'e.EventName DESC';
        }

        $text	= $db->Quote('%'.$db->getEscaped($text, true).'%', false);
        $query	= $db->getQuery(true);

        $return = array();

        $query->select("e.EventName AS title,
                        e.EventSubtitle as section,
                        e.EventDescription AS text,
                        e.EventStartDate AS created,
                        '2' AS browsernav,
                        e.IdEvent");
        $query->from('#__rsevents_events AS e');
        $query->where('(e.EventName LIKE '. $text .' OR e.EventDescription LIKE '. $text .') AND e.published = 1 ');
        $query->group('e.IdEvent');
        $query->order($order);

        $db->setQuery($query, 0, $limit);
        $rows = $db->loadObjectList();

        if ($rows)
        {
            $count = count($rows);
            for ($i = 0; $i < $count; $i++)
            {
                $rows[$i]->href = JRoute::_('index.php?option=com_rsevents&view=events&layout=show&cid='.$rows[$i]->IdEvent.':'.JFilterOutput::stringURLSafe($rows[$i]->title),false);
            }

            $return = array();
            foreach($rows AS $key => $category)
            {
                if (searchHelper::checkNoHTML($category, $searchText, array('name', 'title', 'text')))
                    $return[] = $category;
            }
        }

        return $return;
    }
}
